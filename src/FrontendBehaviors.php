<?php

/**
 * @brief rosetta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul contact@open-time.net
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\rosetta;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Url;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Network\Http;

class FrontendBehaviors
{
    public const ROSETTA_NONE = 0;

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
                    'archive',
                ];
                if (in_array(App::url()->getType(), $url_types)) {
                    // Set language according to blog default language setting
                    $params['post_lang'] = App::blog()->settings()->system->lang;
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
     * @return     string  The post language.
     */
    private static function getPostLang(int $id, string $type): string
    {
        $system_lang = is_string($system_lang = App::blog()->settings()->system->lang) ? $system_lang : 'en';

        /**
         * @var        ArrayObject<string, mixed>
         */
        $params = new ArrayObject([
            'post_id'    => $id,
            'no_content' => true,
        ]);
        if ($type !== '') {
            $params['type'] = $type;
        }
        $rs = App::blog()->getPosts($params);
        if ($rs->count()) {
            $rs->fetch();

            return is_string($post_lang = $rs->post_lang) ? $post_lang : $system_lang;
        }

        // Return blog default lang
        return $system_lang;
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
        // Start replacing posts only if in Filtering posts state
        if ($settings->active && $settings->accept_language && $rs->count() && static::$state === self::ROSETTA_FILTER) {
            $cols = $rs->columns();
            if (count($cols) > 1 || !str_starts_with((string) $cols[0], 'count(')) {
                // Only operate when not counting (aka getPosts() called with $count_only = true)
                static::$state = self::ROSETTA_SWITCH;
                // replace translated posts if any may be using core->getPosts()
                $langs = Http::getAcceptLanguages();
                if (count($langs) > 0) {
                    $ids = [];
                    $nbx = 0;
                    while ($rs->fetch()) {
                        $exchanged = false;
                        $post_id   = is_numeric($post_id = $rs->post_id) ? (int) $post_id : 0;
                        $post_type = is_string($post_type = $rs->post_type) ? $post_type : '';
                        $post_lang = $rs->exists('post_lang') && is_string($rs->post_lang) ? $rs->post_lang : self::getPostLang($post_id, $post_type);
                        foreach ($langs as $lang) {
                            if ($post_lang === $lang) {
                                // Already in an accepted language, do nothing
                                break;
                            }

                            // Try to find an associated post corresponding to the requested lang
                            $id = CoreData::findTranslation($post_id, $post_lang, $lang);
                            if (($id >= 0) && ($id !== $post_id)) {
                                // Get post/page data

                                /**
                                 * @var        ArrayObject<string, mixed>
                                 */
                                $params = new ArrayObject([
                                    'post_id'     => $id,
                                    'post_type'   => $post_type,
                                    'post_status' => $rs->post_status,
                                    'no_content'  => false,
                                ]);
                                $rst = App::blog()->getPosts($params);
                                if ($rst->count()) {
                                    // Load first record
                                    $rst->fetch();
                                    // Replace source id with its translation id
                                    $ids[] = $id;
                                    ++$nbx;
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

                        /**
                         * @var        ArrayObject<string, mixed>
                         */
                        $params = new ArrayObject([
                            'post_id' => $ids,
                        ]);

                        $alt['rs'] = App::blog()->getPosts($params);
                    }
                }
            }

            // Back to normal operation
            static::$state = self::ROSETTA_NONE;
        }

        return '';
    }

    public static function publicHeadContent(): string
    {
        $current   = '';
        $urlTypes  = ['post'];
        $postTypes = ['post'];
        if (App::plugins()->moduleExists('pages')) {
            $urlTypes[]  = 'pages';
            $postTypes[] = 'page';
        }

        $settings = My::settings();
        if ($settings->active && App::frontend()->context()->posts instanceof MetaRecord && in_array(App::url()->getType(), $urlTypes) && in_array(App::frontend()->context()->posts->post_type, $postTypes)) {
            // Find translations and add meta in header
            $post_id   = is_numeric($post_id = App::frontend()->context()->posts->post_id) ? (int) $post_id : 0;
            $post_lang = is_string($post_lang = App::frontend()->context()->posts->post_lang) ? $post_lang : '';
            $post_type = is_string($post_type = App::frontend()->context()->posts->post_type) ? $post_type : '';
            $list      = FrontendHelper::EntryListHelper(
                $post_id,
                $post_lang,
                $post_type,
                'none',
                $current,
                true
            );
            if (is_array($list) && count($list)) {
                echo '<!-- Rosetta: translated version of this entry -->' . "\n";
                foreach ($list as $lang => $url) {
                    echo '<link rel="alternate" href="' . $url . '" hreflang="' . $lang . '">' . "\n";
                }
            }
        }

        return '';
    }

    /**
     * Finds a translated entry.
     *
     * @param      Url          $handler  The handler
     * @param      string       $lang     The language
     */
    private static function findTranslatedEntry(Url $handler, string $lang): bool
    {
        $postTypes = ['post'];
        if (App::plugins()->moduleExists('pages')) {
            $postTypes[] = 'page';
        }

        // Get post/page id

        /**
         * @var        ArrayObject<string, mixed>
         */
        $paramsSrc = new ArrayObject([
            'post_url'   => $handler->args,
            'post_type'  => $postTypes,
            'no_content' => true,
        ]);

        App::behavior()->callBehavior('publicPostBeforeGetPosts', $paramsSrc, $handler->args);
        $rsSrc = App::blog()->getPosts($paramsSrc);

        // Check if post/page id exists in rosetta table
        if ($rsSrc->count()) {
            // Load first record
            $rsSrc->fetch();

            // Try to find an associated post corresponding to the requested lang
            $post_id   = is_numeric($post_id = $rsSrc->post_id) ? (int) $post_id : 0;
            $post_lang = is_string($post_lang = $rsSrc->post_lang) ? $post_lang : '';

            // If current entry is in the requested languages, return true
            if ($post_lang === $lang) {
                return true;
            }

            $id = CoreData::findTranslation($post_id, $post_lang, $lang);
            if (($id >= 0) && ($id !== $post_id)) {
                // Get post/page URL

                /**
                 * @var        ArrayObject<string, mixed>
                 */
                $paramsDst = new ArrayObject([
                    'post_id'    => $id,
                    'post_type'  => $postTypes,
                    'no_content' => true,
                ]);

                App::behavior()->callBehavior('publicPostBeforeGetPosts', $paramsDst, $handler->args);
                $rsDst = App::blog()->getPosts($paramsDst);

                if ($rsDst->count()) {
                    // Load first record
                    $rsDst->fetch();

                    // Redirect to translated post
                    $url = is_string($url = $rsDst->getURL()) ? $url : '';
                    if ($url !== '') {
                        if (!preg_match('%^https?://%', $url)) {
                            // Prepend scheme if not present
                            $url = (isset($_SERVER['HTTPS']) ? 'https:' : 'http:') . $url;
                        }

                        Http::redirect($url);
                        exit;
                    }
                }
            }
        }

        return false;
    }

    public static function urlHandlerGetArgsDocument(Url $handler): string
    {
        $settings = My::settings();
        if (!$settings->active) {
            return '';
        }

        $langs = [];
        $lang  = isset($_GET['lang']) && is_string($lang = $_GET['lang']) ? $lang : '';

        if ($lang !== '') {
            // Check lang scheme
            if (preg_match('/^[a-z]{2}(-[a-z]{2})?$/', rawurldecode($lang), $matches)) {
                // Assume that the URL scheme is for post/page
                $langs[] = $matches[0];
            }
        } elseif ($settings->accept_language) {
            $urlType = '';
            $urlPart = '';
            $url     = is_string($url = $_SERVER['URL_REQUEST_PART']) ? $url : '';
            $handler->getArgs($url, $urlType, $urlPart);
            if (in_array($urlType, ['post', 'pages'])) {
                // It is a post or page: Try to find a translation according to the browser settings
                $langs = Http::getAcceptLanguages();
            }
        }

        if (count($langs) > 0) {
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
