<?php

/*
структуры используемых таблиц
CREATE TABLE `our_users` (
	`id` bigint(20) UNSIGNED NOT NULL auto_increment,
	`login` varchar(30) NOT NULL,
	`password` varbinary(20) NOT NULL,
	`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`level` smallint(6) NOT NULL DEFAULT '1',
	`roles` text NOT NULL,
	`options` mediumtext NOT NULL
	PRIMARY KEY  (`id`)
)

CREATE TABLE `coupons` (
	`id` bigint(20) UNSIGNED NOT NULL auto_increment,
	`coupon` text NOT NULL,
	`roles` text NOT NULL,
	`work_time` tinytext NOT NULL,
	`work_end` timestamp NULL DEFAULT NULL,
	`user_id` bigint(20) UNSIGNED DEFAULT NULL
	PRIMARY KEY  (`id`)
)
*/

/**
 * Класс предоставляет методы работы с данными пользователей
 * у пользователя есть уникальные ID и никнейм ($login), дата регистрации ($date),
 * права ($roles) и методы работы с ними (can_user()),
 * параметры, индивидуальные для каждого пользователя ($options)
 */
class rad_user{
	/**  @var int $id - ID пользователя в БД */
	private $id;
	/** @var string $login - логин юзера в БД */
	private $login = '';
	/** @var string $date - дата (timestamp) регистрации юзера в БД */
	private $date = 0;
	/**  @var string $email - почта пользоватля */
	private $email;
	/** @var array $roles - массив id прав пользователя */
	private $roles;

	/**  @var array $all_roles - массив всех возможных прав пользователя*/
	static private $all_roles = null;
	
	/** @var array $options - массив параметров пользователя */
	private $options = null;
	/**
	 * @var int $user_level - уровень прав пользователя
	 * @see get_roles_range
	 */
	private $user_level;
	/** @var int Гость, не залогинен */
	const GUEST = 0;
	/** @var int юзер, мин права*/
	const USER = 1;
	/** @var int юзер, макс права*/
	const SUPERUSER = 45;
	/** @var int админ, мин права*/
	const NEDOADMIN = 50;
	/** @var int админ, макс права*/
	const ADMIN  = 100;
	
	/**
	 * создает объект юзера
	 * @param int $id - ID юзера, null для создания объекта текущекого юзера
	 */
	function __construct($id = null){
		self::load_all_roles();
		if(isset($id)){
			try{
				$this->load_user($id);
			}catch(Exception $e){
				$this->set_guest();
			}
		}else{
			if(is_login()){
				try{
					$this->load_user($_SESSION['user_id']);
				}catch(Exception $e){
					$this->user_logout();
				}
			}else{
				$this->set_guest();
			}
		}
	}
	
	/**
	 * Загружает данные пользователя из БД
	 * @param int $id - ID пользователя
	 * @throws Exception - пользователь не найден в БД
	 */
	function load_user($id){
		global $DB;
		$this->id = $id;
		$tmp = $DB->getRow('SELECT `login`, `email`, `level`, `date` FROM `our_u_users` WHERE `id` = ?i', $this->id);
		if(empty($tmp))
			throw new Exception('undefined user');
		$this->login = $tmp['login'];
		$this->email = $tmp['email'];
		$this->date = $tmp['date'];
		$this->user_level = absint($tmp['level']);
		$this->load_roles();
		$this->options = array();
	}

	/**
	 * загружает массив id, дополнительных прав пользователя
	 */
	private function load_roles(){
		global $DB;
		//загружаем перманентные права, work_time == 'inf'
		$this->roles = $DB->getCol('SELECT `role_id` FROM `our_u_users_roles` WHERE `user_id` = ?i AND `work_time` != ?s', $this->id, 'INF');
	}
	
	/**
	 * Устанавливает пользователя гостем
	 */
	function set_guest(){
		$this->id = 0;
		$this->user_level = $this::GUEST;
		$this->email = '';
		$this->roles = array();
		$this->options = array();
	}
	
	/**
	 * Загружает параметры пользователя из БД
	 * @return bool - false если пользователь гость
	 */
	function load_all_options(){
		if(!$this->id)
			return false;
		global $DB;
		$tmp = $DB->getAll('SELECT `key`, `value` FROM `our_u_options` WHERE `user_id` = ?i', $this->id);
		$len = sizeof($tmp);
		for($i=0; $i<$len; $i++){
			$this->options[$tmp[$i]['key']] = array('isset' => true, 'val' => unserialize($tmp[$i]['value']));
		}
		return true;
	}
	
