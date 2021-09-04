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
 * обертка can_user() для текущего пользователя
 * @see rad_user_roles::can_user()
 * @param string|string[] $role
 * @return bool
 */
function can_user($role){
	global $USER;
	return $USER->roles->can_user($role);
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
 * возвращает ip пользователя
 * @return string
 */
function get_ip() {
	if(!empty($_SERVER['HTTP_CLIENT_IP'])){
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	}else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}else{
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	return $ip;
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
 * переводит количество секунд во время
 * @param int $sec - 0, SECONDS_PER_DAY
 * @param bool $hour_zero - предшествующий 0 для часов
 * @return string
 */
function seconds_to_time($sec, $hour_zero = true){
	$sec = absint($sec);
	$tmp = intdiv($sec, SECONDS_PER_HOUR);
	if($hour_zero){
		$tmp .= str_pad($tmp, 2, '0', STR_PAD_LEFT);
	}
	$tmp .= ':'.str_pad(intdiv($sec % SECONDS_PER_HOUR, SECONDS_PER_MINUTE), 2, '0', STR_PAD_LEFT);
	return $tmp;
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
 * Возвращает строку смещения относительно московского времени
 * типа 'МСК' (если смещение == 0), 'МСК+4', 'МСК-3', 'МСК+2:30'
 * @param DateTime $date_time
 * @return string 
 */
function get_msk_time_offset($date_time){
	$offset = $date_time->getOffset() / SECONDS_PER_HOUR - 3.0;
	$offset_str = '';
	if($offset != 0){
		$offset_str = ($offset >= 0 ? '+' : '-').absint($offset);
		$offset = abs($offset);
		if(intval($offset) != $offset){
			$offset -= intval($offset);
			$offset_str .= ':'.round(60 * $offset);
		}
	}
	return 'МСК'.$offset_str;
}

/**
 * Проверяет существование ФАЙЛА
 * @param $path - путь до файла
 * @return bool
 */
function check_file($path){
	return file_exists($path) && is_file($path);
}

/**
 * Проверяет существование папки
 * @param $path - путь до папки
 * @return bool
 */
function check_dir($path){
	return file_exists($path) && is_dir($path);
}

//получает список файлов
//path - путь до директории
//ext - расширение файлов (c точкой)
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

/**
 * возвращает объем файла в байтах, обновляет кэш
 * @param string $path - путь до файла
 * @return false|int - false если файл не найден
 */
function get_filesize($path){
	if(!check_file($path))
		return false;
	clearstatcache(1, $path);
	return filesize($path);

}

///////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// не используются
////////////////////////////////////////////////////////////////////////////////



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

?>