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
 * Copy of /inc/admin/lib.pager.php with two additionnal columns:
 *
 * - language of post/page
 * - list of existing translation's associations
 *
 * All standard classes are overrided by using $__autoload[…] = <this file>
 */
class dcPager extends pager
{
	protected $form_action;
	protected $form_hidden;

	protected function getLink($li_class,$href,$img_src,$img_src_nolink,$img_alt,$enable_link) {
		if ($enable_link) {
			$formatter = '<li class="%s btn"><a href="%s"><img src="%s" alt="%s"/></a><span class="hidden">%s</span></li>';
			return sprintf ($formatter,
				$li_class,$href,$img_src,$img_alt,$img_alt);
		} else {
			$formatter = '<li class="%s no-link btn"><img src="%s" alt="%s"/></li>';
			return sprintf ($formatter,
				$li_class,$img_src_nolink,$img_alt,$img_alt);
		}
	}
	public function setURL() {
		parent::setURL();
		$url = parse_url($_SERVER['REQUEST_URI']);
		if (isset($url['query'])) {
			parse_str($url['query'],$args);
		} else {
			$args=array();
		}
		# Removing session information
		if (session_id())
		{
			if (isset($args[session_name()]))
				unset($args[session_name()]);
		}
		if (isset($args[$this->var_page])) {
			unset($args[$this->var_page]);
		}
		if (isset($args['ok'])) {
			unset($args['ok']);
		}
		$this->form_hidden = '';
		foreach ($args as $k=>$v) {
			if (is_array($v)) {
				foreach ($v as $k2=>$v2) {
					$this->form_hidden .= form::hidden(array($k.'[]'),html::escapeHTML($v2));
				}
			} else {
				$this->form_hidden .= form::hidden(array($k),html::escapeHTML($v));
			}
		}
		$this->form_action = $url['path'];
	}

	/**
	* Pager Links
	*
	* Returns pager links
	*
	* @return string
	*/
	public function getLinks()
	{
		$this->setURL();
		$htmlFirst = $this->getLink(
			"first",
			sprintf($this->page_url,1),
			"images/pagination/first.png",
			"images/pagination/no-first.png",
			__('First page'),
			($this->env > 1)
		);
		$htmlPrev = $this->getLink(
			"prev",
			sprintf($this->page_url,$this->env-1),
			"images/pagination/previous.png",
			"images/pagination/no-previous.png",
			__('Previous page'),
			($this->env > 1)
		);
		$htmlNext = $this->getLink(
			"next",
			sprintf($this->page_url,$this->env+1),
			"images/pagination/next.png",
			"images/pagination/no-next.png",
			__('Next page'),
			($this->env < $this->nb_pages)
		);
		$htmlLast = $this->getLink(
			"last",
			sprintf($this->page_url,$this->nb_pages),
			"images/pagination/last.png",
			"images/pagination/no-last.png",
			__('Last page'),
			($this->env < $this->nb_pages)
		);
		$htmlCurrent =
			'<li class="active"><strong>'.
			sprintf(__('Page %s / %s'),$this->env,$this->nb_pages).
			'</strong></li>';

		$htmlDirect =
			($this->nb_pages > 1 ?
				sprintf('<li class="direct-access">'.__('Direct access page %s'),
					form::field(array($this->var_page),3,10)).
				'<input type="submit" value="'.__('ok').'" class="reset" '.
				'name="ok" />'.$this->form_hidden.'</li>' : '');

		$res =
			'<form action="'.$this->form_action.'" method="get">'.
			'<div class="pager"><ul>'.
			$htmlFirst.
			$htmlPrev.
			$htmlCurrent.
			$htmlNext.
			$htmlLast.
			$htmlDirect.
			'</ul>'.
			'</div>'.
			'</form>'
		;

		return $this->nb_elements > 0 ? $res : '';
	}
}

class adminGenericList
{
	protected $core;
	protected $rs;
	protected $rs_count;