	/**
	 * Возвращает параметр юзера
	 * @param string $key - ключ параметра
	 * @return mixed|null - null в случае если параметр не существует
	 */
	function get_option($key){
		if(isset($this->options[$key]))
			return $this->options[$key]['val'];
		global $DB;
		$key = option_name_clear($key);
		if((string)$key === '')
			return null;
		$tmp = $DB->getOne('SELECT `value` FROM `our_u_options` WHERE `user_id` = ?i AND `key` = ?s', $this->id, $key);
		if($tmp === false){
			$this->options[$key] = array('isset' => false, 'val' => null);
		}else{
			$this->options[$key] = array('isset' => true, 'val' => unserialize($tmp));
		}
		return $this->options[$key]['val'];
	}
	
	/**
	 * Устанавливает параметр для пользователя
	 * @param string $key - ключ параметра
	 * @param mixed $value - значение параметра, если null параметр удаляется
	 */
	function set_option($key, $value){
		$key = option_name_clear($key);
		if((string)$key === '')
			return false;
		if(!isset($this->options[$key])){
			$this->get_option($key);
		}
		$this->options[$key]['val'] = $value;
		return true;
	}
	
	/**
	 * Обновляет указанные параметры пользователя в БД
	 * @param array|string $param1 - массив ключей
	 * @param string $param2 или ключи
	 * @param string $param3
	 */
	function update_options(){
		//собираем массив ключей параметров
		if(func_num_args() == 0)
			return;
		/** @var  string[] $args - массив ключей параметров */
		$args = func_get_args();
		if(is_array($args[0])){
			$args = $args[0];
		}
		//обновляем параметры
		global $DB;
		$len = sizeof($args);
		for($i=0; $i<$len; $i++){
			$key = $args[$i];
			if(isset($this->options[$key])){
				//если параметр установлен, но равен null
				if($this->options[$key]['isset'] && is_null($this->options[$key]['val'])){
					//то его нужно удалить
					$DB->query('DELETE FROM `our_u_options` WHERE `user_id` = ?i AND `key` = ?s', $this->id, $key);
					$this->options[$key]['isset'] = false;
				//если параметр установлен и имеет значение
				}else if($this->options[$key]['isset'] && !is_null($this->options[$key]['val'])){
					//то его нужно обновить
					$DB->query('UPDATE `our_u_options` SET `value` = ?s WHERE `user_id` = ?i AND `key` = ?s', serialize($this->options[$key]['val']), $this->id, $key);
				//если параметр не установлен, но имеет значение
				}else if(!$this->options[$key]['isset'] && !is_null($this->options[$key]['val'])){
					//то его нужно добавить
					$DB->query('INSERT INTO `our_u_options` (`user_id`, `key`, `value`) VALUES (?i, ?s, ?s)', $this->id, $key, serialize($this->options[$key]['val']));
					$this->options[$key]['isset'] = true;
				}
			}
		}
	}
	
	/**
	 * Обновляет значение всех параметров юзера в БД
	 */
	function update_all_options(){
		global $DB;
		$query = 'INSERT INTO `our_u_options` (`user_id`, `key`, `value`) VALUES ';
		foreach($this->options as $key => $val){
			if(!$this->options[$key]['isset'] && !is_null($this->options[$key]['val'])){
				$this->options[$key]['isset'] = true;
			}
			$query .= $DB->parse('(?i, ?s, ?s),',  $this->id, $key, serialize($val));
		}
		$DB->query('LOCK TABLES `our_u_options` WRITE');
		$DB->query('DELETE FROM `our_u_options` WHERE `user_id` = ?i', $this->id);
		if(sizeof($this->options)){
			$DB->query(mb_substr($query, 0, mb_strlen($query)-1));
		}
		$DB->query('UNLOCK TABLES');
	}

	/**
	 * загружает данные о возможных правах
	 */
	static public function load_all_roles(){
		global $DB;
		if(is_null(self::$all_roles))
			self::$all_roles = $DB->getAll('SELECT * FROM `our_u_roles`');
	}

	/**
	 * возвращает границу права пользователя, если передана одна строка с ключом права; массив границ прав, если передано несколько ключей; или все границы, если не переданы параметры
	 * @param string $type - тип ключа 'id' или 'role'
	 * @param string[] $add_columns - дополнительные колонки ('id', 'role', 'description', 'level')
	 * @param array|string $keys - массив ключей
	 * @param string $_ или ключи
	 * @return array|int|null
	 * права
	 * view_debug_info
	 * edit_users
	 * edit_settings
	 * ignore_max_token_remember
	 */
	static public function get_roles_range(){
		//используйте только лат. буквы и нижние подчеркивания
		self::load_all_roles();
		$args = func_get_args();
		$type = array_shift($args);
		$add_columns = array_shift($args);
		$roles_range = array();
		switch($type){
			case 'role':
			case 'id':
				$roles_range = my_array_column(self::$all_roles, $add_columns, $type);
				break;
			default:
				return null;
		}
		if(func_num_args() == 2){
			return $roles_range;
		}else if(func_num_args() == 3){
			if(!is_array($args[0])){
				return isset($roles_range[$args[0]]) ? $roles_range[$args[0]] : null;
			}else{
				$args = $args[0];
			}
		}
		return array_intersect_key($roles_range, array_flip($args));
	}

