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

require dirname(__FILE__) . '/_widgets.php';

// Public behaviours
$core->addBehavior('urlHandlerGetArgsDocument', ['rosettaPublicBehaviors', 'urlHandlerGetArgsDocument']);
$core->addBehavior('publicHeadContent', ['rosettaPublicBehaviors', 'publicHeadContent']);
$core->addBehavior('coreBlogBeforeGetPosts', ['rosettaPublicBehaviors', 'coreBlogBeforeGetPosts']);
$core->addBehavior('coreBlogAfterGetPosts', ['rosettaPublicBehaviors', 'coreBlogAfterGetPosts']);

// Public template tags
$core->tpl->addValue('RosettaEntryList', ['rosettaTpl', 'rosettaEntryList']);
