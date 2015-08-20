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
	public static function EntryListHelper($post_id,$post_lang,$post_type,$include,&$current)
	{
		global $core;

		// Get associated entries
		$ids = rosettaData::findAllTranslations($post_id,$post_lang,($include != 'none'));
		if (!is_array($ids)) {
			return false;
		}

		// source = $ids : array ('lang' => 'entry-id')
		// destination = $table : array ('language' => 'entry-url')
		// $current = current language
		$table = array();
		$langs = l10n::getLanguagesName();
		$current = '';
		foreach ($ids as $lang => $id) {
			$name = isset($langs[$lang]) ? $langs[$lang] : $langs[$core->blog->settings->system->lang];
			if ($post_id == $id) {
				$current = $name;
			}
			if ($post_id == $id && $include != 'link') {
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
			return false;
		}
		dcUtils::lexicalKeySort($table,'public');

		return $table;
	}

	public static function rosettaEntryList($attr)
	{
		$option = !empty($attr['include_current']) ? $attr['include_current'] : 'std';

		if (!preg_match('#^(std|link|none)$#',$option)) {
			$option = 'std';
		}

		$res = <<<EOT
			\$rosetta_table = rosettaTpl::EntryListHelper(
				\$_ctx->posts->post_id,\$_ctx->posts->post_lang,\$_ctx->posts->post_type,
				'$option',\$rosetta_current);
			if (is_array(\$rosetta_table) && count(\$rosetta_table)) {
				echo '<ul class="rosetta-entries-list">'."\n";
				foreach (\$rosetta_table as \$rosetta_name => \$rosetta_url) {
					\$rosetta_link = (\$rosetta_name != \$rosetta_current || '$option' == 'link');
					\$rosetta_class = (\$rosetta_name == \$rosetta_current ? 'class="current"' : '');
					echo '<li'.\$rosetta_class.'>'.
						(\$rosetta_link ? '<a href="'.\$rosetta_url.'">' : '').
						(\$rosetta_class ? '<strong>' : '').html::escapeHTML(\$rosetta_name).(\$rosetta_class ? '</strong>' : '').
						(\$rosetta_link ? '</a>' : '').
						'</li>'."\n";
				}
				echo '</ul>'."\n";
			}
EOT;
		return ($res != '' ? '<?php '.$res.' ?>' : '');
	}

	public static function rosettaEntryWidget($w)
	{
		global $core,$_ctx;

		if ($w->offline)
			return;

		if ($core->url->type != 'post' && $core->url->type != 'page') {
			return;
		}

		// Get list of available translations for current entry
		$post_type = ($core->url->type == 'post' ? 'post' : 'page');
		$table = self::EntryListHelper($_ctx->posts->post_id,$_ctx->posts->post_lang,$post_type,$w->current,$current);
		if (!$table) {
			return;
		}

		// Render widget title
		$res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title))."\n" : '');

		// Render widget list of translations
		$list = '';
		foreach ($table as $name => $url) {
			$link = ($name != $current || $w->current == 'link');
			$class = ($name == $current ? ' class="current"' : '');

			$list .= '<li'.$class.'>'.
				($link ? '<a href="'.$url.'">' : '').
				($class ? '<strong>' : '').html::escapeHTML($name).($class ? '</strong>' : '').
				($link ? '</a>' : '').
				'</li>'."\n";
		}
		if ($list == '') {
			return;
		}
		$res .= '<ul>'.$list.'</ul>'."\n";

		// Render full content
		return $w->renderDiv($w->content_only,'rosetta-entries '.$w->class,'',$res);
	}
}
