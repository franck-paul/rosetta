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
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;

class FrontendHelper
{
    public static function EntryListHelper($post_id, $post_lang, $post_type, $include, &$current, $code_only = false)
    {
        // Get associated entries
        $ids = CoreData::findAllTranslations($post_id, $post_lang, ($include != 'none'));
        if (!is_array($ids)) {
            return false;
        }
        // source = $ids : array ('lang' => 'entry-id')
        // destination = $table : array ('language' (or 'lang' if $code=true) => 'entry-url')
        // $current = current language
        $table   = [];
        $langs   = L10n::getLanguagesName();
        $current = '';
        foreach ($ids as $lang => $id) {
            $name = $langs[$lang] ?? $langs[dcCore::app()->blog->settings->system->lang];
            if ($post_id == $id) {
                $current = ($code_only ? $lang : $name);
            }
            if ($post_id == $id && $include != 'link') {
                $table[($code_only ? $lang : $name)] = '';
            } else {
                // Get post/page URL
                $params = new ArrayObject([
                    'post_id'    => $id,
                    'post_type'  => $post_type,
                    'no_content' => true, ]);
                dcCore::app()->callBehavior('publicPostBeforeGetPosts', $params, null);
                $rs = dcCore::app()->blog->getPosts($params);
                if ($rs->count()) {
                    $rs->fetch();
                    $url      = dcCore::app()->blog->url . dcCore::app()->getPostPublicURL($post_type, Html::sanitizeURL($rs->post_url));
                    $settings = My::settings();
                    if ($settings->accept_language) {
                        // Add lang parameter to the URL to prevent accept-language auto redirect
                        $url .= (strpos($url, '?') === false ? '?' : '&') . 'lang=' . $lang;
                    }
                    $table[($code_only ? $lang : $name)] = $url;
                }
            }
        }
        if (!count($table)) {
            return false;
        }
        dcUtils::lexicalKeySort($table, dcUtils::PUBLIC_LOCALE);

        return $table;
    }
}
