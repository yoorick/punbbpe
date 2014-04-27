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


if ($pun_user['g_id'] > PUN_ADMIN)
	message($lang_common['No permission']);

if (isset($_POST['kind']))
	$kind = intval($_POST['kind']);
else if (isset($_GET['kind']))
	$kind = intval($_GET['kind']);
else
	$kind = 0;

$csrf_hash = csrf_hash();

// Add a "default" board
if (isset($_POST['add_forum']))
{
	confirm_referrer('admin/boards.php');

	$forum_name = (!isset($_POST['forum_name']) || trim($_POST['forum_name']) == '')? 'New board' : trim($_POST['forum_name']);
	$add_to_cat = intval($_POST['add_to_cat']);
	if ($add_to_cat < 1)
		message($lang_common['Bad request']);

	$db->query('INSERT INTO '.$db->prefix.'forums (cat_id, forum_name) VALUES('.$add_to_cat.', \''.$db->escape($forum_name).'\')') or error('Unable to create board', __FILE__, __LINE__, $db->error());
	$new_forum_id = $db->insert_id();

	// Regenerate the quickjump cache
	require_once PUN_ROOT.'include/cache.php';
	generate_quickjump_cache();

	// Immediatelly edit newborn board
	redirect($base_url.'/admin/boards.php?edit_forum='.$new_forum_id, 'Board added. Redirecting &hellip;');
//	redirect($base_url.'/admin/boards.php', 'Board added. Redirecting &hellip;');
}


// Make new board with the same permissions
else if (isset($_GET['clone_forum']))
{
	$board_id = intval($_GET['clone_forum']);
	if ($board_id < 1)
		message($lang_common['Bad request']);

	confirm_referrer('admin/boards.php', true);

	// Make copy of board and its permissions
	$db->query('INSERT INTO '.$db->prefix.'forums (cat_id, forum_name, redirect_url, moderators, sort_by) (SELECT cat_id, concat( \'Copy of \', forum_name), redirect_url, moderators, sort_by FROM '.$db->prefix.'forums WHERE id='.$board_id.')') or error('Unable to clone board', __FILE__, __LINE__, $db->error());
	$new_forum_id = $db->insert_id();
	$db->query('INSERT INTO '.$db->prefix.'forum_perms (forum_id, group_id, read_forum, post_replies, post_topics, file_upload, file_download, file_limit) '.
	           '(SELECT '.$new_forum_id.', group_id, read_forum, post_replies, post_topics, file_upload, file_download, file_limit FROM '.$db->prefix.'forum_perms WHERE forum_id='.$board_id.')') or error('Unable to clone forum_perms', __FILE__, __LINE__, $db->error());

	// Regenerate the quickjump cache
	require_once PUN_ROOT.'include/cache.php';
	generate_quickjump_cache();

	// Immediatelly edit newborn board
	redirect($base_url.'/admin/boards.php?edit_forum='.$new_forum_id, 'Board cloned. Redirecting &hellip;');
}

