<?php
/***********************************************************************

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)

  This file is part of PunBB.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************

  This file has previously named viewforum.php
  PunBB PE uses separated lists for each kind of board.
  This is why Index link and quickjump list are different.

  (c) 2007 artoodetoo (master@punbb-pe.ru)

************************************************************************/


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request']);

// Load the viewboard.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/board.php';

// Fetch some info about the forum
$result = $db->query('SELECT f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics, f.cat_id AS cid, f.owner_id, c.cat_name, c.kind FROM '.$db->prefix.'forums AS f INNER JOIN '.$db->prefix.'categories AS c ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$cur_board = $db->fetch_assoc($result);
$kind = $cur_board['kind'];
$kind_script = $kinds[$kind];
$person = $cur_board['owner_id'];

// Is this a redirect forum? In that case, redirect!
if ($cur_board['redirect_url'] != '')
{
	header('Location: '.$cur_board['redirect_url']);
	exit;
}

// Prepare board filter & Get person info
if (empty($person))
{
	$owner = 'f.owner_id IS NULL';
}
else
{
	$owner = 'f.owner_id='.$person;
	$result = $db->query('SELECT u.id AS poster_id, u.is_team, u.teams, u.registration_ip, u.username, u.email, u.title, u.url, u.location, u.email_setting, u.style, u.num_posts, u.registered, u.admin_note, g.g_id, g.g_user_title, o.user_id AS is_online FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE u.id='.$person) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);
	$user = $db->fetch_assoc($result);
	$teams = ($user['teams']!='') ? unserialize($user['teams']) : array();
	$is_teamleader = ($pun_user['g_id'] == PUN_ADMIN) || ($user['is_team'] == '1' && in_array($pun_user['id'], array_values($teams)));

	// Use THIS user style for this page
	if ($pun_user['id'] != $person)
		$pun_user['style'] = $user['style'];

}

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = array();
if ($cur_board['moderators'] != '')
	$mods_array = unserialize($cur_board['moderators']);

$is_admmod = (!empty($person) && ($pun_user['id'] == $person || $is_teamleader)) ||
	($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_id'] == PUN_MOD && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

// Can we or can we not post new topics?
if (($cur_board['post_topics'] == '' && $pun_user['g_post_topics'] == '1') || $cur_board['post_topics'] == '1' || $is_admmod)
	$post_link = '<p class="postlink conr"><a href="post.php?fid='.$id.'">'.$lang_board['Post topic'].'</a></p>';
else
	$post_link = '';


// Determine the topic offset (based on $_GET['p'])
$num_pages = ceil($cur_board['num_topics'] / $pun_user['disp_topics']);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : $_GET['p'];
$start_from = $pun_user['disp_topics'] * ($p - 1);

// Generate paging links
$paging_links = $lang_common['Pages'].': '.paginate($num_pages, $p, 'viewboard.php?id='.$id);

// Generate bread crumbs
$bread_crumbs = '<ul>'.((isset($person))? ('<li><a href="'.$base_url.'/profile.php?id='.$person.'">'.(($person==$pun_user['id'])?$lang_common['Yours'] : pun_htmlspecialchars($user['username'])).'</a>&nbsp;&raquo;&nbsp;</li>') : '').'<li><a href="'.$base_url.'/'.basename($kinds[$cur_board['kind']]).(empty($person)?'':('?user_id='.$person)).'#cat'.$cur_board['cid'].'">'.pun_htmlspecialchars($cur_board['cat_name']).'</a>&nbsp;</li><li>&raquo;&nbsp;'.pun_htmlspecialchars($cur_board['forum_name']).'</li></ul>';

$page_title = pun_htmlspecialchars($pun_config['o_board_title'].' / '.$cur_board['forum_name']);
define('PUN_ALLOW_INDEX', 1);
define('PUN_VIEWBOARD', 1);

$context_menu[] = '<a href="'.$base_url.'/extern.php?action=new&amp;fid='.$id.'&amp;type=RSS">RSS</a>';

require PUN_ROOT.'include/header.php';

// use kind-dependant view as occasion serves
if (is_file(PUN_ROOT.'include/board/top_'.$kind_script))
	require PUN_ROOT.'include/board/top_'.$kind_script;
else
	require PUN_ROOT.'include/board/top_forums.php';

require PUN_ROOT.'include/parser.php';

// Fetch list of topics to display on this page
if ($pun_user['is_guest'] || $pun_config['o_show_dot'] == '0')
{
	// Without "the dot"
	$sql = 'SELECT id, poster, subject, topic_desc, posted, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to, labels FROM '.$db->prefix.'topics WHERE forum_id='.$id.' ORDER BY sticky DESC, '.(($cur_board['sort_by'] == '1') ? 'posted' : 'last_post').' DESC LIMIT '.$start_from.', '.$pun_user['disp_topics'];
}
else
{
	// With "the dot"
	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'SELECT p.poster_id AS has_posted, t.id, t.subject, t.topic_desc, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, t.labels FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'posts AS p ON t.id=p.topic_id AND p.poster_id='.$pun_user['id'].' WHERE t.forum_id='.$id.' GROUP BY t.id ORDER BY sticky DESC, '.(($cur_board['sort_by'] == '1') ? 'posted' : 'last_post').' DESC LIMIT '.$start_from.', '.$pun_user['disp_topics'];
			break;

		case 'sqlite':
			$sql = 'SELECT p.poster_id AS has_posted, t.id, t.subject, t.topic_desc, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, t.labels FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'posts AS p ON t.id=p.topic_id AND p.poster_id='.$pun_user['id'].' WHERE t.id IN(SELECT id FROM '.$db->prefix.'topics WHERE forum_id='.$id.' ORDER BY sticky DESC, '.(($cur_board['sort_by'] == '1') ? 'posted' : 'last_post').' DESC LIMIT '.$start_from.', '.$pun_user['disp_topics'].') GROUP BY t.id ORDER BY t.sticky DESC, t.last_post DESC';
			break;

		default:
			$sql = 'SELECT p.poster_id AS has_posted, t.id, t.subject, t.topic_desc, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, t.labels FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'posts AS p ON t.id=p.topic_id AND p.poster_id='.$pun_user['id'].' WHERE t.forum_id='.$id.' GROUP BY t.id, t.subject, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, p.poster_id ORDER BY sticky DESC, '.(($cur_board['sort_by'] == '1') ? 'posted' : 'last_post').' DESC LIMIT '.$start_from.', '.$pun_user['disp_topics'];
			break;

	}
}

