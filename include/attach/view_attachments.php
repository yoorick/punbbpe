<?php
/***********************************************************************

  Show list of attachments in post.
  This file is part of PunBB Power Edition.

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2007       artoodetoo (master@punbb-pe.ru)

  Included from: edit.php, viewtopic.php

  Incoming variables:
    $attachments: array - cache of attachments records

************************************************************************/

if (count($attachments))
{
	echo "\t\t\t\t\t\t".'<ul class="attach_list">'."\n";

	$is_inplace = $pun_config['file_popup_info'] == '2';

	foreach ($attachments as $attachment) {
			$title = pun_htmlspecialchars($attachment['filename']);
			$aid = $attachment['id'];
			$downloads = $attachment['downloads'];
			$location = $attachment['location'];

			// in edit.php attachments has checkboxes to delete
			if ($pun_page == 'edit')
				$check = '<br /><label><input type="checkbox" name="delete_image[]" value="'.$aid.'" />'.$lang_fu['Mark to Delete'].'</label>';
			else
				$check = '';

			if ($pun_config['file_popup_info'] == '1')
			{
				$link_events = ' onmouseover="downloadPopup(event,\''.$aid.'\')"';
				$att_info = '';
			}
			else
			{
				$link_events = '';
				if ($is_inplace)
				{
					$att_info  = '<br />'.(($attachment['size']>=1048576)? (round($attachment['size']/1048576,0).'m'): (round($attachment['size']/1024,0).'k'));
					if (preg_match('#^image/(.*)$#i', $attachment['mime'], $regs))
					{
						$att_info .= ','.$regs[1].' '.$attachment['image_dim'];
						$att_info .= '<br />'.$lang_fu['Downloads'].': '.$attachment['downloads'];
						$thumbnail = '<img src="'.require_thumb($attachment['id'], $attachment['location'], $pun_config['file_thumb_width'], $pun_config['file_thumb_height'], true).'">';
						if ($can_download) {
							//$thumbnail = '<a href="'.$base_url.'/download.php?aid='.$aid.'">'.$thumbnail.'</a>';
							$thumbnail = '<a href="javascript:void(0);" onclick="{a=\'::thumb'.$aid.'::\';prompt(\'BBcode\',a);}">'.$thumbnail.'</a>';
						}
						$att_info .=  '<br />'.$thumbnail;
					}
					else
						$att_info .= '<br />downloads: '.$attachment['downloads'];
				}
				else
					$att_info = '';			}

			if ($can_download)
				echo "\t\t\t\t\t\t\t\t".'<li'.(($is_inplace)? ' class="att_info"':'').'><a href="'.$base_url.'/download.php?aid='.$aid.'"'.$link_events.' class="att_filename">'.$title.'</a>'.$att_info.$check.'</li>'."\n";
			else
				echo "\t\t\t\t\t\t\t\t".'<li'.(($is_inplace)? ' class="att_info"':'').$link_events.'><span class="att_filename">'.$title.'</span>'.$att_info.$check.'</li>'."\n";
	}

	echo "\t\t\t\t\t\t</ul>\n\t\t\t\t\t\t<div class=\"clearer\"></div>\n";
}