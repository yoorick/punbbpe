<?php
/***********************************************************************

  Download an attachment.
  This file is part of Elektra File Upload mod for PunBB.

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2007       artoodetoo (master@1wd.ru)

************************************************************************/

define('PUN_ROOT', './');
define('PUN_DISABLE_BUFFERING', 1); // !!! important
define('CHUNKSIZE', 10240);

require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);

if (!isset($_GET['aid']))
	error('Invalid image parameters', __FILE__, __LINE__);
$aid = intval($_GET['aid']);

// Retrieve attachment info and permissions
$result_attach = $db->query('SELECT a.filename, a.location, a.mime, a.uploaded, a.size, p.poster_id, f.moderators, fp.file_download '.
	'FROM '.$db->prefix.'attachments AS a INNER JOIN '.
		$db->prefix.'posts AS p ON p.id=a.post_id INNER JOIN '.
		$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.
		$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.
		$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$pun_user['g_id'].') '.
	'WHERE a.id='.$aid) or error('Unable to fetch if there were any attachments to the post', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result_attach))
	error('There are no attachment or access denied', __FILE__, __LINE__);

list($file, $location, $mime, $uploaded, $size, $poster_id, $moderators, $file_download) = $db->fetch_row($result_attach);
if (!$uploaded)
	$uploaded = time();

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = array();
if ($moderators != '')
	$mods_array = unserialize($moderators);
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_id'] == PUN_MOD && array_key_exists($pun_user['username'], $mods_array))) ? true : false;
$can_download = ($file_download == '' && $pun_user['g_file_download'] == '1') || $file_download == '1' || $is_admmod;


// author of post always can download his attachments
// other users can has rights or not
if (!$can_download && !($poster_id == $pun_user['id'])) {
	if (preg_match('#^image/#i', $mime)) {
		// show noaccess icon instead of image
		header('Location: '.$base_url.'/'.$pun_config['file_thumb_path'].'err_access.gif');
		exit;
	}
	else
	{
		header ('HTTP/1.0 403 Forbidden');
		echo '403 Access denied';
		exit;
	}
}

if (!is_file($location))
{
	header ('HTTP/1.0 404 Not Found');
	echo '404 File not found';
	exit;
}

$file_handler = @fopen($location, 'rb');

$range = 0;
if (isset($_SERVER['HTTP_RANGE']))
{
	$range = $_SERVER['HTTP_RANGE'];
	$range = str_replace('bytes=', '', $range);
	$range = intval(str_replace('-', '', $range));

	if ($range)
		fseek($file_handler, $range);

	header('HTTP/1.1 206 Partial Content');
	header('Accept-Ranges: bytes');
	header('Content-Range: bytes '.$range.'-'.($size-1).'/'.$size);
}
else
	header('HTTP/1.1 200 OK');

header('Content-type: '.$mime);
header('Content-Disposition: '.(preg_match('#^image/#i', $mime) ? 'inline' : 'attachment').'; filename="'.$file.'";');
header('Last-Modified: '.date('D, d M Y H:i:s T', $uploaded));
header('Content-Length: '.$size);

@set_time_limit(0);
while (!feof($file_handler) && !connection_status())
{
	echo fread($file_handler, CHUNKSIZE);

	// Just in case flush the output buffer
	@ob_flush();
	@flush();

	$range += CHUNKSIZE;

	// Prevent server high load
	sleep(1);
}

fclose($file_handler);


// When download finished
if ($range >= $size)
{
/* todo: add download log
	  INSERT INTO attach_downloads(attach_id, user_id, user_name, ip, referer, downloaded) VALUES(...)

*/
	// do not update counter when poster get its own file
	if ($pun_user['is_guest'] || $poster_id != $pun_user['id'])
		$db->query('UPDATE '.$db->prefix.'attachments SET downloads=downloads+1 WHERE id='.$aid);
}


// End the transaction
$db->end_transaction();
$db->close();


?>