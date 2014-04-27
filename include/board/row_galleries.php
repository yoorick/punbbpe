<?php
	// Should we show the "New posts" and/or the multipage links?
	if (!empty($subject_new_posts) || !empty($subject_multipage))
	{
		$subject .= '&nbsp; '.(!empty($subject_new_posts) ? $subject_new_posts : '');
		$subject .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
	}

	// Get image thumbnail
	$post = $posts[$cur_topic['id']];
	if (!isset($post['aid']))
		$image = '<img src="'.$base_url.'/img/err_none.gif" alt="error" />';
	else 
	{
		$image = '<img src="'.$base_url.'/'.require_thumb($post['aid'], $post['location'], $pun_config['file_thumb_width'], $pun_config['file_thumb_height'], true).'" alt="thumb'.$post['aid'].'" />';
		$image = '<a href="'.$base_url.'/viewtopic.php?id='.$cur_topic['id'].'#image">'.$image.'</a>';
	}

?>
				<tr<?php if ($item_status != '') echo ' class="'.trim($item_status).'"'; ?>>
					<td class="tcl">
						<div class="intd">
							<div class="<?php echo $icon_type ?>"><div class="nosize"><?php echo trim($icon_text) ?></div></div>
							<div class="tclcon">
								<?php echo $subject.' <span class="byuser">'.$lang_common['by'].'&nbsp;'.pun_htmlspecialchars($cur_topic['poster']).'</span>'."\n" ?>
								<?php if (trim($cur_topic['topic_desc']) != '') echo parse_message($cur_topic['topic_desc'], false)."\n"; ?>
							</div>
						</div>
						<?php if (!empty($labels)) echo '<div class="topiclabels">' . $lang_common['Labels'] . ': ['. show_labels($labels) . ']</div>'; ?>
					</td>
					<td class="tct">
							<div><?php echo $image ?></div>
					</td>
					<td class="tc2"><?php echo ($cur_topic['moved_to'] == null) ? $cur_topic['num_replies'] : '&nbsp;' ?></td>
					<td class="tc3"><?php echo ($cur_topic['moved_to'] == null) ? $cur_topic['num_views'] : '&nbsp;' ?></td>
					<td class="tcr"><?php echo $last_post ?></td>
				</tr>
