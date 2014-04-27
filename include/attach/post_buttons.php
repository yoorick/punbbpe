<?php
/***********************************************************************

  Show buttons above edit and post form.
  This file is part of PunBB Power Edition.

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2007       artoodetoo (master@punbb-pe.ru)

  Included from: edit.php, post.php

  Incoming variables;

  Outgoing variables:
    $attachments: array - cache of attachments records

************************************************************************/

?>

<!-- *** BUTTONS START *** -->
<script type="text/javascript">
<!--
var txt			= '';
var text_enter_title	= '<?php echo $lang_fu['JS enter title'] ?>'
var text_enter_url      = '<?php echo $lang_fu['JS enter url'] ?>'
var text_enter_url_name = '<?php echo $lang_fu['JS enter url name'] ?>'
var text_enter_image    = '<?php echo $lang_fu['JS enter image'] ?>'
var text_enter_email    = '<?php echo $lang_fu['JS enter email'] ?>'
var error_no_url	= '<?php echo $lang_fu['JS no url'] ?>'
var error_no_title	= '<?php echo $lang_fu['JS no title'] ?>'
var error_no_email	= '<?php echo $lang_fu['JS no email'] ?>'

//-->
</script>
<script type="text/javascript" src="<?php echo $base_url ?>/js/reply.js"></script>

<div id="buttonmenu">

