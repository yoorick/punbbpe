<?php
/***********************************************************************

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)

  This file is part of PunBB+PE.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/


// Tell header.php to use the help template
// because we don't want to edit header.php lets use the standard header template

define('PUN_ROOT', './');

include PUN_ROOT.'include/config.php';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="<?php echo $base_url ?>/style/Cold_PE.css" />
<style>
body, #punwrap {width: 100%; margin: 0; padding: 0;}
</style>
<title>Smilies</title>

<script type="text/javascript">
<!--
	function insert_text(open, close)
	{
		var docOpener = window.opener.document;

		msgfield = docOpener.getElementsByName("req_message").item(0);

		// artoodetoo: add extra spaces
		open = ' ' + open
		close = close + ' '

		// IE support
		if (docOpener.selection && docOpener.selection.createRange)
		{
			msgfield.focus();
			sel = docOpener.selection.createRange();
			sel.text = open + sel.text + close;
			msgfield.focus();
		}

		// Moz support
		else if (msgfield.selectionStart || msgfield.selectionStart == '0')
		{
			var startPos = msgfield.selectionStart;
			var endPos = msgfield.selectionEnd;

			msgfield.value = msgfield.value.substring(0, startPos) + open + msgfield.value.substring(startPos, endPos) + close + msgfield.value.substring(endPos, msgfield.value.length);
			msgfield.selectionStart = msgfield.selectionEnd = endPos + open.length + close.length;
			msgfield.focus();
		}

		// Fallback support for other browsers
		else
		{
			msgfield.value += open + close;
			msgfield.focus();
		}

		window.close();
		return;
	}
-->
</script>

</head>
<body>

<div id="punwrap">
<div id="smilies" class="pun">

<div id="smileyblock" class="blocktable">
	<!-- <h2><span>Smilies</span></h2> -->
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<!--
			<thead>
				<tr>
					<th class="tcl" scope="col">Text</th>
					<th class="tcr" scope="col">Image</th>
				</tr>
			</thead>
			-->
			<tbody>
<?php

// Display the smiley set
require PUN_ROOT.'include/smilies.php';

$smiley_dups = array();
$num_smilies = count($smiley_text);

for ($i = 0; $i < $num_smilies; ++$i)
{
	// Is there a smiley at the current index?
	if (!isset($smiley_text[$i]))
		continue;

	if (!in_array($smiley_img[$i], $smiley_dups))
	{
?>
 				<tr>

					<td class="tcl"><?php echo $smiley_text[$i] ?></td>
					<td class="tcr"><a href="javascript:insert_text('<?php echo $smiley_text[$i] ?>', '');"><img src="img/smilies/<?php echo $smiley_img[$i] ?>" alt="" /></a></td>
				</tr>
<?php
	}

	$smiley_dups[] = $smiley_img[$i];

}

?>

			</tbody>
			</table>
		</div>
	</div>
</div>

</div>
</div>

</body>
</html>

<?php
