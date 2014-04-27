<?php
/***********************************************************************

  Show and set team members or teams of user.
  This file is part of PunBB Power Edition.

  Copyright (C) 2007       artoodetoo (master@punbb-pe.org.ru)

************************************************************************/


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

define('PUN_TEAMS', 1);

// Load language files
require PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';

define('PUN_ALLOW_INDEX', 0);

$person = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($person < 2)
	message($lang_common['Bad request']);

$leader = isset($_GET['leader']) ? intval($_GET['leader']) : 0;


$result = $db->query('SELECT u.id, u.id AS is_online, 0 AS post_id, u.registration_ip, u.username, u.is_team, u.teams, u.email, u.title, u.realname, u.url, u.gender, u.birthday, u.hide_age, u.interests, u.aboutme, u.jabber, u.icq, u.msn, u.aim, u.yahoo, u.location, u.signature, u.disp_topics, u.disp_posts, u.email_setting, u.save_pass, u.notify_with_post, u.show_smilies, u.show_img, u.show_img_sig, u.show_avatars, u.show_sig, u.timezone, u.time_format, u.date_format, u.language, u.style, u.num_posts, u.num_files, u.file_bonus, u.last_post, u.registered, u.registration_ip, u.admin_note, g.g_id, g.g_user_title FROM '.$db->prefix.'users AS u LEFT JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id WHERE u.id='.$person) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$user = $db->fetch_assoc($result);
$teams = ($user['teams']!='') ? unserialize($user['teams']) : array();
$is_teamleader = ($pun_user['g_id'] == PUN_ADMIN) || ($user['is_team'] == '1' && in_array($pun_user['id'], array_values($teams)));

// Use THIS user style for this page
if ($pun_user['id'] != $person)
	$pun_user['style'] = $user['style'];

$action = isset($_GET['action']) ? $_GET['action'] : (($user['is_team'] == '1')? 'members' : 'teams');

