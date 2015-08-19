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

class rosettaData
{
	/**
	 * Find direct posts/pages associated with a post/page id and lang
	 * @param  integer $id   original post/page id
	 * @param  string  $lang original lang
	 * @param  boolean $full result should include original post/page+lang
	 * @return array         associative array (lang => id), false if nothing found
	 */
	private static function findDirectTranslations($id,$lang,$full=false)
	{
		global $core;

		if ($lang == '' || !$lang) {
			// Use blog language if language not specified for original post
			$lang = $core->blog->settings->system->lang;
		}

		$strReq = 'SELECT * FROM '.$core->prefix.'rosetta R '.
			"WHERE ".
			"(R.src_id = '".$core->con->escape($id)."' AND R.src_lang = '".$core->con->escape($lang)."') OR ".
			"(R.dst_id = '".$core->con->escape($id)."' AND R.dst_lang = '".$core->con->escape($lang)."') ";

		$rs = $core->con->select($strReq);
		if ($rs->count()) {
			$list = array();
			while ($rs->fetch()) {
				// Add src couple if requested
				if (($full) || (!$full && ($rs->src_id != $id || $rs->src_lang != $lang))) {
					$list[$rs->src_lang] = $rs->src_id;
				}
				// Add dst couple if requested
				if (($full) || (!$full && ($rs->dst_id != $id || $rs->dst_lang != $lang))) {
					$list[$rs->dst_lang] = $rs->dst_id;
				}
			}
			return $list;
		}

		// Nothing found
		return false;
	}

	/**
	 * Find all posts/pages associated with a post/page id and lang
	 * @param  integer $id   original post/page id
	 * @param  string  $lang original lang
	 * @param  boolean $full result should include original post/page+lang
	 * @return array         associative array (lang => id), false if nothing found
	 */
	private static function findAllTranslations($id,$lang,$full=false)
	{
		global $core;

		if ($lang == '' || !$lang) {
			// Use blog language if language not specified for original post
			$lang = $core->blog->settings->system->lang;
		}

		// Get direct associations
		$list = self::findDirectTranslations($id,$lang,$full);

		if (is_array($list)) {
			// Get indirect associations
			$ids = array();
			foreach ($list as $l => $i) {
				$ids[] = array($l => $i);
			}
			while (count($ids)) {
				$pair = array_shift($ids);
				foreach ($pair as $l => $i) {
					$next = self::findDirectTranslations($i,$l,false);
					if (is_array($next)) {
						foreach ($next as $key => $value) {
							if (!in_array($value,$list,true)) {
								$list[$key] = $value;
								$ids[] = array($key => $value);
							}
						}
					}
				}
			}
			return $list;
		}

		// Nothing found
		return false;
	}

	/**
	 * Find a post/page id with the requested lang
	 * @param  integer $src_id   original post/page id
	 * @param  string  $src_lang original lang
	 * @param  string  $dst_lang requested lang
	 * @return integer           first found id, -1 if none
	 */
	public static function findTranslation($src_id,$src_lang,$dst_lang)
	{
		global $core;

		if ($src_lang == '' || !$src_lang) {
			// Use blog language if language not specified for original post
			$src_lang = $core->blog->settings->system->lang;
		}

		// Looks for a post/page with an association with the corresponding lang
		$strReq = 'SELECT * FROM '.$core->prefix.'rosetta R '.
			"WHERE ".
			"(R.src_id = '".$core->con->escape($src_id)."' AND R.dst_lang = '".$core->con->escape($dst_lang)."') OR ".
			"(R.dst_id = '".$core->con->escape($src_id)."' AND R.src_lang = '".$core->con->escape($dst_lang)."') ".
			"ORDER BY R.dst_id DESC";

		$rs = $core->con->select($strReq);
		if ($rs->count()) {
			// Load first record
			$rs->fetch();

			// Return found ID
			return ($rs->src_id == $src_id ? $rs->dst_id : $rs->src_id);
		}

		// Looks for an indirect post/page association
		$list = self::findAllTranslations($src_id,$src_lang,false);
		if (is_array($list)) {
			if (array_key_exists($dst_lang,$list)) {
				return $list[$dst_lang];
			}
		}

		// No record found
		return -1;
	}
}
