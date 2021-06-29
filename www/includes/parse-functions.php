<?php
/**
 * Обновляет расписание алтгту
 * @return bool
 */
function reload_timetable(){
	global $DB, $DATA;
	//получение факультетов
	$faculties = array();
	$tmp = $DB->getOne('SELECT COUNT(`id`) FROM `stud_faculties`');
	if(!$tmp){
		//если нет факультетов то парсим их
		$ret = rad_parser::get_page_content('https://www.altstu.ru/main/schedule/');
		if(!empty($ret['status'])){
			trigger_error('CURL error['.$ret['status'].'] (can\'t load faculties): '.$ret['status_text'].', info('.print_r($ret['info'], 1).')', E_USER_WARNING);
			return false;
		}
		
		$faculties = parse_faculties($ret['content']);
		if(!$faculties)
			return false;
		
		if(!$DB->query('INSERT INTO `stud_faculties` ?d', $faculties)){
			return false;
		}
		
		$faculties = array_column($faculties, 'name', 'id');
	}else{
		//иначе берем не обновленные факультеты
		$faculties = $DB->getIndCol('id', 'SELECT * FROM `stud_faculties` WHERE `last_reload` + INTERVAL ?p <= NOW()', $DATA->get('update_interval_groups'));
	}
	
	if($faculties){
		//парсим группы необновленных факультетов
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
		
		rad_parser::get_pages_content($curl_data, 'parse_groups');
		unset($curl_data, $faculties);
	}
	//массив преподов и кабинетов
	$GLOBALS['TEACHS'] = $DB->getAll('SELECT `id`, `fio`, `additive` FROM `stud_teachers`');
	$GLOBALS['CABS'] = $DB->getAll('SELECT * FROM `stud_cabinets`');
	
	//получим список всех факультетов
	$faculties = $DB->getCol('SELECT `id` FROM `stud_faculties`');
	$fac_len = sizeof($faculties);
	for($i=0; $i<$fac_len; $i++){
		//и будим искать в нем необновленные группы
		$groups = $DB->getCol('SELECT `id` FROM `stud_groups` WHERE `last_reload` + INTERVAL ?p <= NOW() AND `faculty_id` = ?s', $DATA->get('update_interval_timetable'), $faculties[$i]);
		if($groups === false)
			continue;
		$len = sizeof($groups);
		if($len){
			//если такие группы есть то парсим их с небольшим промежутком по времени
			for($i1=0; $i1<$len; $i1++){
				$groups[$i1] = array(
					'url'     => 'https://www.altstu.ru/main/schedule/?group_id='.$groups[$i1],
					'options' => array(
						'post_data' => array(
							'faculty' => $faculties[$i],
							'group'   => $groups[$i1]
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
	
	return true;
}

/**
 * Парсит факультеты со странички расписания
 * @param $html
 * @return array|bool - массив для загрузки в БД (stud_faculties)
 */
function parse_faculties($html){
	$matches = array();
	$ret = array();
	preg_match('#<select[^>]*?id=["\']?id_faculty[\'"]?[^>]*?>.*?</select>#isu', $html, $matches);
	if(empty($matches[0])){
		trigger_error('Can\'t find select#id_faculty', E_USER_WARNING);
		return false;
	}
	$html = $matches[0];
	preg_match_all('#<option[^>]*?value=["\']([^\'"]*?)[\'"][^>]*>(.*?)</option>#isu', $html, $matches);
	if(empty($matches[1])){
		trigger_error('Can\'t find option in select#id_faculty', E_USER_WARNING);
		return false;
	}
	$len = sizeof($matches[1]);
	for($i=0; $i<$len; $i++){
		$ret[] = array('id' => $matches[1][$i], 'name' => !empty($matches[2][$i]) ? $matches[2][$i] : '%noname%');
	}
	return $ret;
}

/**
 * Коллбэк для парсинга, извлекает из JSON группы, добавляет их в БД (stud_groups) и прописывает обновление для факультета
 * @param $content - JSON
 * @param $info - CURL info
 * @param $status - CURL status
 * @param $status_text - CURL status text
 * @return bool
 */
function parse_groups($content, $info, $status, $status_text){
	global $DB;
	if($status){
		//error
		trigger_error('CURL error['.$status.'](can\'t load groups): '.$status_text.', info('.print_r($info, 1).')', E_USER_WARNING);
		return false;
	}
	$dat = json_decode($content, 1);
	if(json_last_error() != JSON_ERROR_NONE){
		trigger_error('JSON decode error['.json_last_error().']: '.json_last_error_msg().', request('.$info['url'].')', E_USER_WARNING);
		return false;
	}
	
	$len = is_array($dat) ? sizeof($dat) : 0;
	if($len == 0)
		return false;
	$fac_id = '';
	for($i=0; $i<$len; $i++){
		$fac_id = $dat[$i]['faculty_id'];
		$tmp = array('faculty_id' => $dat[$i]['faculty_id'], 'id' => $dat[$i]['id'], 'name' => $dat[$i]['name'], 'last_reload' => '1980-01-01 00:00:00');
		unset($dat[$i]['faculty_id'], $dat[$i]['id'], $dat[$i]['name']);
		if($dat[$i])
			$tmp['data'] = serialize($dat[$i]);
		$dat[$i] = $tmp;
	}
	
	$DB->query('DELETE FROM `stud_groups` WHERE `faculty_id` = ?s', $fac_id);
	
	if(!$DB->query('INSERT INTO `stud_groups` ?d', $dat))
		return false;
	
	$DB->query('UPDATE `stud_faculties` SET `last_reload` = NOW() WHERE `id` = ?s ', $fac_id);
	
	return true;
}

/**
 * Коллбэк для парсинга, парсит страницу с расписанием, добавляет его в БД (stud_timetable)
 * @param $content - html
 * @param $info - CURL info
 * @param $status - CURL status
 * @param $status_text - CURL status text
 * @return bool
 */
function parse_timetable($content, $info, $status, $status_text){
	if($status){
		//error
		trigger_error('CURL error['.$status.'](can\'t load timetable): '.$status_text.', info('.print_r($info, 1).')', E_USER_WARNING);
		return false;
	}
	$matches = array();
	
	//получим текущую группу
	preg_match('#<select[^>]*?id=["\']?id_group[\'"]?[^>]*?>.*?</select>#isu', $content, $matches);
	if(empty($matches[0])){
		trigger_error('Can\'t find select#id_group, info(http_code: '.$info['http_code'].', url: '.$info['url'].', redirect: '.$info['redirect_url'].')', E_USER_WARNING);
		return false;
	}
	$html = $matches[0];
	preg_match('#<option[^>]*?value=["\']([^\'"]*?)[\'"][^>]*?selected[^>]*>.*?</option>#isu', $html, $matches);
	if(empty($matches[1])){
		trigger_error('Can\'t find selected option in select#id_group, info(http_code: '.$info['http_code'].', url: '.$info['url'].', redirect: '.$info['redirect_url'].')', E_USER_WARNING);
		return false;
	}
	global $DB;
	$group_id = $matches[1];
	
	//получим блок с таблицами расписания
	preg_match_all('#(<div[^>]*?class=["\']schedule[\'"][^>]*>)(.*)$#ismu', $content, $matches);
	
	if(!isset($matches[0][0])){
		$DB->query('DELETE FROM `stud_timetable` WHERE `group_id` = ?s', $group_id);
		$DB->query('UPDATE `stud_groups` SET `last_reload` = NOW() WHERE `id` = ?s', $group_id);
		return false;
	}
	
	$content = $matches[2][0];
	$html = $matches[1][0];
	$tmp = 1;
	while($tmp && preg_match('#(.*?)(</div>|<div[^>]*>)(.*)$#ismu', $content, $matches)){
		$html .=  $matches[1].$matches[2];
		$content = $matches[3];
		if($matches[2] === '</div>'){
			$tmp--;
		}else{
			$tmp++;
		}
	}
	unset($content);
	
	preg_match_all('#<h3[^>]*>(.*?)</h3>.*?<table[^>]*>(.*?)</table>#isu', $html, $matches);
	unset($html);
	
	if(empty($matches[1]) || empty($matches[2])){
		return false;
	}
	
	$weeks = $matches[1];
	$tables = $matches[2];
	$insert = array();
	$len = sizeof($tables);
	
	$week_days = array(
		'понедельник' => 0,
		'вторник' => 1,
		'среда' => 2,
		'четверг' => 3,
		'пятница' => 4,
		'суббота' => 5,
		'воскресенье' => 6
	);
	$time_interval = '#(8:15)|(9:55)|(11:35)|(13:35)|(15:15)|(16:55)|(18:35)|(20:15)#isu';
	
	
	for($i=0; $i<$len; $i++){
		preg_match('#</thead>(.*)$#isum', $tables[$i], $matches);
		if(empty($matches[1])){
			trigger_error('Timetable hasn\'t thead, info(http_code: '.$info['http_code'].', url: '.$info['url'].', redirect: '.$info['redirect_url'].')', E_USER_WARNING);
			return false;
		}
		$tables[$i] = $matches[1];
		preg_match_all('#<tr[^>]*>(.*?)</tr>#isum', $tables[$i], $matches);
		$tmp = $matches[1];
		$len1 = sizeof($tmp);
		$day = '';
		preg_match('#[0-9]+#isu', $weeks[$i], $matches);
		$week = $matches[0];
		for($i1=0; $i1<$len1; $i1++){
			if(preg_match('#class=[\'"][^\'"]*?day[^\'"]*[\'"]#isu', $tmp[$i1])){
				preg_match('#<th[^>]*>(.*?)</th>#isu', $tmp[$i1], $matches);
				$a = trim(mb_strtolower($matches[1]));
				$day = isset($week_days[$a]) ? $week_days[$a] : '';
			}else{
				if($day === ''){
					trigger_error('Day not found, info(http_code: '.$info['http_code'].', url: '.$info['url'].', redirect: '.$info['redirect_url'].')', E_USER_WARNING);
					return false;
				}
				preg_match_all('#<td[^>]*>(.*?)</td>#isu', $tmp[$i1], $matches);
				$td = $matches[1];
				if(sizeof($td) != 4){
					trigger_error('Timetable hasn\'t 4 td, info(http_code: '.$info['http_code'].', url: '.$info['url'].', redirect: '.$info['redirect_url'].')', E_USER_WARNING);
					return false;
				}
				
				//получаем номер пары
				preg_match($time_interval, trim($td[0]), $matches);
				$time = sizeof($matches)-1;
				if($time < 1){
					trigger_error('Can\'t find time, info(http_code: '.$info['http_code'].', url: '.$info['url'].', redirect: '.$info['redirect_url'].')', E_USER_WARNING);
					return false;
				}
				
				//получаем предмет и дополнение
				preg_match('#<strong[^>]*>(.*?)</strong>(.*?)$#isu', $td[1], $matches);
				if(sizeof($matches) < 2){
					trigger_error('Can\'t find lesson, info(http_code: '.$info['http_code'].', url: '.$info['url'].', redirect: '.$info['redirect_url'].')', E_USER_WARNING);
					return false;
				}
				$lesson = mb_strtolower(trim($matches[1]));
				$lesson_type = isset($matches[2]) ? trim($matches[2]) : '';
				
				//Получим кабинет и корпус
				$cab = '';
				$cab_add = '';
				$building = '';
				if(preg_match('#([0-9]+)([\\-()a-zа-я 0-9]*?)\\s+([a-zа-я]+)$#isu', trim($td[2]), $matches)){
					$cab = $matches[1];
					$cab_add = mb_strtolower(trim($matches[2]));
					$building = mb_strtoupper($matches[3]);
				}
				
				//получаем преподавателя
				$teacher_add = '';
				preg_match('#<nobr[^>]*>(.*?)</nobr>#isu', trim($td[3]), $matches);
				$teacher = mb_strtolower(trim($matches[1]));
				if(preg_match('#<span[^>]*>(.*?)</span>#isu', trim($td[3]), $matches)){
					$teacher_add = trim($matches[1]);
				}
				
				if($cab !== ''){
					$cabinet_id = find_cabinet($cab, $building, $cab_add);
				}else{
					$cabinet_id = 0;
				}
				
				if($teacher !== ''){
					$teacher_id = find_teacher($teacher, $teacher_add);
				}else{
					$teacher_id = 0;
				}
				
				$insert[] = compact('week', 'day', 'time', 'group_id', 'lesson', 'lesson_type', 'cabinet_id', 'teacher_id');
			}
		}
	}
	
	$DB->query('DELETE FROM `stud_timetable` WHERE `group_id` = ?s', $group_id);
	$DB->query('UPDATE `stud_groups` SET `last_reload` = NOW() WHERE `id` = ?s', $group_id);
	$DB->query('INSERT INTO `stud_timetable` ?d', $insert);
	
	return true;
}

/**
 * Возвращает id кабинета или добавляет его в БД (stud_cabinets)
 * @param string $cabinet - кабинет
 * @param string $building - корпус
 * @param string $additive - дополнение
 * @return int
 */
function find_cabinet($cabinet, $building, $additive=''){
	global $CABS, $DB;
	$len = sizeof($CABS);
	for($i=0; $i<$len; $i++){
		if((string)$CABS[$i]['cabinet'] === (string)$cabinet && $CABS[$i]['building'] == $building && (string)$CABS[$i]['additive'] === (string)$additive)
			return (int)$CABS[$i]['id'];
	}
	
	$DB->query('INSERT INTO `stud_cabinets` (`cabinet`, `building`, `additive`) VALUES (?s, ?s, ?s)', $cabinet, $building, $additive);
	$id = $DB->insertId();
	$CABS[] = array('id' => $id, 'cabinet' => $cabinet, 'building'=> $building, 'additive' => $additive);
	return (int)$id;
}

/**
 * Возвращает id препода, обновляет его статус (если он не пуст) или добавляет в БД (stud_teachers)
 * @param string $fio - ФИО
 * @param string $additive - статус
 * @return int
 */
function find_teacher($fio, $additive=''){
	global $TEACHS, $DB;
	$len = sizeof($TEACHS);
	for($i=0; $i<$len; $i++){
		if((string)$TEACHS[$i]['fio'] === (string)$fio){
			if($additive !== '' && (string)$TEACHS[$i]['additive'] !== (string) $additive){
				$DB->query('UPDATE `stud_teachers` SET `additive` = ?s WHERE `id` = ?i', $additive, $TEACHS[$i]['id']);
			}
			return (int)$TEACHS[$i]['id'];
		}
	}
	
	$DB->query('INSERT INTO `stud_teachers` (`fio`, `additive`) VALUES (?s, ?s)', $fio, $additive);
	$id = $DB->insertId();
	$TEACHS[] = array('id' => $id, 'fio' => $fio, 'additive' => $additive);
	return (int)$id;
}
?>