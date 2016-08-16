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

if (!defined('DC_RC_PATH')) { return; }

$this->registerModule(
	/* Name */			"Rosetta",
	/* Description*/		"Manage post/page translations",
	/* Author */			"Franck Paul",
	/* Version */			'0.6',
	array(
		/* Dependencies */	'requires' =>		array(array('core','2.10')),
		/* Permissions */	'permissions' =>	'usage,contentadmin',
		/* Priority */		'priority' =>		1001,	// Must be higher than pages plugin
		/* Type */			'type' =>			'plugin'
	)
);
