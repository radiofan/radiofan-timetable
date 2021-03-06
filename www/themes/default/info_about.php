<?php

/**
 * выводит тело страницы 'info_about'
 */
function view_info_about_page($page_data){
	?>
	<div class="container-fluid">
		<h1><?= $page_data['title'] ?></h1>
		<p>
			Данный сайт, позволяет улучшить работу с расписанием АлтГТУ.<br>
			А именно:
		</p>
		<ul>
			<li>Возможность узнать расписание группы, преподавателя, кабинета (в дальнейшем - разделы).</li>
			<li>Возможность добавить на главный экран несколько разделов, для их сравнения между собой.</li>
			<li>Объединение уроков по времени, а также по другим признакам (пара, группа, кабинет, преподаватель) (опционально)</li>
		</ul>
		<p>
			Возможность скрывать лишнюю информацию, столбцы.<br>
			<br>
			В дальнейшем планируется добавление новых функций.
		</p>
	</div>
	<?php
}