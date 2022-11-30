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

require_once __DIR__ . '/_widgets.php';

// Public behaviours
dcCore::app()->addBehavior('urlHandlerGetArgsDocument', [rosettaPublicBehaviors::class, 'urlHandlerGetArgsDocument']);
dcCore::app()->addBehavior('publicHeadContent', [rosettaPublicBehaviors::class, 'publicHeadContent']);
dcCore::app()->addBehavior('coreBlogBeforeGetPosts', [rosettaPublicBehaviors::class, 'coreBlogBeforeGetPosts']);
dcCore::app()->addBehavior('coreBlogAfterGetPosts', [rosettaPublicBehaviors::class, 'coreBlogAfterGetPosts']);

// Public template tags
dcCore::app()->tpl->addValue('RosettaEntryList', [rosettaTpl::class, 'rosettaEntryList']);
