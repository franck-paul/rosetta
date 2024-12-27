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
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsElement;

class FrontendWidgets
{
    public static function rosettaEntryWidget(WidgetsElement $w): string
    {
        $settings = My::settings();
        if (!$settings->active) {
            return '';
        }

        if ($w->offline) {
            return '';
        }

        $urlTypes = ['post'];
        if (App::plugins()->moduleExists('pages')) {
            $urlTypes[] = 'pages';
        }

        if (!in_array(App::url()->getType(), $urlTypes)) {
            return '';
        }

        // Get list of available translations for current entry
        $post_type = (App::url()->getType() == 'post' ? 'post' : 'page');
        $current   = '';
        $table     = FrontendHelper::EntryListHelper((int) App::frontend()->context()->posts->post_id, App::frontend()->context()->posts->post_lang, $post_type, $w->get('current'), $current);
        if (!is_array($table)) {
            return '';
        }

        // Render widget title
        $res = ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) . "\n" : '');

        // Render widget list of translations
        $list = '';
        foreach ($table as $name => $url) {
            $link  = ($name != $current || $w->get('current') == 'link');
            $class = ($name == $current ? ' class="current"' : '');

            $list .= '<li' . $class . '>' .
                ($link ? '<a href="' . $url . '">' : '') .
                ($class !== '' ? '<strong>' : '') . Html::escapeHTML($name) . ($class !== '' ? '</strong>' : '') .
                ($link ? '</a>' : '') .
                '</li>' . "\n";
        }

        if ($list === '') {
            return '';
        }

        $res .= '<ul>' . $list . '</ul>' . "\n";

        // Render full content
        return $w->renderDiv((bool) $w->content_only, 'rosetta-entries ' . $w->class, '', $res);
    }
}
