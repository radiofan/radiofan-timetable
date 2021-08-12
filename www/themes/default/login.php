<?php
/**
 * функция вывода тела страницы 'login'
 */
function view_login_page(){
	global $URL;
	$type = $URL->get_parameter('type');
	switch($type){
		case 'password-recovery':
		case 'sign-in':
			break;
		default:
			$type = 'login';
			break;
	}
?>
	<div class="container-fluid">
		<div class="child-center">
			<div class="login-form">
				<div class="btn-group btn-group-toggle form-toggle" data-toggle="buttons">
					<label class="btn btn-primary btn-lg <?= ($type != 'sign-in') ? 'active' : '';?>">
						<input type="radio" autocomplete="off" name="in-form-type" value="login" <?= ($type != 'sign-in') ? 'checked="checked"' : '';?>> Вход
					</label>
					<label class="btn btn-primary btn-lg <?= ($type == 'sign-in') ? 'active' : '';?>">
						<input type="radio" autocomplete="off" name="in-form-type" value="signin" <?= ($type == 'sign-in') ? 'checked="checked"' : '';?>> Регистрация
					</label>
				</div>
				<form class="in-form in-form-login <?= ($type == 'sign-in') ? 'display-none' : '';?>" method="post" action="/login" data-not-ajax="true">
					<input type="hidden" name="action" value="login">
					<input class="form-control" type="text" placeholder="Логин" name="login"><br>
					<input class="form-control" autocomplete="off" id="login_pass" style="width:calc(100% - 50px);float: left;" type="password" placeholder="Пароль" name="password">
					<div class="password-view not-select" title="Показать пароль" data-view-pattern="(ಠ_ಠ)" data-target="#login_pass">
						(–_–)
					</div><br>
					<a href="/login/password-recovery" id="show-pass-recovery">Восстановить пароль</a><br>
					<span style="line-height:30px"><input class="" type="checkbox" value="1" name="remember"> Запомнить меня</span><br>
					<input class="btn btn-primary" style="margin-top:12px;width:100%" type="submit" value="Войти">
				</form>
				<form class="in-form in-form-signin <?= ($type != 'sign-in') ? 'display-none' : '';?>" method="post" action="/login/sign-in" data-not-ajax="true">
					<input type="hidden" name="action" value="signin">
					<input class="form-control" type="text" placeholder="Логин" name="login" autocomplete="off">
					<div class="invalid-feedback">Логин занят</div><br>
					<input class="form-control" type="email" placeholder="Почта" name="email">
					<div class="invalid-feedback">Заполните поле</div><br>
					<input class="form-control" autocomplete="off" id="signin_pass" style="width:calc(100% - 50px);float: left;" type="password" placeholder="Пароль" name="password">
					<div class="password-view not-select" title="Показать пароль" data-view-pattern="(ಠ_ಠ)" data-target="#signin_pass">
						(–_–)
					</div>
					<div class="invalid-feedback">мин. 6 символов (a-z A-Z 0-9 ! @ $ % & ? *)</div><br>
					<input class="btn btn-primary" style="margin-top:12px;width:100%" type="submit" value="Зарегистрироваться">
				</form>
				<div class="in-form-pass-rec <?= ($type != 'password-recovery') ? 'display-none' : '';?>">
					<div class="form-toggle" style="height:56px">
						<a href="/login" id="hide-pass-recovery">&lt;- вернуться</a><br>
						<h5 class="text-center">Восстановление пароля</h5>
					</div>
					<form class="in-form" method="post" action="/">
						<input type="hidden" name="action" value="send_pass_recovery">
						<input class="form-control" type="text" placeholder="Логин" name="login"><br>
						<input class="form-control" type="email" placeholder="Почта" name="email"><br>
						<input class="btn btn-primary" style="margin-top:12px;width:100%" type="submit" value="Отправить">
					</form>
				</div>
			</div>
		</div>
	</div>
<?php
}

/**
 * функция проверки на доступ к странице 'login'
 * если юзер зареган и не умеет смотреть debug, то редиректим его на главную
 * если параметр type(1) не равен 'password-recovery', 'sign-in', '', то false
 * @return bool
 */
function test_view_login(){
	global $USER, $URL;
	//если пользователь зарегистрирован, но пытается зайти на сттраницу регистрации (не имея возможности дебага)
	//отправим его на главную
	if($USER->get_user_level() > 0 && !$USER->roles->can_user('view_debug_info')){
		redirect('/');
		return false;
	}

	//проверка параметра
	$type = $URL->get_parameter('type');
	switch($type){
		case 'password-recovery':
		case 'sign-in':
		case '':
		case null:
			break;
		default:
			return false;
			break;
	}
	return true;
}

/**
 * Дорабатывает title страницы добавляя к нему
 * ' | Восстановление пароля' или ' | Регистрация'
 * @param $data
 * @return array
 */
function footer_header_data_login($data){
	global $URL;
	$type = $URL->get_parameter('type');
	switch($type){
		case 'password-recovery':
			$data['title'] .= ' | Восстановление пароля';
			break;
		case 'sign-in':
			$data['title'] .= ' | Регистрация';
			break;
		default:
			break;
	}
	return $data;
}
?>