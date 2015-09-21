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

	/**
	 * Get a full row for one translation
	 *
	 * @param  string $src_lang  source language (from currently edited post or page)
	 * @param  string $id        source id (post or page)
	 * @param  string $lang      translation language code
	 * @param  string $name      translation language name
	 * @param  string $title     title of translated post or page
	 * @param  string $post_link sprintf format for post/page edition (post-id, label, post-title)
	 * @param  string $url_page  current admin page URL
	 * @return string            row (<tr>…</tr>)
	 */
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
				$url = $redir_url.'&id='.$post->post_id;
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
						$html_lines .= self::translationRow($post->post_lang,$id,$lang,$name,
							$rs->post_title,$post_link,$url);
					}
				}
			}

			// Display table
			echo sprintf($html_block,$html_lines);

			// Add a button for adding a new translation
			$action_add =
				'<a href="%s" class="button rosetta-add">'.__('Attach a translation').'</a>';

			echo '<p>'.
				// Button
				sprintf($action_add,$url.
					sprintf(self::$args_rosetta,
						($post->post_lang == '' || !$post->post_lang ? $core->blog->settings->system->lang : $post->post_lang ),
						$post_type,'add',0,'')).
				// Hidden field for selected post/page URL
				form::hidden(array('rosetta_url','rosetta_url'), '').
				'</p>';

			// Add a field (title), a combo (lang) and a button to create a new translation
			$action_new =
				'<a href="%s" class="button add rosetta-new">'.__('Create a new translation').'</a>';
			$action_new_edit =
				'<a href="%s" class="button add rosetta-new">'.__('Create and edit a new translation').'</a>';

			echo
				'<p class="top-add">'.
				sprintf($action_new,$url.
					sprintf(self::$args_rosetta,
						($post->post_lang == '' || !$post->post_lang ? $core->blog->settings->system->lang : $post->post_lang ),
						$post_type,'new',0,'').
					'&amp;edit=0').
				' '.
				sprintf($action_new_edit,$url.
					sprintf(self::$args_rosetta,
						($post->post_lang == '' || !$post->post_lang ? $core->blog->settings->system->lang : $post->post_lang ),
						$post_type,'new_edit',0,'').
					'&amp;edit=1').
				// Hidden fields for new entry title and lang
				form::hidden(array('rosetta_title','rosetta_title'), '').
				form::hidden(array('rosetta_lang','rosetta_lang'), '').
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

	public static function adminPopupPosts($editor='')
	{
		if (empty($editor) || $editor!='rosetta') {
			return;
		}

		return dcPage::jsLoad(dcPage::getPF('rosetta/js/popup_posts.js'));
	}

	private static function adminEntryListHeader($core,$rs,$cols)
	{
		$cols['language'] = '<th scope="col">'.__('Language').'</th>';
		$cols['translations'] = '<th scope="col">'.__('Translations').'</th>';
	}

	public static function adminPostListHeader($core,$rs,$cols)
	{
		self::adminEntryListHeader($core,$rs,$cols);
	}

	public static function adminPagesListHeader($core,$rs,$cols)
	{
		self::adminEntryListHeader($core,$rs,$cols);
	}

	public static function adminEntryListValue($core,$rs,$cols)
	{
		$translations = '';
		$list = rosettaData::findAllTranslations($rs->post_id,$rs->post_lang,false);
		if (is_array($list) && count($list)) {
			dcUtils::lexicalKeySort($list,'admin');
			$langs = l10n::getLanguagesName();
			foreach ($list as $lang => $id) {
				// Display existing translations
				$name = isset($langs[$lang]) ? $langs[$lang] : $langs[$core->blog->settings->system->lang];
				// Get post/page id
				$params = new ArrayObject(array(
					'post_id' => $id,
					'post_type' => $rs->post_type,
					'no_content' => true));
				$rst = $core->blog->getPosts($params);
				if ($rst->count()) {
					$rst->fetch();
					$translation = sprintf('<a href="%s" title="%s">%s</a>',
						$core->getPostAdminURL($rst->post_type,$rst->post_id),
						$rst->post_title,
						$name);
					$translations .= ($translations ? ' / ' : '').$translation;
				}
			}
		}

		$cols['language'] = '<td class="nowrap">'.$rs->post_lang.'</td>';
		$cols['translations'] = '<td class="nowrap">'.$translations.'</td>';
	}

	public static function adminPostListValue($core,$rs,$cols)
	{
		self::adminEntryListValue($core,$rs,$cols);
	}

	public static function adminPagesListValue($core,$rs,$cols)
	{
		self::adminEntryListValue($core,$rs,$cols);
	}

	public static function adminPostMiniListHeader($core,$rs,$cols)
	{
		$cols['language'] = '<th scope="col">'.__('Language').'</th>';
	}

	public static function adminPostMiniListValue($core,$rs,$cols)
	{
		$cols['language'] =	'<td class="nowrap">'.$rs->post_lang.'</td>';
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

			// If current entry is in the requested languages, return true
			if ($rsSrc->post_lang == $lang) {
				return true;
			}

			// Try to find an associated post corresponding to the requested lang
			$id = rosettaData::findTranslation($rsSrc->post_id,$rsSrc->post_lang,$lang);
			if (($id >= 0) && ($id != $rsSrc->post_id)) {
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
						$url = (isset($_SERVER['HTTPS']) ? 'https:' : 'http:').$url;
					}
					http::redirect($url);
					exit;
				}
			}
		}

		return false;
	}

	/**
	* Client languages
	*
	* Returns an array of accepted langages ordered by priority.
	* can be a two letters language code or a xx-xx variant.
	*
	* @return array
	*/
	private static function getAcceptLanguages()
	{
		$langs = array();
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {

			// break up string into pieces (languages and q factors)
			preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
				$_SERVER['HTTP_ACCEPT_LANGUAGE'],$lang_parse);

			if (count($lang_parse[1])) {
				// create a list like "en" => 0.8
				$langs = array_combine($lang_parse[1],$lang_parse[4]);

				// set default to 1 for any without q factor
				foreach ($langs as $lang => $val) {
					if ($val === '') {
						$langs[$lang] = 1;
					}
				}

				// sort list based on value
				arsort($langs,SORT_NUMERIC);
				$langs = array_map('strtolower',array_keys($langs));
			}
		}
		return $langs;
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
			if (in_array($urlType,array('post','pages'))) {
				// It is a post or page: Try to find a translation according to the browser settings
				$langs = self::getAcceptLanguages();
			}
		}

		if (count($langs)) {
			foreach ($langs as $lang) {
				// Try to find an according translation (will http-redirect if any)
				if (self::findTranslatedEntry($handler,$lang)) {
					// The current entry is already in one of the browser languages
					break;
				}
			}
		}
	}
}
