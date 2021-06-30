<?php
class rad_cookie{
	/** @var array $validated - массив методов которые выполнены, хранит доп. данные оставшиеся после обработки*/
	private $validated;
	
	function __construct(){
		$validated = array();
	}
	
	/**
	 * вернет массив элементов (elements), у каждого элемента имеется 'gr_name' - имя столбца по умолчанию, если не задано; 'html_type' - Тип столбца для фронта
	 * Эти данные не пишутся в куки
	 * @param bool $is_alerts
	 * @return array
	 */
	public function timetable_validation($is_alerts = false){
		if(isset($this->validated['timetable_validation']))
			return $this->validated['timetable_validation'];
		
		$this->validated['timetable_validation'] = array();
		$this->timetable_elements_v($is_alerts);
		$this->timetable_options_v($is_alerts);
		
		
		return $this->validated['timetable_validation'];
	}
	
	private function timetable_elements_v($is_alerts){
		global $ALERTS, $DB;
		$this->validated['timetable_validation']['elements'] = array();
		
		$elems = isset($_COOKIE['timetable']['elements']) ? array_values($_COOKIE['timetable']['elements']) : array();
		$elems_len = sizeof($elems);
		if($elems_len > MAX_ELEMENTS_TIMETABLE){
			if($is_alerts)
				$ALERTS->add_alert('Вы превысили лимит разделов (max '.MAX_ELEMENTS_TIMETABLE.')', 'warning', 1);
			@setcookie('timetable[elements]', array(), time()+86400*30);
			$elems = array();
			$elems_len = 0;
		}
		
		$uniq = array();
		
		for($i=0; $i<$elems_len; $i++){
			if(!isset($elems[$i]['type'], $elems[$i]['id'])){
				unset($elems[$i]);
				continue;
			}
			$elems[$i] = array_intersect_key($elems[$i], array(
				'type'      => 0,
				'id'        => 0,
				'gr_name'   => 0
			));
			$table_name = '';
			$elems[$i]['type'] = mb_strtolower(trim($elems[$i]['type']));
			switch($elems[$i]['type']){
				case 'cabinet':
				case 'group':
				case 'teacher':
					$table_name = 'stud_'.$elems[$i]['type'].'s';
					break;
				default:
					$elems[$i]['type'] = false;
			}
			if(!$elems[$i]['type']){
				unset($elems[$i]);
				continue;
			}
			
			$elems[$i]['id'] = preg_replace('#[^0-9]#u', '', $elems[$i]['id']);
			if(isset($uniq[$elems[$i]['type'].$elems[$i]['id']])){
				unset($elems[$i]);
				continue;
			}
			
			$dat = '';
			//TODO проверить id группы
			if((int) $elems[$i]['id'] != 0 && !($dat = $DB->getRow('SELECT * FROM ?n WHERE `id` = ?s', $table_name, $elems[$i]['id']))){
				unset($elems[$i]);
			}
			
			$tmp = '';
			switch($elems[$i]['type']){
				case 'cabinet':
					$tmp = $elems[$i]['id'] ? $dat['cabinet'].$dat['cabinet_additive'].' '.$dat['building'] : 'Без кабинета';
					$this->validated['timetable_validation']['elements'][$i] = array('html_type' => 'Кабинет: '.$tmp);
					
					break;
				case 'group':
					$tmp = $dat['name'];
					$this->validated['timetable_validation']['elements'][$i] = array('html_type' => 'Группа: '.$tmp);
					break;
				case 'teacher':
					$tmp = $elems[$i]['id'] ? mb_convert_case($dat['fio'], MB_CASE_TITLE) : 'Без учителя';
					$this->validated['timetable_validation']['elements'][$i] = array('html_type' => 'Учитель: '.$tmp);
					break;
			}
			
			if(empty($elems[$i]['gr_name'])){
				$this->validated['timetable_validation']['elements'][$i]['gr_name'] = $tmp;
			}else{
				$elems[$i]['gr_name'] = mb_substr(trim($elems[$i]['gr_name']), 0, MAX_SECTION_NAME_LEN);
				$this->validated['timetable_validation']['elements'][$i]['gr_name'] = $elems[$i]['gr_name'];
			}
			
			$uniq[$elems[$i]['type'].$elems[$i]['id']] = false;
		}
		$this->validated['timetable_validation']['elements'] = array_values($this->validated['timetable_validation']['elements']);
		$elems = array_values($elems);
		self::delcookie_array(array('timetable', 'elements'));
		$_COOKIE['timetable']['elements'] = $elems;
		
		self::setcookie_array('timetable[elements]', $elems, time()+86400*30);
	}
	
	private function timetable_options_v($is_alerts){
		global $ALERTS;
		$options = isset($_COOKIE['timetable']['options']) ? $_COOKIE['timetable']['options'] : array();
		$options = array_intersect_key($options, array(
			'teacher_add_hide'  => 0,
			'cell_word_wrap'    => 0,
			'go2curr_day'       => 0,
			'cell_rowspan'      => 0,
			'size'              => 0
		));
		$size_style = isset($options['size']) && is_array($options['size']) ? $options['size'] : array();
		if(sizeof($size_style) > MAX_ELEMENTS_TIMETABLE*5 + 3){
			if($is_alerts)
				$ALERTS->add_alert('Вы превысили лимит размеров', 'warning', 1);
			$size_style = array();
		}
		$options['size'] = $size_style;
		
		if(isset($options['teacher_add_hide']) && $options['teacher_add_hide']){
			$options['teacher_add_hide'] = 1;
		}else{
			$options['teacher_add_hide'] = 0;
		}
		
		if(isset($options['cell_word_wrap']) && !$options['cell_word_wrap']){
			$options['cell_word_wrap'] = 0;
		}else{
			$options['cell_word_wrap'] = 1;
		}
		
		if(isset($options['go2curr_day'])){
			switch($options['go2curr_day']){
				case 1://week
				case 2://day
					$options['go2curr_day'] = (int)$options['go2curr_day'];
					break;
				default:
					$options['go2curr_day'] = 0;//disable
					break;
			}
		}else{
			$options['go2curr_day'] = 0;
		}
		
		if(isset($options['cell_rowspan']) && !$options['cell_rowspan']){
			$options['cell_rowspan'] = 0;
		}else{
			$options['cell_rowspan'] = 1;
		}
		
		self::delcookie_array(array('timetable', 'options'));
		$_COOKIE['timetable']['options'] = $options;
		self::setcookie_array('timetable[options]', $options, time()+86400*30);
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
			@setcookie($cookie_name, null, time()-86400);
		}else{
			foreach($dat as $key => &$val){
				self::delcookie_array_recursive($cookie_name.'['.urlencode($key).']', $val);
			}
		}
	
	}
	
}

?>