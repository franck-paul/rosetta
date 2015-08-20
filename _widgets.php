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

$core->addBehavior('initWidgets',array('rosettaWidgets','initWidgets'));

class rosettaWidgets
{
	public static function initWidgets($w)
	{
		// Widget for currently displayed post
		$w->create('rosettaEntry',__('Entry\'s translations'),array('rosettaTpl','rosettaEntryWidget'),
			null,__('Translation(s) of this entry'));
		$w->rosettaEntry->setting('title',__( 'Title:' ),__( 'Translations' ));
		$w->rosettaEntry->setting('current',__('Include current entry:'),'std','combo',
			array(__('Without its URL') => 'std',__('With its URL') => 'link',__('None') => 'none')
			);
		$w->rosettaEntry->setting('content_only',__('Content only'),0,'check');
		$w->rosettaEntry->setting('class',__('CSS class:'),'');
		$w->rosettaEntry->setting('offline',__('Offline'),0,'check');
	}
}
