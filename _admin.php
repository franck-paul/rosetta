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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

// dead but useful code, in order to have translations
__('Rosetta') . __('Manage post/page translations');

$_menu['Blog']->addItem(__('Rosetta'), 'plugin.php?p=rosetta', urldecode(dcPage::getPF('rosetta/icon.png')),
    preg_match('/plugin.php\?p=rosetta(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('usage,contentadmin', $core->blog->id));

require dirname(__FILE__) . '/_widgets.php';

/* Register favorite */
$core->addBehavior('adminDashboardFavorites', array('rosettaAdminBehaviors', 'adminDashboardFavorites'));

// Add behaviour callback for post
$core->addBehavior('adminPostForm', array('rosettaAdminBehaviors', 'adminPostForm'));
$core->addBehavior('adminPostHeaders', array('rosettaAdminBehaviors', 'adminPostHeaders'));

// Add behaviour callback for page
$core->addBehavior('adminPageForm', array('rosettaAdminBehaviors', 'adminPageForm'));
$core->addBehavior('adminPageHeaders', array('rosettaAdminBehaviors', 'adminPageHeaders'));

// Add behaviour callback for post/page list popup
$core->addBehavior('adminPopupPosts', array('rosettaAdminBehaviors', 'adminPopupPosts'));

// Add behaviour callback for post/page lists
$core->addBehavior('adminColumnsLists', array('rosettaAdminBehaviors', 'adminColumnsLists'));
$core->addBehavior('adminPostListHeader', array('rosettaAdminBehaviors', 'adminPostListHeader'));
$core->addBehavior('adminPostListValue', array('rosettaAdminBehaviors', 'adminPostListValue'));
$core->addBehavior('adminPostMiniListHeader', array('rosettaAdminBehaviors', 'adminPostMiniListHeader'));
$core->addBehavior('adminPostMiniListValue', array('rosettaAdminBehaviors', 'adminPostMiniListValue'));
$core->addBehavior('adminPagesListHeader', array('rosettaAdminBehaviors', 'adminPagesListHeader'));
$core->addBehavior('adminPagesListValue', array('rosettaAdminBehaviors', 'adminPagesListValue'));

// Add behaviour callback for import/export
$core->addBehavior('exportSingle', array('rosettaAdminBehaviors', 'exportSingle'));
$core->addBehavior('exportFull', array('rosettaAdminBehaviors', 'exportFull'));
$core->addBehavior('importInit', array('rosettaAdminBehaviors', 'importInit'));
$core->addBehavior('importSingle', array('rosettaAdminBehaviors', 'importSingle'));
$core->addBehavior('importFull', array('rosettaAdminBehaviors', 'importFull'));

// Register REST methods
$core->rest->addFunction('newTranslation', array('rosettaRest', 'newTranslation'));
$core->rest->addFunction('addTranslation', array('rosettaRest', 'addTranslation'));
$core->rest->addFunction('removeTranslation', array('rosettaRest', 'removeTranslation'));
$core->rest->addFunction('getTranslationRow', array('rosettaRest', 'getTranslationRow'));

// Administrative actions
$core->blog->settings->addNamespace('rosetta');
if ($core->blog->settings->rosetta->active) {
    // Cope with actions on post/page edition (if javascript not enabled)
    if (isset($_GET['rosetta']) && in_array($_GET['rosetta'], array('add', 'remove', 'new', 'new_edit'))) {
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
