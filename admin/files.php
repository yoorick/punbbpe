<?php
/***********************************************************************

  This file is part of PunBB Power Edition
  Copyright (C) 2007 artoodetoo (master@punbb-pe.ru)

  File Upload file permissions.

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

if (isset($_POST['show_errors']) || isset($_POST['delete_orphans']) || isset($_POST['delete_thumbnails']) || isset($_POST['fix_counters']))
{
	require PUN_ROOT.'include/file_upload.php';
}

// If the "Show text" button was clicked
if (isset($_POST['save']))
{
	confirm_referrer('admin/files.php');

	$form = array_map('trim', $_POST['form']);

	// Error checking
	if ($form['upload_path'] == '')
		message('You must enter an upload path.', true);

	if (realpath(PUN_ROOT.$form['upload_path']) === false)
		message('Upload path you entered isn\'t a valid directory.', true);

	if ($form['thumb_path'] == '')
		message('You must enter a thumbnail path.', true);

	if (!is_writable(PUN_ROOT.$form['upload_path']))
		message('Upload path isn\'t writable.', true);

	if (!is_dir(PUN_ROOT.$form['upload_path']))
		message('Upload path you entered isn\'t a valid directory.', true);

	if (realpath(PUN_ROOT.$form['thumb_path']) === false)
		message('Thumbnail path you entered isn\'t a valid directory.', true);

	if (!is_writable(PUN_ROOT.$form['thumb_path']))
		message('Thumbnail path isn\'t writable.', true);

	if (!is_dir(PUN_ROOT.$form['thumb_path']))
		message('Thumbnail path you entered isn\'t a valid directory.', true);

	$form['max_width'] = intval($form['max_width']);
	if ($form['max_width'] <= 0)
		message('Invalid maximum image width.', true);

	$form['max_height'] = intval($form['max_height']);
	if ($form['max_height'] <= 0)
		message('Invalid maximum image height.', true);

	$form['max_size'] = intval($form['max_size']);
	if ($form['max_size'] <= 0)
		message('Invalid maximum image size.', true);

	$form['thumb_width'] = intval($form['thumb_width']);
	if ($form['thumb_width'] <= 0)
		message('Invalid thumbnail width.', true);

	$form['thumb_height'] = intval($form['thumb_height']);
	if ($form['thumb_height'] <= 0)
		message('Invalid thumbnail height.', true);

	$form['preview_width'] = intval($form['preview_width']);
	if ($form['preview_width'] <= 0)
		message('Invalid preview width.', true);

	$form['preview_height'] = intval($form['preview_height']);
	if ($form['preview_height'] <= 0)
		message('Invalid preview height.', true);

	$form['first_only'] = (isset($form['first_only'])&& $form['first_only']=='1')? '1': '0';

	$form['max_post_files'] = intval($form['max_post_files']);
	if ($form['max_post_files'] <= 0)
		message('Invalid maximum files per post.', true);

	$form['allowed_ext'] = strtolower($form['allowed_ext']);

	while (list($key, $input) = @each($form))
	{
		// Only update values that have changed
		if (array_key_exists('file_'.$key, $pun_config) && $pun_config['file_'.$key] != $input)
		{
			if ($input != '' || is_int($input))
				$value = '\''.$db->escape($input).'\'';
			else
				$value = 'NULL';

			$db->query('UPDATE '.$db->prefix.'config SET conf_value='.$value.' WHERE conf_name=\'file_'.$db->escape($key).'\'') or error('Unable to update board config', __FILE__, __LINE__, $db->error());
		}
	}

	// Regenerate the config cache
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();

	redirect($base_url.'/admin/files.php', 'Options updated. Redirecting &hellip;');
}
else	// If not, we show the "Show text" form
{
	$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / Admin / Files';
	$focus_element = array('files', 'form[upload_path]');
	require PUN_ROOT.'include/header.php';

	// Display the admin navigation menu
//	generate_admin_menu('files');

?>

<?php
				if (isset($_POST['show_errors'])) {
					confirm_referrer('admin/files.php');

					$log = show_problems();
?>
	<div id="imageupload" class="blockform">
		<h2><span>Error Checker Report</span></h2>
			<div class="box">
				<div class="inform">
					<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<td>
									<?php echo implode("<br />\n", $log); ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
	</div>
	<br />
<?php
				}

				if (isset($_POST['delete_orphans'])) {
					confirm_referrer('admin/files.php');

					$log = delete_orphans();
?>
	<div id="imageupload" class="blockform">
		<h2><span>Orphan Clean-up Report</span></h2>
			<div class="box">
				<div class="inform">
					<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<td>
									<?php echo implode("<br />\n", $log); ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
	</div>
	<br />
<?php
				}

				if (isset($_POST['delete_thumbnails'])) {
					confirm_referrer('admin/files.php');

					$log = delete_all_thumbnails();
?>
	<div id="imageupload" class="blockform">
		<h2><span>Thumbnails Clean-up Report</span></h2>
			<div class="box">
				<div class="inform">
					<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<td>
									<?php echo implode("<br />\n", $log); ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
	</div>
	<br />
<?php
				}

				if (isset($_POST['fix_counters'])) {
					confirm_referrer('admin/files.php');

					$log = fix_user_counters();
?>
	<div id="imageupload" class="blockform">
		<h2><span>Fix User Counters Report</span></h2>
			<div class="box">
				<div class="inform">
					<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<td>
									<?php echo implode("<br />\n", $log); ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
	</div>
	<br />
<?php
				}
?>

	<div id="imageupload" class="blockform">
		<h2><span>File Options</span></h2>
			<div class="box">
				<form id="files" method="post" action="<?php echo $base_url ?>/admin/files.php">
					<p class="submittop"><input type="submit" name="save" value="Save changes" /></p>
					<div class="inform">
						<input type="hidden" name="csrf_hash" value="<?php echo csrf_hash() ?>" />
						<input type="hidden" name="form_sent" value="1" />
						<fieldset>
							<legend>General Settings</legend>
							<div class="infldset">
								<table class="aligntop" cellspacing="0">
									<tr>
										<th scope="row">Upload Directory</th>
										<td>
											<input type="text" name="form[upload_path]" size="50" maxlength="255" value="<?php echo $pun_config['file_upload_path']; ?>" />
											<span>This is the relative path to the directory where all files are uploaded. Be sure that the web server is able to write to it, but preferably not serve content from it (for security reasons).</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Thumbnail Directory</th>
										<td>
											<input type="text" name="form[thumb_path]" size="50" maxlength="255" value="<?php echo $pun_config['file_thumb_path']; ?>" />
											<span>This is the relative path to the directory where all thumbnails of images will create. Be sure that the web server is able to write to it and read from it.</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Allowed Extensions</th>
										<td>
											<input type="text" name="form[allowed_ext]" size="50" maxlength="255" value="<?php echo $pun_config['file_allowed_ext']; ?>" />
											<span>This is a list of all extensions that will be accepted.</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Max size</th>
										<td>
											<input type="text" name="form[max_size]" size="8" maxlength="8" value="<?php echo $pun_config['file_max_size']; ?>" />
											<span>The maximum allowed size of files in bytes.</span>
										</td>
									</tr>
								</table>
							</div>
						</fieldset>
					</div>
					<div class="inform">
						<fieldset>
							<legend>Image Settings</legend>
							<div class="infldset">
								<table class="aligntop" cellspacing="0">
									<tr>
										<th scope="row">Image Extensions</th>
										<td>
											<input type="text" name="form[image_ext]" size="50" maxlength="255" value="<?php echo $pun_config['file_image_ext']; ?>" />
											<span>This is a list of all extensions that accepted as images. It have to be subset of <strong>Allowed Extensions</strong>.</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Max dimension</th>
										<td>
											<input type="text" name="form[max_width]" size="5" maxlength="5" value="<?php echo $pun_config['file_max_width']; ?>" /> x
											<input type="text" name="form[max_height]" size="5" maxlength="5" value="<?php echo $pun_config['file_max_height']; ?>" />
											<span>The maximum image width & height. Images wider then this value will be discarded, but it does not affect those that are already uploaded.<br />
											Images exceeding this limit will not uploads.</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Thumbnail dimension</th>
										<td>
											<input type="text" name="form[thumb_width]" size="5" maxlength="5" value="<?php echo $pun_config['file_thumb_width']; ?>" /> x
											<input type="text" name="form[thumb_height]" size="5" maxlength="5" value="<?php echo $pun_config['file_thumb_height']; ?>" />
											<span>The maximum width & height of thumbnail images. Changing this value will not affect already generated images.<br />
											Thumbnail will generates in <strong>Thumbnail Directory</strong> just in time of first request.</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Preview dimension</th>
										<td>
											<input type="text" name="form[preview_width]" size="5" maxlength="5" value="<?php echo $pun_config['file_preview_width']; ?>" /> x
											<input type="text" name="form[preview_height]" size="5" maxlength="5" value="<?php echo $pun_config['file_preview_height']; ?>" />
											<span>The maximum width & height of preview images. Changing this value will not affect already generated images.<br />
											Previews will generates in <strong>Thumbnail Directory</strong> just in time of first request.</span>
										</td>
									</tr>
								</table>
							</div>
						</fieldset>
					</div>
					<div class="inform">
						<fieldset>
							<legend>Post View Settings</legend>
							<div class="infldset">
								<table class="aligntop" cellspacing="0">
									<tr>

										<th scope="row">First post only</th>
										<td>
											<input type="checkbox" name="form[first_only]" value="1" <?php echo ($pun_config['file_first_only']=='1') ? ' checked="checked"' : ''; ?> />&nbsp;Attachment allowed in first post only.
											<span>Select this to restrict attachments in comments.</span>
										</td>
									</tr>
									<tr>

										<th scope="row">Attachment info</th>
										<td>
											<input type="radio" name="form[popup_info]" value="0"<?php if ($pun_config['file_popup_info'] == '0') echo ' checked="checked"' ?> />&nbsp;None&nbsp;&nbsp;&nbsp;<input type="radio" name="form[popup_info]" value="1"<?php if ($pun_config['file_popup_info'] == '1') echo ' checked="checked"' ?> />&nbsp;Popup&nbsp;&nbsp;&nbsp;<input type="radio" name="form[popup_info]" value="2"<?php if ($pun_config['file_popup_info'] == '2') echo ' checked="checked"' ?> />&nbsp;Inplace
											<span>Select the method for displaying attachment info including thumbnail. You can choose no info at all, info in popup panel or static text and thumbnail in post.</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Attachments per post</th>
										<td>
											<input type="text" name="form[max_post_files]" size="6" maxlength="6" value="<?php echo $pun_config['file_max_post_files']; ?>" />
											<span>The maximum number of files for any post.</span>
										</td>
									</tr>
								</table>
							</div>

						</fieldset>
					</div>
					<p class="submitend"><input type="submit" name="save" value="Save changes" /></p>
					<div class="inform">
						<fieldset>
							<legend>Tools</legend>
							<div class="infldset">
								<table class="aligntop" cellspacing="0">
									<tr>
										<th scope="row">Error Checker</th>
										<td>
											<input type="submit" name="show_errors" value="Scan for Errors" />
											<span>This function is used to check the upload & thumbnails directories for any problems. Under normal operation, there should be none, but if the directories was modified by hand, this may help find certain problems.</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Orphan Clean-up</th>
										<td>
											<input type="submit" name="delete_orphans" value="Delete Orphans" />
											<span>Delete files that aren't attached to any post.<br />
											Also it removes 'broken links' to missing files.</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Thumbnails Clean-up</th>
										<td>
											<input type="submit" name="delete_thumbnails" value="Delete Thumbnails" />
											<span>Delete all generated thumbnails.<br />
											The new thumbnails will generate just in time.</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Fix user counters</th>
										<td>
											<input type="submit" name="fix_counters" value="Count" />
											<span>Count attachments by users and store them. It can take a long time.</span>
										</td>
									</tr>
								</table>
							</div>

						</fieldset>
					</div>
				</form>
			</div>
	</div>

<?php

}

require PUN_ROOT.'include/footer.php';

