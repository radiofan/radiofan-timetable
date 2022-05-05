<?php

/**
 * выводит тело страницы 'info_faq'
 */
function view_info_faq_page($page_data){
	?>
	<div class="container-fluid">
		<h1><?= $page_data['title'] ?></h1>
		<p class="question-answer">
			<b class="question">Количество используемых разделов не достаточно для моих целей. Как я могу его увеличить?</b>
			<br>
			<span class="answer">На данный момент функция не реализована. Её добавление планируется в ближайшее время.</span>
		</p>
		<p class="question-answer">
			<b class="question">Как перенести настроенный рабочий стол на другое устройство?</b>
			<br>
			<span class="answer">На данный момент это возможно только с помощью переноса файлов cookie.</span>
		</p>
	</div>
	<?php
}
