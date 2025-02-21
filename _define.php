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
$this->registerModule(
    'Rosetta',
    'Manage post/page translations',
    'Franck Paul',
    '5.2',
    [
        'date'        => '2025-02-21T12:05:36+0100',
        'requires'    => [['core', '2.33']],
        'permissions' => 'My',
        'priority'    => 1001,
        'type'        => 'plugin',

        'details'    => 'https://open-time.net/?q=rosetta',
        'support'    => 'https://github.com/franck-paul/rosetta',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/rosetta/main/dcstore.xml',
    ]
);
