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

Clearbricks::lib()->autoload([
    'rosettaPublicBehaviors' => __DIR__ . '/inc/rosetta.behaviors.php',
    'rosettaTpl'             => __DIR__ . '/inc/rosetta.tpl.php',
    'rosettaData'            => __DIR__ . '/inc/rosetta.data.php',
    'rosettaRest'            => __DIR__ . '/_services.php',
]);

if (!defined('DC_CONTEXT_ADMIN')) {
    return false;
}

// Admin mode only

Clearbricks::lib()->autoload(['rosettaAdminBehaviors' => __DIR__ . '/inc/rosetta.behaviors.php']);
