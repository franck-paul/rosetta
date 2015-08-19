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

if (!empty($_REQUEST['m'])) {
	switch ($_REQUEST['m']) {
		case 'posts' :	// Manage posts translations
		case 'pages' :	// Manage pages translations
//			require dirname(__FILE__).'/'.$_REQUEST['m'].'.php';
			break;
	}
}

// Plugin settings for current blog
// ...
