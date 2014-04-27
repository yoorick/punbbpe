<div id="cloud" class="block">
	<h2><span><?php echo $lang_common['Labels cloud'] ?></span></h2>
	<div class="box">
		<div class="inbox">
<?php
	if (!defined('PUN_LABELS_LOADED') && file_exists(PUN_ROOT.'cache/cache_labels.php'))
		include(PUN_ROOT.'cache/cache_labels.php');

	if (!defined('PUN_LABELS_LOADED'))
		echo "\t\t\t&nbsp;\n";
	else
	{
		$min = $max = 1;
		foreach($pun_labels as $k => $v)
		{			$min = min($min, $v);
			$max = max($max, $v);
		}
		$range1 = $min + intval(($max-$min)/4);
		$range2 = $min + intval(($max-$min)/2);

		echo "\t\t\t";
		foreach($pun_labels as $k => $v)
		{
			echo '<a href="'.$base_url.'/search.php?action=show_label&text='.rawurlencode($k).'" title="'.$v.
//			echo '<a href="'.$base_url.'/tags/'.rawurlencode($k).'" title="'.$v.
			  '" class="'.(($v>$range1)?(($v>$range2)?'often':'common'):'rare').'">'.
			  pun_htmlspecialchars($k).'</a>, ';
		}
		echo "\n";
	}

?>
		</div>
	</div>
</div>
