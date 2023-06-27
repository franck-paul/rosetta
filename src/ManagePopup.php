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

use dcAdminCombos;
use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Html\Html;
use form;

class ManagePopup extends dcNsProcess
{
    protected static $init = false; /** @deprecated since 2.27 */
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::MANAGE) && !empty($_REQUEST['popup_new']);

        return static::$init;
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        $id   = !empty($_GET['id']) ? $_GET['id'] : '';
        $type = !empty($_GET['type']) ? $_GET['type'] : '';
        $lang = !empty($_GET['lang']) ? $_GET['lang'] : '';

        $title = '';

        // Languages combo
        $rs         = dcCore::app()->blog->getLangs(['order' => 'asc']);
        $lang_combo = dcAdminCombos::getLangsCombo($rs, true);
        // Remove empty select
        unset($lang_combo['']);
        // Remove already existed translation's languages from combo
        $ids = CoreData::findAllTranslations((int) $id, $lang, true);
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

        $head = dcPage::jsModuleLoad(My::id() . '/js/popup_new.js', dcCore::app()->getVersion('rosetta'));

        dcPage::openModule(__('Create a new translation'), $head);

        echo dcPage::breadcrumb(
            [
                Html::escapeHTML(dcCore::app()->blog->name) => '',
                __('Rosetta')                               => '',
            ]
        );
        echo dcPage::notices();

        echo
        '<form id="link-insert-form" action="#" method="get">' .

        '<p><label for="title">' . __('Entry title:') . '</label> ' .
        form::field('title', 35, 512, Html::escapeHTML($title)) . '</p>' .

        '<p><label for="lang">' . __('Entry language:') . '</label> ' .
        form::combo('lang', $lang_combo, $lang) . '</p>' .

        '</form>' .

        '<p><button class="reset" id="rosetta-new-cancel">' . __('Cancel') . '</button> - ' .
        '<button id="rosetta-new-ok"><strong>' . __('Create') . '</strong></button></p>' . "\n";

        dcPage::closeModule();
    }
}
