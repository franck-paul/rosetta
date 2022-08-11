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

require __DIR__ . '/_widgets.php';

// Public behaviours
dcCore::app()->addBehavior('urlHandlerGetArgsDocument', ['rosettaPublicBehaviors', 'urlHandlerGetArgsDocument']);
dcCore::app()->addBehavior('publicHeadContent', ['rosettaPublicBehaviors', 'publicHeadContent']);
dcCore::app()->addBehavior('coreBlogBeforeGetPosts', ['rosettaPublicBehaviors', 'coreBlogBeforeGetPosts']);
dcCore::app()->addBehavior('coreBlogAfterGetPosts', ['rosettaPublicBehaviors', 'coreBlogAfterGetPosts']);

// Public template tags
dcCore::app()->tpl->addValue('RosettaEntryList', ['rosettaTpl', 'rosettaEntryList']);
