<?php

/**
 * @param $body
 * @param $subject
 * @param $to
 * @return bool
 */
function send_mail($body, $subject, $to){
	global $LOG, $DATA;
	$MAIL = new PHPMailer\PHPMailer\PHPMailer(true);

	$MAIL->isSMTP();
	if(DEBUG_SMTP){
		$MAIL->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_CONNECTION;
		$MAIL->Debugoutput = array($LOG, 'log_write');
	}else{
		$MAIL->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
	}
	
	$MAIL->Host = SMTP_SERVER;
	$MAIL->SMTPAuth = true;
	$MAIL->Username = SMTP_USER;
	$MAIL->Password = SMTP_PASS;
	$MAIL->SMTPSecure = 'ssl';
	$MAIL->Port = SMTP_PORT;

	try{
		$MAIL->CharSet = 'utf-8';
		$MAIL->setFrom($DATA->get('email_address_from'), $DATA->get('email_name_from'));
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
		return false;
	}
}

/**
 * Обнуляет уровень пользователя, и отправляет письмо с верификацией почты
 * @param $user_id
 *
 * @return bool
 */
/*
function send_verified_mail($user_id){
	$new_user = new rad_user($user_id);
	if($new_user->get_user_level() < $new_user::USER)
		return false;
	$new_user->load_options();
	if($new_user->get_user_level() > $new_user::VERIFIED){
		$new_user->set_option('old_user_level', $new_user->get_user_level());
	}
	$new_user->set_user_level($new_user::USER);
	$token = hash('sha256', $user_id.$new_user->get_login().$new_user->get_email().time().SALT);
	$new_user->set_option('mail_verified_token', $token);
	$new_user->update_options('mail_verified_token', 'old_user_level');

	$verify_link = get_protocol().'://'.$_SERVER['HTTP_HOST'].'/activation/'.$user_id.'/'.$token.'/';
	$mail_body = file_get_contents(MAIN_DIR.'templates/verify-mail.html');
	$mail_body = rad_template_old($mail_body, array('verify_link' => $verify_link, 'user_login' => $new_user->get_login(), 'user_email' => $new_user->get_email()));

	return send_mail($mail_body, 'Подтверждение аккаунта', $new_user->get_email());
}

function send_pass_recovery_mail($user_id){
	$user = new rad_user($user_id);
	if($user->get_user_level() < $user::USER)
		return false;
	if(($user->get_user_level() >= $user::NEDOADMIN && !ADMIN_RECOVERY_PASS) || $user->get_user_level() < $user::VERIFIED){
		return false;
	}
	$token = hash('sha256', $user->get_login().$user->get_email().SALT.time().$user_id);
	$user->set_option('pass_recovery', array('date' => time(), 'token' => $token));
	$user->update_options('pass_recovery');

	$verify_link = get_protocol().'://'.$_SERVER['HTTP_HOST'].'/recovery-password/'.$user_id.'/'.$token.'/';
	$mail_body = file_get_contents(MAIN_DIR.'templates/pass-recovery-mail.html');
	$mail_body = rad_template_old($mail_body, array('pass_recovery_link' => $verify_link, 'user_login' => $user->get_login(), 'user_email' => $user->get_email()));

	return send_mail($mail_body, 'Восстановление пароля', $user->get_email());
}
*/
?>