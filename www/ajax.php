<?php
mb_internal_encoding("UTF-8");
header("Content-Type: text/html; charset=UTF-8");
//setlocale(LC_COLLATE | LC_CTYPE | LC_TIME, 'ru_RU.UTF-8', 'ru_RU', 'ru', 'russian');
define('MAIN_DIR', __DIR__.'/');
define('AJAX', true);
require_once MAIN_DIR.'defines.php';

require_once MAIN_DIR.'functions.php';
require_once MAIN_DIR.'includes/parse-functions.php';
require_once MAIN_DIR.'includes/parser-class.php';

require_once MAIN_DIR.'includes/log-class.php';
if(defined('USE_LOG') && USE_LOG)
	$LOG = new rad_log(MAIN_DIR.'files/debug.log');

require_once MAIN_DIR.'includes/db-class.php';
$DB = new rad_db(array('host' => MAIN_DBHOST, 'user' => MAIN_DBUSER, 'pass' => MAIN_DBPASS, 'db' => MAIN_DBNAME));

require_once MAIN_DIR.'includes/data-class.php';
$DATA = new rad_data();
is_session_exists();

require_once MAIN_DIR.'includes/user-class.php';
$USER = new rad_user();

require_once MAIN_DIR.'actions.php';

$ret = do_actions();
if($USER->can_user('view_debug_info'))
	$ret['debug'] = array('db_stat' => $DB->getStats());
die(json_encode($ret));

?>