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

use dcAuth;
use dcCore;
use dcNamespace;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Manage extends dcNsProcess
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            static::$init = !empty($_REQUEST['popup_new']) ? ManagePopup::init() : true;
        }

        static::$init = My::checkContext(My::MANAGE);

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

        if (!empty($_REQUEST['popup_new'])) {
            return ManagePopup::process();
        }

        if (!empty($_POST['save_settings'])) {
            $tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

            try {
                $settings = dcCore::app()->blog->settings->get(My::id());
                $settings->put('active', empty($_POST['active']) ? false : true, dcNamespace::NS_BOOL);
                $settings->put('accept_language', empty($_POST['accept_language']) ? false : true, dcNamespace::NS_BOOL);

                dcPage::addSuccessNotice(__('Configuration successfully updated.'));
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), [
                    'tab' => $tab,
                ], '#' . $tab);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
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

        if (!empty($_REQUEST['popup_new'])) {
            ManagePopup::render();

            return;
        }

        // Main page of plugin
        $settings                = dcCore::app()->blog->settings->get(My::id());
        $rosetta_active          = $settings->active;
        $rosetta_accept_language = $settings->accept_language;

        $tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

        $head = dcPage::jsPageTabs($tab);

        dcPage::openModule(__('Rosetta'), $head);

        echo dcPage::breadcrumb(
            [
                Html::escapeHTML(dcCore::app()->blog->name) => '',
                __('Rosetta')                               => '',
            ]
        );
        echo dcPage::notices();

        // Form

        // Display tabs
        // 1. Posts translations
        echo
        '<div id="posts" class="multi-part" title="' . __('Posts tranlations') . '">' .
        '<h3>' . __('Posts tranlations') . '</h3>' .
            '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post">';

        // TODO

        echo
        '<p class="field"><input type="submit" value="' . __('Save') . '" /> ' .
        form::hidden(['tab'], 'posts') .
        dcCore::app()->formNonce() . '</p>' .
            '</form>' .
            '</div>';

        // 2. Pages translations
        if (dcCore::app()->plugins->moduleExists('pages')) {
            echo
            '<div id="pages" class="multi-part" title="' . __('Pages tranlations') . '">' .
            '<h3>' . __('Pages tranlations') . '</h3>' .
                '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post">';

            // TODO

            echo
            '<p class="field"><input type="submit" value="' . __('Save') . '" /> ' .
            form::hidden(['tab'], 'pages') .
            dcCore::app()->formNonce() . '</p>' .
                '</form>' .
                '</div>';
        }

        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
        ]), dcCore::app()->blog->id)) {
            // 3. Plugin settings
            echo
            '<div id="settings" class="multi-part" title="' . __('Settings') . '">' .
            '<h3>' . __('Settings') . '</h3>' .
            '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post">' .

            '<h4 class="pretty-title">' . __('Activation') . '</h4>' .
            '<p>' . form::checkbox('active', 1, $rosetta_active) .
            '<label class="classic" for="active">' . __('Enable posts/pages translations for this blog') . '</label></p>' .

            '<h4 class="pretty-title">' . __('Options') . '</h4>' .
            '<p>' . form::checkbox('accept_language', 1, $rosetta_accept_language) .
            '<label class="classic" for="accept_language">' . __('Automatic posts/pages redirect on browser\'s language for this blog') . '</label></p>';

            echo
            '<p class="field wide"><input type="submit" value="' . __('Save') . '" /> ' .
            form::hidden(['tab'], 'settings') .
            form::hidden(['save_settings'], 1) .
            dcCore::app()->formNonce() . '</p>' .
                '</form>' .
                '</div>';
        }

        dcPage::closeModule();
    }
}
