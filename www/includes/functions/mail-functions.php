<?php

/**
 * @param $body
 * @param $subject
 * @param $to
 * @return bool
 */
function send_mail($body, $subject, $to){
	global $LOG;
	$MAIL = new PHPMailer\PHPMailer\PHPMailer(true);

	$MAIL->isSMTP();
	if(DEBUG_SMTP){
		//TODO перенаправление не работает
		$MAIL->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_CONNECTION;
		$MAIL->Debugoutput = array($LOG, 'log_write');
	}else{
		$MAIL->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
	}
	
	/*
	$MAIL->Host = SMTP_SERVER;
	$MAIL->SMTPAuth = true;
	$MAIL->Username = SMTP_USER;
	$MAIL->Password = SMTP_PASS;
	$MAIL->SMTPSecure = 'ssl';
	$MAIL->Port = SMTP_PORT;
	*/
	$MAIL->Host = SMTP_SERVER;
	$MAIL->Username = SMTP_USER;
	$MAIL->Password = SMTP_PASS;
	$MAIL->SMTPSecure = '';
	$MAIL->Port = SMTP_PORT;

	try{
		$MAIL->CharSet = 'utf-8';
		$MAIL->setFrom(SMTP_ADDRES_FROM, SMTP_NAME_FROM);
		$MAIL->addAddress($to);
		$MAIL->Subject = $subject;
		$MAIL->isHTML(1);
		$MAIL->Body = $body;


		if($MAIL->send()){
			return true;
		}else{
			trigger_error('Mailer Error: '.$MAIL->ErrorInfo, E_USER_WARNING);
			return false;
		}
	}catch(Exception $e){
		trigger_error('[EXCEPTION] '.$e->getMessage().';, line: '.($e->getLine()).PHP_EOL, E_USER_WARNING);
		return false;
	}
}

/**
 * Обнуляет уровень пользователя, и отправляет письмо с верификацией почты
 * также записывает его предыдущий уровень в его параметры, если он был больше VERIFIED
 * @param $user_id
 * 0 : Успешно
 * -1: Пользователь не найден;
 * -2: Сообщение не отправлено;
 * @return int
 */
function send_verified_mail($user_id){
	$new_user = new rad_user($user_id);
	
	if($new_user->get_user_level() < $new_user::USER)
		return -1;
	if($new_user->get_user_level() > $new_user::VERIFIED){
		$new_user->set_option('old_user_level', $new_user->get_user_level());
		$new_user->update_options('old_user_level');
	}
	$new_user->set_user_level($new_user::USER);
	$now_time = new DateTime();
	$token_hash = hash('sha256', $user_id.$new_user->get_login().$new_user->get_email().$now_time->getTimestamp().mt_rand());
	$end_time = $now_time->add(new DateInterval('P'.MAIL_VERIFY_TOKEN_LIVE_DAYS.'D')); 
	$token = $new_user::encode_cookie_token(array('user_id' => $new_user->get_id(), 'time_end' => $end_time->format(DB_DATE_FORMAT)), $token_hash);
	$new_user->set_option('mail_verified_token', $token);
	$new_user->update_options('mail_verified_token');

	$mail_body = get_verified_mail_body(array(
		'verify_link' => 'http'.(USE_SSL ? 's' : '').'://'.$_SERVER['HTTP_HOST'].'/activation/',
		'token' => $token,
		'time_end' => $end_time,
		'login' => $new_user->get_login()
	));

	return send_mail($mail_body, 'Подтверждение аккаунта', $new_user->get_email()) ? 0 : -2;
}

/**
 * Отправляет пользователю ссылку на страницу восстановления пароля
 * @param $user_id
 * @return int
 * 0 : Успешно
 * -1: Пользователь не найден;
 * -2: Запрос на смену пароля админа с выключенной настройкой ADMIN_RECOVERY_PASS;
 * -3: Почта пользователя не проверена;
 * -4: Предыдущий запрос еще не истек;
 * -5: Сообщение не отправлено;
 * @see ADMIN_RECOVERY_PASS
 */
