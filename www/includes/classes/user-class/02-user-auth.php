<?php
if(!defined('MAIN_DIR'))
	die();

require_once MAIN_DIR.'includes/classes/user-class/01-user-base.php';

abstract class rad_user_auth extends rad_user_base{
	
	function __construct($id){
		parent::__construct($id);
		$this->clear_old_tokens();
	}

	/**
	 * Загружает пользователя с помощью логина и пароля
	 * если не удачно, текущий экземпляр станет гостем
	 * @param string $login - логин пользователя, лечится login_clear
	 * @param string $password - пароль пользователя (НЕ хэш)
	 * @see login_clear
	 * @see rad_user::password_hash
	 * @return bool - загружен ли пользователь
	 */
	final function load_by_loginpass($login, $password){
		global $DB;
		$password = (string) $password;
		$login = login_clear($login);
		if($password === '' || $login === ''){
			$this->set_guest();
			return false;
		}
		$data = $DB->getRow('SELECT `id`, `password`, `login` FROM `our_u_users` WHERE `login` = ?s', $login);
		if(!$data){
			$this->set_guest();
			return false;
		}
		$pass_hash = self::password_hash($password, $data['login']);
		if(!hash_equals($data['password'], $pass_hash)){
			$this->set_guest();
			return false;
		}
		if(!$this->load_user($data['id'])){
			$this->set_guest();
			return false;
		}
		return true;
	}
	
	
	/**
	 * загружает ползователя с помощью токена
	 * если не удачно, текущий экземпляр станет гостем
	 * @param string $token - $_COOKIE['sid']
	 * @return bool - загружен ли пользователь
	 */
	final function load_user_by_token($token){
		global $DB, $OPTIONS;
		$token_data = self::decode_cookie_token($token);
		if($token_data){
			if(is_array($token_data['data']) && isset($token_data['data']['user_id'])){
				$user_id = absint($token_data['data']['user_id']);
				$hash = hex_clear($token_data['hash']);
				if($user_id && mb_strlen($hash) === 64){//длина sha256
					$check_token_data = $DB->getRow('SELECT `time_end`, `user_agent`, `time_work` FROM `our_u_tokens` WHERE `user_id` = ?i AND `token` = ?p', $user_id, '0x'.$hash);
					if($check_token_data){
						$sha_user_agent = sha1($OPTIONS['user_agent']);
						$check_token_data['time_end'] = DateTime::createFromFormat(DB_DATETIME_FORMAT, $check_token_data['time_end']);
						$now_time = new DateTime();
						if(strcmp($sha_user_agent, bin2hex($check_token_data['user_agent'])) === 0 && $check_token_data['time_end'] > $now_time){
							//токен подошел
							//обновим время жизни
							$type = $check_token_data['time_work'] === 'PT'.SESSION_TOKEN_LIVE_SECONDS.'S' ? 'session' : 'remember';
							$time_end = $now_time->add(new DateInterval($check_token_data['time_work']));
							$DB->query('UPDATE `our_u_tokens` SET `time_end` = ?s WHERE `user_id` = ?i AND `token` = ?p', $time_end->format(DB_DATETIME_FORMAT), $user_id, '0x'.$hash);
							if($type == 'remember')
								setcookie('sid', $token, $time_end->getTimestamp(), '/', null, USE_SSL, 1);
							//вход
							$this->load_user($user_id);
							return true;
						}else{
							//токен существует, но либо старый, либо не совпадают user_agent
							$DB->query('DELETE FROM `our_u_tokens` WHERE `user_id` = ?i AND `token` = ?p', $user_id, '0x'.$hash);
						}
					}
				}
			}
		}
		setcookie('sid', '', time()-SECONDS_PER_DAY);
		$this->set_guest();
		return false;
	}

	/**
	 * обертка для set_default()
	 * @see rad_user_base::set_default
	 */
	final function set_guest(){
		$this->set_default();
	}
	
	/**
	 * Возвращает хеш пароля пользователя
	 * пароль не обрабатывается
	 * @param string $password
	 * @param string $dynamic
	 * @param bool $binary
	 * @return string
	 */
	static final public function password_hash($password, $dynamic='', $binary=true){
		$ret = hash('sha256', SALT_START.$password.$dynamic.SALT_END);
		return hash('sha256', SALT_END.strrev($ret).SALT_START, $binary);
	}

	/**
	 * проверяет незанятость логина
	 * @param string $login - логин пользователя, пропускается через login_clear
	 * @see login_clear();
	 * @return bool - true если логин не занят
	 */
	static final public function check_login($login){
		global $DB;
		$login = login_clear($login);
		if(mb_strlen($login) < 1)
			return false;
		return !is_string($DB->getOne('SELECT `id` FROM `our_u_users` WHERE `login` = ?s', $login));
	}

