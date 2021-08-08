<?php

function view_user_settings_page(){
	
}

/**
 * проверяет время работы токена, подтверждает почту
 * выводит тело страницы 'activation'
 * @see view_activation_page_success, view_activation_page_failed, view_activation_page_old_token
 */
function view_activation_page(){
	global $OPTIONS;
	if(!isset($OPTIONS['email_activation_data'])){
		view_activation_page_failed();
		return;
	}
	$token_data = $OPTIONS['email_activation_data'];
	unset($OPTIONS['email_activation_data']);
	$curr_user = new rad_user(absint($token_data['data']['user_id']));
	if(!$curr_user->get_id()){
		view_activation_page_failed();
		return;
	}
	
	$time_end = DateTime::createFromFormat(DB_DATE_FORMAT, $token_data['data']['time_end']);
	if($time_end < (new DateTime())){
		$curr_user->set_option('mail_verified_token', null);
		$curr_user->update_options('mail_verified_token');
		view_activation_page_old_token();
		return;
	}
	
	$curr_user->mail_verify();
	view_activation_page_success();
	
}

/**
 * вывод тела страницы 'activation' о успехе подтверждаения почты
 */
function view_activation_page_success(){
?>
	<div class="container-fluid">
		<div class="col-md-8 col-md-offset-2">
			<a href="/">На главную</a>
		</div>
		<div class="child-center">
			<h1>Почтовый ящик подтвержден</h1>
		</div>
	</div>
<?php
}

/**
 * вывод тела страницы 'activation' о ошибке подтверждаения почты
 */
function view_activation_page_failed(){
?>
	<div class="container-fluid">
		<div class="col-md-8 col-md-offset-2">
			<a href="/">На главную</a>
		</div>
		<div class="child-center">
			<h1>Произошла неопознанная ошибка</h1>
			<p>Повторите запрос в <a href="/settings">настройках</a></p>
		</div>
	</div>
<?php
}

/**
 * вывод тела страницы 'activation' о устареваниие токена подтверждаения почты
 */
function view_activation_page_old_token(){
?>
	<div class="container-fluid">
		<div class="col-md-8 col-md-offset-2">
			<a href="/">На главную</a>
		</div>
		<div class="child-center">
			<h1>Ссылка проверки устарела</h1>
			<p>Повторите запрос в <a href="/settings">настройках</a></p>
		</div>
	</div>
<?php
}

/**
 * функция проверки на доступ к странице 'activation'
 * проверяет параметр token(1)
 * если он не валиден то false
 * @return bool
 */
function test_view_activation(){
	global $URL, $OPTIONS;
	$token = $URL->get_parameter('token');
	if(!$token)
		return false;
	$token_data = rad_user::decode_cookie_token($token);
	if(!$token_data || !is_array($token_data['data']))
		return false;
	if(!isset($token_data['data']['user_id']))
		return false;
	
	$curr_user = new rad_user(absint($token_data['data']['user_id']));
	if(!$curr_user->get_id())
		return false;
	$real_token = $curr_user->get_option('mail_verified_token');
	if(!$real_token)
		return false;
	if(strcmp($real_token, $token))
		return false;
	//не очень нравится такой ход
	//но поновой парсить токен лень 
	$OPTIONS['email_activation_data'] = $token_data;
	
	return true;
}
?>