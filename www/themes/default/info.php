<?php

/**
 * функция проверки на доступ к странице 'info'
 * редиректим на 'info_about'
 * @return bool
 */
function test_view_info(){
	global $URL;
	$URL->redirect('info_about');

	return false;
}
