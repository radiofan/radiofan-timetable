<?php
if(!defined('MAIN_DIR'))
	die();

//перебирает события в массиве $_REQUEST
//и вызывает для них обработчики
//возвращает резльтаты работы функций в виде массива
//array($_REQUEST['action'][0] => result, $_REQUEST['action'][1] => result, ...)
function do_actions(){
	if(isset($_REQUEST['action'])){
		$actions = $_REQUEST['action'];
		if(!is_array($actions))
			$actions = array($actions);
		$ret = array();
		foreach($actions as $act){
			if(function_exists('action_'.$act)){
				$ret[$act] = call_user_func('action_'.$act);
			}
		}
		return $ret;
	}
}

//событие входа пользователя
function action_login(){
	global $DB, $USER;
	if(is_login())
		return true;
	if(!isset($_POST['login'], $_POST['password']))
		return false;
	$login = login_clear($_POST['login']);
	//посоленый хеш SHA-1
	$password = sha1(SALT . trim($_POST['password']), 1);
	
	$data = $DB->getRow('SELECT `password`, `id` FROM `our_u_users` WHERE `login` = ?s', $login);
	if($data === false)
		return false;
	if(!hash_equals($data['password'], $password))
		return false;
	if(!is_session_exists())
		my_start_session();
	$_SESSION['user_id'] = $data['id'];
	$sha_user_agent = sha1($_SERVER['HTTP_USER_AGENT']);
	$USER->load_user($data['id']);
	//создадим токен для пользователя
	//для каждого user_agent он свой
	if(isset($_POST['remember']) && $_POST['remember']){
		//TODO: переделать логику токенов
		//TODO clear_old_tokens
		$tokens_arr = $USER->get_option('login_tokens');
		$tokens_arr = is_null($tokens_arr) || !is_array($tokens_arr) ? array() : $tokens_arr;
		//проверка ограничения на колличество токенов
		if(sizeof($tokens_arr) + 1 > MAX_TOKEN_REMEMBER && !$USER->can_user('ignore_max_token_remember')){
			global $ALERTS;
			$ALERTS->add_alert('Достигнут предел запоминания, данный вход не запомнен.', 'warning');
		}else{
			$time_start = time();
			$token = hash('sha256', mt_rand().$data['password'].$sha_user_agent.$time_start.$USER->get_id());
			$tokens_arr[$sha_user_agent] = array('time_start' => $time_start, 'time_end' => $time_start+86400*TOKEN_LIVE_DAYS, 'token' => $token);
			$USER->set_option('login_tokens', $tokens_arr);
			$USER->update_options('login_tokens');
			
			/*
			 * собираем токен
			 * имеет вид base64.base64
			 * 1-ая часть - JSON массив, ['user_id':int]
			 * 2-ая часть - строка, токен
			 */
			$data_token = array('user_id' => $USER->get_id()); 
			$token = base64_encode(json_encode($data_token)).'.'.base64_encode($token);
			setcookie('token', $token, $time_start+86400*TOKEN_LIVE_DAYS, '/', null, USE_SSL, 1);
		}
	}
	//$_SESSION['options'] = unserialize($data['options']);
	//небольшая защита от кражи сессии
	$_SESSION['secret_key'] = $sha_user_agent;
	return true;
}

//событие выхода пользователя
function action_exit(){
	global $USER;
	$USER->user_logout();
	redirect('/login');
}


/*
function action_update_data(){
	global $DATA;
	//die(print_r($_POST, 1));
	$referer = parse_url($_SERVER['HTTP_REFERER']);
	$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	if($referer['host'] !== $_SERVER['HTTP_HOST'] || $referer['path'] !== '/admin' || ($request !== '/admin' && $request !== '/ajax.php'))
		return false;
	if(CLEAR_POST)
		header('Location: /admin');
	return true;
}
*/


//событие ввода купона
function action_use_coupon(){
	if(!is_login())
		return false;
	if(empty($_POST['coupon']))
		return false;
	$text = $_POST['coupon'];
	$text = coupon_clear($text);
	if($text === '')
		return false;
	/*
	 * 'status'
	 * 0 - OK
	 * 1 - купон не существует
	 * 2 - юзер уже ввел этот купон
	 * 3 - купон использовал другой юзер
	 * 4 - ошибка БД
	 */
	global $DB, $USER;
	$res = $DB->getRow('SELECT * FROM `coupons` WHERE `coupon` = ?s', $text);
	if(empty($res))
		return array('status' => 1, 'message' => 'Неверный купон');
	if(empty($res['user_id'])){
		if($DB->query('UPDATE `coupons` SET `user_id` = ?i, `work_end` = NOW() + INTERVAL ?p WHERE `id` = ?i', $USER->get_id(), $res['work_time'], $res['id']) === false){
			return array('status' => 4, 'message' => 'Не удалось использовать купон');
		}else{
			return array('status' => 0, 'message' => 'Купон активирован');
		}
	}else{
		if($res['user_id'] == $USER->get_id()){
			return array('status' => 2, 'message' => 'Вы уже использовали этот купон');
		}else{
			return array('status' => 3, 'message' => 'Неверный купон');
		}
	}
}

function action_add_user(){
	global $USER, $DB;
	if(!isset($_POST['login'], $_POST['password'], $_POST['level'])){
		return false;
	}
	if(!$USER->can_user('edit_users'))
		return false;
	/*
	 * 'status'
	 * 0 - OK
	 * 1 - данные отсутствуют
	 * 2 - коллизия логинов
	 * 3 - ошибка БД
	 */
	$login = login_clear($_POST['login']);
	if($login === '')
		return array('status' => 1, 'message' => 'Логин пуст');
	if($DB->getOne('SELECT `id` FROM `our_u_users` WHERE login = ?s', $login))
		return array('status' => 2, 'message' => 'Такой логин уже существует');
	$password = password_clear($_POST['password']);
	if(mb_strlen($password) < 6)
		return array('status' => 1, 'message' => 'Пароль пуст');
	$password = '0x'.sha1(SALT.$password);
	$level = absint($_POST['level']);
	if($level < $USER::USER || $level > $USER::ADMIN)
		return array('status' => 1, 'message' => 'Ошибочный уровень пользователя');
	/*
	if($level > $USER->get_user_level())
		return array('status' => 1, 'message' => 'Админ меньшего уровня не может создать пользователя большего уровня');
	*/
	$user_roles = array();
	if(isset($_POST['user_roles']) && is_array($_POST['user_roles'])){
		$roles_range = $USER->get_roles_range();
		$len = sizeof($_POST['user_roles']);
		for($i=0; $i<$len; $i++){
			if(isset($roles_range[$_POST['user_roles'][$i]]))
				$user_roles[] = $_POST['user_roles'][$i];
		}
	}
	if($DB->query('INSERT INTO `our_u_users` (`login`, `password`, `level`, `roles`, `options`) VALUES(?s, ?p, ?i, ?s, ?s)', $login, $password, $level, serialize($user_roles), serialize(array())) === false){
		return array('status' => 3, 'message' => 'Не удалось добавить пользователя');
	}else{
		return array('status' => 0, 'message' => 'пользователь добавлен');
	}
}


//событие получения данных (обычно для получения чего-то с помощью ajax)
function action_get_data(){
	global $DATA;
	
	if(!isset($_REQUEST['options']))
		die();
	$options = $_REQUEST['options'];
	
	$allowed = array();
	
	$ret = '';
	if(is_array($options)){
		$options = array_intersect($allowed, $options);
		return $DATA->get_array($options);
	}else{
		return in_array($options, $allowed) ? $DATA->get($options) : null;
	}
}

?>