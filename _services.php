<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of Rosetta, a plugin for Dotclear 2.
#
# Copyright (c) Franck Paul and contributors
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) { return; }

class rosettaRest
{
	/**
	 * Serve method to add a new translation's link for current edited post/page.
	 *
	 * @param	core	<b>dcCore</b>	dcCore instance
	 * @param	get		<b>array</b>	cleaned $_GET
	 *
	 * @return	<b>xmlTag</b>	XML representation of response
	 */
	public static function addTranslation($core,$get)
	{
		$id = !empty($get['id']) ? $get['id'] : -1;
		$lang = !empty($get['lang']) ? $get['lang'] : '';
		$rosetta_id = !empty($get['rosetta_id']) ? $get['rosetta_id'] : -1;
		$rosetta_lang = !empty($get['rosetta_lang']) ? $get['rosetta_lang'] : '';
		$rsp = new xmlTag('rosetta');

		$ret = false;
		$ret = false;
		if ($id != -1 && $rosetta_id != -1) {
			// get new language if not provided
			if ($rosetta_lang == '') {
				$params = new ArrayObject(array(
					'post_id' => $rosetta_id,
					'post_type' => array('post','page'),
					'no_content' => true));

				$rs = $core->blog->getPosts($params);
				if ($rs->count()) {
					// Load first record
					$rs->fetch();
					$rosetta_lang = $rs->post_lang;
				}
			}
			// add the translation link
			$ret = rosettaData::addTranslation($id,$lang,$rosetta_id,$rosetta_lang);
		}

		$rsp->ret = $ret;
		$rsp->msg = $ret ? __('New translation attached.') : __('Error during translation attachment.');

		return $rsp;
	}

	/**
	 * Serve method to remove an existing translation's link for current edited post/page.
	 *
	 * @param	core	<b>dcCore</b>	dcCore instance
	 * @param	get		<b>array</b>	cleaned $_GET
	 *
	 * @return	<b>xmlTag</b>	XML representation of response
	 */
	public static function removeTranslation($core,$get)
	{
		$id = !empty($get['id']) ? $get['id'] : -1;
		$lang = !empty($get['lang']) ? $get['lang'] : '';
		$rosetta_id = !empty($get['rosetta_id']) ? $get['rosetta_id'] : -1;
		$rosetta_lang = !empty($get['rosetta_lang']) ? $get['rosetta_lang'] : '';
		$rsp = new xmlTag('rosetta');

		$ret = false;
		if ($id != -1 && $rosetta_id != -1) {
			// Remove the translation link
			$ret = rosettaData::removeTranslation($id,$lang,$rosetta_id,$rosetta_lang);
		}

		$rsp->ret = $ret;
		$rsp->msg = $ret ? __('Translation removed.') : __('Error during removing translation attachment.');

		return $rsp;
	}
}
