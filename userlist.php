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

************************************************************************/


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

define('PUN_USERLIST', 1);

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


// Load the language files
require PUN_ROOT.'lang/'.$pun_user['language'].'/userlist.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/search.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';


// Determine if we are allowed to view post counts
$show_post_count = ($pun_config['o_show_post_count'] == '1' || $pun_user['g_id'] < PUN_GUEST) ? true : false;

$username = (isset($_GET['username']) && $pun_user['g_search_users'] == '1') ? pun_trim($_GET['username']) : '';
$interest = (isset($_GET['interest']) && $pun_user['g_search_users'] == '1') ? pun_trim($_GET['interest']) : '';
$show_group = (!isset($_GET['show_group']) || intval($_GET['show_group']) < -1 && intval($_GET['show_group']) > 2) ? -1 : intval($_GET['show_group']);
//$sort_by = (!isset($_GET['sort_by']) || $_GET['sort_by'] != 'username' && $_GET['sort_by'] != 'registered' && ($_GET['sort_by'] != 'num_posts' || !$show_post_count)) ? 'username' : $_GET['sort_by'];
$sort_by = (!isset($_GET['sort_by']) || $_GET['sort_by'] != 'username' && $_GET['sort_by'] != 'registered' && $_GET['sort_by'] != 'num_posts') ? 'username' : $_GET['sort_by'];
$sort_dir = (!isset($_GET['sort_dir']) || $_GET['sort_dir'] != 'ASC' && $_GET['sort_dir'] != 'DESC') ? 'ASC' : strtoupper($_GET['sort_dir']);
$show_teams = (!isset($_GET['show_teams']) || intval($_GET['show_teams']) < 0 && intval($_GET['show_teams']) > 1) ? 0 : intval($_GET['show_teams']);


$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / '.$lang_common['User list'];
if ($pun_user['g_search_users'] == '1')
	$focus_element = array('userlist', 'username');


$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups WHERE g_id!='.PUN_GUEST.' ORDER BY g_id') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());
$groups = array();
while ($cur_group = $db->fetch_assoc($result))
	$groups[] = $cur_group;

// Create any SQL for the WHERE clause
$where_sql = array();
$like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';

if ($pun_user['g_search_users'] == '1' && $username != '')
	$where_sql[] = 'u.username '.$like_command.' \''.$db->escape(str_replace('*', '%', $username)).'\'';
if ($pun_user['g_search_users'] == '1' && $interest != '')
	$where_sql[] = 'u.interests '.$like_command.' \''.$db->escape('%,'.$interest.',%').'\'';
if ($show_group > -1)
	$where_sql[] = 'u.group_id='.$show_group;

// Fetch user count
$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'users AS u WHERE u.is_team='.$show_teams.' AND u.id>1'.(!empty($where_sql) ? ' AND '.implode(' AND ', $where_sql) : '')) or error('Unable to fetch user list count', __FILE__, __LINE__, $db->error());
$num_users = $db->result($result);


// Determine the user offset (based on $_GET['p'])
$num_pages = ceil($num_users / 50);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : $_GET['p'];
$start_from = 50 * ($p - 1);

// Generate paging links
$paging_links = $lang_common['Pages'].': '.paginate($num_pages, $p, 'userlist.php?username='.urlencode($username).'&amp;show_group='.$show_group.'&amp;sort_by='.$sort_by.'&amp;sort_dir='.strtoupper($sort_dir));
$post_link = '<p class="postlink conr">'.($show_teams ? '<a href="'.$base_url.'/userlist.php">'.$lang_ul['Show users'].'</a>' : '<a href="'.$base_url.'/userlist.php?show_teams=1">'.$lang_ul['Show teams'].'</a>').'</p>';
$bread_crumbs = '<ul><li>'.$lang_common[$show_teams? 'Team list' : 'User list'].'</li></ul>';


define('PUN_ALLOW_INDEX', 1);
require PUN_ROOT.'include/header.php';

?>
<div class="block" id="navi">
	<h2><span><?php echo $lang_search[$show_teams? 'Team search' : 'User search'] ?></span></h2>
	<div class="box">
	<form id="userlist" method="get" action="userlist.php">
		<div class="inform">
			<fieldset>
				<input type="hidden" name="show_teams" value="<?php echo $show_teams ?>" />
				<div class="infldset">
<?php if ($pun_user['g_search_users'] == '1'): ?>					<label class="conl"><?php echo $lang_common['Nick'] ?><br /><input type="text" name="username" value="<?php echo pun_htmlspecialchars($username) ?>" size="20" maxlength="25" /><br /></label>

<?php endif; ?>					<label class="conl"><?php echo $lang_ul['User group']."\n" ?>
					<br /><select name="show_group">
						<option value="-1"<?php if ($show_group == -1) echo ' selected="selected"' ?>><?php echo $lang_ul['All users'] ?></option>
<?php

foreach ($groups as $cur_group)
{
	if ($cur_group['g_id'] == $show_group)
		echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
}

