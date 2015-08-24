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

// Admin behaviours

class rosettaAdminBehaviors
{
	public static function adminDashboardFavorites($core,$favs)
	{
		$favs->register('rosetta', array(
			'title' => __('Rosetta'),
			'url' => 'plugin.php?p=rosetta',
			'small-icon' => 'index.php?pf=rosetta/icon.png',
			'large-icon' => 'index.php?pf=rosetta/icon-big.png',
			'permissions' => 'usage,contentadmin'
		));
	}

	private static function adminEntryHeaders()
	{
		return
			'<script type="text/javascript">'."\n".
			"//<![CDATA[\n".
			dcPage::jsVar('dotclear.msg.confirm_remove_rosetta',
				__('Are you sure to remove this translation?')).
			"\n//]]>\n".
			"</script>\n".
			dcPage::jsLoad('index.php?pf=rosetta/js/rosetta_entry.js')."\n".
			dcPage::cssLoad('index.php?pf=rosetta/css/style.css')."\n";
	}

	public static function adminPostHeaders()
	{
		global $core;

		$core->blog->settings->addNamespace('rosetta');
		if ($core->blog->settings->rosetta->active) {
			return
				'<script type="text/javascript">'."\n".
				"//<![CDATA[\n".
				dcPage::jsVar('dotclear.post_type','post').
				"\n//]]>\n".
				"</script>\n".
				self::adminEntryHeaders();
		}
	}

	public static function adminPageHeaders()
	{
		global $core;

		$core->blog->settings->addNamespace('rosetta');
		if ($core->blog->settings->rosetta->active) {
			return
				'<script type="text/javascript">'."\n".
				"//<![CDATA[\n".
				dcPage::jsVar('dotclear.post_type','page').
				"\n//]]>\n".
				"</script>\n".
				self::adminEntryHeaders();
		}
	}

	private static function adminEntryForm($post,$post_type='post')
	{
		global $core,$lang_combo,$post_link,$redir_url;

		$core->blog->settings->addNamespace('rosetta');
		if ($core->blog->settings->rosetta->active) {

			if (!$post || !$post->post_id) {
				// Manage translation only on already created posts/pages
				return;
			}

			echo
				'<div id="rosetta-area" class="area">'."\n".
				'<label>'.
				($post_type == 'post' ? __('Post\'s translations:') : __('Page\'s translations:')).
				'</label>'."\n";

			if ($post_type == 'post') {
				$url = $core->adminurl->get('admin.post',array('id' => $post->post_id));
			} else {
				$url = $redir_url;
			}
			$url_rosetta = '&amp;lang=%s&amp;rosetta=%s&amp;rosetta_id=%s&amp;rosetta_lang=%s';

			$html_block =
				'<div class="table-outer">'.
				'<table id="rosetta-list" summary="'.__('Attached Translations').'" class="clear maximal">'.
				'<thead>'.
				'<tr>'.
				'<th class="nowrap">'.__('Language').'</th>'.
				'<th>'.($post_type == 'post' ? __('Entry') : __('Page')).'</th>'.
				'<th class="nowrap">'.'</th>'.
				'</tr>'.
				'</thead>'.
				'<tbody>%s</tbody>'.
				'</table>'.
				'</div>';
			$html_lines = '';
			$html_line =
				'<tr class="line wide" id="r%s">'."\n".
				'<td class="minimal nowrap">%s</td>'."\n".			// language
				'<td class="maximal">%s</td>'."\n".					// Entry link
				'<td class="minimal nowrap">%s</td>'."\n".	// Action
				'</tr>'."\n";

			$action_add =
				'<a href="%s" class="button">'.__('Attach a translation').'</a>';
			$action_remove =
				'<a href="%s" title="'.__('Remove this translation\'s link').
				'" name="delete"><img src="index.php?pf=rosetta/img/unlink.png" alt="'.__('Remove this translation\'s link').
				'" /></a>';

			$list = rosettaData::findAllTranslations($post->post_id,$post->post_lang,false);
			if (is_array($list) && count($list)) {

				dcUtils::lexicalKeySort($list,'admin');

				$langs = l10n::getLanguagesName();
				$i = 1;
				foreach ($list as $lang => $id) {
					// Display existing translations
					$name = isset($langs[$lang]) ? $langs[$lang] : $langs[$core->blog->settings->system->lang];
					// Get post/page id
					$params = new ArrayObject(array(
						'post_id' => $id,
						'post_type' => $post_type,
						'no_content' => true));
					$rs = $core->blog->getPosts($params);
					if ($rs->count()) {
						$rs->fetch();
						$html_lines .= sprintf($html_line,$i++,
							$lang.' - '.$name,
							sprintf($post_link,$id,__('Edit this entry'),
								html::escapeHTML($rs->post_title)),
							sprintf($action_remove,$url.sprintf($url_rosetta,$post->post_lang,'remove',$id,$lang)));
					}
				}
			}

			// Display table
			echo sprintf($html_block,$html_lines);

			// Add a button for adding a new translation
			echo '<p>'.sprintf($action_add,$url.sprintf($url_rosetta,
				($post->post_lang == '' || !$post->post_lang ? $core->blog->settings->system->lang : $post->post_lang ),
				'add',0,'')).'</p>';

			echo '</div>'."\n";
		}
	}

