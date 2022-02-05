<?php

function log_parse_event($text, $data = ''){
	global $DB;
	if(is_array($data) || is_object($data))
		$data = print_r($data, 1);
	$DB->query('INSERT INTO `log_events` (`time`, `type`, `message`, `addition`) VALUES (MY_NOW(), 1, ?s, ?s)', (string)$text, (string)$data);
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
 * @param $info - CURL info + CURL options
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
 * @param $info - CURL info + CURL options
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
	if(intdiv($info['http_code'], 100) != 2){
		log_parse_event('CURL error['.$status.'](can\'t load timetable): '.$status_text, $info);
		return false;
	}
	
	
	global $DB, $TIME_REGEXP;
	$matches = array();
	$group_id = $info['options'][CURLOPT_POSTFIELDS]['group'];
	
	//получим блок с таблицами расписания
	preg_match_all('#(<div[^>]*?class=["\']schedule[\'"][^>]*>)(.*)$#ismu', $content, $matches);

	$DB->query('UPDATE `stud_groups` SET `last_reload` = MY_NOW() WHERE `id` = ?s', $group_id);
	
	if(!isset($matches[0][0])){
		return false;
	}
	
	//последовательно переберем внутренние дивы, и получим нужный блок
	$content = $matches[2][0];
	$html = $matches[1][0];
	$tr = 1;
	while($tr && preg_match('#(.*?)(</div>|<div[^>]*>)(.*)$#ismu', $content, $matches)){
		$html .=  $matches[1].$matches[2];
		$content = $matches[3];
		if($matches[2] === '</div>'){
			$tr--;
		}else{
			$tr++;
		}
	}
	unset($content);
	
	//выберем таблицы с заголовками
	preg_match_all('#<h3[^>]*>(.*?)</h3>.*?<table[^>]*>(.*?)</table>#isu', $html, $matches);
	unset($html);
	
	if(empty($matches[1]) || empty($matches[2])){
		return false;
	}
	
	$weeks = $matches[1];
	$tables = $matches[2];
	$len = sizeof($tables);
	
	$today = new DateTime();
	$today->setTime(0, 0);
	
	for($i=0; $i<$len; $i++){
		preg_match('#</thead>(.*)$#isum', $tables[$i], $matches);
		if(empty($matches[1])){
			log_parse_event('Timetable #'.$i.' hasn\'t thead', $info);
			return false;
		}
		$tables[$i] = $matches[1];
		preg_match_all('#<tr[^>]*>(.*?)</tr>#isum', $tables[$i], $matches);
		$tr = $matches[1];
		$tr_c = sizeof($tr);
		/** @var $week_start_day - день старта недели */
		$week_start_day = '';
		/** @var $date - дата дня */
		$date = '';
		$date_obj = '';
		preg_match('#\\(([0-9])+\s+неделя\\)#isu', $weeks[$i], $matches);
		if(empty($matches[1])){
			log_parse_event('Timetable #'.$i.' head hasn\'t week', $info);
			return false;
		}
		/** @var $week - номер недели (1, 2) */
		$week = $matches[1];
		$insert = array();
		for($tr_n=0; $tr_n<$tr_c; $tr_n++){
			if(preg_match('#class=[\'"][^\'"]*?day[^\'"]*[\'"]#isu', $tr[$tr_n])){
				//эта строчка содержит день
				preg_match('#<th[^>]*>(.*?)</th>#isu', $tr[$tr_n], $matches);
				if(empty($matches[1])){
					log_parse_event('Timetable #'.$i.', tr#'.$tr_n.' day hasn\'t <th>', $info);
					return false;
				}
				//извлекаем дату из него
				$a = trim(mb_strtolower($matches[1]));
				preg_match('#[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{2,4}#isu', $a, $matches);
				if(empty($matches[0])){
					log_parse_event('Timetable #'.$i.', tr#'.$tr_n.' day hasn\'t date', $info);
					return false;
				}
				$date_obj = DateTime::createFromFormat('d.m.Y|', $matches[0]);
				$date = $date_obj->format(DB_DATE_FORMAT);
				if($week_start_day === ''){
					$week_start_day = (clone $date_obj)->sub(new DateInterval('P'.($date_obj->format('N') - 1).'D'));
				}
			}else{
				//найдена строчка с данными расписания, но строчка с днем еще не найдена
				if($date === ''){
					log_parse_event('Timetable #'.$i.', tr#'.$tr_n.' day still not found', $info);
					return false;
				//найдена строчка с данными но день уже прошел
				}else if($date_obj < $today){
					continue;
				}
				preg_match_all('#<td[^>]*>(.*?)</td>#isu', $tr[$tr_n], $matches);
				$td = $matches[1];
				if(sizeof($td) != 4){
					log_parse_event('Timetable #'.$i.', tr#'.$tr_n.' has '.sizeof($td).' <td>', $info);
					return false;
				}
				
				//получаем номер пары
				preg_match($TIME_REGEXP, trim($td[0]), $matches);
				$time = sizeof($matches)-2;
				if($time < 0){
					log_parse_event('Timetable #'.$i.', tr#'.$tr_n.' can\'t find time', $info);
					return false;
				}
				
				//получаем предмет и дополнение
				preg_match('#<strong[^>]*>(.*?)</strong>(.*?)$#isu', $td[1], $matches);
				if(sizeof($matches) < 2){
					log_parse_event('Timetable #'.$i.', tr#'.$tr_n.' can\'t find lesson', $info);
					return false;
				}
				$lesson = mb_strtolower(trim($matches[1]));
				$lesson_type = isset($matches[2]) ? trim($matches[2]) : '';
				
				//Получим кабинет и корпус
				$cab = '';
				$cab_add = '';
				$building = '';
				$td[2] = trim($td[2]);
				if(preg_match('#([0-9]+)([\\-()a-zа-я 0-9]*?)\\s+([a-zа-я]+)$#isu', $td[2], $matches)){
					$cab = $matches[1];
					$cab_add = mb_strtolower(trim($matches[2]));
					$building = mb_strtoupper($matches[3]);
				}else if(preg_match('#манеж#isu', $td[2], $matches)){
					$building = 'МАНЕЖ';
				}else if($td[2] !== ''){
					log_parse_event('Timetable #'.$i.', tr#'.$tr_n.' cabinet not parse', $info);
				}
				
				//получаем преподавателя
				$teacher_add = '';
				$teacher = '';
				if(preg_match('#<nobr[^>]*>(.*?)</nobr>#isu', trim($td[3]), $matches)){
					$teacher = mb_strtolower(trim($matches[1]));
				}else{
					log_parse_event('Timetable #'.$i.', tr#'.$tr_n.' teacher not parse', $info);
				}
				if(preg_match('#<span[^>]*>(.*?)</span>#isu', trim($td[3]), $matches)){
					$teacher_add = trim($matches[1]);
				}
				
				//ищем ID урока, кабинета и препода
				$lesson_id = find_lesson($lesson);
				
				if($cab !== '' || $building !== ''){
					$cabinet_id = find_cabinet($cab, $building, $cab_add);
				}else{
					$cabinet_id = null;
				}
				
				if($teacher !== ''){
					$teacher_id = find_teacher($teacher, $teacher_add);
				}else{
					$teacher_id = null;
				}
				
				$insert[] = compact('date', 'week', 'time', 'group_id', 'lesson_id', 'lesson_type', 'cabinet_id', 'teacher_id');
			}
		}
		//получили данные для одной недели
		if($week_start_day === '' || !sizeof($insert))
			continue;
		$week_start_day_str = $week_start_day->format(DB_DATE_FORMAT);
		$DB->startTransaction();
		$DB->query(
			'DELETE FROM `stud_timetable` WHERE `group_id` = ?s AND `week` = ?s AND `date` >= ?s AND `date` <= ?s + INTERVAL 6 DAY',
			$group_id,
			$week,
			$today > $week_start_day ? $today->format(DB_DATE_FORMAT) : $week_start_day_str,
			$week_start_day_str
		);
		$delete_rows = $DB->affectedRows();
		$DB->query('INSERT INTO `stud_timetable` ?d', $insert);
		$insert_rows = $DB->affectedRows();
		$DB->commit();
		if($delete_rows == 0){
			log_parse_event('Add new week('.$week.'; '.$week_start_day_str.' - '.$week_start_day->add(new DateInterval('P6D'))->format(DB_DATETIME_FORMAT).') for group('.$group_id.'); added '.$insert_rows.' rows');
		}else if($delete_rows != $insert_rows){
			log_parse_event('Update week('.$week.'; '.$week_start_day_str.' - '.$week_start_day->add(new DateInterval('P6D'))->format(DB_DATETIME_FORMAT).') for group('.$group_id.'); added '.$insert_rows.' rows, delete '.$delete_rows.' rows');
		}
	}
	
	return true;
}

