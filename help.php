<?php
/***********************************************************************

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)

  This file is part of PunBB.

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

************************************************************************

  Collapsed help categories implemented for PunBB PE
  (c) 2007 artoodetoo (master@punbb-pe.org.ru)

************************************************************************/


// Tell header.php to use the help template
define('PUN_HELP', 1);

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


// Load the help.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/help.php';


$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / '.$lang_help['Help'];
require PUN_ROOT.'include/header.php';

$QnA = array(
	$lang_common['BBCode'] =>
		'<p><a name="bbcode"></a>'.$lang_help['BBCode info 1'].'</p><br />'.
		'<p>'.$lang_help['BBCode info 2'].'</p>',

	$lang_help['Text style'] =>
		'<p>'.$lang_help['Text style info'].'</p><br />'.
		'<div style="padding-left: 4px">'.
			'[b]'.$lang_help['Bold text'].'[/b] '.$lang_help['produces'].' <b>'.$lang_help['Bold text'].'</b><br />'.
			'[u]'.$lang_help['Underlined text'].'[/u] '.$lang_help['produces'].' <span class="bbu">'.$lang_help['Underlined text'].'</span><br />'.
			'[i]'.$lang_help['Italic text'].'[/i] '.$lang_help['produces'].' <i>'.$lang_help['Italic text'].'</i><br />'.
			'[color=#FF0000]'.$lang_help['Red text'].'[/color] '.$lang_help['produces'].' <span style="color: #ff0000">'.$lang_help['Red text'].'</span><br />'.
			'[color=blue]'.$lang_help['Blue text'].'[/color] '.$lang_help['produces'].' <span style="color: blue">'.$lang_help['Blue text'].'</span>'.
		'</div>',

	$lang_help['Links and images'] =>
		'<p>'.$lang_help['Links info'].'</p><br />'.
		'<div style="padding-left: 4px">'.
			'<p><strong>[url='.$base_url.'/]</strong>'.pun_htmlspecialchars($pun_config['o_board_title']).'<strong>[/url]</strong> '.$lang_help['produces'].' <a href="'.$base_url.'/'.'">'.pun_htmlspecialchars($pun_config['o_board_title']).'</a></p>'.
			'<p><strong>[url]</strong>'.$base_url.'/'.'<strong>[/url]</strong> '.$lang_help['produces'].' <a href="'.$base_url.'">'.$base_url.'/'.'</a></p>'.
			'<p><strong>[email]</strong>myname@mydomain.com<strong>[/email]</strong> '.$lang_help['produces'].' <a href="mailto:myname@mydomain.com">myname@mydomain.com</a></p>'.
			'<p><strong>[email=myname@mydomain.com]</strong>'.$lang_help['My e-mail address'].'<strong>[/email]</strong> '.$lang_help['produces'].' <a href="mailto:myname@mydomain.com">'.$lang_help['My e-mail address'].'</a><br /></p>'.
		'</div>'.
		'<p><a name="img"></a>'.$lang_help['Images info'].'</p>'.
		'<div><strong>[img]</strong>'.$base_url.'/style/Cold_PE/img/cold-logo.jpg<strong>[/img]</strong> '.$lang_help['produces'].' <img src="'.$base_url.'/style/Cold_PE/img/cold-logo.jpg" alt="" /></div>',

	$lang_help['Quotes'] =>
		'<div style="padding-left: 4px">'.
			$lang_help['Quotes info'].'<br /><br />'.
			'&nbsp;&nbsp;&nbsp;&nbsp;[quote=James]'.$lang_help['Quote text'].'[/quote]<br /><br />'.
			$lang_help['produces quote box'].'<br /><br />'.
			'<div class="postmsg">'.
				'<blockquote><div class="incqbox"><h4>James '.$lang_common['wrote'].':</h4><p>'.$lang_help['Quote text'].'</p></div></blockquote>'.
			'</div>'.
			'<br />'.
			$lang_help['Quotes info 2'].'<br /><br />'.
			'&nbsp;&nbsp;&nbsp;&nbsp;[quote]'.$lang_help['Quote text'].'[/quote]<br /><br />'.
			$lang_help['produces quote box'].'<br /><br />'.
			'<div class="postmsg">'.
				'<blockquote><div class="incqbox"><p>'.$lang_help['Quote text'].'</p></div></blockquote>'.
			'</div>'.
		'</div>',

	$lang_help['Code'] =>
		'<div style="padding-left: 4px">'.
			$lang_help['Code info'].'<br /><br />'.
			'&nbsp;&nbsp;&nbsp;&nbsp;[code]'.$lang_help['Code text'].'[/code]<br /><br />'.
			$lang_help['produces code box'].'<br /><br />'.
			'<div class="postmsg">'.
				'<div class="codebox"><div class="incqbox"><h4>'.$lang_common['Code'].':</h4><div class="scrollbox" style="height: 4.5em"><pre>'.$lang_help['Code text'].'</pre></div></div></div>'.
			'</div>'.
		'</div>',

	$lang_help['Nested tags'] =>
		'<div style="padding-left: 4px">'.
			$lang_help['Nested tags info'].'<br /><br />'.
			'&nbsp;&nbsp;&nbsp;&nbsp;[b][u]'.$lang_help['Bold, underlined text'].'[/u][/b] '.$lang_help['produces'].' <span class="bbu"><b>'.$lang_help['Bold, underlined text'].'</b></span><br /><br />'.
		'</div>'

);

