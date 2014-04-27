<?php
/***********************************************************************

  Show list of favorite topics for user.
  This file is part of PunBB Power Edition.

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2007       artoodetoo (master@punbb-pe.org.ru)

************************************************************************/


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

define('PUN_FAVES', 1);

// Load language files
require PUN_ROOT.'lang/'.$pun_user['language'].'/index.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/search.php';

define('PUN_ALLOW_INDEX', 0);

$person = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($person < 2)
	message($lang_common['Bad request']);

$result = $db->query('SELECT u.id, u.registration_ip, u.username, u.is_team, u.teams, u.email, u.title, u.realname, u.url, u.gender, u.birthday, u.hide_age, u.interests, u.aboutme, u.jabber, u.icq, u.msn, u.aim, u.yahoo, u.location, u.signature, u.disp_topics, u.disp_posts, u.email_setting, u.save_pass, u.notify_with_post, u.show_smilies, u.show_img, u.show_img_sig, u.show_avatars, u.show_sig, u.timezone, u.time_format, u.date_format, u.language, u.style, u.num_posts, u.num_files, u.file_bonus, u.last_post, u.registered, u.registration_ip, u.admin_note, g.g_id, g.g_user_title FROM '.$db->prefix.'users AS u LEFT JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id WHERE u.id='.$person) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$user = $db->fetch_assoc($result);

// Use THIS user style for this page
if ($pun_user['id'] != $person)
	$pun_user['style'] = $user['style'];

$page_title = pun_htmlspecialchars($pun_config['o_board_title']) . ' / '. pun_htmlspecialchars($user['username']) . ' - ' . $lang_common['Favorites'];

// Build context menu
$context_menu[] = '<a href="'.$base_url.'/extern.php?action=active&amp;subscriber='.$person.'&amp;type=RSS">RSS</a>';

// Fetch subscriptions for person
$result = $db->query('SELECT t.id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$person.') INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1)') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
$num_hits = $db->num_rows($result);

// Determine the post offset (based on $_GET['p'])
$num_pages = ceil(($num_hits) / $pun_user['disp_topics']);
$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : $_GET['p'];
$start_from = $pun_user['disp_posts'] * ($p - 1);

// Generate bread crumbs
$paging_links = $lang_common['Pages'].': '.paginate($num_pages, $p, $base_url.'/favorites.php?user_id='.$person);
$bread_crumbs = '<ul><li><a href="'.$base_url.'/profile.php?id='.$person.'">'.(($person==$pun_user['id'])?$lang_common['Yours'] : pun_htmlspecialchars($user['username'])).'</a></li><li>&nbsp;&raquo;&nbsp;'.$lang_common['Favorites'].'</li></ul>';
$post_link = '';

$search_ids = $topics = $has_posted = array();

if ($num_hits)
{
	while ($row = $db->fetch_row($result))
		$search_ids[] = $row[0];
	$db->free_result($result);
	$search_results = implode(',', $search_ids);

	$sort_by_sql = 'c.kind ASC, c.disp_position ASC, f.disp_position ASC, t.last_post DESC ';
	$result = $db->query('SELECT '.
			't.id, t.poster, t.subject, t.topic_desc, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.forum_id, '.
			'f.forum_name, f.owner_id, f.cat_id AS cid, c.kind, c.cat_name '.
		'FROM '.
			$db->prefix.'topics AS t INNER JOIN '.
			$db->prefix.'forums AS f ON f.id=t.forum_id INNER JOIN '.
			$db->prefix.'categories AS c ON c.id=f.cat_id '.
		'WHERE t.id IN('.$search_results.') ORDER BY '.$sort_by_sql.
		'LIMIT '.$start_from.', '.$pun_user['disp_topics']
		) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());

	// Fetch all topics for page and get ids again (for page only)
	while ($cur_topic = $db->fetch_assoc($result))
	{
		$topics[] = $cur_topic;
		$search_ids[] = $cur_topic['id'];
	}
	$db->free_result($result);

	// Should we display the dot or not?
	if (!$pun_user['is_guest'] && $pun_config['o_show_dot'] == '1')
	{
		// Collect subset of topic ids where user has posted.
		$result = $db->query('SELECT topic_id FROM '.$db->prefix.'posts WHERE topic_id IN('.implode(',',$search_ids).') AND poster_id='.$pun_user['id'].' GROUP BY topic_id') or error('Unable to fetch has_posted', __FILE__, __LINE__, $db->error());
		while ($row = $db->fetch_row($result))
			$has_posted[] = $row[0];

		$db->free_result($result);
	}

}

require PUN_ROOT.'include/header.php';


// Show info on your own faves and on first page only
if (($person == $pun_user['id']) && ($p == 1))
{
?>
<div id="favinfo" class="block">
	<h2><span></span><?php echo $lang_common['Info'] ?></h2>
	<div class="box">
		<div class="inbox">
			<p><?php echo sprintf($lang_profile['Favorites info'], $base_url.'/profile.php?section=privacy&amp;id='.$person) ?></p>
		</div>
	</div>
</div>

<?php
}

