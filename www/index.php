<?php
mb_internal_encoding("UTF-8");
header("Content-Type: text/html; charset=UTF-8");
//setlocale(LC_COLLATE | LC_CTYPE | LC_TIME, 'ru_RU.UTF-8', 'ru_RU', 'ru', 'russian');
define('MAIN_DIR', __DIR__.'/');
define('AJAX', false);
require_once MAIN_DIR.'defines.php';

require_once MAIN_DIR.'functions.php';
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

require_once MAIN_DIR.'includes/url-class.php';
$URL = new URL();

require_once MAIN_DIR.'pages.php';
gen_pages_tree();


$URL->load_current_page();

require_once MAIN_DIR.'actions.php';
if(do_actions() && CLEAR_POST)
	redirect($URL->get_current_url());

$PAGE_DATA = prepare_page_data();


$URL->view_current_page($PAGE_DATA);


?>