// Delete a board
else if (isset($_GET['del_forum']))
{
	confirm_referrer('admin/boards.php', true);

	$board_id = intval($_GET['del_forum']);
	if ($board_id < 1)
		message($lang_common['Bad request']);

	if (isset($_POST['del_forum_comply']))	// Delete a board with all posts
	{
		@set_time_limit(0);

		// Prune all posts and topics
		prune($board_id, 1, -1);

		// Locate any "orphaned redirect topics" and delete them
		$result = $db->query('SELECT t1.id FROM '.$db->prefix.'topics AS t1 LEFT JOIN '.$db->prefix.'topics AS t2 ON t1.moved_to=t2.id WHERE t2.id IS NULL AND t1.moved_to IS NOT NULL') or error('Unable to fetch redirect topics', __FILE__, __LINE__, $db->error());
		$num_orphans = $db->num_rows($result);

		if ($num_orphans)
		{
			for ($i = 0; $i < $num_orphans; ++$i)
				$orphans[] = $db->result($result, $i);

			$db->query('DELETE FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $orphans).')') or error('Unable to delete redirect topics', __FILE__, __LINE__, $db->error());
		}

		// Delete the board and any board specific group permissions
		$db->query('DELETE FROM '.$db->prefix.'forums WHERE id='.$board_id) or error('Unable to delete board', __FILE__, __LINE__, $db->error());
		$db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE forum_id='.$board_id) or error('Unable to delete group board permissions', __FILE__, __LINE__, $db->error());

		// Regenerate the quickjump cache
		require_once PUN_ROOT.'include/cache.php';
		generate_quickjump_cache();

		redirect($base_url.'/admin/boards.php?kind='.$kind, 'Board deleted. Redirecting &hellip;');
	}
	else	// If the user hasn't confirmed the delete
	{
		$result = $db->query('SELECT f.forum_name, c.kind FROM '.$db->prefix.'forums AS f INNER JOIN '.$db->prefix.'categories AS c ON c.id=f.cat_id WHERE f.id='.$board_id) or error('Unable to fetch board info', __FILE__, __LINE__, $db->error());
		$cur_board = $db->fetch_assoc($result);
		$forum_name = pun_htmlspecialchars($cur_board['forum_name']);
		$kind = $cur_board['kind'];


		$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / Admin / Boards';
		require PUN_ROOT.'include/header.php';

//		generate_admin_menu('boards');

?>
	<div class="blockform">
		<h2><span>Confirm delete board</span></h2>
		<div class="box">
			<form method="post" action="<?php echo $base_url ?>/admin/boards.php?del_forum=<?php echo $board_id ?>">
				<div class="inform">
					<input type="hidden" name="kind" value="<?php echo $kind ?>" />
					<input type="hidden" name="csrf_hash" value="<?php echo $csrf_hash ?>" />
					<fieldset>
						<legend>Important! Read before deleting</legend>
						<div class="infldset">
							<p>Are you sure that you want to delete the board "<?php echo $forum_name ?>"?</p>
							<p>WARNING! Deleting a board will delete all posts (if any) in that board!</p>
						</div>
					</fieldset>
				</div>
				<p><input type="submit" name="del_forum_comply" value="Delete" /><a href="javascript:history.go(-1)">Go back</a></p>
			</form>
		</div>
	</div>
<?php

		require PUN_ROOT.'include/footer.php';
	}
}


// Update board positions
else if (isset($_POST['update_positions']))
{
	confirm_referrer('admin/boards.php');

	while (list($board_id, $disp_position) = @each($_POST['position']))
	{
		if (!@preg_match('#^\d+$#', $disp_position))
			message('Position must be a positive integer value.');

		$db->query('UPDATE '.$db->prefix.'forums SET disp_position='.$disp_position.' WHERE id='.intval($board_id)) or error('Unable to update board', __FILE__, __LINE__, $db->error());
	}

	// Regenerate the quickjump cache
	require_once PUN_ROOT.'include/cache.php';
	generate_quickjump_cache();

	redirect($base_url.'/admin/boards.php?kind='.$kind, 'Boards updated. Redirecting &hellip;');
}


