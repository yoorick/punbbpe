<?php

if (!defined('TOP_NUM'))
	define('TOP_NUM', 5);

// list parameters
if (!isset($kind))
{
	$filter = PUN_KIND_FORUM;
}
else
	$filter = $kind;

?>
<div id="recentonforum" class="block">
	<h2><span><span class="conr hot"><?php echo $lang_common['TOP'].TOP_NUM ?></span><?php echo $lang_common['Boards kind'][$filter] ?></span></h2>
	<div class="box">
		<div class="inbox">
<?php

if ($pun_user['g_read_board'] == '1')
{

?>
		<ul class="primary">

		<li><strong><?php echo $lang_index['Recent posts'] ?></strong><ul class="secondary">
<?php

$result = $db->query('SELECT t.id, t.subject, t.last_post, t.last_poster, t.last_post_id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id INNER JOIN '.$db->prefix.'categories AS c ON (f.cat_id=c.id) AND (c.kind='.$filter.') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.moved_to IS NULL AND t.num_replies>0 ORDER BY t.last_post DESC LIMIT 0,'.TOP_NUM) or error('Unable to fetch recent post list', __FILE__, __LINE__, $db->error());
while ($row = $db->fetch_assoc($result))
{
	$post = '<a href="'.$base_url.'/viewtopic.php?pid='.$row['last_post_id'].'#p'.$row['last_post_id'].'" title="'.format_time($row['last_post']).'">'.pun_htmlspecialchars($row['subject']).'</a>';
	echo "\t\t\t\t".'<li><span class="user">'.pun_htmlspecialchars($row['last_poster']).'</span>: '.$post.'</li>'."\n";
}

?>
		</ul></li>
		<li><hr /></li>
		<li><strong><?php echo $lang_index['Most viewed topics'] ?></strong><ul class="secondary">
<?php

$result = $db->query('SELECT t.id, t.subject, t.num_views FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id INNER JOIN '.$db->prefix.'categories AS c ON (f.cat_id=c.id) AND (c.kind='.$filter.') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.moved_to IS NULL ORDER BY t.num_views DESC LIMIT 0,'.TOP_NUM) or error('Unable to fetch recent post list', __FILE__, __LINE__, $db->error());
while ($row = $db->fetch_assoc($result))
{
	$post = '<a href="'.$base_url.'/viewtopic.php?id='.$row['id'].'" title="'.$lang_common['Views'].': '.$row['num_views'].'">'.pun_htmlspecialchars($row['subject']).'</a>';
	echo "\t\t\t\t".'<li>'.$post.'</li>'."\n";
}

?>
		</ul></li>

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
