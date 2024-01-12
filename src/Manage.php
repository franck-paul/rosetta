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
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Manage extends Process
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            return self::status(empty($_REQUEST['popup_new']) ? true : ManagePopup::init());
        }

        return self::status(My::checkContext(My::MANAGE));
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!empty($_REQUEST['popup_new'])) {
            return ManagePopup::process();
        }

        if (!empty($_POST['save_settings'])) {
            $tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

            try {
                $settings = My::settings();
                $settings->put('active', !empty($_POST['active']), App::blogWorkspace()::NS_BOOL);
                $settings->put('accept_language', !empty($_POST['accept_language']), App::blogWorkspace()::NS_BOOL);

                Notices::addSuccessNotice(__('Configuration successfully updated.'));
                My::redirect([
                    'tab' => $tab,
                ], '#' . $tab);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
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

        if (!empty($_REQUEST['popup_new'])) {
            ManagePopup::render();

            return;
        }

        // Main page of plugin
        $settings                = My::settings();
        $rosetta_active          = $settings->active;
        $rosetta_accept_language = $settings->accept_language;

        $tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

        $head = Page::jsPageTabs($tab);

        Page::openModule(My::name(), $head);

        echo Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('Rosetta')                         => '',
            ]
        );
        echo Notices::getNotices();

        // Form

        // Display tabs
        // 1. Posts translations
        echo
        '<div id="posts" class="multi-part" title="' . __('Posts tranlations') . '">' .
        '<h3>' . __('Posts tranlations') . '</h3>' .
            '<form action="' . App::backend()->getPageURL() . '" method="post">';

        // TODO

        echo
        '<p class="field"><input type="submit" value="' . __('Save') . '"> ' .
        My::parsedHiddenFields([
            'tab' => 'posts',
        ]) .
        '</p>' .
        '</form>' .
        '</div>';

        // 2. Pages translations
        if (App::plugins()->moduleExists('pages')) {
            echo
            '<div id="pages" class="multi-part" title="' . __('Pages tranlations') . '">' .
            '<h3>' . __('Pages tranlations') . '</h3>' .
                '<form action="' . App::backend()->getPageURL() . '" method="post">';

            // TODO

            echo
            '<p class="field"><input type="submit" value="' . __('Save') . '"> ' .
            My::parsedHiddenFields([
                'tab' => 'pages',
            ]) .
            '</p>' .
            '</form>' .
            '</div>';
        }

        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]), App::blog()->id())) {
            // 3. Plugin settings
            echo
            '<div id="settings" class="multi-part" title="' . __('Settings') . '">' .
            '<h3>' . __('Settings') . '</h3>' .
            '<form action="' . App::backend()->getPageURL() . '" method="post">' .

            '<h4 class="pretty-title">' . __('Activation') . '</h4>' .
            '<p>' . form::checkbox('active', 1, $rosetta_active) .
            '<label class="classic" for="active">' . __('Enable posts/pages translations for this blog') . '</label></p>' .

            '<h4 class="pretty-title">' . __('Options') . '</h4>' .
            '<p>' . form::checkbox('accept_language', 1, $rosetta_accept_language) .
            '<label class="classic" for="accept_language">' . __('Automatic posts/pages redirect on browser\'s language for this blog') . '</label></p>';

            echo
            '<p class="field wide"><input type="submit" value="' . __('Save') . '"> ' .
            My::parsedHiddenFields([
                'tab'           => 'settings',
                'save_settings' => '1',
            ]) .
            '</p>' .
            '</form>' .
            '</div>';
        }

        Page::closeModule();
    }
}
