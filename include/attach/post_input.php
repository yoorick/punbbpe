<?php
/***********************************************************************

  Show list of file input fields in post and edit form.
  This file is part of PunBB Power Edition.

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2007       artoodetoo (master@punbb-pe.ru)

  Included from: edit.php, post.php

  Incoming variables:
    $attachments: array        - cache of attachments records
    $can_upload: boolean       - can user upload the file here
    $file_limit: integer       - how much files user can attach here
    $uploaded_to_post: integer - how much files is uploaded (if edit)

************************************************************************/

?>
			<div class="infldset txtarea">

				<div id="divAttachRules" style="display:none;">
						<?php echo $lang_fu['File Upload limits'].': '.
							sprintf($lang_fu['File Upload limits1'], $file_limit).', '.
							sprintf($lang_fu['File Upload limits2'], round($pun_config['file_max_size']/1024)).', '.
							sprintf($lang_fu['File Upload limits3'], $pun_config['file_max_width'], $pun_config['file_max_height']).', '.
							sprintf($lang_fu['File Upload limits4'], $pun_config['file_allowed_ext']) ?>
				</div>


				<div id="divImage1">
					<label id="lblFileOne"    style="display:inline;"><strong><?php echo $lang_fu['Choose a file'] ?></strong></label>
					<label id="lblFileFive"   style="display:none;"  ><strong><?php echo $lang_fu['Choose a few files'] ?></strong></label>
					<label id="lblFileTwenty" style="display:none;"  ><strong><?php echo $lang_fu['Choose a whole bunch of files'] ?></strong></label>

					<span id="lblShowQuota"  style="display:inline;">(<a href="javascript:void(0);" onclick="toggle('lblHideQuota', 'lblShowQuota', 'divAttachRules');"><?php echo $lang_fu['show quota'] ?></a>)</span>
					<span id="lblHideQuota"  style="display:none;"  >(<a href="javascript:void(0);" onclick="toggle('lblShowQuota', 'lblHideQuota', 'divAttachRules');"><?php echo $lang_fu['hide quota'] ?></a>)</span>

					<br class="clearb" />

					<div class="floated" id="input_1"><span><a href="#" onclick="return insert_text('',' ::thumb$1:: ');">#1</a>&nbsp;</span><input type="file" name="attach[]" size="50"  tabindex="<?php echo $cur_index++ ?>" /></div>
<?php
if ($num_to_upload >= 2)
{?>
					<div id="addMoreFiles1" class="fine_print">
						<a href="javascript:void(0);" onclick="toggle('lblFileOne', 'lblFileFive', 'addMoreFiles1', 'divImage2');"><?php echo $lang_fu['Add more files'] ?></script></a>
					</div>
				</div>

				<div id="divImage2" style="display:none;">
<?php
	for ($i=2; $i<=min(5,$num_to_upload); $i++)
		echo "\t\t\t\t\t".'<div class="floated" id="input_'.$i.'"><span><a href="#" onclick="return insert_text(\'\',\' ::thumb$'.$i.':: \');">#'.$i.'</a>&nbsp;</span><input type="file" name="attach[]" size="50"  tabindex="'.($cur_index++).'" /></div>'."\n";
?>

					<div id="addMoreFiles2" class="fine_print">
<?php	if ($num_to_upload > 5) { ?>
						<a href="javascript:void(0);" onclick="toggle('lblFileFive', 'lblFileTwenty', 'addMoreFiles2', 'divImage3');"><?php echo $lang_fu['Add even more here'] ?></a>
						(<?php echo $lang_fu['or just'] ?> <a href="javascript:void(0);" onclick="toggle('lblFileOne', 'lblFileFive', 'addMoreFiles1', 'divImage2');"><?php echo $lang_fu['one slot'] ?></a>)
<?php	} else { ?>						<a href="javascript:void(0);" onclick="toggle('lblFileOne', 'lblFileFive', 'addMoreFiles1', 'divImage2');"><?php echo $lang_fu['Upload just one'] ?></a>
<?php	} ?>
					</div>
				</div>
<?php
	if ($num_to_upload > 5)
	{?>

				<div id="divImage3" class="inputArea" style="display:none;">
<?php
		for ($i=6; $i <= $num_to_upload; $i++)
			echo "\t\t\t\t\t".'<div class="floated" id="input_'.$i.'"><span><a href="#" onclick="return insert_text(\'\',\' ::thumb$'.$i.':: \');">#'.$i.'</a>&nbsp;</span><input type="file" name="attach[]" size="50"  tabindex="'.($cur_index++).'" /></div>'."\n";
?>

					<div id="addMoreFiles3" class="fine_print">
						<a href="javascript:void(0);" onclick="toggle('lblFileOne', 'lblFileTwenty', 'addMoreFiles1', 'addMoreFiles2', 'divImage2', 'divImage3');"><?php echo $lang_fu['Upload just one'] ?></a>
					</div>
				</div>
<?php
	}
}
else
{
?>

				</div>
<?php
}
?>
				<div class="clearer"></div>
			</div>
