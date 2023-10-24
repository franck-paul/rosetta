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
use Dotclear\Helper\L10n;
use Exception;

class BackendRest
{
    /**
     * Serve method to add a new translation for current edited post/page. (JSON)
     *
     * @param      array<string, string>   $get    The cleaned $_GET
     * @param      array<string, string>   $post   The cleaned $_POST
     *
     * @return     array<string, mixed>
     */
    public static function newTranslation(array $get, array $post): array
    {
        $id            = !empty($post['id']) ? (int) $post['id'] : -1;
        $lang          = !empty($post['lang']) ? $post['lang'] : '';
        $type          = !empty($post['type']) ? $post['type'] : 'post';
        $rosetta_title = !empty($post['rosetta_title']) ? $post['rosetta_title'] : '';
        $rosetta_lang  = !empty($post['rosetta_lang']) ? $post['rosetta_lang'] : '';

        $ret        = false;
        $rosetta_id = -1;
        if ($id !== -1 && $lang != '' && $rosetta_title != '' && $rosetta_lang != '') {
            try {
                // Default format and content
                $format  = 'xhtml';
                $content = '<p>...</p>';

                // Get currently edited post format
                $rs = App::blog()->getPosts(['post_id' => $id]);
                if (!$rs->isEmpty()) {
                    $rs->fetch();
                    $format = $rs->post_format;
                    if ($format != 'xhtml') {
                        $content = '...';
                    }
                }

                // Create a new entry with given title and lang
                $cur = App::con()->openCursor(App::con()->prefix() . App::blog()::POST_TABLE_NAME);

                $cur->post_title = $rosetta_title;
                $cur->post_type  = $type;
                $cur->post_lang  = $rosetta_lang;

                $cur->user_id           = App::auth()->userID();
                $cur->post_content      = $content;
                $cur->post_format       = $format;
                $cur->post_status       = App::blog()::POST_PENDING; // forced to pending
                $cur->post_open_comment = (int) App::blog()->settings()->system->allow_comments;
                $cur->post_open_tb      = (int) App::blog()->settings()->system->allow_trackbacks;

                # --BEHAVIOR-- adminBeforePostCreate
                App::behavior()->callBehavior('adminBeforePostCreate', $cur);

                $rosetta_id = App::blog()->addPost($cur);

                # --BEHAVIOR-- adminAfterPostCreate
                App::behavior()->callBehavior('adminAfterPostCreate', $cur, $rosetta_id);

                // add the translation link
                $ret = CoreData::addTranslation($id, $lang, $rosetta_id, $rosetta_lang);
            } catch (Exception $e) {
                $rosetta_id = -1;
            }
        }

        return [
            'ret' => $ret,
            'msg' => ($ret ? __('New translation created.') : ($rosetta_id === -1 ?
                __('Error during new translation creation.') :
                __('Error during newly created translation attachment.'))),
            'id'   => $rosetta_id,
            'edit' => App::config()->adminUrl() . App::postTypes()->get($type)->adminUrl($rosetta_id),
        ];
    }

    /**
     * Serve method to add a new translation's link for current edited post/page. (JSON)
     *
     * @param      array<string, string>   $get    The cleaned $_GET
     * @param      array<string, string>   $post   The cleaned $_POST
     *
     * @return     array<string, mixed>
     */
    public static function addTranslation(array $get, array $post): array
    {
        $id           = !empty($post['id']) ? (int) $post['id'] : -1;
        $lang         = !empty($post['lang']) ? $post['lang'] : '';
        $rosetta_id   = !empty($post['rosetta_id']) ? (int) $post['rosetta_id'] : -1;
        $rosetta_lang = !empty($post['rosetta_lang']) ? $post['rosetta_lang'] : '';

        $ret = false;
        if ($id !== -1 && $rosetta_id !== -1) {
            // get new language if not provided
            if ($rosetta_lang == '') {
                $params = new ArrayObject([
                    'post_id'    => $rosetta_id,
                    'post_type'  => ['post', 'page'],
                    'no_content' => true, ]);

                $rs = App::blog()->getPosts($params);
                if ($rs->count()) {
                    // Load first record
                    $rs->fetch();
                    $rosetta_lang = $rs->post_lang;
                }
            }
            // add the translation link
            $ret = CoreData::addTranslation($id, $lang, $rosetta_id, $rosetta_lang);
        }

        return [
            'ret' => $ret,
            'msg' => ($ret ? __('New translation attached.') : __('Error during translation attachment.')),
        ];
    }

    /**
     * Serve method to remove an existing translation's link for current edited post/page. (JSON)
     *
     * @param      array<string, string>   $get    The cleaned $_GET
     * @param      array<string, string>   $post   The cleaned $_POST
     *
     * @return     array<string, mixed>
     */
    public static function removeTranslation(array $get, array $post): array
    {
        $id           = !empty($post['id']) ? (int) $post['id'] : -1;
        $lang         = !empty($post['lang']) ? $post['lang'] : '';
        $rosetta_id   = !empty($post['rosetta_id']) ? (int) $post['rosetta_id'] : -1;
        $rosetta_lang = !empty($post['rosetta_lang']) ? $post['rosetta_lang'] : '';

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
     * @param      array<string, string>   $get    The cleaned $_GET
     * @param      array<string, string>   $post   The cleaned $_POST
     *
     * @return     array<string, mixed>
     */
    public static function getTranslationRow(array $get, array $post): array
    {
        $id         = !empty($post['id']) ? (int) $post['id'] : -1;
        $lang       = !empty($post['lang']) ? $post['lang'] : '';
        $rosetta_id = !empty($post['rosetta_id']) ? (int) $post['rosetta_id'] : -1;

        $ret = false;
        $row = '';
        if ($id !== -1 && $rosetta_id !== -1) {
            // Get missing info for current edited entry (post/page)
            $params = new ArrayObject([
                'post_id'    => $id,
                'post_type'  => ['post', 'page'],
                'no_content' => true, ]);
            $rs = App::blog()->getPosts($params);
            if ($rs->count()) {
                $rs->fetch();
                $url_page = App::postTypes()->get($rs->post_type)->adminUrl($rs->post_id);
                // Get missing info for translated entry (post/page)
                $params = new ArrayObject([
                    'post_id'    => $rosetta_id,
                    'post_type'  => ['post', 'page'],
                    'no_content' => true, ]);
                $rs = App::blog()->getPosts($params);
                if ($rs->count()) {
                    $rs->fetch();
                    $post_link = '<a id="r-%s" href="' . App::postTypes()->get($rs->post_type)->adminUrl($rs->post_id) . '" title="%s">%s</a>';
                    $langs     = L10n::getLanguagesName();
                    $name      = $langs[$rs->post_lang] ?? $langs[App::blog()->settings()->system->lang];
                    // Get the translation row
                    $row = BackendBehaviors::translationRow(
                        $lang,
                        $rosetta_id,
                        $rs->post_lang,
                        $name,
                        $rs->post_title,
                        $post_link,
                        $url_page
                    );
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
