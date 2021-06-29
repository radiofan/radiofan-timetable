<?php
//TODO Сделать класс для страниц

function get_pages_options(){
	return array(
		'admin' => array(
			'title' => 'Вход',
			'user_level' => rad_user::GUEST,
			'need_roles' => array(),
			'type' => 'system'
		),
		'login' => array(
			'title' => 'Вход',
			'user_level' => rad_user::GUEST,
			'need_roles' => array(),
			'type' => 'system'
		),
		'404' => array(
			'title' => '404',
			'user_level' => rad_user::GUEST,
			'need_roles' => array(),
			'type' => 'system'
		),
		'main_page' => array(
			'title' => 'Главная',
			'user_level' => rad_user::GUEST,
			'need_roles' => array(),
			'type' => 'system'
		),
		'admin_edit_users' => array(
			'title' => 'Редактирование пользователей',
			'user_level' => rad_user::NEDOADMIN,
			'need_roles' => array('edit_users'),
			'type' => 'admin'
		),
		'admin_edit_settings' => array(
			'title' => 'Настройки',
			'user_level' => rad_user::NEDOADMIN,
			'need_roles' => array('edit_settings'),
			'type' => 'admin'
		)
	);
}

function gen_pages_tree(){
	global $URL;
	
	$URL->set_pages_path($_SERVER['DOCUMENT_ROOT'].'/templates');
	$URL->set_main_page('index.html');
	$URL->set_page_404('404.html');
	
	/*
	$URL->add_page('login', 'login', 'index.html');
	$URL->add_page('admin', 'admin', 'index.html', 'login');
	$URL->add_page('admin2', 'admin2', 'index.html', 'login');
	$URL->add_page('str_1', 'str1', 'index.html');
	$URL->add_page('str_2', 'str2', 'index.html');
	$URL->set_parametres('str_1', 'param1', 1);
	$URL->set_parametres('str_2', 'param1', 1);
	$URL->set_parametres('str_2', 'param2', 2);
	*/
	
	
	$URL->add_page('admin', 'admin', 'admin.html');
	$URL->add_page('login', 'login', 'admin.html');
	$URL->add_page('admin_edit_users', 'edit-users', 'edit-users.html', 'admin');
	$URL->add_page('admin_edit_settings', 'edit-settings', 'edit-settings.html', 'admin');
}

function prepare_page_data(){
	global $URL, $USER, $DATA, $DB;
	$ret = array();
	$pages_options = get_pages_options();
	$page_id = $URL->get_current_id();
	$ret['title'] = $pages_options[$page_id]['title'];
	if($USER->get_user_level() < $USER::NEDOADMIN && $pages_options[$page_id]['user_level'] >= $USER::NEDOADMIN){
		$URL->set_current_404();
		return array('title' => $pages_options['404']['title'], 'addition_scripts' => '', 'addition_libs' => '', 'addition_styles' => '');
	}
	if(!is_login() && $pages_options[$page_id]['user_level'] > 0){
		redirect('/login');
	}else if(is_login() && ($page_id === 'admin' || $page_id === 'login') && !$USER->can_user('view_debug_info')){
		redirect('/');
	}
	
	$ret['addition_scripts'] = '';
	$ret['addition_libs'] = '';
	$ret['addition_styles'] = '';
	if(isset($pages_options[$page_id]['libs'])){
		$len = sizeof($pages_options[$page_id]['libs']);
		for($i=0; $i<$len; $i++){
			$tmp = $pages_options[$page_id]['libs'][$i];
			if(file_exists(MAIN_DIR.'libs/'.$tmp))
				$ret['addition_libs'] .= '<script src="/libs/'.$tmp.'?ver='.filemtime(MAIN_DIR. 'libs/'.$tmp).'"></script>';
		}
	}
	if(isset($pages_options[$page_id]['styles'])){
		$len = sizeof($pages_options[$page_id]['styles']);
		for($i=0; $i<$len; $i++){
			$tmp = $pages_options[$page_id]['styles'][$i];
			if(file_exists(MAIN_DIR.$tmp))
				$ret['addition_styles'] .= '<link rel="stylesheet" href="/'.$tmp.'?ver='.filemtime(MAIN_DIR.$tmp).'"/>';
		}
	}
	if(isset($pages_options[$page_id]['scripts'])){
		$len = sizeof($pages_options[$page_id]['scripts']);
		for($i=0; $i<$len; $i++){
			$tmp = $pages_options[$page_id]['scripts'][$i];
			if(file_exists(MAIN_DIR.'scripts/'.$tmp))
				$ret['addition_scripts'] .= '<script src="/scripts/'.$tmp.'?ver='.filemtime(MAIN_DIR. 'scripts/'.$tmp).'"></script>';
		}
	}
	
	if($page_id == 'main_page'){
		$ret['admin_links'] = '';
		$ret['users_links'] = '';
		if($USER->get_user_level() >= rad_user::NEDOADMIN){
			$ret['admin_links'] = '';
		}
		foreach($pages_options as $id => &$options){
			switch($options['type']){
				case 'user':
				case 'admin':
					$flag = true;
					$len = sizeof($options['need_roles']);
					for($i=0; $i<$len; $i++){
						if(!can_user($options['need_roles'][$i])){
							$flag = false;
							break;
						}
					}
					if($flag){
						$tmp = '<a href="'.$URL->get_url($id).'">'.$options['title'].'</a><br>';
						if($options['type'] == 'user'){
							$ret['users_links'] .= $tmp;
						}else{
							$ret['admin_links'] .= $tmp;
						}
					}
				default:
					break;
			}
		}
	}else{
		$flag = true;
		$len = sizeof($pages_options[$page_id]['need_roles']);
		for($i=0; $i<$len; $i++){
			if(!can_user($pages_options[$page_id]['need_roles'][$i])){
				$flag = false;
				break;
			}
		}
		if(!$flag){
			$URL->set_current_404();
			return array('title' => $pages_options['404']['title'], 'addition_scripts' => '', 'addition_libs' => '', 'addition_styles' => '');
		}else{
			if($page_id == 'admin_edit_users'){
				$roles = $USER->get_roles_range();
				$admin_roles = '<optgroup label="Администрирование">';
				$user_roles = '<optgroup label="Функционал">';
				
				foreach($roles as $role => $range){
					if($range <= $USER::SUPERUSER){
						$user_roles .= '<option value="'.$role.'">'.$role.'</option>';
					}else if($range >= $USER::NEDOADMIN){
						$admin_roles .= '<option value="'.$role.'">'.$role.'</option>';
					}
				}
				$admin_roles .= '</optgroup>';
				$user_roles .= '</optgroup>';
				$ret['all_roles'] = $user_roles.$admin_roles;
			}else if($page_id == 'admin_edit_settings'){
			
			}
		}
	}
	
	return $ret;
}

?>