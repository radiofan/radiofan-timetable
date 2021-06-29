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
	/**
	 * @var int $id - ID пользователя в БД
	 */
	private $id;
	/**
	 * @var string $login - логин юзера в БД
	 */
	private $login = '';
	/**
	 * @var string $date - дата (timestamp) регистрации юзера в БД
	 */
	private $date = 0;
	/**
	 * @var array $roles - массив прав пользователя
	 */
	private $roles;
	/**
	 * @var array $options - массив параметров пользователя
	 */
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
		$tmp = $DB->getRow('SELECT `login`, `level`, `roles`, `date` FROM `our_users` WHERE `id` = ?i', $this->id);
		if(empty($tmp))
			throw new Exception('undefined user');
		$this->login = $tmp['login'];
		$this->date = $tmp['date'];
		$this->roles = unserialize($tmp['roles']);
		$this->user_level = absint($tmp['level']);
	}
	
	/**
	 * Устанавливает пользователя гостем
	 */
	function set_guest(){
		$this->id = 0;
		$this->user_level = $this::GUEST;
		$this->roles = array();
		$this->options = array();
	}
	
	/**
	 * Загружает параметры пользователя из БД
	 * @return bool - false если пользователь гость
	 * @throws Exception - пользователь не найден в БД
	 */
	function load_options(){
		if(!$this->id)
			return false;
		global $DB;
		$tmp = $DB->getOne('SELECT `options` FROM `our_users` WHERE `id` = ?i', $this->id);
		if($tmp === false)
			throw new Exception('undefined user');
		$this->options = unserialize($tmp);
		return true;
	}
	
	/**
	 * Возвращает параметр юзера
	 * @param $key - ключ параметра
	 * @return mixed|null - null в случае если параметр не существует
	 */
	function get_option($key){
		if(is_null($this->options)){
			try{
				$this->load_options();
			}catch(Exception $e){
				$this->user_logout();
			}
		}
		return isset($this->options[$key]) ? $this->options[$key] : null;
	}
	
	/**
	 * Устанавливает параметр для пользователя
	 * @param $key - ключ параметра
	 * @param $value - значение параметра
	 */
	function set_option($key, $value){
		if(is_null($this->options))
			$this->options = array();
		if(is_null($value) && isset($this->options[$key])){
			unset($this->options[$key]);
		}else{
			$this->options[$key] = $value;
		}
	}
	
	/**
	 * Обновляет указанные парамтеры пользователя в БД
	 * @param array|string $param1 - массив ключей
	 * @param string $param2 или ключи
	 * @param string $param3
	 * @throws Exception - пользователь не найден в БД
	 */
	function update_options(){
		if(func_num_args() == 0)
			return;
		$args = func_get_args();
		if(is_array($args[0])){
			$args = $args[0];
		}
		$len = sizeof($args);
		$old_options = $this->options;
		$this->load_options();
		for($i=0; $i<$len; $i++){
			//если существует параметр с ключом из массива
			if(isset($old_options[$args[$i]])){
				//задаем значение параметра
				$this->options[$args[$i]] = $old_options[$args[$i]];
			}else{
				//иначе попытаемся удалить параметр
				unset($this->options[$args[$i]]);
			}
		}
		$this->update_all_options();
	}
	
	/**
	 * Обновляет значение всех параметров юзера в БД
	 */
	function update_all_options(){
		global $DB;
		$DB->query('UPDATE `our_users` SET `options` = ?s WHERE `id` = ?i', serialize($this->options), $this->id);
	}
	
	/**
	 * Добавление пользователю прав, если уровень пользователя больше, чем уровень права, то оно не установится, но будет работать
	 * @param $role - id права из get_roles_range()
	 * @see get_roles_range
	 * @return bool
	 */
	function add_role($role){
		$tmp = $this->get_roles_range($role);
		if($this->user_level >= $tmp)
			return true;
		if(is_null($tmp) || in_array($role, $this->roles)){
			return false;
		}else{
			$this->roles[] = $role;
			global $DB;
			$DB->query('UPDATE `our_users` SET `roles` = ?s WHERE `id` = ?i', serialize($this->roles), $this->id);
			return true;
		}
	}
	
	/**
	 * Удаление дополнительных прав пользователя
	 * @param $role - id права из get_roles_range()
	 * @see get_roles_range
	 * @return bool
	 */
	function remove_role($role){
		$tmp = array_search($role, $this->roles);
		if($tmp === false){
			return false;
		}else{
			unset($this->roles[$tmp]);
			global $DB;
			$DB->query('UPDATE `our_users` SET `roles` = ?s WHERE `id` = ?i', serialize($this->roles), $this->id);
			return true;
		}
	}
	
	/**
	 * Удаление всех дополнительных прав пользователя
	 */
	function remove_all_roles(){
			$this->roles = array();
			global $DB;
			$DB->query('UPDATE `our_users` SET `roles` = ?s WHERE `id` = ?i', serialize($this->roles), $this->id);
	}
	
	/**
	 * возвращает границу права пользователя, если передана одна строка с ключом права, массив границ прав, если передано несколько ключей, или все границы, если не переданы параметры
	 * @param array|string $param1 - массив ключей
	 * @param string $param2 или ключи
	 * @param string $param3
	 * @return array|int|null
	 * права
	 * view_debug_info
	 * edit_users
	 * edit_settings
	 */
	function get_roles_range(){
		//используйте только лат. буквы и нижние подчеркивания
		$roles_range = array(
			'view_debug_info'=> $this::ADMIN,
			'edit_users'     => $this::ADMIN,
			'edit_settings'  => $this::ADMIN
		);
		$args = func_get_args();
		if(func_num_args() == 0){
			return $roles_range;
		}else if(func_num_args() == 1){
			if(!is_array($args[0])){
				return isset($roles_range[$args[0]]) ? $roles_range[$args[0]] : null;
			}else{
				$args = $args[0];
			}
		}
		return array_intersect_assoc($roles_range, array_flip($args));
	}
	
	/**
	 * Проверяет имеет ли юзер право $role, также проверяет имеет ли юзер купон для данного действия (юзеры неадмины не могут использовать админские купоны)
	 * @param $role - id права из get_roles_range()
	 * @see get_roles_range
	 * @return bool
	 */
	function can_user($role){
		$role_range = $this->get_roles_range($role);
		//если передано несуществующее право - false
		if(is_null($role_range))
			return false;
		//если уровень юзера больше уровня права - true
		if($this->user_level >= $role_range)
			return true;
		//если юзер имеет доп право - true
		if(in_array($role, $this->roles))
			return true;
		
		return false;
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
		setcookie('user_id', '', time()- 3600*24);
		$this->set_option('token', null);
		$this->update_options('token');
		$this->set_guest();
	}
}
?>