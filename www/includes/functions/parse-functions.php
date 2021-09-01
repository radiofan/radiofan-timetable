<?php

function log_parse_event($text, $data = ''){
	global $DB;
	if(is_array($data) || is_object($data))
		$data = print_r($data, 1);
	$DB->query('INSERT INTO `log_events` (`time`, `type`, `message`, `addition`) VALUES (NOW(), 1, ?s, ?s)', (string)$text, (string)$data);
}

function update_first_week_day(){
	global $DB, $DATA;
	$gr_id = $DB->getOne('SELECT `group_id` FROM `stud_timetable` LIMIT 1');
	if($gr_id === false){
		$DATA->set('first_week_day', null);
		$DATA->update('first_week_day');
		trigger_error('stud_timetable is empty', E_USER_WARNING);
		return false;
	}
	
	$week_n = false;
	$matches = array();
	for($i=0; $i<10;$i++){
		$ret = rad_parser::get_page_content(
			'https://www.altstu.ru/main/schedule/?group_id='.$gr_id,
			array(
				'post_data' => array(
					'group'   => $gr_id
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
		));
		if(!$ret['status']){
			preg_match('#<h3[^>]*?class=["\']?current[\'"]?[^>]*?>(.*?)</h3>#isu', $ret['content'], $matches);
			if(isset($matches[1])){
				$week_n = $matches[1];
				break;
			}
		}
		usleep((rand()%375+125)*1000);
	}
	if(!$week_n){
		$DATA->set('first_week_day', null);
		$DATA->update('first_week_day');
		trigger_error('can\'t load current week gr_id='.$gr_id, E_USER_WARNING);
		return false;
	}
	
	preg_match('#[0-9]+#isu',$week_n, $matches);
	$week_n = (int)$matches[0];
	$today = new DateTime();
	$tmp = (int)$today->format('N')-1;
	$tmp += ($week_n-1)*7;
	$today->sub(new DateInterval('P'.$tmp.'D'));
	$today->setTime(0, 0);
	$DATA->set('first_week_day', $today);
	$DATA->update('first_week_day');
	
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
		log_parse_event('Can\'t find select#id_faculty', $html);
		return false;
	}
	$html = $matches[0];
	preg_match_all('#<option[^>]*?value=["\']([^\'"]*?)[\'"][^>]*>(.*?)</option>#isu', $html, $matches);
	if(empty($matches[1])){
		log_parse_event('Can\'t find option in select#id_faculty', $html);
		return false;
	}
	$len = sizeof($matches[1]);
	for($i=0; $i<$len; $i++){
		$ret[] = array('id' => $matches[1][$i], 'name' => !empty($matches[2][$i]) ? $matches[2][$i] : '%noname%', 'abbr' => '');
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
		log_parse_event('CURL error['.$status.'](can\'t load groups): '.$status_text, $info);
		return false;
	}
	$dat = json_decode($content, 1);
	if(json_last_error() != JSON_ERROR_NONE){
		log_parse_event('JSON decode error['.json_last_error().'](can\'t parse groups): '.json_last_error_msg(), $info);
		return false;
	}
	
	$len = is_array($dat) ? sizeof($dat) : 0;
	if($len == 0)
		return false;
	$fac_id = '';
	$all_id = array();
	
	$DB->query('LOCK TABLES `stud_groups` WRITE');
	for($i=0; $i<$len; $i++){
		$fac_id = (string)$dat[$i]['faculty_id'];
		$id = (string)$dat[$i]['id'];
		$all_id[] = $id;
		
		if($DB->getOne('SELECT COUNT(*) FROM `stud_groups` WHERE `id` = ?s', $id)){
			unset($dat[$i]);
			continue;
		}
		
		$tmp = array('faculty_id' => $fac_id, 'id' => $id, 'name' => $dat[$i]['name'], 'last_reload' => '1980-01-01 00:00:00', 'status' => 1);
		unset($dat[$i]['faculty_id'], $dat[$i]['id'], $dat[$i]['name']);
		if($dat[$i])
			$tmp['data'] = serialize($dat[$i]);
		
		$DB->query('INSERT INTO `stud_groups` ?d', array($tmp));
		unset($dat[$i]);
	}
	$DB->query('UPDATE `stud_groups` SET `status` = 0 WHERE `faculty_id` = ?s AND `id` NOT IN (?a)', $fac_id, $all_id);
	$DB->query('UNLOCK TABLES');
	
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
		log_parse_event('CURL error['.$status.'](can\'t load timetable): '.$status_text, $info);
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