<?php
class URL{
	/**
 	* путь до папки со страницами (по умолчанию корень сайта)
 	* @var string
 	*/
	private $pages_path;
	/**
	 * имя файла главной страницы
	 * @var string
	 */
	private $main_page = null;
	/**
	 * имя файла ошибки 404
	 * @var string
	 */
	private $page_404 = null;
	/**
	 * древовидный массив со страницами
	 * @var array
	 */
	private $pages;
	/**
	 * текущий url
	 * @var string
	 */
	private $url;
	/**
	 * массив использованных id
	 * @var array
	 */
	private $id_list;
	/**
	 * текущая страница(название файла)
	 * @var string
	 */
	private $current_page = '';
	/**
	 * параметры текущей страницы
	 * @var array
	 */
	private $current_parametres;
	/**
	 * массив ID страниц от родительской до текущей дочерней
	 * @var array
	 */
	private $breadcrumbs;


	/**
	 * устанавливает путь до шаблонов в корне сайта, получает текущий url
	 */
	function __construct(){
		//$pages_path = $_SERVER['DOCUMENT_ROOT'].'/pages/';
		$this->pages_path = $_SERVER['DOCUMENT_ROOT'].'/';
		$this->url = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
		$this->pages = array();
		$this->id_list = array();
		$this->breadcrumbs = array();
	}

	/**
	 * добавление страницы
	 *
	 * @param string $id        - уникальный ID (404 и main_page заняты)
	 * @param string $url_slice - кусочек УРЛа
	 * @param string|null $file_name - имя файла страницы (нулл для узла без страницы)
	 * @param string|null $parent_id - ID родителя (нулл для верхней страницы)
	 * @return bool true если успешно
	 * @throws Exception
	 */
	function add_page($id, $url_slice, $file_name = NULL, $parent_id = NULL){
		if(in_array($id, $this->id_list) || $id === '404' || $id === 'main_page')
			throw new Exception('Неуникальный ID '.$id);
			//return false;
		if(!is_null($file_name) && !$this->check_file($this->pages_path . $file_name))
			throw new Exception('Страница '.$file_name.' не найдена');
			//return false;
		if(!is_null($parent_id) && !in_array($parent_id, $this->id_list))
			throw new Exception('Родитель '.$parent_id.' не найден');
			//return false;
		if(empty($id) || empty($url_slice)){
			throw new Exception('ID или кусочек URLа пустой');
			//return false;
		}
		
		$data = array('id' => $id, 'url_slice' => $url_slice, 'file_name' => $file_name, 'child' => array(), 'parametres' => array());
		if(is_null($parent_id)){
			if(isset($this->pages[$url_slice]))
				throw new Exception('Кусочек УРЛа '.$url_slice.' образует коллизию');
				//return false;
			$this->pages[$url_slice] = $data;
			$this->id_list[] = $id;
		}else{
			$parent_page = &$this->get_page_recursion($parent_id, $this->pages);
			if(isset($parent_page['child'][$data['url_slice']]))
				throw new Exception('Кусочек УРЛа '.$data['url_slice'].' образует коллизию');
			$parent_page['child'][$data['url_slice']] = $data;
			$this->id_list[] = $data['id'];
		}
		return true;
	}

	/**
	 * установка шаблона для главной страницы (проверяет существование файла)
	 *
	 * @param string $file_name - имя шаблона из папки с шаблонами
	 * @return bool
	 * @throws Exception
	 */
	function set_main_page($file_name){
		if(!$this->check_file($this->pages_path . $file_name))
			throw new Exception('Страница не найдена');
			//return false;
		$this->main_page = $file_name;
		return true;
	}

	/**
	 * установка шаблона для 404 страницы (проверяет существование файла)
	 *
	 * @param string $file_name - имя шаблона из папки с шаблонами
	 * @return bool
	 * @throws Exception
	 */
	function set_page_404($file_name){
		if(!$this->check_file($this->pages_path . $file_name))
			throw new Exception('Страница не найдена');
			//return false;
		$this->page_404 = $file_name;
		return true;
	}

