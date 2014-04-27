<?php

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

$kind = PUN_KIND_BLOG;
$person = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
require PUN_ROOT.'boards.php';

