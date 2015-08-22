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

// Public and Admin mode

$__autoload['rosettaPublicBehaviors'] = 	dirname(__FILE__).'/inc/rosetta.behaviors.php';
$__autoload['rosettaTpl'] = 				dirname(__FILE__).'/inc/rosetta.tpl.php';
$__autoload['rosettaData'] = 				dirname(__FILE__).'/inc/rosetta.data.php';

if (!defined('DC_CONTEXT_ADMIN')) { return false; }

// Admin mode only

$__autoload['rosettaAdminBehaviors'] = 		dirname(__FILE__).'/inc/rosetta.behaviors.php';