<ul>
<li><a href="#" onclick="return insert_text('[b]', '[/b]');"><b>B</b></a></li>
<li><a href="#" onclick="return insert_text('[i]', '[/i]');"><i>I</i></a></li>
<li><a href="#" onclick="return insert_text('[u]', '[/u]');"><u>U</u></a></li>
<li><a href="#" onclick="return insert_text('[s]', '[/s]');"><s>S</s></a></li>
<li><a href="#" onclick="return insert_text('[left]',   '[/left]'  );">Left</a></li>
<li><a href="#" onclick="return insert_text('[center]', '[/center]');">Center</a></li>
<li><a href="#" onclick="return insert_text('[right]',  '[/right]' );">Right</a></li>
<li><a href="#" onclick="return tag_url();"  >Url</a></li>
<li><a href="#" onclick="return tag_email();">Email</a></li>
<li><a href="#" onclick="return tag_image();">Image</a></li>
<li><a href="#" onclick="return insert_text('[quote]',  '[/quote]');">Quote</a></li>
<li><a href="#" onclick="return insert_text('[code]',   '[/code]' );">Code</a></li>
<li><a href="#" onclick="return false;">Color<!--[if IE 7]><!--></a><!--<![endif]-->
<!--[if lte IE 6]><table><tr><td><![endif]-->
	<ul id="colorcontent">
        <li>
            <a href="#" style="background:#000" title="Black"		onclick="return insert_text('[color=#000]','[/color]');"></a>
            <a href="#" style="background:#930" title="Brown"		onclick="return insert_text('[color=#930]','[/color]');"></a>
            <a href="#" style="background:#330" title="Olive Green"	onclick="return insert_text('[color=#330]','[/color]');"></a>
            <a href="#" style="background:#030" title="Dark Green"	onclick="return insert_text('[color=#030]','[/color]');"></a>
            <a href="#" style="background:#036" title="Dark Teal"	onclick="return insert_text('[color=#036]','[/color]');"></a>
            <a href="#" style="background:#008" title="Dark Blue"	onclick="return insert_text('[color=#008]','[/color]');"></a>
            <a href="#" style="background:#339" title="Indigo"		onclick="return insert_text('[color=#339]','[/color]');"></a>
            <a href="#" style="background:#333" title="Gray-80%"	onclick="return insert_text('[color=#333]','[/color]');"></a>

            <a href="#" style="background:#800" title="Dark Red"	onclick="return insert_text('[color=#800]','[/color]');"></a>
            <a href="#" style="background:#f60" title="Orange"		onclick="return insert_text('[color=#F60]','[/color]');"></a>
            <a href="#" style="background:#880" title="Dark Yellow"	onclick="return insert_text('[color=#880]','[/color]');"></a>
            <a href="#" style="background:#080" title="Green"		onclick="return insert_text('[color=#080]','[/color]');"></a>
            <a href="#" style="background:#088" title="Teal"		onclick="return insert_text('[color=#088]','[/color]');"></a>
            <a href="#" style="background:#00f" title="Blue"		onclick="return insert_text('[color=#00f]','[/color]');"></a>
            <a href="#" style="background:#669" title="Blue-Gray"	onclick="return insert_text('[color=#669]','[/color]');"></a>
            <a href="#" style="background:#888" title="Gray-50%"	onclick="return insert_text('[color=#888]','[/color]');"></a>

            <a href="#" style="background:#f00" title="Red"		onclick="return insert_text('[color=#f00]','[/color]');"></a>
            <a href="#" style="background:#f90" title="Light Orange"	onclick="return insert_text('[color=#F90]','[/color]');"></a>
            <a href="#" style="background:#9c0" title="Lime"		onclick="return insert_text('[color=#9C0]','[/color]');"></a>
            <a href="#" style="background:#396" title="Sea Green"	onclick="return insert_text('[color=#396]','[/color]');"></a>
            <a href="#" style="background:#3cc" title="Aqua"		onclick="return insert_text('[color=#3CC]','[/color]');"></a>
            <a href="#" style="background:#36f" title="Light Blue"	onclick="return insert_text('[color=#36F]','[/color]');"></a>
            <a href="#" style="background:#808" title="Violet"		onclick="return insert_text('[color=#808]','[/color]');"></a>
            <a href="#" style="background:#aaa" title="Gray-40%"	onclick="return insert_text('[color=#aaa]','[/color]');"></a>

            <a href="#" style="background:#f0f" title="Pink"		onclick="return insert_text('[color=#F0F]','[/color]');"></a>
            <a href="#" style="background:#fc0" title="Gold"		onclick="return insert_text('[color=#FC0]','[/color]');"></a>
            <a href="#" style="background:#ff0" title="Yellow"		onclick="return insert_text('[color=#FF0]','[/color]');"></a>
            <a href="#" style="background:#0f0" title="Bright Green"	onclick="return insert_text('[color=#0F0]','[/color]');"></a>
            <a href="#" style="background:#0ff" title="Turquoise"	onclick="return insert_text('[color=#0FF]','[/color]');"></a>
            <a href="#" style="background:#0cf" title="Sky Blue"	onclick="return insert_text('[color=#0CF]','[/color]');"></a>
            <a href="#" style="background:#936" title="Plum"		onclick="return insert_text('[color=#936]','[/color]');"></a>
            <a href="#" style="background:#ccc" title="Gray-25%"	onclick="return insert_text('[color=#CCC]','[/color]');"></a>

            <a href="#" style="background:#f9c" title="Rose"		onclick="return insert_text('[color=#F9C]','[/color]');"></a>
            <a href="#" style="background:#fc9" title="Tan"		onclick="return insert_text('[color=#FC9]','[/color]');"></a>
            <a href="#" style="background:#ff9" title="Light Yellow"	onclick="return insert_text('[color=#FF9]','[/color]');"></a>
            <a href="#" style="background:#cfc" title="Light Green"	onclick="return insert_text('[color=#CFC]','[/color]');"></a>
            <a href="#" style="background:#cff" title="Light Turquoise"	onclick="return insert_text('[color=#CFF]','[/color]');"></a>
            <a href="#" style="background:#9cf" title="Pale Blue"	onclick="return insert_text('[color=#9CF]','[/color]');"></a>
            <a href="#" style="background:#c9f" title="Lavender"	onclick="return insert_text('[color=#C9F]','[/color]');"></a>
            <a href="#" style="background:#fff" title="White"		onclick="return insert_text('[color=#fff]','[/color]');"></a>
        </li>
	</ul>
