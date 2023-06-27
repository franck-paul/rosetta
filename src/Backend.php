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

use dcAdmin;
use dcCore;
use dcNsProcess;

class Backend extends dcNsProcess
{
    protected static $init = false; /** @deprecated since 2.27 */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::BACKEND);

        // dead but useful code, in order to have translations
        __('Rosetta') . __('Manage post/page translations');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
            __('Rosetta'),
            My::makeUrl(),
            My::icons(),
            preg_match(My::urlScheme(), $_SERVER['REQUEST_URI']),
            My::checkContext(My::MENU)
        );

        dcCore::app()->addBehaviors([
            // Register favorite
            'adminDashboardFavoritesV2' => [BackendBehaviors::class, 'adminDashboardFavorites'],

            // Add behaviour callback for post
            'adminPostForm'    => [BackendBehaviors::class, 'adminPostForm'],
            'adminPostHeaders' => [BackendBehaviors::class, 'adminPostHeaders'],

            // Add behaviour callback for page
            'adminPageForm'    => [BackendBehaviors::class, 'adminPageForm'],
            'adminPageHeaders' => [BackendBehaviors::class, 'adminPageHeaders'],

            // Add behaviour callback for post/page list popup
            'adminPopupPosts' => [BackendBehaviors::class, 'adminPopupPosts'],

            // Add behaviour callback for post/page lists
            'adminColumnsListsV2'       => [BackendBehaviors::class, 'adminColumnsLists'],
            'adminPostListHeaderV2'     => [BackendBehaviors::class, 'adminPostListHeader'],
            'adminPostListValueV2'      => [BackendBehaviors::class, 'adminPostListValue'],
            'adminPostMiniListHeaderV2' => [BackendBehaviors::class, 'adminPostMiniListHeader'],
            'adminPostMiniListValue'    => [BackendBehaviors::class, 'adminPostMiniListValue'],
            'adminPagesListHeaderV2'    => [BackendBehaviors::class, 'adminPagesListHeader'],
            'adminPagesListValueV2'     => [BackendBehaviors::class, 'adminPagesListValue'],
            'adminFiltersListsV2'       => [BackendBehaviors::class, 'adminFiltersLists'],

            // Add behaviour callback for import/export
            'exportSingleV2' => [BackendBehaviors::class, 'exportSingle'],
            'exportFullV2'   => [BackendBehaviors::class, 'exportFull'],
            'importInitV2'   => [BackendBehaviors::class, 'importInit'],
            'importSingleV2' => [BackendBehaviors::class, 'importSingle'],
            'importFullV2'   => [BackendBehaviors::class, 'importFull'],
        ]);

        // Register REST methods
        dcCore::app()->rest->addFunction('newTranslation', [BackendRest::class, 'newTranslation']);
        dcCore::app()->rest->addFunction('addTranslation', [BackendRest::class, 'addTranslation']);
        dcCore::app()->rest->addFunction('removeTranslation', [BackendRest::class, 'removeTranslation']);
        dcCore::app()->rest->addFunction('getTranslationRow', [BackendRest::class, 'getTranslationRow']);

        if (My::checkContext(My::WIDGETS)) {
            dcCore::app()->addBehaviors([
                'initWidgets' => [Widgets::class, 'initWidgets'],
            ]);
        }

        return true;
    }
}
