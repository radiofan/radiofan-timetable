<?php
mb_internal_encoding('UTF-8');
define('MAIN_DIR', realpath(__DIR__.'/../../').'/');
define('AJAX', false);
require_once MAIN_DIR.'defines.php';
//языковые константы, используются в событиях (actions)
require_once MAIN_DIR.'langs/ru-lang.php';

//подключаем функции из файлов includes/functions/*-functions.php
require_once MAIN_DIR.'includes/functions/other-functions.php';
$files = file_list(MAIN_DIR.'includes/functions/', '.php', '^.*?-functions');
for($i=0; $i<sizeof($files); $i++){
	if($files[$i] != 'other-functions.php')
		require_once MAIN_DIR.'includes/functions/'.$files[$i];
}

require_once MAIN_DIR.'includes/classes/parser-class.php';
require_once MAIN_DIR.'includes/phpmailer/PHPMailer.php';
require_once MAIN_DIR.'includes/phpmailer/Exception.php';
require_once MAIN_DIR.'includes/phpmailer/SMTP.php';

require_once MAIN_DIR.'includes/classes/log-class.php';
if(defined('USE_LOG') && USE_LOG)
	$LOG = new rad_log(MAIN_DIR.'files/logs/');

require_once MAIN_DIR.'includes/classes/db-class.php';
$DB = new rad_db(array('host' => MAIN_DBHOST, 'user' => MAIN_DBUSER, 'pass' => MAIN_DBPASS, 'db' => MAIN_DBNAME));


require_once MAIN_DIR.'includes/classes/data-class.php';
$DATA = new rad_data();//TODO

$OPTIONS['time_start'] = microtime(1);

set_time_limit(0);

?>