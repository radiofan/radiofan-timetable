<?php

class rad_user_roles{
	/** @var int $user_id - ID пользователя в БД */
	protected $user_id;
	/** @var int $user_level - уровень прав пользователя */
	protected $user_level;
	/** @var array $roles - массив id прав пользователя */
	protected $roles;
	/** @var array $all_roles - массив всех возможных прав пользователя*/
	static protected $all_roles = null;

	/** @var int Гость, не залогинен */
	const GUEST = 0;
	/** @var int юзер, мин права*/
	const USER = 1;
	/** @var int юзер, проверена почта*/
	const VERIFIED = 5;
	/** @var int юзер, макс права*/
	const SUPERUSER = 45;
	/** @var int админ, мин права*/
	const NEDOADMIN = 50;
	/** @var int админ, макс права*/
	const ADMIN  = 100;

	/**
	 * создает объект прав пользователя
	 * @param int $id - ID юзера, 0 для дефолтного юзера
	 * @throws Exception - user #{id} not found
	 */
	function __construct($id){
		self::load_all_roles();
		$this->user_id = absint($id);
		if(empty($this->user_id)){
			$this->user_level = $this::GUEST;
			$this->roles = array();
			return;
		}

		global $DB;
		$tmp = $DB->getOne('SELECT `level` FROM `our_u_users` WHERE `id` = ?i', $this->user_id);
		if($tmp === false || is_null($tmp)){
			throw new Exception('user #'.$this->user_id.' not found');
		}
		$this->user_level = absint($tmp);
		$this->load_roles();
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//Все что связано с правами
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//TODO права не протестированы
	/**
	 * загружает массив id, перманентных дополнительных прав пользователя
	 */
	final private function load_roles(){
		//проверка на гостя
		if(!$this->user_id){
			$this->roles = array();
			return;
		}
		global $DB;
		//загружаем перманентные права, work_time == 'inf'
		$this->roles = $DB->getCol('SELECT `role_id` FROM `our_u_users_roles` WHERE `user_id` = ?i AND `work_time` = \'INF\'', $this->user_id);
	}

	/**
	 * загружает данные о возможных правах
	 */
	static final public function load_all_roles(){
		global $DB;
		if(is_null(self::$all_roles))
			self::$all_roles = $DB->getAll('SELECT * FROM `our_u_roles`');
	}

	/**
	 * возвращает границу права пользователя, если передана одна строка с ключом права; массив границ прав, если передано несколько ключей; или все границы, если не переданы параметры
	 * @param string $type - тип ключа 'id' или 'role'
	 * @param string[] $add_columns - дополнительные колонки ('id', 'role', 'description', 'level')
	 * @param string[]|string $keys - массив ключей
	 * @param string ...$_ или ключи
	 * @return array|int|null
	 * права
	 * view_debug_info
	 * edit_users
	 * edit_settings
	 * ignore_max_token_remember
	 * view_db_stat
	 */
	static final public function get_roles_range(){
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
	final function add_role($role){
		//проверка на гостя
		if(!$this->user_id)
			return false;
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
			$query .= $DB->parse(' (?i, ?i)', $this->user_id, $add[$i]);
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
	final function add_temp_role($role, $work_time, $work_start='now', $act_id=null){
		//проверка на гостя
		if(!$this->user_id)
			return false;
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
			$work_start = 'MY_NOW()';
		}else{
			$work_start = '\''.$work_start->format(DB_DATETIME_FORMAT).'\'';
		}
		$len = sizeof($add);
		global $DB;
		$query = 'INSERT INTO `our_u_users_roles` (`user_id`, `role_id`, `start_time`, `end_time`, `work_time`, `action_id`) VALUES';
		for($i=0; $i<$len; $i++){
			$query .= $DB->parse(
				' (?i, ?i, ?p, ?p + INTERVAL ?p, ?s, ?i)',
				$this->user_id,
				$add[$i],
				$work_start,
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
	final function remove_role($role, $type='inf'){
		//проверка на гостя
		if(!$this->user_id)
			return false;
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
			$DB->query('DELETE FROM `our_u_users_roles` WHERE `user_id` = ?i AND `role_id` IN(?a)?p', $this->user_id, $tmp, $query);
			return true;
		}
	}

	/**
	 * Удаление всех дополнительных прав пользователя
	 * @param string $type - 'inf' - постоянные права, 'temp' - временные, 'all' - все
	 */
	final function remove_all_roles($type='inf'){
		//проверка на гостя
		if(!$this->user_id)
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
		$this->roles = array();
		$DB->query('DELETE FROM `our_u_users_roles` WHERE `user_id` = ?i?p', $this->user_id, $query);
		return true;
	}

	static final function get_levels_range(){
		return array(
			self::GUEST => 'GUEST (гость)',
			self::USER => 'USER (пользователь без прав)',
			self::VERIFIED => 'VERIFIED (пользователь с подтвержденной почтой)',
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
	final function can_user($role){
		$role_range = $this->get_roles_range('role', array('id', 'level'), $role);
		//если передано несуществующее право - false
		if(is_null($role_range))
			return false;
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
					AND `end_time` >= MY_NOW()
					AND `start_time` <= MY_NOW()
					AND `work_time` != \'INF\'
					AND `role_id` IN(?a)',
				$this->user_id,
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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//Геттеры/сеттеры
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * вернет уровень прав юзера
	 * @return int
	 */
	final public function get_user_level(){
		return $this->user_level;
	}

	/**
	 * @param $level
	 * @return bool
	 */
	final public function set_user_level($level){
		if(!$this->user_id)
			return false;
		$level = absint($level);
		if($this->user_level == $this::GUEST)
			return false;
		if($level < $this::USER)
			$level = $this::USER;
		$this->user_level = $level;
		global $DB;
		return (bool)$DB->query('UPDATE `our_u_users` SET `level` = ?i WHERE `id` = ?i', $this->user_level, $this->user_id);
	}
}
?>