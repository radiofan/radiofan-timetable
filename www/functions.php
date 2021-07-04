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
 * //TODO замутить статик, при первой проверке
 * @return bool
 */
function is_login(){
	if(is_session_exists()){
		//если сессия создана, но секретный ключ не пробивается
		if(!isset($_SESSION['secret_key']) || sha1($_SERVER['HTTP_USER_AGENT']) !== $_SESSION['secret_key']){
			//грохаем все аутентификационные данные
			session_unset();
			session_destroy();
			setcookie(session_name(), '', time() - SECONDS_PER_DAY);
			setcookie('token', '', time()- SECONDS_PER_DAY);
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
 * //TODO tested
 * @return bool
 */
function check_token_login(){
	if(!isset($_COOKIE['token']))
		return false;
	$token_data = rad_user::decode_cookie_token((string)$_COOKIE['token']);
	$hash = hex_clear($token_data['hash']);
	//тест данных
	if(isset($token_data['data']['user_id']) && mb_strlen($hash) === 64){//длина sha256
		$user_id = absint($token_data['data']['user_id']);
		global $DB;
		$check_token_data = $DB->getRow('SELECT `time_end`, `user_agent` FROM `our_u_tokens` WHERE `user_id` = ?i AND `token` = ?p', $user_id, '0x'.$hash);
		//получение данных токена из БД
		if($check_token_data){
			$sha_user_agent = sha1($_SERVER['HTTP_USER_AGENT']);
			$check_token_data['time_end'] = DateTime::createFromFormat(DB_DATE_FORMAT, $check_token_data['time_end']);
			if(strcmp($sha_user_agent, bin2hex($check_token_data['user_agent'])) == 0 && $check_token_data['time_end']->getTimestamp() > time()){
				//токен подошел
				//обновим время жизни
				$time_end = (new DateTime())->add(new DateInterval('P'.TOKEN_LIVE_DAYS.'D'));
				$DB->query('UPDATE `our_u_tokens` SET `time_end` = ?s WHERE `user_id` = ?i AND `token` = ?p', $time_end->format(DB_DATE_FORMAT), $user_id, '0x'.$hash);
				setcookie('token', (string)$_COOKIE['token'], $time_end->getTimestamp(), '/', null, USE_SSL, 1);
				if(!is_session_exists())
					my_start_session();
				$_SESSION['user_id'] = $user_id;
				$_SESSION['secret_key'] = $sha_user_agent;
				return true;
			}else{
				//токен существует, но либо старый, либо не совпадают user_agent
				$DB->query('DELETE FROM `our_u_tokens` WHERE `user_id` = ?i AND `token` = ?p', $user_id, '0x'.$hash);
			}
		}
	}
	setcookie('token', '', time()-SECONDS_PER_DAY);
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
 * @param $array
 * @param $columns
 * @param string $index
 * @return array|bool
 */
function my_array_column($array, $columns, $index = null){
	$ret = array();
	if(is_array($columns))
		$columns = array_flip($columns);
	foreach($array as $key => &$val){
		if(!is_array($val))
			return false;
		$new_key = $key;
		if($index){
			if(!isset($val[$index]))
				return false;
			$new_key = $val[$index];
		}
		if(is_array($columns)){
			$ret[$new_key] = array_intersect_key($val, $columns);
		}else{
			if(!isset($val[$columns]))
				return false;
			$ret[$new_key] = $val[$columns];
		}
	}
	return $ret;
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

/**
 * Склонение слова после числа.
 *
 * Примеры вызова:
 * num_decline($num, ['книга','книги','книг'])
 * num_decline($num, 'книга', 'книги', 'книг')
 * num_decline($num, 'книга', 'книг')
 *
 * @param  int    $number  Число после которого будет слово. Можно указать число в HTML тегах.
 * @param  string|array  $titles  Варианты склонения или первое слово для кратного 1.
 * @param  string        $param2  Второе слово, если не указано в параметре $titles.
 * @param  string        $param3  Третье слово, если не указано в параметре $titles.
 *
 * @return string 1 книга, 2 книги, 10 книг.
 */
function num_decline($number, $titles, $param2 = '', $param3 = ''){
	if($param2)
		$titles = array($titles, $param2, $param3);

	if(empty($titles[2]))
		$titles[2] = $titles[1]; // когда указано 2 элемента

	$cases = array(2, 0, 1, 1, 1, 2);

	$number = absint($number);

	return $number.' '. $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}

/**
 * Десериализует переданное значение, если оно сериализовано или просто возвращает переданное значение.
 * @param $data
 * @return mixed
 */
function maybe_unserialize($data){
	if(is_serialized($data)){
		return @unserialize(trim($data));
	}
	return $data;
}

/**
 * Проверяет переданное значение, является ли оно сериализованной строкой.
 * @param string $data - начение, которое нужно проверить, является ли оно сериализованными данными
 * @param bool $strict - Точная проверка для конца строки. При true строка всегда должна заканчиваться на символ ; или }
 * @return bool
 */
function is_serialized($data, $strict = true){
	// If it isn't a string, it isn't serialized.
	if(!is_string($data)){
		return false;
	}
	$data = trim($data);
	if('N;' === $data){
		return true;
	}
	if(strlen($data) < 4){
		return false;
	}
	if(':' !== $data[1]){
		return false;
	}
	if($strict){
		$lastc = substr($data, -1);
		if(';' !== $lastc && '}' !== $lastc){
			return false;
		}
	}else{
		$semicolon = strpos($data, ';');
		$brace = strpos($data, '}');
		// Either ; or } must exist.
		if(false === $semicolon && false === $brace){
			return false;
		}
		// But neither must be in the first X characters.
		if(false !== $semicolon && $semicolon < 3){
			return false;
		}
		if(false !== $brace && $brace < 4){
			return false;
		}
	}
	$token = $data[0];
	switch($token){
		case 's':
			if($strict){
				if('"' !== substr($data, -2, 1)){
					return false;
				}
			}else if(false === strpos($data, '"')){
				return false;
			}
		// Or else fall through.
		case 'a':
		case 'O':
			return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
		case 'b':
		case 'i':
		case 'd':
			$end = $strict ? '$' : '';
			return (bool)preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
	}
	return false;
}

///////////////////////////////////////////////////////////////////////////////




///////////////////////////////////////////////////////////////////////////////

/**
 * удаляем все символы кроме [a-zа-я0-9_@#&-$]
 * @param string $text
 * @return string
 */
function coupon_clear($text){
	return preg_replace('/[^a-zа-я0-9_@#&\\-\\$]/iu', '', $text);
}
/**
 * удаляем все символы кроме [a-z0-9_-]
 * @param string $text
 * @return string
 */
function login_clear($text){
	return preg_replace('/[^a-z0-9_\\-]/iu', '', $text);
}
/**
 * удаляем все символы кроме [a-f0-9], переводит в верхний регистр
 * @param string $text
 * @return string
 */
function hex_clear($text){
	return mb_strtoupper(preg_replace('/[^a-f0-9]/iu', '', $text));
}
/**
 * удаляем все символы кроме [a-z0-9!@%&$?*]
 * @param string $text
 * @return string
 */
function password_clear($text){
	return preg_replace('/[^a-z0-9!@%&\\$\\?\\*]/iu', '', $text);
}

/**
 * то же самое что и login_clear но переводит в нижний регистр
 * @param string $text
 * @return string
 * @see login_clear() 
 */
function option_name_clear($text){
	return mb_strtolower(login_clear($text));
}
/**
 * сепаратор - любое кол-во, символы [ :/_,.+-];
 * кавычки " или '
 * @param string $text
 * 'секунды SECOND'
 * 'минуты MINUTE'
 * 'часы HOUR'
 * 'дни DAY'
 * 'месяцы MONTH'
 * 'года YEAR'
 * '"минуты:секунды" MINUTE_SECOND'
 * '"часы:минуты" HOUR_MINUTE'
 * '"дни:часы" DAY_HOUR'
 * '"года:месяцы" YEAR_MONTH'
 * '"часы:минуты:секунды" HOUR_SECOND'
 * '"дни:часы:минуты" DAY_MINUTE'
 * '"дни:часы:минуты:секунды" DAY_SECOND'
 * @return string
 */
function sql_time_interval_clear($text){
	$text = trim($text);
	$matches = array();
	preg_match('#( SECOND$)|( MINUTE$)|( HOUR$)|( DAY$)|( MONTH$)|( YEAR$)|( MINUTE_SECOND$)|( HOUR_MINUTE$)|( DAY_HOUR$)|( YEAR_MONTH$)|( HOUR_SECOND$)|( DAY_MINUTE$)|( DAY_SECOND$)#iu', $text, $matches);
	if(!isset($matches[0]))
		return '';
	$type = mb_strtoupper(mb_substr($matches[0], 1));
	$numb = array();
	switch($type){
		case 'SECOND':
		case 'MINUTE':
		case 'HOUR':
		case 'SECDAYOND':
		case 'MONTH':
		case 'YEAR':
			preg_match('#([0-9]+) '.$type.'$#iu', $text, $matches);
			if(!isset($matches[1]))
				return '';
			$numb = (int) $matches[1];
			if($numb <= 0)
				return '';
			return $numb.' '.$type;
		case 'MINUTE_SECOND':
		case 'HOUR_MINUTE':
		case 'DAY_HOUR':
		case 'YEAR_MONTH':
			preg_match('#[\'"]([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[\'"] '.$type.'$#iu', $text, $matches);
			if(!isset($matches[1], $matches[2]))
				return '';
			$numb = array((int) $matches[1], (int) $matches[2]);
			break;
		case 'HOUR_SECOND':
		case 'DAY_MINUTE':
			preg_match('#[\'"]([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[\'"] '.$type.'$#iu', $text, $matches);
			if(!isset($matches[1], $matches[2], $matches[3]))
				return '';
			$numb = array((int) $matches[1], (int) $matches[2], (int) $matches[3]);
			break;
		case 'DAY_SECOND':
			preg_match('#[\'"]([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[\'"] '.$type.'$#iu', $text, $matches);
			if(!isset($matches[1], $matches[2], $matches[3], $matches[4]))
				return '';
			$numb = array((int) $matches[1], (int) $matches[2], (int) $matches[3], (int) $matches[4]);
			break;
	}
	if(array_sum($numb) <= 0)
		return '';
	return '"'.implode(':', $numb).'" '.$type;
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