function send_pass_recovery_mail($user_id){
	$user = new rad_user($user_id);
	if($user->get_user_level() < $user::USER)
		return -1;
	if($user->get_user_level() >= $user::NEDOADMIN && !ADMIN_RECOVERY_PASS){
		return -2;
	}
	if($user->get_user_level() < $user::VERIFIED){
		return -3;
	}

	$now_time = new DateTime();
	
	$old_token = $user->get_option('pass_recovery_token');
	if($old_token){
		$data = $user::decode_cookie_token($old_token);
		$end_time = $data['data']['time_end'];
		$end_time = DateTime::createFromFormat(DB_DATE_FORMAT, $end_time);
		if($now_time < $end_time){
			return -4;
		}
	}
	
	
	$token_hash = hash('sha256', $user_id.$user->get_login().$user->get_email().$now_time->getTimestamp().mt_rand());
	$end_time = $now_time->add(new DateInterval('PT'.MAIL_PASS_RECOVERY_LIVE_HORS.'H'));
	$token = $user::encode_cookie_token(array('user_id' => $user->get_id(), 'time_end' => $end_time->format(DB_DATE_FORMAT)), $token_hash);
	$user->set_option('pass_recovery_token', $token);
	$user->update_options('pass_recovery_token');

	$mail_body = get_pass_recovery_body(array(
		'verify_link' => 'http'.(USE_SSL ? 's' : '').'://'.$_SERVER['HTTP_HOST'].'/recovery-password/',
		'token' => $token,
		'time_end' => $end_time,
		'login' => $user->get_login()
	));
	
	return send_mail($mail_body, 'Восстановление пароля', $user->get_email()) ? 0 : -5;
}



/**
 * возвращает тело письма для верификации почты
 * @param array $data - ['verify_link' =>string, 'token' =>string, 'time_end' =>DateTime, 'login' =>string]
 * @return string - html тела письма
 */
function get_verified_mail_body($data){
	//TODO доработать шаблон
	$html = ' 
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
</head>
<body>
	<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0;font-family: \'Courier New\', monospace;" width="100%">
		<tr>
			<td style="text-align:center">Добро пожаловать в сервис <b>RADIOFAN timetable</b></td>
		</tr>
		<tr>
			<td style="text-align:left">Ваш логин: <span style="background-color:#e6e6e6;font-weight:bold">&nbsp;'.$data['login'].'&nbsp;</span></td>
		</tr>
		<tr>
			<td style="text-align:left">
				Подтвердить почту <a href="'.$data['verify_link'].$data['token'].'">'.$data['verify_link'].'</a>
				<br>
				Ссылка доступна до <b>'.$data['time_end']->format('d.m.Y H:i').' ('.get_msk_time_offset($data['time_end']).')</b>
			</td>
		</tr>
		<tr>
			<td style="text-align:left">'.get_mail_footer().'</td>
		</tr>
	</table>
</body>
</html>
';

	return $html;
}


/**
 * возвращает тело письма для восстановления пароля
 * @param array $data - ['verify_link' =>string, 'token' =>string, 'time_end' =>DateTime, 'login' =>string]
 * @return string - html тела письма
 */
function get_pass_recovery_body($data){
	$html = ' 
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
</head>
<body>
	<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0;font-family: \'Courier New\', monospace;" width="100%">
		<tr>
			<td style="text-align:center"><b>RADIOFAN timetable</b></td>
		</tr>
		<tr>
			<td style="text-align:left">
				Был произведен запрос на смену пароля для пользователя <span style="background-color:#e6e6e6;font-weight:bold">&nbsp;'.$data['login'].'&nbsp;</span>
				<br>
				Если это были не вы, проигнорируйте сообщение
			</td>
		</tr>
		<tr>
			<td style="text-align:left">
				Ссылка для смены пароля <a href="'.$data['verify_link'].$data['token'].'">'.$data['verify_link'].'</a>
				<br>
				Ссылка доступна до <b>'.$data['time_end']->format('d.m.Y H:i').' ('.get_msk_time_offset($data['time_end']).')</b>
			</td>
		</tr>
		<tr>
			<td style="text-align:left">'.get_mail_footer().'</td>
		</tr>
	</table>
</body>
</html>
';

	return $html;
}


function get_mail_footer(){
	return '
<span style="display:block;overflow:hidden">--------------------------------------------------------------------------------</span>
<xmp>
       .           ___    _   ___ ___ ___  ___ _   _  _ 
     --┼--        | _ \\  /_\\ |   \\_ _/ _ \\| __/_\\ | \\| |
   / ══╧══ \\      |   / / _ \\| |) | | (_) | _/ _ \\| .` |
   \\ ↗   \\ /      |_|_\\/_/ \\_\\___/___\\___/|_/_/ \\_\\_|\\_| timetable
  ‾‾ ----- ‾‾
</xmp>';
}

?>