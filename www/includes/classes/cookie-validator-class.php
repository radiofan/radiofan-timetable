<?php

/**
 * Class rad_cookie - производит валидацию куки
 * проверяет их на правильност, заполняет их дефолтными значениями
 * @property-read array timetable_parts
 * @see rad_cookie::get_timetable_parts
 * @property-read array timetable_cols_size
 * @see rad_cookie::get_timetable_cols_size
 * @property-read array timetable_options
 * @see rad_cookie::get_timetable_options
 */
class rad_cookie{
	/** @var array $validated - массив методов которые выполнены, хранит доп. данные оставшиеся после обработки*/
	private $validated;
	
	function __construct(){
		$validated = array();
	}
	
	function __get($opt){
		$this->validate($opt);
		return $this->validated[$opt];
	}
	
	function validate($opt){
		if(!isset($this->validated[$opt])){
			$method = 'get_'.$opt;
			if(method_exists($this, $method)){
				$this->$method();
			}else{
				throw new Exception('undefined validate property - '.$opt);
			}
		}
	}

	/**
	 * производит валидацию куки tmt['p'] - список разделов
	 * в validated['timetable_parts'] помещает массив массивов
	 * ['html_type','type','id','cols_order','part_name']
	 * html_type - строка стандарного названия раздела с указанием его типа;
	 * type - 'group', 'teacher', 'cabinet';
	 * id - строка, id соответствующего раздела;
	 * cols_order - массив возвращаемый this::get_cols_order_from_str;
	 * part_name - строка, название раздела пользователя или стандартное, max длина - MAX_PART_NAME_LEN;
	 * @see rad_cookie::extract_cols_order_from_str
	 * @see MAX_PART_NAME_LEN
	 */
	private function get_timetable_parts(){
		global $DB;
		$this->validated['timetable_parts'] = array();
		
		$parts = array();
		if(isset($_COOKIE['tmt']) && is_array($_COOKIE['tmt']) && isset($_COOKIE['tmt']['p']) && is_array($_COOKIE['tmt']['p'])){
			$parts = array_values($_COOKIE['tmt']['p']);
			self::delcookie_array(array('tmt', 'p'));
			unset($_COOKIE['tmt']['p']);
		}
		
		$parts_len = sizeof($parts);
		if($parts_len > MAX_PARTS_TIMETABLE){
			$parts = array_slice($parts, 0, MAX_PARTS_TIMETABLE);
			$parts_len = MAX_PARTS_TIMETABLE;
		}
		
		$uniq = array();
		
		for($i=0; $i<$parts_len; $i++){
			if(!isset($parts[$i]['i'], $parts[$i]['t'])){
				unset($parts[$i]);
				continue;
			}
			$parts[$i] = array_intersect_key($parts[$i], array(
				'i' => false,
				't' => false,
				'n' => false,
				'o' => false
			));
			if(empty($parts[$i]['i']))
				$parts[$i]['i'] = 0;
			if(empty($parts[$i]['t']))
				$parts[$i]['t'] = 0;
			if(empty($parts[$i]['o']))
				$parts[$i]['o'] = 'LTGCP';
			
			//проверка типа раздела
			$table_name = '';
			switch($parts[$i]['t']){
				case 0://группа
					$table_name = 'stud_groups';
					break;
				case 1://препод
					$table_name = 'stud_teachers';
					break;
				case 2://кабинет
					$table_name = 'stud_cabinets';
					break;
				default:
					$parts[$i]['t'] = false;
			}
			if($parts[$i]['t'] === false){
				unset($parts[$i]);
				continue;
			}
			//получение id
			$parts[$i]['i'] = int_clear($parts[$i]['i']);
			//проверка на уникальность раздела
			if(isset($uniq[$parts[$i]['t'].'-'.$parts[$i]['i']])){
				unset($parts[$i]);
				continue;
			}

			//проверяем существование id и загружаем данные о разделе
			$dat = '';
			if($parts[$i]['i'] && !($dat = $DB->getRow('SELECT * FROM ?n WHERE `id` = ?s', $table_name, $parts[$i]['i']))){
				unset($parts[$i]);
				continue;
			}

			//установка htmlтипа раздела
			$tmp = '';
			switch($parts[$i]['t']){
				case 0://группа
					$tmp = $dat['name'];
					$this->validated['timetable_parts'][$i] = array('html_type' => 'Группа: '.$tmp, 'type' => 'group');
					break;
				case 1://препод
					$tmp = $parts[$i]['i'] ? mb_convert_case($dat['fio'], MB_CASE_TITLE) : 'Без препода';
					$this->validated['timetable_parts'][$i] = array('html_type' => 'Препод: '.$tmp, 'type' => 'teacher');
					break;
				case 2://кабинет
					$tmp = $parts[$i]['i'] ? $dat['cabinet'].$dat['additive'].' '.$dat['building'] : 'Без кабинета';
					$this->validated['timetable_parts'][$i] = array('html_type' => 'Кабинет: '.$tmp, 'type' => 'cabinet');

					break;
			}
			//установка id
			$this->validated['timetable_parts'][$i]['id'] = $parts[$i]['i'];

			//установка порядка столбцов в разделе
			$this->validated['timetable_parts'][$i]['cols_order'] = $this->extract_cols_order_from_str($parts[$i]['o']);
			$parts[$i]['o'] = '';
			foreach($this->validated['timetable_parts'][$i]['cols_order'] as $col => $view){
				$parts[$i]['o'] .= $view ? mb_strtoupper($col) : $col;
			}

			//установка названия раздела
			if(empty($parts[$i]['n'])){
				$this->validated['timetable_parts'][$i]['part_name'] = mb_substr($tmp, 0, MAX_PART_NAME_LEN);
			}else{
				$parts[$i]['n'] = mb_substr(trim($parts[$i]['n']), 0, MAX_PART_NAME_LEN);
				$this->validated['timetable_parts'][$i]['part_name'] = $parts[$i]['n'];
			}
			
			//запись в юник
			$uniq[$parts[$i]['t'].'-'.$parts[$i]['i']] = false;
		}
		
		
		$this->validated['timetable_parts'] = array_values($this->validated['timetable_parts']);
		$parts = array_values($parts);
		
		//обновим куки если они имеются
		if(sizeof($parts)){
			if(!is_array($_COOKIE['tmt']))
				$_COOKIE['tmt'] = array();
			$_COOKIE['tmt']['p'] = $parts;

			self::setcookie_array('tmt[p]', $parts, time()+TIMETABLE_PARTS_LIVE_DAYS * SECONDS_PER_DAY);
		}
	}