	public static function adminPostForm($post)
	{
		self::adminEntryForm($post,'post');
	}

	public static function adminPageForm($post)
	{
		self::adminEntryForm($post,'page');
	}
}

// Public behaviours

class rosettaPublicBehaviors
{
	public static function publicHeadContent()
	{
		global $core,$_ctx;

		$urlTypes = array('post');
		$postTypes = array('post');
		if ($core->plugins->moduleExists('pages')) {
			$urlTypes[] = 'page';
			$postTypes[] = 'post';
		}

		$core->blog->settings->addNamespace('rosetta');
		if ($core->blog->settings->rosetta->active) {
			if (in_array($core->url->type,$urlTypes)) {
				if (in_array($_ctx->posts->post_type,$postTypes)) {
					// Find translations and add meta in header
					$list = rosettaTpl::EntryListHelper(
						$_ctx->posts->post_id,$_ctx->posts->post_lang,$_ctx->posts->post_type,
						'none',$current,true);
					if (is_array($list)) {
						if (count($list)) {
							echo '<!-- Rosetta: translated version of this entry -->'."\n";
							foreach ($list as $lang => $url) {
								echo '<link rel="alternate" href="'.$url.'" hreflang="'.$lang.'" />'."\n";
							}
						}
					}
				}
			}
		}
	}

	public static function urlHandlerGetArgsDocument($handler)
	{
		global $core;

		$core->blog->settings->addNamespace('rosetta');
		if (!$core->blog->settings->rosetta->active) {
			return;
		}

		$lang = '';
		if (!empty($_GET['lang'])) {
			// Check lang scheme
			if (preg_match('/^[a-z]{2}(-[a-z]{2})?$/',rawurldecode($_GET['lang']),$matches)) {
				// Assume that the URL scheme is for post/page
				$lang = $matches[0];
			}
		}

		if ($lang) {
			$postTypes = array('post');
			if ($this->core->plugins->moduleExists('pages')) {
				$postTypes[] = 'post';
			}

			// Get post/page id
			$paramsSrc = new ArrayObject(array(
				'post_url' => $handler->args,
				'post_type' => $postTypes,
				'no_content' => true));

			$core->callBehavior('publicPostBeforeGetPosts',$paramsSrc,$handler->args);
			$rsSrc = $core->blog->getPosts($paramsSrc);

			// Check if post/page id exists in rosetta table
			if ($rsSrc->count()) {
				// Load first record
				$rsSrc->fetch();

				// Try to find an associated post corresponding to the requested lang
				$id = rosettaData::findTranslation($rsSrc->post_id,$rsSrc->post_lang,$lang);
				if ($id >= 0) {
					// Get post/page URL
					$paramsDst = new ArrayObject(array(
						'post_id' => $id,
						'post_type' => $postTypes,
						'no_content' => true));

					$core->callBehavior('publicPostBeforeGetPosts',$paramsDst,$handler->args);
					$rsDst = $core->blog->getPosts($paramsDst);

					if ($rsDst->count()) {
						// Load first record
						$rsDst->fetch();

						// Redirect to translated post
						$url = $rsDst->getURL();
						if (!preg_match('%^http[s]?://%',$url)) {
							// Prepend scheme if not present
							$url = ($_SERVER['HTTPS'] ? 'https:' : 'http').$url;
						}
						http::redirect($url);
						exit;
					}
				}
			}
		}
	}
}
