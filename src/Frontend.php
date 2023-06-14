<?php
/**
 * @brief rosetta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\rosetta;

use dcCore;
use dcNsProcess;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::checkContext(My::FRONTEND);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // Don't do things in frontend if plugin disabled
        $settings = dcCore::app()->blog->settings->get(My::id());
        if (!(bool) $settings->active) {
            return false;
        }

        // Public behaviours
        dcCore::app()->addBehaviors([
            'urlHandlerGetArgsDocument' => [FrontendBehaviors::class, 'urlHandlerGetArgsDocument'],
            'publicHeadContent'         => [FrontendBehaviors::class, 'publicHeadContent'],
            'coreBlogBeforeGetPosts'    => [FrontendBehaviors::class, 'coreBlogBeforeGetPosts'],
            'coreBlogAfterGetPosts'     => [FrontendBehaviors::class, 'coreBlogAfterGetPosts'],

            'initWidgets' => [Widgets::class, 'initWidgets'],
        ]);

        // Public template tags
        dcCore::app()->tpl->addValue('RosettaEntryList', [FrontendTemplate::class, 'rosettaEntryList']);

        return true;
    }
}
