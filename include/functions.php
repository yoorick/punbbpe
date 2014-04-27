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

//
// Cookie stuff!
//
function check_cookie(&$pun_user)
{
	global $db, $db_type, $pun_config, $cookie_name, $cookie_seed, $pun_time_formats, $pun_date_formats;

	$now = time();
	$expire = $now + 31536000;	// The cookie expires after a year

	// We assume it's a guest
	$cookie = array('user_id' => 1, 'password_hash' => 'Guest');

	// If a cookie is set, we get the user_id and password hash from it
	if (isset($_COOKIE[$cookie_name]))
		list($cookie['user_id'], $cookie['password_hash']) = @unserialize($_COOKIE[$cookie_name]);

	if ($cookie['user_id'] > 1)
	{
		// Check if there's a user with the user ID and password hash from the cookie
		$sql ='SELECT u.*, g.*, o.logged, o.idle, o.csrf_token'.
			' FROM '.$db->prefix.'users AS u'.
			' INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id'.
			' LEFT JOIN '.$db->prefix.'online AS o ON o.user_id=u.id'.
			' WHERE u.id='.intval($cookie['user_id']);
		$result = $db->query($sql) or error('Unable to fetch user information', __FILE__, __LINE__, $db->error());
		$pun_user = $db->fetch_assoc($result);

		// If user authorisation failed
		if (!isset($pun_user['id']) || md5($cookie_seed.$pun_user['password']) !== $cookie['password_hash'])
		{
			pun_setcookie(1, md5(uniqid(rand(), true)), $expire);
			set_default_user();

			return;
		}

		// Set a default language if the user selected language no longer exists
		if (!@file_exists(PUN_ROOT.'lang/'.$pun_user['language']))
			$pun_user['language'] = $pun_config['o_default_lang'];

		// Set a default style if the user selected style no longer exists
		if (!@file_exists(PUN_ROOT.'style/'.$pun_user['style'].'.css'))
			$pun_user['style'] = $pun_config['o_default_style'];

		if (!$pun_user['disp_topics'])
			$pun_user['disp_topics'] = $pun_config['o_disp_topics_default'];
		if (!$pun_user['disp_posts'])
			$pun_user['disp_posts'] = $pun_config['o_disp_posts_default'];

		if ($pun_user['save_pass'] == '0')
			$expire = 0;

		// Check user has a valid date and time format
		if (!isset($pun_time_formats[$pun_user['time_format']]))
			$pun_user['time_format'] = 0;
		if (!isset($pun_date_formats[$pun_user['date_format']]))
			$pun_user['date_format'] = 0;

		// Define this if you want this visit to affect the online list and the users last visit data
		if (!defined('PUN_QUIET_VISIT'))
		{
			// Update the online list
			if (!$pun_user['logged'])
			{
				$pun_user['logged'] = $now;
				$pun_user['csrf_token'] = random_pass(40);

				// With MySQL/MySQLi, REPLACE INTO avoids a user having two rows in the online table
				switch ($db_type)
				{
					case 'mysql':
					case 'mysqli':
						$db->query('REPLACE INTO '.$db->prefix.'online (user_id, ident, logged, csrf_token) VALUES('.$pun_user['id'].', \''.$db->escape($pun_user['username']).'\', '.$pun_user['logged'].', \''.$pun_user['csrf_token'].'\')') or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
						break;

					default:
						$db->query('INSERT INTO '.$db->prefix.'online (user_id, ident, logged, csrf_token) VALUES('.$pun_user['id'].', \''.$db->escape($pun_user['username']).'\', '.$pun_user['logged'].', \''.$pun_user['csrf_token'].'\')') or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
						break;
				}
			}
			else
			{
				// Special case: We've timed out, but no other user has browsed the forums since we timed out
				if ($pun_user['logged'] < ($now-$pun_config['o_timeout_visit']))
				{
					$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$pun_user['logged'].' WHERE id='.$pun_user['id']) or error('Unable to update user visit data', __FILE__, __LINE__, $db->error());
					$pun_user['last_visit'] = $pun_user['logged'];
				}

				$idle_sql = ($pun_user['idle'] == '1') ? ', idle=0' : '';
				$db->query('UPDATE '.$db->prefix.'online SET logged='.$now.$idle_sql.' WHERE user_id='.$pun_user['id']) or error('Unable to update online list', __FILE__, __LINE__, $db->error());
			}
		}

		$pun_user['is_guest'] = false;
	}
	else
		set_default_user();
}


