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

// Open specific popup if required
if (!empty($_REQUEST['popup_new'])) {
    require __DIR__ . '/' . 'popup_new.php';

    return;
}

// Main page of plugin
dcCore::app()->blog->settings->addNamespace('rosetta');
$rosetta_active          = dcCore::app()->blog->settings->rosetta->active;
$rosetta_accept_language = dcCore::app()->blog->settings->rosetta->accept_language;

$tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

// Save Rosetta settings
if (!empty($_POST['save_settings'])) {
    try {
        dcCore::app()->blog->settings->rosetta->put('active', empty($_POST['active']) ? false : true, 'boolean');
        dcCore::app()->blog->settings->rosetta->put('accept_language', empty($_POST['accept_language']) ? false : true, 'boolean');

        dcPage::addSuccessNotice(__('Configuration successfully updated.'));
        http::redirect($p_url . '&tab=' . $tab . '#' . $tab);
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

// Display page
echo '<html><head>
  <title>' . __('Rosetta') . '</title>' .
dcPage::jsPageTabs($tab) .
    '</head>
  <body>';

echo dcPage::breadcrumb(
    [
        html::escapeHTML(dcCore::app()->blog->name) => '',
        __('Rosetta')                               => '',
    ]
) . dcPage::notices();

// Display tabs
// 1. Posts translations
echo
'<div id="posts" class="multi-part" title="' . __('Posts tranlations') . '">' .
'<h3>' . __('Posts tranlations') . '</h3>' .
    '<form action="' . $p_url . '" method="post">';

// TODO

echo
'<p class="field"><input type="submit" value="' . __('Save') . '" /> ' .
form::hidden(['tab'], 'posts') .
dcCore::app()->formNonce() . '</p>' .
    '</form>' .
    '</div>';

// 2. Pages translations
if (dcCore::app()->plugins->moduleExists('pages')) {
    echo
    '<div id="pages" class="multi-part" title="' . __('Pages tranlations') . '">' .
    '<h3>' . __('Pages tranlations') . '</h3>' .
        '<form action="' . $p_url . '" method="post">';

    // TODO

    echo
    '<p class="field"><input type="submit" value="' . __('Save') . '" /> ' .
    form::hidden(['tab'], 'pages') .
    dcCore::app()->formNonce() . '</p>' .
        '</form>' .
        '</div>';
}

if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_ADMIN,
]), dcCore::app()->blog->id)) {
    // 3. Plugin settings
    echo
    '<div id="settings" class="multi-part" title="' . __('Settings') . '">' .
    '<h3>' . __('Settings') . '</h3>' .
    '<form action="' . $p_url . '" method="post">' .

    '<h4 class="pretty-title">' . __('Activation') . '</h4>' .
    '<p>' . form::checkbox('active', 1, $rosetta_active) .
    '<label class="classic" for="active">' . __('Enable posts/pages translations for this blog') . '</label></p>' .

    '<h4 class="pretty-title">' . __('Options') . '</h4>' .
    '<p>' . form::checkbox('accept_language', 1, $rosetta_accept_language) .
    '<label class="classic" for="accept_language">' . __('Automatic posts/pages redirect on browser\'s language for this blog') . '</label></p>';

    echo
    '<p class="field wide"><input type="submit" value="' . __('Save') . '" /> ' .
    form::hidden(['tab'], 'settings') .
    form::hidden(['save_settings'], 1) .
    dcCore::app()->formNonce() . '</p>' .
        '</form>' .
        '</div>';
}

echo
    '</body></html>';