	/**
	 * Добавление пользователю постоянных прав, если уровень пользователя больше, чем уровень права, то оно не установится, но будет работать
	 * @param string|string[] $role - role права из get_roles_range()
	 * @see get_roles_range
	 * @return bool
	 */
	function add_role($role){
		$tmp = self::get_roles_range('role', array('id', 'level'), $role);
		if(is_null($tmp))
			return false;
		$add = array();
		if(is_array($role)){
			foreach($tmp as $r => &$val){
				if($this->user_level >= $val['level'])
					continue;
				if(in_array($val['id'], $this->roles))
					continue;
				$add[] = $val['id'];
			}
		}else{
			if($this->user_level >= $tmp['level'])
				return true;
			if(in_array($tmp['id'], $this->roles)){
				return false;
			}
			$add[] = $tmp['id'];
		}
		$len = sizeof($add);
		if(!$len)
			return false;
		global $DB;
		$query = 'INSERT INTO `our_u_users_roles` (`user_id`, `role_id`) VALUES';
		for($i=0; $i<$len; $i++){
			$query .= $DB->parse(' (?i, ?i)', $this->id, $add[$i]);
			if($i != $len-1)
				$query .= ',';
		}
		$DB->query($query);

		$this->roles = array_merge($this->roles, $add);
		return true;
	}
	
	
	/**
	 * Добавление пользователю временных прав
	 * @param string|string[] $role - role права из get_roles_range()
	 * @param string $work_time - время работы права - sql time INTERVAL
	 * @param string|DateTime $work_start - время начала работы права
	 * @param int|null $act_id - id транзакции, вследствии которой добавляется это право
	 * @see get_roles_range
	 * @return bool
	 */
	function add_temp_role($role, $work_time, $work_start='now', $act_id=null){
		$tmp = self::get_roles_range('role', 'id', $role);
		if(is_null($tmp))
			return false;
		$add = array();
		if(is_array($role)){
			$add = array_values($tmp);
		}else{
			$add[] = $tmp;
		}
		$work_time = sql_time_interval_clear($work_time);
		if($work_start === 'now'){
			$work_start = 'NOW()';
		}else{
			$work_start = '\''.$work_start->format('Y-m-d H:i:s').'\'';
		}
		$len = sizeof($add);
		global $DB;
		$query = 'INSERT INTO `our_u_users_roles` (`user_id`, `role_id`, `start_time`, `end_time`, `work_time`, `action_id`) VALUES';
		for($i=0; $i<$len; $i++){
			$query .= $DB->parse(
				' (?i, ?i, ?p, NOW() + INTERVAL ?p, ?s, ?i)',
				$this->id,
				$add[$i],
				$work_start,
				$work_time,
				$work_time,
				$act_id
			);
			if($i != $len-1)
				$query .= ',';
		}
		$DB->query($query);
		return true;
	}
	
	
	/**
	 * Удаление дополнительных прав пользователя
	 * @param string|string[] $role - role права из get_roles_range()
	 * @param string $type - 'inf' - постоянные права, 'temp' - временные, 'all' - все
	 * @see get_roles_range
	 * @return bool
	 */
	function remove_role($role, $type='inf'){
		$tmp = self::get_roles_range('role', 'id', $role);
		if(is_null($tmp)){
			return false;
		}else{
			if(!is_array($tmp)){
				$tmp = array($tmp);
			}else{
				$tmp = array_values($tmp);
			}
			$len = sizeof($tmp);
			for($i=0; $i<$len; $i++){
				$key = array_search($tmp[$i], $this->roles);
				if($key !== false){
					unset($this->roles[$key]);
				}else{
					unset($tmp[$i]);
				}
			}
			if(!$len)
				return false;
			
			$query = '';
			switch($type){
				case 'inf':
					$query = ' AND `work_time` = \'INF\'';
					break;
				case 'temp':
					$query = ' AND `work_time` != \'INF\'';
					break;
				case 'all':
					break;
				default:
					throw new Exception('undefined type');
			}
			
			global $DB;
			$DB->query('DELETE FROM `our_u_users_roles` WHERE `user_id` = ?i AND `role_id` IN(?a)?p', $this->id, $tmp, $query);
			return true;
		}
	}

