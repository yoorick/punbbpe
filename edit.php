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
require PUN_ROOT.'include/file_upload.php';


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request']);

// Fetch some info about the post, the topic and the board
$result = $db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, f.cat_id AS cid, f.owner_id, c.cat_name, c.kind, fp.post_replies, fp.post_topics, fp.file_upload, fp.file_download, fp.file_limit, t.id AS tid, t.subject, t.topic_desc, t.posted, t.closed, t.labels, p.poster, p.poster_id, p.message, p.hide_smilies FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id INNER JOIN '.$db->prefix.'categories AS c ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$cur_post = $db->fetch_assoc($result);
$kind = $cur_post['kind'];
$person = $cur_post['owner_id'];
if (!empty($person))
{
	$result = $db->query('SELECT u.is_team, u.teams FROM '.$db->prefix.'users AS u WHERE u.id='.$person) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);
	$user = $db->fetch_assoc($result);
	$teams = ($user['teams']!='') ? unserialize($user['teams']) : array();
	$is_teamleader = ($pun_user['g_id'] == PUN_ADMIN) || ($user['is_team'] == '1' && in_array($pun_user['id'], array_values($teams)));
}


// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
$is_admmod = (!empty($person) && ($pun_user['id'] == $person || $is_teamleader)) ||
	($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_id'] == PUN_MOD && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

// Determine whether this post is the "topic post" or not
$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$cur_post['tid'].' ORDER BY posted LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
$topic_post_id = $db->result($result);

$can_edit_subject = ($id == $topic_post_id && (($pun_user['g_edit_subjects_interval'] == '0' || (time() - $cur_post['posted']) < $pun_user['g_edit_subjects_interval']) || $is_admmod)) ? true : false;

// There are only images in gallery topic
if (($id == $topic_post_id) and ($kind == PUN_KIND_GALLERY))
	$pun_config['file_allowed_ext'] = $pun_config['file_image_ext'];

// have we permission to attachments?
$can_download = ($cur_post['file_download'] == '' && $pun_user['g_file_download'] == '1') || $cur_post['file_download'] == '1' || $is_admmod;
$can_upload = ($cur_post['file_upload'] == '' && $pun_user['g_file_upload'] == '1') || $cur_post['file_upload'] == '1' || $is_admmod;
if ($pun_user['is_guest'])
{
	$file_limit = 0;
}
else
{
	$result = $db->query('SELECT count(*) FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'attachments AS a ON t.id=a.topic_id WHERE t.forum_id='.$cur_post['fid'].' AND a.poster_id='.$pun_user['id']) or error('Unable to attachments count', __FILE__, __LINE__, $db->error());
	$uploaded_to_board = $db->result($result);

	$result = $db->query('SELECT count(*) FROM '.$db->prefix.'attachments AS a WHERE a.post_id='.$id) or error('Unable to attachments count', __FILE__, __LINE__, $db->error());
	$uploaded_to_post = $db->fetch_row($result); $uploaded_to_post = $uploaded_to_post[0];

	$board_file_limit = (!empty($cur_post['file_limit']))? intval($cur_post['file_limit']): intval($pun_user['g_file_limit']);

	$global_file_limit = $pun_user['g_file_limit'] + $pun_user['file_bonus'];

	$post_file_limit = intval($pun_config['file_max_post_files']);

	if ($pun_user['g_id'] == PUN_ADMIN)
		$file_limit = 20; // just unlimited
	else
		$file_limit = min(20, $board_file_limit-$uploaded_to_board, $global_file_limit-$pun_user['num_files'], $post_file_limit-$uploaded_to_post);
}

if (!$is_admmod && ($id != $topic_post_id && $pun_config['file_first_only'] == '1'))
	$can_upload = false;

// Do we have permission to edit this post?
if (($pun_user['g_edit_posts'] == '0' ||
	$cur_post['poster_id'] != $pun_user['id'] ||
	$cur_post['closed'] == '1') &&
	!$is_admmod)
	message($lang_common['No permission']);

// Load the post.php/edit.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

// Start with a clean slate
$errors = array();


if (isset($_POST['form_sent']))
{
	if ($is_admmod)
		confirm_referrer('edit.php');

	// If it is a topic it must contain a subject
	if ($can_edit_subject)
	{
		$subject = pun_trim($_POST['req_subject']);

		if ($subject == '')
			$errors[] = $lang_post['No subject'];
		else if (pun_strlen($subject) > 70)
			$errors[] = $lang_post['Too long subject'];
		else if ($pun_config['p_subject_all_caps'] == '0' && strtoupper($subject) == $subject && $pun_user['g_id'] > PUN_MOD)
			$subject = ucwords(strtolower($subject));

		// If it is a topic it may contain a search labels
		$labels = pun_trim((isset($_POST['unreq_labels']))? $_POST['unreq_labels']: '');
		// cleanup string
		$labels = implode_labels( explode_labels($labels) );

		$topic_desc = pun_trim($_POST['topic_desc']);
	}

	// Clean up message from POST
	$message = pun_linebreaks(pun_trim($_POST['req_message']));

	if ($message == '')
		$errors[] = $lang_post['No message'];
	else if (mb_strlen($message) > 120000) // was 65535
		$errors[] = $lang_post['Too long message'];
	else if ($pun_config['p_message_all_caps'] == '0' && strtoupper($message) == $message && $pun_user['g_id'] > PUN_MOD)
		$message = ucwords(strtolower($message));

	// Validate BBCode syntax
	if ($pun_config['p_message_bbcode'] == '1' && strpos($message, '[') !== false && strpos($message, ']') !== false)
	{
		require PUN_ROOT.'include/parser.php';
		$message = preparse_bbcode($message, $errors);
	}


	$hide_smilies = isset($_POST['hide_smilies']) ? intval($_POST['hide_smilies']) : 0;
	if ($hide_smilies != '1') $hide_smilies = '0';

	// Did everything go according to plan?
	if (empty($errors) && !isset($_POST['preview']))
	{
		// Make sure that all required thumbnails have gererated just in time
	        preg_replace('#::thumb([0-9]+)::#e', 'handle_thumb_tag(\'$1\')', $message);

		$edited_sql = (!isset($_POST['silent']) || !$is_admmod) ? $edited_sql = ', edited='.time().', edited_by=\''.$db->escape($pun_user['username']).'\'' : '';

		require PUN_ROOT.'include/search_idx.php';

		if ($can_edit_subject)
		{
			// Update the topic and any redirect topics
			$db->query('UPDATE '.$db->prefix.'topics SET subject=\''.$db->escape($subject).'\', topic_desc=\''.$db->escape($topic_desc).'\', labels=\''.$db->escape($labels).'\' WHERE id='.$cur_post['tid'].' OR moved_to='.$cur_post['tid']) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

			// We changed the subject, so we need to take that into account when we update the search words
			update_search_index('edit', $id, $message, $subject.' '.$topic_desc);
		}
		else
			update_search_index('edit', $id, $message);

		// Update the post
		$db->query('UPDATE '.$db->prefix.'posts SET message=\''.$db->escape($message).'\', hide_smilies=\''.$hide_smilies.'\''.$edited_sql.' WHERE id='.$id) or error('Unable to update post', __FILE__, __LINE__, $db->error());

		$attach_result =  process_deleted_files($id, $deleted);
		$attach_result .= process_uploaded_files($cur_post['tid'], $id, $uploaded);
		process_magic_thumbs($id, $message);

		// If the posting user is logged in, increment his/her post count
		if (!$pun_user['is_guest'] && ($uploaded-$deleted) != 0)
		{
			$low_prio = ($db_type == 'mysql') ? 'LOW_PRIORITY ' : '';
			$db->query('UPDATE '.$low_prio.$db->prefix.'users SET num_files=num_files+'.($uploaded-$deleted).' WHERE id='.$pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());
		}

		// Control gallery topic
		if (($id == $topic_post_id) and ($kind == PUN_KIND_GALLERY))
		{
			// Count how many files attached to "topic post"
			$result = $db->query('SELECT count(*) FROM '.$db->prefix.'attachments WHERE post_id='.$id) or error('Unable to fetch attachment count', __FILE__, __LINE__, $db->error());
			$total_uploaded = $db->result($result);

			if ($total_uploaded == 0)
			{
				delete_topic($cur_post['tid']);
				update_board($cur_post['fid']);
				message($lang_post['Galley without image']."\n".
					'<p>'.$lang_common['Jump to'].' <a href="'.$base_url.'/viewboard.php?id='.$cur_post['fid'].'">'.pun_htmlspecialchars($cur_post['forum_name']).'</a></p>', true);
			}

		}

		redirect($base_url.'/viewtopic.php?pid='.$id.'#p'.$id, $attach_result.$lang_post['Edit redirect']);
	}
}



$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / '.$lang_post['Edit post'];
$required_fields = array('req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
$focus_element = array('edit', 'req_message');
require PUN_ROOT.'include/header.php';

$cur_index = 1;

?>
<div class="linkst">
	<div class="inbox">
		<ul><li><a href="<?php echo $base_url.'/'.$kinds[$kind] ?>"><?php echo $lang_common['Boards kind'][$kind] ?></a></li><li>&nbsp;&raquo;&nbsp;<a href="viewboard.php?id=<?php echo $cur_post['fid'] ?>"><?php echo pun_htmlspecialchars($cur_post['forum_name']) ?></a></li><li>&nbsp;&raquo;&nbsp;<?php echo pun_htmlspecialchars($cur_post['subject']) ?></li></ul>
	</div>
</div>

<?php

// If there are errors, we display them
if (!empty($errors))
{

?>
<div id="posterror" class="block">
	<h2><span><?php echo $lang_post['Post errors'] ?></span></h2>
	<div class="box">
		<div class="inbox"
			<p><?php echo $lang_post['Post errors info'] ?></p>
			<ul>
<?php

	while (list(, $cur_error) = each($errors))
		echo "\t\t\t\t".'<li><strong>'.$cur_error.'</strong></li>'."\n";
?>
			</ul>
		</div>
	</div>
</div>

<?php

}
else if (isset($_POST['preview']))
{
	// Make sure that all required thumbnails have gererated just in time
        preg_replace('#::thumb([0-9]+)::#e', 'handle_thumb_tag(\'$1\')', $message);

	require_once PUN_ROOT.'include/parser.php';
	$preview_message = parse_message($message, $hide_smilies);

?>
<div id="postpreview" class="blockpost">
	<h2><span><?php echo $lang_post['Post preview'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postright">
				<div class="postmsg">
					<?php echo $preview_message."\n" ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php

}

?>
<div class="blockform">
	<h2><span><?php echo $lang_post['Edit post'] ?></span></h2>
	<div class="box">
		<form id="post" method="post" action="edit.php?id=<?php echo $id ?>&amp;action=edit" onsubmit="return process_form(this)" enctype="multipart/form-data">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_post['Edit post legend'] ?></legend>
					<input type="hidden" name="csrf_hash" value="<?php echo csrf_hash() ?>" />
					<input type="hidden" name="form_sent" value="1" />
					<div class="infldset txtarea">
<?php if ($can_edit_subject): ?>						<label><strong><?php echo $lang_common['Subject'] ?></strong><em class=\"req-text\">*</em><br />
						<input class="longinput" type="text" name="req_subject" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" value="<?php echo pun_htmlspecialchars(isset($_POST['req_subject']) ? $_POST['req_subject'] : $cur_post['subject']) ?>" /><br /></label>
<?php endif; ?>						<label><strong><?php echo $lang_common['Message'] ?></strong><em class=\"req-text\">*</em><br />
<?php
include PUN_ROOT.'include/attach/fetch.php' ;
// insert popup info panel & its data (javascript)
if ($pun_config['file_popup_info'] == '1')
	include PUN_ROOT.'include/attach/popup_data.php';

include PUN_ROOT.'include/attach/post_buttons.php';

?>
						<p class="areafield required">
							<textarea id="req_message" name="req_message" style="height: 200px" tabindex="<?php echo $cur_index++ ?>"><?php echo pun_htmlspecialchars(isset($_POST['req_message']) ? $message : $cur_post['message']) ?></textarea><br /></label>
						</p>
						<div id="msghelp">
						<script type="text/javascript">document.write('<span class="conr"><ul><li>size: <a href="#" onclick="return resize_message(100)">[+]</a>&nbsp;<a href="#" onclick="return resize_message(-100)">[-]</a>&nbsp;</li></ul></span>');</script>
						<ul class="bblinks">
							<li><a href="help.php#bbcode" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a>: <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="help.php#img" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a>: <?php echo ($pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="help.php#smilies" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a>: <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
						</ul>
<?php if ($can_edit_subject)
{
	if (!empty($pun_config['o_topic_labels'])) {
?>

						<label><strong><?php echo $lang_common['Labels'] ?></strong><br />
						<input class="longinput" type="text" name="unreq_labels" size="80" maxlength="255" tabindex="<?php echo $cur_index++ ?>" value="<?php echo pun_htmlspecialchars(substr($cur_post['labels'],1,-1)) ?>" /><br /></label>
<?php
	} else {
?>
						<input class="longinput" type="hidden" name="unreq_labels" value="<?php echo pun_htmlspecialchars(substr($cur_post['labels'],1,-1)) ?>" />
<?php
	}
}
?>
						</div>
<?php if ($can_edit_subject): ?>						<label><strong><?php echo $lang_common['Description'] ?></strong><br />
						<input class="longinput" type="text" name="topic_desc" size="80" maxlength="1024" tabindex="<?php echo $cur_index++ ?>" value="<?php echo pun_htmlspecialchars(isset($_POST['topic_desc']) ? $_POST['topic_desc'] : $cur_post['topic_desc']) ?>" /><br /></label>
<?php endif; ?>
					</div>
				</fieldset>
<?php
// increase numer of rows to number of already attached files
// $file_limit will grow up when user delete files and become lower on each upload
// but numer of rows is less or equal 20
$num_to_upload = $file_limit/* + $uploaded_to_post*/;
$num_to_upload = min($num_to_upload, 20);
if ($uploaded_to_post || ($can_upload && $num_to_upload>0))
{
	echo "\t\t\t\t<br class=\"clearb\" />\n";
	echo "\t\t\t\t<fieldset>\n";
	echo "\t\t\t\t\t<legend>".$lang_fu['Attachments']."</legend>\n";
	include PUN_ROOT.'include/attach/view_attachments.php';
	if ($can_upload && $num_to_upload>0)
		include PUN_ROOT.'include/attach/post_input.php';
	echo "\t\t\t\t</fieldset>\n";
}

$checkboxes = array();
if ($pun_config['o_smilies'] == '1')
{
	if (isset($_POST['hide_smilies']) || $cur_post['hide_smilies'] == '1')
		$checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" checked="checked" tabindex="'.($cur_index++).'" />&nbsp;'.$lang_post['Hide smilies'];
	else
		$checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'" />&nbsp;'.$lang_post['Hide smilies'];
}

if ($is_admmod)
{
	if ((isset($_POST['form_sent']) && isset($_POST['silent'])) || !isset($_POST['form_sent']))
		$checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" checked="checked" />&nbsp;'.$lang_post['Silent edit'];
	else
		$checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" />&nbsp;'.$lang_post['Silent edit'];
}

if (!empty($checkboxes))
{

?>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Options'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<?php echo implode('</label>'."\n\t\t\t\t\t\t\t", $checkboxes).'</label>'."\n" ?>
						</div>
					</div>
				</fieldset>
<?php

	}

?>
			</div>
			<p><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="s" /><input type="submit" name="preview" value="<?php echo $lang_post['Preview'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="p" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
<?php

require PUN_ROOT.'include/footer.php';
