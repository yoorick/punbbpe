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

  This file has previously named index.php
  PunBB PE uses separated lists for each kind of board.
  This is why Index link is different.

  (c) 2007 artoodetoo (master@punbb-pe.ru)

************************************************************************/



if (!isset($kind))
	exit('Yoy cannot execute this script directly.');

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


// Load the index.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/index.php';

$page_title = pun_htmlspecialchars($pun_config['o_board_title']);
define('PUN_ALLOW_INDEX', 1);
define('PUN_BOARDS', 1);

// Build context menu
$context_menu[] = '<a href="'.$base_url.'/extern.php?action=new&amp;kind='.$kind.'&amp;type=RSS">RSS</a>';
if (!$pun_user['is_guest'])
{
	$kind_get = '&amp;kind='.$kind;
	$context_menu[] = '<a href="'.$base_url.'/search.php?action=show_new'.$kind_get.'">'.$lang_common['Show new posts'].'</a>';
	$context_menu[] = '<a href="'.$base_url.'/misc.php?action=markread">'.$lang_common['Mark all as read'].'</a>';
}

// Prepare board filter & Get person info
$board_owners = array();
if (empty($person))
{

	// Look for personal board owners
	$result = $db->query('SELECT u.id, u.username, u.is_team, COUNT(f.id) AS cnt FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'forums AS f ON u.id=f.owner_id INNER JOIN '.$db->prefix.'categories AS c ON c.id=f.cat_id AND c.kind='.$kind.' GROUP BY u.id ORDER BY u.username') or error('Unable to fetch users with boards', __FILE__, __LINE__, $db->error());
	while ($board_owner = $db->fetch_assoc($result)) $board_owners[] = $board_owner;

	$owner = 'f.owner_id IS NULL';
}
else
{
	if ($person <= 1)
		message($lang_common['Bad request']);

	$owner = 'f.owner_id='.$person;
	$result = $db->query('SELECT 0 AS id, u.id AS poster_id, u.registration_ip, u.username, u.is_team, u.teams, u.email, u.title, u.url, u.location, u.email_setting, u.style, u.num_posts, u.registered, u.admin_note, g.g_id, g.g_user_title, o.user_id AS is_online FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE u.id='.$person) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$user = $db->fetch_assoc($result);
	$teams = ($user['teams']!='') ? unserialize($user['teams']) : array();
	$is_teamleader = ($pun_user['g_id'] == PUN_ADMIN) || ($user['is_team'] == '1' && in_array($pun_user['id'], array_values($teams)));

	// Use THIS user style for this page
	if ($pun_user['id'] != $person)
		$pun_user['style'] = $user['style'];

}

// Generate bread crumbs
$paging_links = $lang_common['Pages'].': <strong>1</strong>'; // temporary
$bread_crumbs = '<ul>'.((isset($person))? ('<li><a href="'.$base_url.'/profile.php?id='.$person.'">'.(($person==$pun_user['id'])?$lang_common['Yours'] : pun_htmlspecialchars($user['username'])).'</a>&nbsp;&raquo;&nbsp;</li>') : '').'<li>'.$lang_common['Boards kind'][$kind].'</li></ul>';
$post_link = '';

require PUN_ROOT.'include/header.php';


// Print the list of board owners
if (count($board_owners))
	require PUN_ROOT.'/include/board/owner_list.php';

// Print the categories and boards
$result = $db->query('SELECT '.
		'c.id AS cid, c.cat_name, '.
		'f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster '.
	'FROM '.
		$db->prefix.'categories AS c INNER JOIN '.
		$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.
		$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') '.
	'WHERE '.
		'('.$owner.') AND '.
		'(c.kind='.$kind.') AND (fp.read_forum IS NULL OR fp.read_forum=1) ORDER BY c.disp_position, c.id, f.disp_position', true)
	or error('Unable to fetch category/board list', __FILE__, __LINE__, $db->error());

$cur_category = 0;
$cat_count = 0;
while ($cur_board = $db->fetch_assoc($result))
{
	$moderators = '';

	if ($cur_board['cid'] != $cur_category)	// A new category since last iteration?
	{
		if ($cur_category != 0)
			echo "\t\t\t".'</tbody>'."\n\t\t\t".'</table>'."\n\t\t".'</div>'."\n\t".'</div>'."\n".'</div>'."\n\n";

		++$cat_count;

?>
<div id="idx<?php echo $cat_count ?>" class="blocktable">
	<h2><span><a name="cat<?php echo $cur_board['cid'].'"></a>'.pun_htmlspecialchars($cur_board['cat_name']) ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_common['Board kind'][$kind] ?></th>
					<th class="tc2" scope="col"><?php echo $lang_index['Topics'] ?></th>
					<th class="tc3" scope="col"><?php echo $lang_common['Posts'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php

		$cur_category = $cur_board['cid'];
	}

	$item_status = '';
	$icon_text = $lang_common['Normal icon'];
	$icon_type = 'icon';

	// Are there new posts?
	if (!$pun_user['is_guest'] && $cur_board['last_post'] > $pun_user['last_visit'])
	{
		$item_status = 'inew';
		$icon_text = $lang_common['New icon'];
		$icon_type = 'icon inew';
	}

	// Is this a redirect board?
	if ($cur_board['redirect_url'] != '')
	{
		$forum_field = '<h3><a href="'.pun_htmlspecialchars($cur_board['redirect_url']).'" title="'.$lang_index['Link to'].' '.pun_htmlspecialchars($cur_board['redirect_url']).'">'.pun_htmlspecialchars($cur_board['forum_name']).'</a></h3>';
		$num_topics = $num_posts = '&nbsp;';
		$item_status = 'iredirect';
		$icon_text = $lang_common['Redirect icon'];
		$icon_type = 'icon';
	}
	else
	{
		$forum_field = '<h3><a href="viewboard.php?id='.$cur_board['fid'].'">'.pun_htmlspecialchars($cur_board['forum_name']).'</a></h3>';
		$num_topics = $cur_board['num_topics'];
		$num_posts = $cur_board['num_posts'];
	}

	if ($cur_board['forum_desc'] != '')
		$forum_field .= "\n\t\t\t\t\t\t\t\t<p>".$cur_board['forum_desc'].'</p>';


	// If there is a last_post/last_poster.
	if ($cur_board['last_post'] != '')
		$last_post = '<a href="viewtopic.php?pid='.$cur_board['last_post_id'].'#p'.$cur_board['last_post_id'].'">'.format_time($cur_board['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_board['last_poster']).'</span>';
	else
		$last_post = '&nbsp;';

	if ($cur_board['moderators'] != '')
	{
		$mods_array = unserialize($cur_board['moderators']);
		$moderators = array();

		while (list($mod_username, $mod_id) = @each($mods_array))
			$moderators[] = '<a href="profile.php?id='.$mod_id.'">'.pun_htmlspecialchars($mod_username).'</a>';

		$moderators = "\t\t\t\t\t\t\t\t".'<p><em>('.$lang_common['Moderated by'].'</em> '.implode(', ', $moderators).')</p>'."\n";
	}

?>
 				<tr<?php if ($item_status != '') echo ' class="'.$item_status.'"'; ?>>
					<td class="tcl">
						<div class="intd">
							<div class="<?php echo $icon_type ?>"><div class="nosize"><?php echo $icon_text ?></div></div>
							<div class="tclcon">
								<?php echo $forum_field."\n".$moderators ?>
							</div>
						</div>
					</td>
					<td class="tc2"><?php echo $num_topics ?></td>
					<td class="tc3"><?php echo $num_posts ?></td>
					<td class="tcr"><?php echo $last_post ?></td>
				</tr>
<?php

}

// Did we output any categories and board?
if ($cur_category > 0)
	echo "\t\t\t".'</tbody>'."\n\t\t\t".'</table>'."\n\t\t".'</div>'."\n\t".'</div>'."\n".'</div>'."\n\n";
else
{
	if (isset($person) && ($person == $pun_user['id'] || $is_teamleader))
		$board_request_info = $lang_index['Board request'].
		  '<a href="'.$base_url.'/misc.php?board_req='.$kind.'&amp;owner='.urlencode($user['username']).'">'.sprintf($lang_index['Board request2'], $lang_common['Board kind'][$kind], $user['username']).'</a>.';
	else
		$board_request_info = '&hellip;';
	echo '<div id="idx0" class="block"><h2>'.$lang_index['No boards'].'</h2><div class="box"><div class="inbox"><p>'.$board_request_info.'</p></div></div></div>';
}


$footer_style = 'boards';
require PUN_ROOT.'include/footer.php';
