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
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use form;

class ManagePopup extends Process
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE) && !empty($_REQUEST['popup_new']));
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $id   = !empty($_GET['id']) ? $_GET['id'] : '';
        $lang = !empty($_GET['lang']) ? $_GET['lang'] : '';

        $title = '';

        // Languages combo
        $rs         = App::blog()->getLangs(['order' => 'asc']);
        $lang_combo = Combos::getLangsCombo($rs, true);
        // Remove empty select
        unset($lang_combo['']);
        // Remove already existed translation's languages from combo
        $ids = CoreData::findAllTranslations($id, $lang, true);
        if (is_array($ids)) {
            foreach ($lang_combo as $lc => $lv) {
                if (is_array($lv)) {
                    foreach ($lv as $name => $code) {
                        if (array_key_exists($code, $ids)) {
                            unset($lang_combo[$lc][$name]);
                        }
                    }
                    if (!(is_countable($lang_combo[$lc]) ? count($lang_combo[$lc]) : 0)) {
                        unset($lang_combo[$lc]);
                    }
                }
            }
        }

        $head = My::jsLoad('popup_new.js');

        Page::openModule(__('Create a new translation'), $head);

        echo Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('Rosetta')                         => '',
            ]
        );
        echo Notices::getNotices();

        echo
        '<form id="link-insert-form" action="#" method="get">' .

        '<p><label for="title">' . __('Entry title:') . '</label> ' .
        form::field('title', 35, 512, Html::escapeHTML($title)) . '</p>' .

        '<p><label for="lang">' . __('Entry language:') . '</label> ' .
        form::combo('lang', $lang_combo, $lang) . '</p>' .

        '</form>' .

        '<p><button class="reset" id="rosetta-new-cancel">' . __('Cancel') . '</button> - ' .
        '<button id="rosetta-new-ok"><strong>' . __('Create') . '</strong></button></p>' . "\n";

        Page::closeModule();
    }
}
