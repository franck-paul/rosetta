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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

// dead but useful code, in order to have translations
__('Rosetta') . __('Manage post/page translations');

dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
    __('Rosetta'),
    'plugin.php?p=rosetta',
    urldecode(dcPage::getPF('rosetta/icon.svg')),
    preg_match('/plugin.php\?p=rosetta(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_USAGE,
        dcAuth::PERMISSION_CONTENT_ADMIN,
    ]), dcCore::app()->blog->id)
);

require_once __DIR__ . '/_widgets.php';

dcCore::app()->addBehaviors([
    // Register favorite
    'adminDashboardFavoritesV2' => [rosettaAdminBehaviors::class, 'adminDashboardFavorites'],

    // Add behaviour callback for post
    'adminPostForm'             => [rosettaAdminBehaviors::class, 'adminPostForm'],
    'adminPostHeaders'          => [rosettaAdminBehaviors::class, 'adminPostHeaders'],

    // Add behaviour callback for page
    'adminPageForm'             => [rosettaAdminBehaviors::class, 'adminPageForm'],
    'adminPageHeaders'          => [rosettaAdminBehaviors::class, 'adminPageHeaders'],

    // Add behaviour callback for post/page list popup
    'adminPopupPosts'           => [rosettaAdminBehaviors::class, 'adminPopupPosts'],

    // Add behaviour callback for post/page lists
    'adminColumnsListsV2'       => [rosettaAdminBehaviors::class, 'adminColumnsLists'],
    'adminPostListHeaderV2'     => [rosettaAdminBehaviors::class, 'adminPostListHeader'],
    'adminPostListValueV2'      => [rosettaAdminBehaviors::class, 'adminPostListValue'],
    'adminPostMiniListHeaderV2' => [rosettaAdminBehaviors::class, 'adminPostMiniListHeader'],
    'adminPostMiniListValue'    => [rosettaAdminBehaviors::class, 'adminPostMiniListValue'],
    'adminPagesListHeaderV2'    => [rosettaAdminBehaviors::class, 'adminPagesListHeader'],
    'adminPagesListValueV2'     => [rosettaAdminBehaviors::class, 'adminPagesListValue'],
    'adminFiltersListsV2'       => [rosettaAdminBehaviors::class, 'adminFiltersLists'],

    // Add behaviour callback for import/export
    'exportSingleV2'            => [rosettaAdminBehaviors::class, 'exportSingle'],
    'exportFullV2'              => [rosettaAdminBehaviors::class, 'exportFull'],
    'importInitV2'              => [rosettaAdminBehaviors::class, 'importInit'],
    'importSingleV2'            => [rosettaAdminBehaviors::class, 'importSingle'],
    'importFullV2'              => [rosettaAdminBehaviors::class, 'importFull'],
]);

// Register REST methods
dcCore::app()->rest->addFunction('newTranslation', [rosettaRest::class, 'newTranslation']);
dcCore::app()->rest->addFunction('addTranslation', [rosettaRest::class, 'addTranslation']);
dcCore::app()->rest->addFunction('removeTranslation', [rosettaRest::class, 'removeTranslation']);
dcCore::app()->rest->addFunction('getTranslationRow', [rosettaRest::class, 'getTranslationRow']);

// Administrative actions
if (dcCore::app()->blog->settings->rosetta->active) {
    // Cope with actions on post/page edition (if javascript not enabled)
    if (isset($_GET['rosetta']) && in_array($_GET['rosetta'], ['add', 'remove', 'new', 'new_edit'])) {
        $redirect = false;
        switch ($_GET['rosetta']) {
            case 'add':
                // Add an existing translation link
                break;
            case 'remove':
                // Remove an existing translation link
                break;
            case 'new-edit':
                // Create, attach and edit a new translation
                $redirect = true;
            case 'new':
                // Create and attach a new translation
                if ($redirect) {
                    ;
                }

                break;
        }
    }
}
