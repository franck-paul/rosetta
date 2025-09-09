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
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

class Manage
{
    use TraitProcess;

    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        return empty($_REQUEST['popup_new']) ?
            self::status(My::checkContext(My::MANAGE)) :
            ManagePopup::init();
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
        echo (new Div('posts'))
            ->class('multi-part')
            ->title(__('Posts tranlations'))
            ->items([
                (new Form('form_posts'))
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        // TODO …
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                ... My::hiddenFields(['tab' => 'posts']),
                                (new Submit('form_posts_submit', __('Save'))),
                            ]),
                    ]),
            ])
        ->render();

        // 2. Pages translations
        if (App::plugins()->moduleExists('pages')) {
            echo (new Div('pages'))
                ->class('multi-part')
                ->title(__('Pages tranlations'))
                ->items([
                    (new Form('form_pages'))
                        ->method('post')
                        ->action(App::backend()->getPageURL())
                        ->fields([
                            // TODO …
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    ... My::hiddenFields(['tab' => 'pages']),
                                    (new Submit('form_pages_submit', __('Save'))),
                                ]),
                        ]),
                ])
            ->render();
        }

        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]), App::blog()->id())) {
            // 3. Plugin settings
            echo (new Div('settings'))
                ->class('multi-part')
                ->title(__('Settings'))
                ->items([
                    (new Form('form_posts'))
                        ->method('post')
                        ->action(App::backend()->getPageURL())
                        ->fields([
                            (new Fieldset())
                                ->legend(new Legend(__('Activation')))
                                ->fields([
                                    (new Para())
                                        ->items([
                                            (new Checkbox('active', $rosetta_active))
                                                ->value(1)
                                                ->label(new Label(__('Enable posts/pages translations for this blog'), Label::IL_FT)),
                                        ]),
                                ]),
                            (new Fieldset())
                                ->legend(new Legend(__('Options')))
                                ->fields([
                                    (new Para())
                                        ->items([
                                            (new Checkbox('accept_language', $rosetta_accept_language))
                                                ->value(1)
                                                ->label(new Label(__('Automatic posts/pages redirect on browser\'s language for this blog'), Label::IL_FT)),
                                        ]),
                                ]),
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    ... My::hiddenFields([
                                        'tab'           => 'settings',
                                        'save_settings' => '1',
                                    ]),
                                    (new Submit('form_settings_submit', __('Save'))),
                                ]),
                        ]),
                ])
            ->render();
        }

        Page::closeModule();
    }
}
