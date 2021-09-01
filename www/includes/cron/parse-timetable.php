<?php
require_once './init.php';
global $DB;

$faculties = $DB->getCol('SELECT `id` FROM `stud_faculties`');
$fac_len = sizeof($faculties);
for($fac_n=0; $fac_n<$fac_len; $fac_n++){
	//и будим искать в нем необновленные группы
	$groups = $DB->getCol('SELECT `id` FROM `stud_groups` WHERE `last_reload` + INTERVAL ?p <= NOW() AND `faculty_id` = ?s', UPDATE_INTERVAL_TIMETABLE, $faculties[$fac_n]);
	if($groups == false)
		continue;
	$len = sizeof($groups);
	if($len){
		//если такие группы есть то парсим их с небольшим промежутком по времени
		for($gr_n=0; $gr_n<$len; $gr_n++){
			$groups[$gr_n] = array(
				'url'     => 'https://www.altstu.ru/main/schedule/?group_id='.$groups[$gr_n],
				'options' => array(
					'post_data' => array(
						'faculty' => $faculties[$fac_n],
						'group'   => $groups[$gr_n]
					),
					'useragent' => rad_parser::get_rand_user_agent(MAIN_DIR.'files/user_agents.txt'),
					'referer'   => 'https://www.altstu.ru/main/schedule/',
					'headers'   => array(
						'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
						'accept-language: ru,en;q=0.8',
						'dnt: 1',
						'origin: https://www.altstu.ru',
						'upgrade-insecure-requests: 1'
					)
				)
			);
		}
		rad_parser::get_pages_content($groups, 'parse_timetable');
		usleep((rand()%375+125)*1000);
	}
}

?>