<?php
mb_internal_encoding('UTF-8');
header("Content-Type: text/html; charset=UTF-8");
//setlocale(LC_COLLATE | LC_CTYPE | LC_TIME, 'ru_RU.UTF-8', 'ru_RU', 'ru', 'russian');
define('MAIN_DIR', __DIR__.'/');
define('AJAX', false);
require_once MAIN_DIR.'defines.php';

require_once MAIN_DIR.'functions.php';
require_once MAIN_DIR.'includes/parse-functions.php';
require_once MAIN_DIR.'includes/timetable-functions.php';
require_once MAIN_DIR.'includes/parser-class.php';

require_once MAIN_DIR.'includes/log-class.php';
if(defined('USE_LOG') && USE_LOG)
	$LOG = new rad_log(MAIN_DIR.'files/debug.log');

require_once MAIN_DIR.'includes/db-class.php';
$DB = new rad_db(array('host' => MAIN_DBHOST, 'user' => MAIN_DBUSER, 'pass' => MAIN_DBPASS, 'db' => MAIN_DBNAME));

require_once MAIN_DIR.'includes/data-class.php';
$DATA = new rad_data();

$OPTIONS = array();
//$OPTIONS['browser_data'] = $_SERVER['HTTP_USER_AGENT'];//get_browser
$OPTIONS['protocol'] = get_protocol();
$OPTIONS['user_agent'] = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
$OPTIONS['time_start'] = $_SERVER['REQUEST_TIME_FLOAT'];
$OPTIONS['user_ip'] = get_ip();
$OPTIONS['referer_data'] = parse_url(empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER']);

is_session_exists();

require_once MAIN_DIR.'includes/alerts-class.php';
$ALERTS = new rad_alerts();//TODO переделать логику, добавить запоминание в БД

require_once MAIN_DIR.'includes/user-class.php';
$USER = new rad_user();

require_once MAIN_DIR.'includes/cookie-validator-class.php';
$COOKIE_V = new rad_cookie();

require_once MAIN_DIR.'includes/url-class.php';
$URL = new rad_url();

require_once MAIN_DIR.'includes/pages-class.php';
$PAGES = new rad_pages_viewer();
$PAGES->load_current_page();

require_once MAIN_DIR.'actions.php';
if(do_actions() && CLEAR_POST)
	redirect($URL->get_current_url());

$PAGES->view_current_page();


?>