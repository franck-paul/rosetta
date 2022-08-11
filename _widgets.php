<?php
/**
 * @brief rosetta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_RC_PATH')) {
    return;
}

dcCore::app()->addBehavior('initWidgets', ['rosettaWidgets', 'initWidgets']);

class rosettaWidgets
{
    public static function initWidgets($w)
    {
        // Widget for currently displayed post
        $w
            ->create(
                'rosettaEntry',
                __('Entry\'s translations'),
                ['rosettaTpl', 'rosettaEntryWidget'],
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
    }
}
