<?php
/***********************************************************************

  Build necessary data for Javascript popup.
  Included when $pun_config['file_popup_info'] == '1' (i.e. "Popup")
  This file is part PunBB Power Edition.

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2007       artoodetoo (master@punbb-pe.ru)

  Included from: edit.php, filemap.php, viewtopic.php

  Incoming variables:
    $attachments: array - cache of attachments records

************************************************************************/
?>
<script type="text/javascript">
<!--
	var O_BASE_URL="<?php echo $base_url ?>";

<?php
if (count($attachments))
{
	$image_height = $pun_config['file_thumb_height'];
	$image_width = $pun_config['file_thumb_width'];

	$tmp = array();
	foreach ($attachments as $attachment)
	{
		// generate preview images just-in-time
		if (preg_match('#^image/(.*)$#i', $attachment['mime'], $regs))
		{
			$thum_fname = require_thumb($attachment['id'], $attachment['location'], $image_width, $image_height, true);
			$img_size = ' ('.$regs[1].' '.$attachment['image_dim'].')';
		}
		else
		{
			$thum_fname = '';
			$img_size = '';
		}

		$tmp[] = "'".$attachment['id']."': [".
			"'".format_time($attachment['uploaded'])."',".
			"'".pun_htmlspecialchars($attachment['filename'])."',".
			"'<b>".$lang_fu['Size'].'</b>: '.round($attachment['size']/1024,1).'Kb'.$img_size.'<br /><b>'.$lang_fu['Downloads'].'</b>: '.$attachment['downloads']."','".
			$thum_fname."',".
			intval(isset($attachment['can_download'])? $attachment['can_download']: $can_download)."]";
	}
	echo "\tvar ATTACH_DATA={\n\t" . implode(",\n\t", $tmp) . "};\n";
	unset($tmp);
}
?>
//-->
</script>
<script type="text/javascript" src="<?php echo $base_url ?>/js/popup.js"></script>
<div id="pun-popup" class="punpopup" style="visibility: hidden"><p id="pun-title" class="popup-title">title</p><p id="pun-desc" class="popup-desc">Description</p><p id="pun-body" class="popup-body">Body</p></div>

