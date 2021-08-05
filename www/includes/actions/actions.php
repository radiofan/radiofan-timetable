<?php
if(!defined('MAIN_DIR'))
	die();

//подключаем файлы с функциями действий
$files = file_list(MAIN_DIR.'includes/actions/', '.php', '^.*?-actions');
for($i=0; $i<sizeof($files); $i++){
	require_once MAIN_DIR.'includes/actions/'.$files[$i];
}

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

/**
 * login-actions.php
 * @see action_login
 * @see action_signin
 * @see action_exit
 * @see action_logout
 */

?>