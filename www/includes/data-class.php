<?php
class rad_data{
	private $data;
	
	//при созданиии класса получает все параметры
	function __construct(){
		if(DATA_IN_DB){
			$this->data = array();
		}else{
			$this->data = null;
		}
		$this->load_all();
	}
	
	//загрузка всех параметров
	function load_all(){
		if(DATA_IN_DB){
			$this->load_from_db();
		}else{
			$this->load_from_file();
		}
	}
	
	//получение значения одного параметра по имени
	function get($key){
		if(DATA_IN_DB){
			return $this->get_from_db($key);
		}else{
			return $this->get_from_file($key);
		}
	}
	
	//получение значений параметров по именам
	//можно вызывать 2 способами
	//get_array('param1', 'param2')
	//get_array(array('param1', 'param2'))
	function get_array(){
		$args = func_get_args();
		if(!$args)
			return false;
		if(is_array($args[0])){
			$args = $args[0];
		}
		$ret = array();
		foreach($args as $key){
			$ret[$key] = $this->get($key);
		}
		return $ret;
	}
	
	//сохраняет значения указанных параметров в БД или в файл
	//можно вызывать 2 способами
	//update('param1', 'param2')
	//update(array('param1', 'param2'))
	function update(){
		$args = func_get_args();
		if(!$args)
			return;
		if(is_array($args[0])){
			$args = $args[0];
		}
		if(DATA_IN_DB){
			$this->update_from_db($args);
		}else{
			$this->update_from_file($args);
		}
	}
	
	//сохраняет значения всех параметров в БД или в файл
	function update_all(){
		if(DATA_IN_DB){
			$this->update_from_db_all();
		}else{
			$this->update_from_file_all();
		}
	}
	
	//установка значения параметра
	//key - ключ
	//value - новое значение
	function set($key, $value){
		if(is_null($this->data))
			$this->data = array();
		$this->data[$key] = $value;
	}
	
	//загрузка данных из файла data.php
	private function load_from_file(){
		if(file_exists(MAIN_DIR.'data.php')){
			$data = file(MAIN_DIR.'data.php');
			unset($data[0]);
			$this->data = @unserialize(implode('', $data));
		}else{
			$this->data = array();
		}
	}
	
	//загрузка данных из БД из таблицы parameters
	//если данная таблица отсутствует, то она создается
	private function load_from_db(){
		global $DB;
		
		$res = $DB->getOne('SHOW TABLES FROM ?n like ?s;', MAIN_DBNAME, 'parameters');
		if($res !== false){
			$this->data = $DB->getIndCol('key', 'SELECT `key`, `value` FROM `parameters`');
			foreach($this->data as $key => &$val){
				$tmp = @unserialize($this->data[$key]);
				$this->data[$key] = $tmp === false ? $this->data[$key] : $tmp;
			}
		}else{
			$res = $DB->query("CREATE TABLE `parameters` (
				`id` bigint(20) NOT NULL auto_increment,
				`key` tinytext  NOT NULL,
				`value` text    NOT NULL,
				PRIMARY KEY  (`id`)
			)"
			);
			$this->data = array();
		}
	}
	
	//получение параметра из ДБ
	//если парметр не загружен, то он будет получен из БД
	private function get_from_db($key){
		if(!$this->data || isset($this->data[$key])){
			global $DB;
			$res = $DB->getRow("SELECT `value` FROM `parameters` WHERE `key` = ?s", $key);
			if($res !== false){
				$tmp = @unserialize($res['value']);
				$this->data[$key] = $tmp === false ? $res['value'] : $tmp;
				return $this->data[$key];
			}else{
				return null;
			}
		}else{
			return isset($this->data[$key]) ? $this->data[$key] : null;
		}
	}
	
	//получение параметра из файла
	private function get_from_file($key){
		if(is_null($this->data)){
			$this->load_from_file();
		}
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}
	
	//обновление параметров в БД
	//keys - массив с ключами параметров которые нужно обновить
	private function update_from_db($keys){
		global $DB;
		$len = sizeof($keys);
		for($i=0; $i<$len; $i++){
			//если существует параметр с ключом из массива
			if(isset($this->data[$keys[$i]])){
				//то проверяется, есть ли запись в БД
				if($DB->getRow("SELECT `value` FROM `parameters` WHERE `key` = ?s", $keys[$i])){
					//если есть то значение обновляется
					$DB->query("UPDATE `parameters` SET `value` = ?s WHERE `key` = ?s", serialize($this->data[$keys[$i]]), $keys[$i]);
				}else{
					//иначе добавляется новый параметр
					$DB->query("INSERT INTO `parameters`(`key`, `value`) VALUES(?s, ?s)", $keys[$i], serialize($this->data[$keys[$i]]));
				}
			}else{
				//если не существует параметр с ключом из массива
				//то попытаемся удалить параметр в БД
				$DB->query("DELETE FROM `parameters` WHERE `key` = ?s", $keys[$i]);
			}
		}
	}
	
	//обновление параметров в файле
	//keys - массив с ключами параметров которые нужно обновить
	private function update_from_file($keys){
		$len = sizeof($keys);
		$data = array();
		if(file_exists(MAIN_DIR.'data.php')){
			$data = file(MAIN_DIR.'data.php');
			unset($data[0]);
			$data = @unserialize(implode('', $data));
		}
		for($i=0; $i<$len; $i++){
			//если существует параметр с ключом из массива
			if(isset($this->data[$keys[$i]])){
				//задаем значение параметра
				$data[$keys[$i]] = $this->data[$keys[$i]];
			}else{
				//иначе попытаемся удалить параметр в файле
				unset($data[$keys[$i]]);
			}
		}
		file_put_contents(MAIN_DIR.'data.php', '<?php header("Location: /");?>'.PHP_EOL . serialize($data));
	}
	
	//обнавляет все параметры в БД
	private function update_from_db_all(){
		global $DB;
		//очищаем таблицу
		$DB->query("TRUNCATE TABLE `parameters`");
		if(!$this->data)
			return;
		//и заполняем данные
		$query = "INSERT INTO `parameters`(`key`, `value`) VALUES";
		foreach($this->data as $key => $val){
			$query .= $DB->parse("(?s, ?s), ", $key, serialize($val));
		}
		$query = mb_substr($query, 0, -2);
		$DB->query($query);
	}
	
	//обнавляет все параметры в файле
	private function update_from_file_all(){
		if(!$this->data)
			$this->data = array();
		file_put_contents(MAIN_DIR.'data.php', '<?php header("Location: /");?>'.PHP_EOL . serialize($this->data));
	}
}

?>