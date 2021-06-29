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




function gen_timetable_html(){
	global $DB;
	/*
	 * TODO список опций
	 * Переход к текущей неделе/дню
	 * Скрытие пустых дней/строк
	 * Выделение текущих неделя/дня/пары
	 * Добавлять плюшку препода
	 * Скрытие столбцов
	 * Сохранение размеров столбцов и таблицы +
	 */
	$elems = isset($_COOKIE['timetable']['elements']) ? array_values($_COOKIE['timetable']['elements']) : array();
	$elems_len = sizeof($elems);
	if($elems_len > MAX_ELEMENTS_TIMETABLE){
		//TODO вывод сообщений
		@setcookie('timetable[elements]', array(), time()+86400*30);
		$elems = array();
		$elems_len = 0;
	}
	$options = isset($_COOKIE['timetable']['options']) ? $_COOKIE['timetable']['options'] : array();
	$size_style = isset($options['size']) && is_array($options['size']) ? $options['size'] : array();
	if(sizeof($size_style) > MAX_ELEMENTS_TIMETABLE*5 + 3){
		//TODO вывод сообщений
		@setcookie('timetable[options][size]', array(), time()+86400*30);
		$size_style = array();
	}
	$size_style = get_table_size_html($size_style);
	
	
	$uniq = array();
	
	for($i=0; $i<$elems_len; $i++){
		if(!isset($elems[$i]['type'], $elems[$i]['id'])){
			unset($elems[$i]);
			continue;
		}
		$elems[$i]['type'] = mb_strtolower(trim($elems[$i]['type']));
		switch($elems[$i]['type']){
			case 'cabinet':
			case 'group':
			case 'teacher':
				$elems[$i]['col_id'] = $elems[$i]['type'].'_id';
				$elems[$i]['table_name'] = 'stud_'.$elems[$i]['type'].'s';
				$elems[$i]['gr_name'] = empty($elems[$i]['gr_name']) ? false : trim($elems[$i]['gr_name']);
				break;
			default:
				$elems[$i]['type'] = false;
		}
		if(!$elems[$i]['type']){
			unset($elems[$i]);
			continue;
		}
		
		$elems[$i]['id'] = preg_replace('#[^0-9]#u', '', $elems[$i]['id']);
		if(isset($uniq[$elems[$i]['type'].$elems[$i]['id']])){
			unset($elems[$i]);
			continue;
		}
		
		$dat = '';
		//TODO проверить id группы
		if((int) $elems[$i]['id'] != 0 && !($dat = $DB->getRow('SELECT * FROM ?n WHERE `id` = ?s', $elems[$i]['table_name'], $elems[$i]['id']))){
			unset($elems[$i]);
		}
		if($elems[$i]['gr_name'] === '' || $elems[$i]['gr_name'] === false){
			switch($elems[$i]['type']){
				case 'cabinet':
					$elems[$i]['gr_name'] = $elems[$i]['id'] ? $dat['cabinet'].$dat['cabinet_additive'].' '.$dat['building'] : 'Без кабинета';
					break;
				case 'group':
					$elems[$i]['gr_name'] = $dat['name'];
					break;
				case 'teacher':
					$elems[$i]['gr_name'] = $elems[$i]['id'] ? mb_convert_case($dat['fio'], MB_CASE_TITLE) : 'Без учителя';
					break;
			}
		}
		$uniq[$elems[$i]['type'].$elems[$i]['id']] = false;
	}
	unset($uniq);
	
	$table_head_1 = '
									<th rowspan="2"><div class="cell col-number" data-col="1">№ пары</div></th>
									<th rowspan="2"><div class="cell col-time" data-col="2">Время</div></th>';
	$table_head_2 = '';
	$sticks = '
					<div class="stick" data-col="1"></div>
					<div class="stick" data-col="2"></div>';
	
	
	$elems =array_values($elems);
	$elems_len = sizeof($elems);
	$table = array();
	for($i=0; $i<$elems_len; $i++){
		//генерация html
		for($i1 = 3; $i1 <= 7; $i1++){
			$sticks .= '
					<div class="stick" data-col="'.($i1+$i*5).'"></div>';
		}
		$table_head_1 .= '
									<th colspan="5"><div class="cell gr-'.($i+1).'">'.esc_html($elems[$i]['gr_name']).'</div></th>';
		$table_head_2 .= '
									<th><div class="cell gr-'.($i+1).' col-lesson" data-col="'.(3 + $i*5).'">Урок</div></th>
									<th><div class="cell gr-'.($i+1).' col-lesson-add" data-col="'.(4 + $i*5).'">Тип</div></th>
									<th><div class="cell gr-'.($i+1).' col-group" data-col="'.(5 + $i*5).'">Группа</div></th>
									<th><div class="cell gr-'.($i+1).' col-cabinet" data-col="'.(6 + $i*5).'">Кабинет</div></th>
									<th><div class="cell gr-'.($i+1).' col-teacher" data-col="'.(7 + $i*5).'">Препод</div></th>';
		
		//генерация данных
		
		/*
SELECT
	`tm_t`.*,
	`gr_t`.`name` AS `group_name`, `gr_t`.`faculty_id`,
	`cb_t`.`cabinet`, `cb_t`.`additive` AS `cabinet_additive`, `cb_t`.`building`,
    `th_t`.`fio`, `th_t`.`additive` AS `teacher_additive`
FROM
	`stud_timetable` AS `tm_t`
LEFT JOIN `stud_groups` AS `gr_t`
	ON `tm_t`.`group_id` = `gr_t`.`id`
LEFT JOIN `stud_cabinets` AS `cb_t`
	ON `tm_t`.`cabinet_id` = `cb_t`.`id`
LEFT JOIN `stud_teachers` AS `th_t`
	ON `tm_t`.`teacher_id` = `th_t`.`id`

WHERE `tm_t`.`group_id` = '0200013857'

ORDER BY `tm_t`.`week`, `tm_t`.`day`, `tm_t`.`time`
		 */
		$query = array(
			'`tm_t`.*',
			'`gr_t`.`name` AS `group_name`, `gr_t`.`faculty_id`',
			'`cb_t`.`cabinet`, `cb_t`.`additive` AS `cabinet_additive`, `cb_t`.`building`',
			'`th_t`.`fio`, `th_t`.`additive` AS `teacher_additive`'
		);
		/*
		switch($elems[$i]['type']){
			case 'cabinet':
				unset($query[2]);
				break;
			case 'group':
				unset($query[1]);
				break;
			case 'teacher':
				unset($query[3]);
				break;
		}
		*/
		$res = $DB->getAll(
'SELECT ?p
FROM
	`stud_timetable` AS `tm_t`
LEFT JOIN `stud_groups` AS `gr_t`
	ON `tm_t`.`group_id` = `gr_t`.`id`
LEFT JOIN `stud_cabinets` AS `cb_t`
	ON `tm_t`.`cabinet_id` = `cb_t`.`id`
LEFT JOIN `stud_teachers` AS `th_t`
	ON `tm_t`.`teacher_id` = `th_t`.`id`
WHERE
    `tm_t`.?n = ?s
ORDER BY
	`tm_t`.`week`, `tm_t`.`day`, `tm_t`.`time`',
			implode(', ', $query),
			$elems[$i]['col_id'],
			$elems[$i]['id']
		);
		prepare_timetable_for_html($res, $i+1, $table);
	}
	
	return '
			<div class="timetable-block">
				'.$size_style.'
				<div class="sticks">'.$sticks.'
				</div>
				<div class="timetable-head">
					<div class="timetable-head-wrap">
						<table class="timetable-table">
							<thead>
								<tr>'.$table_head_1.'
								</tr>
								<tr>'.$table_head_2.'
								</tr>
							</thead>
						</table>
					</div>
				</div>
				<div class="timetable-body">
					<table class="timetable-table">'.gen_timetable_body_html($table, $elems_len).'
					</table>
				</div>
				<div class="timetable-extender"><span class="timetable-extender-marker"></span></div>
			</div>';
}

