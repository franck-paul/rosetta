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
    '2.3',
    [
        'requires'    => [['core', '2.26']],
        'permissions' => dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]),
        'priority' => 1001,
        'type'     => 'plugin',

        'details'    => 'https://open-time.net/?q=rosetta',
        'support'    => 'https://github.com/franck-paul/rosetta',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/rosetta/master/dcstore.xml',
    ]
);
