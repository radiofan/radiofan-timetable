<?php
mb_internal_encoding("UTF-8");
header("Content-Type: text/html; charset=UTF-8");
//setlocale(LC_COLLATE | LC_CTYPE | LC_TIME, 'ru_RU.UTF-8', 'ru_RU', 'ru', 'russian');
define('MAIN_DIR', __DIR__.'/');

require_once MAIN_DIR.'functions.php';
require_once MAIN_DIR.'includes/parser-class.php';

$useragents = file(MAIN_DIR.'files/user_agents.txt');

echo '<xmp>';

for($i=430; $i<sizeof($useragents); $i++){
	$ret = rad_parser::get_page_content('https://www.altstu.ru/main/schedule/', array('useragent'=>$useragents[$i]));
	if($ret['info']['http_code'] != 200){
		print_r($ret['info']);
		echo PHP_EOL.$i.'.'.$useragents[$i].PHP_EOL;
	}
	usleep((rand()%375+125)*1000);
}

echo '</xmp>';

?>