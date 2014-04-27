<br />
<div id="quickpost" class="blockform">
	<h2><span><?php echo $lang_topic['Quick post'] ?></span></h2>
	<div class="box">
		<form id="post" method="post" action="post.php?tid=<?php echo $id ?>" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Write message legend'] ?></legend>
<?php include PUN_ROOT.'include/attach/post_buttons.php'; ?>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="form_user" value="<?php echo (!$pun_user['is_guest']) ? pun_htmlspecialchars($pun_user['username']) : 'Guest'; ?>" />
						<label><textarea id="req_message" name="req_message" style="height: 100px" tabindex="1"></textarea></label>
						<div id="msghelp">
						<script type="text/javascript">document.write('<span class="conr"><ul><li>size: <a href="#" onclick="return resize_message(100)">[+]</a>&nbsp;<a href="#" onclick="return resize_message(-100)">[-]</a>&nbsp;</li></ul></span>');</script>
						<ul class="bblinks">
							<li><a href="help.php#bbcode" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a>: <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="help.php#img" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a>: <?php echo ($pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="help.php#smilies" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a>: <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
						</ul>
						</div>
					</div>
				</fieldset>
			</div>
			<p><input type="submit" name="submit" tabindex="2" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /></p>
		</form>
	</div>
</div>
