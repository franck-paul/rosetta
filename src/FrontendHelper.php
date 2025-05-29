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
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;

class FrontendHelper
{
    /**
     * Finds a translated entry.
     *
     * @param      string       $entry    The original entry url
     * @param      string       $lang     The language
     *
     * @return     string       The translated entry URL or empty string if not found
     */
    public static function findTranslatedEntry(string $entry, string $lang): string
    {
        $postTypes = array_keys(App::postTypes()->dump());

        // Get post/page id
        $paramsSrc = new ArrayObject([
            'post_url'   => $entry,
            'post_type'  => $postTypes,
            'no_content' => true,
        ]);

        App::behavior()->callBehavior('publicPostBeforeGetPosts', $paramsSrc, $entry);
        $rsSrc = App::blog()->getPosts($paramsSrc);

        // Check if post/page id exists in rosetta table
        if ($rsSrc->count()) {
            // Load first record
            $rsSrc->fetch();

            // If current entry is in the requested languages, return true
            if ($rsSrc->post_lang == $lang) {
                return $entry;
            }

            // Try to find an associated post corresponding to the requested lang
            $id = CoreData::findTranslation((int) $rsSrc->post_id, $rsSrc->post_lang, $lang);
            if (($id >= 0) && ($id != (int) $rsSrc->post_id)) {
                // Get post/page URL
                $paramsDst = new ArrayObject([
                    'post_id'    => $id,
                    'post_type'  => $postTypes,
                    'no_content' => true,
                ]);

                App::behavior()->callBehavior('publicPostBeforeGetPosts', $paramsDst, $entry);
                $rsDst = App::blog()->getPosts($paramsDst);

                if ($rsDst->count()) {
                    // Load first record
                    $rsDst->fetch();

                    // Return entry URL
                    $url = $rsDst->post_url;

                    return $url;
                }
            }
        }

        return '';
    }

    /**
     * @param      int          $post_id    The post identifier
     * @param      null|string  $post_lang  The post language
     * @param      string       $post_type  The post type
     * @param      string       $include    The include
     * @param      string       $current    The current language
     * @param      bool         $code_only  The code only
     *
     * @return     array<string, string>|bool
     */
    public static function EntryListHelper(int $post_id, ?string $post_lang, string $post_type, string $include, string &$current, bool $code_only = false): array|bool
    {
        // Get associated entries
        $ids = CoreData::findAllTranslations($post_id, $post_lang, ($include !== 'none'));
        if (!is_array($ids)) {
            return false;
        }

        // source = $ids : array ('lang' => 'entry-id')
        // destination = $table : array ('language' (or 'lang' if $code=true) => 'entry-url')
        // $current = current language
        $table = [];
        $langs = L10n::getLanguagesName();
        foreach ($ids as $lang => $id) {
            $name = $langs[$lang] ?? $langs[App::blog()->settings()->system->lang];
            if ($post_id == $id) {
                $current = $code_only ? $lang : $name;
            }

            if ($post_id == $id && $include !== 'link') {
                $table[($code_only ? $lang : $name)] = '';
            } else {
                // Get post/page URL
                $params = new ArrayObject([
                    'post_id'    => $id,
                    'post_type'  => $post_type,
                    'no_content' => true, ]);
                App::behavior()->callBehavior('publicPostBeforeGetPosts', $params, null);
                $rs = App::blog()->getPosts($params);
                if ($rs->count()) {
                    $rs->fetch();
                    $url = App::blog()->url() . App::postTypes()->get($post_type)->publicUrl(Html::sanitizeURL($rs->post_url));

                    $settings = My::settings();
                    if ($settings->accept_language) {
                        // Add lang parameter to the URL to prevent accept-language auto redirect
                        $url .= (str_contains($url, '?') ? '&' : '?') . 'lang=' . $lang;
                    }

                    $table[($code_only ? $lang : $name)] = $url;
                }
            }
        }

        if ($table === []) {
            return false;
        }

        App::lexical()->lexicalKeySort($table, App::lexical()::PUBLIC_LOCALE);

        return $table;
    }
}
