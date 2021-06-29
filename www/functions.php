<?php

/**
 * возвращает целое положительное число
 * @param mixed $a
 * @return int
 */
function absint($a){
	return abs(intval($a));
}

/**
 * очистка текста от html (заменяет на сущности)
 * @param string $text
 * @return string
 */
function esc_html($text){
	return htmlspecialchars($text, ENT_NOQUOTES|ENT_SUBSTITUTE|ENT_HTML401, 'UTF-8', 0);
}

/**
 * подключает файл, возвращает то что вывел данный файл
 * @param $path - путь к файлу
 * @return false|string
 */
function include_file($path){
	if(is_file($path)){
		ob_start();
		include $path;
		return ob_get_clean();
	}
	return false;
}

/**
 * создает страницу из хэдера, шаблона и футера,
 * быстрее,
 * отсутсвует экранировка
 * @param $file - путь к шаблону
 * @param $data - ассоциативный массив ключа и значения параметра для вставки в шаблон
 * @return string|false
 */
function rad_template_old($file, $data){
	if(!is_file($file)){
		return false;
	}
	$tpl = file_get_contents($file);
	//trigger_error(print_r($data, 1), E_USER_NOTICE);
	foreach($data as $key => $value){
		$tpl = str_replace('{'.$key.'}', $value, $tpl);
	}
	return include_file(MAIN_DIR.'header.php').$tpl.include_file(MAIN_DIR.'footer.php');
}

/**
 * проверка на вход на сайт
 * @return bool
 */
function is_login(){
	if(is_session_exists()){
		if(!isset($_SESSION['secret_key']) || sha1($_SERVER['HTTP_USER_AGENT']) !== $_SESSION['secret_key']){
			session_unset();
			session_destroy();
			setcookie(session_name(), '', time() - 3600*24);
			setcookie('token', '', time()- 3600*24);
			setcookie('user_id', '', time()- 3600*24);
			return false;
		}
		return !empty($_SESSION['user_id']);
	}else{
		return check_token_login();
	}
}

/**
 * Тоже проверка входа на сайт, только по токену в куки
 * Если куки верны, то запускается сессия
 * Если куки не верны, то они удаляются
 * @return bool
 */
function check_token_login(){
	if(!isset($_COOKIE['token'], $_COOKIE['user_id']))
		return false;
	$user_id = absint($_COOKIE['user_id']);
	global $DB;
	$options = $DB->getOne('SELECT `options` FROM `our_users` WHERE `id` = ?i', $user_id);
	if($options === false)
		return false;
	$options = unserialize($options);
	if(isset($options['token']) && (string)$options['token'] === (string)$_COOKIE['token']){
		if(!is_session_exists())
			my_start_session();
		setcookie('token', $options['token'], time()+86400*7, '/', null, null, 1);
		setcookie('user_id', $user_id, time()+86400*7, '/', null, null, 1);
		$_SESSION['user_id'] = $user_id;
		$_SESSION['secret_key'] = sha1($_SERVER['HTTP_USER_AGENT']);
		return true;
	}else{
		setcookie('token', '', time()-3600*24);
		setcookie('user_id', '', time()-3600*24);
	}
	return false;
}

/**
 * обертка can_user() для текущего пользователя
 * @see rad_user::can_user()
 * @param string $role
 * @return bool
 */
function can_user($role){
	global $USER;
	return $USER->can_user($role);
}

/**
 * проверка на существование сессии и её запуск, если данные в ней есть, но сессия не запущена
 * @return bool
 */
function is_session_exists(){
	if(!session_id() && (isset($_REQUEST[session_name()]) || isset($_COOKIE[session_name()]))){
	//if(!session_id() && isset($_REQUEST[session_name()])){
		$sessid = isset($_REQUEST[session_name()]) ? $_REQUEST[session_name()] : '';
		if(!$sessid)
			$sessid = $_COOKIE[session_name()];
		session_id($sessid);
		my_start_session();
		if(empty($_SESSION)){
			session_destroy();
			return false;
		}
		return true;
	}else if(session_id()){
		return true;
	}
	return false;
}