	/**
	 * установка пути до папки с шаблонами (проверяет существование папки)
	 *
	 * @param string $path - путь до папки с шаблонами
	 * @return bool
	 * @throws Exception
	 */
	function set_pages_path($path){
		if(!file_exists($path) || !is_dir($path))
			throw new Exception('Путь не найден');
			//return false;
		$this->pages_path = realpath($path).DIRECTORY_SEPARATOR;
		return true;
	}

	/**
	 * установка параметров для страницы
	 * типа my_site.ru/page/param1/param2
	 *
	 * @param string $id - ID страницы
	 * @param string $parameter_name - имя параметра
	 * @param int $pos - позиция параметра в УРЛе
	 *
	 * @return bool
	 * @throws Exception
	 */
	function set_parametres($id, $parameter_name, $pos=1){
		if(!in_array($id, $this->id_list)){
			throw new Exception('Несуществующий ID');
			//return false;
		}
		$tmp = &$this->get_page_recursion($id, $this->pages);
		if(sizeof($tmp['child']))
			throw new Exception('Не возможно указать параметры родительской странице');
			//return false;
		if(in_array($parameter_name, $tmp['parametres']))
			throw new Exception('Не возможно указать одинаковые параметры для одной и той же страницы');
		$tmp['parametres'][$pos] = $parameter_name;
		return true;
	}

	/**
	 * устанавливает текущей страницей 404-ую
	 */
	function set_current_404(){
		$this->current_page = $this->page_404;
		$this->breadcrumbs = array('404' => '404');
	}

	/**
	 * устанавливает текущей страницей главную
	 */
	function get_main_page(){
		return $this->main_page;
	}

	/**
	 * возвращает имя файла шаблона 404 страницы
	 * @return string
	 */
	function get_page_404(){
		return $this->page_404;
	}

	/**
	 * возвращает имя файла шаблона главной страницы
	 * @return string
	 */
	function get_pages_path(){
		return $this->pages_path;
	}

	/**
	 * возвращает текущий УРЛ
	 * @return string
	 */
	function get_current_url(){
		return $this->url;
	}

	/**
	 * возвращает название файла текущей страницы
	 * перед этим загружает текущую страницу
	 * @return string|bool
	 * @throws Exception
	 */
	function get_current_page(){
		if(!$this->load_current_page())
			return false;
		return $this->current_page;
	}

	/**
	 * возвращает ID текущей страницы
	 * перед этим загружает текущую страницу
	 * @return string|bool
	 * @throws Exception
	 */
	function get_current_id(){
		if(!$this->load_current_page())
			return false;
		$len = sizeof($this->breadcrumbs);
		end($this->breadcrumbs);
		return $len > 0 ?  key($this->breadcrumbs): 'main_page';
	}
	
	/**
	 * Вернет массив хлебных крошек, если была загружена текущая страница, иначе false
	 * @return array|false
	 */
	public function get_breadcrumbs(){
		return $this->breadcrumbs;
	}

	/**
	 * возвращает значение параметра текущей страницы (null если параметр не установлен)
	 *
	 * @param string $key - имя параметра
	 * @return string|null
	 */
	function get_parameter($key){
		return isset($this->current_parametres[$key]) ? $this->current_parametres[$key] : null;
	}
	
	/**
	 * анализирует УРЛ, устанавливает текущую страницу и параметры для неё
	 * @return bool
	 * @throws Exception
	 */
	function load_current_page(){
		//die(print_r($this->pages, 1));
		
		if($this->current_page !== '')
			return true;
		if(is_null($this->main_page) || is_null($this->page_404))
			throw new Exception('Отсутсвуют главная страница и ошибка 404');
			//return false;
		
		$elements = array_slice(explode('/', $this->url), 1);

		$len = sizeof($elements);
		if($len == 1 && $elements[0] === ''){
			$this->current_page = $this->main_page;
		}else{
			$pages = $this->pages;
			$i=0;
			for($i=0; $i<$len; $i++){
				if($i == $len-1 && $elements[$i] === '')
					break;
				if(isset($pages[$elements[$i]])){
					$this->current_page = $pages[$elements[$i]]['file_name'];
					$this->breadcrumbs[$pages[$elements[$i]]['id']] = $pages[$elements[$i]]['url_slice'];
					if(sizeof($pages[$elements[$i]]['parametres']))
						break;
					$pages = $pages[$elements[$i]]['child'];
				}else{
					$this->set_current_404();
					return true;
				}
			}
			if($this->current_page === '' || is_null($this->current_page)){
				$this->set_current_404();
				return true;
			}
			//обработка параметров
			if($i < $len-1 && sizeof($pages[$elements[$i]]['parametres'])){
				$params = $pages[$elements[$i]]['parametres'];
				ksort($params);
				$params = array_values($params);
				$u = 0;
				for($i = $i+1; $i<$len; $i++, $u++){
					if($i == $len-1 && $elements[$i] === '')
						break;
					if(isset($params[$u])){
						$this->current_parametres[$params[$u]] = $elements[$i];
					}else{
						$this->set_current_404();
						break;
					}
				}
			}
		}
		return true;
	}

