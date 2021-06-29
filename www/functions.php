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