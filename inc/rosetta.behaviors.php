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
}

class rosettaPublicBehaviors
{
	public static function urlHandlerGetArgsDocument($handler)
	{
		global $core;

		$lang = '';
		if (!empty($_GET['lang'])) {
			// Check lang scheme
			if (preg_match('/^[a-z]{2}(-[a-z]{2})?$/',rawurldecode($_GET['lang']),$matches)) {
				// Assume that the URL scheme is for post/page
				$lang = $matches[0];
			}
		}

		if ($lang) {
			// Get post/page id
			$paramsSrc = new ArrayObject(array(
				'post_url' => $handler->args,
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
