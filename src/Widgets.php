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

use Dotclear\Plugin\widgets\WidgetsStack;

class Widgets
{
    public static function initWidgets(WidgetsStack $w): void
    {
        // Widget for currently displayed post
        $w
            ->create(
                'rosettaEntry',
                __('Entry\'s translations'),
                FrontendWidgets::rosettaEntryWidget(...),
                null,
                __('Translation(s) of this entry')
            )
            ->addTitle(__('Translations'))
            ->setting(
                'current',
                __('Include current entry:'),
                'std',
                'combo',
                [
                    __('Without its URL') => 'std',
                    __('With its URL')    => 'link',
                    __('None')            => 'none',
                ]
            )
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        // Widget for static home entry
        $w
            ->create(
                'rosettaStaticHome',
                __('Blog language'),
                FrontendWidgets::rosettaStaticHomeWidget(...),
                null,
                __('Language(s) of blog')
            )
            ->addTitle(__('Language'))
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }
}
