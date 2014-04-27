<div class="block">
	<h2><span><?php echo $lang_index['Statistics'] ?></span></h2>
	<div class="box">
		<div class="inbox">
<?php
// Collect some statistics from the database
$result = $db->query('SELECT COUNT(id)-1 FROM '.$db->prefix.'users') or error('Unable to fetch total user count', __FILE__, __LINE__, $db->error());
$stats['total_users'] = $db->result($result);

$result = $db->query('SELECT id, username, is_team FROM '.$db->prefix.'users ORDER BY registered DESC LIMIT 1') or error('Unable to fetch newest registered user', __FILE__, __LINE__, $db->error());
$stats['last_user'] = $db->fetch_assoc($result);

$result = $db->query('SELECT c.kind, SUM(f.num_topics), SUM(f.num_posts) FROM '.$db->prefix.'forums AS f INNER JOIN '.$db->prefix.'categories AS c ON c.id=f.cat_id GROUP BY c.kind') or error('Unable to fetch topic/post count', __FILE__, __LINE__, $db->error());
$num_topics_total = 0;
$num_posts_total = 0;
while (list($k,$num_topics,$num_posts) = $db->fetch_row($result))
{
	$stats[$k] = array('topics' => $num_topics, 'posts' => $num_posts);
	$num_topics_total += $num_topics;
	$num_posts_total += $num_posts;
}

?>
			<ul id="board-stat">
				<li><?php echo $lang_index['Topic num'].'/'.$lang_index['Post num'].': <strong>'.$num_topics_total.'/'.$num_posts_total.'</strong>' ?>
					<ul class="secondary">
<?php
foreach($kinds as $k => $v)
{
	if (isset($stats[$k]))
	{
		$num_topics = $stats[$k]['topics'];
		$num_posts = $stats[$k]['posts'];
	}
	else
	{
		$num_topics = 0;
		$num_posts = 0;
	}

	echo "\t\t\t\t\t".'<li>'.$lang_common['Boards kind'][$k].': '.$num_topics.'/'.$num_posts.'</li>'."\n";
}
?>
					</ul>
				</li>
				<li><?php echo $lang_index['Users registered']. ': <strong>'.$stats['total_users'] ?></strong>
				    <p><?php echo $lang_index['Newest'] ?>: <a href="<?php echo $base_url.'/profile.php?id='.$stats['last_user']['id'] ?>" class="<?php echo ($stats['last_user']['is_team']?'team':'user') ?>"><?php echo pun_htmlspecialchars($stats['last_user']['username']) ?></a></p></li>
			</ul>

<?php

if ($pun_config['o_users_online'] == '1')
{
?>
			<hr />
			<ul>
				<li><strong><?php echo $lang_index['Online info'] ?></strong>:</li>
<?php

	// Fetch users online info and generate strings for output
	$num_guests = 0;
	$users = array();
	$result = $db->query('SELECT o.user_id, o.ident FROM '.$db->prefix.'online AS o WHERE o.idle=0 ORDER BY o.ident', true) or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());

	while ($pun_user_online = $db->fetch_assoc($result))
	{
		if ($pun_user_online['user_id'] > 1)
			$users[] = '<a href="profile.php?id='.$pun_user_online['user_id'].'" class="user">'.pun_htmlspecialchars($pun_user_online['ident']).'</a>';
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

	$result = $db->query("SELECT username, is_team, id, last_visit from ".$db->prefix."users WHERE last_visit >= '".$todaystamp."' ORDER by last_visit DESC") or error("Unable to find the list of the users online today", __FILE__, __LINE__, $db->error());

	$users_today = array();
	while ($pun_user_online_today = $db->fetch_assoc($result))
		$users_today[] =  '<a href="profile.php?id='.$pun_user_online_today['id'].'" class="'.($pun_user_online_today['is_team']?'team':'user').'" title="Last visit of '.$pun_user_online_today['username'].' : '.format_time($pun_user_online_today['last_visit']).'">'.$pun_user_online_today['username'].'</a>';

	$num_users_today = count($users_today);

	echo "\t\t\t\t".'<li>'.$lang_index['Guests now'].': <strong>'.$num_guests.'</strong></li>';
	echo "\t\t\t\t".'<li>'.$lang_index['Users now'].': <strong>'.$num_users.'</strong></li>';
	if ($num_users > 0)
		echo "\n\t\t\t\t".'<li>'.implode(', ', $users).'</li>';
	echo "\n\t\t\t".'</ul>'."\n";

	echo "\t\t\t\t".'<ul>';
	echo "\n\t\t\t\t".'<li>'.$lang_index['Users today'].': <strong>'.$num_users_today.'</strong></li>';
	if ($num_users_today > 0)
		echo "\n\t\t\t\t".'<li>'.implode(', ', $users_today).'</li>';
	echo "\n\t\t\t".'</ul>'."\n";

	// users' birthday today
	// take care about user time offset to GMT
	$today = getdate(time() + $pun_user['timezone'] * 3600);
	$result = $db->query('SELECT username, id, YEAR(FROM_UNIXTIME(birthday)) AS year, hide_age from '.$db->prefix.'users WHERE birthday<> 0 AND DAYOFMONTH(FROM_UNIXTIME(birthday))=\''.$today['mday'].'\' AND MONTH(FROM_UNIXTIME(birthday))=\''.$today['mon'].'\' ORDER by username ASC') or error('Cannot retreive birthdays', __FILE__, __LINE__, $db->error());

	$birthdays_today = array();
	while ($row = $db->fetch_assoc($result))
		$birthdays_today[] =  "\n\t\t\t\t".'<a href="profile.php?id='.$row['id'].'" class="user">'.$row['username'].'</a>'.
		((!$row['hide_age'])?'('.($today['year']-$row['year']).')':'');

	if (count($birthdays_today) > 0)
	{
		echo "\t\t\t".'<ul>'."\n\t\t\t\t".'<li><strong>' . $lang_index['Birthday today'] . ':</strong></li>';
		echo "\n\t\t\t\t".'<li>'.implode(', ', $birthdays_today).'</li>'."\n\t\t\t".'</ul>'."\n";
	}

}

?>
		</div>
	</div>
</div>

<?php
