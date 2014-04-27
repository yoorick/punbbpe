<?php

require_once PUN_ROOT.'lang/'.$pun_user['language'].'/board.php';
require_once PUN_ROOT.'include/parser.php';

$prev_kind = (isset($kind))? $kind : null;
$kind = PUN_KIND_BLOG;
$kind_script = $kinds[$kind];

if ($pun_user['g_read_board'] == '0')
{

?>
<div class="block">
	<h2><span><?php echo $lang_common['Boards kind'][$kind].' - '.$lang_index['Recent topics'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<?php echo $lang_common['No view'] ?>
		</div>
	</div>
</div>

<?php

}
else
{

$topic_to_display = '10';

// Initialization
$num_topics = 0;
$fids = $forums = $topics = $tids = $posts = $pids = array();

$bg_switch = true;	// Used for switching background color in topics
$topic_count = 0;	// Keep track of topic numbers

// Fetch list of available boards
$result = $db->query('SELECT f.id, f.forum_name '.
  'FROM '.$db->prefix.'forums AS f '.
  'INNER JOIN '.$db->prefix.'categories AS c ON c.id=f.cat_id '.
  'LEFT JOIN '. $db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') '.
  'WHERE (c.kind='.$kind.') AND '.
  '(fp.read_forum IS NULL OR fp.read_forum=1)')
  or error('Unable to fetch board list', __FILE__, __LINE__, $db->error());

while ($cur_board = $db->fetch_assoc($result))
{
	$forums[$cur_board['id']] = $cur_board['forum_name'];
	$fids[] = $cur_board['id'];
}

if (count($fids))
{
	// Fetch list of topics to display on this page
	if ($pun_user['is_guest'] || $pun_config['o_show_dot'] == '0')
	{
		// Without "the dot"
		$sql = 'SELECT forum_id, id, poster, subject, topic_desc, posted, last_post, last_post_id, last_poster, num_views, num_replies, labels, closed, sticky, moved_to FROM '.$db->prefix.'topics WHERE (forum_id IN('.implode(',',$fids).')) AND (moved_to IS NULL) ORDER BY sticky DESC, posted DESC LIMIT '.$topic_to_display;
	}
	else
	{
		// With "the dot"
		switch ($db_type)
		{
			case 'mysql':
			case 'mysqli':
				$sql = 'SELECT p.poster_id AS has_posted, t.forum_id, t.id, t.subject, t.topic_desc, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.labels, t.closed, t.sticky, t.moved_to FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'posts AS p ON t.id=p.topic_id AND p.poster_id='.$pun_user['id'].' WHERE (t.forum_id IN('.implode(',',$fids).')) AND (t.moved_to IS NULL) GROUP BY t.id ORDER BY sticky DESC, posted DESC LIMIT '.$topic_to_display;
				break;

			case 'sqlite':
				$sql = 'SELECT p.poster_id AS has_posted, t.forum_id, t.id, t.subject, t.topic_desc, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.labels, t.closed, t.sticky, t.moved_to FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'posts AS p ON t.id=p.topic_id AND p.poster_id='.$pun_user['id'].' WHERE t.id IN(SELECT id FROM '.$db->prefix.'topics WHERE (forum_id IN('.implode(',',$fids).')) AND (moved_to IS NULL) ORDER BY sticky DESC, posted DESC LIMIT '.$topic_to_display.') GROUP BY t.id ORDER BY t.sticky DESC, t.last_post DESC';
				break;

			default:
				$sql = 'SELECT p.poster_id AS has_posted, t.forum_id, t.id, t.subject, t.topic_desc, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.labels, t.closed, t.sticky, t.moved_to FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'posts AS p ON t.id=p.topic_id AND p.poster_id='.$pun_user['id'].' WHERE (t.forum_id IN('.implode(',',$fids).')) AND (t.moved_to IS NULL) GROUP BY t.id, t.subject, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, p.poster_id ORDER BY sticky DESC, posted DESC LIMIT '.$topic_to_display;
				break;

		}
	}

	$result = $db->query($sql) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$num_topics = $db->num_rows($result);
	unset($fids);
}


if ($num_topics)
{

	// fetch all the topics and collect ids
	while ($cur_topic = $db->fetch_assoc($result))
	{
		$cur_topic['forum_name'] = $forums[$cur_topic['forum_id']];
		$topics[] = $cur_topic;
		$tids[] = $cur_topic['id'];
	}
	$db->free_result($result);
	unset($forums);

	// fetch start posts from topics
	if ($kind == PUN_KIND_BLOG)
	{
		$result_posts = $db->query('SELECT p.topic_id, p.id, p.message, p.poster_id, p.hide_smilies FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON p.topic_id=t.id AND p.posted=t.posted WHERE topic_id IN ('.implode(',',$tids).')') or error('Unable to fetch first posts for topics', __FILE__, __LINE__, $db->error());
		while ($cur_post = $db->fetch_assoc($result_posts))
		{
			$posts[$cur_post['topic_id']] = $cur_post;
			$pids[] = $cur_post['id'];
		}
	}

}

?>

<div class="blocktable">
	<h2><span><?php echo $lang_common['Boards kind'][$kind].' - '.$lang_index['Recent topics'] ?></span></h2>
	<div class="box">
		<div class="inbox">

<?php
foreach($topics as $cur_topic)
{
		$topic_count++;

		// Switch the background color for every topic.
		$bg_switch = ($bg_switch) ? $bg_switch = false : $bg_switch = true;
		$vtbg = ($bg_switch) ? ' roweven' : ' rowodd';

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

		$labels = (!empty($pun_config['o_topic_labels']))? $cur_topic['labels']: '';

		// use kind-dependant view as occasion serves
		if (is_file(PUN_ROOT.'include/board/row_'.$kind_script))
			require PUN_ROOT.'include/board/row_'.$kind_script;
		else
			require PUN_ROOT.'include/board/row_forums.php';
}
?>
		</div>
	</div>
</div>

<?php

// Squeeze the garbage
if (!isset($prev_kind))
	unset($kind);

}