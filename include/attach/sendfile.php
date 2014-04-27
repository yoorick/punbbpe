<?php

if ($pun_user['is_guest'])
	message($lang_common['Login please']);

require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

function generate_gallery_list($forum_id, &$exists)
{
	global $db, $lang_common, $pun_user, $lang_fu, $kinds;

	$output = '<label><strong>'.$lang_fu['Send to']."</strong><em class=\"req-text\">*</em>\n\n\t\t\t\t\t".'<br /><select name="fid">'."\n";

	$result = $db->query('SELECT c.id AS cid, c.cat_name, c.kind, f.id AS fid, f.forum_name, fp.file_upload '.
		'FROM '.
		$db->prefix.'categories AS c INNER JOIN '.
		$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.
		$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') '.
		'WHERE (fp.file_upload IS NULL OR fp.file_upload=1) '.
		'ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

	$cur_category = 0;
	$exists = false;
	while ($cur_forum = $db->fetch_assoc($result))
	{
		if ($pun_user['g_file_upload'] != '1'&& $cur_forum['file_upload'] == '') continue;
		if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
		{
			if ($cur_category)
				$output .= "\t\t\t\t\t\t".'</optgroup>'."\n";

			$output .= "\t\t\t\t\t\t".'<optgroup label="'.pun_htmlspecialchars($cur_forum['cat_name']).' ('.$lang_common['Boards kind'][$cur_forum['kind']].')">'."\n";
			$cur_category = $cur_forum['cid'];
		}

		if ($forum_id == $cur_forum['fid'])
		{
			$selected = ' selected="selected"';
			$exists = true;
		}
		else
			$selected = '';

		$output .= "\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'"'.$selected.'>'.pun_htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";
	}

	$output .= "\t\t\t\t\t</optgroup>\n\t\t\t\t\t</select>\n\t\t\t\t\t\n\t\t\t\t\t</label>";

	return $output;
}

$required_fields = array('req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
$focus_element = array('post','fid');
$action = $lang_fu['Send file'];
$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / '.$action;

$fid = (!empty($_GET['sendfile']))? intval($_GET['sendfile']) : 0;

//if ($fid <= 0)
//	message($lang_common['Bad request']);

$exists = false;
$gallery_list = generate_gallery_list($fid, $exists);
if ($fid && !$exists)
	message($lang_common['Bad request']);


require PUN_ROOT.'include/header.php';

$cur_index = 1;

?>
<div class="blockform">
	<h2><span><?php echo $action ?></span></h2>
	<div class="box">
		<form id="post" method="post" action="<?php echo $base_url ?>/post.php" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}" enctype="multipart/form-data">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Write message legend'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="form_user" value="<?php echo pun_htmlspecialchars($pun_user['username']) ?>" />

						<?php echo $gallery_list ?>

						<label><strong><?php echo $lang_fu['File'] ?></strong><em class="req-text">*</em><br /><input type="file" name="attach[]" size="70"  tabindex="'.($cur_index++).'" /><br /></label>
							<p id="new_attach_rules">
								(<?php echo $lang_fu['File Upload limits'].': '.
								            sprintf($lang_fu['File Upload limits2'], round($pun_config['file_max_size']/1024)).', '.
								            sprintf($lang_fu['File Upload limits3'], $pun_config['file_max_width'], $pun_config['file_max_height']) ?>)
							</p>

						<label><strong><?php echo $lang_common['Subject'] ?></strong><em class="req-text">*</em><br /><input class="longinput" type="text" name="req_subject" value="<?php if (isset($_POST['req_subject'])) echo pun_htmlspecialchars($subject); ?>" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" /><br /></label>

						<label><strong><?php echo $lang_common['Message'] ?></strong><br />
						<p class="areafield required">
							<textarea id="req_message" name="req_message" style="height: 200px" tabindex="<?php echo $cur_index++ ?>"><?php echo isset($_POST['req_message']) ? pun_htmlspecialchars($message) : (isset($quote) ? $quote : ''); ?></textarea><br /></label>
						</p>
						<ul class="bblinks">
							<li><a href="<?php echo $base_url.'/' ?>help.php#bbcode" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a>: <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="<?php echo $base_url.'/' ?>help.php#img" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a>: <?php echo ($pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="<?php echo $base_url.'/' ?>help.php#smilies" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a>: <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
						</ul>
					</div>
				</fieldset>
			</div>
			<p><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="s" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>

<?php
require PUN_ROOT.'include/footer.php';
