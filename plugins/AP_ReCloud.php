<?php

if (!defined('PUN'))
	exit;

define('PUN_PLUGIN_LOADED', 1);


$plugin_name = 'AP_ReCloud.php';

if (isset($_GET['i_per_page']) && isset($_GET['i_start_at']))
{
	$per_page = intval($_GET['i_per_page']);
	$start_at = intval($_GET['i_start_at']);
	if ($per_page < 1 || $start_at < 1)
		message($lang_common['Bad request']);

	@set_time_limit(0);

	// If this is the first cycle of posts we empty the search index before we proceed
	if (isset($_GET['i_empty_index']))
	{
		// This is the only potentially "dangerous" thing we can do here, so we check the referer
		confirm_referrer('admin/loader.php', true);

		// Reset the sequence for the search words
		@unlink(PUN_ROOT.'cache/cache_labels.php');
	}

?>

Rebuilding index &hellip; This might be a good time to put on some coffee :-)<br /><br />

<?php
	// Fetch posts to process
	$result = $db->query('SELECT DISTINCT t.id, t.labels FROM '.$db->prefix.'topics AS t WHERE t.id>='.$start_at.' ORDER BY t.id LIMIT '.$per_page) or error('Unable to fetch topic/post info', __FILE__, __LINE__, $db->error());

	$labels = array();
	while ($cur_topic = $db->fetch_row($result))
	{

		echo 'Processing topic <strong>'.$cur_topic[0].'</strong> <br />'."\n";
		$start_at = $cur_topic[0];

		$tmp = explode_labels($cur_topic[1]);
		if (count($tmp))
			$labels = array_merge($labels, $tmp);

	}

	// Check if there is more work to do
	$result = $db->query('SELECT id FROM '.$db->prefix.'topics WHERE id>'.$start_at.' ORDER BY id ASC LIMIT 1') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());

	$query_str = ($db->num_rows($result)) ? 'i_per_page='.$per_page.'&i_start_at='.$db->result($result).'&csrf_hash='.csrf_hash() : '';

	$db->end_transaction();
	$db->close();

	if (count($labels))
	{
		if (file_exists(PUN_ROOT.'cache/cache_labels.php'))
			include(PUN_ROOT.'cache/cache_labels.php');
		else
			$pun_labels = array();

		foreach($labels as $label)
		{
			if (isset($pun_labels[$label]))
				$pun_labels[$label]++;
			else
				$pun_labels[$label] = 1;
		}

		ksort($pun_labels);

		// Output label list as PHP code
		$fh = @fopen(PUN_ROOT.'cache/cache_labels.php', 'wb');
		if (!$fh)
			error('Unable to write labels cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'', __FILE__, __LINE__);

		fwrite($fh, '<?php'."\n\n".'define(\'PUN_LABELS_LOADED\', 1);'."\n\n".'$pun_labels = '.var_export($pun_labels, true).';'."\n\n".'?>');

		fclose($fh);
	}

	echo '<script type="text/javascript">window.location="loader.php?plugin='.$plugin_name.'&'.$query_str.'"</script><br />JavaScript redirect unsuccessful. Click <a href="loader.php?plugin='.$plugin_name.'&'.$query_str.'">here</a> to continue.';
}

else

{

?>
	<div id="exampleplugin" class="blockform">
		<h2><span>ReCloud plugin: Labels Maintenance</span></h2>
		<div class="box">
			<form method="get" action="loader.php">
				<div class="inform">
					<fieldset>
						<input type="hidden" name="csrf_hash" value="<?php echo csrf_hash() ?>" />
						<input type="hidden" name="plugin" value="<?php echo $plugin_name ?>" />

						<legend>Rebuild index of topic labels</legend>
						<div class="infldset">
							<p>If you've added, edited or removed posts manually in the database or if you're having problems searching, you should rebuild the topic labels cloud. For best performance you should put the forum in maintenance mode during rebuilding. <br />
							<strong>Rebuilding the search index can take a long time and will increase server load during the rebuild process!</strong></p>
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Topics per cycle</th>
									<td>
										<input type="text" name="i_per_page" size="7" maxlength="7" value="100" tabindex="1" />
										<span>The number of topics to process per pageview. E.g. if you were to enter 100, one hundred topics would be processed and then the page would refresh. This is to prevent the script from timing out during the rebuild process.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Starting Topic ID</th>
									<td>
										<input type="text" name="i_start_at" size="7" maxlength="7" value="1" tabindex="2" />
										<span>The topic ID to start rebuilding at. It's default value is the first available ID in the database. Normally you wouldn't want to change this.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Empty index</th>
									<td class="inputadmin">
										<span><input type="checkbox" name="i_empty_index" value="1" tabindex="3" checked="checked" />&nbsp;&nbsp;Select this if you want the search index to be emptied before rebuilding (see below).</span>
									</td>
								</tr>
							</table>
							<p class="topspace">Once the process has completed you will be redirected back to this page. It is highly recommended that you have JavaScript enabled in your browser during rebuilding (for automatic redirect when a cycle has completed). If you are forced to abort the rebuild process, make a note of the last processed topic ID and enter that ID+1 in "Topic ID to start at" when/if you want to continue ("Empty index" must not be selected).</p>
							<div class="fsetsubmit"><input type="submit" name="rebuild_index" value="Rebuild index" tabindex="4" /></div>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
<?php

}