else if (isset($_GET['edit_forum']))
{
	$board_id = intval($_GET['edit_forum']);
	if ($board_id < 1)
		message($lang_common['Bad request']);

	// Update group permissions for $board_id
	if (isset($_POST['save']))
	{
		confirm_referrer('admin/boards.php');

		// Start with the board details
		$forum_name = trim($_POST['forum_name']);
		$forum_desc = pun_linebreaks(trim($_POST['forum_desc']));
		$cat_id = intval($_POST['cat_id']);
		$sort_by = intval($_POST['sort_by']);
		$redirect_url = isset($_POST['redirect_url']) ? trim($_POST['redirect_url']) : null;

		if ($forum_name == '')
			message('You must enter a board name.');

		if ($cat_id < 1)
			message($lang_common['Bad request']);

		$owner = trim($_POST['board_owner']);
		if ($owner == '')
		{
			$owner_id = 'null';
			$owner = 'null';
			$mod_option = '';
		}
		else
		{
			// search username (case insensitive)
			$result = $db->query('SELECT id, username FROM '.$db->prefix.'users WHERE username=\''.$db->escape($owner).'\'') or error('Unable to fetch board owner id', __FILE__, __LINE__, $db->error());
			if ($db->num_rows($result) != 1)
				message($lang_common['Bad board owner']);

			// get user id and username in real character case
			list($owner_id, $owner) = $db->fetch_row($result);
			if ($owner_id <= 1)
				message($lang_common['Bad board owner']);

			$owner = '\''.$db->escape($owner).'\'';
			$mod_option = ', moderators=NULL';
		}

		$forum_desc = ($forum_desc != '') ? '\''.$db->escape($forum_desc).'\'' : 'NULL';
		$redirect_url = ($redirect_url != '') ? '\''.$db->escape($redirect_url).'\'' : 'NULL';

		$db->query('UPDATE '.$db->prefix.'forums SET forum_name=\''.$db->escape($forum_name).'\', forum_desc='.$forum_desc.', redirect_url='.$redirect_url.', sort_by='.$sort_by.', cat_id='.$cat_id.', owner_id='.$owner_id.', owner='.$owner.$mod_option.' WHERE id='.$board_id) or error('Unable to update board', __FILE__, __LINE__, $db->error());

		// Now let's deal with the permissions
		if (isset($_POST['read_forum_old']))
		{
			$result = $db->query('SELECT g_id, g_read_board, g_post_replies, g_post_topics, g_file_upload, g_file_download, g_file_limit FROM '.$db->prefix.'groups WHERE g_id!='.PUN_ADMIN) or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());
			while ($cur_group = $db->fetch_assoc($result))
			{
				$read_forum_new = ($cur_group['g_read_board'] == '1') ? isset($_POST['read_forum_new'][$cur_group['g_id']]) ? '1' : '0' : intval($_POST['read_forum_old'][$cur_group['g_id']]);
				$post_replies_new = isset($_POST['post_replies_new'][$cur_group['g_id']]) ? '1' : '0';
				$post_topics_new = isset($_POST['post_topics_new'][$cur_group['g_id']]) ? '1' : '0';

				$file_download_new = isset($_POST['file_download_new'][$cur_group['g_id']]) ? '1' : '0';
				if ($cur_group['g_id'] == PUN_GUEST)
				{
                                    // upload settings never changed for guests
				    $file_upload_new = $_POST['file_upload_old'][$cur_group['g_id']] = $cur_group['g_file_upload'];
				    $file_limit_new  = $_POST['file_limit_old'][$cur_group['g_id']]  = $cur_group['g_file_limit'];
				}
				else
				{
				    $file_upload_new   = isset($_POST['file_upload_new']  [$cur_group['g_id']]) ? '1' : '0';
				    $file_limit_new = isset($_POST['file_limit_new'][$cur_group['g_id']]) ? intval($_POST['file_limit_new'][$cur_group['g_id']]) : '0';
				}

				// Check if the new settings differ from the old
				if ($read_forum_new != $_POST['read_forum_old'][$cur_group['g_id']] || $post_replies_new != $_POST['post_replies_old'][$cur_group['g_id']] || $post_topics_new != $_POST['post_topics_old'][$cur_group['g_id']] ||
				    $file_download_new != $_POST['file_download_old'][$cur_group['g_id']] ||
				    $file_upload_new != $_POST['file_upload_old'][$cur_group['g_id']] ||
				    $file_limit_new != $_POST['file_limit_old'][$cur_group['g_id']] )
				{
					// If the new settings are identical to the default settings for this group, delete it's row in forum_perms
					if ($read_forum_new == '1' && $post_replies_new == $cur_group['g_post_replies'] && $post_topics_new == $cur_group['g_post_topics'] && $file_upload_new == $cur_group['g_file_upload'] && $file_download_new == $cur_group['g_file_download'] && $file_limit_new == 0)
						$db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE group_id='.$cur_group['g_id'].' AND forum_id='.$board_id) or error('Unable to delete group board permissions', __FILE__, __LINE__, $db->error());
					else
					{
						// Run an UPDATE and see if it affected a row, if not, INSERT
						$db->query('UPDATE '.$db->prefix.'forum_perms SET read_forum='.$read_forum_new.', post_replies='.$post_replies_new.', post_topics='.$post_topics_new.', file_upload='.$file_upload_new.', file_download='.$file_download_new.', file_limit='.$file_limit_new.' WHERE group_id='.$cur_group['g_id'].' AND forum_id='.$board_id) or error('Unable to insert group board permissions', __FILE__, __LINE__, $db->error());
						if (!$db->affected_rows())
							$db->query('INSERT INTO '.$db->prefix.'forum_perms (group_id, forum_id, read_forum, post_replies, post_topics, file_upload, file_download, file_limit) VALUES('.$cur_group['g_id'].', '.$board_id.', '.$read_forum_new.', '.$post_replies_new.', '.$post_topics_new.', '.$file_upload_new.', '.$file_download_new.', '.$file_limit_new.')') or error('Unable to insert group board permissions', __FILE__, __LINE__, $db->error());
					}
				}
			}
		}

		// Regenerate the quickjump cache
		require_once PUN_ROOT.'include/cache.php';
		generate_quickjump_cache();

		redirect($base_url.'/admin/boards.php?kind='.$kind, 'Board updated. Redirecting &hellip;');
	}
	else if (isset($_POST['revert_perms']))
	{
		confirm_referrer('admin/boards.php');

		$db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE forum_id='.$board_id) or error('Unable to delete group board permissions', __FILE__, __LINE__, $db->error());

		// Regenerate the quickjump cache
		require_once PUN_ROOT.'include/cache.php';
		generate_quickjump_cache();

		redirect($base_url.'/admin/boards.php?edit_forum='.$board_id, 'Permissions reverted to defaults. Redirecting &hellip;');
	}


	// Fetch board info
	$result = $db->query('SELECT f.id, f.forum_name, f.forum_desc, f.redirect_url, f.num_topics, f.sort_by, f.owner_id, f.owner, f.cat_id, c.kind FROM '.$db->prefix.'forums AS f INNER JOIN '.$db->prefix.'categories AS c ON c.id=f.cat_id WHERE f.id='.$board_id) or error('Unable to fetch board info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$cur_board = $db->fetch_assoc($result);
	$kind = $cur_board['kind'];


	$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / Admin / Boards';
	require PUN_ROOT.'include/header.php';

//	generate_admin_menu('boards');

?>
	<div class="blockform">
		<h2><span>Edit board</span></h2>
		<div class="box">
			<form id="edit_forum" method="post" action="<?php echo $base_url ?>/admin/boards.php?edit_forum=<?php echo $board_id ?>">
				<p class="submittop"><input type="submit" name="save" value="Save changes" tabindex="6" /></p>
				<div class="inform">
					<input type="hidden" name="kind" value="<?php echo $kind ?>" />
					<input type="hidden" name="csrf_hash" value="<?php echo $csrf_hash ?>" />
					<fieldset>
						<legend>Edit board details</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_common['Board kind'][$kind] ?></th>
									<td><input type="text" name="forum_name" size="35" maxlength="80" value="<?php echo pun_htmlspecialchars($cur_board['forum_name']) ?>" tabindex="1" /></td>
								</tr>
								<tr>
									<th scope="row">Owner</th>
									<td><input type="text" name="board_owner" size="25" maxlength="25" value="<?php echo pun_htmlspecialchars($cur_board['owner']) ?>" tabindex="2" />
									&nbsp;(leave this field blank for shared board)</td>
								</tr>
								<tr>
									<th scope="row">Description (HTML)</th>
									<td><textarea name="forum_desc" rows="3" cols="50" tabindex="3"><?php echo pun_htmlspecialchars($cur_board['forum_desc']) ?></textarea></td>
								</tr>
								<tr>
									<th scope="row">Category</th>
									<td>
										<select name="cat_id" tabindex="3">
<?php

	$result = $db->query('SELECT id, cat_name FROM '.$db->prefix.'categories WHERE kind='.$kind.' ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $db->error());
	while ($cur_cat = $db->fetch_assoc($result))
	{
		$selected = ($cur_cat['id'] == $cur_board['cat_id']) ? ' selected="selected"' : '';
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'"'.$selected.'>'.pun_htmlspecialchars($cur_cat['cat_name']).'</option>'."\n";
	}

?>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row">Sort topics by</th>
									<td>
										<select name="sort_by" tabindex="4">
											<option value="0"<?php if ($cur_board['sort_by'] == '0') echo ' selected="selected"' ?>>Last post</option>
											<option value="1"<?php if ($cur_board['sort_by'] == '1') echo ' selected="selected"' ?>>Topic start</option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row">Redirect URL</th>
									<td><?php echo ($cur_board['num_topics']) ? 'Only available in empty boards' : '<input type="text" name="redirect_url" size="45" maxlength="100" value="'.pun_htmlspecialchars($cur_board['redirect_url']).'" tabindex="5" />'; ?></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Edit group permissions for this board</legend>
						<div class="infldset">
							<p>In this form, you can set the board specific permissions for the different user groups. If you haven't made any changes to this boards group permissions, what you see below is the default based on settings in <a href="<?php echo $base_url ?>/admin/groups.php">User groups</a>. Administrators always have full permissions and are thus excluded. Permission settings that differ from the default permissions for the user group are marked red. The "Read board" permission checkbox will be disabled if the group in question lacks the "Read board" permission. For redirect boards, only the "Read board" permission is editable.</p>
							<table id="forumperms" cellspacing="0">
							<thead>
								<tr>
									<th class="atcl">&nbsp;</th>
									<th>Read board</th>
									<th>Post replies</th>
									<th>Post topics</th>
									<th>File Download</th>
									<th>File Upload</th>
									<th>File Limit<sup>*</sup></th>
								</tr>
							</thead>
							<tbody>
<?php

	$result = $db->query('SELECT g.g_id, g.g_title, g.g_read_board, g.g_post_replies, g.g_post_topics, g.g_file_upload, g.g_file_download, g.g_file_limit, fp.read_forum, fp.post_replies, fp.post_topics, fp.file_upload, fp.file_download, fp.file_limit FROM '.$db->prefix.'groups AS g LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (g.g_id=fp.group_id AND fp.forum_id='.$board_id.') WHERE g.g_id!='.PUN_ADMIN.' ORDER BY g.g_id') or error('Unable to fetch group board permission list', __FILE__, __LINE__, $db->error());

	while ($cur_perm = $db->fetch_assoc($result))
	{
		$read_forum = ($cur_perm['read_forum'] != '0') ? true : false;
		$post_replies = (($cur_perm['g_post_replies'] == '0' && $cur_perm['post_replies'] == '1') || ($cur_perm['g_post_replies'] == '1' && $cur_perm['post_replies'] != '0')) ? true : false;
		$post_topics = (($cur_perm['g_post_topics'] == '0' && $cur_perm['post_topics'] == '1') || ($cur_perm['g_post_topics'] == '1' && $cur_perm['post_topics'] != '0')) ? true : false;
		$file_upload   = (($cur_perm['g_file_upload'] == '0'   && $cur_perm['file_upload'] == '1')   || ($cur_perm['g_file_upload'] == '1'   && $cur_perm['file_upload'] != '0')) ?   true : false;
		$file_download = (($cur_perm['g_file_download'] == '0' && $cur_perm['file_download'] == '1') || ($cur_perm['g_file_download'] == '1' && $cur_perm['file_download'] != '0')) ? true : false;
		$file_limit = ($cur_perm['file_limit'] != '') ? $cur_perm['file_limit'] : '0';

		// Determine if the current sittings differ from the default or not
		$read_forum_def = ($cur_perm['read_forum'] == '0') ? false : true;
		$post_replies_def = (($post_replies && $cur_perm['g_post_replies'] == '0') || (!$post_replies && ($cur_perm['g_post_replies'] == '' || $cur_perm['g_post_replies'] == '1'))) ? false : true;
		$post_topics_def = (($post_topics && $cur_perm['g_post_topics'] == '0') || (!$post_topics && ($cur_perm['g_post_topics'] == '' || $cur_perm['g_post_topics'] == '1'))) ? false : true;
		$file_upload_def   = (($file_upload   && $cur_perm['g_file_upload'] == '0')   || (!$file_upload   && ($cur_perm['g_file_upload'] == ''   || $cur_perm['g_file_upload'] == '1'))) ?   false : true;
		$file_download_def = (($file_download && $cur_perm['g_file_download'] == '0') || (!$file_download && ($cur_perm['g_file_download'] == '' || $cur_perm['g_file_download'] == '1'))) ? false : true;
		$file_limit_def = ($file_limit == '0');

?>
								<tr>
									<th class="atcl"><?php echo pun_htmlspecialchars($cur_perm['g_title']) ?></th>
									<td<?php if (!$read_forum_def) echo ' class="nodefault"'; ?>>
										<input type="hidden" name="read_forum_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($read_forum) ? '1' : '0'; ?>" />
										<input type="checkbox" name="read_forum_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($read_forum) ? ' checked="checked"' : ''; ?><?php echo ($cur_perm['g_read_board'] == '0') ? ' disabled="disabled"' : ''; ?> />
									</td>
									<td<?php if (!$post_replies_def && $cur_board['redirect_url'] == '') echo ' class="nodefault"'; ?>>
										<input type="hidden" name="post_replies_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($post_replies) ? '1' : '0'; ?>" />
										<input type="checkbox" name="post_replies_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($post_replies) ? ' checked="checked"' : ''; ?><?php echo ($cur_board['redirect_url'] != '') ? ' disabled="disabled"' : ''; ?> />
									</td>
									<td<?php if (!$post_topics_def && $cur_board['redirect_url'] == '') echo ' class="nodefault"'; ?>>
										<input type="hidden" name="post_topics_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($post_topics) ? '1' : '0'; ?>" />
										<input type="checkbox" name="post_topics_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($post_topics) ? ' checked="checked"' : ''; ?><?php echo ($cur_board['redirect_url'] != '') ? ' disabled="disabled"' : ''; ?> />
									</td>

									<td<?php if (!$file_download_def && $cur_board['redirect_url'] == '') echo ' class="nodefault"'; ?>>
										<input type="hidden" name="file_download_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($file_download) ? '1' : '0'; ?>" />
										<input type="checkbox" name="file_download_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($file_download) ? ' checked="checked"' : ''; ?><?php echo ($cur_board['redirect_url'] != '') ? ' disabled="disabled"' : ''; ?> />
									</td>
									<td<?php if (!$file_upload_def && $cur_board['redirect_url'] == '') echo ' class="nodefault"'; ?>>
										<input type="hidden" name="file_upload_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($file_upload) ? '1' : '0'; ?>" />
										<input type="checkbox" name="file_upload_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($file_upload) ? ' checked="checked"' : ''; ?><?php echo ($cur_board['redirect_url'] != '' || $cur_perm['g_id'] == PUN_GUEST) ? ' disabled="disabled"' : ''; ?> />
									</td>
									<td<?php if (!$file_limit_def && $cur_board['redirect_url'] == '') echo ' class="nodefault"'; ?>>
										<input type="hidden" name="file_limit_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo $file_limit ?>" />
										<input type="text" name="file_limit_new[<?php echo $cur_perm['g_id'] ?>]"  size="5" maxlength="4" value=<?php echo '"'.$file_limit.'"' ?><?php echo ($cur_board['redirect_url'] != '' || $cur_perm['g_id'] == PUN_GUEST) ? ' disabled="disabled"' : ''; ?> />
									</td>
								</tr>
<?php

	}

?>
							</tbody>
							</table>
							<p class="clearb">*) File Limit = 0 means "default group limit".
							Set File Upload = off to disable uploading.</p>
							<div class="fsetsubmit"><input type="submit" name="revert_perms" value="Revert All to default" /></div>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="save" value="Save changes" /></p>
			</form>
		</div>
	</div>

<?php

	require PUN_ROOT.'include/footer.php';
}


