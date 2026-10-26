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

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsElement;

class FrontendWidgets
{
    public static function rosettaEntryWidget(WidgetsElement $widgetsElement): string
    {
        $settings = My::settings();
        if (!$settings->getBool('active')) {
            return '';
        }

        if ($widgetsElement->isOffline()) {
            return '';
        }

        if (App::url()->isType(['post', 'pages']) && App::frontend()->context()->posts instanceof MetaRecord) {
            $post_id   = App::frontend()->context()->posts->intField('post_id');
            $post_lang = App::frontend()->context()->posts->strField('post_lang');
            $post_type = App::frontend()->context()->posts->strField('post_type');
        } else {
            return '';
        }

        // Get list of available translations for current entry
        $current = '';
        $include = is_string($include = $widgetsElement->get('current')) ? $include : '';
        $table   = FrontendHelper::EntryListHelper($post_id, $post_lang, $post_type, $include, $current);
        if (!is_array($table)) {
            return '';
        }

        // Render widget title
        $res = ($widgetsElement->getTitle() !== '' ? $widgetsElement->renderTitle(Html::escapeHTML($widgetsElement->getTitle())) . "\n" : '');

        // Render widget list of translations
        $list = '';
        foreach ($table as $name => $url) {
            $link  = ($name !== $current || $widgetsElement->get('current') == 'link');
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
        return $widgetsElement->renderDiv($widgetsElement->getContentOnly(), 'rosetta-entries ' . $widgetsElement->getClass(), '', $res);
    }

    public static function rosettaStaticHomeWidget(WidgetsElement $widgetsElement): string
    {
        $settings = My::settings();
        if (!$settings->getBool('active')) {
            return '';
        }

        if ($widgetsElement->isOffline()) {
            return '';
        }

        if (!$widgetsElement->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $static_home_url = App::blog()->settings()->get('system')->getStr('static_home_url', false);
        if ($static_home_url !== '') {
            $rs = App::blog()->getPosts(['post_url' => $static_home_url, 'post_type' => '']);
            if ($rs->isEmpty()) {
                return '';
            }

            $post_id   = $rs->intField('post_id');
            $post_lang = $rs->strField('post_lang');
            $post_type = $rs->strField('post_type');
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
        $lang = isset($_GET['lang']) && is_string($lang = $_GET['lang']) ? $lang : '';
        if ($lang !== '') {
            // Get current language
            $current = Html::escapeHTML($lang);
        }

        // Render widget title
        $res = ($widgetsElement->getTitle() !== '' ? $widgetsElement->renderTitle(Html::escapeHTML($widgetsElement->getTitle())) . "\n" : '');

        // Render widget list of translations
        $list        = '';
        $langs       = App::lang()->getLanguagesName();
        $system_lang = App::blog()->settings()->get('system')->getStr('lang', false) ?: 'en';

        $names = array_keys($table);
        foreach ($names as $name) {
            $class = ($name === $current ? ' class="current"' : '');

            $url  = App::blog()->getQmarkURL() . 'lang=' . $name;
            $name = $langs[$name] ?? $langs[$system_lang] ?? $system_lang;

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
        return $widgetsElement->renderDiv($widgetsElement->getContentOnly(), 'rosetta-static-home ' . $widgetsElement->getClass(), '', $res);
    }
}
