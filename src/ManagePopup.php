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
use Dotclear\Helper\Html\Form\Btn;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Html;

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
        return (bool) self::status();
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $id   = empty($_GET['id']) ? '' : $_GET['id'];
        $lang = empty($_GET['lang']) ? '' : $_GET['lang'];

        $title = '';

        // Languages combo
        $rs = App::blog()->getLangs([
            'order_by' => 'nb_post',
            'order'    => 'desc',
        ]);
        $lang_combo = Combos::getLangsCombo($rs, true);
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

                    if ((is_countable($lang_combo[$lc]) ? count($lang_combo[$lc]) : 0) === 0) {
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

        echo (new Set())
            ->items([
                (new Form('link-insert-form'))
                    ->method('get')
                    ->action('#')
                    ->fields([
                        (new Para())
                            ->items([
                                (new Input('title'))
                                    ->size(35)
                                    ->maxlength(512)
                                    ->default(Html::escapeHTML($title))
                                    ->label(new Label(__('Entry title:'), Label::OL_TF)),
                            ]),
                        (new Para())
                            ->items([
                                (new Select('lang'))
                                    ->items($lang_combo)
                                    ->default($lang)
                                    ->label(new Label(__('Entry language:'), Label::OL_TF)),
                            ]),
                    ]),
                (new Para())
                    ->class(['form-buttons', 'vertical-separator'])
                    ->items([
                        (new Btn('rosetta-new-cancel', __('Cancel')))
                            ->class('reset'),
                        (new Btn('rosetta-new-ok', __('Create')))
                            ->class('submit'),
                    ]),
            ])
        ->render();

        Page::closeModule();
    }
}