/** запуск сессии с нужными параметрами */
function my_start_session(){
	$params = session_get_cookie_params();
	$params['httponly'] = true;
	session_set_cookie_params($params['lifetime'], $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	
	session_start();
}

/**
 * возвращает протокол, по которому обратились к странице
 * @return string
 */
function get_protocol(){
	if((isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')){
		return 'https';
	}else{
		return 'http';
	}
}

/**
 * Перенаправляет на страницу с указанным url
 * @param string $url
 */
function redirect($url){
	header('Location: '.$url);
	die();
}

/**
 * выводит данные мимо буфера
 * @param $data - текст для вывода
 * @param bool $flush - отправить ли данные моментально?
 * @return int - количество буферов
 */
function ob_ignore($data, $flush = false){
	$ob = array();
	$len = ob_get_level();
	for($i=0; $i<$len; $i++){
		$ob[] = ob_get_contents();
		ob_end_clean();
	}
	
	echo $data;
	if($flush)
		flush();
	
	for($i=$len-1; $i>=0; $i--){
		ob_start();
		echo $ob[$i];
	}
	return sizeof($ob);
}

/**
 * возвращает форматированный объем памяти
 * @param int|float $size
 * @return string
 */
function round_memsize($size){
	$unit = 'b';
	
	if($size > 1024){
		$size = (float) $size / 1024;
		$unit = 'Kb';
	}
	
	if($size > 1024){
		$size = (float) $size / 1024;
		$unit = 'Mb';
	}
	
	if($size > 1024){
		$size = (float) $size / 1024;
		$unit = 'Gb';
	}
	
	if($size < 100){
		$size = round($size, 2);
	}else if($size < 1000){
		$size = round($size, 1);
	}else{
		$size = round($size, 0);
	}
	
	return $size.' '.$unit;
}

///////////////////////////////////////////////////////////////////////////////

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
					$teacher_add = mb_strtolower(trim($matches[1]));
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

///////////////////////////////////////////////////////////////////////////////

function coupon_clear($text){
	return preg_replace('/[^a-zа-я0-9_@#&\\-\\$]/iu', '', $text);
}
function login_clear($text){
	return preg_replace('/[^a-z0-9_\\-]/iu', '', $text);
}
function password_clear($text){
	return preg_replace('/[^a-z0-9!@%&\\$\\?\\*]/iu', '', $text);
}


////////////////////////////////////////////////////////////////////////////////
// не используются
////////////////////////////////////////////////////////////////////////////////


//возвращает форматированный вес файла
//path - путь до файла
function get_filesize($path){
	if(!file_exists($path))
		return false;
	return round_memsize(filesize($path));
	
}

//отправка файла на скачку
//path - путь до файла
//filename - имя скачиваемого файла
function file_force_download($path, $filename){
	if(!file_exists($path))
		return false;
	$filename = validation_filename($filename);
	if($filename == '')
		return false;
	if(ob_get_level())
		ob_end_clean();
	
	// заставляем браузер показать окно сохранения файла
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename='.$filename);
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($path));
	// читаем файл и отправляем его пользователю
	readfile($path);
	exit;
}

//оставляет в имени только допустимые символы
function validation_filename($filename){
	$filename = preg_replace('#[^a-zA-Z0-9А-Яа-яЁё\\.\\-_ ]#u', '', $filename);
	$filename = trim($filename, ' .-_');
	return $filename;
}

function validation_prefix($prefix){
	$prefix = preg_replace('#[^a-zA-Z0-9\\.\\-_]#u', '', $prefix);
	$prefix = ltrim($prefix, '.-_');
	return $prefix;
}



//имеется экранировка
//медленее
function rad_template($file, $data){
	$tpl = file_get_contents($file);
	$values = array_values($data);
	$vars = array_keys($data);
	$len = sizeof($data);
	for($i=0; $i<$len; $i++){
		$vars[$i] = '#{'.preg_quote($vars[$i], '}').'[^\\\\]?}#u';
	}
	$tpl = preg_replace($vars, $values, $tpl);
	$tpl = str_replace('\\}', '}', $tpl);
	return include_file(MAIN_DIR."header.php").$tpl.include_file(MAIN_DIR."footer.php");
}

//очистка
function id_clear($id){
	$id = prefix_clear($id);
	$id = preg_replace('#_+$#', '', $id);
	return strlen($id)? $id : false;
}

//очистка
function prefix_clear($prefix){
	$prefix = preg_replace('#[^a-zA-Z0-9_]#', '', $prefix);
	$prefix = preg_replace('#^[0-9]+#', '', $prefix);
	return strlen($prefix)? $prefix : false;
}

//получает список файлов
//path - путь до директории
//ext - расширение файлов
//name_pattern - шаблон имени (регулярное выражение)
function file_list($path, $ext='', $name_pattern='^.*?'){
	$files = scandir($path);
	$pattern = '#'.$name_pattern.preg_quote($ext).'$#';
	$len = sizeof($files);
	for($i=0; $i<$len; $i++){
		if(!preg_match($pattern, $files[$i])){
			unset($files[$i]);
		}
	}
	return array_values($files);
}

?>