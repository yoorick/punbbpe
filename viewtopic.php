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

  PunBB PE uses separated lists for each kind of board.
  This is why Index link and quickjump list are different.

  (c) 2007 artoodetoo (master@punbb-pe.org.ru)

************************************************************************/


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/file_upload.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
if ($id < 1 && $pid < 1)
	message($lang_common['Bad request']);

// Load the viewtopic.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';

// If a post ID is specified we determine topic ID and page number so we can redirect to the correct message
if ($pid)
{
	$result = $db->query('SELECT topic_id FROM '.$db->prefix.'posts WHERE id='.$pid) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$id = $db->result($result);

	// Determine on what page the post is located (depending on $pun_user['disp_posts'])
	$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$id.' ORDER BY posted') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$num_posts = $db->num_rows($result);

	for ($i = 0; $i < $num_posts; ++$i)
	{
		$cur_id = $db->result($result, $i);
		if ($cur_id == $pid)
			break;
	}
	++$i;	// we started at 0

	$_GET['p'] = ceil($i / $pun_user['disp_posts']);
}

// If action=new, we redirect to the first new post (if any)
else if ($action == 'new' && !$pun_user['is_guest'])
{
	$result = $db->query('SELECT MIN(id) FROM '.$db->prefix.'posts WHERE topic_id='.$id.' AND posted>'.$pun_user['last_visit']) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$first_new_post_id = $db->result($result);

	if ($first_new_post_id)
		header('Location: viewtopic.php?pid='.$first_new_post_id.'#p'.$first_new_post_id);
	else	// If there is no new post, we go to the last post
		header('Location: viewtopic.php?id='.$id.'&action=last');

	exit;
}