function prepare_timetable_for_html($tm_t, $gr_n, &$table){
	$len = sizeof($tm_t);
	for($i=0; $i<$len; $i++){
		$week = $tm_t[$i]['week'];
		$day = $tm_t[$i]['day'];
		$time = $tm_t[$i]['time'];
		unset($tm_t[$i]['week'], $tm_t[$i]['day'], $tm_t[$i]['time']);
		
		if(!isset($table[$week]))
			$table[$week] = array();
		if(!isset($table[$week][$day]))
			$table[$week][$day] = array();
		if(!isset($table[$week][$day][$time]))
			$table[$week][$day][$time] = array();
		
		$line_c = sizeof($table[$week][$day][$time]);
		$f = 0;
		for($i1=0; $i1<$line_c; $i1++){
			if(!isset($table[$week][$day][$time][$i1][$gr_n])){
				$table[$week][$day][$time][$i1][$gr_n] = $tm_t[$i];
				$f = 1;
				break;
			}
		}
		
		if(!$f){
			$table[$week][$day][$time][] = array(
				$gr_n => $tm_t[$i]
			);
		}
	}
}

function gen_timetable_body_html($table, $elems_len){
	$html_table = '';
	$week_days = array('понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье');
	for($i=0; $i<7; $i++){
		$week_days[$i] = mb_convert_case($week_days[$i], MB_CASE_TITLE);
	}
	//(8:15)|(9:55)|(11:35)|(13:35)|(15:15)|(16:55)|(18:35)|(20:15)
	$time_list = array('', '08:15-09:45', '09:55-11:25', '11:35-13:05', '13:35-15:05', '15:15-16:45', '16:55-18:25', '18:35-20:05', '20:15-21:45');
	
	for($week = 1; $week<=2; $week++){
		$html_table .= '
						<tr class="clean-row row">
							<td colspan="'.(2+$elems_len*5).'"><div class="cell week">Неделя '.$week.'</div></td>
						</tr>';
		for($day=0; $day<6; $day++){
			$html_table .= '
						<tr class="clean-row row">
							<td colspan="'.(2+$elems_len*5).'"><div class="cell day">'.$week_days[$day].'</div></td>
						</tr>';
			for($less=1; $less<=8; $less++){
				$len = isset($table[$week][$day][$less]) ? sizeof($table[$week][$day][$less]) : 1;
				for($line=0; $line<$len; $line++){
					$html_table .= '
						<tr class="default-row row">
							<td><div class="cell col-number" data-col="1">'.$less.'</div></td>
							<td><div class="cell col-time" data-col="2">'.$time_list[$less].'</div></td>';
					for($i = 1; $i <= $elems_len; $i++){
						$tmp = isset($table[$week][$day][$less][$line][$i]) ? $table[$week][$day][$less][$line][$i] : array();
						$lesson = isset($tmp['lesson']) ? $tmp['lesson'] : '';
						$lesson_add = isset($tmp['lesson_type']) ? $tmp['lesson_type'] : '';
						$group_name = isset($tmp['group_name']) ? $tmp['group_name'] : '';
						$cabinet = isset($tmp['cabinet_id']) ? $tmp['cabinet'].$tmp['cabinet_additive'].' '.$tmp['building'] : '';
						$teacher = isset($tmp['teacher_id']) ? '<span class="teacher_fio">'.mb_convert_case($tmp['fio'], MB_CASE_TITLE).'</span> <span class="teacher_add">'.$tmp['teacher_additive'].'</span>' : '<span class="teacher_fio"></span><span class="teacher_add"></span>';
						$html_table .= '
							<td><div class="cell gr-'.$i.' col-lesson" data-col="'.(3+($i-1)*5).'">'.$lesson.'</div></td>
							<td><div class="cell gr-'.$i.' col-lesson-add" data-col="'.(4+($i-1)*5).'">'.$lesson_add.'</div></td>
							<td><div class="cell gr-'.$i.' col-group" data-col="'.(5+($i-1)*5).'">'.$group_name.'</div></td>
							<td><div class="cell gr-'.$i.' col-cabinet" data-col="'.(6+($i-1)*5).'">'.$cabinet.'</div></td>
							<td><div class="cell gr-'.$i.' col-teacher" data-col="'.(7+($i-1)*5).'">'.$teacher.'</div></td>';
					}
					$html_table .= '
						</tr>';
				}
			}
		}
	}
	return $html_table;
}

function get_table_size_html($width){
	$max_width = array(
		0,
		25,
		55,
		100,
		35,
		50,
		35,
		50
	);
	$height = '';
	foreach($width as $key => $value){
		if($key == 'height'){
			$value = absint($value);
			$height = ' .timetable-body{height:'.$value.'px}';
			unset($width[$key]);
		}else{
			$col = absint($key);
			$value = absint($value);
			unset($width[$key]);
			if($col < 1 || $col > MAX_ELEMENTS_TIMETABLE * 5 + 2)
				continue;
			$key = $col <= 2 ? $col : ($col - 3) % 5 + 3;
			if($value < $max_width[$key] || $value > 500)
				continue;
			$width[$col] = '.timetable-block .cell[data-col="'.$col.'"]{width:'.$value.'px}';
		}
	}
	ksort($width, SORT_NUMERIC);
	return sizeof($width) || $height ? '<style>'.implode(' ', $width).$height.'</style>' : '';
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