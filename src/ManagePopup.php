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
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Btn;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;

class ManagePopup
{
    use TraitProcess;

    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        return empty($_REQUEST['popup_new']) ?
            self::status(My::checkContext(My::MANAGE)) :
            self::status(My::checkContext(My::BACKEND));
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        return self::status();
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
        $langs = [];
        $ids   = CoreData::findAllTranslations((int) $id, $lang, true);
        $rs    = App::blog()->getLangs([
            'order_by' => 'nb_post',
            'order'    => 'desc',
        ]);
        while ($rs->fetch()) {
            if (is_array($ids) && !array_key_exists($rs->post_lang, $ids)) {
                $langs[] = ['post_lang' => $rs->post_lang];
            }
        }

        $lang_combo = App::backend()->combos()->getLangsCombo(MetaRecord::newFromArray($langs), true, true);
        // Remove 1st empty option
        unset($lang_combo[0]);

        $head = My::jsLoad('popup_new.js');

        App::backend()->page()->openModule(__('Create a new translation'), $head);

        echo App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('Rosetta')                         => '',
            ]
        );
        echo App::backend()->notices()->getNotices();

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

        App::backend()->page()->closeModule();
    }
}