// If action=last, we redirect to the last post
else if ($action == 'last')
{
	$result = $db->query('SELECT MAX(id) FROM '.$db->prefix.'posts WHERE topic_id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$last_post_id = $db->result($result);

	if ($last_post_id)
	{
		header('Location: viewtopic.php?pid='.$last_post_id.'#p'.$last_post_id);
		exit;
	}
}


// Fetch some info about the topic
if (!$pun_user['is_guest'])
	$result = $db->query('SELECT t.id, t.subject, t.topic_desc, t.closed, t.num_replies, t.sticky, t.labels, t.poster, f.id AS forum_id, f.sort_by, f.owner_id, f.forum_name, f.moderators, f.cat_id AS cid, c.cat_name, c.kind, fp.post_replies, fp.file_download, s.user_id AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id INNER JOIN '.$db->prefix.'categories AS c ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$pun_user['id'].') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
else
	$result = $db->query('SELECT t.id, t.subject, t.topic_desc, t.closed, t.num_replies, t.sticky, t.labels, t.poster, f.id AS forum_id, f.sort_by, f.owner_id, f.forum_name, f.moderators, f.cat_id AS cid, c.cat_name, c.kind, fp.post_replies, fp.file_download, 0 FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id INNER JOIN '.$db->prefix.'categories AS c ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());

if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$cur_topic = $db->fetch_assoc($result);
$kind = $cur_topic['kind'];
$kind_script = $kinds[$kind];


// Get PREV & NEXT topics for gallery
$prev_topic = $temp = $next_topic = null;
if ($kind == PUN_KIND_GALLERY)
{

	$result = $db->query('SELECT t.id, p.id AS post_id, t.poster, t.subject FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'posts AS p ON (p.topic_id=t.id) AND (p.posted=t.posted) WHERE t.forum_id='.$cur_topic['forum_id'].' ORDER BY sticky DESC, '.(($cur_topic['sort_by'] == '1') ? 'posted' : 'last_post')) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	do
	{
		$next_topic = $temp;
		$temp = $prev_topic;
		$prev_topic = $db->fetch_assoc($result);
	} while ($temp['id'] != $id);
	$db->free_result($result);
}


$person = $cur_topic['owner_id'];
if (!empty($person))
{
	$result = $db->query('SELECT u.is_team, u.teams, u.username, u.style FROM '.$db->prefix.'users AS u WHERE u.id='.$person) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
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
$mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
$is_admmod = (!empty($person) && ($pun_user['id'] == $person || $is_teamleader)) ||
	($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_id'] == PUN_MOD && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

// Can we or can we not post replies?
if ($cur_topic['closed'] == '0')
{
	if (($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1' || $is_admmod)
		$post_link = '<p class="postlink conr"><a href="post.php?tid='.$id.'">'.$lang_topic['Post reply'].'</a></p>';
	else
		$post_link = '';
}
else
{
	$post_link = '<p class="postlink conr">'.$lang_topic['Topic closed'];

	if ($is_admmod)
		$post_link .= ' / <a href="post.php?tid='.$id.'">'.$lang_topic['Post reply'].'</a>';

	$post_link .= '</p>';
}

// Can we or can we not download attachments?
$can_download = ($cur_topic['file_download'] == '' && $pun_user['g_file_download'] == '1') || $cur_topic['file_download'] == '1' || $is_admmod;

// Determine the post offset (based on $_GET['p'])
$num_pages = ceil(($cur_topic['num_replies'] + 1) / $pun_user['disp_posts']);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : $_GET['p'];
$start_from = $pun_user['disp_posts'] * ($p - 1);

// Generate paging links
$paging_links = $lang_common['Pages'].': '.paginate($num_pages, $p, 'viewtopic.php?id='.$id);

// Generate bread crumbs
$bread_crumbs = '<ul>'.((isset($person))? ('<li><a href="'.$base_url.'/profile.php?id='.$person.'">'.(($person==$pun_user['id'])?$lang_common['Yours'] : pun_htmlspecialchars($user['username'])).'</a>&nbsp;&raquo;&nbsp;</li>') : '').'<li><a href="'.$base_url.'/'.basename($kinds[$cur_topic['kind']]).(empty($person)?'':('?user_id='.$person)).'#cat'.$cur_topic['cid'].'">'.pun_htmlspecialchars($cur_topic['cat_name']).'</a>&nbsp;</li><li>&raquo;&nbsp;<a href="'.$base_url.'/viewboard.php?id='.$cur_topic['forum_id'].'">'.pun_htmlspecialchars($cur_topic['forum_name']).'</a></li><li>&nbsp;&raquo;&nbsp;'.pun_htmlspecialchars($cur_topic['subject']).'</li></ul>';

if ($pun_config['o_censoring'] == '1')
	$cur_topic['subject'] = censor_words($cur_topic['subject']);


$quickpost = false;
if ($pun_config['o_quickpost'] == '1' &&
	!$pun_user['is_guest'] &&
	($cur_topic['post_replies'] == '1' || ($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1')) &&
	($cur_topic['closed'] == '0' || $is_admmod))
{
	$required_fields = array('req_message' => $lang_common['Message']);
	$quickpost = true;
}

if (!$pun_user['is_guest'] && $pun_config['o_subscriptions'] == '1')
{
	if ($cur_topic['is_subscribed'])
		// I apologize for the variable naming here. It's a mix of subscription and action I guess :-)
		$context_menu[] = $lang_topic['Is subscribed'].' - <a href="'.$base_url.'/misc.php?unsubscribe='.$id.'">'.$lang_topic['Unsubscribe'].'</a>';
	else
		$context_menu[] = '<a href="'.$base_url.'/misc.php?subscribe='.$id.'">'.$lang_topic['Subscribe'].'</a>';
}
else
	$subscraction = '<div class="clearer"></div>'."\n";

$page_title = pun_htmlspecialchars($pun_config['o_board_title'].' / '.$cur_topic['subject']);
define('PUN_ALLOW_INDEX', 1);
require PUN_ROOT.'include/header.php';

// use kind-dependant view as occasion serves
if (is_file(PUN_ROOT.'include/topic/top_'.$kind_script))
	require PUN_ROOT.'include/topic/top_'.$kind_script;
else
	require PUN_ROOT.'include/topic/top_forums.php';

require PUN_ROOT.'include/parser.php';

// Parse topic description if any
$cur_topic['topic_desc'] = parse_message($cur_topic['topic_desc'], false);

$bg_switch = true;	// Used for switching background color in posts
$post_count = 0;	// Keep track of post numbers

// Retrieve the posts (and their respective poster/online status)
$result = $db->query('SELECT u.is_team, u.teams, u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, g.g_id, g.g_user_title, o.user_id AS is_online FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE p.topic_id='.$id.' ORDER BY p.id LIMIT '.$start_from.','.$pun_user['disp_posts'], true) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
$posts = array();
$pids = array();

if ($prev_topic) $pids[] = $prev_topic['post_id'];
if ($next_topic) $pids[] = $next_topic['post_id'];

while ($cur_post = $db->fetch_assoc($result))
{
	$posts[] = $cur_post;
	$pids[] = $cur_post['id'];
}
$db->free_result($result);

// Retrieve the attachments
include PUN_ROOT.'include/attach/fetch.php';

if ($prev_topic)
	for($i=0; $i<count($attachments); $i++)
		if ((preg_match('#^image/(.*)$#i', $attachments[$i]['mime'])) && ($prev_topic['post_id'] == $attachments[$i]['post_id']))
		{
			$prev_topic['aid'] = $attachments[$i]['id'];
			$prev_topic['location'] = $attachments[$i]['location'];
			break;
		}
if ($next_topic)
	for($i=0; $i<count($attachments); $i++)
		if ((preg_match('#^image/(.*)$#i', $attachments[$i]['mime'])) && ($next_topic['post_id'] == $attachments[$i]['post_id']))
		{
			$next_topic['aid'] = $attachments[$i]['id'];
			$next_topic['location'] = $attachments[$i]['location'];
			break;
		}

// insert popup info panel & its data (javascript)
if ($pun_config['file_popup_info'] == '1')
	include PUN_ROOT.'include/attach/popup_data.php';

foreach ($posts as $cur_post)
{
	$post_count++;
	$post_actions = array();
	$signature = '';

	list($username, $user_title, $is_online, $user_avatar, $user_badges, $user_info, $user_contacts) = format_userinfo($cur_post);

	// Generation post action array (quote, edit, delete etc.)
	if (!$is_admmod)
	{
		if (!$pun_user['is_guest'])
			$post_actions[] = '<li class="postreport"><a href="misc.php?report='.$cur_post['id'].'">'.$lang_topic['Report'].'</a>';

		if ($cur_topic['closed'] == '0')
		{
			if ($cur_post['poster_id'] == $pun_user['id'])
			{
				if ((($start_from + $post_count) == 1 && $pun_user['g_delete_topics'] == '1') || (($start_from + $post_count) > 1 && $pun_user['g_delete_posts'] == '1'))
					$post_actions[] = '<li class="postdelete"><a href="delete.php?id='.$cur_post['id'].'">'.$lang_topic['Delete'].'</a>';
				if ($pun_user['g_edit_posts'] == '1')
					$post_actions[] = '<li class="postedit"><a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a>';
			}

			if (($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1')
				$post_actions[] = '<li class="postquote"><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a>';
		}
	}
	else
		$post_actions[] = '<li class="postreport"><a href="misc.php?report='.$cur_post['id'].'">'.$lang_topic['Report'].'</a>'.$lang_topic['Link separator'].'</li><li class="postdelete"><a href="delete.php?id='.$cur_post['id'].'">'.$lang_topic['Delete'].'</a>'.$lang_topic['Link separator'].'</li><li class="postedit"><a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a>'.$lang_topic['Link separator'].'</li><li class="postquote"><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a>';


	// Switch the background color for every message.
	$bg_switch = ($bg_switch) ? $bg_switch = false : $bg_switch = true;
	$vtbg = ($bg_switch) ? ' roweven' : ' rowodd';


	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

	// Do signature parsing/caching
	if ($cur_post['signature'] != '' && $pun_user['show_sig'] != '0')
	{
		if (isset($signature_cache[$cur_post['poster_id']]))
			$signature = $signature_cache[$cur_post['poster_id']];
		else
		{
			$signature = parse_signature($cur_post['signature']);
			$signature_cache[$cur_post['poster_id']] = $signature;
		}
	}

	// use kind-dependant view as occasion serves
	if (is_file(PUN_ROOT.'include/topic/row_'.$kind_script))
		require PUN_ROOT.'include/topic/row_'.$kind_script;
	else
		require PUN_ROOT.'include/topic/row_forums.php';

}

// use kind-dependant view as occasion serves
if (is_file(PUN_ROOT.'include/topic/bottom_'.$kind_script))
	require PUN_ROOT.'include/topic/bottom_'.$kind_script;
else
	require PUN_ROOT.'include/topic/bottom_forums.php';

// Display quick post if enabled
if ($quickpost)
{
	// use kind-dependant view as occasion serves
	if (is_file(PUN_ROOT.'include/topic/qpost_'.$kind_script))
		require PUN_ROOT.'include/topic/qpost_'.$kind_script;
	else
		require PUN_ROOT.'include/topic/qpost_forums.php';
}

// Increment "num_views" for topic
$low_prio = ($db_type == 'mysql') ? 'LOW_PRIORITY ' : '';
$db->query('UPDATE '.$low_prio.$db->prefix.'topics SET num_views=num_views+1 WHERE id='.$id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

$board_id = $cur_topic['forum_id'];
$footer_style = 'viewtopic';
require PUN_ROOT.'include/footer.php';