<!--[if lte IE 6]></td></tr></table></a><![endif]-->
</li>
<li><a href="#" onclick="return false;">Font<!--[if IE 7]><!--></a><!--<![endif]-->
<!--[if lte IE 6]><table><tr><td><![endif]-->
	<ul>
        <li><a href="#" style="font-family:Arial"		 onclick="return insert_text('[font=Arial]',            '[/font]');">Arial</a></li>
        <li><a href="#" style="font-family:Arial Black"		 onclick="return insert_text('[font=Arial Black]',        '[/font]');">Arial Black</a></li>
        <li><a href="#" style="font-family:Arial Narrow"	 onclick="return insert_text('[font=Arial Narrow]',        '[/font]');">Arial Narrow</a></li>
        <li><a href="#" style="font-family:Century Gothic"	 onclick="return insert_text('[font=Century Gothic]',        '[/font]');">Century Gothic</a></li>
        <li><a href="#" style="font-family:Courier New"		 onclick="return insert_text('[font=Courier New]',        '[/font]');">Courier New</a></li>
        <li><a href="#" style="font-family:Garamond"		 onclick="return insert_text('[font=Garamond]',            '[/font]');">Garamond</a></li>
        <li><a href="#" style="font-family:Georgia"		 onclick="return insert_text('[font=Georgia]',            '[/font]');">Georgia</a></li>
        <li><a href="#" style="font-family:Impact"		 onclick="return insert_text('[font=Impact]',            '[/font]');">Impact</a></li>
        <li><a href="#" style="font-family:Microsoft Sans Serif" onclick="return insert_text('[font=Microsoft Sans Serif]',    '[/font]');">Microsoft Sans Serif</a></li>
        <li><a href="#" style="font-family:Palatino Linotype"	 onclick="return insert_text('[font=Palatino Linotype]',    '[/font]');">Palatino Linotype</a></li>
        <li><a href="#" style="font-family:Tahoma"		 onclick="return insert_text('[font=Tahoma]',            '[/font]');">Tahoma</a></li>
        <li><a href="#" style="font-family:Times New Roman"	 onclick="return insert_text('[font=Times New Roman]',        '[/font]');">Times New Roman</a></li>
        <li><a href="#" style="font-family:Verdana"		 onclick="return insert_text('[font=Verdana]',            '[/font]');">Verdana</a></li>
	</ul>
<!--[if lte IE 6]></td></tr></table></a><![endif]-->
</li>
<li><a href="#" onclick="return false;">Size<!--[if IE 7]><!--></a><!--<![endif]-->
<!--[if lte IE 6]><table><tr><td><![endif]-->
	<ul>
        <li><a href="#" onclick="return insert_text('[size=8]',  '[/size]');"><span style="font-size:8px" >8px</span></a></li>
        <li><a href="#" onclick="return insert_text('[size=10]', '[/size]');"><span style="font-size:10px">10px</span></a></li>
        <li><a href="#" onclick="return insert_text('[size=12]', '[/size]');"><span style="font-size:12px">12px</span></a></li>
        <li><a href="#" onclick="return insert_text('[size=14]', '[/size]');"><span style="font-size:14px">14px</span></a></li>
        <li><a href="#" onclick="return insert_text('[size=16]', '[/size]');"><span style="font-size:16px">16px</span></a></li>
        <li><a href="#" onclick="return insert_text('[size=18]', '[/size]');"><span style="font-size:18px">18px</span></a></li>
        <li><a href="#" onclick="return insert_text('[size=20]', '[/size]');"><span style="font-size:20px">20px</span></a></li>
	</ul>
<!--[if lte IE 6]></td></tr></table></a><![endif]-->
</li>
</ul>

</div>
						<div class="clearer"></div>

						<style> DIV#smilies-area {margin: 0; padding: 0} DIV#smilies-area IMG {cursor: pointer; float: left; padding: 2px} </style>
						<div id="smilies-area">
							<span>
<?php
require_once PUN_ROOT.'include/parser.php';
$shown_smiles = array();
for ($i = 0; $i < count($smiley_text); ++$i)
{
	$cur_img = $smiley_img[$i];
	$cur_text = $smiley_text[$i];
	if (in_array($cur_img, $shown_smiles)) continue;
	echo "\t\t\t\t\t\t\t\t".'<img src="'.$base_url.'/img/smilies/'.$cur_img.'" alt="'.$cur_text.'" onclick="return insert_text(\' '.$cur_text.' \', \'\');" />'."\n";
	$shown_smiles[] = $cur_img;
	if (count($shown_smiles) == 12) break;
}
unset($shown_smiles);
?>
							</span>
							<span>&nbsp;<a href="#" onclick="return emo_pop();"><?php echo $lang_fu['Show all smilies'] ?></a></span>
						</div>

<!-- *** BUTTONS END *** -->