?>
					</select>
					<br /></label>

					<label class="conl"><?php echo $lang_search['Sort by']."\n" ?>
					<br /><select name="sort_by">
						<option value="username"<?php if ($sort_by == 'username') echo ' selected="selected"' ?>><?php echo $lang_common['Nick'] ?></option>
						<option value="registered"<?php if ($sort_by == 'registered') echo ' selected="selected"' ?>><?php echo $lang_common['Registered'] ?></option>
						<option value="num_posts"<?php if ($sort_by == 'num_posts') echo ' selected="selected"' ?>><?php echo $lang_ul[$show_teams? 'No of members' : 'No of posts'] ?></option>
					</select>
					<br /></label>

					<label class="conl"><?php echo $lang_search['Sort order']."\n" ?>
					<br /><select name="sort_dir">
						<option value="ASC"<?php if ($sort_dir == 'ASC') echo ' selected="selected"' ?>><?php echo $lang_search['Ascending'] ?></option>
						<option value="DESC"<?php if ($sort_dir == 'DESC') echo ' selected="selected"' ?>><?php echo $lang_search['Descending'] ?></option>
					</select>
					<br /></label>

					<div class="clearb"></div>

					<br /><br />
					<p><input type="submit" name="search" value="<?php echo $lang_search['Search'] ?>" accesskey="s" /></p>
				</div>
			</fieldset>
		</div>
	</form>
	</div>
</div>
<?php


?>
<div id="content">
	<h2><span><?php echo $lang_common[$show_teams? 'Team list' : 'User list'] ?></span></h2>
<?php

// Grab the users
//$result = $db->query('SELECT u.id AS poster_id, u.is_team, u.username, u.realname, u.title, u.num_posts, u.registered, u.last_post, g.g_id, g.g_user_title FROM '.$db->prefix.'users AS u LEFT JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id WHERE u.is_team='.$show_teams.' AND u.id>1'.(!empty($where_sql) ? ' AND '.implode(' AND ', $where_sql) : '').' ORDER BY '.$sort_by.' '.$sort_dir.' LIMIT '.$start_from.', 50') or error('Unable to fetch user list', __FILE__, __LINE__, $db->error());
$result = $db->query('SELECT u.id AS id, u.registration_ip, u.username, u.realname, u.gender, u.interests, u.aboutme, u.birthday, u.hide_age, u.is_team, u.teams, u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, g.g_id, g.g_user_title, o.user_id AS is_online FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE  u.is_team='.$show_teams.' AND u.id>1'.(!empty($where_sql) ? ' AND '.implode(' AND ', $where_sql) : '').' ORDER BY '.$sort_by.' '.$sort_dir.' LIMIT '.$start_from.', 50') or error('Unable to fetch user list', __FILE__, __LINE__, $db->error());
if ($db->num_rows($result))
{
	while ($user = $db->fetch_assoc($result))
	{
		list($username, $user_title, $is_online, $user_avatar, $user_badges, $user_info, $user_contacts) = format_userinfo($user);

		$username = '<a href="'.$base_url.'/profile.php?id='.$user['id'].'" class="'.($user['is_team']? 'team' : 'user').'">'.pun_htmlspecialchars($user['username']).'</a>';
		if (!empty($user['gender']))
			$gender = ($user['gender'] == 1) ? $lang_profile['Gender male'] : $lang_profile['Gender female'];
		else
			$gender = $lang_profile['Unknown'];

		if (!empty($user['interests']))
		{
			$interests = show_labels($user['interests'], 'userlist.php?'.(($user['is_team']=='1')?'show_teams=1&amp;':'').'interest=');
		}
		else
			$interests = $lang_profile['Unknown'];

		if (!empty($user['birthday']))
		{
			if (!empty($user['hide_age']))
			{
				$birthday = getdate($user['birthday']);
				$month = $birthday['mon'];
				$mday  = $birthday['mday'];
				$birthday = $mday.'-'.mb_substr($lang_profile['Birthday months'][$month], 0, 3);
			}
			else
				// $birthday = format_time($user['birthday'], true);
				$birthday = date($pun_date_formats[$pun_user['date_format']], $user['birthday']);
		}
		else
			$birthday = $lang_profile['Unknown'];

	if ($user['aboutme'] != '')
	{
		require_once PUN_ROOT.'include/parser.php';
		$pun_config['p_sig_img_tag'] = '0'; // suppress image output
		$parsed_aboutme = ($user['aboutme'] != '')? parse_signature($user['aboutme']) : null;
	}
	else
		$parsed_aboutme = null;

?>
<div class="blockpost">
	<div class="box">
		<div class="inbox">
			<div class="postleft">
<?php
require PUN_ROOT.'include/person/userinfo.php';
?>
			</div>
			<div class="postright">
				<dl>
					<dt><?php echo $lang_common['Nick'] ?>: </dt>
					<dd><?php echo $username ?></dd>
					<dt><?php echo $lang_profile['Realname'] ?>: </dt>
					<dd><?php echo ($user['realname'] !='') ? pun_htmlspecialchars(($pun_config['o_censoring'] == '1') ? censor_words($user['realname']) : $user['realname']) : $lang_profile['Unknown']; ?></dd>
<?php
	if ($user['is_team'] != '1')
	{
?>
					<dt><?php echo $lang_profile['Gender'] ?>: </dt>
					<dd><?php echo $gender ?>&nbsp;</dd>
					<dt><?php echo $lang_profile['Birthday'] ?>: </dt>
					<dd><?php echo $birthday ?>&nbsp;</dd>
<?php
	}
?>
					<dt><?php echo $lang_profile['Interests'] ?>: </dt>
					<dd><?php echo $interests ?>&nbsp;</dd>
					<dt><?php echo $lang_profile['Aboutme'] ?>: </dt>
					<dd><?php echo isset($parsed_aboutme) ? $parsed_aboutme : $lang_profile['No aboutme']; ?>&nbsp;</dd>
				</dl>
				<div class="clearer"></div>
			</div>
		</div>
	</div>
</div>
<?php
	}
}
else
	echo "\t\t\t".'<div class="box">'."\n\t\t\t\t\t".'<p>'.$lang_search['No hits'].'</pd></tr>'."\n";

?>
</div>
<div class="clearer"></div>

<?php

require PUN_ROOT.'include/footer.php';
