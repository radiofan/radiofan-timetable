<?php

/**
 * событие входа пользователя
 */
function action_login(){
	if(!isset($_POST['login'], $_POST['password']))
		return false;
	
	global $OPTIONS, $USER, $ALERTS;
	$return_data = true;
	
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
	if(!is_session_exists())
		my_start_session();
	$_SESSION['user_id'] = $USER->get_id();
	//небольшая защита от кражи сессии
	$_SESSION['secret_key'] = sha1($OPTIONS['user_agent']);
	if(isset($_POST['remember']) && $_POST['remember']){
		//создадим токен для пользователя
		$ret = $USER->create_token();
		if(isset($ret['error'])){
			if(AJAX){
				$return_data = array('status' => 0, 'message' => STR_ACTION_LOGIN_2);
			}else{
				$ALERTS->add_alert(STR_ACTION_LOGIN_2, 'warning');
				$return_data = true;
			}
		}else{
			setcookie('token', $ret['token'], $ret['date_end_token']->getTimestamp(), '/', null, USE_SSL, 1);
		}
	}
	return $return_data;
}

/**
 * событие регистрации пользователя
 */
function action_signin(){
	if(!isset($_POST['login'], $_POST['password'], $_POST['email']))
		return false;
	global $USER, $ALERTS, $OPTIONS;
	if($USER->get_id())
		return false;
	
	$new_user_id = rad_user::create_new_user($_POST['login'], $_POST['password'], $_POST['email'], rad_user::USER);
	
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
	
	send_verified_mail($new_user_id);//TODO
	if(!is_session_exists())
		my_start_session();
	$_SESSION['user_id'] = $new_user_id;
	setcookie('token', '', time()-SECONDS_PER_DAY, '/', null, USE_SSL, 1);
	//небольшая защита от кражи сессии
	$_SESSION['secret_key'] = sha1($OPTIONS['user_agent']);
	
	//TODO привествие
	return array('status' => 0, 'message' => STR_ACTION_SIGNIN_10);
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