	/**
	 * преобразует строку порядка и видимости столбцов в массив
	 * производит валидацию
	 * строка должна быть длиной 5 символов ltgcp
	 * большой символ - столбец включен, маленький - выключен
	 * @param $str_order
	 * @return array ['l' => bool, 't' => bool, 'g' => bool, 'c' => bool, 'p' => bool], порядок зависит от входной строки
	 */
	function extract_cols_order_from_str($str_order){
		$str_order = preg_replace('#[^ltgcp]#iu', '', mb_substr($str_order, 0, 5));
		$tmp = preg_split('##u', $str_order, 0, PREG_SPLIT_NO_EMPTY);
		$ret = array();
		for($i=0; $i<sizeof($tmp); $i++){
			$ret[mb_strtolower($tmp[$i])] = mb_strtolower($tmp[$i]) != $tmp[$i];
		}
		if(sizeof($ret) != 5){
			return array_merge($ret, array_diff_key(array('l' => true, 't' => true, 'g' => true, 'c' => true, 'p' => true), $ret));
		}else{
			if(array_sum($ret) < 1)
				$ret['l'] = true;
		}
		
		return $ret;
	}

	/**
	 * производит валидацию куки tmt['s'] - размеры столбцов
	 * в validated['timetable_cols_size'] помещает массив
	 * ['number', 'time', 'parts']
	 * number - null|int - размер столбца с номером пары;
	 * time - null|int - размер столбца с временем пары;
	 * parts - null|array - массив размеров столбцов разделов, ключи не по порядку
	 * ['l' => null|int,'t' => null|int,'g' => null|int,'c' => null|int,'p' => null|int]
	 * @see rad_cookie::clear_timetable_part_cols_size
	 */
	private function get_timetable_cols_size(){
		$this->validate('timetable_parts');
		$this->validated['timetable_cols_size'] = array();
		
		$sizes = array();
		if(isset($_COOKIE['tmt']) && is_array($_COOKIE['tmt']) && isset($_COOKIE['tmt']['s']) && is_array($_COOKIE['tmt']['s'])){
			$sizes = array_intersect_key($_COOKIE['tmt']['s'], array('nsz' => false, 'tsz' => false, 'p' => false));
			self::delcookie_array(array('tmt', 's'));
			unset($_COOKIE['tmt']['s']);
		}
		//размер колонки - номер пары
		if(isset($sizes['nsz'])){
			$sizes['nsz'] = absint($sizes['nsz']);
			if($sizes['nsz'] <= 0){
				unset($sizes['nsz']);
			}else{
				$this->validated['timetable_cols_size']['number'] = $sizes['nsz'];
			}
		}
		//размер колонки - время пары
		if(isset($sizes['tsz'])){
			$sizes['tsz'] = absint($sizes['tsz']);
			if($sizes['tsz'] <= 0){
				unset($sizes['tsz']);
			}else{
				$this->validated['timetable_cols_size']['time'] = $sizes['tsz'];
			}
		}
		//размер колонок разделов
		if(isset($sizes['p'])){
			$sizes['p'] = $this->clear_timetable_part_cols_size($sizes['p']);
			if($sizes['p']){
				$this->validated['timetable_cols_size']['parts'] = $sizes['p'];
			}else{
				unset($sizes['p']);
			}
		}
		//обновим куки если они имеются
		if(sizeof($sizes)){
			if(!is_array($_COOKIE['tmt']))
				$_COOKIE['tmt'] = array();
			$_COOKIE['tmt']['s'] = $sizes;

			self::setcookie_array('tmt[s]', $sizes, time()+TIMETABLE_PARTS_LIVE_DAYS * SECONDS_PER_DAY);
		}
	}

