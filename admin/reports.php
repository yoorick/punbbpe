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


// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);

define('PUN_ROOT', '../');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';


if ($pun_user['g_id'] > PUN_MOD)
	message($lang_common['No permission']);


// Zap a report
if (isset($_POST['zap_id']))
{
	confirm_referrer('admin/reports.php');

	$zap_id = intval(key($_POST['zap_id']));

	$result = $db->query('SELECT zapped FROM '.$db->prefix.'reports WHERE id='.$zap_id) or error('Unable to fetch report info', __FILE__, __LINE__, $db->error());
	$zapped = $db->result($result);

	if ($zapped == '')
		$db->query('UPDATE '.$db->prefix.'reports SET zapped='.time().', zapped_by='.$pun_user['id'].' WHERE id='.$zap_id) or error('Unable to zap report', __FILE__, __LINE__, $db->error());

	redirect($base_url.'/admin/reports.php', 'Report zapped. Redirecting &hellip;');
}


$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / Admin / Reports';
require PUN_ROOT.'include/header.php';

// generate_admin_menu('reports');

?>
	<div class="blockform">
		<h2><span>New reports</span></h2>
		<div class="box">
			<form method="post" action="<?php echo $base_url ?>/admin/reports.php?action=zap">
<?php

// Fetch all new and upto 10 zapped reports
$reports = array();

$result = $db->query('SELECT r.id, r.report_type, r.post_id, r.topic_id, r.forum_id, r.reported_by, r.created, r.message FROM '.$db->prefix.'reports AS r WHERE r.zapped IS NULL ORDER BY created DESC') or error('Unable to fetch report list', __FILE__, __LINE__, $db->error());
$num_new = $db->num_rows($result);
while ($cur_report = $db->fetch_assoc($result)) $reports[] = $cur_report;

$result = $db->query('SELECT r.id, r.report_type, r.post_id, r.topic_id, r.forum_id, r.reported_by, r.message, r.zapped, r.zapped_by FROM '.$db->prefix.'reports AS r WHERE r.zapped IS NOT NULL ORDER BY zapped DESC LIMIT 10') or error('Unable to fetch report list', __FILE__, __LINE__, $db->error());
$num_zapped = $db->num_rows($result);
while ($cur_report = $db->fetch_assoc($result)) $reports[] = $cur_report;


// Fetch all auxillary info
$boards = $topics = $posts = $users = array();
foreach ($reports as $cur_report)
{
	if ($cur_report['report_type'] == PUN_REP_BOARD)
	{
		// PUN_REP_BOARD, $board_kind, $owner_id, 0, $pun_user['id']
		$users[] = $cur_report['topic_id'];
	}
	else if ($cur_report['report_type'] == PUN_REP_ABUSE)
	{
		$posts[] = $cur_report['post_id'];
		$topics[] = $cur_report['topic_id'];
		$boards[] = $cur_report['forum_id'];
	}
	$users[] = $cur_report['reported_by'];
	if (!empty($cur_report['zapped_by'])) $users[] = $cur_report['zapped_by'];
}

$posts = array_unique($posts);
if (count($posts))
{
	$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE id IN ('.implode(',',$posts).')') or error('Unable to fetch posts list', __FILE__, __LINE__, $db->error());
	$posts = array();
	while ($temp = $db->fetch_row($result)) $posts[] = $temp[0];
}

$topics = array_unique($topics);
if (count($topics))
{
	$result = $db->query('SELECT id, subject FROM '.$db->prefix.'topics WHERE id IN ('.implode(',',$topics).')') or error('Unable to fetch topics list', __FILE__, __LINE__, $db->error());
	$topics = array();
	while ($temp = $db->fetch_assoc($result)) $topics[$temp['id']] = $temp['subject'];
}

$boards = array_unique($boards);
if (count($boards))
{
	$result = $db->query('SELECT id, forum_name FROM '.$db->prefix.'forums WHERE id IN ('.implode(',',$boards).')') or error('Unable to fetch boards list', __FILE__, __LINE__, $db->error());
	$boards = array();
	while ($temp = $db->fetch_assoc($result)) $boards[$temp['id']] = $temp['forum_name'];
}

$users = array_unique($users);
if (count($users))
{
	$result = $db->query('SELECT id, username FROM '.$db->prefix.'users WHERE id IN ('.implode(',',$users).')') or error('Unable to fetch users list', __FILE__, __LINE__, $db->error());
	$users = array();
	while ($temp = $db->fetch_assoc($result)) $users[$temp['id']] = $temp['username'];
}

