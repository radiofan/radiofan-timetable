<?php
class rad_url_node{
	/** @var string $id - уникалный идентификатор страницы */
	public $id;
	/** @var string $url_slice - строка-уровень используемая в url */
	public $url_slice;
	/** @var rad_url_node[] $child - массив дочерних страниц */
	public $child;
	/** @var null|rad_url_node $parent - родителский узел */
	public $parent;
	/** @var array $parametres - массив параметров страницы, если она не имеет дочерние */
	public $parametres;
	function __construct(){
		$this->id = '';
		$this->url_slice = '';
		$this->child = array();
		$this->parametres = array();
		$this->parent = null;
	}
}


class rad_url{
	/**
	 * id текущей страницы
	 * @var string
	 */
	private $current_page = '';
	/**
	 * массив со страницами
	 * @var rad_url_node[]
	 */
	private $pages;
	/**
	 * текущий url
	 * @var string
	 */
	private $url;
	/**
	 * массив использованных id, id являются ключами
	 * [id => true, ...]
	 * @var array
	 */
	private $id_list;
	/**
	 * параметры текущей страницы
	 * @var array
	 */
	private $current_parametres;
	/**
	 * массив ID страниц от родительской до текущей дочерней
	 * [id => url_slice, ...]
	 * @var array
	 */
	private $breadcrumbs;


	/**
	 * устанавливает путь до шаблонов в корне сайта, получает текущий url
	 */
	function __construct(){
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
	 * @param string|null $parent_id - ID родителя (null для верхней страницы)
	 * @return bool true если успешно
	 * @throws Exception
	 */
	function add_page(string $id, string $url_slice, $parent_id = NULL){
		if($id === '' || $url_slice === ''){
			throw new Exception('ID или кусочек URLа пустой');
			//return false;
		}
		if(isset($this->id_list[$id]) || $id === '404' || $id === 'main_page')
			throw new Exception('Неуникальный ID '.$id);
			//return false;
		if(!is_null($parent_id) && !isset($this->id_list[$parent_id]))
			throw new Exception('Родитель '.$parent_id.' не найден');
			//return false;
		
		//$data = array('id' => $id, 'url_slice' => $url_slice, 'child' => array(), 'parametres' => array());
		$node = new rad_url_node();
		$node->id = $id;
		$node->url_slice = $url_slice;
		if(is_null($parent_id)){
			if(isset($this->pages[$url_slice]))
				throw new Exception('Кусочек УРЛа '.$url_slice.' образует коллизию');
				//return false;
			$this->pages[$url_slice] = $node;
			$this->id_list[$id] = true;
		}else{
			$parent_page = $this->get_page_recursion($parent_id, $this->pages);
			if(isset($parent_page->child[$node->url_slice]))
				throw new Exception('Кусочек УРЛа '.$node->url_slice.' образует коллизию');
			$node->parent = $parent_page;
			$parent_page->child[$node->url_slice] = $node;
			$this->id_list[$node->id] = true;
		}
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
		if(!isset($this->id_list[$id])){
			throw new Exception('Несуществующий ID');
			//return false;
		}
		$tmp = $this->get_page_recursion($id, $this->pages);
		if(sizeof($tmp->child))
			throw new Exception('Не возможно указать параметры родительской странице');
			//return false;
		if(in_array($parameter_name, $tmp->parametres))
			throw new Exception('Не возможно указать одинаковые параметры для одной и той же страницы');
		$tmp->parametres[$pos] = $parameter_name;
		return true;
	}

	/**
	 * устанавливает текущей страницей 404-ую
	 * //TODO переделать и добавить main_page
	 */
	function set_current_404(){
		$this->current_page = '404';
		//$this->breadcrumbs = array('404' => '404');
	}
	
	/**
	 * возвращает текущий УРЛ
	 * @return string
	 */
	function get_current_url(){
		return $this->url;
	}

	/**
	 * возвращает id текущей страницы
	 * перед этим загружает текущую страницу
	 * @return string|false
	 * @throws Exception
	 */
	function get_current_page(){
		if(!$this->load_current_page())
			return false;
		return $this->current_page;
	}
	
	/**
	 * Вернет массив хлебных крошек
	 * @return array
	 */
	function get_breadcrumbs(){
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
		
		$elements = array_slice(explode('/', $this->url), 1);
		
		$len = sizeof($elements);
		if($len == 1 && $elements[0] === ''){
			$this->current_page = 'main_page';
		}else{
			$pages = $this->pages;
			$i=0;
			for(; $i<$len; $i++){
				if($i == $len-1 && $elements[$i] === '')
					break;
				if(isset($pages[$elements[$i]])){
					$this->current_page = $pages[$elements[$i]]->id;
					$this->breadcrumbs[$pages[$elements[$i]]->id] = $pages[$elements[$i]]->url_slice;
					if(sizeof($pages[$elements[$i]]->parametres))
						break;
					$pages = $pages[$elements[$i]]->child;
				}else{
					$this->set_current_404();
					return true;
				}
			}
			if($this->current_page === ''){
				$this->set_current_404();
				return true;
			}
			//обработка параметров
			if($i < $len-1 && sizeof($pages[$elements[$i]]->parametres)){
				$params = $pages[$elements[$i]]->parametres;
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
		if(!isset($this->id_list[$id]))
			return false;
		//$this->current_id = $id;
		$breadcr = array();
		$tmp = $this->get_page_recursion($id, $this->pages, $breadcr);
		$this->breadcrumbs = array_reverse($breadcr);
		$this->current_page = $tmp->id;
		$this->current_parametres = array_intersect_key($parametres, array_flip($tmp->parametres));
		return true;
	}
	
	/**
	 * Перенаправляет на страницу с указанным ID
	 * @param string $id
	 * @param array $parametres
	 */
	function redirect($id, $parametres=array()){
		header('Location: '.$this->get_url($id, $parametres));
		die();
	}
	
	/**
	 * возвращает урл для страницы с указанным ID
	 * @todo test
	 * @param $id
	 * @param array $parametres
	 * @return string
	 */
	function get_url($id, $parametres=array()){
		$tmp = clone $this;
		$tmp->set_current_page($id, $parametres);
		return '/'.implode('/', $tmp->breadcrumbs).'/'.implode('/', $tmp->current_parametres);
	}

	/**
	 * узел страницы
	 * @param $id - ID нужной страницы (если ID не существует вернет пустой rad_url_node)
	 * @param $tree - узел дерева, в первый раз передавать $this->pages
	 * @param $breadcrumbs - массив куда поместятся хлебные крошки (в обратном порядке)
	 * @return rad_url_node
	 */
	private function get_page_recursion($id, &$tree, &$breadcrumbs = array()){
		foreach($tree as $url_slice => &$page){
			if($page->id == $id){
				$breadcrumbs[$page->id] = $page->url_slice;
				return $page;
			}else{
				$tmp = $this->get_page_recursion($id, $page->child, $breadcrumbs);
				if($tmp->id !== ''){
					$breadcrumbs[$page->id] = $page->url_slice;
					return $tmp;
				}
			}
		}
		return new rad_url_node();
	}
}
?>