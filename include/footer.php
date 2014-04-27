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


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// START SUBST - <pun_bottombreadcrumbs>
if (strpos($tpl_main, '<pun_bottombreadcrumbs>') !== false)
{
	if (isset($bread_crumbs))
	{
		ob_start();
?>
<div class="<?php if ($pun_page == 'viewtopic') echo 'post'; ?>linksb">
	<div class="inbox">
		<p class="pagelink conl"><?php echo $paging_links ?></p>
		<?php echo $post_link ?>
		<?php echo $bread_crumbs ?>
		<div class="clearer"></div>
	</div>
</div>
<?php
		$tpl_temp = trim(ob_get_contents());
		$tpl_main = str_replace('<pun_bottombreadcrumbs>', $tpl_temp, $tpl_main);
		ob_end_clean();
	}
	else
		$tpl_main = str_replace('<pun_bottombreadcrumbs>', '', $tpl_main);
}
// END SUBST - <pun_bottombreadcrumbs>


$tpl_content = trim(ob_get_contents());
ob_end_clean();

$tpl_main = str_replace('<pun_main>', $tpl_content, $tpl_main);
// END SUBST - <pun_main>


// START SUBST - <pun_footer>
ob_start();

// If no footer style has been specified, we use the default (only copyright/debug info)
$footer_style = isset($footer_style) ? $footer_style : NULL;

// START SUBST - <pun_stat>

if (strpos($tpl_main, '<pun_stat>') !== false)
{
	if ($footer_style == 'boards')
	{
		ob_start();
// Collect some statistics from the database
$result = $db->query('SELECT COUNT(id)-1 FROM '.$db->prefix.'users') or error('Unable to fetch total user count', __FILE__, __LINE__, $db->error());
$stats['total_users'] = $db->result($result);

$result = $db->query('SELECT id, username, is_team FROM '.$db->prefix.'users ORDER BY registered DESC LIMIT 1') or error('Unable to fetch newest registered user', __FILE__, __LINE__, $db->error());
$stats['last_user'] = $db->fetch_assoc($result);

$result = $db->query('SELECT SUM(num_topics), SUM(num_posts) FROM '.$db->prefix.'forums') or error('Unable to fetch topic/post count', __FILE__, __LINE__, $db->error());
list($stats['total_topics'], $stats['total_posts']) = $db->fetch_row($result);

?>
<div id="brdstats" class="block">
	<h2><span><?php echo $lang_index['Statistics'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<dl class="conr">
				<dt><strong><?php echo $lang_index['Statistics'] ?></strong></dt>
				<dd><?php echo $lang_index['No of users'].': <strong>'. $stats['total_users'] ?></strong></dd>
				<dd><?php echo $lang_index['No of topics'].': <strong>'.$stats['total_topics'] ?></strong></dd>
				<dd><?php echo $lang_index['No of posts'].': <strong>'.$stats['total_posts'] ?></strong></dd>
			</dl>
			<dl class="conl">
				<dt><strong><?php echo $lang_index['User info'] ?></strong></dt>
				<dd><?php echo $lang_index['Newest user'] ?>: <a href="<?php echo $base_url.'/profile.php?id='.$stats['last_user']['id'] ?>" class="<?php echo ($stats['last_user']['is_team']?'team':'user') ?>"><?php echo pun_htmlspecialchars($stats['last_user']['username']) ?></a></dd>
<?php

if ($pun_config['o_users_online'] == '1')
{
	// Fetch users online info and generate strings for output
	$num_guests = 0;
	$users = array();
	$result = $db->query('SELECT user_id, ident FROM '.$db->prefix.'online WHERE idle=0 ORDER BY ident', true) or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());

	while ($pun_user_online = $db->fetch_assoc($result))
	{
		if ($pun_user_online['user_id'] > 1)
			$users[] = "\n\t\t\t\t".'<dd><a href="'.$base_url.'/profile.php?id='.$pun_user_online['user_id'].'" class="user">'.pun_htmlspecialchars($pun_user_online['ident']).'</a>';
		else
			++$num_guests;
	}

	$num_users = count($users);

	// users online today

	$date = getdate(time());
	$todaystamp = mktime(0,0,0, $date['mon'], $date['mday'], $date['year']);
	// online information stored with server time, but we want to use a user time
	$diff = ($pun_user['timezone'] - $pun_config['o_server_timezone']) * 3600;
	$todaystamp -= $diff;

	$result = $db->query("SELECT username, id, last_visit from ".$db->prefix."users WHERE last_visit >= '".$todaystamp."' ORDER by last_visit DESC") or error("Unable to find the list of the users online today", __FILE__, __LINE__, $db->error());

	$users_today = array();
	while ($pun_user_online_today = $db->fetch_assoc($result))
		$users_today[] =  "\n\t\t\t\t".'<dd><a href="'.$base_url.'/profile.php?id='.$pun_user_online_today['id'].'" title="Last visit of '.$pun_user_online_today['username'].' : '.format_time($pun_user_online_today['last_visit']).'" class="user">'.$pun_user_online_today['username'].'</a>';

	$num_users_today = count($users_today);

	echo "\t\t\t\t".'<dd>'. $lang_index['Users online'].': <strong>'.$num_users.'</strong></dd>'."\n\t\t\t\t".'<dd>'.$lang_index['Users today'].': <strong>'.$num_users_today.'</strong></dd>'."\n\t\t\t\t".'<dd>'.$lang_index['Guests online'].': <strong>'.$num_guests.'</strong></dd>'."\n\t\t\t".'</dl>'."\n";

	if ($num_users > 0)
		echo "\t\t\t".'<dl id="onlinelist" class= "clearb">'."\n\t\t\t\t".'<dt><strong>'.$lang_index['Online'].':&nbsp;</strong></dt>'."\t\t\t\t".implode(',</dd> ', $users).'</dd>'."\n\t\t\t".'</dl>'."\n";
	else
		echo "\t\t\t".'<div class="clearer"></div>'."\n";

	echo "\t\t\t".'<dl id="onlinetodaylist">'."\n\t\t\t\t".'<dt><strong>'.$lang_index['Online today'].': </strong></dt>';

	if ($num_users_today > 0)
		echo implode(',</dd> ', $users_today).'</dd>'."\n\t\t\t".'</dl>'."\n";
	else
		echo '<dd><em></em></dd>'."\n\t\t\t".'</dl>'."\n";

	// users' birthday today
	// take care about user time offset to GMT
	$today = getdate(time() + $pun_user['timezone'] * 3600);
	$result = $db->query('SELECT username, id, YEAR(FROM_UNIXTIME(birthday)) AS year, hide_age from '.$db->prefix.'users WHERE birthday <> 0 AND DAYOFMONTH(FROM_UNIXTIME(birthday))=\''.$today['mday'].'\' AND MONTH(FROM_UNIXTIME(birthday))=\''.$today['mon'].'\' ORDER by username ASC') or error('Cannot retreive birthdays', __FILE__, __LINE__, $db->error());

	$birthdays_today = array();
	while ($row = $db->fetch_assoc($result))
		$birthdays_today[] =  "\n\t\t\t\t".'<dd><a href="'.$base_url.'/profile.php?id='.$row['id'].'">'.$row['username'].'</a>'.
		((!$row['hide_age'])?'('.($today['year']-$row['year']).')':'');

	if (count($birthdays_today) > 0)
	{
		echo "\t\t\t".'<dl id="birthdayslist">'."\n\t\t\t\t".'<dt><strong>' . $lang_index['Birthday today'] . ':&nbsp;</strong></dt>';
		echo implode(',</dd> ', $birthdays_today) . '</dd>' . "\n\t\t\t" . '</dl>' . "\n";
	}

}
else
	echo "\t\t".'</dl>'."\n\t\t\t".'<div class="clearer"></div>'."\n";


?>
		</div>
	</div>
</div>
<?php
		$tpl_temp = trim(ob_get_contents());
		$tpl_main = str_replace('<pun_stat>', $tpl_temp, $tpl_main);
		ob_end_clean();
	}
	else
		$tpl_main = str_replace('<pun_stat>', '', $tpl_main);
}
// END SUBST - <pun_stat>


