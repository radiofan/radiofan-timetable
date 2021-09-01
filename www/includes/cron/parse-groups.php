<?php
require_once './init.php';
global $DB;

$faculties = $DB->getIndCol('id', 'SELECT * FROM `stud_faculties`');
$curl_data = array();
foreach($faculties as $fac_id => $name){
	$curl_data[] = array(
		'url' => 'https://www.altstu.ru/main/schedule/ws/group/?f='.urlencode($fac_id),
		'options' => array(
			'useragent' => rad_parser::get_rand_user_agent(MAIN_DIR.'files/user_agents.txt'),
			'referer'   => 'https://www.altstu.ru/main/schedule/',
			'headers'   => array(
				'x-requested-with: XMLHttpRequest',
				'accept: application/json, text/javascript, */*; q=0.01',
				'accept-language:ru,en;q=0.8'
			)
		)
	);
}

unset($faculties);
rad_parser::get_pages_content($curl_data, 'parse_groups');



function load_faculties(){
	global $DB;
	$ret = rad_parser::get_page_content('https://www.altstu.ru/main/schedule/');
	if(!empty($ret['status'])){
		log_parse_event('CURL error['.$ret['status'].'] (can\'t load faculties): '.$ret['status_text'], $ret['info']);
		return false;
	}

	$faculties = parse_faculties($ret['content']);
	if(!$faculties)
		return false;

	if(!$DB->query('INSERT INTO `stud_faculties` ?d', $faculties)){
		return false;
	}
	return true;
}
?>