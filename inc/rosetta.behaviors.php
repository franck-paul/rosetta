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

// Admin behaviours

class rosettaAdminBehaviors
{
    public static $args_rosetta = '&amp;lang=%s&amp;type=%s&amp;rosetta=%s&amp;rosetta_id=%s&amp;rosetta_lang=%s';

    public static function adminDashboardFavorites($core = null, $favs)
    {
        $favs->register('rosetta', [
            'title'       => __('Rosetta'),
            'url'         => 'plugin.php?p=rosetta',
            'small-icon'  => urldecode(dcPage::getPF('rosetta/icon.svg')),
            'large-icon'  => urldecode(dcPage::getPF('rosetta/icon.svg')),
            'permissions' => 'usage,contentadmin',
        ]);
    }

    private static function adminEntryHeaders()
    {
        return
        dcPage::jsJson('rosetta_entry', [
            'msg'              => ['confirm_remove_rosetta' => __('Are you sure to remove this translation?')],
            'rosetta_post_url' => '',
        ]) .
        dcPage::jsModuleLoad('rosetta/js/rosetta_entry.js', dcCore::app()->getVersion('rosetta')) . "\n" .
        dcPage::cssModuleLoad('rosetta/css/style.css', dcCore::app()->getVersion('rosetta')) . "\n";
    }

    public static function adminPostHeaders()
    {
        dcCore::app()->blog->settings->addNamespace('rosetta');
        if (dcCore::app()->blog->settings->rosetta->active) {
            return
            dcPage::jsJson('rosetta_type', ['post_type' => 'post']) .
            self::adminEntryHeaders();
        }
    }