	public function __construct($core,$rs,$rs_count)
	{
		$this->core =& $core;
		$this->rs =& $rs;
		$this->rs_count = $rs_count;
		$this->html_prev = __('&#171; prev.');
		$this->html_next = __('next &#187;');
	}
}

class adminPostList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='',$filter=false)
	{
		if ($this->rs->isEmpty())
		{
			if( $filter ) {
				echo '<p><strong>'.__('No entry matches the filter').'</strong></p>';
			} else {
				echo '<p><strong>'.__('No entry').'</strong></p>';
			}
		}
		else
		{
			$pager = new dcPager($page,$this->rs_count,$nb_per_page,10);
			$entries = array();
			if (isset($_REQUEST['entries'])) {
				foreach ($_REQUEST['entries'] as $v) {
					$entries[(integer)$v]=true;
				}
			}
			$html_block =
			'<div class="table-outer">'.
			'<table>';

			if( $filter ) {
				$html_block .= '<caption>'.sprintf(__('List of %s entries match the filter.'), $this->rs_count).'</caption>';
			} else {
				$html_block .= '<caption class="hidden">'.__('Entries list').'</caption>';
			}

			$html_block .= '<tr>'.
			'<th colspan="2" class="first">'.__('Title').'</th>'.
			'<th scope="col">'.__('Date').'</th>'.
			'<th scope="col">'.__('Category').'</th>'.
			'<th scope="col">'.__('Author').'</th>'.
			'<th scope="col"><img src="images/comments.png" alt="" title="'.__('Comments').'" /><span class="hidden">'.__('Comments').'</span></th>'.
			'<th scope="col"><img src="images/trackbacks.png" alt="" title="'.__('Trackbacks').'" /><span class="hidden">'.__('Trackbacks').'</span></th>'.
			'<th scope="col">'.__('Status').'</th>'.
			'<th scope="col">'.__('Language').'</th>'.
			'<th scope="col">'.__('Translations').'</th>'.
			'</tr>%s</table></div>';

			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}

			echo $pager->getLinks();

			$blocks = explode('%s',$html_block);

			echo $blocks[0];

			while ($this->rs->fetch())
			{
				echo $this->postLine(isset($entries[$this->rs->post_id]));
			}

			echo $blocks[1];

			echo $pager->getLinks();
		}
	}

	private function postLine($checked)
	{
		if ($this->core->auth->check('categories',$this->core->blog->id)) {
			$cat_link = '<a href="'.$this->core->adminurl->get('admin.category',array('id' => '%s'),'&amp;',true).'">%s</a>';
		} else {
			$cat_link = '%2$s';
		}

		if ($this->rs->cat_title) {
			$cat_title = sprintf($cat_link,$this->rs->cat_id,
			html::escapeHTML($this->rs->cat_title));
		} else {
			$cat_title = __('(No cat)');
		}

		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case 1:
				$img_status = sprintf($img,__('Published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('Unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('Scheduled'),'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img,__('Pending'),'check-wrn.png');
				break;
		}

		$protected = '';
		if ($this->rs->post_password) {
			$protected = sprintf($img,__('Protected'),'locker.png');
		}

		$selected = '';
		if ($this->rs->post_selected) {
			$selected = sprintf($img,__('Selected'),'selected.png');
		}

		$attach = '';
		$nb_media = $this->rs->countMedia();
		if ($nb_media > 0) {
			$attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
			$attach = sprintf($img,sprintf($attach_str,$nb_media),'attach.png');
		}

		$translations = '';
		$list = rosettaData::findAllTranslations($this->rs->post_id,$this->rs->post_lang,false);
		if (is_array($list) && count($list)) {
			dcUtils::lexicalKeySort($list,'admin');
			$langs = l10n::getLanguagesName();
			foreach ($list as $lang => $id) {
				// Display existing translations
				$name = isset($langs[$lang]) ? $langs[$lang] : $langs[$core->blog->settings->system->lang];
				// Get post/page id
				$params = new ArrayObject(array(
					'post_id' => $id,
					'post_type' => $this->rs->post_type,
					'no_content' => true));
				$rs = $this->core->blog->getPosts($params);
				if ($rs->count()) {
					$rs->fetch();
					$translation = sprintf('<a href="%s" title="%s">%s</a>',
						$this->core->getPostAdminURL($rs->post_type,$rs->post_id),
						$rs->post_title,
						$name);
					$translations .= ($translations ? ' / ' : '').$translation;
				}
			}
		}

		$res = '<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">';

		$res .=
		'<td class="nowrap">'.
		form::checkbox(array('entries[]'),$this->rs->post_id,$checked,'','',!$this->rs->isEditable()).'</td>'.
		'<td class="maximal" scope="row"><a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'">'.
		html::escapeHTML($this->rs->post_title).'</a></td>'.
		'<td class="nowrap count">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>'.
		'<td class="nowrap">'.$cat_title.'</td>'.
		'<td class="nowrap">'.html::escapeHTML($this->rs->user_id).'</td>'.
		'<td class="nowrap count">'.$this->rs->nb_comment.'</td>'.
		'<td class="nowrap count">'.$this->rs->nb_trackback.'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$selected.' '.$protected.' '.$attach.'</td>'.
		'<td class="nowrap">'.$this->rs->post_lang.'</td>'.
		'<td class="nowrap">'.$translations.'</td>'.
		'</tr>';

		return $res;
	}
}

