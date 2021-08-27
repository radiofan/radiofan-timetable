<?php

class rad_page{
	/** @var int тип страницы - страница */
	const TYPE_PAGE = 0;
	/** @var int тип страницы - административная */
	const TYPE_ADMIN = 1;
	/** @var int тип страницы - системная */
	const TYPE_SYSTEM = 2;
	
	/** @var string $title - заголовок страницы */
	public $title = '';
	/** @var int $user_level - минимальный уровень юзера для доступа к странице */
	public $user_level = rad_user_roles::GUEST;
	/** @var array $need_roles - массив необходимых прав для доступа к странице */
	public $need_roles;
	/** @var int $type - тип страницы */
	public $type = self::TYPE_PAGE;
	/** @var array $scripts - массив путей подключения доп скриптов из папки scripts, при загрузке добавляется ?ver=filemtime() */
	public $scripts;
	/** @var array $scripts - массив путей подключения доп библиотек из папки libs, при загрузке добавляется */
	public $libs;
	/** @var array $styles - массив путей подключения доп стилей из папки styles, при загрузке добавляется ?ver=filemtime() */
	public $styles;
	/** @var string $file_name - название php файла, в котором имеется функция вывода страницы */
	public $file_name = '';
	
	function __construct(){
		$this->need_roles = array();
		$this->scripts = array();
		$this->libs = array();
		$this->styles = array();
	}
	
	function add_scripts(){
		if(func_num_args() == 0)
			return 0;
		/** @var  string[] $args - массив ключей параметров */
		$args = func_get_args();
		if(is_array($args[0])){
			$args = array_values($args[0]);
		}
		$ret = 0;
		$len = sizeof($args);
		for($i=0; $i<$len; $i++){
			$args[$i] = trim($args[$i]);
			if(in_array($args[$i], $this->scripts))
				continue;
			$path = MAIN_DIR.'scripts/'.$args[$i];
			if(!check_file($path)){
				trigger_error('script not find, path:'.$path, E_USER_WARNING);
			}
			$this->scripts[] = $args[$i];
			$ret++;
		}
		return $ret;
	}

	function add_styles(){
		if(func_num_args() == 0)
			return 0;
		/** @var  string[] $args - массив ключей параметров */
		$args = func_get_args();
		if(is_array($args[0])){
			$args = array_values($args[0]);
		}
		$ret = 0;
		$len = sizeof($args);
		for($i=0; $i<$len; $i++){
			$args[$i] = trim($args[$i]);
			if(in_array($args[$i], $this->styles))
				continue;
			$path = MAIN_DIR.'styles/'.$args[$i];
			if(!check_file($path)){
				trigger_error('style not find, path:'.$path, E_USER_WARNING);
			}
			$this->styles[] = $args[$i];
			$ret++;
		}
		return $ret;
	}

	function add_libs(){
		if(func_num_args() == 0)
			return 0;
		/** @var  string[] $args - массив ключей параметров */
		$args = func_get_args();
		if(is_array($args[0])){
			$args = array_values($args[0]);
		}
		$ret = 0;
		$len = sizeof($args);
		for($i=0; $i<$len; $i++){
			$args[$i] = trim($args[$i]);
			if(in_array($args[$i], $this->libs))
				continue;
			$path = MAIN_DIR.'libs/'.$args[$i];
			if(!check_file($path)){
				trigger_error('lib not find, path:'.$path, E_USER_WARNING);
			}
			$this->libs[] = $args[$i];
			$ret++;
		}
		return $ret;
	}

	function add_roles(){
		if(func_num_args() == 0)
			return 0;
		/** @var  string[] $args - массив ключей параметров */
		$args = func_get_args();
		if(is_array($args[0])){
			$args = array_values($args[0]);
		}
		$ret = 0;
		$len = sizeof($args);
		for($i=0; $i<$len; $i++){
			$args[$i] = trim($args[$i]);
			if(in_array($args[$i], $this->need_roles))
				continue;
			if(is_null(rad_user_roles::get_roles_range('role', 'level', $args[$i]))){
				trigger_error('undefined role:'.$args[$i], E_USER_WARNING);
			}
			$this->need_roles[] = $args[$i];
			$ret++;
		}
		return $ret;
	}
}

class rad_pages_viewer{
	/** @var rad_page[] $pages - массив данных страниц */
	private $pages;

	/** @var array $loaded_page_files - массив путей подключенных файлов для отображения страниц */
	private $loaded_page_files;
	
	/** @var string $current_page_id - ID текущй страницы, исп. как ключи в $pages */
	private $current_page_id = null;
	