?>
<div id="brdfooter" class="block">
	<h2><span><?php echo $lang_common['Board footer'] ?></span></h2>
	<div class="box">
		<div class="inbox">
<?php

if ($footer_style == 'index' || $footer_style == 'boards' || $footer_style == 'search')
{
	$kind_get = (isset($kind))? ('&amp;kind='.$kind) : '';

	if (!$pun_user['is_guest'])
	{
		echo "\n\t\t\t".'<dl id="searchlinks" class="conl">'."\n\t\t\t\t".'<dt><strong>'.$lang_common['Search links'].'</strong></dt>'."\n\t\t\t\t".'<dd><a href="'.$base_url.'/search.php?action=show_24h'.$kind_get.'">'.$lang_common['Show recent posts'].'</a></dd>'."\n";
		echo "\t\t\t\t".'<dd><a href="'.$base_url.'/search.php?action=show_unanswered'.$kind_get.'">'.$lang_common['Show unanswered posts'].'</a></dd>'."\n";

		if ($pun_config['o_subscriptions'] == '1')
			echo "\t\t\t\t".'<dd><a href="'.$base_url.'/favorites.php?user_id='.$pun_user['id'].'">'.$lang_common['Show subscriptions'].'</a></dd>'."\n";

		echo "\t\t\t\t".'<dd><a href="'.$base_url.'/search.php?action=show_user'.$kind_get.'&amp;user_id='.$pun_user['id'].'">'.$lang_common['Show your posts'].'</a></dd>'."\n\t\t\t".'</dl>'."\n";
	}
	else
	{
		if ($pun_user['g_search'] == '1')
		{
			echo "\n\t\t\t".'<dl id="searchlinks" class="conl">'."\n\t\t\t\t".'<dt><strong>'.$lang_common['Search links'].'</strong></dt><dd><a href="'.$base_url.'/search.php?action=show_24h'.$kind_get.'">'.$lang_common['Show recent posts'].'</a></dd>'."\n";
			echo "\t\t\t\t".'<dd><a href="'.$base_url.'/search.php?action=show_unanswered'.$kind_get.'">'.$lang_common['Show unanswered posts'].'</a></dd>'."\n\t\t\t".'</dl>'."\n";
		}
	}
}
else if ($footer_style == 'viewboard' || $footer_style == 'viewtopic')
{
	echo "\n\t\t\t".'<div class="conl">'."\n";

	// Display the "Jump to" drop list
	if ($pun_config['o_quickjump'] == '1')
	{
		// Load cached quickjump
		@include PUN_ROOT.'cache/cache_quickjump_'.$pun_user['g_id'].((isset($kind))? ('_'.$kind) : '').'.php';
		if (!defined('PUN_QJ_LOADED'))
		{
			require_once PUN_ROOT.'include/cache.php';
			generate_quickjump_cache($pun_user['g_id'], (isset($kind)? $kind : null));
			require PUN_ROOT.'cache/cache_quickjump_'.$pun_user['g_id'].((isset($kind))? ('_'.$kind) : '').'.php';
		}
	}

	$csrf_hash = csrf_hash();
	if ($footer_style == 'viewboard' && $is_admmod)
		echo "\t\t\t".'<p id="modcontrols"><a href="'.$base_url.'/moderate.php?fid='.$board_id.'&amp;p='.$p.'">'.$lang_common['Moderate board'].'</a></p>'."\n";
	else if ($footer_style == 'viewtopic' && $is_admmod)
	{
		echo "\t\t\t".'<dl id="modcontrols"><dt><strong>'.$lang_topic['Mod controls'].'</strong></dt>'."\n";
		echo "\t\t\t".'<dd><a href="'.$base_url.'/moderate.php?fid='.$board_id.'&amp;tid='.$id.'&amp;p='.$p.'&amp;csrf_hash='.$csrf_hash.'">'.$lang_common['Delete posts'].'</a></dd>'."\n";
		echo "\t\t\t".'<dd><a href="'.$base_url.'/moderate.php?fid='.$board_id.'&amp;move_topics='.$id.'&amp;csrf_hash='.$csrf_hash.'">'.$lang_common['Move topic'].'</a></dd>'."\n";

		if ($cur_topic['closed'] == '1')
			echo "\t\t\t".'<dd><a href="'.$base_url.'/moderate.php?fid='.$board_id.'&amp;open='.$id.'&amp;csrf_hash='.$csrf_hash.'">'.$lang_common['Open topic'].'</a></dd>'."\n";
		else
			echo "\t\t\t".'<dd><a href="'.$base_url.'/moderate.php?fid='.$board_id.'&amp;close='.$id.'&amp;csrf_hash='.$csrf_hash.'">'.$lang_common['Close topic'].'</a></dd>'."\n";

		if ($cur_topic['sticky'] == '1')
			echo "\t\t\t".'<dd><a href="'.$base_url.'/moderate.php?fid='.$board_id.'&amp;unstick='.$id.'&amp;csrf_hash='.$csrf_hash.'">'.$lang_common['Unstick topic'].'</a></dd></dl>'."\n";
		else
			echo "\t\t\t".'<dd><a href="'.$base_url.'/moderate.php?fid='.$board_id.'&amp;stick='.$id.'&amp;csrf_hash='.$csrf_hash.'">'.$lang_common['Stick topic'].'</a></dd></dl>'."\n";
	}

	echo "\t\t\t".'</div>'."\n";
}

