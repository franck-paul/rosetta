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

// Public and Admin mode

$__autoload['rosettaPublicBehaviors'] = __DIR__ . '/inc/rosetta.behaviors.php';
$__autoload['rosettaTpl']             = __DIR__ . '/inc/rosetta.tpl.php';
$__autoload['rosettaData']            = __DIR__ . '/inc/rosetta.data.php';
$__autoload['rosettaRest']            = __DIR__ . '/_services.php';

if (!defined('DC_CONTEXT_ADMIN')) {
    return false;
}

// Admin mode only

$__autoload['rosettaAdminBehaviors'] = __DIR__ . '/inc/rosetta.behaviors.php';
