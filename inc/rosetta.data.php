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

/**
 * Rosetta table schema:
 *
 * src_id 		post/page ID
 * src_lang 	post/page lang
 * dst_id 		translated post/page ID
 * dst_lang 	translated post/page lang
 *
 * Principe :
 *
 * Les chaînages individuels sont bijectifs
 * Une nouvelle traduction est ajoutée à tous les billets déjà chaînés
 * Une traduction supprimée du chaînage l'est pour tous les billets chaînés
 *
 * Par exemple, si A (fr) et B (en) sont chaînés, une nouvelle traduction C (de) est ajoutée à A et à B
 *
 * ce qui donne la table suivante :
 * A (fr) -> B (en)
 * A (fr) -> C (de)
 * B (en) -> C (de)
 *
 * Avantage : La suppression d'un billet ou d'une page n'entraîne du coup pas de rupture dans la chaîne de traduction.
 * Inconvénient : Le nombre de tuples peut vite grimper (factorielle du nb de langues / chaîne) si on gère beaucoup de langues.
 */

class rosettaData
{
	/**
	 * Add a new translation for a post/page (only if it does not already exists)
	 *
	 * @param integer $src_id   original post/page id
	 * @param string  $src_lang original lang
	 * @param integer $dst_id   new post/page translation id
	 * @param string  $dst_lang new post/page translation lang
	 *
	 * @return true if translation have been successfully added, else false
	 */
	public static function addTranslation($src_id,$src_lang,$dst_id,$dst_lang)
	{
		global $core;

		// Check args
		if ($src_lang == '' || !$src_lang) {
			// Use blog language if language not specified for original post
			$src_lang = $core->blog->settings->system->lang;
		}
		if ($dst_lang == '' || !$dst_lang) {
			// Use blog language if language not specified for original post
			$dst_lang = $core->blog->settings->system->lang;
		}
		if ($src_lang == $dst_lang) {
			return false;
		}
		if (self::findTranslation($src_id,$src_lang,$dst_lang) != -1) {
			// translation already attached
			return false;
		}

		// Get all existing translations -> array(lang => id)
		$list = self::findAllTranslations($src_id,$src_lang,true);
		// Add the new translation attachment for all existing translations
		try
		{
			foreach ($list as $lang => $id) {
				if (self::findTranslation($id,$lang,$dst_lang,false) == -1) {
					// Add the new translation
					$core->con->writeLock($core->prefix.'rosetta');
					$cur = $core->con->openCursor($core->prefix.'rosetta');
					$cur->src_id = $id;
					$cur->src_lang = $lang;
					$cur->dst_id = $dst_id;
					$cur->dst_lang = $dst_lang;
					$cur->insert();
					$core->con->unlock();
				}
			}
		}
		catch (Exception $e)
		{
			$core->con->unlock();
			throw $e;
		}

		return true;
	}

	/**
	 * Remove an existing translation for a post/page
	 *
	 * @param integer $src_id   original post/page id
	 * @param string  $src_lang original lang
	 * @param integer $dst_id   post/page translation id to be removed
	 * @param string  $dst_lang new post/page translation lang to be removed
	 *
	 * @return true if translation have been successfully removed, else false
	 */
	public static function removeTranslation($src_id,$src_lang,$dst_id,$dst_lang)
	{
		global $core;

		// Check args
		if ($src_lang == '' || !$src_lang) {
			// Use blog language if language not specified for original post
			$src_lang = $core->blog->settings->system->lang;
		}
		if ($dst_lang == '' || !$dst_lang) {
			// Use blog language if language not specified for original post
			$dst_lang = $core->blog->settings->system->lang;
		}
		if ($src_lang == $dst_lang) {
			return false;
		}
		if (self::findTranslation($src_id,$src_lang,$dst_lang) == -1) {
			// Translation attachment not found
			return false;
		}

		$core->con->writeLock($core->prefix.'rosetta');
		try
		{
			// Remove the translations
			$strReq =
				'DELETE FROM '.$core->prefix.'rosetta '.
				"WHERE ".
				"(src_id = '".$core->con->escape($dst_id)."' AND src_lang = '".$core->con->escape($dst_lang)."') OR ".
				"(dst_id = '".$core->con->escape($dst_id)."' AND dst_lang = '".$core->con->escape($dst_lang)."') ";
			$core->con->execute($strReq);
			$core->con->unlock();
		}
		catch (Exception $e)
		{
			$core->con->unlock();
			throw $e;
		}

		return true;
	}

	/**
	 * Find direct posts/pages associated with a post/page id and lang
	 *
	 * @param  integer $id   original post/page id
	 * @param  string  $lang original lang
	 * @param  boolean $full result should include original post/page+lang
	 *
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
	 *
	 * @param  integer $id   		original post/page id
	 * @param  string  $lang 		original lang
	 * @param  boolean $full 		result should include original post/page+lang
	 *
	 * @return array 				associative array (lang => id), false if nothing found
	 */
	public static function findAllTranslations($id,$lang,$full=false)
	{
		global $core;

		if ($lang == '' || !$lang) {
			// Use blog language if language not specified for original post
			$lang = $core->blog->settings->system->lang;
		}

		// Get direct associations
		$list = self::findDirectTranslations($id,$lang,true);
		if (is_array($list)) {
			// Get indirect associations
			$ids = array();
			foreach ($list as $l => $i) {
				$ids[] = array($l => $i);
			}
			while (count($ids)) {
				$pair = array_shift($ids);
				foreach ($pair as $l => $i) {
					$next = self::findDirectTranslations($i,$l,true);
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
			if (!$full) {
				// Remove original from list
				if ($key = array_search($id,$list,true)) {
					unset($list[$key]);
				}
			}
			return $list;
		}

		// Nothing found
		return false;
	}

	/**
	 * Find a post/page id with the requested lang
	 *
	 * @param  integer $src_id   original post/page id
	 * @param  string  $src_lang original lang
	 * @param  string  $dst_lang requested lang
	 * @param  boolean $indirect look also for indirect associations
	 *
	 * @return integer           first found id, -1 if none
	 */
	public static function findTranslation($src_id,$src_lang,$dst_lang,$indirect=true)
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

		if ($indirect) {
			// Looks for an indirect post/page association, ie a -> b and b-> c in table, src = b, looking for c
			$list = self::findAllTranslations($src_id,$src_lang,false);
			if (is_array($list)) {
				if (array_key_exists($dst_lang,$list)) {
					return $list[$dst_lang];
				}
			}
		}

		// No record found
		return -1;
	}
}
