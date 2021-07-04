<?php
mb_internal_encoding("UTF-8");
header("Content-Type: text/html; charset=UTF-8");
//setlocale(LC_COLLATE | LC_CTYPE | LC_TIME, 'ru_RU.UTF-8', 'ru_RU', 'ru', 'russian');
define('MAIN_DIR', __DIR__.'/');
define('AJAX', false);
require_once MAIN_DIR.'defines.php';

require_once MAIN_DIR.'functions.php';
require_once MAIN_DIR.'includes/parse-functions.php';
require_once MAIN_DIR.'includes/timetable-functions.php';
require_once MAIN_DIR.'includes/parser-class.php';

require_once MAIN_DIR.'includes/log-class.php';
if(defined('USE_LOG') && USE_LOG)
	$LOG = new rad_log(MAIN_DIR.'files/debug.log');

require_once MAIN_DIR.'includes/db-class.php';
$DB = new rad_db(array('host' => MAIN_DBHOST, 'user' => MAIN_DBUSER, 'pass' => MAIN_DBPASS, 'db' => MAIN_DBNAME));

require_once MAIN_DIR.'includes/data-class.php';
$DATA = new rad_data();
is_session_exists();

require_once MAIN_DIR.'includes/alerts-class.php';
$ALERTS = new rad_alerts();

require_once MAIN_DIR.'includes/user-class.php';
$USER = new rad_user();

require_once MAIN_DIR.'actions.php';
do_actions();


?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title></title>
	<link href="/favicon.ico" rel="shortcut icon" type="image/x-icon">
</head>
<body>
<nav class="navbar navbar-inverse navbar-fixed-top">
	<div class="container">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
		</div>
		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			<div class="navbar-right navbar-text">

			</div>
			<?php
			if(is_login()){
				echo '<div class="navbar-right navbar-text"><a href="/test.php?action=exit" class="navbar-link">Выход</a></div>';
			}
			?>
		</div>
	</div>
</nav>
	
<div class="container-fluid">
	<div class="child-center">
		<form class="login" method="post" action="/test.php" data-not-ajax="true">
			<input type="hidden" name="action" value="login">
			<input class="form-control" type="text" placeholder="Логин" name="login" id="login"><br>
			<input class="form-control" type="password" placeholder="Пароль" name="password" id="password"><br>
			<input class="" type="checkbox" value="1" name="remember" id="remember"> Запомнить меня<br>
			<a href="#" data-toggle="modal" data-target="#main-modal" data-modal="registration">Зарегистрироваться</a>
			<br>
			<a href="#" data-toggle="modal" data-target="#main-modal" data-modal="pass_recovery">Восстановить пароль</a>
			<br><br>
			<input class="btn btn-primary" type="submit" value="Войти">
		</form>
	</div>
</div>
</body>
</html>