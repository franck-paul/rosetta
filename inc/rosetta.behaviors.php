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
	public static function publicHeadContent()
	{
		global $core,$_ctx;

		$core->blog->settings->addNamespace('rosetta');
		if ($core->blog->settings->rosetta->active) {
			if ($core->url->type == 'post' || $core->url->type == 'page') {
				if ($_ctx->posts->post_type == 'post' || $_ctx->posts->post_type == 'page') {
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
			// Get post/page id
			$paramsSrc = new ArrayObject(array(
				'post_url' => $handler->args,
				'post_type' => array('post','page'),
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
						'post_type' => array('post','page'),
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
