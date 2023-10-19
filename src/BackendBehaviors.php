<?php
/**
 * @brief rosetta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\rosetta;

use ArrayObject;
use dcCore;
use dcUtils;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\Page;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Plugin\importExport\FlatBackupItem;
use Dotclear\Plugin\importExport\FlatExport;
use Dotclear\Plugin\importExport\FlatImportV2;
use Exception;

// Admin behaviours

class BackendBehaviors
{
    public static string $args_rosetta = '&amp;lang=%s&amp;type=%s&amp;rosetta=%s&amp;rosetta_id=%s&amp;rosetta_lang=%s';

    public static function adminDashboardFavorites(Favorites $favs): string
    {
        $favs->register('rosetta', [
            'title'       => __('Rosetta'),
            'url'         => My::manageUrl(),
            'small-icon'  => My::icons(),
            'large-icon'  => My::icons(),
            'permissions' => My::checkContext(My::MENU),
        ]);

        return '';
    }

    private static function adminEntryHeaders(): string
    {
        return
        Page::jsJson('rosetta_entry', [
            'msg'     => ['confirm_remove_rosetta' => __('Are you sure to remove this translation?')],
            'rosetta' => [
                'popup_posts_url' => dcCore::app()->adminurl->get('admin.posts.popup', [
                    'popup'     => 1,
                    'plugin_id' => 'rosetta',
                    'type'      => '',
                ], '&'),
                'plugin_url' => dcCore::app()->adminurl->get('admin.plugin.' . My::id(), [
                    'popup_new' => 1,
                    'popup'     => 1,
                ], '&'),
            ],
        ]) .
        My::jsLoad('rosetta_entry.js') .
        My::cssLoad('style.css');
    }

    public static function adminPostHeaders(): string
    {
        $settings = My::settings();
        if ($settings->active) {
            return
            Page::jsJson('rosetta_type', ['post_type' => 'post']) .
            self::adminEntryHeaders();
        }

        return '';
    }

    public static function adminPageHeaders(): string
    {
        $settings = My::settings();
        if ($settings->active) {
            return
            Page::jsJson('rosetta_type', ['post_type' => 'page']) .
            self::adminEntryHeaders();
        }

        return '';
    }

    /**
     * Get a full row for one translation
     *
     * @param  string $src_lang  source language (from currently edited post or page)
     * @param  int    $id        source id (post or page)
     * @param  string $lang      translation language code
     * @param  string $name      translation language name
     * @param  string $title     title of translated post or page
     * @param  string $post_link sprintf format for post/page edition (post-id, label, post-title)
     * @param  string $url_page  current admin page URL
     *
     * @return string            row (<tr>…</tr>)
     */
    public static function translationRow(string $src_lang, int $id, string $lang, string $name, string $title, string $post_link, string $url_page): string
    {
        $html_line = '<tr class="line wide">' . "\n" .
        '<td class="minimal nowrap">%s</td>' . "\n" . // language
        '<td class="maximal">%s</td>' . "\n" .        // Entry link
        '<td class="minimal nowrap">%s</td>' . "\n" . // Action
        '</tr>' . "\n";
        $action_remove = '<a href="%s" class="rosetta-remove" title="' . __('Remove this translation\'s link') . '" name="delete">' .
        '<img src="' . urldecode(Page::getPF(My::id() . '/img/unlink.png')) .
        '" alt="' . __('Remove this translation\'s link') . '" /></a>';

        return sprintf(
            $html_line,
            $lang . ' - ' . $name,
            sprintf($post_link, (string) $id, __('Edit this entry'), Html::escapeHTML($title)),
            sprintf($action_remove, $url_page . sprintf(self::$args_rosetta, $src_lang, '', 'remove', $id, $lang))
        );
    }

    /**
     * @param      MetaRecord|null  $post       The post
     * @param      string           $post_type  The post type
     *
     * @return     string
     */
    private static function adminEntryForm(?MetaRecord $post, string $post_type = 'post'): string
    {
        $settings = My::settings();
        if ($settings->active) {
            if (!$post || !$post->post_id) {
                // Manage translation only on already created posts/pages
                return '';
            }

            if ($post_type == 'post') {
                $url = dcCore::app()->adminurl->get('admin.post', ['id' => $post->post_id]);
            } else {
                $url = dcCore::app()->adminurl->get('admin.plugin.pages', ['act' => 'page', 'id' => $post->post_id]);
            }

            $html_lines = '';

            $list = CoreData::findAllTranslations((int) $post->post_id, $post->post_lang, false);
            if (is_array($list) && count($list)) {
                dcUtils::lexicalKeySort($list, dcUtils::ADMIN_LOCALE);

                $langs = L10n::getLanguagesName();
                foreach ($list as $lang => $id) {
                    // Display existing translations
                    $name = $langs[$lang] ?? $langs[App::blog()->settings()->system->lang];
                    // Get post/page id
                    $params = new ArrayObject([
                        'post_id'    => $id,
                        'post_type'  => $post_type,
                        'no_content' => true, ]);
                    $rs = App::blog()->getPosts($params);
                    if ($rs->count()) {
                        $rs->fetch();
                        $html_lines .= self::translationRow(
                            $post->post_lang,
                            $id,
                            $lang,
                            $name,
                            $rs->post_title,
                            dcCore::app()->admin->post_link,    // see plugins/pages/page.php and admin/post.php
                            $url
                        );
                    }
                }
            }

            $html_block = '<div class="table-outer">' .
            '<table id="rosetta-list" summary="' . __('Attached Translations') . '" class="clear maximal">' .
            '<thead>' .
            '<tr>' .
            '<th class="nowrap">' . __('Language') . '</th>' .
            '<th>' . ($post_type == 'post' ? __('Entry') : __('Page')) . '</th>' .
            '<th class="nowrap">' . '</th>' .
            '</tr>' .
            '</thead>' .
            '<tbody>%s</tbody>' .
            '</table>' .
            '</div>';

            echo
            '<div id="rosetta-area" class="area">' . "\n" .
            '<details id="rosetta-details"' . ($html_lines !== '' ? ' open ' : '') . '>' .
            '<summary>' .
            ($post_type == 'post' ? __('Post\'s translations:') : __('Page\'s translations:')) .
            '</summary>' . "\n";

            // Display table
            echo sprintf($html_block, $html_lines);

            // Add a button for adding a new translation
            $action_add = '<a href="%s" class="button rosetta-add">' . __('Attach a translation') . '</a>';

            echo '<p>' .
            // Button
            sprintf($action_add, $url .
                sprintf(
                    self::$args_rosetta,
                    ($post->post_lang == '' || !$post->post_lang ? App::blog()->settings()->system->lang : $post->post_lang),
                    $post_type,
                    'add',
                    0,
                    ''
                )) .
            // Hidden field for selected post/page URL
            (new Hidden('rosetta_url', ''))->render() .
            '</p>';

            // Add a field (title), a combo (lang) and a button to create a new translation
            $action_new      = '<a href="%s" class="button add rosetta-new">' . __('Create a new translation') . '</a>';
            $action_new_edit = '<a href="%s" class="button add rosetta-new">' . __('Create and edit a new translation') . '</a>';

            echo
            '<p class="top-add">' .
            sprintf($action_new, $url .
                sprintf(
                    self::$args_rosetta,
                    ($post->post_lang == '' || !$post->post_lang ? App::blog()->settings()->system->lang : $post->post_lang),
                    $post_type,
                    'new',
                    0,
                    ''
                ) .
                '&amp;edit=0') .
            ' ' .
            sprintf($action_new_edit, $url .
                sprintf(
                    self::$args_rosetta,
                    ($post->post_lang == '' || !$post->post_lang ? App::blog()->settings()->system->lang : $post->post_lang),
                    $post_type,
                    'new_edit',
                    0,
                    ''
                ) .
                '&amp;edit=1') .
            // Hidden fields for new entry title and lang
            (new Hidden('rosetta_title', ''))->render() .
            (new Hidden('rosetta_lang', ''))->render() .
            '</p>';

            echo
            '</details>' .
            '</div>' . "\n";
        }

        return '';
    }

    /**
     * @param      MetaRecord|null  $post   The post
     *
     * @return     string
     */
    public static function adminPostForm(?MetaRecord $post): string
    {
        return self::adminEntryForm($post, 'post');
    }

    /**
     * @param      MetaRecord|null  $post   The post
     *
     * @return     string
     */
    public static function adminPageForm(?MetaRecord $post): string
    {
        return self::adminEntryForm($post, 'page');
    }

    public static function adminPopupPosts(string $editor = ''): string
    {
        if (empty($editor) || $editor != My::id()) {
            return '';
        }

        return My::jsLoad('popup_posts.js');
    }

    /**
     * @param      ArrayObject<string, mixed>  $cols   The cols
     *
     * @return     string
     */
    public static function adminColumnsLists(ArrayObject $cols): string
    {
        $cols['posts'][1]['language']     = [true, __('Language')];
        $cols['posts'][1]['translations'] = [true, __('Translations')];
        $cols['pages'][1]['language']     = [true, __('Language')];
        $cols['pages'][1]['translations'] = [true, __('Translations')];

        return '';
    }

    /**
     * @param      ArrayObject<string, string>    $cols   The cols
     *
     * @return     string
     */
    private static function adminEntryListHeader(ArrayObject $cols): string
    {
        $settings = My::settings();
        if ($settings->active) {
            $cols['language']     = '<th scope="col">' . __('Language') . '</th>';
            $cols['translations'] = '<th scope="col">' . __('Translations') . '</th>';
        }

        return '';
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     *
     * @return     string
     */
    public static function adminPostListHeader(MetaRecord $rs, ArrayObject $cols): string
    {
        return self::adminEntryListHeader($cols);
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     *
     * @return     string
     */
    public static function adminPagesListHeader(MetaRecord $rs, ArrayObject $cols): string
    {
        return self::adminEntryListHeader($cols);
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     *
     * @return     string
     */
    private static function adminEntryListValue(MetaRecord $rs, ArrayObject $cols): string
    {
        $settings = My::settings();
        if ($settings->active) {
            $translations = '';
            $list         = CoreData::findAllTranslations((int) $rs->post_id, $rs->post_lang, false);
            if (is_array($list) && count($list)) {
                dcUtils::lexicalKeySort($list, dcUtils::ADMIN_LOCALE);
                $langs = L10n::getLanguagesName();
                foreach ($list as $lang => $id) {
                    // Display existing translations
                    $name = $langs[$lang] ?? $langs[App::blog()->settings()->system->lang];
                    // Get post/page id
                    $params = new ArrayObject([
                        'post_id'    => $id,
                        'post_type'  => $rs->post_type,
                        'no_content' => true, ]);
                    $rst = App::blog()->getPosts($params);
                    if ($rst->count()) {
                        $rst->fetch();
                        $translation = sprintf(
                            '<a href="%s" title="%s">%s</a>',
                            dcCore::app()->getPostAdminURL($rst->post_type, $rst->post_id),
                            $rst->post_title,
                            $name
                        );
                        $translations .= ($translations ? ' / ' : '') . $translation;
                    }
                }
            }

            $cols['language']     = '<td class="nowrap">' . $rs->post_lang . '</td>';
            $cols['translations'] = '<td class="nowrap">' . $translations . '</td>';
        }

        return '';
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     *
     * @return     string
     */
    public static function adminPostListValue(MetaRecord $rs, ArrayObject $cols): string
    {
        return self::adminEntryListValue($rs, $cols);
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     *
     * @return     string
     */
    public static function adminPagesListValue(MetaRecord $rs, ArrayObject $cols): string
    {
        return self::adminEntryListValue($rs, $cols);
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     *
     * @return     string
     */
    public static function adminPostMiniListHeader(MetaRecord $rs, ArrayObject $cols): string
    {
        $settings = My::settings();
        if ($settings->active) {
            $cols['language'] = '<th scope="col">' . __('Language') . '</th>';
        }

        return '';
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     *
     * @return     string
     */
    public static function adminPostMiniListValue(MetaRecord $rs, ArrayObject $cols): string
    {
        $settings = My::settings();
        if ($settings->active) {
            $cols['language'] = '<td class="nowrap">' . $rs->post_lang . '</td>';
        }

        return '';
    }

    /**
     * @param      ArrayObject<string, mixed>  $sorts  The sorts
     *
     * @return     string
     */
    public static function adminFiltersLists(ArrayObject $sorts): string
    {
        // TODO when 1st and 2nd tab of index will be developped, if necessary
        return '';
    }

    public static function exportSingle(FlatExport $exp, string $blog_id): string
    {
        $exp->export(
            'rosetta',
            'SELECT R.* ' .
            'FROM ' . dcCore::app()->prefix . 'rosetta R, ' . dcCore::app()->prefix . 'post P ' .
            'WHERE P.post_id = R.src_id ' .
            "AND P.blog_id = '" . $blog_id . "'"
        );

        return '';
    }

    public static function exportFull(FlatExport $exp): string
    {
        $exp->exportTable('rosetta');

        return '';
    }

    public static function importInit(FlatImportV2 $fi): string
    {
        $fi->setExtraCursor('rosetta', dcCore::app()->con->openCursor(dcCore::app()->prefix . 'rosetta'));

        return '';
    }

    private static function insertRosettaLine(FlatBackupItem $line, FlatImportV2 $fi): void
    {
        $cur = $fi->getExtraCursor('rosetta');
        if (!is_null($cur)) {
            $cur->clean();

            $cur->src_id   = (int) $line->src_id;   // @phpstan-ignore-line
            $cur->src_lang = (string) $line->src_lang;  // @phpstan-ignore-line
            $cur->dst_id   = (int) $line->dst_id;   // @phpstan-ignore-line
            $cur->dst_lang = (string) $line->dst_lang;  // @phpstan-ignore-line

            $cur->insert();
        }
    }

    public static function importSingle(FlatBackupItem $line, FlatImportV2 $fi): string
    {
        if ($line->__name == 'rosetta') {
            if (isset($fi->old_ids['post'][(int) $line->src_id]) && isset($fi->old_ids['post'][(int) $line->dst_id])) { // @phpstan-ignore-line
                $line->src_id = $fi->old_ids['post'][(int) $line->src_id];  // @phpstan-ignore-line
                $line->dst_id = $fi->old_ids['post'][(int) $line->dst_id];  // @phpstan-ignore-line
                self::insertRosettaLine($line, $fi);
            } else {
                throw new Exception(sprintf(
                    __('ID of "%3$s" does not match on record "%1$s" at line %2$s of backup file.'),
                    Html::escapeHTML($line->__name),
                    Html::escapeHTML((string) $line->__line),
                    Html::escapeHTML('rosetta')
                ));
            }
        }

        return '';
    }

    public static function importFull(FlatBackupItem $line, FlatImportV2 $fi): string
    {
        if ($line->__name == 'rosetta') {
            self::insertRosettaLine($line, $fi);
        }

        return '';
    }
}
