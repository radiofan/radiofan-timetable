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
	global $OPTIONS, $USER, $ALERTS;
	if(is_login())
		return true;
	if(!isset($_POST['login'], $_POST['password']))
		return false;
	if(!$USER->load_by_loginpass($_POST['login'], $_POST['password']))
		return false;
	if(!is_session_exists())
		my_start_session();
	$_SESSION['user_id'] = $USER->get_id();
	//небольшая защита от кражи сессии
	$_SESSION['secret_key'] = sha1($OPTIONS['user_agent']);
	if(isset($_POST['remember']) && $_POST['remember']){
		//создадим токен для пользователя
		$ret = $USER->create_token();
		if(isset($ret['error'])){
			$a = 0;
			$ALERTS->add_alert('Достигнут предел запоминания, данный вход не запомнен.', 'warning');
		}else{
			setcookie('token', $ret['token'], $ret['date_end_token']->getTimestamp(), '/', null, USE_SSL, 1);
		}
	}
	return true;
}

//событие выхода пользователя
function action_exit(){
	global $USER;
	$USER->user_logout();
	redirect('/login');
}


?>