	function __construct(){
		global $URL;
		$this->pages = array();
		$this->loaded_page_files = array();
		/////////////////////////////////////////////////////////////////////////////////////
		//технические страницы
		
		//главная страница
		$this->pages['main_page'] = new rad_page();
		$this->pages['main_page']->title = 'Главная';
		$this->pages['main_page']->type = rad_page::TYPE_SYSTEM;
		$this->pages['main_page']->file_name = 'index.php';
		$this->pages['main_page']->add_scripts('table_script.js');
		$this->pages['main_page']->add_libs('jquery-ui-1.12.1.sortable.min.js');
		$this->pages['main_page']->add_styles('table_style.css');
		
		//404
		$this->pages['404'] = new rad_page();
		$this->pages['404']->title = 'Ошибка 404';
		$this->pages['404']->type = rad_page::TYPE_SYSTEM;
		$this->pages['404']->file_name = 'error.php';
		
		/*
		//403
		$this->pages['403'] = new rad_page();
		$this->pages['403']->title = 'Доступ запрещен';
		$this->pages['403']->type = rad_page::TYPE_SYSTEM;
		$this->pages['403']->file_name = 'error.php';
		$URL->add_page('403', 'access-denied');
		*/
		
		//страница входа
		$this->pages['login'] = new rad_page();
		$this->pages['login']->title = 'Вход';
		$this->pages['login']->type = rad_page::TYPE_SYSTEM;
		$this->pages['login']->file_name = 'login.php';
		$this->pages['login']->add_scripts('login_script.js');
		$URL->add_page('login', 'login');
		$URL->set_parametres('login', 'type', 1);
		
		//страница верификации email/активации аккаунта
		$this->pages['activation'] = new rad_page();
		$this->pages['activation']->title = 'Активация';
		$this->pages['activation']->type = rad_page::TYPE_SYSTEM;
		$this->pages['activation']->file_name = 'email_verif_pass_rec.php';
		$URL->add_page('activation', 'activation');
		$URL->set_parametres('activation', 'token', 1);
		
		//страница смены пароля
		$this->pages['pass_recovery'] = new rad_page();
		$this->pages['pass_recovery']->title = 'Восстановление пароля';
		$this->pages['pass_recovery']->type = rad_page::TYPE_SYSTEM;
		$this->pages['pass_recovery']->file_name = 'email_verif_pass_rec.php';
		$URL->add_page('pass_recovery', 'recovery-password');
		$URL->set_parametres('pass_recovery', 'token', 1);
		/////////////////////////////////////////////////////////////////////////////////////
		
		//страницы администрирования
		
		//страница админа
		$this->pages['admin'] = new rad_page();
		$this->pages['admin']->title = 'Управление';
		$this->pages['admin']->type = rad_page::TYPE_SYSTEM;
		$this->pages['admin']->file_name = 'admin.php';
		$this->pages['admin']->user_level = rad_user_roles::NEDOADMIN;
		$URL->add_page('admin', 'adminka');
		
		//страница управления пользователями
		$this->pages['admin_edit_users'] = new rad_page();
		$this->pages['admin_edit_users']->title = 'Управление пользователями';
		$this->pages['admin_edit_users']->type = rad_page::TYPE_ADMIN;
		$this->pages['admin_edit_users']->file_name = 'admin_edit_users.php';
		$this->pages['admin_edit_users']->user_level = rad_user_roles::NEDOADMIN;
		$this->pages['admin_edit_users']->add_roles('edit_users');
		$URL->add_page('admin_edit_users', 'edit-users', 'admin');

		//страница управления настройками сайта
		$this->pages['admin_edit_settings'] = new rad_page();
		$this->pages['admin_edit_settings']->title = 'Управление сайтом';
		$this->pages['admin_edit_settings']->type = rad_page::TYPE_ADMIN;
		$this->pages['admin_edit_settings']->file_name = 'admin_settings.php';
		$this->pages['admin_edit_settings']->user_level = rad_user_roles::NEDOADMIN;
		$this->pages['admin_edit_settings']->add_roles('edit_settings');
		$URL->add_page('admin_edit_settings', 'edit-settings', 'admin');
		/////////////////////////////////////////////////////////////////////////////////////
		
		//страницы сайта
		
		//сттраница настроек пользователя
		$this->pages['user_settings'] = new rad_page();
		$this->pages['user_settings']->title = 'Настройки';
		$this->pages['user_settings']->type = rad_page::TYPE_PAGE;
		$this->pages['user_settings']->file_name = 'settings.php';
		$this->pages['user_settings']->user_level = rad_user_roles::USER;
		$URL->add_page('user_settings', 'settings');
	}

	/**
	 * обертка для rad_url::get_current_page
	 * получает id страницы которую юзер хочет получить
	 * @see rad_url::get_current_page
	 */
	function load_current_page(){
		global $URL;
		$this->current_page_id = $URL->get_current_page();
	}

