<?php
$is_firstpost = ($post_count + $start_from) == 1;

if ($is_firstpost)
	$labels = (!empty($pun_config['o_topic_labels']))? $cur_topic['labels']: '';
else
	$labels = '';

?>
<div id="p<?php echo $cur_post['id'] ?>" class="blockpost<?php echo $vtbg ?><?php if ($is_firstpost) echo ' firstpost'; ?>">
	<h2><span><span class="conr"><?php echo format_time($cur_post['posted']) ?>&nbsp;</span><a href="viewtopic.php?pid=<?php echo $cur_post['id'].'#p'.$cur_post['id'] ?>"><?php echo '#'.($start_from + $post_count) ?></a>: <strong><?php echo $username ?></strong></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postleft">
<?php
require PUN_ROOT.'include/person/userinfo.php';
?>
			</div>
			<div class="postright">
				<h3><span class="subject"><?php if (!$is_firstpost) echo ' Re: '; ?><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></span><?php if ($is_firstpost && ($cur_topic['topic_desc'] != '')) echo $cur_topic['topic_desc']; ?></h3>
				<div class="postmsg">
					<?php echo $cur_post['message']."\n" ?>
<?php
        $save_attachments = $attachments;
	$attachments = array_filter($attachments, 'filter_attachments_of_post');
	if (count($attachments)) {
		echo "\t\t\t\t\t<br />\n\t\t\t\t\t<fieldset><legend>".$lang_fu['Attachments'].'</legend>'."\n";
include PUN_ROOT.'include/attach/view_attachments.php';
		echo "\t\t\t\t\t</fieldset>\n";
	}
        $attachments = $save_attachments;
?>

<?php if ($cur_post['edited'] != '') echo "\t\t\t\t\t".'<p class="postedit"><em>'.$lang_topic['Last edit'].' '.pun_htmlspecialchars($cur_post['edited_by']).' ('.format_time($cur_post['edited']).')</em></p>'."\n"; ?>
				</div>
<?php if ($signature != '') echo "\t\t\t\t".'<div class="postsignature"><hr />'.$signature.'</div>'."\n"; ?>
				<div class="clearer"></div>
			</div>
			<div class="clearer"></div>
			<div class="postfootleft"><?php if ($cur_post['poster_id'] > 1) echo '<p>'.$is_online.'</p>'; ?></div>
			<div class="postfootright">
				<?php if (!empty($labels)) echo '<div class="topiclabels">' . $lang_common['Labels'] . ': ['. show_labels($labels) . ']</div>'; ?>
				<?php echo (count($post_actions)) ? '<ul>'.implode($lang_topic['Link separator'].'</li>', $post_actions).'</li></ul>'."\n" : '<div>&nbsp;</div>'."\n" ?>
			</div>
		</div>
	</div>
</div>