//
// Fill $pun_user with default values (for guests)
//
function set_default_user()
{
	global $db, $db_type, $pun_user, $pun_config;

	$remote_addr = get_remote_address();

	// Fetch guest user
	$sql = 'SELECT u.*, g.*, o.logged, o.csrf_token'.
		' FROM '.$db->prefix.'users AS u'.
		' INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id'.
		' LEFT JOIN '.$db->prefix.'online AS o ON o.ident=\''.$remote_addr.'\''.
		' WHERE u.id=1';
	$result = $db->query($sql) or error('Unable to fetch guest information', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		exit('Unable to fetch guest information. The table \''.$db->prefix.'users\' must contain an entry with id = 1 that represents anonymous users.');

	$pun_user = $db->fetch_assoc($result);

	// Update online list
	if (!$pun_user['logged'])
	{
		$pun_user['logged'] = time();
		$pun_user['csrf_token'] = random_pass(40);

		// With MySQL/MySQLi, REPLACE INTO avoids a user having two rows in the online table
		switch ($db_type)
		{
			case 'mysql':
			case 'mysqli':
				$db->query('REPLACE INTO '.$db->prefix.'online (user_id, ident, logged, csrf_token) VALUES(1, \''.$db->escape($remote_addr).'\', '.$pun_user['logged'].', \''.$pun_user['csrf_token'].'\')') or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
				break;

			default:
				$db->query('INSERT INTO '.$db->prefix.'online (user_id, ident, logged, csrf_token) VALUES(1, \''.$db->escape($remote_addr).'\', '.$pun_user['logged'].', \''.$pun_user['csrf_token'].'\')') or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
				break;
		}
	}
	else
		$db->query('UPDATE '.$db->prefix.'online SET logged='.time().' WHERE ident=\''.$db->escape($remote_addr).'\'') or error('Unable to update online list', __FILE__, __LINE__, $db->error());

	$pun_user['disp_topics'] = $pun_config['o_disp_topics_default'];
	$pun_user['disp_posts'] = $pun_config['o_disp_posts_default'];
	$pun_user['timezone'] = $pun_config['o_server_timezone'];
	$pun_user['language'] = (isset($_COOKIE['guest_language']))? $_COOKIE['guest_language'] : $pun_config['o_default_lang'];
	$pun_user['style'] = (isset($_COOKIE['guest_style']))? $_COOKIE['guest_style'] : $pun_config['o_default_style'];
	$pun_user['is_guest'] = true;
	$pun_user['is_admmod'] = false;
}


//
// Set a cookie, PunBB style!
//
function pun_setcookie($user_id, $password_hash, $expire)
{
	global $cookie_name, $cookie_path, $cookie_domain, $cookie_secure, $cookie_seed;

	// Enable sending of a P3P header by removing // from the following line (try this if login is failing in IE6)
//	@header('P3P: CP="CUR ADM"');

	if (version_compare(PHP_VERSION, '5.2.0', '>='))
		setcookie($cookie_name, serialize(array($user_id, md5($cookie_seed.$password_hash))), $expire, $cookie_path, $cookie_domain, $cookie_secure, true);
	else
		setcookie($cookie_name, serialize(array($user_id, md5($cookie_seed.$password_hash))), $expire, $cookie_path.'; HttpOnly', $cookie_domain, $cookie_secure);
}


//
// Check whether the connecting user is banned (and delete any expired bans while we're at it)
//
function check_bans()
{
	global $db, $pun_config, $lang_common, $pun_user, $pun_bans;

	// Admins aren't affected
	if ($pun_user['g_id'] == PUN_ADMIN || !$pun_bans)
		return;

	// Add a dot at the end of the IP address to prevent banned address 192.168.0.5 from matching e.g. 192.168.0.50
	$user_ip = get_remote_address().'.';
	$bans_altered = false;

	foreach ($pun_bans as $cur_ban)
	{
		// Has this ban expired?
		if ($cur_ban['expire'] != '' && $cur_ban['expire'] <= time())
		{
			$db->query('DELETE FROM '.$db->prefix.'bans WHERE id='.$cur_ban['id']) or error('Unable to delete expired ban', __FILE__, __LINE__, $db->error());
			$bans_altered = true;
			continue;
		}

		if ($cur_ban['username'] != '' && !strcasecmp($pun_user['username'], $cur_ban['username']))
		{
			$db->query('DELETE FROM '.$db->prefix.'online WHERE ident=\''.$db->escape($pun_user['username']).'\'') or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());
			message($lang_common['Ban message'].' '.(($cur_ban['expire'] != '') ? $lang_common['Ban message 2'].' '.strtolower(format_time($cur_ban['expire'], true)).'. ' : '').(($cur_ban['message'] != '') ? $lang_common['Ban message 3'].'<br /><br /><strong>'.pun_htmlspecialchars($cur_ban['message']).'</strong><br /><br />' : '<br /><br />').$lang_common['Ban message 4'].' <a href="mailto:'.$pun_config['o_admin_email'].'">'.$pun_config['o_admin_email'].'</a>.', true);
		}

		if ($cur_ban['ip'] != '')
		{
			$cur_ban_ips = explode(' ', $cur_ban['ip']);

			for ($i = 0; $i < count($cur_ban_ips); ++$i)
			{
				$cur_ban_ips[$i] = $cur_ban_ips[$i].'.';

				if (substr($user_ip, 0, strlen($cur_ban_ips[$i])) == $cur_ban_ips[$i])
				{
					$db->query('DELETE FROM '.$db->prefix.'online WHERE ident=\''.$db->escape($pun_user['username']).'\'') or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());
					message($lang_common['Ban message'].' '.(($cur_ban['expire'] != '') ? $lang_common['Ban message 2'].' '.strtolower(format_time($cur_ban['expire'], true)).'. ' : '').(($cur_ban['message'] != '') ? $lang_common['Ban message 3'].'<br /><br /><strong>'.pun_htmlspecialchars($cur_ban['message']).'</strong><br /><br />' : '<br /><br />').$lang_common['Ban message 4'].' <a href="mailto:'.$pun_config['o_admin_email'].'">'.$pun_config['o_admin_email'].'</a>.', true);
				}
			}
		}
	}

	// If we removed any expired bans during our run-through, we need to regenerate the bans cache
	if ($bans_altered)
	{
		require_once PUN_ROOT.'include/cache.php';
		generate_bans_cache();
	}
}


//
// Update "Users online"
//
function update_users_online()
{
	global $db, $pun_config, $pun_user;

	$now = time();

	// Fetch all online list entries that are older than "o_timeout_online"
	$result = $db->query('SELECT * FROM '.$db->prefix.'online WHERE logged<'.($now-$pun_config['o_timeout_online'])) or error('Unable to fetch old entries from online list', __FILE__, __LINE__, $db->error());
	while ($cur_user = $db->fetch_assoc($result))
	{
		// If the entry is a guest, delete it
		if ($cur_user['user_id'] == '1')
			$db->query('DELETE FROM '.$db->prefix.'online WHERE ident=\''.$db->escape($cur_user['ident']).'\'') or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());
		else
		{
			// If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
			if ($cur_user['logged'] < ($now-$pun_config['o_timeout_visit']))
			{
				$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$cur_user['logged'].' WHERE id='.$cur_user['user_id']) or error('Unable to update user visit data', __FILE__, __LINE__, $db->error());
				$db->query('DELETE FROM '.$db->prefix.'online WHERE user_id='.$cur_user['user_id']) or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());
			}
			else if ($cur_user['idle'] == '0')
				$db->query('UPDATE '.$db->prefix.'online SET idle=1 WHERE user_id='.$cur_user['user_id']) or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
		}
	}
}


