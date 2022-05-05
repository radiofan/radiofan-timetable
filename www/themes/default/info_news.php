<?php

/**
 * выводит тело страницы 'info_news'
 */
function view_info_news_page($page_data){
	?>
	<div class="container-fluid">
		<h1><?= $page_data['title'] ?></h1>
		<div class="news-card">
			<p class="news-header">
				<i class="news-date">05.05.2022</i> <b class="news-name">V0.1.0.3</b>
			</p>
			<p class="news-body">
				-добавлены новостные страницы<br>
			</p>
		</div>
		<div class="news-card">
			<p class="news-header">
				<i class="news-date">05.02.2022</i> <b class="news-name">V0.1.0.2</b>
			</p>
			<p class="news-body">
				-у политеха сменилось АПИ<br>
				-теперь parse-groups.php беcполезен<br>
			</p>
		</div>
		<div class="news-card">
			<p class="news-header">
				<i class="news-date">05.09.2021</i> <b class="news-name">V0.1</b>
			</p>
			<p class="news-body">
				-первая тестовая сборка, вроде выпоняющая свои функции<br>
			</p>
		</div>
		<div class="news-card">
			<p class="news-header">
				<i class="news-date">04.09.2021</i> <b class="news-name">V0.0.9.2</b>
			</p>
			<p class="news-body">
				-пересобран парсинг, расписания и бд<br>
			</p>
		</div>
	</div>
	<?php
}
