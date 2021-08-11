<?php

class rad_user_options{
	/** @var int $user_id - ID пользователя в БД */
	protected $user_id;
	/** @var array $options - массив параметров пользователя */
	protected $options;

	/**
	 * создает объект параметров юзера
	 * @param int $id - ID юзера, 0 для дефолтного юзера
	 */
	function __construct($id){
		$this->options = array();
		$this->user_id = absint($id);
	}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//Все что связано с параметрами
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Загружает параметры пользователя из БД
	 * @return bool - false если пользователь гость
	 */
	final function load_all_options(){
		if(!$this->user_id)
			return false;
		global $DB;
		$this->options = array();
		$tmp = $DB->getAll('SELECT `key`, `value` FROM `our_u_options` WHERE `user_id` = ?i', $this->user_id);
		$len = sizeof($tmp);
		for($i=0; $i<$len; $i++){
			$this->options[$tmp[$i]['key']] = array('isset' => true, 'val' => unserialize($tmp[$i]['value']));
		}
		return true;
	}

	/**
	 * Возвращает параметр юзера, ключ пропускается через option_name_clear()
	 * @see option_name_clear
	 * @param string $key - ключ параметра
	 * @return mixed|null - null в случае если параметр не существует
	 */
	final function get_option($key){
		$key = option_name_clear($key);
		if(isset($this->options[$key]))
			return $this->options[$key]['val'];
		global $DB;
		if((string)$key === '')
			return null;
		$tmp = $DB->getOne('SELECT `value` FROM `our_u_options` WHERE `user_id` = ?i AND `key` = ?s', $this->user_id, $key);
		if($tmp === false){
			$this->options[$key] = array('isset' => false, 'val' => null);
		}else{
			$this->options[$key] = array('isset' => true, 'val' => unserialize($tmp));
		}
		return $this->options[$key]['val'];
	}

	/**
	 * Устанавливает параметр для пользователя, ключ пропускается через option_name_clear()
	 * @see option_name_clear
	 * @param string $key - ключ параметра
	 * @param mixed $value - значение параметра, если null параметр удаляется
	 * @return bool - false если пользователь гость
	 */
	final function set_option($key, $value){
		if(!$this->user_id)
			return false;
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
	 * Обновляет указанные параметры пользователя в БД, ключи пропускаются через option_name_clear()
	 * @see option_name_clear
	 * @param string[]|string $keys - массив ключей
	 * @param string ...$params или ключи
	 */
	final function update_options(){
		//проверка на гостя
		if(!$this->user_id)
			return;
		//собираем массив ключей параметров
		if(func_num_args() == 0)
			return;
		/** @var  string[] $args - массив ключей параметров */
		$args = func_get_args();
		if(is_array($args[0])){
			$args = array_values($args[0]);
		}
		//обновляем параметры
		global $DB;
		$len = sizeof($args);
		//TODO замутить транзакцию
		for($i=0; $i<$len; $i++){
			$key = option_name_clear($args[$i]);
			if(isset($this->options[$key])){
				//если параметр установлен, но равен null
				if($this->options[$key]['isset'] && is_null($this->options[$key]['val'])){
					//то его нужно удалить
					$DB->query('DELETE FROM `our_u_options` WHERE `user_id` = ?i AND `key` = ?s', $this->user_id, $key);
					$this->options[$key]['isset'] = false;
					//если параметр установлен и имеет значение
				}else if($this->options[$key]['isset'] && !is_null($this->options[$key]['val'])){
					//то его нужно обновить
					$DB->query('UPDATE `our_u_options` SET `value` = ?s WHERE `user_id` = ?i AND `key` = ?s', serialize($this->options[$key]['val']), $this->user_id, $key);
					//если параметр не установлен, но имеет значение
				}else if(!$this->options[$key]['isset'] && !is_null($this->options[$key]['val'])){
					//то его нужно добавить
					$DB->query('INSERT INTO `our_u_options` (`user_id`, `key`, `value`) VALUES (?i, ?s, ?s)', $this->user_id, $key, serialize($this->options[$key]['val']));
					$this->options[$key]['isset'] = true;
				}
			}
		}
	}

	/**
	 * Обновляет значение всех параметров юзера в БД
	 * блокирует и разблокирует таблицу `our_u_options`
	 * //TODO опции не протестированы
	 */
	final function update_all_options(){
		//проверка на гостя
		if(!$this->user_id)
			return;
		global $DB;
		$query = 'INSERT INTO `our_u_options` (`user_id`, `key`, `value`) VALUES ';
		foreach($this->options as $key => $val){
			if(is_null($this->options[$key]['val'])){
				unset($this->options[$key]);
				continue;
			}
			if(!$this->options[$key]['isset'] && !is_null($this->options[$key]['val'])){
				$this->options[$key]['isset'] = true;
			}
			$query .= $DB->parse('(?i, ?s, ?s),',  $this->user_id, $key, serialize($val));
		}
		$DB->query('LOCK TABLES `our_u_options` WRITE');
		$DB->query('DELETE FROM `our_u_options` WHERE `user_id` = ?i', $this->user_id);
		if(sizeof($this->options)){
			$DB->query(mb_substr($query, 0, mb_strlen($query)-1));
		}
		$DB->query('UNLOCK TABLES');
	}
}

?>