//
// Generate the "navigator" that appears at the top of every page
//
function generate_navlinks()
{
	global $pun_config, $lang_common, $pun_user, $base_url, $kind, $kinds;

	$focused = '';
	if (defined('PUN_INDEX')) $focused = 'index';
	else if (defined('PUN_USERLIST')) $focused = 'userlist';
	else if (defined('PUN_SEARCH') && (!isset($kind) || $kind < 0)) $focused = 'search';
	else if (defined('PUN_PROFILE') || defined('PUN_FAVES')) $focused = 'profile';
	else if (defined('PUN_ADMIN_CONSOLE')) $focused = 'admin';

	$isactive = $focused == 'index' || (empty($focused) && !isset($kind));
	$links[] = '<li id="navindex"'.($isactive?' class="isactive"':'').'><a href="'.$base_url.'/index.php">'.$lang_common['Index'].'</a>';

	foreach (array_keys($kinds) as $k)
	{
		$isactive = empty($focused) && (isset($kind) && intval($kind) == $k);
		$links[] = '<li id="nav'.basename($kinds[$k], '.php').'"'.($isactive?' class="isactive"':'').'><a href="'.$base_url.'/'.basename($kinds[$k]).'">'.$lang_common['Boards kind'][$k].'</a>';
	}

	$links[] = '<li id="navuserlist"'.($focused=='userlist'?' class="isactive"':'').'><a href="'.$base_url.'/userlist.php">'.$lang_common['User list'].'</a>';

	if ($pun_config['o_rules'] == '1')
		$links[] = '<li id="navrules"><a href="'.$base_url.'/misc.php?action=rules">'.$lang_common['Rules'].'</a>';

	if ($pun_user['is_guest'])
	{
		if ($pun_user['g_search'] == '1')
			$links[] = '<li id="navsearch"'.($focused=='search'?' class="isactive"':'').'><a href="'.$base_url.'/search.php">'.$lang_common['Search'].'</a>';

		$info = $lang_common['Not logged in'];
	}
	else
	{
		if ($pun_user['g_id'] > PUN_MOD)
		{
			if ($pun_user['g_search'] == '1')
				$links[] = '<li id="navsearch"'.($focused=='search'?' class="isactive"':'').'><a href="'.$base_url.'/search.php">'.$lang_common['Search'].'</a>';
			$links[] = '<li id="navprofile"'.($focused=='profile'?' class="isactive"':'').'><a href="'.$base_url.'/profile.php?id='.$pun_user['id'].'">'.$lang_common['Yours'].'</a>';
		}
		else
		{
			$links[] = '<li id="navsearch"'.($focused=='search'?' class="isactive"':'').'><a href="'.$base_url.'/search.php">'.$lang_common['Search'].'</a>';
			$links[] = '<li id="navprofile"'.($focused=='profile'?' class="isactive"':'').'><a href="'.$base_url.'/profile.php?id='.$pun_user['id'].'">'.$lang_common['Yours'].'</a>';
			$links[] = '<li id="navadmin"'.($focused=='admin'?' class="isactive"':'').'><a href="'.$base_url.'/admin/index.php">'.$lang_common['Admin'].'</a>';
		}
	}

	// Are there any additional navlinks we should insert into the array before imploding it?
	if ($pun_config['o_additional_navlinks'] != '')
	{
		if (preg_match_all('#([0-9]+)\s*=\s*(.*?)\n#s', $pun_config['o_additional_navlinks']."\n", $extra_links))
		{
			// Insert any additional links into the $links array (at the correct index)
			for ($i = 0; $i < count($extra_links[1]); ++$i)
				array_splice($links, $extra_links[1][$i], 0, array('<li id="navextra'.($i + 1).'">'.$extra_links[2][$i]));
		}
	}

	return '<ul>'."\n\t\t\t\t".implode($lang_common['Link separator'].'</li>'."\n\t\t\t\t", $links).'</li>'."\n\t\t\t".'</ul>';
}


//
// Display the profile navigation menu
//
function generate_profile_menu()
{
	global $lang_common, $lang_profile, $pun_config, $pun_user;
	global $person, $kind, $kinds, $base_url;
	global $user, $view_as_guest, $can_edit, $pun_page;

	if ($pun_page == 'profile')
	{
		$section = isset($_GET['section'])? $_GET['section'] : 'essentials';
	}

?>
		<h2><span><?php echo pun_htmlspecialchars($user['username']) ?></span></h2>
		<div id="userinfo" class="box">
			<div class="inbox">
<?php
	list($username, $user_title, $is_online, $user_avatar, $user_badges, $user_info, $user_contacts) = format_userinfo($user);

	// Load the profile.php language file
	require_once PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';

	// Prepare user info
	require PUN_ROOT.'include/person/userinfo.php';
?>
			</div>
		</div>
		<div id="profilemenu" class="box">
			<div class="inbox">
			  <ul class="primary">
				<li<?php if ($pun_page == 'profile') echo ' class="isactive"'; ?>><a href="<?php echo $base_url.'/profile.php?id='.$person.((!empty($can_edit)) ? '&amp;action=edit' : '').'">'.$lang_common['Profile'] ?></a>
<?php if (($pun_page == 'profile') && !$view_as_guest) { ?>

				<ul class="secondary">
					<li<?php if ($section == 'essentials') echo ' class="isactive"'; ?>><a href="profile.php?section=essentials&amp;id=<?php echo $person ?>"><?php echo $lang_profile['Section essentials'] ?></a></li>
					<li<?php if ($section == 'personal') echo ' class="isactive"'; ?>><a href="profile.php?section=personal&amp;id=<?php echo $person ?>"><?php echo $lang_profile['Section personal'] ?></a></li>
<?php if ($user['is_team']!='1'): ?>					<li<?php if ($section == 'messaging') echo ' class="isactive"'; ?>><a href="profile.php?section=messaging&amp;id=<?php echo $person ?>"><?php echo $lang_profile['Section messaging'] ?></a></li>
<?php endif; ?>					<li<?php if ($section == 'personality') echo ' class="isactive"'; ?>><a href="profile.php?section=personality&amp;id=<?php echo $person ?>"><?php echo $lang_profile['Section personality'] ?></a></li>
					<li<?php if ($section == 'display') echo ' class="isactive"'; ?>><a href="profile.php?section=display&amp;id=<?php echo $person ?>"><?php echo $lang_profile['Section display'] ?></a></li>
					<li<?php if ($section == 'privacy') echo ' class="isactive"'; ?>><a href="profile.php?section=privacy&amp;id=<?php echo $person ?>"><?php echo $lang_profile['Section privacy'] ?></a></li>
<?php if ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_id'] == PUN_MOD && $pun_config['p_mod_ban_users'] == '1')): ?>					<li<?php if ($section == 'admin') echo ' class="isactive"'; ?>><a href="profile.php?section=admin&amp;id=<?php echo $person ?>"><?php echo $lang_profile['Section admin'] ?></a></li>
<?php endif; ?>				</ul>
<?php } ?>
				</li>
				<li<?php if (isset($kind) && ($kind == PUN_KIND_BLOG)   ) echo ' class="isactive"'; ?>><a href="<?php echo $base_url.'/'.$kinds[PUN_KIND_BLOG].'?user_id='.$person.'">'.$lang_common['Board kind'][PUN_KIND_BLOG] ?></a></li>
				<li<?php if (isset($kind) && ($kind == PUN_KIND_GALLERY)) echo ' class="isactive"'; ?>><a href="<?php echo $base_url.'/'.$kinds[PUN_KIND_GALLERY].'?user_id='.$person.'">'.$lang_common['Board kind'][PUN_KIND_GALLERY] ?></a></li>
<?php
	if ($user['is_team'] == '1')
	{
?>
				<li<?php if ($pun_page == 'teams')     echo ' class="isactive"'; ?>><a href="<?php echo $base_url.'/teams.php?user_id='.$person.'">'    .$lang_profile['Members'] ?></a></li>
<?php
	}
	else
	{
?>
				<li<?php if ($pun_page == 'favorites') echo ' class="isactive"'; ?>><a href="<?php echo $base_url.'/favorites.php?user_id='.$person.'">'.$lang_common['Favorites'] ?></a></li>
				<li<?php if ($pun_page == 'teams')     echo ' class="isactive"'; ?>><a href="<?php echo $base_url.'/teams.php?user_id='.$person.'">'    .$lang_profile['Teams'] ?></a></li>
				<li<?php if ($pun_page == 'friends')   echo ' class="isactive"'; ?>><a href="<?php echo $base_url.'/friends.php?user_id='.$person.'">'  .$lang_profile['Friends'] ?></a></li>
<?php
	}
?>
			  </ul>
			</div>
		</div>
<?php

}