	/**
	 * Удаление всех постоянных дополнительных прав пользователя
	 * @param string $type - 'inf' - постоянные права, 'temp' - временные, 'all' - все
	 */
	function remove_all_roles($type='inf'){
		$this->roles = array();

		$query = '';
		switch($type){
			case 'inf':
				$query = ' AND `work_time` = \'INF\'';
				break;
			case 'temp':
				$query = ' AND `work_time` != \'INF\'';
				break;
			case 'all':
				break;
			default:
				throw new Exception('undefined type');
		}
		
		global $DB;
		$DB->query('DELETE FROM `our_u_users_roles` WHERE `user_id` = ?i?p', $this->id, $query);
	}

	static function get_levels_range(){
		return array(
				self::GUEST => 'GUEST (гость)',
				self::USER => 'USER (пользователь без прав)',
				self::NEDOADMIN => 'NEDOADMIN (администратор без прав)',
				self::ADMIN => 'ADMIN (администратор со всеми правами)'
		);
	}
	
	/**
	 * Проверяет имеет ли юзер право $role, также проверяет имеет ли юзер купон для данного действия (юзеры неадмины не могут использовать админские купоны)
	 * если передано несуществующее право - false
	 * @param string|string[] $role - id права из get_roles_range()
	 * @see get_roles_range
	 * @return bool
	 */
	function can_user($role){
		$role_range = $this->get_roles_range('role', array('id', 'level'), $role);
		//если передано несуществующее право - false
		if(is_null($role_range))
			return true;
		if(is_array($role)){
			$role_range = array_values($role_range);
		}else{
			$role_range = array($role_range);
		}
		$len = sizeof($role_range);
		global $DB;
		//получим массив, где ключи это id временных прав, которые доступны пользователю
		$temp_roles = array();
		if($len){
			$temp_roles = $DB->getIndCol(
				'role_id',
				'SELECT `end_time` FROM `our_u_users_roles`
					WHERE `user_id` = ?i
					AND `work_end` >= NOW()
					AND `work_start` <= NOW()
					AND `work_time` != \'INF\'
					AND `role_id` IN(?a)',
				$this->id,
				my_array_column($role_range, 'id')
			);
		}
		for($i=0; $i<$len; $i++){
			/*
			//если юзер не админ, а право админское - false
			if($this->user_level <= $this::SUPERUSER && $role_range[$i]['level'] >= $this::NEDOADMIN)
				return false;
			*/
			//если уровень юзера меньше уровня права и у него нет этого права - false
			if($this->user_level < $role_range[$i]['level'] && !in_array($role_range[$i]['id'], $this->roles) && !isset($temp_roles[$role_range[$i]['id']]))
				return false;
		}
		return true;
	}
	
	/**
	 * вернет ID юзера
	 * @return int
	 */
	public function get_id(){
		return $this->id;
	}
	
	/**
	 * вернет логин юзера
	 * @return string
	 */
	public function get_login(){
		return $this->login;
	}

	/**
	 * вернет почту юзера
	 * @return string
	 */
	public function get_email(){
		return $this->email;
	}

	public function set_email($email){
		if($this->user_level == $this::GUEST)
			return false;
		$email = trim($email);
		if((string)$email === '')
			return false;
		$this->email = $email;
		global $DB;
		$DB->query('UPDATE `our_u_users` SET `email` = ?s WHERE `id` = ?i', $this->email, $this->id);
		return true;
	}
	
	/**
	 * вернет массив ключей доп. прав юзера
	 * @return array
	 */
	public function get_roles(){
		return $this->roles;
	}
	
	/**
	 * вернет уровень прав юзера
	 * @return int
	 */
	public function get_user_level(){
		return $this->user_level;
	}

	public function set_user_level($level){
		$level = absint($level);
		if($this->user_level == $this::GUEST)
			return false;
		if($level < $this::USER)
			$level = $this::USER;
		$this->user_level = $level;
		global $DB;
		$DB->query('UPDATE `our_u_users` SET `level` = ?i WHERE `id` = ?i', $this->user_level, $this->id);
		return true;
	}
	
	/**
	 * вернет дату (timestamp) регистрации юзера
	 * @return string
	 */
	public function get_date(){
		return $this->date;
	}
	
	/**
	 * Делает юзера гостем и удаляет сессию текущего юзера
	 */
	function user_logout(){
		if(is_session_exists()){
			session_unset();
			session_destroy();
			setcookie(session_name(), '', time() - 3600 * 24);
		}
		setcookie('token', '', time()- 3600*24);
		//TODO: переделать логику токенов
		$sha_user_agent = sha1($_SERVER['HTTP_USER_AGENT']);
		$tokens_arr = $this->get_option('login_tokens');
		$tokens_arr = is_null($tokens_arr) || !is_array($tokens_arr) ? array() : $tokens_arr;
		if(isset($tokens_arr[$sha_user_agent]))
			unset($tokens_arr[$sha_user_agent]);
		$this->set_option('login_tokens', $tokens_arr);
		$this->update_options('login_tokens');
		$this->set_guest();
	}
}
?>