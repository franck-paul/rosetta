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

$_menu['Blog']->addItem(
    __('Rosetta'),
    'plugin.php?p=rosetta',
    urldecode(dcPage::getPF('rosetta/icon.svg')),
    preg_match('/plugin.php\?p=rosetta(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check('usage,contentadmin', dcCore::app()->blog->id)
);

require __DIR__ . '/_widgets.php';

/* Register favorite */
dcCore::app()->addBehavior('adminDashboardFavorites', ['rosettaAdminBehaviors', 'adminDashboardFavorites']);

// Add behaviour callback for post
dcCore::app()->addBehavior('adminPostForm', ['rosettaAdminBehaviors', 'adminPostForm']);
dcCore::app()->addBehavior('adminPostHeaders', ['rosettaAdminBehaviors', 'adminPostHeaders']);

// Add behaviour callback for page
dcCore::app()->addBehavior('adminPageForm', ['rosettaAdminBehaviors', 'adminPageForm']);
dcCore::app()->addBehavior('adminPageHeaders', ['rosettaAdminBehaviors', 'adminPageHeaders']);

// Add behaviour callback for post/page list popup
dcCore::app()->addBehavior('adminPopupPosts', ['rosettaAdminBehaviors', 'adminPopupPosts']);

// Add behaviour callback for post/page lists
dcCore::app()->addBehavior('adminColumnsLists', ['rosettaAdminBehaviors', 'adminColumnsLists']);
dcCore::app()->addBehavior('adminPostListHeader', ['rosettaAdminBehaviors', 'adminPostListHeader']);
dcCore::app()->addBehavior('adminPostListValue', ['rosettaAdminBehaviors', 'adminPostListValue']);
dcCore::app()->addBehavior('adminPostMiniListHeader', ['rosettaAdminBehaviors', 'adminPostMiniListHeader']);
dcCore::app()->addBehavior('adminPostMiniListValue', ['rosettaAdminBehaviors', 'adminPostMiniListValue']);
dcCore::app()->addBehavior('adminPagesListHeader', ['rosettaAdminBehaviors', 'adminPagesListHeader']);
dcCore::app()->addBehavior('adminPagesListValue', ['rosettaAdminBehaviors', 'adminPagesListValue']);
dcCore::app()->addBehavior('adminFiltersLists', ['rosettaAdminBehaviors', 'adminFiltersLists']);

// Add behaviour callback for import/export
dcCore::app()->addBehavior('exportSingle', ['rosettaAdminBehaviors', 'exportSingle']);
dcCore::app()->addBehavior('exportFull', ['rosettaAdminBehaviors', 'exportFull']);
dcCore::app()->addBehavior('importInit', ['rosettaAdminBehaviors', 'importInit']);
dcCore::app()->addBehavior('importSingle', ['rosettaAdminBehaviors', 'importSingle']);
dcCore::app()->addBehavior('importFull', ['rosettaAdminBehaviors', 'importFull']);

// Register REST methods
dcCore::app()->rest->addFunction('newTranslation', ['rosettaRest', 'newTranslation']);
dcCore::app()->rest->addFunction('addTranslation', ['rosettaRest', 'addTranslation']);
dcCore::app()->rest->addFunction('removeTranslation', ['rosettaRest', 'removeTranslation']);
dcCore::app()->rest->addFunction('getTranslationRow', ['rosettaRest', 'getTranslationRow']);

// Administrative actions
dcCore::app()->blog->settings->addNamespace('rosetta');
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
