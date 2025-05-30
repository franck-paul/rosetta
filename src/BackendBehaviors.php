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
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\Page;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Details;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Summary;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
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
                'popup_posts_url' => App::backend()->url()->get('admin.posts.popup', [
                    'popup'     => 1,
                    'plugin_id' => My::id(),
                    'type'      => '',
                ], '&'),
                'plugin_url' => App::backend()->url()->get('admin.plugin.' . My::id(), [
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
     */
    public static function translationRow(string $src_lang, int $id, string $lang, string $name, string $title, string $post_link, string $url_page): Tr
    {
        return (new Tr())
            ->class(['line', 'wide'])
            ->cols([
                (new Td())
                    ->class(['minimal', 'nowrap'])
                    ->translate(false)
                    ->text($lang . ' - ' . $name),
                (new Td())
                    ->class('maximal')
                    // $post_link looks like <a href="index.php?process=Post&amp;id=%s" class="%s" title="%s">%s</a>
                    ->translate(false)
                    ->text(sprintf($post_link, (string) $id, __('Edit this entry'), '', Html::escapeHTML($title))),
                (new Td())
                    ->class(['minimal', 'nowrap'])
                    ->items([
                        (new Link(['delete']))
                            ->href($url_page . sprintf(self::$args_rosetta, $src_lang, '', 'remove', $id, $lang))
                            ->class('rosetta-remove')
                            ->title(__('Remove this translation\'s link'))
                            ->items([
                                (new Img(urldecode(Page::getPF(My::id() . '/img/unlink.png'))))
                                    ->alt(__('Remove this translation\'s link')),
                            ]),
                    ]),
            ]);
    }

    /**
     * @param      MetaRecord|null  $post       The post
     * @param      string           $post_type  The post type
     */
    private static function adminEntryForm(?MetaRecord $post, string $post_type = 'post'): string
    {
        $settings = My::settings();
        if ($settings->active) {
            if (!$post instanceof MetaRecord || !$post->post_id) {
                // Manage translation only on already created posts/pages
                return '';
            }

            if ($post_type === 'post') {
                $url = App::backend()->url()->get('admin.post', ['id' => $post->post_id]);
            } else {
                $url = App::backend()->url()->get('admin.plugin.pages', ['act' => 'page', 'id' => $post->post_id]);
            }

            $html_lines   = '';
            $translations = [];

            $list = CoreData::findAllTranslations((int) $post->post_id, $post->post_lang, false);
            if (is_array($list) && count($list)) {
                App::lexical()->lexicalKeySort($list, App::lexical()::ADMIN_LOCALE);

                $langs = L10n::getLanguagesName();
                foreach ($list as $lang => $id) {
                    // Display existing translations
                    $name = $langs[$lang] ?? $langs[App::blog()->settings()->system->lang];
                    // Get post/page id
                    $params = new ArrayObject([
                        'post_id'    => $id,
                        'post_type'  => $post_type,
                        'no_content' => true,
                    ]);
                    $rs = App::blog()->getPosts($params);
                    if ($rs->count()) {
                        $rs->fetch();
                        $translation = self::translationRow(
                            $post->post_lang,
                            $id,
                            $lang,
                            $name,
                            $rs->post_title,
                            App::backend()->post_link,
                            $url
                        );
                        $translations[] = $translation;
                        $html_lines .= $translation->render();
                    }
                }
            }

            echo (new Div('rosetta-area'))
                ->class('area')
                ->items([
                    (new Details('rosetta-details'))
                        ->open($translations !== [])
                        ->summary(new Summary($post_type === 'post' ? __('Post\'s translations:') : __('Page\'s translations:')))
                        ->items([
                            (new Div())
                                ->class('table-outer')
                                ->items([
                                    (new Table('rosetta-list'))
                                        ->class(['clear', 'maximal'])
                                        ->caption(new Caption(__('Attached Translations')))
                                        ->thead((new Thead())
                                            ->rows([
                                                (new Tr())
                                                    ->cols([
                                                        (new Th())
                                                            ->class('nowrap')
                                                            ->translate(false)
                                                            ->text(__('Language')),
                                                        (new Th())
                                                            ->text($post_type === 'post' ? __('Entry') : __('Page')),
                                                        (new Th())
                                                            ->class('nowrap'),
                                                    ]),
                                            ]))
                                        ->tbody((new Tbody())
                                            ->rows($translations)),
                                ]),
                            // Add a button for adding a new translation
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    (new Link())
                                        ->href($url . sprintf(
                                            self::$args_rosetta,
                                            ($post->post_lang == '' || !$post->post_lang ? App::blog()->settings()->system->lang : $post->post_lang),
                                            $post_type,
                                            'add',
                                            0,
                                            ''
                                        ))
                                        ->class(['button', 'rosetta-add'])
                                        ->text(__('Attach a translation')),
                                    (new Hidden('rosetta_url', '')),
                                ]),
                            // Add buttons to create (and edit) a new translation
                            (new Para())
                                ->class(['form-buttons', 'top-add', 'new-stuff'])   // 'top-add' should be removed when 2.35 were released
                                ->items([
                                    (new Link())
                                        ->href($url . sprintf(
                                            self::$args_rosetta,
                                            ($post->post_lang == '' || !$post->post_lang ? App::blog()->settings()->system->lang : $post->post_lang),
                                            $post_type,
                                            'new',
                                            0,
                                            ''
                                        ) . '&amp;edit=0')
                                        ->class(['button', 'add', 'rosetta-new'])
                                        ->text(__('Create a new translation')),
                                    (new Link())
                                        ->href($url . sprintf(
                                            self::$args_rosetta,
                                            ($post->post_lang == '' || !$post->post_lang ? App::blog()->settings()->system->lang : $post->post_lang),
                                            $post_type,
                                            'new_edit',
                                            0,
                                            ''
                                        ) . '&amp;edit=1')
                                        ->class(['button', 'add', 'rosetta-new'])
                                        ->text(__('Create and edit a new translation')),
                                    (new Hidden('rosetta_title', '')),
                                    (new Hidden('rosetta_lang', '')),
                                ]),
                        ]),
                ])
            ->render();
        }

        return '';
    }

    /**
     * @param      MetaRecord|null  $post   The post
     */
    public static function adminPostForm(?MetaRecord $post): string
    {
        return self::adminEntryForm($post, 'post');
    }

    /**
     * @param      MetaRecord|null  $post   The post
     */
    public static function adminPageForm(?MetaRecord $post): string
    {
        return self::adminEntryForm($post, 'page');
    }

    public static function adminPopupPosts(string $editor = ''): string
    {
        if ($editor === '' || $editor != My::id()) {
            return '';
        }

        return My::jsLoad('popup_posts.js');
    }

    /**
     * @param      ArrayObject<string, mixed>  $cols   The cols
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
     */
    private static function adminEntryListHeader(ArrayObject $cols): string
    {
        $settings = My::settings();
        if ($settings->active) {
            $cols['language'] = (new Th())
                ->scope('col')
                ->class('nowrap')
                ->translate(false)
                ->text(__('Language'))
            ->render();
            $cols['translations'] = (new Th())
                ->scope('col')
                ->class('nowrap')
                ->translate(false)
                ->text(__('Translations'))
            ->render();
        }

        return '';
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     */
    public static function adminPostListHeader(MetaRecord $rs, ArrayObject $cols): string
    {
        return self::adminEntryListHeader($cols);
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     */
    public static function adminPagesListHeader(MetaRecord $rs, ArrayObject $cols): string
    {
        return self::adminEntryListHeader($cols);
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     */
    private static function adminEntryListValue(MetaRecord $rs, ArrayObject $cols): string
    {
        $settings = My::settings();
        if ($settings->active) {
            $translations = [];
            $list         = CoreData::findAllTranslations((int) $rs->post_id, $rs->post_lang, false);
            if (is_array($list) && count($list)) {
                App::lexical()->lexicalKeySort($list, App::lexical()::ADMIN_LOCALE);
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
                        $translations[] = (new Link())
                            ->href(App::postTypes()->get($rs->post_type)->adminUrl($rs->post_id))
                            ->title($rst->post_title)
                            ->translate(false)
                            ->text($name);
                    }
                }
            }

            $cols['language'] = (new Td())
                ->class('nowrap')
                ->text($rs->post_lang)
                ->translate(false)
            ->render();
            $cols['translations'] = (new Td())
                ->class('nowrap')
                ->translate(false)
                ->items([
                    (new Span(implode(', ', array_map(fn ($translation) => $translation->render(), $translations)))),
                ])
            ->render();
        }

        return '';
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     */
    public static function adminPostListValue(MetaRecord $rs, ArrayObject $cols): string
    {
        return self::adminEntryListValue($rs, $cols);
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     */
    public static function adminPagesListValue(MetaRecord $rs, ArrayObject $cols): string
    {
        return self::adminEntryListValue($rs, $cols);
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     */
    public static function adminPostMiniListHeader(MetaRecord $rs, ArrayObject $cols): string
    {
        $settings = My::settings();
        if ($settings->active) {
            $cols['language'] = (new Th())
                ->scope('col')
                ->class('nowrap')
                ->text(__('Language'))
                ->translate(false)
            ->render();
        }

        return '';
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     */
    public static function adminPostMiniListValue(MetaRecord $rs, ArrayObject $cols): string
    {
        $settings = My::settings();
        if ($settings->active) {
            $cols['language'] = (new Td())
                ->class('nowrap')
                ->text($rs->post_lang)
                ->translate(false)
            ->render();
        }

        return '';
    }

    /**
     * @param      ArrayObject<string, mixed>  $sorts  The sorts
     */
    public static function adminFiltersLists(ArrayObject $sorts): string
    {
        // TODO when 1st and 2nd tab of index will be developped, if necessary
        return '';
    }

    public static function exportSingle(FlatExport $exp, string $blog_id): string
    {
        $sql = new SelectStatement();
        $sql
            ->column('R.*')
            ->from([
                $sql->as(App::con()->prefix() . CoreData::ROSETTA_TABLE_NAME, 'R'),
                $sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'),
            ])
            ->where('P.post_id = R.src_id')
            ->and('P.blog_id = ' . $sql->quote($blog_id))
        ;

        $exp->export(
            CoreData::ROSETTA_TABLE_NAME,
            $sql->statement()
        );

        return '';
    }

    public static function exportFull(FlatExport $exp): string
    {
        $exp->exportTable(CoreData::ROSETTA_TABLE_NAME);

        return '';
    }

    public static function importInit(FlatImportV2 $fi): string
    {
        $fi->setExtraCursor(CoreData::ROSETTA_TABLE_NAME, App::con()->openCursor(App::con()->prefix() . CoreData::ROSETTA_TABLE_NAME));

        return '';
    }

    private static function insertRosettaLine(FlatBackupItem $line, FlatImportV2 $fi): void
    {
        $cur = $fi->getExtraCursor(CoreData::ROSETTA_TABLE_NAME);
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
        if ($line->__name == CoreData::ROSETTA_TABLE_NAME) {
            if (isset($fi->old_ids['post'][(int) $line->src_id]) && isset($fi->old_ids['post'][(int) $line->dst_id])) { // @phpstan-ignore-line
                $line->src_id = $fi->old_ids['post'][(int) $line->src_id];  // @phpstan-ignore-line
                $line->dst_id = $fi->old_ids['post'][(int) $line->dst_id];  // @phpstan-ignore-line
                self::insertRosettaLine($line, $fi);
            } else {
                throw new Exception(sprintf(
                    __('ID of "%3$s" does not match on record "%1$s" at line %2$s of backup file.'),
                    Html::escapeHTML($line->__name),
                    Html::escapeHTML((string) $line->__line),
                    Html::escapeHTML(CoreData::ROSETTA_TABLE_NAME)
                ));
            }
        }

        return '';
    }

    public static function importFull(FlatBackupItem $line, FlatImportV2 $fi): string
    {
        if ($line->__name == CoreData::ROSETTA_TABLE_NAME) {
            self::insertRosettaLine($line, $fi);
        }

        return '';
    }
}
