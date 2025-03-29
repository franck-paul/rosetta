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
                $rosetta_text       = (new \Dotclear\Helper\Html\Form\Text(
                    $rosetta_is_current ? 'strong' : null,
                    \Dotclear\Helper\Html\Html::escapeHTML($rosetta_name)
                ));
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
