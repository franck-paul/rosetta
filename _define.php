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

$this->registerModule(
    "Rosetta",                       // Name
    "Manage post/page translations", // Description
    "Franck Paul",                   // Author
    '0.8',                           // Version
    [
        'requires'    => [['core', '2.16']],                       // Dependencies
        'permissions' => 'usage,contentadmin',                     // Permissions
        'priority'    => 1001,                                     // Must be higher than pages plugin // Priority
        'type'        => 'plugin',                                 // Type
        'details'     => 'https://open-time.net/?q=rosetta',       // Details URL
        'support'     => 'https://github.com/franck-paul/rosetta' // Support URL
    ]
);
