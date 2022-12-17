<?php
require_once 'init.php';
global $DB, $OPTIONS;

$group_names = $DB->getAll('SELECT DISTINCT SUBSTRING(`name`, 1,CHAR_LENGTH(`name`)-2) AS `names`, `faculty_id` FROM `stud_groups`');

$len = sizeof($group_names);
for($i=0; $i<$len; $i++){
	$curl_data = array();
	for($kurs = 0; $kurs<10; $kurs++){
		$curl_data[] = array(
			'url' => 'https://www.altstu.ru/m/s/ajax/?query='.urlencode($group_names[$i]['names'].$kurs).'&faculty='.$group_names[$i]['faculty_id'],
			'options' => array(
				'useragent' => rad_parser::get_rand_user_agent(MAIN_DIR.'files/user_agents.txt'),
				'referer' => 'https://www.altstu.ru/main/schedule/',
				'headers' => array(
					'x-requested-with: XMLHttpRequest',
					'accept: application/json, text/javascript, */*; q=0.01',
					'accept-language:ru,en;q=0.8'
				)
			)
		);
	}
	unset($group_names[$i]);
	rad_parser::get_pages_content($curl_data, 'parse_groups');
	usleep((rand()%375+125)*1000);
}


log_parse_event('Parse groups complete. Time start: '.$OPTIONS['time_start'].' sec, execute time: '.(microtime(1) - $OPTIONS['time_start']).' sec, memory_peak: '.round_memsize(memory_get_peak_usage(1)));

?>