if ($action == 'join' || $action == 'leave' || $action == 'add_leader' || $action == 'del_leader')
{
	$now = time();

	if (($pun_user['g_id'] == PUN_GUEST) || ($person == $pun_user['id']))
		message($lang_common['Bad request']);

	$result = $db->query('SELECT 1 FROM '.$db->prefix.'friends WHERE (id='.$pun_user['id'].') AND (friend_id='.$person.')') or error('Unable to fetch friends info', __FILE__, __LINE__, $db->error());
	$already_friend = $db->num_rows($result);

	if ($action == 'join' || $action == 'leave')
		// The new page is "user friends"
		$redirect_url = $base_url.'/teams.php?user_id='.$pun_user['id'];
	else
		// Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to index.php after that)
		$redirect_url = (isset($_SERVER['HTTP_REFERER']) && preg_match('#^'.preg_quote($base_url).'/(.*?)\.php#i', $_SERVER['HTTP_REFERER'])) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : ($base_url.'/profile.php?id='.$person);

	if (($action == 'join') && !$already_friend)
	{
		// Join the team
		$db->query('INSERT INTO '.$db->prefix.'friends(id, friend_id, added) VALUES('.$pun_user['id'].','.$person.','.$now.')') or error('Unable to insert to friends', __FILE__, __LINE__, $db->error());
		// Increment members counter
		$db->query('UPDATE '.$db->prefix.'users SET num_posts=num_posts+1, last_post='.$now.' WHERE id='.$person) or error('Unable to update members counter', __FILE__, __LINE__, $db->error());
		// Change user cached team list
		$user_teams = ($pun_user['teams']!='') ? unserialize($pun_user['teams']) : array();
		$user_teams[$user['username']] = $user['id'];
		ksort($user_teams);
		$user_teams = (!empty($user_teams)) ? '\''.$db->escape(serialize($user_teams)).'\'' : 'NULL';
		$db->query('UPDATE '.$db->prefix.'users SET teams='.$user_teams.' WHERE id='.$pun_user['id']) or error('Unable to update user teams', __FILE__, __LINE__, $db->error());

		redirect($redirect_url, $lang_profile['Join team redirect']);
	}
	else if (($action == 'leave') && $already_friend)
	{
		// Leave the team
		$db->query('DELETE FROM '.$db->prefix.'friends WHERE (id='.$pun_user['id'].') AND (friend_id='.$person.')') or error('Unable to delete from friends', __FILE__, __LINE__, $db->error());
		// Decrement members counter
		$db->query('UPDATE '.$db->prefix.'users SET num_posts=num_posts-1 WHERE id='.$person) or error('Unable to update members counter', __FILE__, __LINE__, $db->error());
		// Change user cached team list
		$user_teams = ($pun_user['teams']!='') ? unserialize($pun_user['teams']) : array();
		foreach ($user_teams as $k => $v) if ($v == $person) unset($user_teams[$k]);
		$user_teams = (!empty($user_teams)) ? '\''.$db->escape(serialize($user_teams)).'\'' : 'NULL';
		$db->query('UPDATE '.$db->prefix.'users SET teams='.$user_teams.' WHERE id='.$pun_user['id']) or error('Unable to update user teams', __FILE__, __LINE__, $db->error());

		redirect($redirect_url, $lang_profile['Leave team redirect']);
	}
	else if ($action == 'add_leader' || $action == 'del_leader')
	{
		if ($leader <= 1 || !$is_teamleader || $user['is_team'] != '1')
			message($lang_common['No permission']);


		// Change leaderlist
		if ($action == 'add_leader' && !in_array($leader, array_values($teams)))
		{
			$result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE id='.$leader) or error('Unable to fetch leader username', __FILE__, __LINE__, $db->error());
			if (!$db->num_rows($result))
				message($lang_common['Bad request']);

			$teams[$db->result($result)] = $leader;
			ksort($teams);
		}
		else if ($action == 'del_leader' && in_array($leader, array_values($teams)))
		{
			foreach ($teams as $k => $v)
				if ($v == $leader)
					unset($teams[$k]);
		}

		$teams = (!empty($teams)) ? '\''.$db->escape(serialize($teams)).'\'' : 'NULL';
		$db->query('UPDATE '.$db->prefix.'users SET teams='.$teams.' WHERE id='.$person) or error('Unable to update team leaderlist', __FILE__, __LINE__, $db->error());

		redirect($redirect_url, $lang_profile['Change leader redirect']);
	}
	else
		message($lang_common['Bad request']);
}
else if ($action == 'teams')
{
	$title = sprintf($lang_profile['Teams of'], pun_htmlspecialchars($user['username']));
	$page_title = $lang_profile['Teams'];
	$friend_sql = '(d.friend_id=u.id) AND (d.id='.$person.') AND (u.is_team=1)';
}
else if ($action == 'members')
{
	$title = sprintf($lang_profile['Members of'], pun_htmlspecialchars($user['username']));
	$page_title = $lang_profile['Members'];
	$friend_sql = '(d.id=u.id) AND (d.friend_id='.$person.')';
}
else
	message($lang_common['Bad request']);




$page_title = pun_htmlspecialchars($pun_config['o_board_title']) . ' / '. pun_htmlspecialchars($user['username']) . ' - ' . $page_title;

// Fetch user count
$result = $db->query('SELECT count(d.friend_id) FROM '.$db->prefix.'friends AS d INNER JOIN '.$db->prefix.'users AS u ON '.$friend_sql) or error('Unable to fetch friends info', __FILE__, __LINE__, $db->error());
$num_users = $db->result($result);

// Determine the user offset (based on $_GET['p'])
$num_pages = ceil($num_users / 50);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : $_GET['p'];
$start_from = 50 * ($p - 1);

// Generate bread crumbs
$paging_links = $lang_common['Pages'].': '.paginate($num_pages, $p, $base_url.'/favorites.php?user_id='.$person);
$bread_crumbs = '<ul><li><a href="'.$base_url.'/profile.php?id='.$person.'">'.(($person==$pun_user['id'])?$lang_common['Yours'] : pun_htmlspecialchars($user['username'])).'</a></li><li>&nbsp;&raquo;&nbsp;'.(($action=='teams')? $lang_profile['Teams'] : $lang_profile['Members']).'</li></ul>';
$post_link = '';