    public static function adminPageHeaders()
    {
        dcCore::app()->blog->settings->addNamespace('rosetta');
        if (dcCore::app()->blog->settings->rosetta->active) {
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
        '<img src="' . urldecode(dcPage::getPF('rosetta/img/unlink.png')) .
        '" alt="' . __('Remove this translation\'s link') . '" /></a>';

        return sprintf(
            $html_line,
            $lang . ' - ' . $name,
            sprintf($post_link, $id, __('Edit this entry'), html::escapeHTML($title)),
            sprintf($action_remove, $url_page . sprintf(self::$args_rosetta, $src_lang, '', 'remove', $id, $lang))
        );
    }

    private static function adminEntryForm($post, $post_type = 'post')
    {
        global $post_link, $redir_url;

        dcCore::app()->blog->settings->addNamespace('rosetta');
        if (dcCore::app()->blog->settings->rosetta->active) {
            if (!$post || !$post->post_id) {
                // Manage translation only on already created posts/pages
                return;
            }

            echo
                '<div id="rosetta-area" class="area">' . "\n" .
                '<label>' .
                ($post_type == 'post' ? __('Post\'s translations:') : __('Page\'s translations:')) .
                '</label>' . "\n";

            if ($post_type == 'post') {
                $url = dcCore::app()->adminurl->get('admin.post', ['id' => $post->post_id]);
            } else {
                $url = $redir_url . '&id=' . $post->post_id;
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

            $list = rosettaData::findAllTranslations($post->post_id, $post->post_lang, false);
            if (is_array($list) && count($list)) {
                dcUtils::lexicalKeySort($list, 'admin');

                $langs = l10n::getLanguagesName();
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
                            $post_link,
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

            echo '</div>' . "\n";
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
        if (empty($editor) || $editor != 'rosetta') {
            return;
        }

        return dcPage::jsModuleLoad('rosetta/js/popup_posts.js', dcCore::app()->getVersion('rosetta'));
    }

    public static function adminColumnsLists($core = null, $cols)
    {
        $cols['posts'][1]['language']     = [true, __('Language')];
        $cols['posts'][1]['translations'] = [true, __('Translations')];
        $cols['pages'][1]['language']     = [true, __('Language')];
        $cols['pages'][1]['translations'] = [true, __('Translations')];
    }

    private static function adminEntryListHeader($core = null, $rs, $cols)
    {
        dcCore::app()->blog->settings->addNamespace('rosetta');
        if (dcCore::app()->blog->settings->rosetta->active) {
            $cols['language']     = '<th scope="col">' . __('Language') . '</th>';
            $cols['translations'] = '<th scope="col">' . __('Translations') . '</th>';
        }
    }

    public static function adminPostListHeader($core = null, $rs, $cols)
    {
        self::adminEntryListHeader(dcCore::app(), $rs, $cols);
    }

    public static function adminPagesListHeader($core = null, $rs, $cols)
    {
        self::adminEntryListHeader(dcCore::app(), $rs, $cols);
    }

    public static function adminEntryListValue($core = null, $rs, $cols)
    {
        dcCore::app()->blog->settings->addNamespace('rosetta');
        if (dcCore::app()->blog->settings->rosetta->active) {
            $translations = '';
            $list         = rosettaData::findAllTranslations($rs->post_id, $rs->post_lang, false);
            if (is_array($list) && count($list)) {
                dcUtils::lexicalKeySort($list, 'admin');
                $langs = l10n::getLanguagesName();
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

    public static function adminPostListValue($core = null, $rs, $cols)
    {
        self::adminEntryListValue(dcCore::app(), $rs, $cols);
    }

    public static function adminPagesListValue($core = null, $rs, $cols)
    {
        self::adminEntryListValue(dcCore::app(), $rs, $cols);
    }

    public static function adminPostMiniListHeader($core = null, $rs, $cols)
    {
        dcCore::app()->blog->settings->addNamespace('rosetta');
        if (dcCore::app()->blog->settings->rosetta->active) {
            $cols['language'] = '<th scope="col">' . __('Language') . '</th>';
        }
    }

    public static function adminPostMiniListValue($core = null, $rs, $cols)
    {
        dcCore::app()->blog->settings->addNamespace('rosetta');
        if (dcCore::app()->blog->settings->rosetta->active) {
            $cols['language'] = '<td class="nowrap">' . $rs->post_lang . '</td>';
        }
    }

    public static function adminFiltersLists($core = null, $sorts)
    {
        // TODO when 1st and 2nd tab of index will be developped, if necessary
    }

    public static function exportSingle($core = null, $exp, $blog_id)
    {
        $exp->export(
            'rosetta',
            'SELECT R.* ' .
            'FROM ' . dcCore::app()->prefix . 'rosetta R, ' . dcCore::app()->prefix . 'post P ' .
            'WHERE P.post_id = R.src_id ' .
            "AND P.blog_id = '" . $blog_id . "'"
        );
    }

    public static function exportFull($core = null, $exp)
    {
        $exp->exportTable('rosetta');
    }

    public static function importInit($fi, $core = null)
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

    public static function importSingle($line, $fi, $core = null)
    {
        if ($line->__name == 'rosetta') {
            if (isset($fi->old_ids['post'][(int) $line->src_id]) && isset($fi->old_ids['post'][(int) $line->dst_id])) {
                $line->src_id = $fi->old_ids['post'][(int) $line->src_id];
                $line->dst_id = $fi->old_ids['post'][(int) $line->dst_id];
                self::insertRosettaLine($line, $fi);
            } else {
                throw new Exception(sprintf(
                    __('ID of "%3$s" does not match on record "%1$s" at line %2$s of backup file.'),
                    html::escapeHTML($line->__name),
                    html::escapeHTML($line->__line),
                    html::escapeHTML('rosetta')
                ));
            }
        }
    }

    public static function importFull($line, $fi, $core = null)
    {
        if ($line->__name == 'rosetta') {
            self::insertRosettaLine($line, $fi);
        }
    }
}

// Public behaviours

define('ROSETTA_NONE', 0);
define('ROSETTA_FILTER', 1);
define('ROSETTA_SWITCH', 2);

class rosettaPublicBehaviors
{
    public static $state = ROSETTA_NONE;

    public static function coreBlogBeforeGetPosts($params)
    {
        dcCore::app()->blog->settings->addNamespace('rosetta');
        if (dcCore::app()->blog->settings->rosetta->active) {
            if (rosettaPublicBehaviors::$state != ROSETTA_NONE || isset($params['post_lang'])) {
                return;
            }

            if (!isset($params['no_context']) && !isset($params['post_url']) && !isset($params['post_id'])) {
                // Operates only in contexts with list of posts
                $url_types = [
                    'default', 'default-page', 'feed',
                    'category',
                    'tag',
                    'search',
                    'archive', ];
                if (in_array(dcCore::app()->url->type, $url_types)) {
                    if (rosettaPublicBehaviors::$state == ROSETTA_NONE) {
                        // Set language according to blog default language setting
                        $params['post_lang'] = dcCore::app()->blog->settings->system->lang;
                        // Filtering posts state
                        rosettaPublicBehaviors::$state = ROSETTA_FILTER;
                    }
                }
            }
        }
    }

    private static function getPostLang($id, $type)
    {
        $params = new ArrayObject([
            'post_id'    => $id,
            'post_type'  => $type,
            'no_content' => true, ]);
        $rs = dcCore::app()->blog->getPosts($params);
        if ($rs->count()) {
            $rs->fetch();

            return $rs->post_lang;
        }
        // Return blog default lang
        return dcCore::app()->blog->settings->system->lang;
    }

    public static function coreBlogAfterGetPosts($rs, $alt)
    {
        dcCore::app()->blog->settings->addNamespace('rosetta');
        if (dcCore::app()->blog->settings->rosetta->active && dcCore::app()->blog->settings->rosetta->accept_language && $rs->count()) {
            // Start replacing posts only if in Filtering posts state
            if (rosettaPublicBehaviors::$state == ROSETTA_FILTER) {
                $cols = $rs->columns();
                if (count($cols) > 1 || strpos($cols[0], 'count(') != 0) {
                    // Only operate when not counting (aka getPosts() called with $count_only = true)
                    rosettaPublicBehaviors::$state = ROSETTA_SWITCH;
                    // replace translated posts if any may be using core->getPosts()
                    $langs = http::getAcceptLanguages();
                    if (count($langs)) {
                        $ids = [];
                        $nbx = 0;
                        while ($rs->fetch()) {
                            $exchanged = false;
                            if (!$rs->exists('post_lang')) {
                                // Find post lang
                                $post_lang = rosettaPublicBehaviors::getPostLang($rs->post_id, $rs->post_type);
                            } else {
                                $post_lang = $rs->post_lang;
                            }
                            foreach ($langs as $lang) {
                                if ($post_lang == $lang) {
                                    // Already in an accepted language, do nothing
                                    break;
                                }
                                // Try to find an associated post corresponding to the requested lang
                                $id = rosettaData::findTranslation($rs->post_id, $post_lang, $lang);
                                if (($id >= 0) && ($id != $rs->post_id)) {
                                    // Get post/page data
                                    $params = new ArrayObject([
                                        'post_id'     => $id,
                                        'post_type'   => $rs->post_type,
                                        'post_status' => $rs->post_status,
                                        'no_content'  => false, ]);
                                    $rst = dcCore::app()->blog->getPosts($params);
                                    if ($rst->count()) {
                                        // Load first record
                                        $rst->fetch();
                                        // Replace source id with its translation id
                                        $ids[] = $id;
                                        $nbx++;
                                        $exchanged = true;
                                        // Switch done
                                        break;
                                    }
                                }
                            }
                            if (!$exchanged) {
                                // Nothing found, keep source id
                                $ids[] = $rs->post_id;
                            }
                        }
                        if (count($ids) && $nbx) {
                            // Get new list of posts as we have at least one exchange done
                            $params = new ArrayObject([
                                'post_id' => $ids, ]);
                            $alt['rs'] = dcCore::app()->blog->getPosts($params);
                        }
                    }
                }

                // Back to normal operation
                rosettaPublicBehaviors::$state = ROSETTA_NONE;
            }
        }
    }

    public static function publicHeadContent()
    {
        $urlTypes  = ['post'];
        $postTypes = ['post'];
        if (dcCore::app()->plugins->moduleExists('pages')) {
            $urlTypes[]  = 'page';
            $postTypes[] = 'page';
        }

        dcCore::app()->blog->settings->addNamespace('rosetta');
        if (dcCore::app()->blog->settings->rosetta->active) {
            if (in_array(dcCore::app()->url->type, $urlTypes)) {
                if (in_array(dcCore::app()->ctx->posts->post_type, $postTypes)) {
                    // Find translations and add meta in header
                    $list = rosettaTpl::EntryListHelper(
                        dcCore::app()->ctx->posts->post_id,
                        dcCore::app()->ctx->posts->post_lang,
                        dcCore::app()->ctx->posts->post_type,
                        'none',
                        $current,
                        true
                    );
                    if (is_array($list)) {
                        if (count($list)) {
                            echo '<!-- Rosetta: translated version of this entry -->' . "\n";
                            foreach ($list as $lang => $url) {
                                echo '<link rel="alternate" href="' . $url . '" hreflang="' . $lang . '" />' . "\n";
                            }
                        }
                    }
                }
            }
        }
    }

    private static function findTranslatedEntry($handler, $lang)
    {
        $postTypes = ['post'];
        if (dcCore::app()->plugins->moduleExists('pages')) {
            $postTypes[] = 'page';
        }

        // Get post/page id
        $paramsSrc = new ArrayObject([
            'post_url'   => $handler->args,
            'post_type'  => $postTypes,
            'no_content' => true, ]);

        dcCore::app()->callBehavior('publicPostBeforeGetPosts', $paramsSrc, $handler->args);
        $rsSrc = dcCore::app()->blog->getPosts($paramsSrc);

        // Check if post/page id exists in rosetta table
        if ($rsSrc->count()) {
            // Load first record
            $rsSrc->fetch();

            // If current entry is in the requested languages, return true
            if ($rsSrc->post_lang == $lang) {
                return true;
            }

            // Try to find an associated post corresponding to the requested lang
            $id = rosettaData::findTranslation($rsSrc->post_id, $rsSrc->post_lang, $lang);
            if (($id >= 0) && ($id != $rsSrc->post_id)) {
                // Get post/page URL
                $paramsDst = new ArrayObject([
                    'post_id'    => $id,
                    'post_type'  => $postTypes,
                    'no_content' => true, ]);

                dcCore::app()->callBehavior('publicPostBeforeGetPosts', $paramsDst, $handler->args);
                $rsDst = dcCore::app()->blog->getPosts($paramsDst);

                if ($rsDst->count()) {
                    // Load first record
                    $rsDst->fetch();

                    // Redirect to translated post
                    $url = $rsDst->getURL();
                    if (!preg_match('%^http[s]?://%', $url)) {
                        // Prepend scheme if not present
                        $url = (isset($_SERVER['HTTPS']) ? 'https:' : 'http:') . $url;
                    }
                    http::redirect($url);
                    exit;
                }
            }
        }

        return false;
    }

    public static function urlHandlerGetArgsDocument($handler)
    {
        dcCore::app()->blog->settings->addNamespace('rosetta');
        if (!dcCore::app()->blog->settings->rosetta->active) {
            return;
        }

        $langs = [];
        if (!empty($_GET['lang'])) {
            // Check lang scheme
            if (preg_match('/^[a-z]{2}(-[a-z]{2})?$/', rawurldecode($_GET['lang']), $matches)) {
                // Assume that the URL scheme is for post/page
                $langs[] = $matches[0];
            }
        } elseif (dcCore::app()->blog->settings->rosetta->accept_language) {
            $urlType = '';
            $urlPart = '';
            $handler->getArgs($_SERVER['URL_REQUEST_PART'], $urlType, $urlPart);
            if (in_array($urlType, ['post', 'pages'])) {
                // It is a post or page: Try to find a translation according to the browser settings
                $langs = http::getAcceptLanguages();
            }
        }

        if (count($langs)) {
            foreach ($langs as $lang) {
                // Try to find an according translation (will http-redirect if any)
                if (self::findTranslatedEntry($handler, $lang)) {
                    // The current entry is already in one of the browser languages
                    break;
                }
            }
        }
    }
}
