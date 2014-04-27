<div class="block">
	<h2><span><?php echo $lang_common['Search'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<form id="searchbox" method="get" action="search.php">
			<fieldset>
				<input type="hidden" name="action" value="search" />
				<input type="text" name="keywords" size="23" maxlength="100" value="<?php echo $lang_common['Search'].'&hellip;' ?>" onfocus="this.value='';" />
<?php
	foreach (array_keys($kinds) as $k)
	{
		$checked = !isset($kind) || $kind == $k;
		$checkboxes[] = '<li><label><input type="checkbox" name="cat_kinds[]" value="'.$k.'"'.($checked? ' checked="checked"' : '').' />&nbsp;'.$lang_common['Boards kind'][$k].'</label></li>';
	}
	echo "\t\t\t\t".'<ul>'.implode(' ',$checkboxes)."</ul>\n";
?>
			</fieldset>
			</form>
		</div>
	</div>
</div>