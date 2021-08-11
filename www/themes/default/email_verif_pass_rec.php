<?php

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
			<h1><?= STR_ACTION_PASS_RECOVERY_1; ?></h1>
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
			<h1><?= STR_ACTION_PASS_RECOVERY_2; ?></h1>
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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//восстановление пароля
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * проверяет время работы токена, показывает страницу смены пароля
 * выводит тело страницы 'pass_recovery'
 */
function view_pass_recovery_page(){
	global $OPTIONS;
	if(!isset($OPTIONS['pass_recovery_data'])){
		view_activation_page_failed();
		return;
	}
	$token_data = $OPTIONS['pass_recovery_data'];
	unset($OPTIONS['pass_recovery_data']);
	$curr_user = new rad_user(absint($token_data['data']['user_id']));
	if(!$curr_user->get_id()){
		view_pass_recovery_page_failed();
		return;
	}

	$time_end = DateTime::createFromFormat(DB_DATE_FORMAT, $token_data['data']['time_end']);
	if($time_end < (new DateTime())){
		$curr_user->set_option('pass_recovery_token', null);
		$curr_user->update_options('pass_recovery_token');
		view_pass_recovery_page_old_token();
		return;
	}
	global $URL;
	
	?>
	<div class="container-fluid">
		<div class="child-center">
			<div class="login-form">
				<div class="form-toggle" style="height:56px">
					<a href="/">На главную</a><br>
					<h5 class="text-center">Восстановление пароля</h5>
				</div>
				<form class="in-form" method="post" action="<?= $URL->get_current_url(); ?>" data-not-ajax="true">
					<input type="hidden" name="action" value="pass_recovery">
					<input class="form-control" autocomplete="off" id="password" style="width:calc(100% - 50px);float: left;" type="password" placeholder="Новый пароль" name="password">
					<div class="password-view not-select" title="Показать пароль" data-view-pattern="(ಠ_ಠ)" data-target="#password">
						(–_–)
					</div>
					<div class="invalid-feedback">мин. 6 символов (a-z A-Z 0-9 ! @ $ % & ? *)</div><br>
					<input class="btn btn-primary" style="margin-top:12px;width:100%" type="submit" value="Сменить">
				</form>
			</div>
		</div>
		<script>
			jQuery(document).ready(function($){
				$('#password').on('input', function(e){
					let $this = $(this),
						value = $this.val();
					if(/^[a-zA-Z0-9!@\$%&\?\*]{6,}$/.test(value)){
						$this.removeClass('is-invalid').addClass('is-valid');
						$this.nextAll('.invalid-feedback').first().hide();
						return;
					}
					$this.removeClass('is-valid').addClass('is-invalid');
					$this.nextAll('.invalid-feedback').first().show();
				});
				
				$('form').on('submit', function(e){
					let $this = $(this);
					if($this.find('.is-valid').length != 1){
						e.preventDefault();
					}
				});
			});
		</script>
	</div>
	<?php

}

/**
 * вывод тела страницы 'pass_recovery' о ошибке
 */
function view_pass_recovery_page_failed(){
	?>
	<div class="container-fluid">
		<div class="col-md-8 col-md-offset-2">
			<a href="/">На главную</a>
		</div>
		<div class="child-center">
			<h1><?= STR_ACTION_PASS_RECOVERY_1; ?></h1>
			<p>Повторите <a href="/login/password-recovery">запрос</a></p>
		</div>
	</div>
	<?php
}

/**
 * вывод тела страницы 'pass_recovery' о устареваниие токена
 */
function view_pass_recovery_page_old_token(){
	?>
	<div class="container-fluid">
		<div class="col-md-8 col-md-offset-2">
			<a href="/">На главную</a>
		</div>
		<div class="child-center">
			<h1><?= STR_ACTION_PASS_RECOVERY_2; ?></h1>
			<p>Повторите <a href="/login/password-recovery">запрос</a></p>
		</div>
	</div>
	<?php
}


/**
 * функция проверки на доступ к странице 'pass_recovery'
 * проверяет параметр token(1)
 * если он не валиден то false
 * @return bool
 */
function test_view_pass_recovery(){
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
	$real_token = $curr_user->get_option('pass_recovery_token');
	if(!$real_token)
		return false;
	if(strcmp($real_token, $token))
		return false;
	//не очень нравится такой ход
	//но поновой парсить токен лень 
	$OPTIONS['pass_recovery_data'] = $token_data;

	return true;
}
?>