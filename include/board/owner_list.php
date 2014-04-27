<div class="block">
	<h2><span></span><?php echo $lang_common['Info'] ?></h2>
	<div class="box">
		<div class="inbox">
			<p><?php
echo sprintf($lang_index['See personal boards'], $lang_common['Boards kind'][$kind]).': ';
for ($i=0; $i<count($board_owners); $i++)
	$refs[] = '<a href="'.$base_url.'/'.basename($kinds[$kind]).'?user_id='.$board_owners[$i]['id'].'" class="'.($board_owners[$i]['is_team']? 'team' : 'user').'">'.pun_htmlspecialchars($board_owners[$i]['username']).'</a>'; // '('.$board_owners[$i]['cnt'].')';
echo implode(', ', $refs);
?>
			</p>
		</div>
	</div>
</div>
