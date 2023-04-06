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
require_once __DIR__ . '/_widgets.php';

// Public behaviours
dcCore::app()->addBehaviors([
    'urlHandlerGetArgsDocument' => [rosettaPublicBehaviors::class, 'urlHandlerGetArgsDocument'],
    'publicHeadContent'         => [rosettaPublicBehaviors::class, 'publicHeadContent'],
    'coreBlogBeforeGetPosts'    => [rosettaPublicBehaviors::class, 'coreBlogBeforeGetPosts'],
    'coreBlogAfterGetPosts'     => [rosettaPublicBehaviors::class, 'coreBlogAfterGetPosts'],
]);

// Public template tags
dcCore::app()->tpl->addValue('RosettaEntryList', [rosettaTpl::class, 'rosettaEntryList']);