$bg_switch = true;	// Used for switching background color in topics
$topic_count = 0;	// Keep track of topic numbers

// Retrieve the topics (and their respective poster/online status)
$result = $db->query($sql) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());


// If there are topics in this forum.
if ($db->num_rows($result))
{

	// fetch all the topics and collect ids
	$topics = array();
	$tids = array();
	while ($cur_topic = $db->fetch_assoc($result))
	{
		$topics[] = $cur_topic;
		$tids[] = $cur_topic['id'];
	}
	$db->free_result($result);

	// fetch start posts from topics
	$posts = array();
	$pids = array();
	if (($kind == PUN_KIND_BLOG) || ($kind == PUN_KIND_GALLERY))
	{
		$result_posts = $db->query('SELECT p.topic_id, p.id'.(($kind==PUN_KIND_BLOG)? ', p.message, p.poster_id, p.hide_smilies' : '').' FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON p.topic_id=t.id AND p.posted=t.posted WHERE topic_id IN ('.implode(',',$tids).')') or error('Unable to fetch first posts for topics', __FILE__, __LINE__, $db->error());
		while ($cur_post = $db->fetch_assoc($result_posts))
		{
			$posts[$cur_post['topic_id']] = $cur_post;
			$pids[] = $cur_post['id'];
		}

		if ($kind == PUN_KIND_GALLERY)
		{
			// Get list of attachments
			require PUN_ROOT.'include/file_upload.php';

			// Get list of attachments for first posts
			require PUN_ROOT.'include/attach/fetch.php';

	                // Save id & location of images in posts
			for($i=0; $i<count($topics); $i++)
			{
				$tid = $topics[$i]['id'];
				foreach ($attachments as $attachment)
				{
					if ((preg_match('#^image/(.*)$#i', $attachment['mime'])) && ($posts[$tid]['id'] == $attachment['post_id']))
					{
						$posts[$tid]['aid'] = $attachment['id'];
						$posts[$tid]['location'] = $attachment['location'];
						break;
					}
				}
			}
			unset($attachments);
		}
	}

	foreach ($topics as $cur_topic)
	{
		$topic_count++;

		// Switch the background color for every topic.
		$bg_switch = ($bg_switch) ? $bg_switch = false : $bg_switch = true;
		$vtbg = ($bg_switch) ? ' roweven' : ' rowodd';

		$icon_text = $lang_common['Normal icon'];
		$item_status = '';
		$icon_type = 'icon';

		if ($cur_topic['moved_to'] == null)
			$last_post = '<a href="viewtopic.php?pid='.$cur_topic['last_post_id'].'#p'.$cur_topic['last_post_id'].'">'.format_time($cur_topic['last_post']).'</a> <span class="byuser">'.$lang_common['by'].'&nbsp;'.pun_htmlspecialchars($cur_topic['last_poster']).'</span>';
		else
			$last_post = '&nbsp;';

		if ($pun_config['o_censoring'] == '1')
			$cur_topic['subject'] = censor_words($cur_topic['subject']);

		if ($cur_topic['moved_to'] != 0)
			$subject = $lang_board['Moved'].': <a href="viewtopic.php?id='.$cur_topic['moved_to'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a>';
		else if ($cur_topic['closed'] == '0')
			$subject = '<a href="viewtopic.php?id='.$cur_topic['id'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a>';
		else
		{
			$subject = '<a href="viewtopic.php?id='.$cur_topic['id'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a>';
			$icon_text = $lang_common['Closed icon'];
			$item_status = 'iclosed';
		}

		if (!$pun_user['is_guest'] && $cur_topic['last_post'] > $pun_user['last_visit'] && $cur_topic['moved_to'] == null)
		{
			$icon_text .= ' '.$lang_common['New icon'];
			$item_status .= ' inew';
			$icon_type = 'icon inew';
			$subject = '<strong>'.$subject.'</strong>';
			$subject_new_posts = '<span class="newtext">[&nbsp;<a href="viewtopic.php?id='.$cur_topic['id'].'&amp;action=new" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a>&nbsp;]</span>';
		}
		else
			$subject_new_posts = null;

		// Should we display the dot or not? :)
		if (!$pun_user['is_guest'] && $pun_config['o_show_dot'] == '1')
		{
			if ($cur_topic['has_posted'] == $pun_user['id'])
				$subject = '<strong>&middot;</strong>&nbsp;'.$subject;
			else
				$subject = '&nbsp;&nbsp;'.$subject;
		}

		if ($cur_topic['sticky'] == '1')
		{
			$subject = '<span class="stickytext">'.$lang_board['Sticky'].': </span>'.$subject;
			$item_status .= ' isticky';
			$icon_text .= ' '.$lang_board['Sticky'];
		}

		$num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $pun_user['disp_posts']);

		if ($num_pages_topic > 1)
			$subject_multipage = '[ '.paginate($num_pages_topic, -1, 'viewtopic.php?id='.$cur_topic['id']).' ]';
		else
			$subject_multipage = null;
/*
		// Should we show the "New posts" and/or the multipage links?
		if (!empty($subject_new_posts) || !empty($subject_multipage))
		{
			$subject .= '&nbsp; '.(!empty($subject_new_posts) ? $subject_new_posts : '');
			$subject .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
		}
*/
		$labels = (!empty($pun_config['o_topic_labels']))? $cur_topic['labels']: '';

		// use kind-dependant view as occasion serves
		if (is_file(PUN_ROOT.'include/board/row_'.$kind_script))
			require PUN_ROOT.'include/board/row_'.$kind_script;
		else
			require PUN_ROOT.'include/board/row_forums.php';

	}
}
else
{

	// use kind-dependant view as occasion serves
	if (is_file(PUN_ROOT.'include/board/empty_'.$kind_script))
		require PUN_ROOT.'include/board/empty_'.$kind_script;
	else
		require PUN_ROOT.'include/board/empty_forums.php';

}

// use kind-dependant view as occasion serves
if (is_file(PUN_ROOT.'include/board/bottom_'.$kind_script))
	require PUN_ROOT.'include/board/bottom_'.$kind_script;
else
	require PUN_ROOT.'include/board/bottom_forums.php';

$board_id = $id;
$footer_style = 'viewboard';
require PUN_ROOT.'include/footer.php';