	/**
	 * устанавливает текущей новую страницу
	 * @param string $id - ID новой страницы
	 * @param array $parametres - параметры новой страницы
	 *
	 * @return bool
	 */
	function set_current_page($id, $parametres=array()){
		if(!in_array($id, $this->id_list))
			return false;
		//$this->current_id = $id;
		$breadcr = array();
		$tmp = &$this->get_page_recursion($id, $this->pages, $breadcr);
		$this->breadcrumbs = array_reverse($breadcr);
		$this->current_page = $tmp['file_name'];
		$this->current_parametres = array_intersect_key($parametres, array_flip($tmp['parametres']));
		return true;
	}
	
	/**
	 * Перенаправляет на страницу с указанным ID
	 * @todo добавить параметры в урл
	 * @param string $id
	 * @param array $parametres
	 */
	function redirect($id, $parametres=array()){
		header('Location: '.$this->get_url($id, $parametres));
		die();
	}
	
	/**
	 * возвращает урл для страницы с указанным ID
	 * @todo добавить параметры в урл
	 * @param $id
	 * @param array $parametres
	 * @return string
	 */
	function get_url($id, $parametres=array()){
		$tmp = clone $this;
		$tmp->set_current_page($id, $parametres);
		return '/'.implode('/', $tmp->breadcrumbs);
	}

	/**
	 * выводит текущую страницу с помощью шаблонизатора
	 * @param array $data - данные для шаблонизатора
	 * @see rad_template_old
	 * @return bool
	 * @throws Exception
	 */
	function view_current_page($data = array()){
		//die(print_r($this->pages, 1));
		if(!$this->load_current_page())
			return false;
		echo rad_template_old($this->pages_path.$this->current_page, $data);
		/*
		$start = microtime(1);
		rad_template($this->pages_path.$this->current_page, $data);
		echo 'rad_template: '.((microtime(1)-$start)*1000).' мсек.'.PHP_EOL;
		$start = microtime(1);
		rad_template_old($this->pages_path.$this->current_page, $data);//быстрее
		echo 'rad_template_old: '.((microtime(1)-$start)*1000).' мсек.'.PHP_EOL;
		*/
		return true;
	}
	
	//проверка на существование файла

	/**
	 * проверка на существование файла
	 * @param $path - путь до файла
	 * @return bool
	 */
	private function check_file($path){
		return file_exists($path) && is_file($path);
	}

	/**
	 * возвращает массив с данными страницы
	 * @param $id - ID нужной страницы (если ID не существует вернет пустой массив)
	 * @param $tree - узел дерева, в первый раз передавать $this->pages
	 * @param $breadcrumbs - массив куда поместятся хлебные крошки (в обратном порядке)
	 * @return array
	 */
	private function &get_page_recursion($id, &$tree, &$breadcrumbs = array()){
		$tmp = '';
		foreach($tree as $url_slice => &$page){
			if($page['id'] == $id){
				$breadcrumbs[$page['id']] = $page['url_slice'];
				return $page;
			}else{
				$tmp = &$this->get_page_recursion($id, $page['child'], $breadcrumbs);
				if($tmp){
					$breadcrumbs[$page['id']] = $page['url_slice'];
					return $tmp;
				}
			}
		}
		static $ret = array();
		return $ret;
	}
}
?>