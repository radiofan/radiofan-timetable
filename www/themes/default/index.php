<?php

function view_main_page_page(){

?>
	<div class="container-fluid">
		<?= gen_timetable_html(); ?>
	</div>
<?php
}

/**
 * функция проверки на доступ к странице 'main_page'
 * Валидирует куки
 * @return bool
 */
function test_view_main_page(){
	global $COOKIE_V;
	$COOKIE_V->validate('timetable_parts');
	$COOKIE_V->validate('timetable_cols_size');
	$COOKIE_V->validate('timetable_options');
	return true;
}
?>