	/**
	 * очищает и прверяет размеры столбцов разделов
	 * @param $cols - массив массивов
	 * ['l' => null|int,'t' => null|int,'g' => null|int,'c' => null|int,'p' => null|int]
	 * @return array|false
	 * TODO оставить размеры для других столбцов
	 */
	private function clear_timetable_part_cols_size($cols){
		if(!is_array($cols))
			return false;
		$parts_len = sizeof($this->validated['timetable_parts']);
		$cols = array_slice($cols, 0, MAX_PARTS_TIMETABLE, 1);
		$ret = array();
		foreach($cols as $key => &$val){
			$col_n = absint($key);
			if($col_n != $key || $col_n >= $parts_len || !is_array($val)){
				unset($cols[$key]);
				continue;
			}
			$ret[$col_n] = array_intersect_key($val, array(
				'l' => false,
				't' => false,
				'g' => false,
				'c' => false,
				'p' => false
			));
			foreach($ret[$col_n] as $col_ind => &$col_size){
				$col_size = absint($col_size);
				if($col_size <= 0){
					unset($ret[$col_n][$col_ind]);
				}
			}
			if(!sizeof($ret[$col_n]))
				unset($ret[$col_n]);
		}
		if(sizeof($ret)){
			return $ret;
		}else{
			return false;
		}
	}