//
// Update posts, topics, last_post, last_post_id and last_poster for a forum (redirect topics are not included)
//
function update_board($board_id)
{
	global $db;

	$result = $db->query('SELECT COUNT(id), SUM(num_replies) FROM '.$db->prefix.'topics WHERE moved_to IS NULL AND forum_id='.$board_id) or error('Unable to fetch forum topic count', __FILE__, __LINE__, $db->error());
	list($num_topics, $num_posts) = $db->fetch_row($result);

	$num_posts = $num_posts + $num_topics;		// $num_posts is only the sum of all replies (we have to add the topic posts)

	$result = $db->query('SELECT last_post, last_post_id, last_poster FROM '.$db->prefix.'topics WHERE forum_id='.$board_id.' AND moved_to IS NULL ORDER BY last_post DESC LIMIT 1') or error('Unable to fetch last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))		// There are topics in the forum
	{
		list($last_post, $last_post_id, $last_poster) = $db->fetch_row($result);

		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.$db->escape($last_poster).'\' WHERE id='.$board_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	}
	else	// There are no topics
		$db->query('UPDATE '.$db->prefix.'forums SET num_topics=0, num_posts=0, last_post=NULL, last_post_id=NULL, last_poster=NULL WHERE id='.$board_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
}


//
// Delete a topic and all of it's posts
//
function delete_topic($topic_id)
{
	global $db;

	// Delete the topic and any redirect topics
	$db->query('DELETE FROM '.$db->prefix.'topics WHERE id='.$topic_id.' OR moved_to='.$topic_id) or error('Unable to delete topic', __FILE__, __LINE__, $db->error());

	// Create a list of the post ID's in this topic
	$post_ids = '';
	$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id) or error('Unable to fetch posts', __FILE__, __LINE__, $db->error());
	while ($row = $db->fetch_row($result))
		$post_ids .= ($post_ids != '') ? ','.$row[0] : $row[0];

	// Make sure we have a list of post ID's
	if ($post_ids != '')
	{
		strip_search_index($post_ids);

		// Delete attachments
		require_once PUN_ROOT.'include/file_upload.php';
		delete_post_attachments($post_ids);

		// Delete posts in topic
		$db->query('DELETE FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id) or error('Unable to delete posts', __FILE__, __LINE__, $db->error());
	}

	// Delete any subscriptions for this topic
	$db->query('DELETE FROM '.$db->prefix.'subscriptions WHERE topic_id='.$topic_id) or error('Unable to delete subscriptions', __FILE__, __LINE__, $db->error());
}


//
// Delete a single post
//
function delete_post($post_id, $topic_id)
{
	global $db;

	$result = $db->query('SELECT id, poster, posted FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id.' ORDER BY id DESC LIMIT 2') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	list($last_id, ,) = $db->fetch_row($result);
	list($second_last_id, $second_poster, $second_posted) = $db->fetch_row($result);

	// Delete the post
	$db->query('DELETE FROM '.$db->prefix.'posts WHERE id='.$post_id) or error('Unable to delete post', __FILE__, __LINE__, $db->error());

	strip_search_index($post_id);

	require_once PUN_ROOT.'include/file_upload.php';
	delete_post_attachments($post_id);

	// Count number of replies in the topic
	$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id) or error('Unable to fetch post count for topic', __FILE__, __LINE__, $db->error());
	$num_replies = $db->result($result, 0) - 1;

	// If the message we deleted is the most recent in the topic (at the end of the topic)
	if ($last_id == $post_id)
	{
		// If there is a $second_last_id there is more than 1 reply to the topic
		if (!empty($second_last_id))
			$db->query('UPDATE '.$db->prefix.'topics SET last_post='.$second_posted.', last_post_id='.$second_last_id.', last_poster=\''.$db->escape($second_poster).'\', num_replies='.$num_replies.' WHERE id='.$topic_id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
		else
			// We deleted the only reply, so now last_post/last_post_id/last_poster is posted/id/poster from the topic itself
			$db->query('UPDATE '.$db->prefix.'topics SET last_post=posted, last_post_id=id, last_poster=poster, num_replies='.$num_replies.' WHERE id='.$topic_id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
	}
	else
		// Otherwise we just decrement the reply counter
		$db->query('UPDATE '.$db->prefix.'topics SET num_replies='.$num_replies.' WHERE id='.$topic_id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
}


//
// Replace censored words in $text
//
function censor_words($text)
{
	global $db;
	static $search_for, $replace_with;

	// If not already built in a previous call, build an array of censor words and their replacement text
	if (!isset($search_for))
	{
		$result = $db->query('SELECT search_for, replace_with FROM '.$db->prefix.'censoring') or error('Unable to fetch censor word list', __FILE__, __LINE__, $db->error());
		$num_words = $db->num_rows($result);

		$search_for = array();
		for ($i = 0; $i < $num_words; ++$i)
		{
			list($search_for[$i], $replace_with[$i]) = $db->fetch_row($result);
			$search_for[$i] = '/\b('.str_replace('\*', '\w*?', preg_quote($search_for[$i], '/')).')\b/i';
		}
	}

	if (!empty($search_for))
		$text = substr(preg_replace($search_for, $replace_with, ' '.$text.' '), 1, -1);

	return $text;
}


//
// Determines the correct title for $user
// $user must contain the elements 'username', 'title', 'posts', 'g_id' and 'g_user_title'
//
function get_title($user)
{
	global $db, $pun_config, $pun_bans, $lang_common;
	static $ban_list, $pun_ranks;

	// It is only one case for team account
	if (isset($user['is_team']) && $user['is_team'] == '1')
		return $lang_common['Team'];

	// If not already built in a previous call, build an array of lowercase banned usernames
	if (empty($ban_list))
	{
		$ban_list = array();

		foreach ($pun_bans as $cur_ban)
			$ban_list[] = strtolower($cur_ban['username']);
	}

	// If not already loaded in a previous call, load the cached ranks
	if ($pun_config['o_ranks'] == '1' && empty($pun_ranks))
	{
		@include PUN_ROOT.'cache/cache_ranks.php';
		if (!defined('PUN_RANKS_LOADED'))
		{
			require_once PUN_ROOT.'include/cache.php';
			generate_ranks_cache();
			require PUN_ROOT.'cache/cache_ranks.php';
		}
	}

	// If the user has a custom title
	if ($user['title'] != '')
		$user_title = pun_htmlspecialchars($user['title']);
	// If the user is banned
	else if (in_array(strtolower($user['username']), $ban_list))
		$user_title = $lang_common['Banned'];
	// If the user group has a default user title
	else if ($user['g_user_title'] != '')
		$user_title = pun_htmlspecialchars($user['g_user_title']);
	// If the user is a guest
	else if ($user['g_id'] == PUN_GUEST)
		$user_title = $lang_common['Guest'];
	else
	{
		// Are there any ranks?
		if ($pun_config['o_ranks'] == '1' && !empty($pun_ranks))
		{
			@reset($pun_ranks);
			while (list(, $cur_rank) = @each($pun_ranks))
			{
				if (intval($user['num_posts']) >= $cur_rank['min_posts'])
					$user_title = pun_htmlspecialchars($cur_rank['rank']);
			}
		}

		// If the user didn't "reach" any rank (or if ranks are disabled), we assign the default
		if (!isset($user_title))
			$user_title = $lang_common['Member'];
	}

	return $user_title;
}


//
// Generate a string with numbered links (for multipage scripts)
//
function paginate($num_pages, $cur_page, $link_to)
{
	$pages = array();
	$link_to_all = false;

	// If $cur_page == -1, we link to all pages (used in viewboard.php)
	if ($cur_page == -1)
	{
		$cur_page = 1;
		$link_to_all = true;
	}

	if ($num_pages <= 1)
		$pages = array('<strong>1</strong>');
	else
	{
		if ($cur_page > 3)
		{
			$pages[] = '<a href="'.$link_to.'">1</a>';

			if ($cur_page != 4)
				$pages[] = '&hellip;';
		}

		// Don't ask me how the following works. It just does, OK? :-)
		for ($current = $cur_page - 2, $stop = $cur_page + 3; $current < $stop; ++$current)
		{
			if ($current < 1 || $current > $num_pages)
				continue;
			else if ($current != $cur_page || $link_to_all)
				$pages[] = '<a href="'.$link_to.(($current==1)?'':('&amp;p='.$current)).'">'.$current.'</a>';
			else
				$pages[] = '<strong>'.$current.'</strong>';
		}

		if ($cur_page <= ($num_pages-3))
		{
			if ($cur_page != ($num_pages-3))
				$pages[] = '&hellip;';

			$pages[] = '<a href="'.$link_to.'&amp;p='.$num_pages.'">'.$num_pages.'</a>';
		}
	}

	return implode('&nbsp;', $pages);
}


//
// Display a message
//
function message($message, $no_back_link = false)
{
	global $db, $lang_common, $pun_config, $pun_start, $tpl_main, $base_url, $kinds;
	global $pun_page, $context_menu, $languages;

	if (!defined('PUN_HEADER'))
	{
		global $pun_user;

		$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / '.$lang_common['Info'];
		require PUN_ROOT.'include/header.php';
	}

?>

<div id="msg" class="block">
	<h2><span><?php echo $lang_common['Info'] ?></span></h2>
	<div class="box">
		<div class="inbox">
		<p><?php echo $message ?></p>
<?php if (!$no_back_link): ?>		<p><a href="javascript: history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
<?php endif; ?>		</div>
	</div>
	<div class="clearer"></div>
</div>
<?php

	require PUN_ROOT.'include/footer.php';
}


//
// Format a time string according to $time_format and timezones
//
function format_time($timestamp, $date_only = false)
{
	global $pun_config, $lang_common, $pun_user, $pun_time_formats, $pun_date_formats;

	if ($timestamp == '')
		return $lang_common['Never'];

	$diff = $pun_user['timezone'] * 3600;
	$timestamp += $diff;
	$now = time();

	$date = gmdate($pun_date_formats[$pun_user['date_format']], $timestamp);
	$base = gmdate('Y-m-d', $timestamp);
	$today = gmdate('Y-m-d', $now+$diff);
	$yesterday = gmdate('Y-m-d', $now+$diff-86400);

	if ($base == $today)
		$date = $lang_common['Today'];
	else if ($base == $yesterday)
		$date = $lang_common['Yesterday'];

	if (!$date_only)
		return $date.' '.gmdate($pun_time_formats[$pun_user['time_format']], $timestamp);
	else
		return $date;
}


//
// If we are running pre PHP 4.3.0, we add our own implementation of file_get_contents
//
if (!function_exists('file_get_contents'))
{
	function file_get_contents($filename, $use_include_path = 0)
	{
		$data = '';

		if ($fh = fopen($filename, 'rb', $use_include_path))
		{
			$data = fread($fh, filesize($filename));
			fclose($fh);
		}

		return $data;
	}
}


//
// Make sure that form posted from right place
//
function confirm_referrer($script, $allow_get=false)
{
	global $pun_user, $lang_common, $base_url;

	if (isset($_POST['csrf_hash']))
		$hash = $_POST['csrf_hash'];
	else if ($allow_get && isset($_GET['csrf_hash']))
		$hash = $_GET['csrf_hash'];
	// hash not found
	else
		message($lang_common['Bad referrer']);

	preg_match('#^(https?\://)(www\.)?(.*)$#i', $base_url, $regs);
	$script = $regs[3].'/'.$script;
	$new_hash = pun_hash($pun_user['csrf_token'].$script);

	// wrong hash
	if ($new_hash != $hash)
		message($lang_common['Bad referrer']);
}


//
// Produce token for post validation
//
function csrf_hash()
{
	global $pun_user;

	preg_match('#^(www\.)?(.*)$#i', $_SERVER['HTTP_HOST'], $regs);
	$script = $regs[2].$_SERVER['SCRIPT_NAME'];

	return pun_hash($pun_user['csrf_token'].$script);
}

//
// Generate a random password of length $len
//
function random_pass($len)
{
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	$password = '';
	for ($i = 0; $i < $len; ++$i)
		$password .= substr($chars, (mt_rand() % strlen($chars)), 1);

	return $password;
}


//
// Compute a hash of $str
// Uses sha1() if available. If not, SHA1 through mhash() if available. If not, fall back on md5().
//
function pun_hash($str)
{
	if (function_exists('sha1'))	// Only in PHP 4.3.0+
		return sha1($str);
	else if (function_exists('mhash'))	// Only if Mhash library is loaded
		return bin2hex(mhash(MHASH_SHA1, $str));
	else
		return md5($str);
}


//
// Try to determine the correct remote IP-address
//
function get_remote_address()
{
	return $_SERVER['REMOTE_ADDR'];
}


//
// Equivalent to htmlspecialchars(), but allows &#[0-9]+ (for unicode)
//
function pun_htmlspecialchars($str)
{
	$str = preg_replace('/&(?!#[0-9]+;)/s', '&amp;', $str);
	$str = str_replace(array('<', '>', '"'), array('&lt;', '&gt;', '&quot;'), $str);

	return $str;
}


//
// Equivalent to strlen(), but counts &#[0-9]+ as one character (for unicode)
//
function pun_strlen($str)
{
	return mb_strlen(preg_replace('/&#([0-9]+);/u', '!', $str));
}


//
// Convert \r\n and \r to \n
//
function pun_linebreaks($str)
{
	return str_replace("\r", "\n", str_replace("\r\n", "\n", $str));
}


//
// A more aggressive version of trim()
//
function pun_trim($str)
{
	global $lang_common;

	if (strpos($lang_common['lang_encoding'], '8859') !== false)
	{
		$fishy_chars = array(chr(0x81), chr(0x8D), chr(0x8F), chr(0x90), chr(0x9D), chr(0xA0));
		return trim(str_replace($fishy_chars, ' ', $str));
	}
	else
		return trim($str);
}


//
// Display a message when board is in maintenance mode
//
function maintenance_message()
{
	global $db, $pun_config, $lang_common, $pun_user, $base_url, $kinds;

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\t", '  ', '  ');
	$replace = array('&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
	$message = str_replace($pattern, $replace, $pun_config['o_maintenance_message']);


	// Load the maintenance template
	$tpl_maint = trim(file_get_contents(PUN_ROOT.'include/template/maintenance.tpl'));


	// START SUBST - <pun_include "*">
	while (preg_match('#<pun_include "([^/\\\\]*?)\.(php[45]?|inc|html?|txt)">#', $tpl_maint, $cur_include))
	{
		if (!file_exists(PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2]))
			error('Unable to process user include '.htmlspecialchars($cur_include[0]).' from template maintenance.tpl. There is no such file in folder /include/user/');

		ob_start();
		include PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2];
		$tpl_temp = ob_get_contents();
		$tpl_maint = str_replace($cur_include[0], $tpl_temp, $tpl_maint);
	    ob_end_clean();
	}
	// END SUBST - <pun_include "*">


	// START SUBST - <pun_content_direction>
	$tpl_maint = str_replace('<pun_content_direction>', $lang_common['lang_direction'], $tpl_maint);
	// END SUBST - <pun_content_direction>


	// START SUBST - <pun_char_encoding>
	$tpl_maint = str_replace('<pun_char_encoding>', $lang_common['lang_encoding'], $tpl_maint);
	// END SUBST - <pun_char_encoding>


	// START SUBST - <pun_head>
	ob_start();

?>
<title><?php echo pun_htmlspecialchars($pun_config['o_board_title']).' / '.$lang_common['Maintenance'] ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo $base_url.'/style/'.$pun_user['style'].'.css' ?>" />
<?php

	$tpl_temp = trim(ob_get_contents());
	$tpl_maint = str_replace('<pun_head>', $tpl_temp, $tpl_maint);
	ob_end_clean();
	// END SUBST - <pun_head>


	// START SUBST - <pun_maint_heading>
	$tpl_maint = str_replace('<pun_maint_heading>', $lang_common['Maintenance'], $tpl_maint);
	// END SUBST - <pun_maint_heading>


	// START SUBST - <pun_maint_message>
	$tpl_maint = str_replace('<pun_maint_message>', $message, $tpl_maint);
	// END SUBST - <pun_maint_message>


	// End the transaction
	$db->end_transaction();


	// Close the db connection (and free up any result data)
	$db->close();

	exit($tpl_maint);
}


//
// Display $message and redirect user to $destination_url
//
function redirect($destination_url, $message)
{
	global $db, $pun_config, $lang_common, $pun_user, $base_url;

	// Prefix with base_url (unless there's already a valid URI)
	if (strpos($destination_url, 'http://') !== 0 && strpos($destination_url, 'https://') !== 0 && strpos($destination_url, '/') !== 0)
		$destination_url = $base_url.'/'.$destination_url;

	// Do a little spring cleaning
	$destination_url = preg_replace('/([\r\n])|(%0[ad])|(;[\s]*data[\s]*:)/i', '', $destination_url);

	// If the delay is 0 seconds, we might as well skip the redirect all together
	if ($pun_config['o_redirect_delay'] == '0')
		header('Location: '.str_replace('&amp;', '&', $destination_url));


	// Load the redirect template
	$tpl_redir = trim(file_get_contents(PUN_ROOT.'include/template/redirect.tpl'));


	// START SUBST - <pun_include "*">
	while (preg_match('#<pun_include "([^/\\\\]*?)\.(php[45]?|inc|html?|txt)">#', $tpl_redir, $cur_include))
	{
		if (!file_exists(PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2]))
			error('Unable to process user include '.htmlspecialchars($cur_include[0]).' from template redirect.tpl. There is no such file in folder /include/user/');

		ob_start();
		include PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2];
		$tpl_temp = ob_get_contents();
		$tpl_redir = str_replace($cur_include[0], $tpl_temp, $tpl_redir);
	    ob_end_clean();
	}
	// END SUBST - <pun_include "*">


	// START SUBST - <pun_content_direction>
	$tpl_redir = str_replace('<pun_content_direction>', $lang_common['lang_direction'], $tpl_redir);
	// END SUBST - <pun_content_direction>


	// START SUBST - <pun_char_encoding>
	$tpl_redir = str_replace('<pun_char_encoding>', $lang_common['lang_encoding'], $tpl_redir);
	// END SUBST - <pun_char_encoding>


	// START SUBST - <pun_head>
	ob_start();

?>
<meta http-equiv="refresh" content="<?php echo $pun_config['o_redirect_delay'] ?>;URL=<?php echo str_replace(array('<', '>', '"'), array('&lt;', '&gt;', '&quot;'), $destination_url) ?>" />
<title><?php echo pun_htmlspecialchars($pun_config['o_board_title']).' / '.$lang_common['Redirecting'] ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo $base_url.'/style/'.$pun_user['style'].'.css' ?>" />
<?php

	$tpl_temp = trim(ob_get_contents());
	$tpl_redir = str_replace('<pun_head>', $tpl_temp, $tpl_redir);
	ob_end_clean();
	// END SUBST - <pun_head>


	// START SUBST - <pun_redir_heading>
	$tpl_redir = str_replace('<pun_redir_heading>', $lang_common['Redirecting'], $tpl_redir);
	// END SUBST - <pun_redir_heading>


	// START SUBST - <pun_redir_text>
	$tpl_temp = $message.'<br /><br />'.'<a href="'.$destination_url.'">'.$lang_common['Click redirect'].'</a>';
	$tpl_redir = str_replace('<pun_redir_text>', $tpl_temp, $tpl_redir);
	// END SUBST - <pun_redir_text>


	// START SUBST - <pun_footer>
	ob_start();

	// End the transaction
	$db->end_transaction();

	// Display executed queries (if enabled)
	if (defined('PUN_SHOW_QUERIES'))
		display_saved_queries();

	$tpl_temp = trim(ob_get_contents());
	$tpl_redir = str_replace('<pun_footer>', $tpl_temp, $tpl_redir);
	ob_end_clean();
	// END SUBST - <pun_footer>


	// Close the db connection (and free up any result data)
	$db->close();

	exit($tpl_redir);
}


//
// Display a simple error message
//
function error($message, $file, $line, $db_error = false)
{
	global $pun_config, $base_url, $kinds;

	// Set a default title if the script failed before $pun_config could be populated
	if (empty($pun_config))
		$pun_config['o_board_title'] = 'PunBB';

	// Empty output buffer and stop buffering
	@ob_end_clean();

	// "Restart" output buffering if we are using ob_gzhandler (since the gzip header is already sent)
	if (!empty($pun_config['o_gzip']) && extension_loaded('zlib') && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
		ob_start('ob_gzhandler');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo pun_htmlspecialchars($pun_config['o_board_title']) ?> / Error</title>
<style type="text/css">
<!--
BODY {MARGIN: 10% 20% auto 20%; font: 10px Verdana, Arial, Helvetica, sans-serif}
#errorbox {BORDER: 1px solid #B84623}
H2 {MARGIN: 0; COLOR: #FFFFFF; BACKGROUND-COLOR: #B84623; FONT-SIZE: 1.1em; PADDING: 5px 4px}
#errorbox DIV {PADDING: 6px 5px; BACKGROUND-COLOR: #F1F1F1}
-->
</style>
</head>
<body>

<div id="errorbox">
	<h2>An error was encountered</h2>
	<div>
<?php

	if (defined('PUN_DEBUG'))
	{
		echo "\t\t".'<strong>File:</strong> '.$file.'<br />'."\n\t\t".'<strong>Line:</strong> '.$line.'<br /><br />'."\n\t\t".'<strong>PunBB reported</strong>: '.$message."\n";

		if ($db_error)
		{
			echo "\t\t".'<br /><br /><strong>Database reported:</strong> '.pun_htmlspecialchars($db_error['error_msg']).(($db_error['error_no']) ? ' (Errno: '.$db_error['error_no'].')' : '')."\n";

			if ($db_error['error_sql'] != '')
				echo "\t\t".'<br /><br /><strong>Failed query:</strong> '.pun_htmlspecialchars($db_error['error_sql'])."\n";
		}
	}
	else
		echo "\t\t".'Error: <strong>'.$message.'.</strong>'."\n";

?>
	</div>
</div>

</body>
</html>
<?php

	// If a database connection was established (before this error) we close it
	if ($db_error)
		$GLOBALS['db']->close();

	exit;
}

// DEBUG FUNCTIONS BELOW

//
// Display executed queries (if enabled)
//
function display_saved_queries()
{
	global $db, $lang_common;

	// Get the queries so that we can print them out
	$saved_queries = $db->get_saved_queries();

?>

<div id="debug" class="blocktable">
	<h2><span><?php echo $lang_common['Debug table'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col">Time (s)</th>
					<th class="tcr" scope="col">Query</th>
				</tr>
			</thead>
			<tbody>
<?php

	$query_time_total = 0.0;
	while (list(, $cur_query) = @each($saved_queries))
	{
		$query_time_total += $cur_query[1];

?>
				<tr>
					<td class="tcl"><?php echo ($cur_query[1] != 0) ? $cur_query[1] : '&nbsp;' ?></td>
					<td class="tcr"><?php echo pun_htmlspecialchars($cur_query[0]) ?></td>
				</tr>
<?php

	}

?>
				<tr>
					<td class="tcl" colspan="2">Total query time: <?php echo $query_time_total ?> s</td>
				</tr>
			</tbody>
			</table>
		</div>
	</div>
</div>
<?php

}


//
// Unset any variables instantiated as a result of register_globals being enabled
//
function unregister_globals()
{
	$register_globals = @ini_get('register_globals');
	if ($register_globals === "" || $register_globals === "0" || strtolower($register_globals === "off"))
		return;

	// Prevent script.php?GLOBALS[foo]=bar
	if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
		exit('I\'ll have a steak sandwich and... a steak sandwich.');

	// Variables that shouldn't be unset
	$no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

	// Remove elements in $GLOBALS that are present in any of the superglobals
	$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
	foreach ($input as $k => $v)
	{
		if (!in_array($k, $no_unset) && isset($GLOBALS[$k]))
		{
			unset($GLOBALS[$k]);
			unset($GLOBALS[$k]);	// Double unset to circumvent the zend_hash_del_key_or_index hole in PHP <4.4.3 and <5.1.4
		}
	}
}


//
// Dump contents of variable(s)
//
function dump()
{
	echo '<pre>';

	$num_args = func_num_args();

	for ($i = 0; $i < $num_args; ++$i)
	{
		print_r(func_get_arg($i));
		echo "\n\n";
	}

	echo '</pre>';
	exit;
}


//
// Deal with topic labels.
//   purge_label(string): string	 - prepare one label
//   explode_labels(string): array	 - CSV text to array of labels
//   implode_labels(array): string	 - convert array of labels to CSV ready for SQL 'like' operation
//   show_labels(string [,$url]): string - build navigation string
//
function purge_label($dirty)
{
	$lab = mb_strtolower(str_replace(',', ' ', $dirty));
	$lab = preg_replace('#[[:space:]]+#u', ' ', $lab);
	return trim($lab);
}


function explode_labels($labels)
{
	$a = explode(',', $labels);

	$pure_a = array();
	foreach($a as $v)
	{
		$v = purge_label($v);
		if (!empty($v)) $pure_a[] = $v;
	}
	asort($pure_a);
	return array_unique($pure_a);
}


function implode_labels($lab_arr)
{
	if (empty($lab_arr)) return null;

	$arr = array();
	foreach ($lab_arr as $lab)
	{
		$lab = purge_label($lab);
		if (!empty($lab)) $arr[] = $lab;
	}

	$arr = array_unique($arr);
	if (empty($arr))
		return null;
	else
		return ',' . implode(',', $arr) . ',';
}


function show_labels($labels, $url=null)
{
	global $lang_common, $base_url;

	if (!isset($url))
		$url = $base_url.'/search.php?action=show_label&text=';

	$s = null;
	if (!empty($labels))
	{
		$lab_array = explode_labels($labels);
		for($k=0; $k<count($lab_array); $k++)
			$lab_array[$k] = '<a href="'.$url.rawurlencode($lab_array[$k]).'">'.pun_htmlspecialchars($lab_array[$k]).'</a>';
		$s = implode(', ', $lab_array);
		unset($lab_array);
	}
	return $s;
}


//
// Get user info for Personal panel (in Profile, User Blogs, etc.)
//
function format_userinfo($user)
{
        global $pun_user, $pun_config, $lang_common, $lang_topic, $base_url;

	// Load language file for the viewtopic.php
	require_once PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';

        $user_id = (isset($user['poster_id']))? $user['poster_id'] : $user['id'];
        $poster_ip = (isset($user['poster_ip']))? $user['poster_ip'] : $user['registration_ip'];
	$user_avatar = '';
	$user_badges = '';
	$user_info = array();
	$user_contacts = array();
	$is_online = '';

	// If the poster is a registered user.
	if ($user_id > 1)
	{
		$username = '<a href="profile.php?id='.$user_id.'">'.pun_htmlspecialchars($user['username']).'</a>';
		$user_title = get_title($user);

		if ($pun_config['o_censoring'] == '1')
			$user_title = censor_words($user_title);

		// Format the online indicator
		$is_online = (isset($user['is_online']) && $user['is_online'] == $user_id) ? '<strong>'.$lang_topic['Online'].'</strong>' : $lang_topic['Offline'];

		if ($pun_config['o_avatars'] == '1' && $pun_user['show_avatars'] != '0')
		{
			if (($user_avatar = lookup_file($pun_config['o_avatars_dir'], $user_id, $pun_config['file_image_ext'])) !== null &&
			    ($img_size = @getimagesize(PUN_ROOT.$user_avatar)))
				$user_avatar = '<img src="'.$base_url.'/'.$user_avatar.'" '.$img_size[3].' alt="" />';
			else
			{
				if ($user['is_team'] == '1')
					$user_avatar = '<img src="'.$base_url.'/'.$pun_config['o_avatars_dir'].'/default_team.gif" width="48" height="48" alt="" />';
				else
					$user_avatar = '<img src="'.$base_url.'/'.$pun_config['o_avatars_dir'].'/default.gif" width="48" height="48" alt="" />';
			}
		}

		if ($user['is_team'] != '1' && $user['teams'] != '')
		{
			$teams = unserialize($user['teams']);
			$user_badges = array();
			foreach ($teams as $k => $v)
			{
				if (count($user_badges) == 3)
				{
					$user_badges[] = '<a class="more" href="'.$base_url.'/teams.php?user_id='.$user_id.'">'.$lang_common['continue reading'].'</a>';
					break;
				}
				$link = $base_url.'/profile.php?id='.$v;
				$title = pun_htmlspecialchars($k);
				if ($pun_config['o_avatars'] == '1' && $pun_user['show_avatars'] != '0' &&
				    ($user_badge = lookup_file($pun_config['o_badges_dir'], $v, $pun_config['file_image_ext'])) !== null &&
				    ($img_size = @getimagesize(PUN_ROOT.$user_badge)))
					$user_badges[] = '<a href="'.$link.'" title="'.$title.'"><img src="'.$base_url.'/'.$user_badge.'" '.$img_size[3].' alt="'.pun_htmlspecialchars($k).'" /></a>';
				else
					$user_badges[] = '[<a href="'.$link.'" title="'.$title.'">'.pun_htmlspecialchars($k).'</a>]';
			}
			$user_badges = implode('<br />', $user_badges);
		}

		// We only show location, register date, post count and the contact links if "Show user info" is enabled
		if ($pun_config['o_show_user_info'] == '1')
		{
			if ($user['location'] != '')
			{
				if ($pun_config['o_censoring'] == '1')
					$user['location'] = censor_words($user['location']);

				$user_info[] = '<dd>'.$lang_topic['From'].': '.pun_htmlspecialchars($user['location']);
			}

			$user_info[] = '<dd>'.$lang_common['Registered'].': '.format_time($user['registered'], true);

			if ($pun_config['o_show_post_count'] == '1' || $pun_user['g_id'] < PUN_GUEST)
				$user_info[] = '<dd>'.(($user['is_team']=='1')? $lang_common['Members'] : $lang_common['Posts']).': '.$user['num_posts'];

			// Now let's deal with the contact links (E-mail and URL)
			if (($user['email_setting'] == '0' && !$pun_user['is_guest']) || $pun_user['g_id'] < PUN_GUEST)
				$user_contacts[] = '<a href="mailto:'.$user['email'].'">'.$lang_common['E-mail'].'</a>';
			else if ($user['email_setting'] == '1' && !$pun_user['is_guest'])
				$user_contacts[] = '<a href="misc.php?email='.$user_id.'">'.$lang_common['E-mail'].'</a>';

			if ($user['url'] != '')
				$user_contacts[] = '<a href="'.pun_htmlspecialchars($user['url']).'">'.$lang_topic['Website'].'</a>';
		}

		if ($pun_user['g_id'] < PUN_GUEST)
		{
			$user_info[] = '<dd>IP: <a href="moderate.php?get_host='.$poster_ip.'">'.$poster_ip.'</a>';

			if ($user['admin_note'] != '')
				$user_info[] = '<dd>'.$lang_topic['Note'].': <strong>'.pun_htmlspecialchars($user['admin_note']).'</strong>';
		}
	}
	// If the poster is a guest (or a user that has been deleted)
	else
	{
		$username = pun_htmlspecialchars($user['username']);
		$user_title = get_title($user);

		if ($pun_user['g_id'] < PUN_GUEST)
			$user_info[] = '<dd>IP: <a href="moderate.php?get_host='.$poster_ip.'">'.$poster_ip.'</a>';

		if ($pun_config['o_show_user_info'] == '1' && $user['poster_email'] != '' && !$pun_user['is_guest'])
			$user_contacts[] = '<a href="mailto:'.$user['poster_email'].'">'.$lang_common['E-mail'].'</a>';
	}

	return array($username, $user_title, $is_online, $user_avatar, $user_badges, $user_info, $user_contacts);
}


//
// Look for filename
//
function lookup_file($dir, $name, $extensions)
{
	if (strpos($extensions, ',') !== false)
	{
		$extensions = explode(',', $extensions);
		$extensions = array_map('trim', $extensions);
	}
	else
		$extensions = array(trim($extensions));

	foreach ($extensions as $ext)
	{
		if (is_file(PUN_ROOT.$dir.'/'.$name.'.'.$ext))
			return $dir.'/'.$name.'.'.$ext;
	}

	return null;
}
