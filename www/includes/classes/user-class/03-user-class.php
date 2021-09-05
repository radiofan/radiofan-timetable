<?php
if(!defined('MAIN_DIR'))
	die();

require_once MAIN_DIR.'includes/classes/user-class/02-user-auth.php';

/**
 * Класс предоставляет методы работы с данными пользователей
 * у пользователя есть уникальные ID и никнейм ($login), дата регистрации ($date), почта ($email), хэш пароля($password)
 * временные и постоянные права ($roles) и методы работы с ними (can_user()),
 * параметры, индивидуальные для каждого пользователя ($options)
 */
final class rad_user extends rad_user_auth{
	
	/**
	 * создает объект юзера
	 * @param int $id - ID юзера, null для создания объекта текущекого юзера
	 */
	function __construct($id = null){
		parent::__construct($id);
		if(empty($id) && isset($_COOKIE['sid'])){
			try{
				$this->load_user_by_token((string)$_COOKIE['sid']);
			}catch(Exception $e){
				$this->user_logout();
			}
		}
	}
	
	/**
	 * добавляет в БД нового пользователя, также перед добавлением производит проверки
	 * @param string $login - логин пользователя
	 * @param string $password - пароль пользователя (НЕ хэш)
	 * @param string $email - почта пользователя
	 * @param string $level - уровень пользователя
	 * @return int
	 * >0: id вставленного пользователя
	 * -1: пароль короткий;
	 * -2: пароль содержит недопустимые символы;
	 * -3: логин короткий;
	 * -4: логин содержит недопустимые символы;
	 * -5: логин неуникален;
	 * -6: почтовый ящик пуст;
	 * -7: почтовый ящик не валиден;
	 * -10: почтовый ящик не уникален;
	 * -8: уровень пользователя не лежит в интервале;
	 * -9: ползователь не добавлен;
	 */
	static public function create_new_user($login, $password, $email, $level){
		global $DB;
		$password_clear = password_clear($password);
		if(strcmp($password_clear, $password)){
			return -2;
		}
		if(mb_strlen($password_clear) < 6){
			return -1;
		}
		$pass_hash = '0x'.self::password_hash($password_clear, $login, 0);
		
		$login_clear = login_clear($login);
		if(strcmp($login_clear, $login)){
			return -4;
		}
		if(mb_strlen($login_clear) < 1){
			return -3;
		}
		if($DB->getOne('SELECT `id` FROM `our_u_users` WHERE `login` = ?s', $login)){
			return -5;
		}

		$email = trim($email);
		if(mb_strlen($email)< 1){
			return -6;
		}
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return -7;
		}
		if($DB->getOne('SELECT `id` FROM `our_u_users` WHERE `email` = ?s', $email)){
			return -10;
		}
		
		if($level < rad_user_roles::USER || $level > rad_user_roles::ADMIN){
			return -8;
		}

		if(!$DB->query(
			'INSERT INTO `our_u_users` (`login`, `password`, `email`, `date`, `level`) VALUES(?s, ?p, ?s, ?p, ?i)',
			$login_clear,
			$pass_hash,
			$email,
			'MY_NOW()',
			$level
		)){
			return -9;
		}
		
		return $DB->insertId();
	}

	/**
	 * Обновляет пароль текущего пользователя
	 * @param string $new_password - новый пароль пользователя (НЕ хэш)
	 * @return int
	 *  0: пароль обновлен
	 * -1: пароль короткий;
	 * -2: пароль содержит недопустимые символы;
	 * -3: ошибка базы;
	 * -4: юзер - гость
	 */
	public function change_password($new_password){
		if(!$this->id)
			return -4;
		
		global $DB;
		$password_clear = password_clear($new_password);
		if(mb_strlen($password_clear) < 6){
			return -1;
		}
		if(strcmp($password_clear, $new_password)){
			return -2;
		}
		$pass_hash = '0x'.self::password_hash($password_clear, $this->login, 0);
		if($DB->query('UPDATE `our_u_users` SET `password` = ?p WHERE `id` = ?i', $pass_hash, $this->id)){
			$this->clear_all_tokens();
			return 0;
		}else{
			return -3;
		}
		
	}

	/**
	 * дает юзеру уровень VERIFIED, или восстанавливает прежний
	 * удаляет параметры 'mail_verified_token' и 'old_user_level'
	 * @see send_verified_mail
	 * //TODO обернуть в транзакцию
	 */
	public function mail_verify(){
		$old_level = $this->options->get_option('old_user_level');
		$old_level = absint($old_level);
		$this->options->set_option('old_user_level', null);
		$this->options->set_option('mail_verified_token', null);
		$this->options->update_options('old_user_level', 'mail_verified_token');
		
		if($this->user_level >= rad_user_roles::VERIFIED)
			return;
		
		if($old_level > rad_user_roles::ADMIN)
			return;
		if($old_level <= rad_user_roles::VERIFIED){
			$this->roles->set_user_level(rad_user_roles::VERIFIED);
		}else{
			$this->roles->set_user_level($old_level);
		}
	}
}
?>