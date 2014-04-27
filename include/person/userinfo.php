				<dl>
					<dd class="postavatar"><?php echo $user_avatar ?></dd>
					<dd class="usertitle"><strong><?php echo $user_title ?></strong></dd>
					<dd class="userteams"><?php echo $user_badges ?></dd>
<?php if (count($user_info)) echo "\t\t\t\t\t".implode('</dd>'."\n\t\t\t\t\t", $user_info).'</dd>'."\n"; ?>
<?php if (count($user_contacts)) echo "\t\t\t\t\t".'<dd class="usercontacts">'.implode('&nbsp;&nbsp;', $user_contacts).'</dd>'."\n"; ?>
				</dl>