// Show all new reports
if ($num_new)
{
	for ($i=0; $i<$num_new; $i++)
	{
		$cur_report = $reports[$i];

		$reporter = (isset($users[$cur_report['reported_by']]))? ('<a href="'.$base_url.'/profile.php?id='.$cur_report['reported_by'].'">'.pun_htmlspecialchars($users[$cur_report['reported_by']]).'</a>') : 'Deleted user';
		$message = str_replace("\n", '<br />', pun_htmlspecialchars($cur_report['message']));

?>
				<div class="inform">
					<input type="hidden" name="csrf_hash" value="<?php echo csrf_hash() ?>" />
					<fieldset>
						<legend>Reported <?php echo format_time($cur_report['created']) ?></legend>
						<div class="infldset">
							<table cellspacing="0">
<?php
		if ($cur_report['report_type'] == PUN_REP_ABUSE)
		{
			$board = (isset($boards[$cur_report['forum_id']])) ? ('<a href="'.$base_url.'/viewboard.php?id='.$cur_report['forum_id'].'">'.pun_htmlspecialchars($boards[$cur_report['forum_id']]).'</a>') : 'Deleted';
			$topic = (isset($topics[$cur_report['topic_id']])) ? ('<a href="'.$base_url.'/viewtopic.php?id='.$cur_report['topic_id'].'">'.pun_htmlspecialchars($topics[$cur_report['topic_id']]).'</a>') : 'Deleted';
			$post = (in_array($cur_report['post_id'],$posts)) ?  ('<a href="'.$base_url.'/viewtopic.php?pid='.$cur_report['post_id'].'#p'.$cur_report['post_id'].'">Post #'.$cur_report['post_id'].'</a>') : 'Deleted';
?>
								<tr>
									<th scope="row">Abuse report:</th>
									<td><?php echo $board ?>&nbsp;&raquo;&nbsp;<?php echo $topic ?>&nbsp;&raquo;&nbsp;<?php echo $post ?></td>
								</tr>
<?php
		}
		else if ($cur_report['report_type'] == PUN_REP_BOARD)
		{
			$board_kind = '<a href="'.$base_url.'/admin/boards.php?kind='.$cur_report['post_id'].'">'.ucfirst(basename($kinds[$cur_report['post_id']], '.php')).'</a>';
			$board_owner = (isset($users[$cur_report['topic_id']]))? ('<a href="'.$base_url.'/profile.php?id='.$cur_report['topic_id'].'">'.pun_htmlspecialchars($users[$cur_report['topic_id']]).'</a>') : 'Deleted user';
?>
								<tr>
									<th scope="row">Board request:</th>
									<td><?php echo $board_kind ?> for <?php echo $board_owner ?></td>
								</tr>
<?php
		}
?>

								<tr>
									<th scope="row">Report by <?php echo $reporter ?><div><input type="submit" name="zap_id[<?php echo $cur_report['id'] ?>]" value=" Zap " /></div></th>
									<td><?php echo $message ?></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
<?php

	}
}
else
	echo "\t\t\t\t".'<p>There are no new reports.</p>'."\n";

?>
			</form>
		</div>
	</div>

	<div class="blockform block2">
		<h2><span>10 last zapped reports</span></h2>
		<div class="box">
			<div class="fakeform">
<?php

// Show last 10 zapped reports
if ($num_zapped)
{
	for ($i=0; $i<$num_zapped; $i++)
	{
		$cur_report = $reports[$num_new+$i];

		$reporter = (isset($users[$cur_report['reported_by']]))? ('<a href="'.$base_url.'/profile.php?id='.$cur_report['reported_by'].'">'.pun_htmlspecialchars($users[$cur_report['reported_by']]).'</a>') : 'Deleted user';
		$zapper = (isset($users[$cur_report['zapped_by']]))? ('<a href="'.$base_url.'/profile.php?id='.$cur_report['zapped_by'].'">'.pun_htmlspecialchars($users[$cur_report['zapped_by']]).'</a>') : 'Deleted user';
		$message = str_replace("\n", '<br />', pun_htmlspecialchars($cur_report['message']));
?>
				<div class="inform">
					<fieldset>
						<legend>Zapped <?php echo format_time($cur_report['zapped']) ?></legend>
						<div class="infldset">
							<table cellspacing="0">
<?php
		if ($cur_report['report_type'] == PUN_REP_ABUSE)
		{
			$board = (isset($boards[$cur_report['forum_id']])) ? ('<a href="'.$base_url.'/viewboard.php?id='.$cur_report['forum_id'].'">'.pun_htmlspecialchars($boards[$cur_report['forum_id']]).'</a>') : 'Deleted';
			$topic = (isset($topics[$cur_report['topic_id']])) ? ('<a href="'.$base_url.'/viewtopic.php?id='.$cur_report['topic_id'].'">'.pun_htmlspecialchars($topics[$cur_report['topic_id']]).'</a>') : 'Deleted';
			$post = (in_array($cur_report['post_id'],$posts)) ?  ('<a href="'.$base_url.'/viewtopic.php?pid='.$cur_report['post_id'].'#p'.$cur_report['post_id'].'">Post #'.$cur_report['post_id'].'</a>') : 'Deleted';
?>
								<tr>
									<th scope="row">Abuse report:</th>
									<td><?php echo $board ?>&nbsp;&raquo;&nbsp;<?php echo $topic ?>&nbsp;&raquo;&nbsp;<?php echo $post ?></td>
								</tr>
<?php
		}
		else if ($cur_report['report_type'] == PUN_REP_BOARD)
		{
			$board_kind = '<a href="'.$base_url.'/admin/boards.php?kind='.$cur_report['post_id'].'">'.ucfirst(basename($kinds[$cur_report['post_id']], '.php')).'</a>';
			$board_owner = (isset($users[$cur_report['topic_id']]))? ('<a href="'.$base_url.'/profile.php?id='.$cur_report['topic_id'].'">'.pun_htmlspecialchars($users[$cur_report['topic_id']]).'</a>') : 'Deleted user';
?>
								<tr>
									<th scope="row">Board request:</th>
									<td><?php echo $board_kind ?> for <?php echo $board_owner ?></td>
								</tr>
<?php
		}
?>
								<tr>
									<th scope="row">Reported by <?php echo $reporter ?><div class="topspace">Zapped by <?php echo $zapper ?></div></th>
									<td><?php echo $message ?></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
<?php

	}
}
else
	echo "\t\t\t\t".'<p>There are no zapped reports.</p>'."\n";

?>
			</div>
		</div>
	</div>
<?php

require PUN_ROOT.'include/footer.php';
