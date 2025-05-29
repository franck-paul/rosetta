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

use Dotclear\App;

class FrontendTemplateCode
{
    /**
     * tpl:BlogStaticEntryURL [attributes] : Prepare the blog's static home URL entry (tpl value)
     *
     * Should be set before a tpl:Entries block to display the according entry (post, page, …)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */

    /**
     * PHP code for tpl:BlogStaticEntryURL value
     *
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function BlogStaticEntryURL(
        bool $_override_,
        array $_params_,
        string $_tag_,
    ): void {
        $rosetta_url = App::blog()->settings()->system->static_home_url;
        if ($_override_ && $rosetta_url) {
            if (!empty($_GET['lang'])) {
                // Check lang scheme
                if (preg_match('/^[a-z]{2}(-[a-z]{2})?$/', rawurldecode((string) $_GET['lang']), $matches)) {
                    // Assume that the URL scheme is for post/page
                    $rosetta_langs[] = $matches[0];
                }
            } else {
                $rosetta_langs = \Dotclear\Helper\Network\Http::getAcceptLanguages();
            }
            if (count($rosetta_langs) > 0) {
                foreach ($rosetta_langs as $rosetta_lang) {
                    $rosetta_new = \Dotclear\Plugin\rosetta\FrontendHelper::findTranslatedEntry($rosetta_url, $rosetta_lang);
                    if ($rosetta_new !== '') {
                        $rosetta_url = $rosetta_new;

                        break;
                    }
                }
            }
        }
        $params['post_type'] = array_keys(App::postTypes()->dump());
        $params['post_url']  = \Dotclear\Core\Frontend\Ctx::global_filters(
            $rosetta_url,
            $_params_,
            $_tag_
        );

        unset($rosetta_url, $rosetta_langs, $rosetta_lang, $rosetta_new);
    }

    /**
     * PHP code for tpl:RosettaEntryList value
     *
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function rosettaEntryList(
        string $_option_,
        array $_params_,
        string $_tag_,
    ): void {
        $rosetta_current = '';
        $rosetta_table   = \Dotclear\Plugin\rosetta\FrontendHelper::EntryListHelper(
            App::frontend()->context()->posts->post_id,
            App::frontend()->context()->posts->post_lang,
            App::frontend()->context()->posts->post_type,
            $_option_,
            $rosetta_current
        );

        if (is_array($rosetta_table) && count($rosetta_table)) {
            $rosetta_list = [];
            foreach ($rosetta_table as $rosetta_name => $rosetta_url) {
                $rosetta_is_current = $rosetta_name === $rosetta_current;
                $rosetta_text       = $rosetta_is_current ?
                    (new \Dotclear\Helper\Html\Form\Strong(\Dotclear\Helper\Html\Html::escapeHTML($rosetta_name))) :
                    (new \Dotclear\Helper\Html\Form\Text(\Dotclear\Helper\Html\Html::escapeHTML($rosetta_name)));
                if (!$rosetta_is_current || $_option_ === 'link') {
                    $rosetta_item = (new \Dotclear\Helper\Html\Form\Link())
                        ->href($rosetta_url)
                        ->items([
                            $rosetta_text,
                        ]);
                } else {
                    $rosetta_item = $rosetta_text;
                }
                $rosetta_list[] = (new \Dotclear\Helper\Html\Form\Li())
                    ->class($rosetta_is_current ? 'current' : '')
                    ->items([
                        $rosetta_item,
                    ]);
            }
            echo (new \Dotclear\Helper\Html\Form\Ul())
                ->class('rosetta-entries-list')
                ->items($rosetta_list)
            ->render();

            unset($rosetta_list, $rosetta_name, $rosetta_url, $rosetta_is_current, $rosetta_text, $rosetta_item);
        }

        unset($rosetta_current, $rosetta_table);
    }
}
