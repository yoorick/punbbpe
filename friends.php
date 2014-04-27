<?php
/***********************************************************************

  Show friendlist or reverse friendlist.
  This file is part of PunBB Power Edition.

  Copyright (C) 2007       artoodetoo (master@punbb-pe.org.ru)

************************************************************************/


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

define('PUN_FRIENDS', 1);

// Load language files
require PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';

define('PUN_ALLOW_INDEX', 0);

$person = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($person < 2)
	message($lang_common['Bad request']);

$action = isset($_GET['action']) ? $_GET['action'] : 'friends';


$result = $db->query('SELECT u.id, u.id AS is_online, 0 AS post_id, u.registration_ip, u.username, u.is_team, u.teams, u.email, u.title, u.realname, u.url, u.gender, u.birthday, u.hide_age, u.interests, u.aboutme, u.jabber, u.icq, u.msn, u.aim, u.yahoo, u.location, u.signature, u.disp_topics, u.disp_posts, u.email_setting, u.save_pass, u.notify_with_post, u.show_smilies, u.show_img, u.show_img_sig, u.show_avatars, u.show_sig, u.timezone, u.time_format, u.date_format, u.language, u.style, u.num_posts, u.num_files, u.file_bonus, u.last_post, u.registered, u.registration_ip, u.admin_note, g.g_id, g.g_user_title FROM '.$db->prefix.'users AS u LEFT JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id WHERE u.id='.$person) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$user = $db->fetch_assoc($result);

// Use THIS user style for this page
if ($pun_user['id'] != $person)
	$pun_user['style'] = $user['style'];


if (($action == 'add') || ($action == 'remove'))
{
	if (($pun_user['g_id'] == PUN_GUEST) || ($person == $pun_user['id']))
		message($lang_common['Bad request']);

	$result = $db->query('SELECT 1 FROM '.$db->prefix.'friends WHERE (id='.$pun_user['id'].') AND (friend_id='.$person.')') or error('Unable to fetch friends info', __FILE__, __LINE__, $db->error());
	$already_friend = $db->num_rows($result);

	$redirect_url = $base_url.'/friends.php?user_id='.$pun_user['id'];

	if (($action == 'add') && !$already_friend)
	{
		$db->query('INSERT INTO '.$db->prefix.'friends(id, friend_id) VALUES('.$pun_user['id'].','.$person.')') or error('Unable to insert to friends', __FILE__, __LINE__, $db->error());
		redirect($redirect_url, $lang_profile['Add friend redirect']);
	}
	else if (($action == 'remove') && $already_friend)
	{
		$db->query('DELETE FROM '.$db->prefix.'friends WHERE (id='.$pun_user['id'].') AND (friend_id='.$person.')') or error('Unable to delete from friends', __FILE__, __LINE__, $db->error());
		redirect($redirect_url, $lang_profile['Remove friend redirect']);
	}
	else
		message($lang_common['Bad request']);
}
else if ($action == 'friends')
{
	$title = sprintf($lang_profile['Friends of'], pun_htmlspecialchars($user['username']));
	$context_menu[] = '<a href="'.$base_url.'/friends.php?user_id='.$person.'&amp;action=in_friends">'.$lang_profile['Action In friend'].'</a>';
	$friend_sql = '(d.friend_id=u.id) AND (d.id='.$person.')';
}
else if ($action == 'in_friends')
{
	$title = sprintf($lang_profile['In friend'], pun_htmlspecialchars($user['username']));
	$context_menu[] = '<a href="'.$base_url.'/friends.php?user_id='.$person.'">'.$lang_profile['Action Friends of'].'</a>';
	$friend_sql = '(d.id=u.id) AND (d.friend_id='.$person.')';
}
else
	message($lang_common['Bad request']);




$page_title = pun_htmlspecialchars($pun_config['o_board_title']) . ' / '. pun_htmlspecialchars($user['username']) . ' - ' . $lang_profile['Friends'];

// Fetch user count
$result = $db->query('SELECT count(d.friend_id) FROM '.$db->prefix.'friends AS d INNER JOIN '.$db->prefix.'users AS u ON '.$friend_sql.' WHERE u.is_team=0') or error('Unable to fetch friends info', __FILE__, __LINE__, $db->error());
$num_users = $db->result($result);

// Determine the user offset (based on $_GET['p'])
$num_pages = ceil($num_users / 50);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : $_GET['p'];
$start_from = 50 * ($p - 1);

// Generate bread crumbs
$paging_links = $lang_common['Pages'].': '.paginate($num_pages, $p, $base_url.'/favorites.php?user_id='.$person);
$bread_crumbs = '<ul><li><a href="'.$base_url.'/profile.php?id='.$person.'">'.(($person==$pun_user['id'])?$lang_common['Yours'] : pun_htmlspecialchars($user['username'])).'</a></li><li>&nbsp;&raquo;&nbsp;'.(($action=='in_friends')? $lang_profile['Action In friend'] : $lang_profile['Friends']).'</li></ul>';
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
				<th class="tc2" scope="col"><?php echo $lang_profile['Avatar'] ?></th>
				<th class="tc3" scope="col"><?php echo $lang_common['Posts'] ?></th>
				<th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?></th>
			</tr>
		</thead>
		<tbody>
<?php

// Grab the users
$result = $db->query('SELECT u.id, u.username, u.realname, u.is_team, u.teams, u.title, u.num_posts, u.num_files, u.last_post, g.g_id, g.g_user_title FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'friends AS d ON '.$friend_sql.' LEFT JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id WHERE u.is_team=0 ORDER BY u.username LIMIT '.$start_from.', 50') or error('Unable to fetch friends info', __FILE__, __LINE__, $db->error());
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
				$avatar_field = '<img src="'.$base_url.'/'.$pun_config['o_avatars_dir'].'/default.gif" width="48" height="48" alt="" />';
			}
		}
		else
			$avatar_field = '&nbsp;';

		if ($user_data['last_post'])
		{
			$last_post = '<a href="'.$base_url.'/search.php?action=show_user&amp;user_id='.$user_data['id'].'">'.format_time($user_data['last_post']).'</a>';
			if (!$pun_user['is_guest'] && $user_data['last_post'] > $pun_user['last_visit'])
				$last_post = '<strong>'.$last_post.'</strong>';
		}
		else
			$last_post = $lang_common['Never'];
?>
				<tr>
					<td class="tcl"><span><span class="conr">[<?php echo $user_title_field ?>]</span><?php echo $username ?></span></td>
					<td class="tc2"><?php echo $avatar_field ?></td>
					<td class="tc3"><?php echo $user_data['num_posts'] ?></td>
					<td class="tcr"><?php echo $last_post ?></td>
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