require PUN_ROOT.'include/header.php';

?>
<div id="users1" class="blocktable">
	<h2><span><?php echo $title ?></span></h2>
	<div class="box">
		<div class="inbox">
		<table cellspacing="0">
		<thead>
			<tr>
				<th class="tcl" scope="col"><?php echo $lang_common['Username'] ?></th>
				<th class="tc3" scope="col"><?php echo $lang_profile['Avatar'] ?></th>
				<th class="tc3" scope="col"><?php echo ($user['is_team']=='1')? $lang_common['Posts'] : $lang_common['Members'] ?></th>
				<th class="tcr" scope="col"><?php echo $lang_profile['Leaders'] ?></th>
			</tr>
		</thead>
		<tbody>
<?php

// Grab the users
$result = $db->query('SELECT  u.id, u.username, u.realname, u.is_team, u.teams, u.title, u.num_posts, u.num_files, u.last_post, g.g_id, g.g_user_title FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'friends AS d ON '.$friend_sql.' LEFT JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id ORDER BY u.username LIMIT '.$start_from.', 50') or error('Unable to fetch friends info', __FILE__, __LINE__, $db->error());
if ($db->num_rows($result))
{
	while ($user_data = $db->fetch_assoc($result))
	{
		$user_title_field = get_title($user_data);

		$username = '<a href="profile.php?id='.$user_data['id'].'" class="'.($user_data['is_team']? 'team' : 'user').'">'.pun_htmlspecialchars($user_data['username']).'</a>';
		if (isset($user_data['realname']))
			$username .= ' ('.pun_htmlspecialchars($user_data['realname']).')';

		if ($pun_config['o_avatars'] == '1' && $pun_user['show_avatars'] != '0')
		{
			if (($avatar_field = lookup_file($pun_config['o_avatars_dir'], $user_data['id'], $pun_config['file_image_ext'])) !== null &&
			    ($img_size = @getimagesize(PUN_ROOT.$avatar_field)))
				$avatar_field = '<img src="'.$base_url.'/'.$avatar_field.'" '.$img_size[3].' alt="" />';
			else
			{
				$avatar_field = '<img src="'.$base_url.'/'.$pun_config['o_avatars_dir'].'/default_team.gif" width="48" height="48" alt="" />';
			}
		}
		else
			$avatar_field = '&nbsp;';

		$leaders = '&nbsp;';
		if ($user['is_team'] == '1')
		{
			if (in_array($user_data['id'], array_values($teams)))
				$leaders = $lang_common['yes'].($is_teamleader? (' : <a href="'.$base_url.'/teams.php?action=del_leader&amp;user_id='.$person.'&amp;leader='.$user_data['id'].'">'.$lang_common['remove'].'</a>') : '');
			else
				$leaders = $lang_common['no'].($is_teamleader? (' : <a href="'.$base_url.'/teams.php?action=add_leader&amp;user_id='.$person.'&amp;leader='.$user_data['id'].'">'.$lang_common['add'].'</a>') : '');
		}
		else if ($user['is_team'] != '1' && $user_data['teams'] != '')
		{
			$teams = unserialize($user_data['teams']);
			$temp = array();

			while (list($leader_username, $leader_id) = @each($teams))
				$temp[] = '<a href="'.$base_url.'/profile.php?id='.$leader_id.'" class="user">'.pun_htmlspecialchars($leader_username).'</a>';

			$leaders = '<span>'.implode(', ', $temp).'</span>';
			unset($temp);
		}

?>
				<tr>
					<td class="tcl"><span><span class="conr">[<?php echo $user_title_field ?>]</span><?php echo $username ?></span></td>
					<td class="tc2"><?php echo $avatar_field ?></td>
					<td class="tc3"><?php echo $user_data['num_posts'] ?></td>
					<td class="tcr"><?php echo $leaders ?></td>
				</tr>
<?php

	}
}
else
	echo "\t\t\t".'<tr>'."\n\t\t\t\t\t".'<td class="tcl" colspan="4">'.$lang_profile['Empty friendlist'].'</td></tr>'."\n";

?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<?php

require PUN_ROOT.'include/footer.php';
