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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

class rosettaRest
{
    public static function newTranslation($core, $get)
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
            try
            {
                // Default format and content
                $format  = 'xhtml';
                $content = '<p>...</p>';

                // Get currently edited post format
                $rs = $core->blog->getPosts(['post_id' => $id]);
                if (!$rs->isEmpty()) {
                    $rs->fetch();
                    $format = $rs->post_format;
                    if ($format != 'xhtml') {
                        $content = '...';
                    }
                }

                // Create a new entry with given title and lang
                $cur = $core->con->openCursor($core->prefix . 'post');

                $cur->post_title = $rosetta_title;
                $cur->post_type  = $type;
                $cur->post_lang  = $rosetta_lang;

                $cur->user_id           = $core->auth->userID();
                $cur->post_content      = $content;
                $cur->post_format       = $format;
                $cur->post_status       = -2; // forced to pending
                $cur->post_open_comment = (integer) $core->blog->settings->system->allow_comments;
                $cur->post_open_tb      = (integer) $core->blog->settings->system->allow_trackbacks;

                # --BEHAVIOR-- adminBeforePostCreate
                $core->callBehavior('adminBeforePostCreate', $cur);

                $rosetta_id = $core->blog->addPost($cur);

                # --BEHAVIOR-- adminAfterPostCreate
                $core->callBehavior('adminAfterPostCreate', $cur, $rosetta_id);

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
        $rsp->edit = DC_ADMIN_URL . $core->getPostAdminURL($type, $rosetta_id);

        return $rsp;
    }

    /**
     * Serve method to add a new translation's link for current edited post/page.
     *
     * @param    core    <b>dcCore</b>    dcCore instance
     * @param    get        <b>array</b>    cleaned $_GET
     *
     * @return    <b>xmlTag</b>    XML representation of response
     */
    public static function addTranslation($core, $get)
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
                    'no_content' => true]);

                $rs = $core->blog->getPosts($params);
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
     * @param    core    <b>dcCore</b>    dcCore instance
     * @param    get        <b>array</b>    cleaned $_GET
     *
     * @return    <b>xmlTag</b>    XML representation of response
     */
    public static function removeTranslation($core, $get)
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

    public static function getTranslationRow($core, $get)
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
                'no_content' => true]);
            $rs = $core->blog->getPosts($params);
            if ($rs->count()) {
                $rs->fetch();
                $url_page = $core->getPostAdminURL($rs->post_type, $rs->post_id);
                // Get missing info for translated entry (post/page)
                $params = new ArrayObject([
                    'post_id'    => $rosetta_id,
                    'post_type'  => ['post', 'page'],
                    'no_content' => true]);
                $rs = $core->blog->getPosts($params);
                if ($rs->count()) {
                    $rs->fetch();
                    $post_link = '<a id="r-%s" href="' . $core->getPostAdminURL($rs->post_type, $rs->post_id) . '" title="%s">%s</a>';
                    $langs     = l10n::getLanguagesName();
                    $name      = isset($langs[$rs->post_lang]) ? $langs[$rs->post_lang] : $langs[$core->blog->settings->system->lang];
                    // Get the translation row
                    $row = rosettaAdminBehaviors::translationRow($lang,
                        $rosetta_id, $rs->post_lang, $name,
                        $rs->post_title, $post_link, $url_page);
                    $ret = true;
                }
            }
        }

        $rsp->ret = $ret;
        $rsp->msg = $row;

        return $rsp;
    }
}
