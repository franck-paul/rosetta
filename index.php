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

$core->blog->settings->addNamespace('rosetta');
$rosetta_active = $core->blog->settings->rosetta->active;

$p_url = $core->adminurl->get('admin.plugin.rosetta');
$tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

// Save Rosetta settings
if (!empty($_POST['save_settings'])) {
	try {
		$core->blog->settings->rosetta->put('active',empty($_POST['active']) ? false : true,'boolean');

		dcPage::addSuccessNotice(__('Configuration successfully updated.'));
		http::redirect($p_url.'&tab='.$tab.'#'.$tab);
	}
	catch(Exception $e) {
		$core->error->add($e->getMessage());
	}
}

// Display page
echo '<html><head>
	<title>'.__('Rosetta').'</title>'.
	dcPage::jsPageTabs($tab).
	'</head>
	<body>';

echo dcPage::breadcrumb(
	array(
		html::escapeHTML($core->blog->name) => '',
		__('Rosetta') => ''
		)
	).dcPage::notices();

// Display tabs
// 1. Posts translations
echo
'<div id="posts" class="multi-part" title="'.__('Posts tranlations').'">'.
'<h3>'.__('Posts tranlations').'</h3>'.
'<form action="'.$p_url.'" method="post">';

echo
'<p class="field"><input type="submit" value="'.__('Save').'" /> '.
form::hidden(array('tab'), 'posts').
$core->formNonce().'</p>'.
'</form>'.
'</div>';

// 2. Pages translations
if ($core->plugins->moduleExists('pages')) {
	echo
	'<div id="pages" class="multi-part" title="'.__('Pages tranlations').'">'.
	'<h3>'.__('Pages tranlations').'</h3>'.
	'<form action="'.$p_url.'" method="post">';

	echo
	'<p class="field"><input type="submit" value="'.__('Save').'" /> '.
	form::hidden(array('tab'), 'pages').
	$core->formNonce().'</p>'.
	'</form>'.
	'</div>';
}

// 3. Plugin settings
echo
'<div id="settings" class="multi-part" title="'.__('Settings').'">'.
'<h3>'.__('Settings').'</h3>'.
'<form action="'.$p_url.'" method="post">'.

'<h4 class="pretty-title">'.__('Activation').'</h4>'.
'<p>'.form::checkbox('active', 1, $rosetta_active).
'<label class="classic" for="active">'.__('Enable posts/pages tranlsations for this blog').'</label></p>';

echo
'<p class="field wide"><input type="submit" value="'.__('Save').'" /> '.
form::hidden(array('tab'), 'settings').
form::hidden(array('save_settings'), 1).
$core->formNonce().'</p>'.
'</form>'.
'</div>';

echo
'</body></html>';
