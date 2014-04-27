<?php

if (!defined('TOP_NUM'))
	define('TOP_NUM', 5);

// list parameters
$filter = PUN_KIND_GALLERY;

$topics = array();
$pids = array();
$attachments = array();

require_once PUN_ROOT.'include/file_upload.php';

?>
<div id="recentongallery" class="block">
	<h2><span><span class="conr hot"><?php echo $lang_common['NEW'].TOP_NUM ?></span><?php echo $lang_common['Boards kind'][$filter] ?></span></h2>
	<div class="box">
		<div class="inbox">
<?php

if ($pun_user['g_read_board'] == '1')
{

?>
		<ul class="secondary">
<?php

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);

// fetch topics
$result = $db->query('SELECT t.id AS tid, p.id AS pid, t.subject, t.posted, t.poster FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'posts AS p ON (p.topic_id=t.id) AND (p.posted=t.posted) INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id INNER JOIN '.$db->prefix.'categories AS c ON (f.cat_id=c.id) AND (c.kind='.$filter.') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.moved_to IS NULL ORDER BY t.posted DESC LIMIT 0,'.TOP_NUM) or error('Unable to fetch recent topic list', __FILE__, __LINE__, $db->error());
while ($topic = $db->fetch_assoc($result))
{
	$topics[] = $topic;
	$pids[] = $topic['pid'];
}

// fetsh attachments
if (count($pids))
{
	$result = $db->query('SELECT * FROM '.$db->prefix.'attachments WHERE post_id IN ('.implode(',',$pids).') ORDER BY uploaded ASC') or error('Unable to fetch attachment list', __FILE__, __LINE__, $db->error());
	while ($attachment = $db->fetch_assoc($result)) $attachments[] = $attachment;
}

// Save id & location of images in topics
for($i=0; $i<count($topics); $i++)
{
	foreach ($attachments as $attachment)
	{
		if ((preg_match('#^image/(.*)$#i', $attachment['mime'])) && ($topics[$i]['pid'] == $attachment['post_id']))
		{
			$topics[$i]['aid'] = $attachment['id'];
			$topics[$i]['location'] = $attachment['location'];
			break;
		}
	}
}
unset($attachments);

// print topics with thumbnails
foreach ($topics as $topic)
{
	$post = '<a href="'.$base_url.'/viewtopic.php?id='.$topic['tid'].'" >'.pun_htmlspecialchars($topic['subject']).'</a>';
	$image = '<a href="'.$base_url.'/viewtopic.php?id='.$topic['tid'].'#image" >'.
		'<img src="'.$base_url.'/'.require_thumb($topic['aid'], $topic['location'], $pun_config['file_thumb_width'], $pun_config['file_thumb_height'], true).'" alt="'.pun_htmlspecialchars($topic['subject']).'" /></a>';

	echo "\t\t\t\t".'<li>'.format_time($topic['posted'],true).' <span class="byuser">'.$lang_common['by'].'&nbsp;'.pun_htmlspecialchars($topic['poster']).'</span>';
	echo "\t\t\t\t".'<br />'.$image."\n";
	echo "\t\t\t\t".'<br />'.$post.'</li>'."\n";
}

?>
		</ul>
<?php
}
else
{
	echo "\t\t\t\t".$lang_common['No view']."\n";
}
?>
		</div>
	</div>
</div>

<?php
