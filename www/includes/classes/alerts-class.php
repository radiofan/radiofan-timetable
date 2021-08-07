<?php
//TODO переделать логику, добавить запоминание в БД

/**
 * Class rad_alerts
 */
class rad_alerts{
	//success
	//info
	//warning
	//danger
	
	/** @var array $alerts массив сообщений */
	private $alerts;
	
	function __construct(){
		$this->alerts = array();
	}
	
	/**
	 * Добавляет новое сообщение пользователю
	 * @param $html
	 * @param string $type - info, success, warning, danger
	 * @param bool $close - можно ли закрыть
	 * @return bool
	 */
	function add_alert($html, $type='info', $close=true){
		switch($type){
			case 'info':
			case 'success':
			case 'warning':
			case 'danger':
				break;
			default:
				return false;
		}
		
		$close = (bool) $close;
		
		$this->alerts[] = compact('html', 'type', 'close');
		
		return true;
	}
	
	/**
	 * возвращает количество сообщений
	 * @return int
	 */
	function get_count(){
		return sizeof($this->alerts);
	}
	
	/**
	 * возвращает массив сообщения по идентификатору
	 * @param $ind
	 * @return array|null
	 */
	function get_alert($ind){
		return isset($this->alerts[$ind]) ? $this->alerts[$ind] : null;
	}
	
	/**
	 * возвращает html сообщения по идентификатору
	 * @param $ind
	 * @return string|null
	 */
	function get_html_alert($ind){
		if(!isset($this->alerts[$ind]))
			return null;
		return '<div class="alert alert-'.$this->alerts[$ind]['type'].'" role="alert">'.
			($this->alerts[$ind]['close'] ? '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>' : '').
			$this->alerts[$ind]['html'].
			'</div>';
	}
	
	/**
	 * возвращает html следующего, в конце списка возвращается назад
	 * @return string|null
	 */
	function next_html_alert(){
		$ind = key($this->alerts);
		if(is_null($ind)){
			reset($this->alerts);
			return false;
		}
		next($this->alerts);
		return $this->get_html_alert($ind);
	}
}
?>