// Display the smiley set
require PUN_ROOT.'include/smilies.php';

$a = '';
$num_smilies = count($smiley_text);
for ($i = 0; $i < $num_smilies; ++$i)
{
	// Is there a smiley at the current index?
	if (!isset($smiley_text[$i]))
		continue;

	$a .= '&nbsp;&nbsp;&nbsp;&nbsp;'.$smiley_text[$i];

	// Save the current text and image
	$cur_img = $smiley_img[$i];
	$cur_text = $smiley_text[$i];

	// Loop through the rest of the array and see if there are any duplicate images
	// (more than one text representation for one image)
	for ($next = $i + 1; $next < $num_smilies; ++$next)
	{
		// Did we find a dupe?
		if (isset($smiley_img[$next]) && $smiley_img[$i] == $smiley_img[$next])
		{
			$a .= ' '.$lang_common['and'].' '.$smiley_text[$next];

			// Remove the dupe so we won't display it twice
			unset($smiley_text[$next]);
			unset($smiley_img[$next]);
		}
	}

	$a .= ' '.$lang_help['produces'].' <img src="img/smilies/'.$cur_img.'" width="15" height="15" alt="'.$cur_text.'" /><br />'."\n";
}

$QnA[$lang_common['Smilies']] = 
	'<div style="padding-left: 4px">'.
		'<a name="smilies"></a>'.$lang_help['Smilies info'].'<br /><br />'.
		$a.
		'<br />'.
	'</div>';

?>

<script>
function open_close(idn)
{
	if(document.getElementById(idn).style.display=='none')
		document.getElementById(idn).style.display = 'block';
	else
		document.getElementById(idn).style.display = 'none';
} 
window.onload = function(){
	var h = document.location.href;
	if (h.indexOf('#') != -1) {
		id = h.substring(h.indexOf('#')+1);
		switch (id) {
		case 'bbcode': open_close('help_1'); break;
		case 'img': open_close('help_3'); break;
		case 'smilies': open_close('help_7'); break;
		}
	}
}
</script>

<h2><?php echo $lang_help['Help'] ?></h2>
<div class="box">
	<div class="inbox">

<?php
$i = 1;
foreach($QnA as $q => $a)
{
	echo '<strong><a href="javascript:void(0);" onClick="open_close(\'help_'.$i.'\');">'.$q.'</a></strong>'."\n";
	echo '<br class="clearb" />'."\n";
	echo '<div id="help_'.$i.'" style="display:none;">'."\n";
	echo "\t".$a."\n";
	echo '</div>'."\n";
	echo '<br class="clearb" />'."\n\n";

	$i++;
}

?>
	</div>
</div>

<?php


require PUN_ROOT.'include/footer.php';
