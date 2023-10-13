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
use Dotclear\Core\Backend\Menus;
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        // dead but useful code, in order to have translations
        __('Rosetta') . __('Manage post/page translations');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(Menus::MENU_BLOG);

        dcCore::app()->addBehaviors([
            // Register favorite
            'adminDashboardFavoritesV2' => BackendBehaviors::adminDashboardFavorites(...),

            // Add behaviour callback for post
            'adminPostForm'    => BackendBehaviors::adminPostForm(...),
            'adminPostHeaders' => BackendBehaviors::adminPostHeaders(...),

            // Add behaviour callback for page
            'adminPageForm'    => BackendBehaviors::adminPageForm(...),
            'adminPageHeaders' => BackendBehaviors::adminPageHeaders(...),

            // Add behaviour callback for post/page list popup
            'adminPopupPosts' => BackendBehaviors::adminPopupPosts(...),

            // Add behaviour callback for post/page lists
            'adminColumnsListsV2'       => BackendBehaviors::adminColumnsLists(...),
            'adminPostListHeaderV2'     => BackendBehaviors::adminPostListHeader(...),
            'adminPostListValueV2'      => BackendBehaviors::adminPostListValue(...),
            'adminPostMiniListHeaderV2' => BackendBehaviors::adminPostMiniListHeader(...),
            'adminPostMiniListValueV2'  => BackendBehaviors::adminPostMiniListValue(...),
            'adminPagesListHeaderV2'    => BackendBehaviors::adminPagesListHeader(...),
            'adminPagesListValueV2'     => BackendBehaviors::adminPagesListValue(...),
            'adminFiltersListsV2'       => BackendBehaviors::adminFiltersLists(...),

            // Add behaviour callback for import/export
            'exportSingleV2' => BackendBehaviors::exportSingle(...),
            'exportFullV2'   => BackendBehaviors::exportFull(...),
            'importInitV2'   => BackendBehaviors::importInit(...),
            'importSingleV2' => BackendBehaviors::importSingle(...),
            'importFullV2'   => BackendBehaviors::importFull(...),
        ]);

        // Register REST methods
        dcCore::app()->rest->addFunction('newTranslation', BackendRest::newTranslation(...));
        dcCore::app()->rest->addFunction('addTranslation', BackendRest::addTranslation(...));
        dcCore::app()->rest->addFunction('removeTranslation', BackendRest::removeTranslation(...));
        dcCore::app()->rest->addFunction('getTranslationRow', BackendRest::getTranslationRow(...));

        if (My::checkContext(My::WIDGETS)) {
            dcCore::app()->addBehaviors([
                'initWidgets' => Widgets::initWidgets(...),
            ]);
        }

        return true;
    }
}
