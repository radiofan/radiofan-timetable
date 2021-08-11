<?php
/**
 * событие восстановления пароля
 * токен восстановления должен находится в URL-параметре token(1)
 * путь реферера должен совпадать с $URL->get_current_url()
 * хост реферера должен совпадать с доменом
 * если токен валиден, но имеются ошибки, или все прошло успешно, происходит редирект на '/login'
 * //TODO сброс активных сессий
 * @param $_POST = ['password' => string]
 * @return false|array [status => int, 'message' => string]
 */
function action_pass_recovery(){
	if(!isset($_POST['password']))
		return false;
	global $ALERTS, $URL, $OPTIONS;
	$token = $URL->get_parameter('token');
	if(!$token)
		return false;
	if(strcmp($OPTIONS['referer_data']['path'], $URL->get_current_url()))
		return false;
	if(strcmp($OPTIONS['referer_data']['host'], $OPTIONS['domen']))
		return false;
	
	$token_data = rad_user::decode_cookie_token($token);
	if(!$token_data || !is_array($token_data['data']))
		return false;
	if(!isset($token_data['data']['user_id']))
		return false;

	$curr_user = new rad_user(absint($token_data['data']['user_id']));
	if(!$curr_user->get_id())
		return false;
	$real_token = $curr_user->get_option('pass_recovery_token');
	if(!$real_token)
		return false;
	if(strcmp($real_token, $token))
		return false;

	if(($curr_user->get_user_level() >= $curr_user::NEDOADMIN && !ADMIN_RECOVERY_PASS) || $curr_user->get_user_level() < $curr_user::VERIFIED){
		return array('status' => 1, 'message' => STR_ACTION_PASS_RECOVERY_1);
	}
	
	$curr_user->set_option('pass_recovery_token', null);
	$curr_user->update_options('pass_recovery_token');

	$time_end = DateTime::createFromFormat(DB_DATE_FORMAT, $token_data['data']['time_end']);
	if($time_end < (new DateTime())){
		if(!AJAX){
			$ALERTS->add_alert(STR_ACTION_SEND_PASS_RECOVERY_2, 'warning');
		}
		return array('status' => 2, 'message' => STR_ACTION_SEND_PASS_RECOVERY_2);
	}
	
	$ret = $curr_user->change_password((string)$_POST['password']);
	switch($ret){
		case 0:
			if(!AJAX){
				$ALERTS->add_alert(STR_ACTION_PASS_RECOVERY_3, 'info');
			}else{
				return array('status' => 0, 'message' => STR_ACTION_PASS_RECOVERY_3);
			}
			break;
		case -1:
			if(!AJAX){
				$ALERTS->add_alert(STR_ACTION_SIGNIN_1, 'info');
			}else{
				return array('status' => 3, 'message' => STR_ACTION_SIGNIN_1);
			}
			break;
		case -2:
			if(!AJAX){
				$ALERTS->add_alert(STR_ACTION_SIGNIN_2, 'info');
			}else{
				return array('status' => 3, 'message' => STR_ACTION_SIGNIN_2);
			}
			break;
		case -3:
			if(!AJAX){
				$ALERTS->add_alert(STR_ACTION_PASS_RECOVERY_1, 'info');
			}else{
				return array('status' => 3, 'message' => STR_ACTION_PASS_RECOVERY_1);
			}
			break;
	}
	redirect('/login');
	return true;
}
?>