?>
			<p class="conr">Powered by <a href="http://www.punbb.org/">PunBB</a> <?php if ($pun_config['o_show_version'] == '1') echo ' '.$pun_config['o_cur_version']; ?>+ <a href="http://punbb-pe.org.ru/">PE</a><br />
				<!-- &copy; Copyright 2002&#8211;2005 Rickard Andersson> --><br />
				<a href="http://validator.w3.org/check?uri=referer"><img src="<?php echo $base_url ?>/img/valid_xhtml_80x15.png" title="Valid XHTML" alt="Valid XHTML" /></a>
				<a href="http://jigsaw.w3.org/css-validator/check/referer"><img src="<?php echo $base_url ?>/img/valid_css_80x15.png" title="Valid CSS" alt="Valid CSS" /></a>
			</p>
<?php

// Display debug info (if enabled/defined)
if (defined('PUN_DEBUG'))
{
	// Calculate script generation time
	list($usec, $sec) = explode(' ', microtime());
	$time_diff = sprintf('%.3f', ((float)$usec + (float)$sec) - $pun_start);
	echo "\t\t\t".'<p class="conr">[ Generated in '.$time_diff.' seconds, '.$db->get_num_queries().' queries executed ]</p>'."\n";
}

?>
			<div class="clearer"></div>
		</div>
	</div>
</div>
<?php


// End the transaction
$db->end_transaction();

// Display executed queries (if enabled)
if (defined('PUN_SHOW_QUERIES'))
	display_saved_queries();

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<pun_footer>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <pun_footer>


// Close the db connection (and free up any result data)
$db->close();

// Spit out the page
exit($tpl_main);