	/**
	 * Подключает файл страницы, если он еще не был подключен
	 * файл располагаются по пути MAIN_DIR.'themes/'.$theme.'/'.$this->pages[$page_id]->file_name
	 * @param string $page_id
	 * @param string $theme
	 */
	private function load_page_files($page_id, $theme='default'){
		$key = $theme.'/'.$this->pages[$page_id]->file_name;
		if(!isset($this->loaded_page_files[$key])){
			require MAIN_DIR.'themes/'.$key;
			$this->loaded_page_files[$key] = true;
		}
	}

	/**
	 * производит проверку на разрешение отображение текущей страницы
	 * может производить редиректы
	 * отображать страницу 404
	 * в файле страницы может располагаться функция 'test_view_'.$page_id
	 * если она вернет false то текущая страница 404
	 * @param string $page_id
	 */
	private function choice_page($page_id){
		global $USER, $URL;
		
		/** @var rad_page $curr_p - данные текущей страницы */
		$curr_p = $this->pages[$page_id];
		
		//Если уровень страницы - админская, а юзер не админ
		//то эта страница 404
		if($USER->get_user_level() < rad_user_roles::NEDOADMIN && $curr_p->user_level >= rad_user_roles::NEDOADMIN){
			$URL->set_current_404();
			$this->current_page_id = '404';
		}
		//если гость пытается зайти на страницу, где нужно быть зарегистрированным 
		//отправляем его на страницу входа
		if($USER->get_user_level() == 0 && $curr_p->user_level > 0){
			redirect('/login');
		}

		//если юзер не имеет прав для просмотра данной страницы
		//то эта страница 404
		if(!can_user($curr_p->need_roles)){
			$URL->set_current_404();
			$this->current_page_id = '404';
		}

		$this->load_page_files($page_id);
		
		//Проверка на редиректы страницы
		if(function_exists('test_view_'.$page_id)){
			if(!call_user_func('test_view_'.$page_id)){
				$URL->set_current_404();
				$this->current_page_id = '404';
			}
		}
		
	}

	/**
	 * подготавливает данные для вывода в футере и хэдаре
	 * а именно скрипты либы стили и титл
	 * в файле страницы может располагаться функция 'footer_header_data_'.$page_id($data)
	 * которая должна возвращать обработанный массив с данными для футера и хэдара
	 * ['title','addition_scripts','addition_libs','addition_styles','tmp_styles','js_data']
	 * @param $page_id
	 * @return array
	 */
	private function prapare_footer_header_data($page_id){
		$ret = array();
		$ret['title'] = $this->pages[$page_id]->title;

		$ret['addition_scripts'] = array();
		$len = sizeof($this->pages[$page_id]->scripts);
		for($i=0; $i<$len; $i++){
			$tmp = $this->pages[$page_id]->scripts[$i];
			$ret['addition_scripts'][] = '<script src="/scripts/'.$tmp.'?ver='.filemtime(MAIN_DIR.'scripts/'.$tmp).'"></script>';
		}
		
		$ret['addition_libs'] = array();
		$len = sizeof($this->pages[$page_id]->libs);
		for($i=0; $i<$len; $i++){
			$tmp = $this->pages[$page_id]->libs[$i];
			$ret['addition_libs'][] = '<script src="/libs/'.$tmp.'?ver='.filemtime(MAIN_DIR. 'libs/'.$tmp).'"></script>';
		}
		
		$ret['addition_styles'] = array();
		$len = sizeof($this->pages[$page_id]->styles);
		for($i=0; $i<$len; $i++){
			$tmp = $this->pages[$page_id]->styles[$i];
			$ret['addition_styles'][] = '<link rel="stylesheet" href="/styles/'.$tmp.'?ver='.filemtime(MAIN_DIR.'styles/'.$tmp).'"/>';
		}
		
		$ret['tmp_styles'] = array();
		$ret['js_data'] = array(
			'ignoreForms' => '.login'
		);
		$this->load_page_files($page_id);

		//Проверка на редиректы страницы
		if(function_exists('footer_header_data_'.$page_id)){
			$ret = call_user_func('footer_header_data_'.$page_id, $ret);
		}
		
		return $ret;
	}
	
	/**
	 * Производит вывод текущей страницы
	 * подключает файлы для страницы
	 * и вызывает в ней функцию 'view_'.$this->current_page_id.'_page'($page_data)
	 */
	function view_current_page(){
		$this->load_current_page();
		if(!isset($this->pages[$this->current_page_id]))
			throw new Exception('page not load');
		$this->choice_page($this->current_page_id);
		
		$page_data = $this->prapare_footer_header_data($this->current_page_id);
		
		$theme = 'default';
		require MAIN_DIR.'themes/'.$theme.'/header.php';
		require MAIN_DIR.'themes/'.$theme.'/footer.php';
		$this->load_page_files($this->current_page_id, $theme);
		
		view_header($page_data).call_user_func('view_'.$this->current_page_id.'_page', $page_data).view_footer($page_data);
	}
}

?>