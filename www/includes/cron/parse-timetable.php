<?php
require_once 'init.php';
global $DB, $OPTIONS;

//сгенерируем строку для парсинга времени пар
$tmp = $DB->getCol('SELECT `time_start` FROM `stud_lesson_times` WHERE `type` = 1 ORDER BY `time_start`');
if(!$tmp){
	log_parse_event('stud_lesson_times type 1 not found');
	die();
}
$TIME_REGEXP = '';
for($i=0; $i<sizeof($tmp); $i++){
	$TIME_REGEXP .= '('.seconds_to_time($tmp[$i], 0).')|';
}
$TIME_REGEXP = mb_substr($TIME_REGEXP, 0, mb_strlen($TIME_REGEXP)-1);
$TIME_REGEXP = '#'.$TIME_REGEXP.'#isu';

//сгенерируем массив уроков
$LESSONS_SHA_ID = $DB->getIndCol('key', 'SELECT SHA1(`parse_text`) AS `key`, `id` FROM `stud_lessons`');

//сгенерируем массив кабинетов
$tmp = $DB->getAll('SELECT `id`, `cabinet`, `additive`, `building` FROM `stud_cabinets`');
$CABINETS_SHA_ID = array();
$len = sizeof($tmp);
for($i=0; $i<$len; $i++){
	$key = $tmp[$i]['cabinet'].'%'.$tmp[$i]['additive'].'%'.$tmp[$i]['building'];
	$CABINETS_SHA_ID[sha1($key)] = $tmp[$i]['id'];
}

//сгенерируем массив преподов
$tmp = $DB->getAll('SELECT `id`, `fio`, `additive` FROM `stud_teachers`');
$TEACHERS_SHA_ID = array();
$len = sizeof($tmp);
for($i=0; $i<$len; $i++){
	$key = $tmp[$i]['fio'].'%'.$tmp[$i]['additive'];
	$TEACHERS_SHA_ID[sha1($key)] = $tmp[$i]['id'];
}
unset($tmp);

//parse_timetable(file_get_contents('./tmp.html'), array('options' => array('post_data' => array('group' => '7000017066'))), 0, '');


$faculties = $DB->getCol('SELECT `id` FROM `stud_faculties`');
$fac_len = sizeof($faculties);
for($fac_n=0; $fac_n<$fac_len; $fac_n++){
	//и будим искать в нем необновленные группы
	$groups = $DB->getCol('SELECT `id` FROM `stud_groups` WHERE `faculty_id` = ?s AND `status` < '.PARSE_LIMIT_404.' AND `last_reload` + INTERVAL ?p <= MY_NOW()', $faculties[$fac_n], UPDATE_INTERVAL_TIMETABLE);
	if($groups == false)
		continue;
	$len = sizeof($groups);
	if($len){
		//если такие группы есть то парсим их с небольшим промежутком по времени
		for($gr_n=0; $gr_n<$len; $gr_n++){
			$groups[$gr_n] = array(
				'url'     => 'https://www.altstu.ru/main/schedule/'.$groups[$gr_n].'/',
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

log_parse_event('Parse timetable complete. Time start: '.$OPTIONS['time_start'].' sec, execute time: '.(microtime(1) - $OPTIONS['time_start']).' sec, memory_peak: '.round_memsize(memory_get_peak_usage(1)));

?>