/**
 * Возвращает id предмета и добавляет его в БД если его не существует (stud_lessons)
 * @param string $lesson - название предмета
 * @return int
 */
function find_lesson($lesson){
	global $LESSONS_SHA_ID, $DB;
	$key = sha1($lesson);
	if(isset($LESSONS_SHA_ID[$key])){
		return (int)$LESSONS_SHA_ID[$key];
	}

	$DB->query('INSERT INTO `stud_lessons` (`parse_text`, `alias`, `data`) VALUES (?s, \'\', \'\')', $lesson);
	$id = $DB->insertId();
	log_parse_event('Add new lesson #'.$id);
	$LESSONS_SHA_ID[$key] = $id;
	return (int)$id;
}

/**
 * Возвращает id кабинета и добавляет его в БД если его не существует (stud_cabinets)
 * @param string $cabinet - кабинет
 * @param string $building - корпус
 * @param string $additive - дополнение
 * @return int
 */
function find_cabinet($cabinet, $building, $additive=''){
	global $CABINETS_SHA_ID, $DB;
	$key = sha1($cabinet.'%'.$additive.'%'.$building);
	if(isset($CABINETS_SHA_ID[$key])){
		return (int)$CABINETS_SHA_ID[$key];
	}
	
	$DB->query('INSERT INTO `stud_cabinets` (`cabinet`, `building`, `additive`) VALUES (?s, ?s, ?s)', $cabinet, $building, $additive);
	$id = $DB->insertId();
	log_parse_event('Add new cabinet #'.$id);
	$CABINETS_SHA_ID[$key] = $id;
	return (int)$id;
}

/**
 * Возвращает id препода и добавляет его в БД если его не существует (stud_teachers)
 * @param string $fio - ФИО
 * @param string $additive - статус
 * @return int
 */
function find_teacher($fio, $additive=''){
	global $TEACHERS_SHA_ID, $DB;
	$key = sha1($fio.'%'.$additive);
	if(isset($TEACHERS_SHA_ID[$key])){
		return (int)$TEACHERS_SHA_ID[$key];
	}
	
	$DB->query('INSERT INTO `stud_teachers` (`fio`, `additive`) VALUES (?s, ?s)', $fio, $additive);
	$id = $DB->insertId();
	log_parse_event('Add new teacher #'.$id);
	$TEACHERS_SHA_ID[$key] = $id;
	return (int)$id;
}
?>