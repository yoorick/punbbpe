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

************************************************************************/


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

define('PUN_INDEX', 1);

// Load the index.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/index.php';

$page_title = pun_htmlspecialchars($pun_config['o_board_title']);
define('PUN_ALLOW_INDEX', 1);

$context_menu[] = '<a href="'.$base_url.'/extern.php?action=new&amp;type=RSS">RSS</a>';
if (!$pun_user['is_guest'])
{
	$context_menu[] = '<a href="'.$base_url.'/favorites.php?user_id='.$pun_user['id'].'">'.$lang_common['Show subscriptions'].'</a>';
	$context_menu[] = '<a href="'.$base_url.'/friends.php?user_id='.$pun_user['id'].'">'.$lang_common['Show friends'].'</a>';
	$context_menu[] = '<a href="'.$base_url.'/search.php?action=show_new">'.$lang_common['Show new posts'].'</a>';
}

require PUN_ROOT.'include/header.php';

?>
<div id="welcome" class="block">

	<h2><span><?php echo $lang_common['Welcome'] ?></span></h2>

	<div class="box">
		<div class="inbox">
<?php
include PUN_ROOT.'lang/'.$pun_user['language'].'/welcome.php';
?>
		</div>
	</div>

</div>

<?php

include PUN_ROOT.'include/user/lastblog.php';


$footer_style = 'index';
require PUN_ROOT.'include/footer.php';
