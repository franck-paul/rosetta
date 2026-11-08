<?php

/**
 * @brief rosetta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul
 *
 * @copyright Franck Paul contact@open-time.net
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Rosetta',
        'Manage post/page translations',
        'Franck Paul',
        '10.0',
        [
            'date'     => '2026-11-08T10:20:36+0100',
            'requires' => [
                ['core', '2.40'],
                ['TemplateHelper'],
            ],
            'permissions' => 'My',
            'priority'    => 1001,
            'type'        => 'plugin',

            'details'    => 'https://open-time.net/?q=rosetta',
            'support'    => 'https://github.com/franck-paul/rosetta',
            'repository' => 'https://raw.githubusercontent.com/franck-paul/rosetta/main/dcstore.xml',
            'license'    => 'gpl2',
        ]
    );
}