	/**
	 * производит валидацию куки tmt['o'] - опции
	 * в validated['timetable_options'] помещает массив
	 * ['teacher_add_hide','cell_word_wrap','go2curr_day','lesson_unite']
	 * teacher_add_hide - bool - скрывать звание препода;
	 * cell_word_wrap - bool - перенос слов;
	 * go2curr_day - string - идти к текущему ('':нет, 'week':неделе, 'day':дню);
	 * lesson_unite - bool - объединять уроки;
	 */
	private function get_timetable_options(){
		$this->validated['timetable_options'] = array();

		$opts = array();
		if(isset($_COOKIE['tmt']) && is_array($_COOKIE['tmt']) && isset($_COOKIE['tmt']['o']) && is_array($_COOKIE['tmt']['o'])){
			$opts = array_intersect_key($_COOKIE['tmt']['o'], array(
				'tah' => 0,
				'wwr' => 0,
				'gcd' => 0,
				'lun' => 0
			));
			self::delcookie_array(array('tmt', 'o'));
			unset($_COOKIE['tmt']['o']);
		}
		
		//скрывать звание препода
		if(isset($opts['tah']) && $opts['tah']){
			$opts['tah'] = 1;
			$this->validated['timetable_options']['teacher_add_hide'] = 1;
		}else{
			$this->validated['timetable_options']['teacher_add_hide'] = 0;
		}
		
		//перенос слов
		if(isset($opts['wwr']) && !$opts['wwr']){
			$opts['wwr'] = 0;
			$this->validated['timetable_options']['cell_word_wrap'] = 0;
		}else{
			$this->validated['timetable_options']['cell_word_wrap'] = 1;
		}
		
		//идти к текущему (0:нет, 1:неделе, 2:дню)
		if(isset($opts['gcd'])){
			switch($opts['gcd']){
				case 1://week
					$opts['gcd'] = 1;
					$this->validated['timetable_options']['go2curr_day'] = 'week';
					break;
				case 2://day
					$opts['gcd'] = 2;
					$this->validated['timetable_options']['go2curr_day'] = 'day';
					break;
				default:
					unset($opts['gcd']);
					$this->validated['timetable_options']['go2curr_day'] = '';//disable
					break;
			}
		}else{
			$this->validated['timetable_options']['go2curr_day'] = '';
		}
		
		//объединять уроки
		if(isset($opts['lun']) && !$opts['lun']){
			$opts['lun'] = 0;
			$this->validated['timetable_options']['lesson_unite'] = 0;
		}else{
			$this->validated['timetable_options']['lesson_unite'] = 1;
		}

		//обновим куки если они имеются
		if(sizeof($opts)){
			if(!is_array($_COOKIE['tmt']))
				$_COOKIE['tmt'] = array();
			$_COOKIE['tmt']['o'] = $opts;

			self::setcookie_array('tmt[o]', $opts, time()+TIMETABLE_PARTS_LIVE_DAYS * SECONDS_PER_DAY);
		}
	}
	
	static function setcookie_array($name, $value = array(), $expires = 0, $path = '', $domain = '', $secure = false, $httponly = false){
		if(!is_array($value)){
			@setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
		}else{
			foreach($value as $key => &$val){
				self::setcookie_array($name.'['.urlencode($key).']', $val, $expires, $path, $domain, $secure, $httponly);
			}
		}
	}
	
	static function delcookie_array($keys = array()){
		$len = sizeof($keys);
		if($len < 2)
			return false;
		$arr = $_COOKIE;
		for($i=0; $i<$len; $i++){
			if(!isset($arr[$keys[$i]]))
				return false;
			$arr = $arr[$keys[$i]];
		}
		self::delcookie_array_recursive($keys[0].'['.implode('][', array_map('urlencode', array_slice($keys, 1))).']', $arr);
		return true;
	}
	
	static private function delcookie_array_recursive($cookie_name, $dat){
		if(!is_array($dat)){
			@setcookie($cookie_name, null, time()-SECONDS_PER_DAY);
		}else{
			foreach($dat as $key => &$val){
				self::delcookie_array_recursive($cookie_name.'['.urlencode($key).']', $val);
			}
		}
	
	}
	
}

?>