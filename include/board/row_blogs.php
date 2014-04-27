<?php
	$cur_post = ($cur_topic['moved_to'] == null)? $posts[$cur_topic['id']] : null;
	$poster = pun_htmlspecialchars($cur_topic['poster']);
	$topic_url = $base_url.'/viewtopic.php?id='.$cur_topic['id'];

	if (isset($cur_topic['forum_name']))
		$at_board = ' @ <a href="'.$base_url.'/viewboard.php?id='.$cur_topic['forum_id'].'">'.pun_htmlspecialchars($cur_topic['forum_name']).'</a>';
	else
		$at_board = '';

	if (isset($cur_post['poster_id']) && ($cur_post['poster_id'] > 1))
		$poster = '<a href="profile.php?id='.$cur_post['poster_id'].'" class="user">'.$poster.'</a>';

?>
			<div class="blogtopic postmsg<?php echo $item_status.$vtbg ?>">
				<div class="blogtopicbody">
					<div class="<?php echo $icon_type ?>"><div class="nosize"><?php echo trim($icon_text) ?></div></div>
					<div class="tclcon">
						<?php echo $subject."\n" ?>
					</div>
					<p class="author"><?php echo format_time($cur_topic['posted']).' <span class="byuser">'.$lang_common['by'].'&nbsp;'.$poster.'</span> '.$at_board ?></p>
<?php
	if ($cur_topic['moved_to'] == null)
	{
/*
		if (trim($cur_topic['topic_desc']) != '')
		{
			$cur_post['message'] = $cur_topic['topic_desc'];
			smartcut($cur_post['message']);
			$was_cut = true;
		}
		else
		{
			$was_cut = smartcut($cur_post['message']);
		}
*/
		$was_cut = smartcut($cur_post['message']);
		echo "\t\t\t\t\t".parse_message($cur_post['message'], !empty($cur_post['hide_smilies'])).($was_cut ? ('<a class="more" href="'.$topic_url.'">'.$lang_common['continue reading'].'</a>') : '');
?>

					<div class="clearer"></div>
				</div>
				<div class="blogtopicfoot">
<?php
		$views = '<a href="'.$topic_url.'" title="'.$lang_common['Views'].': '.$cur_topic['num_views'].'">'.$lang_board['Permalink'].'</a>';
		$replies = $lang_board['Comments'].($cur_topic['num_replies']? (' ('.$cur_topic['num_replies'].')') : '');
		$replies = '<a href="'.$base_url.'/viewtopic.php?pid='.$cur_topic['last_post_id'].'#p'.$cur_topic['last_post_id'].'" title="'.$lang_common['Replies'].': '.$cur_topic['num_replies'].'">'.$replies.'</a>';
		// Should we show the "New posts" and/or the multipage links?
		if (!empty($subject_new_posts) || !empty($subject_multipage))
		{
			$replies .= '&nbsp; '.(!empty($subject_new_posts) ? $subject_new_posts : '');
			$replies .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
		}

		echo "\t\t\t\t\t";
		if (!empty($labels)) echo '<div class="topiclabels">' . $lang_common['Labels'] . ': ['. show_labels($labels) . ']</div>';
		echo '<div class="comments">'.$views.' | '.$replies.'</div>';
		echo "\n";
	}
?>
				</div>
			</div>
			<div class="clearer"></div>

