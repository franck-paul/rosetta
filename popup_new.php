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
		dcPage::jsLoad(dcPage::getPF('rosetta/js/popup_new.js')).
	'</head>';

echo '<body>';
echo '<h2 class="page-title">'.__('Create a new translation').'</h2>';

// Languages combo
$rs = $core->blog->getLangs(array('order'=>'asc'));
$lang_combo = dcAdminCombos::getLangsCombo($rs,true);

// Remove already existed translation's languages from combo
// ???

echo
'<form id="link-insert-form" action="#" method="get">'.

	'<p><label for="title">'.__('Entry title:').'</label> '.
	form::field('title',35,512,html::escapeHTML($title)).'</p>'.

	'<p><label for="lang">'.__('Entry language:').'</label> '.
	form::combo('lang',$lang_combo,$lang).'</p>'.

'</form>'.

'<p><a class="button reset" href="#" id="rosetta-new-cancel">'.__('Cancel').'</a> - '.
'<strong><a class="button" href="#" id="rosetta-new-ok">'.__('Create').'</a></strong></p>'."\n".

'<script type="text/javascript">'."\n".
'//<![CDATA['."\n".
'$(\'input[name="title"]\').get(0).focus();'."\n".
'//]]>'."\n".
'</script>'."\n";

echo '</body>';
echo '</html>';
