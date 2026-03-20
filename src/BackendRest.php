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
use Exception;

class BackendRest
{
    /**
     * Serve method to add a new translation for current edited post/page. (JSON)
     *
     * @param      array<string, mixed>   $get    The cleaned $_GET
     * @param      array<string, mixed>   $post   The cleaned $_POST
     *
     * @return     array<string, mixed>
     */
    public static function newTranslation(array $get, array $post): array
    {
        $_Int = fn (string $name, int $default = 0): int => isset($post[$name]) && is_numeric($val = $post[$name]) ? (int) $val : $default;
        $_Str = fn (string $name, string $default = ''): string => isset($post[$name]) && is_string($val = $post[$name]) ? $val : $default;

        $id            = $_Int('id', -1);
        $lang          = $_Str('lang', '');
        $type          = $_Str('type', 'post');
        $rosetta_title = $_Str('rosetta_title', '');
        $rosetta_lang  = $_Str('rosetta_lang', '');

        $ret        = false;
        $rosetta_id = -1;
        if ($id !== -1 && $lang !== '' && $rosetta_title !== '' && $rosetta_lang !== '') {
            try {
                // Default format and content
                $format  = 'xhtml';
                $content = '<p>…</p>';

                // Get currently edited post format
                $rs = App::blog()->getPosts(['post_id' => $id]);
                if (!$rs->isEmpty()) {
                    $rs->fetch();
                    $format = $rs->post_format;
                    if ($format != 'xhtml') {
                        $content = '…';
                    }
                }

                $allow_comments   = is_bool($allow_comments = App::blog()->settings()->system->allow_comments)     && $allow_comments;
                $allow_trackbacks = is_bool($allow_trackbacks = App::blog()->settings()->system->allow_trackbacks) && $allow_trackbacks;

                // Create a new entry with given title and lang
                $cur = App::db()->con()->openCursor(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME);

                $cur->post_title = $rosetta_title;
                $cur->post_type  = $type;
                $cur->post_lang  = $rosetta_lang;

                $cur->user_id           = App::auth()->userID();
                $cur->post_content      = $content;
                $cur->post_format       = $format;
                $cur->post_status       = App::status()->post()::PENDING; // forced to pending
                $cur->post_open_comment = (int) $allow_comments;
                $cur->post_open_tb      = (int) $allow_trackbacks;

                # --BEHAVIOR-- adminBeforePostCreate
                App::behavior()->callBehavior('adminBeforePostCreate', $cur);

                $rosetta_id = App::blog()->addPost($cur);

                # --BEHAVIOR-- adminAfterPostCreate
                App::behavior()->callBehavior('adminAfterPostCreate', $cur, $rosetta_id);

                // add the translation link
                $ret = CoreData::addTranslation($id, $lang, $rosetta_id, $rosetta_lang);
            } catch (Exception) {
                $rosetta_id = -1;
            }
        }

        return [
            'ret' => $ret,
            'msg' => ($ret ? __('New translation created.') : ($rosetta_id === -1 ?
                __('Error during new translation creation.') :
                __('Error during newly created translation attachment.'))),
            'id'   => $rosetta_id,
            'edit' => App::config()->adminUrl() . App::postTypes()->get($type)->adminUrl($rosetta_id, false),
        ];
    }

    /**
     * Serve method to add a new translation's link for current edited post/page. (JSON)
     *
     * @param      array<string, mixed>   $get    The cleaned $_GET
     * @param      array<string, mixed>   $post   The cleaned $_POST
     *
     * @return     array<string, mixed>
     */
    public static function addTranslation(array $get, array $post): array
    {
        $_Int = fn (string $name, int $default = 0): int => isset($post[$name]) && is_numeric($val = $post[$name]) ? (int) $val : $default;
        $_Str = fn (string $name, string $default = ''): string => isset($post[$name]) && is_string($val = $post[$name]) ? $val : $default;

        $id           = $_Int('id', -1);
        $lang         = $_Str('lang', '');
        $rosetta_id   = $_Int('rosetta_id', -1);
        $rosetta_lang = $_Str('rosetta_lang', '');

        $ret = false;
        if ($id !== -1 && $rosetta_id !== -1) {
            // get new language if not provided
            if ($rosetta_lang === '') {
                /**
                 * @var ArrayObject<string, mixed>
                 */
                $params = new ArrayObject([
                    'post_id'    => $rosetta_id,
                    'post_type'  => ['post', 'page'],
                    'no_content' => true, ]);

                $rs = App::blog()->getPosts($params);
                if ($rs->count()) {
                    // Load first record
                    $rs->fetch();
                    $rosetta_lang = is_string($rosetta_lang = $rs->post_lang) ? $rosetta_lang : '';
                }
            }

            if ($rosetta_lang !== '') {
                // add the translation link
                $ret = CoreData::addTranslation($id, $lang, $rosetta_id, $rosetta_lang);
            }
        }

        return [
            'ret' => $ret,
            'msg' => ($ret ? __('New translation attached.') : __('Error during translation attachment.')),
        ];
    }

