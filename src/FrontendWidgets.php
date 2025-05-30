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
use Dotclear\Helper\L10n;
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

        $urlTypes = [
            'post',
            'pages',
        ];

        if (in_array(App::url()->getType(), $urlTypes)) {
            $post_id   = (int) App::frontend()->context()->posts->post_id;
            $post_lang = App::frontend()->context()->posts->post_lang;
            $post_type = App::frontend()->context()->posts->post_type;
        } else {
            return '';
        }

        // Get list of available translations for current entry
        $current = '';
        $table   = FrontendHelper::EntryListHelper($post_id, $post_lang, $post_type, $w->get('current'), $current);
        if (!is_array($table)) {
            return '';
        }

        // Render widget title
        $res = ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) . "\n" : '');

        // Render widget list of translations
        $list = '';
        foreach ($table as $name => $url) {
            $link  = ($name !== $current || $w->get('current') == 'link');
            $class = ($name === $current ? ' class="current"' : '');

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

    public static function rosettaStaticHomeWidget(WidgetsElement $w): string
    {
        $settings = My::settings();
        if (!$settings->active) {
            return '';
        }

        if ($w->offline) {
            return '';
        }

        if (App::blog()->settings()->system->static_home_url) {
            $rs = App::blog()->getPosts(['post_url' => App::blog()->settings()->system->static_home_url, 'post_type' => '']);
            if ($rs->isEmpty()) {
                return '';
            }
            $post_id   = (int) $rs->post_id;
            $post_lang = $rs->post_lang;
            $post_type = $rs->post_type;
        } else {
            return '';
        }

        // Get list of available translations for current entry
        $current = '';
        $table   = FrontendHelper::EntryListHelper($post_id, $post_lang, $post_type, 'link', $current, true);
        if (!is_array($table)) {
            return '';
        }

        // Get current language if set in URL
        if (isset($_GET['lang'])) {
            // Get current language
            $current = Html::escapeHTML($_GET['lang']);
        }

        // Render widget title
        $res = ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) . "\n" : '');

        // Render widget list of translations
        $list  = '';
        $langs = L10n::getLanguagesName();

        foreach ($table as $name => $url) {
            $class = ($name === $current ? ' class="current"' : '');

            $url  = App::blog()->getQmarkURL() . 'lang=' . $name;
            $name = $langs[$name] ?? $langs[App::blog()->settings()->system->lang];

            $list .= '<li' . $class . '>' .
                '<a href="' . $url . '">' .
                ($class !== '' ? '<strong>' : '') . Html::escapeHTML($name) . ($class !== '' ? '</strong>' : '') .
                '</a>' .
                '</li>' . "\n";
        }

        if ($list === '') {
            return '';
        }

        $res .= '<ul>' . $list . '</ul>' . "\n";

        // Render full content
        return $w->renderDiv((bool) $w->content_only, 'rosetta-static-home ' . $w->class, '', $res);
    }
}