class adminPostMiniList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if ($this->rs->isEmpty())
		{
			echo '<p><strong>'.__('No entry').'</strong></p>';
		}
		else
		{
			$pager = new dcPager($page,$this->rs_count,$nb_per_page,10);

			$html_block =
			'<div class="table-outer clear">'.
			'<table><caption class="hidden">'.__('Entries list').'</caption><tr>'.
			'<th scope="col">'.__('Title').'</th>'.
			'<th scope="col">'.__('Date').'</th>'.
			'<th scope="col">'.__('Author').'</th>'.
			'<th scope="col">'.__('Status').'</th>'.
			'<th scope="col">'.__('Language').'</th>'.
			'</tr>%s</table></div>';

			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}

			echo $pager->getLinks();

			$blocks = explode('%s',$html_block);

			echo $blocks[0];

			while ($this->rs->fetch())
			{
				echo $this->postLine();
			}

			echo $blocks[1];

			echo $pager->getLinks();
		}
	}

	private function postLine()
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case 1:
				$img_status = sprintf($img,__('Published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('Unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('Scheduled'),'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img,__('Pending'),'check-wrn.png');
				break;
		}

		$protected = '';
		if ($this->rs->post_password) {
			$protected = sprintf($img,__('Protected'),'locker.png');
		}

		$selected = '';
		if ($this->rs->post_selected) {
			$selected = sprintf($img,__('Selected'),'selected.png');
		}

		$attach = '';
		$nb_media = $this->rs->countMedia();
		if ($nb_media > 0) {
			$attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
			$attach = sprintf($img,sprintf($attach_str,$nb_media),'attach.png');
		}

		$res = '<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">';

		$res .=
		'<td scope="row" class="maximal"><a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'" '.
		'title="'.html::escapeHTML($this->rs->getURL()).'">'.
		html::escapeHTML($this->rs->post_title).'</a></td>'.
		'<td class="nowrap count">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>'.
		'<td class="nowrap">'.html::escapeHTML($this->rs->user_id).'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$selected.' '.$protected.' '.$attach.'</td>'.
		'<td class="nowrap">'.$this->rs->post_lang.'</td>'.
		'</tr>';

		return $res;
	}
}

