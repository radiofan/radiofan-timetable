<?php

/**
 * событие входа пользователя, если не ajax запрос и все прошло успешно, то редирктит на главную
 * @param $_POST = ['login' => string, 'password' => string]
 * @return bool|array [status => int, 'message' => string]
 */
function action_login(){
	if(!isset($_POST['login'], $_POST['password']))
		return false;

	global $USER, $ALERTS;

	if($USER->get_id())
		return true;
	if(!$USER->load_by_loginpass($_POST['login'], $_POST['password'])){
		if(AJAX){
			return array('status' => 1, 'message' => STR_ACTION_LOGIN_1);
		}else{
			$ALERTS->add_alert(STR_ACTION_LOGIN_1, 'info');
			return false;
		}
	}
	$type = isset($_POST['remember']) && $_POST['remember'] ? 'remember' : 'session';
	$ret = $USER->create_token($type);
	if($ret['status'] == -3){
		if(!AJAX){
			$ALERTS->add_alert(STR_ACTION_LOGIN_2, 'danger');
		}
		return array('status' => 2, 'message' => STR_ACTION_LOGIN_2);
	}else if($ret['status']){
		if(!AJAX){
			$ALERTS->add_alert(STR_UNDEFINED_ERROR, 'danger');
		}
		return array('status' => 3, 'message' => STR_UNDEFINED_ERROR);
	}else{
		$end_time = $type == 'session' ? 0 : $ret['date_end_token']->getTimestamp();
		setcookie('sid', $ret['token'], $end_time, '/', null, USE_SSL, 1);
	}
	if(AJAX){
		return true;
	}else{
		redirect('/');
	}
}

/**
 * событие регистрации пользователя
 * @param $_POST = ['login' => string, 'password' => string, 'email' => string]
 * @return false|array [status => int, 'message' => string]
 */
function action_signin(){
	if(!isset($_POST['login'], $_POST['password'], $_POST['email']))
		return false;
	global $USER, $ALERTS;
	if($USER->get_id())
		return false;
	
	$new_user_id = rad_user::create_new_user($_POST['login'], $_POST['password'], $_POST['email'], rad_user_roles::USER);
	
	$ret = '';
	$status = 0;
	switch($new_user_id){
		case -1:
			$ret = STR_ACTION_SIGNIN_1;
			$status = 1;
			break;
		case -2:
			$ret = STR_ACTION_SIGNIN_2;
			$status = 1;
			break;
		case -3:
			$ret = STR_ACTION_SIGNIN_3;
			$status = 2;
			break;
		case -4:
			$ret = STR_ACTION_SIGNIN_4;
			$status = 2;
			break;
		case -5:
			$ret = STR_ACTION_SIGNIN_5;
			$status = 2;
			break;
		case -6:
			$ret = STR_ACTION_SIGNIN_6;
			$status = 3;
			break;
		case -7:
			$ret = STR_ACTION_SIGNIN_7;
			$status = 3;
			break;
		case -8:
			throw new Exception(STR_ACTION_SIGNIN_8);
			break;
		case -9:
			$ret = STR_ACTION_SIGNIN_9;
			$status = 4;
			break;
		default:
			break;
	}
	
	if($status){
		if(!AJAX){
			$ALERTS->add_alert($ret, 'danger');
		}
		return array('status' => $status, 'message' => $ret);
	}
	
	//TODO переделать
	send_verified_mail($new_user_id);
	//$USER->user_logout();
	$USER->load_user($new_user_id);
	$ret = $USER->create_token();
	if($ret['status']){
		if(!AJAX){
			$ALERTS->add_alert(STR_UNDEFINED_ERROR, 'danger');
		}
		return array('status' => 4, 'message' => STR_UNDEFINED_ERROR);
	}else{
		setcookie('sid', $ret['token'], 0, '/', null, USE_SSL, 1);
	}
	
	//TODO привествие
	return array('status' => 0, 'message' => STR_ACTION_SIGNIN_10);
}

/**
 * Проверяет незанятость логина
 * @param $_POST = ['login' => string]
 * @return bool true - логин свободен
 */
function action_check_login(){
	if(!isset($_POST['login']))
		return false;
	return rad_user::check_login($_POST['login']);
}

/**
 * запрос на восстановление пароля, отправляет сообщение с ссылкой
 * @param $_POST = ['login' => string, 'email' => string]
 * @return false|array [status => int, 'message' => string]
 */
function action_send_pass_recovery(){
	if(!isset($_POST['login'], $_POST['email']))
		return false;
	global $DB, $ALERTS;
	$login = login_clear($_POST['login']);
	if(strcmp($login, $_POST['login'])){
		if(!AJAX){
			$ALERTS->add_alert(STR_ACTION_SEND_PASS_RECOVERY_1, 'warning');
		}
		return array('status' => 1, 'message' => STR_ACTION_SEND_PASS_RECOVERY_1);
	}
	$email = trim($_POST['email']);
	$user = $DB->getRow('SELECT `id`, `email` FROM `our_u_users` WHERE `login` = ?s', $login);
	
	if(!$user){
		if(!AJAX){
			$ALERTS->add_alert(STR_ACTION_SEND_PASS_RECOVERY_1, 'warning');
		}
		return array('status' => 1, 'message' => STR_ACTION_SEND_PASS_RECOVERY_1);
	}

	if(strcmp($user['email'], $email)){
		if(!AJAX){
			$ALERTS->add_alert(STR_ACTION_SEND_PASS_RECOVERY_2, 'warning');
		}
		return array('status' => 2, 'message' => STR_ACTION_SEND_PASS_RECOVERY_2);
	}
	
	$ret = send_pass_recovery_mail((int)$user['id']);
	if($ret == -4){
		if(!AJAX){
			$ALERTS->add_alert(STR_ACTION_SEND_PASS_RECOVERY_3, 'warning');
		}
		return array('status' => 3, 'message' => STR_ACTION_SEND_PASS_RECOVERY_3);
	}else if($ret == 0){
		if(!AJAX){
			$ALERTS->add_alert(STR_ACTION_SEND_PASS_RECOVERY_4, 'info');
		}
		return array('status' => 0, 'message' => STR_ACTION_SEND_PASS_RECOVERY_4);
	}else{
		if(!AJAX){
			$ALERTS->add_alert(STR_ACTION_SEND_PASS_RECOVERY_5, 'warning');
		}
		return array('status' => 3, 'message' => STR_ACTION_SEND_PASS_RECOVERY_5);
	}
}

/**
 * событие выхода пользователя
 */
function action_exit(){
	global $USER;
	$USER->user_logout();
	//redirect('/login');
}

/**
 * событие выхода пользователя
 */
function action_logout(){
	action_exit();
}
?>