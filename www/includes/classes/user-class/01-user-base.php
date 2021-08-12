<?php
if(!defined('MAIN_DIR'))
	die();

require_once MAIN_DIR.'includes/classes/user-class/01-1-user-roles.php';
require_once MAIN_DIR.'includes/classes/user-class/01-2-user-options.php';

/**
 * @property-read int $user_level @see rad_user_roles::get_user_level()
 * @property-read rad_user_roles $roles - объект отвечающий за права
 * @property-read rad_user_options $options - объект отвечающий за параметры
 */
abstract class rad_user_base{
	/**  @var int $id - ID пользователя в БД */
	protected $id;
	/** @var string $login - логин юзера в БД */
	protected $login;
	/** @var string $pass_hash - хэш пароля юзера в БД */
	protected $pass_hash;
	/** @var DateTime $date - дата регистрации юзера в БД */
	protected $date;
	/** @var string $email - почта пользоватля */
	protected $email;
	/** @var rad_user_roles $roles - объект отвечающий за права */
	protected $roles;
	/** @var rad_user_options $options - объект отвечающий за параметры */
	protected $options;
	

	/**
	 * создает объект юзера
	 * если ползователь не найден
	 * то становится по умолчанию @see $this::set_default
	 * @param int $id - ID юзера, 0 для дефолтного юзера
	 */
	function __construct($id){
		$id = absint($id);
		if(!empty($id)){
			if($this->load_user($id))
				return;
		}
		$this->set_default();
	}
	
	/**
	 * Устанавливает стандартные данные для пользователя
	 */
	function set_default(){
		$this->id = 0;
		$this->login = '';
		$this->pass_hash = '';
		$this->email = '';
		$this->date = new DateTime();
		$this->roles = new rad_user_roles(0);
		$this->options = new rad_user_options(0);
	}
	
	/**
	 * Загружает данные пользователя из БД
	 * @param int $id - ID пользователя
	 * @return bool - false - пользователь не найден
	 */
	function load_user($id){
		global $DB;
		$id = absint($id);
		$tmp = $DB->getRow('SELECT `login`, `password`, `email`, `date` FROM `our_u_users` WHERE `id` = ?i', $id);
		if(empty($tmp)){
			return false;
		}
		$this->id = $id;
		$this->login = $tmp['login'];
		$this->pass_hash = $tmp['password'];
		$this->email = $tmp['email'];
		$this->date = DateTime::createFromFormat(DB_DATE_FORMAT, $tmp['date']);
		try{
			$this->roles = new rad_user_roles($this->id);
		}catch(Exception $e){
			return false;
		}
		$this->options = new rad_user_options($this->id);
		return true;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//Геттеры/сеттеры
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * вернет ID юзера
	 * @return int
	 */
	final public function get_id(){
		return $this->id;
	}

	/**
	 * вернет логин юзера
	 * @return string
	 */
	final public function get_login(){
		return $this->login;
	}

	/**
	 * вернет почту юзера
	 * @return string
	 */
	final public function get_email(){
		return $this->email;
	}

	/**
	 * устанавливает почту юзера
	 * проверка на пустоты и на дефолтного юзера
	 * @param $email
	 * @return bool
	 */
	final public function set_email($email){
		if($this->id == 0)
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
	 * вернет дату регистрации юзера
	 * @return DateTime
	 */
	final public function get_date(){
		return (clone $this->date);
	}

	/**
	 * вернет уровень прав юзера
	 * @return int
	 */
	public function get_user_level(){
		return $this->roles->get_user_level();
	}
	
	function __get($prop){
		switch($prop){
			case 'user_level':
				return $this->roles->get_user_level();
			case 'roles':
				return $this->roles;
			case 'options':
				return $this->options;
			default:
				return null;
				//throw new Exception('undefined property');
		}
	}
}
?>