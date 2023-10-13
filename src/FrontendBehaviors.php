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
use dcUrlHandlers;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Network\Http;

class FrontendBehaviors
{
    public const ROSETTA_NONE   = 0;
    public const ROSETTA_FILTER = 1;
    public const ROSETTA_SWITCH = 2;

    public static int $state = self::ROSETTA_NONE;

    /**
     * @param      ArrayObject<string, mixed>  $params  The parameters
     */
    public static function coreBlogBeforeGetPosts(ArrayObject $params): string
    {
        $settings = My::settings();
        if ($settings->active) {
            if (static::$state !== self::ROSETTA_NONE || isset($params['post_lang'])) {
                return '';
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
                    // Set language according to blog default language setting
                    $params['post_lang'] = dcCore::app()->blog->settings->system->lang;
                    // Filtering posts state
                    static::$state = self::ROSETTA_FILTER;
                }
            }
        }

        return '';
    }

    /**
     * Gets the post language.
     *
     * @param      int          $id     The identifier
     * @param      string       $type   The type
     *
     * @return     null|string  The post language.
     */
    private static function getPostLang(int $id, string $type): ?string
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

    /**
     * @param      MetaRecord                   $rs     Recordset
     * @param      ArrayObject<string, mixed>   $alt    The alternate params
     *
     * @return     string                         ( description_of_the_return_value )
     */
    public static function coreBlogAfterGetPosts(MetaRecord $rs, ArrayObject $alt): string
    {
        $settings = My::settings();
        if ($settings->active && $settings->accept_language && $rs->count()) {
            // Start replacing posts only if in Filtering posts state
            if (static::$state === self::ROSETTA_FILTER) {
                $cols = $rs->columns();
                if (count($cols) > 1 || strpos($cols[0], 'count(') != 0) {
                    // Only operate when not counting (aka getPosts() called with $count_only = true)
                    static::$state = self::ROSETTA_SWITCH;
                    // replace translated posts if any may be using core->getPosts()
                    $langs = Http::getAcceptLanguages();
                    if (count($langs)) {
                        $ids = [];
                        $nbx = 0;
                        while ($rs->fetch()) {
                            $exchanged = false;
                            if (!$rs->exists('post_lang')) {
                                // Find post lang
                                $post_lang = self::getPostLang($rs->post_id, $rs->post_type);
                            } else {
                                $post_lang = $rs->post_lang;
                            }
                            foreach ($langs as $lang) {
                                if ($post_lang == $lang) {
                                    // Already in an accepted language, do nothing
                                    break;
                                }
                                // Try to find an associated post corresponding to the requested lang
                                $id = CoreData::findTranslation($rs->post_id, $post_lang, $lang);
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
                static::$state = self::ROSETTA_NONE;
            }
        }

        return '';
    }

    public static function publicHeadContent(): string
    {
        $current   = null;
        $urlTypes  = ['post'];
        $postTypes = ['post'];
        if (dcCore::app()->plugins->moduleExists('pages')) {
            $urlTypes[]  = 'page';
            $postTypes[] = 'page';
        }

        $settings = My::settings();
        if ($settings->active && in_array(dcCore::app()->url->type, $urlTypes) && in_array(dcCore::app()->ctx->posts->post_type, $postTypes)) {
            // Find translations and add meta in header
            $list = FrontendHelper::EntryListHelper(
                dcCore::app()->ctx->posts->post_id,
                dcCore::app()->ctx->posts->post_lang,
                dcCore::app()->ctx->posts->post_type,
                'none',
                $current,
                true
            );
            if (is_array($list) && count($list)) {
                echo '<!-- Rosetta: translated version of this entry -->' . "\n";
                foreach ($list as $lang => $url) {
                    echo '<link rel="alternate" href="' . $url . '" hreflang="' . $lang . '" />' . "\n";
                }
            }
        }

        return '';
    }

    /**
     * Finds a translated entry.
     *
     * @param      dcUrlHandlers  $handler  The handler
     * @param      string         $lang     The language
     *
     * @return     bool
     */
    private static function findTranslatedEntry(dcUrlHandlers $handler, string $lang)
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
            $id = CoreData::findTranslation($rsSrc->post_id, $rsSrc->post_lang, $lang);
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
                    if (!preg_match('%^https?://%', $url)) {
                        // Prepend scheme if not present
                        $url = (isset($_SERVER['HTTPS']) ? 'https:' : 'http:') . $url;
                    }
                    Http::redirect($url);
                    exit;
                }
            }
        }

        return false;
    }

    public static function urlHandlerGetArgsDocument(dcUrlHandlers $handler): string
    {
        $settings = My::settings();
        if (!$settings->active) {
            return '';
        }

        $langs = [];
        if (!empty($_GET['lang'])) {
            // Check lang scheme
            if (preg_match('/^[a-z]{2}(-[a-z]{2})?$/', rawurldecode($_GET['lang']), $matches)) {
                // Assume that the URL scheme is for post/page
                $langs[] = $matches[0];
            }
        } elseif ($settings->accept_language) {
            $urlType = '';
            $urlPart = '';
            $handler->getArgs($_SERVER['URL_REQUEST_PART'], $urlType, $urlPart);
            if (in_array($urlType, ['post', 'pages'])) {
                // It is a post or page: Try to find a translation according to the browser settings
                $langs = Http::getAcceptLanguages();
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

        return '';
    }
}