    /**
     * Serve method to remove an existing translation's link for current edited post/page. (JSON)
     *
     * @param      array<string, mixed>   $get    The cleaned $_GET
     * @param      array<string, mixed>   $post   The cleaned $_POST
     *
     * @return     array<string, mixed>
     */
    public static function removeTranslation(array $get, array $post): array
    {
        $_Int = fn (string $name, int $default = 0): int => isset($post[$name]) && is_numeric($val = $post[$name]) ? (int) $val : $default;
        $_Str = fn (string $name, string $default = ''): string => isset($post[$name]) && is_string($val = $post[$name]) ? $val : $default;

        $id           = $_Int('id', -1);
        $lang         = $_Str('lang', '');
        $rosetta_id   = $_Int('rosetta_id', -1);
        $rosetta_lang = $_Str('rosetta_lang', '');

        $ret = false;
        if ($id !== -1 && $rosetta_id !== -1) {
            // Remove the translation link
            $ret = CoreData::removeTranslation($id, $lang, $rosetta_id, $rosetta_lang);
        }

        return [
            'ret' => $ret,
            'msg' => ($ret ? __('Translation removed.') : __('Error during removing translation attachment.')),
        ];
    }

    /**
     * Serve method to get existing translations for current edited post/page. (JSON)
     *
     * @param      array<string, mixed>   $get    The cleaned $_GET
     * @param      array<string, mixed>   $post   The cleaned $_POST
     *
     * @return     array<string, mixed>
     */
    public static function getTranslationRow(array $get, array $post): array
    {
        $system_lang = is_string($system_lang = App::blog()->settings()->system->lang) ? $system_lang : 'en';

        $_Int = fn (string $name, int $default = 0): int => isset($post[$name]) && is_numeric($val = $post[$name]) ? (int) $val : $default;
        $_Str = fn (string $name, string $default = ''): string => isset($post[$name]) && is_string($val = $post[$name]) ? $val : $default;

        $id         = $_Int('id', -1);
        $lang       = $_Str('lang', '');
        $rosetta_id = $_Int('rosetta_id', -1);

        $ret = false;
        $row = '';
        if ($id !== -1 && $rosetta_id !== -1) {
            // Get missing info for current edited entry (post/page)

            /**
             * @var ArrayObject<string, mixed>
             */
            $params = new ArrayObject([
                'post_id'    => $id,
                'post_type'  => ['post', 'page'],
                'no_content' => true, ]);
            $rs = App::blog()->getPosts($params);
            if ($rs->count()) {
                $rs->fetch();

                $post_id   = is_numeric($post_id = $rs->post_id) ? (int) $post_id : 0;
                $post_type = is_string($post_type = $rs->post_type) ? $post_type : '';

                $url_page = App::postTypes()->get($post_type)->adminUrl($post_id);

                // Get missing info for translated entry (post/page)

                /**
                 * @var ArrayObject<string, mixed>
                 */
                $params = new ArrayObject([
                    'post_id'    => $rosetta_id,
                    'post_type'  => ['post', 'page'],
                    'no_content' => true, ]);
                $rs = App::blog()->getPosts($params);
                if ($rs->count()) {
                    $rs->fetch();

                    $post_id    = is_numeric($post_id = $rs->post_id) ? (int) $post_id : 0;
                    $post_type  = is_string($post_type = $rs->post_type) ? $post_type : '';
                    $post_lang  = is_string($post_lang = $rs->post_lang) ? $post_lang : '';
                    $post_title = is_string($post_title = $rs->post_title) ? $post_title : '';

                    $post_link = '<a id="r-%s" href="' . App::postTypes()->get($post_type)->adminUrl($post_id) . '" class="%s" title="%s">%s</a>';

                    $langs = App::lang()->getLanguagesName();
                    $name  = $langs[$post_lang] ?? $langs[$system_lang] ?? $system_lang;

                    // Get the translation row
                    $row = BackendBehaviors::translationRow(
                        $lang,
                        $rosetta_id,
                        $post_lang,
                        $name,
                        $post_title,
                        $post_link,
                        $url_page
                    )->render();
                    $ret = true;
                }
            }
        }

        return [
            'ret' => $ret,
            'msg' => $row,
        ];
    }
}
