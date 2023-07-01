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
use dcPage;
use dcUtils;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Exception;
use form;

// Admin behaviours

class BackendBehaviors
{
    public static $args_rosetta = '&amp;lang=%s&amp;type=%s&amp;rosetta=%s&amp;rosetta_id=%s&amp;rosetta_lang=%s';

    public static function adminDashboardFavorites($favs)
    {
        $favs->register('rosetta', [
            'title'      => __('Rosetta'),
            'url'        => My::makeUrl(),
            'small-icon' => My::icons(),
            'large-icon' => My::icons(),
            My::checkContext(My::MENU),
        ]);
    }

    private static function adminEntryHeaders()
    {
        return
        dcPage::jsJson('rosetta_entry', [
            'msg'     => ['confirm_remove_rosetta' => __('Are you sure to remove this translation?')],
            'rosetta' => [
                'popup_posts_url' => dcCore::app()->adminurl->get('admin.popup_posts', [
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
        dcPage::jsModuleLoad(My::id() . '/js/rosetta_entry.js', dcCore::app()->getVersion(My::id())) . "\n" .
        dcPage::cssModuleLoad(My::id() . '/css/style.css', dcCore::app()->getVersion(My::id())) . "\n";
    }

    public static function adminPostHeaders()
    {
        $settings = dcCore::app()->blog->settings->get(My::id());
        if ($settings->active) {
            return
            dcPage::jsJson('rosetta_type', ['post_type' => 'post']) .
            self::adminEntryHeaders();
        }
    }

    public static function adminPageHeaders()
    {
        $settings = dcCore::app()->blog->settings->get(My::id());
        if ($settings->active) {
            return
            dcPage::jsJson('rosetta_type', ['post_type' => 'page']) .
            self::adminEntryHeaders();
        }
    }

    /**
     * Get a full row for one translation
     *
     * @param  string $src_lang  source language (from currently edited post or page)
     * @param  string $id        source id (post or page)
     * @param  string $lang      translation language code
     * @param  string $name      translation language name
     * @param  string $title     title of translated post or page
     * @param  string $post_link sprintf format for post/page edition (post-id, label, post-title)
     * @param  string $url_page  current admin page URL
     * @return string            row (<tr>…</tr>)
     */
    public static function translationRow($src_lang, $id, $lang, $name, $title, $post_link, $url_page)
    {
        $html_line = '<tr class="line wide">' . "\n" .
        '<td class="minimal nowrap">%s</td>' . "\n" . // language
        '<td class="maximal">%s</td>' . "\n" .        // Entry link
        '<td class="minimal nowrap">%s</td>' . "\n" . // Action
        '</tr>' . "\n";
        $action_remove = '<a href="%s" class="rosetta-remove" title="' . __('Remove this translation\'s link') . '" name="delete">' .
        '<img src="' . urldecode(dcPage::getPF(My::id() . '/img/unlink.png')) .
        '" alt="' . __('Remove this translation\'s link') . '" /></a>';

        return sprintf(
            $html_line,
            $lang . ' - ' . $name,
            sprintf($post_link, $id, __('Edit this entry'), Html::escapeHTML($title)),
            sprintf($action_remove, $url_page . sprintf(self::$args_rosetta, $src_lang, '', 'remove', $id, $lang))
        );
    }

    private static function adminEntryForm($post, $post_type = 'post')
    {
        $settings = dcCore::app()->blog->settings->get(My::id());
        if ($settings->active) {
            if (!$post || !$post->post_id) {
                // Manage translation only on already created posts/pages
                return;
            }

            echo
            '<div id="rosetta-area" class="area">' . "\n" .
            '<details id="rosetta-details">' .
            '<summary>' .
            ($post_type == 'post' ? __('Post\'s translations:') : __('Page\'s translations:')) .
            '</summary>' . "\n";

            if ($post_type == 'post') {
                $url = dcCore::app()->adminurl->get('admin.post', ['id' => $post->post_id]);
            } else {
                $url = dcCore::app()->adminurl->get('admin.plugin.pages', ['act' => 'page', 'id' => $post->post_id]);
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
            $html_lines = '';

            $list = CoreData::findAllTranslations($post->post_id, $post->post_lang, false);
            if (is_array($list) && count($list)) {
                dcUtils::lexicalKeySort($list, dcUtils::ADMIN_LOCALE);

                $langs = L10n::getLanguagesName();
                foreach ($list as $lang => $id) {
                    // Display existing translations
                    $name = $langs[$lang] ?? $langs[dcCore::app()->blog->settings->system->lang];
                    // Get post/page id
                    $params = new ArrayObject([
                        'post_id'    => $id,
                        'post_type'  => $post_type,
                        'no_content' => true, ]);
                    $rs = dcCore::app()->blog->getPosts($params);
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

            // Display table
            echo sprintf($html_block, $html_lines);

            // Add a button for adding a new translation
            $action_add = '<a href="%s" class="button rosetta-add">' . __('Attach a translation') . '</a>';

            echo '<p>' .
            // Button
            sprintf($action_add, $url .
                sprintf(
                    self::$args_rosetta,
                    ($post->post_lang == '' || !$post->post_lang ? dcCore::app()->blog->settings->system->lang : $post->post_lang),
                    $post_type,
                    'add',
                    0,
                    ''
                )) .
            // Hidden field for selected post/page URL
            form::hidden(['rosetta_url', 'rosetta_url'], '') .
                '</p>';

            // Add a field (title), a combo (lang) and a button to create a new translation
            $action_new      = '<a href="%s" class="button add rosetta-new">' . __('Create a new translation') . '</a>';
            $action_new_edit = '<a href="%s" class="button add rosetta-new">' . __('Create and edit a new translation') . '</a>';

            echo
            '<p class="top-add">' .
            sprintf($action_new, $url .
                sprintf(
                    self::$args_rosetta,
                    ($post->post_lang == '' || !$post->post_lang ? dcCore::app()->blog->settings->system->lang : $post->post_lang),
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
                    ($post->post_lang == '' || !$post->post_lang ? dcCore::app()->blog->settings->system->lang : $post->post_lang),
                    $post_type,
                    'new_edit',
                    0,
                    ''
                ) .
                '&amp;edit=1') .
            // Hidden fields for new entry title and lang
            form::hidden(['rosetta_title', 'rosetta_title'], '') .
            form::hidden(['rosetta_lang', 'rosetta_lang'], '') .
                '</p>';

            echo
            '</details>' .
            '</div>' . "\n";
        }
    }

    public static function adminPostForm($post)
    {
        self::adminEntryForm($post, 'post');
    }

    public static function adminPageForm($post)
    {
        self::adminEntryForm($post, 'page');
    }

    public static function adminPopupPosts($editor = '')
    {
        if (empty($editor) || $editor != My::id()) {
            return;
        }

        return dcPage::jsModuleLoad(My::id() . '/js/popup_posts.js', dcCore::app()->getVersion(My::id()));
    }

    public static function adminColumnsLists($cols)
    {
        $cols['posts'][1]['language']     = [true, __('Language')];
        $cols['posts'][1]['translations'] = [true, __('Translations')];
        $cols['pages'][1]['language']     = [true, __('Language')];
        $cols['pages'][1]['translations'] = [true, __('Translations')];
    }

    private static function adminEntryListHeader($core, $rs, $cols)
    {
        $settings = dcCore::app()->blog->settings->get(My::id());
        if ($settings->active) {
            $cols['language']     = '<th scope="col">' . __('Language') . '</th>';
            $cols['translations'] = '<th scope="col">' . __('Translations') . '</th>';
        }
    }

    public static function adminPostListHeader($rs, $cols)
    {
        self::adminEntryListHeader(dcCore::app(), $rs, $cols);
    }

    public static function adminPagesListHeader($rs, $cols)
    {
        self::adminEntryListHeader(dcCore::app(), $rs, $cols);
    }

    public static function adminEntryListValue($core, $rs, $cols)
    {
        $settings = dcCore::app()->blog->settings->get(My::id());
        if ($settings->active) {
            $translations = '';
            $list         = CoreData::findAllTranslations($rs->post_id, $rs->post_lang, false);
            if (is_array($list) && count($list)) {
                dcUtils::lexicalKeySort($list, dcUtils::ADMIN_LOCALE);
                $langs = L10n::getLanguagesName();
                foreach ($list as $lang => $id) {
                    // Display existing translations
                    $name = $langs[$lang] ?? $langs[dcCore::app()->blog->settings->system->lang];
                    // Get post/page id
                    $params = new ArrayObject([
                        'post_id'    => $id,
                        'post_type'  => $rs->post_type,
                        'no_content' => true, ]);
                    $rst = dcCore::app()->blog->getPosts($params);
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
    }

    public static function adminPostListValue($rs, $cols)
    {
        self::adminEntryListValue(dcCore::app(), $rs, $cols);
    }

    public static function adminPagesListValue($rs, $cols)
    {
        self::adminEntryListValue(dcCore::app(), $rs, $cols);
    }

    public static function adminPostMiniListHeader($rs, $cols)
    {
        $settings = dcCore::app()->blog->settings->get(My::id());
        if ($settings->active) {
            $cols['language'] = '<th scope="col">' . __('Language') . '</th>';
        }
    }

    public static function adminPostMiniListValue($core, $rs, $cols)
    {
        $settings = dcCore::app()->blog->settings->get(My::id());
        if ($settings->active) {
            $cols['language'] = '<td class="nowrap">' . $rs->post_lang . '</td>';
        }
    }

    public static function adminFiltersLists($sorts)
    {
        // TODO when 1st and 2nd tab of index will be developped, if necessary
    }

    public static function exportSingle($exp, $blog_id)
    {
        $exp->export(
            'rosetta',
            'SELECT R.* ' .
            'FROM ' . dcCore::app()->prefix . 'rosetta R, ' . dcCore::app()->prefix . 'post P ' .
            'WHERE P.post_id = R.src_id ' .
            "AND P.blog_id = '" . $blog_id . "'"
        );
    }

    public static function exportFull($exp)
    {
        $exp->exportTable('rosetta');
    }

    public static function importInit($fi)
    {
        $fi->cur_rosetta = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'rosetta');
    }

    private static function insertRosettaLine($line, $fi)
    {
        $fi->cur_rosetta->clean();

        $fi->cur_rosetta->src_id   = (int) $line->src_id;
        $fi->cur_rosetta->src_lang = (string) $line->src_lang;
        $fi->cur_rosetta->dst_id   = (int) $line->dst_id;
        $fi->cur_rosetta->dst_lang = (string) $line->dst_lang;

        $fi->cur_rosetta->insert();
    }

    public static function importSingle($line, $fi)
    {
        if ($line->__name == 'rosetta') {
            if (isset($fi->old_ids['post'][(int) $line->src_id]) && isset($fi->old_ids['post'][(int) $line->dst_id])) {
                $line->src_id = $fi->old_ids['post'][(int) $line->src_id];
                $line->dst_id = $fi->old_ids['post'][(int) $line->dst_id];
                self::insertRosettaLine($line, $fi);
            } else {
                throw new Exception(sprintf(
                    __('ID of "%3$s" does not match on record "%1$s" at line %2$s of backup file.'),
                    Html::escapeHTML($line->__name),
                    Html::escapeHTML($line->__line),
                    Html::escapeHTML('rosetta')
                ));
            }
        }
    }

    public static function importFull($line, $fi)
    {
        if ($line->__name == 'rosetta') {
            self::insertRosettaLine($line, $fi);
        }
    }
}