class adminCommentList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='',$filter=false,$spam=false)
	{
		if ($this->rs->isEmpty())
		{
			if( $filter ) {
				echo '<p><strong>'.__('No comments or trackbacks matches the filter').'</strong></p>';
			} else {
				echo '<p><strong>'.__('No comments').'</strong></p>';
			}
		}
		else
		{
			// Get antispam filters' name
			$filters = array();
			if ($spam) {
				if (class_exists('dcAntispam')) {
					dcAntispam::initFilters();
					$fs = dcAntispam::$filters->getFilters();
					foreach ($fs as $fid => $f)
					{
						$filters[$fid] = $f->name;
					}
				}
			}

			$pager = new dcPager($page,$this->rs_count,$nb_per_page,10);

			$comments = array();
			if (isset($_REQUEST['comments'])) {
				foreach ($_REQUEST['comments'] as $v) {
					$comments[(integer)$v]=true;
				}
			}
			$html_block =
			'<div class="table-outer">'.
			'<table>';

			if( $filter ) {
				$html_block .= '<caption>'.
					sprintf(__(
						'Comment or trackback matching the filter.',
						'List of %s comments or trackbacks matching the filter.',
						$this->rs_count), $this->rs_count).
					'</caption>';
			} else {
				$html_block .= '<caption class="hidden">'.__('Comments and trackbacks list').'</caption>';
			}

			$html_block .= '<tr>'.
			'<th colspan="2" scope="col" abbr="comm" class="first">'.__('Type').'</th>'.
			'<th scope="col">'.__('Author').'</th>'.
			'<th scope="col">'.__('Date').'</th>'.
			'<th scope="col" class="txt-center">'.__('Status').'</th>';
			if ($spam) {
				$html_block .=
				'<th scope="col">'.__('IP').'</th>'.
				'<th scope="col">'.__('Spam filter').'</th>';
			}
			$html_block .=
			'<th scope="col" abbr="entry">'.__('Entry').'</th>'.
			'</tr>%s</table></div>';

			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}

			echo $pager->getLinks();

			$blocks = explode('%s',$html_block);

			echo $blocks[0];

			while ($this->rs->fetch())
			{
				echo $this->commentLine(isset($comments[$this->rs->comment_id]),$spam,$filters);
			}

			echo $blocks[1];

			echo $pager->getLinks();
		}
	}

	private function commentLine($checked=false,$spam=false,$filters=array())
	{
		global $core, $author, $status, $sortby, $order, $nb_per_page;

		$author_url =
		$this->core->adminurl->get('admin.comments',array(
			'n' => $nb_per_page,
			'status' => $status,
			'sortby' => $sortby,
			'order' => $order,
			'author' => $this->rs->comment_author
			));

		$post_url = $this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id);

		$comment_url = $this->core->adminurl->get('admin.comment',array('id' => $this->rs->comment_id));

		$comment_dt =
		dt::dt2str($this->core->blog->settings->system->date_format.' - '.
		$this->core->blog->settings->system->time_format,$this->rs->comment_dt);

		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->comment_status) {
			case 1:
				$img_status = sprintf($img,__('Published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('Unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('Pending'),'check-wrn.png');
				break;
			case -2:
				$img_status = sprintf($img,__('Junk'),'junk.png');
				break;
		}

		$post_title = html::escapeHTML($this->rs->post_title);
		if (mb_strlen($post_title) > 70) {
			$post_title = mb_strcut($post_title,0,67).'...';
		}
		$comment_title = sprintf(__('Edit the %1$s from %2$s'),
			$this->rs->comment_trackback ? __('trackback') : __('comment'),
			html::escapeHTML($this->rs->comment_author));

		$res = '<tr class="line'.($this->rs->comment_status != 1 ? ' offline' : '').'"'.
		' id="c'.$this->rs->comment_id.'">';

		$res .=
		'<td class="nowrap">'.
		form::checkbox(array('comments[]'),$this->rs->comment_id,$checked,'','',0).'</td>'.
		'<td class="nowrap" abbr="'.__('Type and author').'" scope="row">'.
			'<a href="'.$comment_url.'" title="'.$comment_title.'">'.
			'<img src="images/edit-mini.png" alt="'.__('Edit').'"/> '.
			($this->rs->comment_trackback ? __('trackback') : __('comment')).' '.'</a></td>'.
		'<td class="nowrap maximal"><a href="'.$author_url.'">'.html::escapeHTML($this->rs->comment_author).'</a></td>'.
		'<td class="nowrap count">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->comment_dt).'</td>'.
		'<td class="nowrap status txt-center">'.$img_status.'</td>';
		if ($spam) {
			$filter_name = '';
			if ($this->rs->comment_spam_filter) {
				if (isset($filters[$this->rs->comment_spam_filter])) {
					$filter_name = $filters[$this->rs->comment_spam_filter];
				} else {
					$filter_name = $this->rs->comment_spam_filter;
				}
			}
			$res .=
			'<td class="nowrap"><a href="'.$core->adminurl->get("admin.comments",array('ip' => $this->rs->comment_ip)).'">'.$this->rs->comment_ip.'</a></td>'.
			'<td class="nowrap">'.$filter_name.'</td>';
		}
		$res .=
		'<td class="nowrap discrete"><a href="'.$post_url.'">'.
		$post_title.'</a>'.
		($this->rs->post_type != 'post' ? ' ('.html::escapeHTML($this->rs->post_type).')' : '').'</td>';

		$res .= '</tr>';

		return $res;
	}
}

