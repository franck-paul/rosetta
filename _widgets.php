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

if (!defined('DC_RC_PATH')) {return;}

$core->addBehavior('initWidgets', ['rosettaWidgets', 'initWidgets']);

class rosettaWidgets
{
    public static function initWidgets($w)
    {
        // Widget for currently displayed post
        $w->create('rosettaEntry', __('Entry\'s translations'), ['rosettaTpl', 'rosettaEntryWidget'],
            null, __('Translation(s) of this entry'));
        $w->rosettaEntry->setting('title', __('Title:'), __('Translations'));
        $w->rosettaEntry->setting('current', __('Include current entry:'), 'std', 'combo',
            [__('Without its URL') => 'std', __('With its URL') => 'link', __('None') => 'none']
        );
        $w->rosettaEntry->setting('content_only', __('Content only'), 0, 'check');
        $w->rosettaEntry->setting('class', __('CSS class:'), '');
        $w->rosettaEntry->setting('offline', __('Offline'), 0, 'check');
    }
}
