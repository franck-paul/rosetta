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

dcPage::check('usage,contentadmin');

$id = !empty($_GET['id']) ? $_GET['id'] : '';
$type = !empty($_GET['type']) ? $_GET['type'] : '';
$lang = !empty($_GET['lang']) ? $_GET['lang'] : '';

$title = '';

echo '<html>';
echo
	'<head>'.
		'<title>'.__('Create a new translation').'</title>'.
		dcPage::jsLoad(urldecode(dcPage::getPF('rosetta/js/popup_new.js')),$core->getVersion('rosetta')).
	'</head>';

echo '<body>';
echo '<h2 class="page-title">'.__('Create a new translation').'</h2>';

// Languages combo
$rs = $core->blog->getLangs(array('order'=>'asc'));
$lang_combo = dcAdminCombos::getLangsCombo($rs,true);
// Remove empty select
unset($lang_combo['']);
// Remove already existed translation's languages from combo
$ids = rosettaData::findAllTranslations($id,$lang,true);
if (is_array($ids)) {
	foreach ($lang_combo as $lc => $lv) {
		if (is_array($lv)) {
			foreach ($lv as $name => $code) {
				if (array_key_exists($code,$ids)) {
					unset($lang_combo[$lc][$name]);
				}
			}
			if (!count($lang_combo[$lc])) {
				unset($lang_combo[$lc]);
			}
		}
	}
}

echo
'<form id="link-insert-form" action="#" method="get">'.

	'<p><label for="title">'.__('Entry title:').'</label> '.
	form::field('title',35,512,html::escapeHTML($title)).'</p>'.

	'<p><label for="lang">'.__('Entry language:').'</label> '.
	form::combo('lang',$lang_combo,$lang).'</p>'.

'</form>'.

'<p><button class="reset" id="rosetta-new-cancel">'.__('Cancel').'</button> - '.
'<button id="rosetta-new-ok"><strong>'.__('Create').'</strong></button></p>'."\n".

'<script type="text/javascript">'."\n".
'$(\'input[name="title"]\').get(0).focus();'."\n".
'</script>'."\n";

echo '</body>';
echo '</html>';
