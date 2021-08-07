<?php
if(!defined('MAIN_DIR'))
	die();


define('STR_ACTION_LOGIN_1', 'Пара логин-пароль не верна');
define('STR_ACTION_LOGIN_2', 'Достигнут предел запоминания, данный вход не запомнен');

define('STR_ACTION_SIGNIN_1', 'Короткий пароль (мин. 6 символов)');
define('STR_ACTION_SIGNIN_2', 'Пароль имеет недопустимые символы, допустимы a-z A-Z 0-9 ! @ $ % & ? *');
define('STR_ACTION_SIGNIN_3', 'Короткий логин');
define('STR_ACTION_SIGNIN_4', 'Логин имеет недопустимые символы, допустимы a-z A-Z 0-9 _ -');
define('STR_ACTION_SIGNIN_5', 'Логин неуникален');
define('STR_ACTION_SIGNIN_6', 'Email пуст');
define('STR_ACTION_SIGNIN_7', 'Email неверен');
define('STR_ACTION_SIGNIN_8', 'Ошибка уровня пользователя');
define('STR_ACTION_SIGNIN_9', 'Не удалось зарегистрироваться. Попробуйте позже');
define('STR_ACTION_SIGNIN_10', 'Вы успешно зарегистрировались');

define('STR_ACTION_SEND_PASS_RECOVERY_1', 'Логин не верен');
define('STR_ACTION_SEND_PASS_RECOVERY_2', 'Почта не верна');
define('STR_ACTION_SEND_PASS_RECOVERY_3', 'Предыдущий запрос на восстановление пароля еще не истек');
define('STR_ACTION_SEND_PASS_RECOVERY_4', 'На почту было выслано письмо для восстановления пароля');
define('STR_ACTION_SEND_PASS_RECOVERY_5', 'Не удалось отправить письмо');

?>