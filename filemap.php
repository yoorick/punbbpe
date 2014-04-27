<?php
/***********************************************************************

  Show list of attachments with its containing topics and bords.
  This file is part of Elektra File Upload mod for PunBB.

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2007       artoodetoo (master@punbb-pe.org.ru)

************************************************************************/


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

define('PUN_FILEMAP', 1);

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
if ($fid < 0)
	message($lang_common['Bad request']);

$cid = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
if ($cid < 0)
	message($lang_common['Bad request']);

$cond = array();

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id < 0)
	message($lang_common['Bad request']);

// Load the language files
require PUN_ROOT.'lang/'.$pun_user['language'].'/board.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/fileup.php';


// Fetch some info about the board(s)
if ($user_id)
{
	$cond[] = '(a.poster_id='.$user_id.')';

	$result = $db->query('SELECT u.username '.
		'FROM '.$db->prefix.'users AS u '.
		'WHERE u.id='.$user_id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$user = $db->fetch_assoc($result);
}

// Fetch some info about the board(s)
if ($fid)
{
	$result = $db->query('SELECT c.id AS cid, c.cat_name, c.kind, f.id AS fid, f.owner_id, f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics '.
		'FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') '.
		'INNER JOIN '.$db->prefix.'categories AS c ON f.cat_id=c.id '.
		'WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fid) or error('Unable to fetch board info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$cur_board = $db->fetch_assoc($result);
	$kind = $cur_board['kind'];
	$cond[] = '(t.forum_id='.$fid.')';

	// Is this a redirect board? In that case, redirect!
	if ($cur_board['redirect_url'] != '')
	{
		header('Location: '.$cur_board['redirect_url']);
		exit;
	}

}
else
{
	$result = $db->query('SELECT c.id AS cid, c.cat_name, c.kind, f.id AS fid, f.owner_id, f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics '.
		'FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') '.
		'INNER JOIN '.$db->prefix.'categories AS c ON f.cat_id=c.id '.
		'WHERE (f.redirect_url IS NULL) AND (fp.read_forum IS NULL OR fp.read_forum=1)'.((!empty($cid))? (' AND (c.id='.$cid.')') : ''))
		or error('Unable to fetch forums info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['No view']);

	$fids = array();
	$forums = array();
	while ($cur_board = $db->fetch_assoc($result))
	{
		$fids[] = $cur_board['fid'];
		$forums[$cur_board['fid']] = $cur_board;
	}
	if (count($fids) == 0)
		message($lang_common['No view']);

	$cond[] = '(t.forum_id IN ('.implode(',',$fids).'))';
}

$cond[] = '(t.moved_to IS NULL)';
$cond = 'WHERE '.implode(' AND ', $cond);

$result = $db->query('SELECT count(*)
	    FROM
	    '.$db->prefix.'attachments AS a INNER JOIN
	    '.$db->prefix.'topics AS t ON a.topic_id=t.id INNER JOIN
	    '.$db->prefix.'forums AS f ON f.id = t.forum_id
	    '.$cond) or
	    error('Unable to fetch attachments count', __FILE__, __LINE__, $db->error());
$num_rows = $db->fetch_row($result);
$num_rows = $num_rows[0];

// Determine the topic offset (based on $_GET['p'])
$num_pages = ceil($num_rows / $pun_user['disp_topics']);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = $pun_user['disp_topics'] * ($p - 1);

// Generate paging links
$paging_links = $lang_common['Pages'].': '.paginate($num_pages, $p, 'filemap.php?fid='.$fid.'&amp;user_id='.$user_id);

// Can we or can we not upload new file?
$post_link = ''; // temporary

$page_title = pun_htmlspecialchars($pun_config['o_board_title'].' / '.$lang_common['File map']);
define('PUN_ALLOW_INDEX', 1);
require PUN_ROOT.'include/header.php';

?>
<div class="linkst">
	<div class="inbox">
		<p class="pagelink conl"><?php echo $paging_links ?></p><?php echo $post_link ?>
		<ul><li><a href="<?php echo $base_url ?>/index.php"><?php echo $lang_common['Index'] ?></a>&nbsp;</li><li>&raquo;&nbsp;<?php echo $lang_common['File map'] ?></li></ul>
		<div class="clearer"></div>
	</div>
</div>

<div id="filelist" class="blocktable">
<?php

// loop through atachments
$result = $db->query('SELECT f.cat_id AS cid, c.cat_name,
    t.forum_id AS fid, f.forum_name, f.moderators,
    t.id AS tid, t.subject, t.poster, t.posted,
    a.id AS aid, a.mime, a.uploaded, a.image_dim, a.filename, a.downloads, a.location, a.size, a.post_id AS pid
    FROM
    '.$db->prefix.'attachments AS a INNER JOIN
    '.$db->prefix.'topics AS t ON a.topic_id=t.id INNER JOIN
    '.$db->prefix.'forums AS f ON f.id = t.forum_id INNER JOIN
    '.$db->prefix.'categories AS c ON f.cat_id = c.id
    '.$cond.'
    ORDER BY c.disp_position, f.disp_position, f.cat_id, t.forum_id, t.posted DESC, a.filename LIMIT '.$start_from.','.$pun_user['disp_topics']) or
    error('Unable to fetch attachment list', __FILE__, __LINE__, $db->error());

// If there are attachments.
if ($db->num_rows($result))
{
	$board_id = 0;
	$topic_id = 0;

	while ($row = $db->fetch_assoc($result))
	{
		if ($board_id != $row['fid'])
		{
			if (!$fid)
				$cur_board = $forums[$row['fid']];

/*
			// Sort out who the moderators are and if we are currently a moderator (or an admin)
			$mods_array = array();
			if ($cur_board['moderators'] != '')
				$mods_array = unserialize($cur_board['moderators']);

			$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_id'] == PUN_MOD && array_key_exists($pun_user['username'], $mods_array))) ? true : false;
*/
			// it is not first block, so close previous
			if ($board_id)
			{

?>
			</tbody>
			</table>
		</div>
	</div>
<?php
			}
			$board_id = $cur_board['fid'];

?>

	<h2><span><span class="conr"><a href="<?php echo $base_url.'/post.php?sendfile='.$cur_board['fid'].'">'.$lang_fu['Send file'] ?></a>&nbsp;</span>
		<strong><?php echo '<a href="'.$base_url.'/'.basename($kinds[$cur_board['kind']]).(empty($cur_board['owner_id'])?'':('?user_id='.$cur_board['owner_id'])).'#cat'.$cur_board['cid'].'">'.pun_htmlspecialchars($cur_board['cat_name']).'</a>&nbsp;&raquo;&nbsp;<a href="'.$base_url.'/viewboard.php?id='.$cur_board['fid'].'">'.pun_htmlspecialchars($cur_board['forum_name']).'</a>' ?></strong></span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_fu['File'] ?></th>
					<th class="tc2" scope="col"><?php echo $lang_fu['Size'] ?></th>
					<th class="tc3" scope="col"><?php echo $lang_fu['Mime'] ?></th>
					<th class="tc3" scope="col"><?php echo $lang_fu['Downloads'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_fu['Uploaded'] ?></th>
				</tr>
			</thead>
			<tbody>

<?php
			$bg_switch = true;
		}
		if ($topic_id != $row['tid'])
		{
			$topic_id = $row['tid'];
			$vtbg = ($bg_switch) ? 'rowodd' : 'roweven';
?>
				<tr class="<?php echo $vtbg ?>">
					<td colspan="5" class="tcl"><strong><?php echo $row['poster'] ?></strong>: <a href="<?php echo $base_url.'/viewtopic.php?id='.$row['tid'].'">'.pun_htmlspecialchars($row['subject']) ?></a></td>
				</tr>
<?php
//			$bg_switch = !$bg_switch;
		}
		$file = pun_htmlspecialchars($row['filename']);
		$file = '<a class="att_item" href="'.$base_url.'/download.php?aid='.$row['aid'].'">'.$file.'</a>';
		$post = format_time($row['uploaded']);
		$post = '<a href="'.$base_url.'/viewtopic.php?pid='.$row['pid'].'#p'.$row['pid'].'">'.$post.'</a>';
		$size = ($row['size']>=1048576)? (round($row['size']/1048576,0).'m'): (round($row['size']/1024,0).'k');

		$vtbg = ($bg_switch) ? 'rowodd' : 'roweven';
?>
				<tr class="<?php echo $vtbg ?>">
					<td class="tcl"><?php echo $file ?></td>
					<td class="tc2"><?php echo $size ?></td>
					<td class="tc3"><?php echo pun_htmlspecialchars($row['mime']) ?></td>
					<td class="tc3"><?php echo $row['downloads'] ?></td>
					<td class="tcr"><?php echo $post ?></td>
				</tr>
<?php

//		$bg_switch = !$bg_switch;
	}
?>
			</tbody>
			</table>
		</div>
	</div>
<?php
}
else
{

?>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr><th class="tc2"><?php echo $lang_fu['No files'] ?></th></tr>
			</thead>
			</table>
		</div>
	</div>
<?php

}

?>

</div>

<div class="linksb">
	<div class="inbox">
		<p class="pagelink conl"><?php echo $paging_links ?></p><?php echo $post_link ?>
		<ul><li><a href="<?php echo $base_url ?>/index.php"><?php echo $lang_common['Index'] ?></a>&nbsp;</li><li>&raquo;&nbsp;<?php echo $lang_common['File map'] ?></li></ul>
		<div class="clearer"></div>
	</div>
</div>

<?php

// START SUBST - <pun_info>
ob_start();

?>
<div id="announce" class="block">
	<h2><span><?php echo $lang_fu['Filemap info'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<div>
<?php
echo "\t\t\t\t".sprintf($lang_fu['Filemap stat'], $num_rows);
if ($user_id)
{
	$temp = pun_htmlspecialchars($user['username']);
	$temp = '<a href="'.$base_url.'/profile.php?id='.$user_id.'" class="user">'.$temp.'</a>';
	echo sprintf($lang_fu['by user'], $temp);
}
if ($fid)
{
	$temp = pun_htmlspecialchars($cur_board['forum_name']);
	$temp = '<a href="'.$base_url.'/viewboard.php?id='.$fid.'">'.$temp.'</a>';
	echo sprintf($lang_fu['into board'], $temp);
}

?>
			</div>
		</div>
	</div>
</div>
<?php

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<pun_info>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <pun_info>


//$footer_style = 'index';
require PUN_ROOT.'include/footer.php';