$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / Admin / Boards';
require PUN_ROOT.'include/header.php';

// generate_admin_menu('boards');

?>
	<div class="blockform">
		<h2><span>Add board</span></h2>
		<div class="box">
			<form method="post" action="<?php echo $base_url ?>/admin/boards.php?action=adddel">
				<div class="inform">
					<input type="hidden" name="kind" value="<?php echo $kind ?>" />
					<input type="hidden" name="csrf_hash" value="<?php echo $csrf_hash ?>" />
					<fieldset>
						<legend>Create a new board</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
<?php

	$result = $db->query('SELECT id, cat_name FROM '.$db->prefix.'categories WHERE kind='.$kind.' ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $db->error());
	$num_categories = $db->num_rows($result);
	if ($num_categories)
	{
?>
									<th scope="row"><?php echo $lang_common['Board kind'][$kind] ?></th>
									<td><input type="text" name="forum_name" size="35" maxlength="80" value="New board" tabindex="1" /></td>
								</tr>
								<tr>
									<th scope="row">Add to category</th>
									<td>
										<select name="add_to_cat" tabindex="2">
<?php
		while ($cur_cat = $db->fetch_assoc($result))
			echo "\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'">'.pun_htmlspecialchars($cur_cat['cat_name']).'</option>'."\n";

		// Fetch existing boards of this kind
		$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.disp_position, f.owner_id, f.owner FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id WHERE c.kind='.$kind.' ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/board list', __FILE__, __LINE__, $db->error());
?>
										</select>
										<span>Select the category to which you wish to add a new board.</span>
									</td>
<?php
	}
	else
	{
?>
								<tr>
									<a href="<?php echo $base_url ?>/admin/categories.php">Create category</a> for <strong><?php echo ucfirst(basename($kinds[$kind], '.php')) ?></strong> first!
<?php
	}
?>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
<?php if ($num_categories): ?>				<p class="submittop"><input type="submit" name="add_forum" value=" Add " tabindex="3" /></p>
<?php endif; ?>			</form>
		</div>

<?php if ($num_categories && $db->num_rows($result)): ?>
		<h2 class="block2"><span>Manage boards</span></h2>
		<div class="box">
			<form id="edforum" method="post" action="<?php echo $base_url ?>/admin/boards.php?action=edit">
				<input type="hidden" name="kind" value="<?php echo $kind ?>" />
				<input type="hidden" name="csrf_hash" value="<?php echo $csrf_hash ?>" />
				<p class="submittop"><input type="submit" name="update_positions" value="Update positions" tabindex="3" /></p>
<?php

$tabindex_count = 4;

// Display all the categories and boards
$cur_category = 0;
while ($cur_board = $db->fetch_assoc($result))
{
	if ($cur_board['cid'] != $cur_category)	// A new category since last iteration?
	{
		if ($cur_category != 0)
			echo "\t\t\t\t\t\t\t".'</table>'."\n\t\t\t\t\t\t".'</div>'."\n\t\t\t\t\t".'</fieldset>'."\n\t\t\t\t".'</div>'."\n";

?>
				<div class="inform">
					<fieldset>
						<legend>Category: <?php echo pun_htmlspecialchars($cur_board['cat_name']) ?></legend>
						<div class="infldset">
							<table cellspacing="0">
<?php

		$cur_category = $cur_board['cid'];
	}

	if ($cur_board['owner_id'] > 1)
		$owner = '<a href="'.$base_url.'/profile.php?id='.$cur_board['owner_id'].'">'.pun_htmlspecialchars($cur_board['owner']).'</a>';
	else
		$owner = '-';
?>
								<tr>
									<th><a href="<?php echo $base_url ?>/admin/boards.php?clone_forum=<?php echo $cur_board['fid'].'&amp;csrf_hash='.$csrf_hash ?>">Clone</a> - <a href="<?php echo $base_url ?>/admin/boards.php?edit_forum=<?php echo $cur_board['fid'] ?>">Edit</a> - <a href="<?php echo $base_url ?>/admin/boards.php?del_forum=<?php echo $cur_board['fid'].'&amp;csrf_hash='.$csrf_hash ?>">Delete</a></th>
									<td>
										Position&nbsp;&nbsp;<input type="text" name="position[<?php echo $cur_board['fid'] ?>]" size="3" maxlength="3" value="<?php echo $cur_board['disp_position'] ?>" tabindex="<?php echo $tabindex_count ?>" />
										&nbsp;&nbsp;<strong><?php echo pun_htmlspecialchars($cur_board['forum_name']) ?></strong><br />
										Owner&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $owner ?>
									</td>
								</tr>
<?php

	$tabindex_count += 2;
}

?>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="update_positions" value="Update positions" tabindex="<?php echo $tabindex_count ?>" /></p>
			</form>
		</div>
<?php endif; ?>
	</div>
<?php

require PUN_ROOT.'include/footer.php';
