<?php
$is_firstpost = ($post_count + $start_from) == 1;

if ($is_firstpost)
	$labels = (!empty($pun_config['o_topic_labels']))? $cur_topic['labels']: '';
else
	$labels = '';

$save_attachments = $attachments;
$attachments = array_filter($attachments, 'filter_attachments_of_post');
//var_dump($attachments); die();
?>
<div id="p<?php echo $cur_post['id'] ?>" class="blockpost<?php echo $vtbg ?><?php if ($is_firstpost) echo ' firstpost'; ?>">
	<h2><span><span class="conr"><?php echo format_time($cur_post['posted']) ?>&nbsp;</span><a href="viewtopic.php?pid=<?php echo $cur_post['id'].'#p'.$cur_post['id'] ?>"><?php echo '#'.($start_from + $post_count) ?></a>: <strong><?php echo $username ?></strong></span></h2>
	<div class="box">
		<div class="inbox">
<?php
if ($is_firstpost)
{
	$preview = current($attachments);
?>
<?php if ($prev_topic): ?>				<div class="prev-nav"><a href="viewtopic.php?id=<?php echo $prev_topic['id'].'#image" title="'.pun_htmlspecialchars($prev_topic['subject']).'"' ?>>&laquo; <?php echo $lang_topic['Previous'] ?><br /><img src="<?php echo $base_url.'/'.require_thumb($prev_topic['aid'], $prev_topic['location'], $pun_config['file_thumb_width'], $pun_config['file_thumb_height'], true).' " alt="'.pun_htmlspecialchars($prev_topic['subject']).'"' ?> /></a></div>
<?php endif; ?>
<?php if ($next_topic): ?>				<div class="next-nav"><a href="viewtopic.php?id=<?php echo $next_topic['id'].'#image" title="'.pun_htmlspecialchars($next_topic['subject']).'"' ?>><?php echo $lang_topic['Next'] ?> &raquo;<br /><img src="<?php echo $base_url.'/'.require_thumb($next_topic['aid'], $next_topic['location'], $pun_config['file_thumb_width'], $pun_config['file_thumb_height'], true).' " alt="'.pun_htmlspecialchars($next_topic['subject']).'"' ?> /></a></div>
<?php endif; ?>
			<div id="image">

<script type="text/javascript">
<!--
function changeBg(o)
{
    div = document.getElementById("image");
    div.style.backgroundColor = o.style.backgroundColor;
    return false;
}
// -->
</script>
					<div id="gradient">
						<a href="#" style="background-color:#FFFFFF;" onclick="return changeBg(this);">&nbsp; &nbsp;</a>
						<a href="#" style="background-color:#E5E5E5;" onclick="return changeBg(this);">&nbsp; &nbsp;</a>
						<a href="#" style="background-color:#CCCCCC;" onclick="return changeBg(this);">&nbsp; &nbsp;</a>
						<a href="#" style="background-color:#B3B3B3;" onclick="return changeBg(this);">&nbsp; &nbsp;</a>
						<a href="#" style="background-color:#999999;" onclick="return changeBg(this);">&nbsp; &nbsp;</a>
						<a href="#" style="background-color:#808080;" onclick="return changeBg(this);">&nbsp; &nbsp;</a>
						<a href="#" style="background-color:#666666;" onclick="return changeBg(this);">&nbsp; &nbsp;</a>
						<a href="#" style="background-color:#4D4D4D;" onclick="return changeBg(this);">&nbsp; &nbsp;</a>
						<a href="#" style="background-color:#333333;" onclick="return changeBg(this);">&nbsp; &nbsp;</a>
						<a href="#" style="background-color:#1A1A1A;" onclick="return changeBg(this);">&nbsp; &nbsp;</a>
						<a href="#" style="background-color:#000000;" onclick="return changeBg(this);">&nbsp; &nbsp;</a>
					</div>
					<a href="<?php echo $base_url.'/download.php?aid='.$preview['id'] ?>"><img src="<?php echo $base_url.'/'.require_thumb($preview['id'], $preview['location'], $pun_config['file_preview_width'], $pun_config['file_preview_height']) ?>" alt="<?php echo pun_htmlspecialchars($cur_topic['subject']) ?>" /></a>
			</div>
<?php
}
?>
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

