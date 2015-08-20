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

class rosettaTpl
{
	public static function rosettaEntryWidget($w)
	{
		global $core,$_ctx;

		if ($w->offline)
			return;

		if ($core->url->type != 'post' && $core->url->type != 'page') {
			return;
		}
		$post_type = ($core->url->type == 'post' ? 'post' : 'page');

		// Get list of available translations for current entry
		$full = ($w->current != 'none');
		$ids = rosettaData::findAllTranslations($_ctx->posts->post_id,$_ctx->posts->post_lang,$full);
		if (!is_array($ids)) {
			return;
		}

		// Get associated entries
		// source = $ids : array ('lang' => 'entry-id')
		// destination = $table : array ('language' => 'entry-url')
		// $current = current language
		$table = array();
		$langs = l10n::getLanguagesName();
		$current = '';
		foreach ($ids as $lang => $id) {
			$name = isset($langs[$lang]) ? $langs[$lang] : $langs[$core->blog->settings->system->lang];
			if ($_ctx->posts->post_id == $id) {
				$current = $name;
			}
			if ($_ctx->posts->post_id == $id && $w->current != 'link') {
				$table[$name] = '';
			} else {
				// Get post/page URL
				$params = new ArrayObject(array(
					'post_id' => $id,
					'post_type' => $post_type,
					'no_content' => true));
				$core->callBehavior('publicPostBeforeGetPosts',$params,null);
				$rs = $core->blog->getPosts($params);
				if ($rs->count()) {
					$rs->fetch();
					$url = $core->blog->url.$core->getPostPublicURL($post_type,html::sanitizeURL($rs->post_url));
					$table[$name] = $url;
				}
			}
		}
		if (!count($table)) {
			return;
		}
		dcUtils::lexicalKeySort($table,'public');

		// Render widget title
		$res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title))."\n" : '');

		// Render widget list of translations
		$list = '';
		foreach ($table as $name => $url) {
			$link = ($name != $current || $w->current == 'link');
			$class = ($name == $current ? ' class="current"' : '');

			$list .= '<li'.$class.'>'.
				($link ? '<a href="'.$url.'">' : '').
				html::escapeHTML($name).
				($link ? '</a>' : '').
				'</li>'."\n";
		}
		if ($list == '') {
			return;
		}
		$res .= '<ul>'.$list.'</ul>'."\n";

		// Render full content
		return $w->renderDiv($w->content_only,'rosetta-entry '.$w->class,'',$res);
	}
}