	/**
	 * Делает юзера гостем и удаляет сессию текущего юзера
	 */
	final function user_logout(){
		if(isset($_COOKIE['sid'])){
			$token_data = self::decode_cookie_token((string)$_COOKIE['sid']);
			if($token_data){
				//токен распарсился, грохнем его
				global $DB;
				if(is_array($token_data['data']) && isset($token_data['data']['user_id'])){
					$user_id = absint($token_data['data']['user_id']);
					$token = hex_clear($token_data['hash']);
					if($user_id === $this->id && mb_strlen($token) === 64){//длина sha256
						$DB->query('DELETE FROM `our_u_tokens` WHERE `user_id` = ?i AND `token` = ?p', $user_id, '0x'.$token);
					}
				}
			}
		}
		setcookie('sid', '', time()- SECONDS_PER_DAY);
		$this->set_guest();
	}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//Все что связано с токенами
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Создает токен аутентификации для текущего пользователя
	 * блокирует и разблокирует таблицы `our_u_tokens`
	 * @param string $type - 'session': SESSION_TOKEN_LIVE_SECONDS, 'remember':REMEMBER_TOKEN_LIVE_DAYS
	 * @see REMEMBER_TOKEN_LIVE_DAYS, SESSION_TOKEN_LIVE_SECONDS
	 * @return array - содержит ключ 'error' если произошла ошибка, ['status' => int, 'token' => string, 'date_end_token' => DateTime]
	 * status  0: успешно;
	 * status -1: юзер - гость;
	 * status -2: ошибочный $type;
	 * status -3: достигнут предел активных сессий;
	 */
	final public function create_token($type='session'){
		$ret = array('status' => 0, 'token' => '', 'date_end_token' => new DateTime());
		if(!$this->id){
			$ret['status'] = -1;
			return $ret;
		}
		
		$date_interval = '';
		switch($type){
			case 'session':
				$date_interval = 'PT'.SESSION_TOKEN_LIVE_SECONDS.'S';
				break;
			case 'remember':
				$date_interval = 'P'.REMEMBER_TOKEN_LIVE_DAYS.'D';
				break;
			default:
				$ret['status'] = -2;
				return $ret;
		}
		
		global $DB, $OPTIONS;
		$this->clear_old_tokens();
		$ignore_token_count = $this->roles->can_user('ignore_max_token_remember');
		$DB->query('LOCK TABLES `our_u_tokens` WRITE');
		$tokens_count = $DB->getOne('SELECT COUNT(*) FROM `our_u_tokens` WHERE `user_id` = ?i', $this->id);
		//проверка ограничения на количество токенов
		if($tokens_count + 1 > MAX_TOKEN_REMEMBER && !$ignore_token_count){
			$DB->query('UNLOCK TABLES');
			$ret['status'] = -3;
			return $ret;
		}
		
		$sha_user_agent = sha1($OPTIONS['user_agent']);
		$time_start = new DateTime();
		$token = hash('sha256', mt_rand().$this->pass_hash.$sha_user_agent.$time_start->getTimestamp().$this->id);
		$ret['date_end_token'] = $time_start->add(new DateInterval($date_interval));
		$DB->query(
			'INSERT INTO `our_u_tokens` (`user_id`, `token`, `user_agent`, `time_start`, `time_end`, `time_work`) VALUES (?i, ?p, ?p, ?s, ?s, ?s)',
			$this->id,
			'0x'.$token,
			'0x'.$sha_user_agent,
			$time_start->format(DB_DATETIME_FORMAT),
			$ret['date_end_token']->format(DB_DATETIME_FORMAT),
			$date_interval
		);
		$DB->query('UNLOCK TABLES');
		$ret['token'] = self::encode_cookie_token(array('user_id' => $this->id), $token);
		return $ret;
	}

	/**
	 * удаляет устаревшие токены текущего пользователя
	 */
	final public function clear_old_tokens(){
		if($this->id)
			self::delete_old_tokens($this->id);
	}

	/**
	 * удаляет устаревшие токены пользователя
	 * @param int $user_id - ID пользователя
	 */
	static final public function delete_old_tokens($user_id){
		global $DB;
		$DB->query('DELETE FROM `our_u_tokens` WHERE `user_id` = ?i AND `time_end` < NOW()', $user_id);
	}

	/**
	 * удаляет все токены текущего пользователя
	 */
	final public function clear_all_tokens(){
		if($this->id)
			self::delete_all_tokens($this->id);
	}

	/**
	 * удаляет все токены пользователя
	 * @param int $user_id - ID пользователя
	 */
	static final public function delete_all_tokens($user_id){
		global $DB;
		$DB->query('DELETE FROM `our_u_tokens` WHERE `user_id` = ?i', $user_id);
	}

	/**
	 * создает токен для записи в cookie
	 * собираем токен
	 * имеет вид base64.base64, == обрезаются
	 * 1-ая часть - JSON $data
	 * 2-ая часть - $hash
	 * @param array|mixed $data - вложенность(глубина) не больше 10
	 * @param string $hash
	 * @return string
	 */
	static final public function encode_cookie_token($data, $hash){
		return str_replace('=', '', base64_encode(json_encode($data)).'.'.base64_encode($hash));
	}

	/**
	 * @see encode_cookie_token
	 * @param string $token - строка сгенерированная encode_cookie_token
	 * @return array|false ['data' => array|mixed, 'hash' => string]
	 */
	static final public function decode_cookie_token($token){
		$token_data = explode('.', $token, 2);
		if(sizeof($token_data) < 2)
			return false;
		$ret = array(
			'data' => base64_decode($token_data[0], 1),
			'hash' => base64_decode($token_data[1], 1)
		);
		if($ret['data'] === false || $ret['hash'] === false)
			return false;
		$ret['data'] = json_decode($ret['data'], true, 10);
		return $ret;
	}
}

?>