<?php
/**
 * @brief rosetta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

class rosettaRest
{
    public static function newTranslation($core = null, $get)
    {
        $id            = !empty($get['id']) ? $get['id'] : -1;
        $lang          = !empty($get['lang']) ? $get['lang'] : '';
        $type          = !empty($get['type']) ? $get['type'] : 'post';
        $rosetta_title = !empty($get['rosetta_title']) ? $get['rosetta_title'] : '';
        $rosetta_lang  = !empty($get['rosetta_lang']) ? $get['rosetta_lang'] : '';
        $rsp           = new xmlTag('rosetta');

        $ret        = false;
        $rosetta_id = -1;
        if ($id != -1 && $lang != '' && $rosetta_title != '' && $rosetta_lang != '') {
            try {
                // Default format and content
                $format  = 'xhtml';
                $content = '<p>...</p>';

                // Get currently edited post format
                $rs = dcCore::app()->blog->getPosts(['post_id' => $id]);
                if (!$rs->isEmpty()) {
                    $rs->fetch();
                    $format = $rs->post_format;
                    if ($format != 'xhtml') {
                        $content = '...';
                    }
                }

                // Create a new entry with given title and lang
                $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'post');

                $cur->post_title = $rosetta_title;
                $cur->post_type  = $type;
                $cur->post_lang  = $rosetta_lang;

                $cur->user_id           = dcCore::app()->auth->userID();
                $cur->post_content      = $content;
                $cur->post_format       = $format;
                $cur->post_status       = -2; // forced to pending
                $cur->post_open_comment = (int) dcCore::app()->blog->settings->system->allow_comments;
                $cur->post_open_tb      = (int) dcCore::app()->blog->settings->system->allow_trackbacks;

                # --BEHAVIOR-- adminBeforePostCreate
                dcCore::app()->callBehavior('adminBeforePostCreate', $cur);

                $rosetta_id = dcCore::app()->blog->addPost($cur);

                # --BEHAVIOR-- adminAfterPostCreate
                dcCore::app()->callBehavior('adminAfterPostCreate', $cur, $rosetta_id);

                // add the translation link
                $ret = rosettaData::addTranslation($id, $lang, $rosetta_id, $rosetta_lang);
            } catch (Exception $e) {
                $rosetta_id = -1;
            }
        }

        $rsp->ret = $ret;
        $rsp->msg = $ret ? __('New translation created.') : ($rosetta_id == -1 ?
            __('Error during new translation creation.') :
            __('Error during newly created translation attachment.'));
        $rsp->id   = $rosetta_id;
        $rsp->edit = DC_ADMIN_URL . dcCore::app()->getPostAdminURL($type, $rosetta_id);

        return $rsp;
    }

    /**
     * Serve method to add a new translation's link for current edited post/page.
     *
     * @param      dcCore  $core   The dcCore instance
     * @param      array   $get    The cleaned $_GET
     *
     * @return     xmlTag  The xml tag.
     */
    public static function addTranslation($core = null, $get)
    {
        $id           = !empty($get['id']) ? $get['id'] : -1;
        $lang         = !empty($get['lang']) ? $get['lang'] : '';
        $rosetta_id   = !empty($get['rosetta_id']) ? $get['rosetta_id'] : -1;
        $rosetta_lang = !empty($get['rosetta_lang']) ? $get['rosetta_lang'] : '';
        $rsp          = new xmlTag('rosetta');

        $ret = false;
        if ($id != -1 && $rosetta_id != -1) {
            // get new language if not provided
            if ($rosetta_lang == '') {
                $params = new ArrayObject([
                    'post_id'    => $rosetta_id,
                    'post_type'  => ['post', 'page'],
                    'no_content' => true, ]);

                $rs = dcCore::app()->blog->getPosts($params);
                if ($rs->count()) {
                    // Load first record
                    $rs->fetch();
                    $rosetta_lang = $rs->post_lang;
                }
            }
            // add the translation link
            $ret = rosettaData::addTranslation($id, $lang, $rosetta_id, $rosetta_lang);
        }

        $rsp->ret = $ret;
        $rsp->msg = $ret ? __('New translation attached.') : __('Error during translation attachment.');

        return $rsp;
    }

    /**
     * Serve method to remove an existing translation's link for current edited post/page.
     *
     * @param      dcCore  $core   The dcCore instance
     * @param      array   $get    The cleaned $_GET
     *
     * @return     xmlTag  The xml tag.
     */
    public static function removeTranslation($core = null, $get)
    {
        $id           = !empty($get['id']) ? $get['id'] : -1;
        $lang         = !empty($get['lang']) ? $get['lang'] : '';
        $rosetta_id   = !empty($get['rosetta_id']) ? $get['rosetta_id'] : -1;
        $rosetta_lang = !empty($get['rosetta_lang']) ? $get['rosetta_lang'] : '';
        $rsp          = new xmlTag('rosetta');

        $ret = false;
        if ($id != -1 && $rosetta_id != -1) {
            // Remove the translation link
            $ret = rosettaData::removeTranslation($id, $lang, $rosetta_id, $rosetta_lang);
        }

        $rsp->ret = $ret;
        $rsp->msg = $ret ? __('Translation removed.') : __('Error during removing translation attachment.');

        return $rsp;
    }

    public static function getTranslationRow($core = null, $get)
    {
        $id         = !empty($get['id']) ? $get['id'] : -1;
        $lang       = !empty($get['lang']) ? $get['lang'] : '';
        $rosetta_id = !empty($get['rosetta_id']) ? $get['rosetta_id'] : -1;
        $rsp        = new xmlTag('rosetta');

        $ret = false;
        $row = '';
        if ($id != -1 && $rosetta_id != -1) {
            // Get missing info for current edited entry (post/page)
            $params = new ArrayObject([
                'post_id'    => $id,
                'post_type'  => ['post', 'page'],
                'no_content' => true, ]);
            $rs = dcCore::app()->blog->getPosts($params);
            if ($rs->count()) {
                $rs->fetch();
                $url_page = dcCore::app()->getPostAdminURL($rs->post_type, $rs->post_id);
                // Get missing info for translated entry (post/page)
                $params = new ArrayObject([
                    'post_id'    => $rosetta_id,
                    'post_type'  => ['post', 'page'],
                    'no_content' => true, ]);
                $rs = dcCore::app()->blog->getPosts($params);
                if ($rs->count()) {
                    $rs->fetch();
                    $post_link = '<a id="r-%s" href="' . dcCore::app()->getPostAdminURL($rs->post_type, $rs->post_id) . '" title="%s">%s</a>';
                    $langs     = l10n::getLanguagesName();
                    $name      = $langs[$rs->post_lang] ?? $langs[dcCore::app()->blog->settings->system->lang];
                    // Get the translation row
                    $row = rosettaAdminBehaviors::translationRow(
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

        $rsp->ret = $ret;
        $rsp->msg = $row;

        return $rsp;
    }
}