$cat_kind = -1;
$board_id = -1;
$bg_switch = true;	// Used for switching background color in topics

foreach ($topics as $cur_topic)
{

	// Switch the background color for every topic.
	$bg_switch = ($bg_switch) ? $bg_switch = false : $bg_switch = true;
	$vtbg = ($bg_switch) ? ' roweven' : ' rowodd';

	$cur_board = array(
		'cid' => $cur_topic['cid'],
		'cat_name' => $cur_topic['cat_name'],
		'kind' => $cur_topic['kind'],
		'id' => $cur_topic['forum_id'],
		'owner_id' => $cur_topic['owner_id'],
		'forum_name' => $cur_topic['forum_name']);

	if ($cur_board['kind'] != $cat_kind)	// A new category since last iteration?
	{
		if ($cat_kind != -1)
			echo "\t\t\t".'</tbody>'."\n\t\t\t".'</table>'."\n\t\t".'</div>'."\n\t".'</div>'."\n".'</div>'."\n\n";
		$cat_kind = $cur_board['kind'];
?>
<div class="blocktable">
	<h2><span><?php echo $lang_common['Boards kind'][$cat_kind] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_common['Board'] . ' / ' .$lang_common['Topic']; ?></th>
					<th class="tc2" scope="col"><?php echo $lang_common['Replies'] ?></th>
					<th class="tc3" scope="col"><?php echo $lang_common['Views'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php
	}

	if ($cur_board['id'] != $board_id)	// A new board since last iteration?
	{
		$board_id = $cur_board['id'];
		echo "\t\t\t\t".
			'<tr class="'.trim($vtbg).'">'."\n\t\t\t\t\t".'<td class="tcl" colspan="4"><strong>'.
			'<a href="'.$base_url.'/'.basename($kinds[$cur_board['kind']]).(empty($cur_board['owner_id'])?'':('?user_id='.$cur_board['owner_id'])).'#cat'.$cur_board['cid'].'">'.pun_htmlspecialchars($cur_board['cat_name']).'</a>'.
			'&nbsp;&raquo;&nbsp;<a href="'.$base_url.'/viewboard.php?id='.$cur_board['id'].'">'.pun_htmlspecialchars($cur_board['forum_name']).'</a>'.
			'</strong></td>'."\n\t\t\t\t".
			'</tr>'."\n";
	}

	$icon_text = $lang_common['Normal icon'];
	$item_status = '';
	$icon_type = 'icon';

	$last_post = '<a href="viewtopic.php?pid='.$cur_topic['last_post_id'].'#p'.$cur_topic['last_post_id'].'">'.format_time($cur_topic['last_post']).'</a> <span class="byuser">'.$lang_common['by'].'&nbsp;'.pun_htmlspecialchars($cur_topic['last_poster']).'</span>';

	if ($pun_config['o_censoring'] == '1')
		$cur_topic['subject'] = censor_words($cur_topic['subject']);

	if ($cur_topic['closed'] == '0')
		$subject = '<a href="viewtopic.php?id='.$cur_topic['id'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a>';
	else
	{
		$subject = '<a href="viewtopic.php?id='.$cur_topic['id'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a>';
		$icon_text = $lang_common['Closed icon'];
		$item_status = 'iclosed';
	}

	if (!$pun_user['is_guest'] && $cur_topic['last_post'] > $pun_user['last_visit'])
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
		if (in_array($cur_topic['id'], $has_posted))
			$subject = '<strong>&middot;</strong>&nbsp;'.$subject;
		else
			$subject = '&nbsp;&nbsp;'.$subject;
	}

	$num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $pun_user['disp_posts']);

	if ($num_pages_topic > 1)
		$subject_multipage = '[ '.paginate($num_pages_topic, -1, 'viewtopic.php?id='.$cur_topic['id']).' ]';
	else
		$subject_multipage = null;

	// Should we show the "New posts" and/or the multipage links?
	if (!empty($subject_new_posts) || !empty($subject_multipage))
	{
		$subject .= '&nbsp; '.(!empty($subject_new_posts) ? $subject_new_posts : '');
		$subject .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
	}

?>
				<tr class="<?php echo trim($item_status.$vtbg) ?>">
					<td class="tcl">
						<div class="intd">
							<div class="<?php echo $icon_type ?>"><div class="nosize"><?php echo trim($icon_text) ?></div></div>
							<div class="tclcon">
								<?php echo $subject.' <span class="byuser">'.$lang_common['by'].'&nbsp;'.pun_htmlspecialchars($cur_topic['poster']).'</span>'."\n" ?>
							</div>
						</div>
					</td>
					<td class="tc2"><?php echo $cur_topic['num_replies'] ?></td>
					<td class="tc3"><?php echo $cur_topic['num_views'] ?></td>
					<td class="tcr"><?php echo $last_post ?></td>
				</tr>
<?php
}

if (!$num_hits)
{

?>
<div id="msg" class="block">
	<div class="box">
		<div class="inbox">
			<p><?php echo $lang_search['No subscriptions'] ?></p>
		</div>
	</div>
</div>
<?php

}
else
{

?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<?php

}

require PUN_ROOT.'include/footer.php';
