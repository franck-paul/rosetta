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
	static $args_rosetta = '&amp;lang=%s&amp;type=%s&amp;rosetta=%s&amp;rosetta_id=%s&amp;rosetta_lang=%s';

	public static function adminDashboardFavorites($core,$favs)
	{
		$favs->register('rosetta', array(
			'title' => __('Rosetta'),
			'url' => 'plugin.php?p=rosetta',
			'small-icon' => dcPage::getPF('rosetta/icon.png'),
			'large-icon' => dcPage::getPF('rosetta/icon-big.png'),
			'permissions' => 'usage,contentadmin'
		));
	}

	private static function adminEntryHeaders()
	{
		return
			'<script type="text/javascript">'."\n".
			"//<![CDATA[\n".
			dcPage::jsVar('dotclear.msg.confirm_remove_rosetta',__('Are you sure to remove this translation?')).
			dcPage::jsVar('dotclear.rosetta_post_url','').
			"\n//]]>\n".
			"</script>\n".
			dcPage::jsLoad(dcPage::getPF('rosetta/js/rosetta_entry.js'))."\n".
			dcPage::cssLoad(dcPage::getPF('rosetta/css/style.css'))."\n";
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

	public static function translationRow($src_lang,$id,$lang,$name,$title,$post_link,$url_page)
	{
		$html_line =
			'<tr class="line wide">'."\n".
			'<td class="minimal nowrap">%s</td>'."\n".			// language
			'<td class="maximal">%s</td>'."\n".					// Entry link
			'<td class="minimal nowrap">%s</td>'."\n".			// Action
			'</tr>'."\n";
		$action_remove =
			'<a href="%s" class="rosetta-remove" title="'.__('Remove this translation\'s link').'" name="delete">'.
			'<img src="'.urldecode(dcPage::getPF('rosetta/img/unlink.png')).
			'" alt="'.__('Remove this translation\'s link').'" /></a>';

		return sprintf($html_line,
			$lang.' - '.$name,
			sprintf($post_link,$id,__('Edit this entry'),html::escapeHTML($title)),
			sprintf($action_remove,$url_page.sprintf(self::$args_rosetta,$src_lang,'','remove',$id,$lang))
			);
	}

	private static function adminEntryForm($post,$post_type='post')
	{
		global $core,$post_link,$redir_url;

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

			$action_add =
				'<a href="%s" class="button rosetta-add">'.__('Attach a translation').'</a>';

			$list = rosettaData::findAllTranslations($post->post_id,$post->post_lang,false);
			if (is_array($list) && count($list)) {

				dcUtils::lexicalKeySort($list,'admin');

				$langs = l10n::getLanguagesName();
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
						$html_lines .= self::translationRow($post->post_lang,$post_type,$id,$lang,$name,
							$rs->post_title,$post_link,$url);
					}
				}
			}

			// Display table
			echo sprintf($html_block,$html_lines);

			// Add a button for adding a new translation
			echo '<p>'.
				// Button
				sprintf($action_add,$url.
					sprintf(self::$args_rosetta,
						($post->post_lang == '' || !$post->post_lang ? $core->blog->settings->system->lang : $post->post_lang ),
						$post_type,'add',0,'')).
				// Hidden field for selected post/page URL
				form::hidden(array('rosetta_url','rosetta_url'), '').
				'</p>';

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

	public static function adminPopupPosts($editor='') {
		if (empty($editor) || $editor!='rosetta') {return;}

		return dcPage::jsLoad(dcPage::getPF('rosetta/js/popup_posts.js'));
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
			$postTypes[] = 'page';
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

	private static function findTranslatedEntry($handler,$lang)
	{
		global $core;

		$postTypes = array('post');
		if ($core->plugins->moduleExists('pages')) {
			$postTypes[] = 'page';
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

	public static function urlHandlerGetArgsDocument($handler)
	{
		global $core;

		$core->blog->settings->addNamespace('rosetta');
		if (!$core->blog->settings->rosetta->active) {
			return;
		}

		$langs = array();
		if (!empty($_GET['lang'])) {
			// Check lang scheme
			if (preg_match('/^[a-z]{2}(-[a-z]{2})?$/',rawurldecode($_GET['lang']),$matches)) {
				// Assume that the URL scheme is for post/page
				$langs[] = $matches[0];
			}
		} elseif ($core->blog->settings->rosetta->accept_language) {
			$urlType = '';
			$urlPart = '';
			$handler->getArgs($_SERVER['URL_REQUEST_PART'],$urlType,$urlPart);
			if (in_array($urlType,array('post','page'))) {
				// It is a post or page: Try to find a translation according to the browser
				$langs = http::getAcceptLanguages();
			}
		}

		if (count($langs)) {
			foreach ($langs as $lang) {
				// Try to find an according translation (will http-redirect if any)
				self::findTranslatedEntry($handler,$lang);
			}
		}
	}
}