class adminUserList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='',$filter=false)
	{
		if ($this->rs->isEmpty())
		{
			if( $filter ) {
				echo '<p><strong>'.__('No user matches the filter').'</strong></p>';
			} else {
				echo '<p><strong>'.__('No user').'</strong></p>';
			}
		}
		else
		{
			$pager = new dcPager($page,$this->rs_count,$nb_per_page,10);

			$html_block =
			'<div class="table-outer clear">'.
			'<table>';

			if( $filter ) {
				$html_block .= '<caption>'.sprintf(__('List of %s users match the filter.'), $this->rs_count).'</caption>';
			} else {
				$html_block .= '<caption class="hidden">'.__('Users list').'</caption>';
			}

			$html_block .= '<tr>'.
			'<th colspan="2" scope="col" class="first">'.__('Username').'</th>'.
			'<th scope="col">'.__('First Name').'</th>'.
			'<th scope="col">'.__('Last Name').'</th>'.
			'<th scope="col">'.__('Display name').'</th>'.
			'<th scope="col" class="nowrap">'.__('Entries (all types)').'</th>'.
			'</tr>%s</table></div>';

			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}

			echo $pager->getLinks();

			$blocks = explode('%s',$html_block);

			echo $blocks[0];

			while ($this->rs->fetch())
			{
				echo $this->userLine();
			}

			echo $blocks[1];

			echo $pager->getLinks();
		}
	}

	private function userLine()
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		$img_status = '';

		$p = $this->core->getUserPermissions($this->rs->user_id);

		if (isset($p[$this->core->blog->id]['p']['admin'])) {
			$img_status = sprintf($img,__('admin'),'admin.png');
		}
		if ($this->rs->user_super) {
			$img_status = sprintf($img,__('superadmin'),'superadmin.png');
		}
		return
		'<tr class="line">'.
		'<td class="nowrap">'.form::hidden(array('nb_post[]'),(integer) $this->rs->nb_post).
		form::checkbox(array('users[]'),$this->rs->user_id).'</td>'.
		'<td class="maximal" scope="row"><a href="'.$this->core->adminurl->get('admin.user',array('id' => $this->rs->user_id)).'">'.
		$this->rs->user_id.'</a>&nbsp;'.$img_status.'</td>'.
		'<td class="nowrap">'.html::escapeHTML($this->rs->user_firstname).'</td>'.
		'<td class="nowrap">'.html::escapeHTML($this->rs->user_name).'</td>'.
		'<td class="nowrap">'.html::escapeHTML($this->rs->user_displayname).'</td>'.
		'<td class="nowrap count"><a href="'.$this->core->adminurl->get('admin.posts',array('user_id' => $this->rs->user_id)).'">'.
		$this->rs->nb_post.'</a></td>'.
		'</tr>';
	}
}
