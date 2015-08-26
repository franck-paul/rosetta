<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of rosetta, a plugin for Dotclear 2.
#
# Copyright (c) Franck Paul and contributors
# carnet.franck.paul@gmail.com
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) { return; }

// dead but useful code, in order to have translations
__('Rosetta').__('Manage post/page translations');

$_menu['Blog']->addItem(__('Rosetta'),'plugin.php?p=rosetta',dcPage::getPF('rosetta/icon.png'),
		preg_match('/plugin.php\?p=rosetta(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('usage,contentadmin',$core->blog->id));

require dirname(__FILE__).'/_widgets.php';

/* Register favorite */
$core->addBehavior('adminDashboardFavorites',array('rosettaAdminBehaviors','adminDashboardFavorites'));

// Add behaviour callback for post
$core->addBehavior('adminPostForm',array('rosettaAdminBehaviors','adminPostForm'));
$core->addBehavior('adminPostHeaders',array('rosettaAdminBehaviors','adminPostHeaders'));

// Add behaviour callback for page
$core->addBehavior('adminPageForm',array('rosettaAdminBehaviors','adminPageForm'));
$core->addBehavior('adminPageHeaders',array('rosettaAdminBehaviors','adminPageHeaders'));

// Add behaviour callback for post/page list popup
$core->addBehavior('adminPopupPosts',array('rosettaAdminBehaviors','adminPopupPosts'));

// Register REST methods
$core->rest->addFunction('addTranslation',array('rosettaRest','addTranslation'));
$core->rest->addFunction('removeTranslation',array('rosettaRest','removeTranslation'));

// Administrative actions
$core->blog->settings->addNamespace('rosetta');
if ($core->blog->settings->rosetta->active) {
	// Cope with actions on post/page edition (if javascript not enabled)
	if (isset($_GET['rosetta']) && in_array($_GET['rosetta'],array('add','remove'))) {
		; // ToDo later
	}
}
