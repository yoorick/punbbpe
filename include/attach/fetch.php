<?php
/***********************************************************************

  Get list of attachments.
  This file is part of PunBB Power Edition.

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2007       artoodetoo (master@punbb-pe.ru)

  Included from: edit.php, viewtopic.php

  Incoming variables;

  Outgoing variables:
    $attachments: array - cache of attachments records

************************************************************************/

// there are different sources to include fetch.php
switch ($pun_page)
{
  case 'viewboard':
	// fetch only first attachment of first post
        $att_sql = 'SELECT * FROM '.$db->prefix.'attachments WHERE post_id in ('.implode(',',$pids).') ORDER BY uploaded ASC';
	break;
  case 'viewtopic':
        $att_sql = 'SELECT * FROM '.$db->prefix.'attachments WHERE post_id in ('.implode(',',$pids).') ORDER BY uploaded ASC';
	break;
  case 'edit':
        $att_sql = 'SELECT * FROM '.$db->prefix.'attachments WHERE post_id='.$id.' ORDER BY uploaded ASC';
	break;
}

// prepare attachments cache data
$attachments = array();
$result = $db->query($att_sql, true) or error('Unable to fetch attachments', __FILE__, __LINE__, $db->error());
while ($attachment = $db->fetch_assoc($result)) $attachments[] = $attachment;
$db->free_result($result);

