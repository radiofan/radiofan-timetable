<?php

function view_main_page_page(){
	//выводим главный контейнер
	//плашку с кнопками подменю
	//выводим подменю
?>
	<div class="container-fluid">
		<div class="settings">
			<div class="card">
				<div class="card-header">
					<div class="btn-group" role="group">
						<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#settings_collapse" aria-expanded="false" aria-controls="settings_collapse">
							Настройки
						</button>
						<button class="btn btn-link" type="button" data-toggle="modal" data-target="#additor-modal">
							Добавить раздел
						</button>
						<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#parts_collapse" aria-expanded="false" aria-controls="sections_collapse">
							Управлять разделами
						</button>
					</div>
				</div>
				<?php view_settings_block(); ?>
				<?php view_parts_manage_block(); ?>
			</div>
		</div>
		<?php view_timetable_block(); ?>
	</div>
<?php
}

/**
 * Выводит блок с настройками отображения таблицы
 */
function view_settings_block(){
	global $COOKIE_V;
	$checked = array('', ' checked="checked"');
	$options = $COOKIE_V->timetable_options;
?>
	<!--Настройки-->
	<div id="settings_collapse" class="collapse settings-collapse">
		<div class="card-body">
			<div class="setting-row settings-teacher_add_hide">
				<label>
					<span>Скрыть звание препода</span>
					<input type="checkbox" name="settings[teacher_add_hide]" value="1" data-settings="teacher_add_hide"<?= $checked[$options['teacher_add_hide']]; ?>>
				</label>
			</div>
			<hr>
			<div class="setting-row settings-go2curr_day">
				Идти к текущ(ей/ему)
				<label>
					<input type="radio" name="settings[go2curr_day]" value="0" data-settings="go2curr_day"<?= ($options['go2curr_day'] == '' ? $checked[1] : ''); ?>>
					<span>Нет</span>
				</label>
				<label>
					<input type="radio" name="settings[go2curr_day]" value="1" data-settings="go2curr_day"<?= ($options['go2curr_day'] == 'week' ? $checked[1] : ''); ?>>
					<span>Неделе</span>
				</label>
				<label>
					<input type="radio" name="settings[go2curr_day]" value="2" data-settings="go2curr_day"<?= ($options['go2curr_day'] == 'day' ? $checked[1] : ''); ?>>
					<span>Дню</span>
				</label>
			</div>
			<hr>
			<div class="setting-row settings-cell_word_wrap">
				<label>
					<span>Переносить слова</span>
					<input type="checkbox" name="settings[cell_word_wrap]" value="1" data-settings="cell_word_wrap"<?= $checked[$options['cell_word_wrap']]; ?>>
				</label>
			</div>
			<hr>
			<div class="setting-row settings-lesson_unite">
				<label>
					<span>Объединять уроки</span>
					<input type="checkbox" name="settings[lesson_unite]" value="1" data-settings="lesson_unite"<?= $checked[$options['lesson_unite']]; ?>>
				</label>
			</div>
		</div>
	</div>
<?php
}

/**
 * выводит блок для упраления разделами
 */
function view_parts_manage_block(){
	global $COOKIE_V;
	$parts = $COOKIE_V->timetable_parts;
	$checked = array('', ' checked="checked"');
	$parts_len = sizeof($parts);

?>
	<!--управление разделами-->
	<div id="parts_collapse" class="collapse parts-collapse">
		<div id="section-edited">
			<p>Изменения сохранены <button class="btn btn-link" onclick="location.reload();return false;">Обновить страницу</button></p>
		</div>
		<div class="card-body">
			<?php
			for($i=0; $i<$parts_len; $i++):
				?>
				<div class="section-wrap" data-part="<?= $i; ?>">
					<div class="section-header">
						<span class="section-draggable part-drag" title="Сортировать">&#8645;</span>
						<span class="section-data">
							<button class="btn btn-link" data-toggle="collapse" data-target="#section-part-<?= $i; ?>" aria-expanded="false" aria-controls="section-part-<?= $i; ?>"><?= $parts[$i]['html_type']; ?></button>
							- <input
								class="input-part-name form-control edit-section-name"
								type="text"
								maxlength="<?= MAX_PART_NAME_LEN; ?>"
								value="<?= htmlspecialchars($parts[$i]['part_name']); ?>"
								data-part="<?= $i; ?>"
							>
						</span>
						<span class="section-delete" title="Удалить">&#10007;</span>
					</div>
					<div class="section-cols-wrap collapse" data-part="<?= $i; ?>" id="section-part-<?= $i; ?>">
						<?php
						$col_pos = 0;
						foreach($parts[$i]['cols_order'] as $col => $view){
							switch($col){
								case 'l':
									?>
									<div class="section-col col-lesson" data-pos="<?= $col_pos; ?>" data-col-type="l">
										<span class="section-draggable col-drag" title="Сортировать">&#8645;</span>
										<span class="child-center">Урок</span>
										<span class="section-showhide" title="Видимость"><input class="cb-col-view" type="checkbox"<?= $checked[$view]; ?>></span>
									</div>
									<?php
									break;
								case 't':
									?>
									<div class="section-col col-lesson-add" data-pos="<?= $col_pos; ?>" data-col-type="t">
										<span class="section-draggable col-drag" title="Сортировать">&#8645;</span>
										<span class="child-center">Тип</span>
										<span class="section-showhide" title="Видимость"><input class="cb-col-view" type="checkbox"<?= $checked[$view]; ?>></span>
									</div>
									<?php
									break;
								case 'g':
									?>
									<div class="section-col col-group" data-pos="<?= $col_pos; ?>" data-col-type="g">
										<span class="section-draggable col-drag" title="Сортировать">&#8645;</span>
										<span class="child-center">Группа</span>
										<span class="section-showhide" title="Видимость"><input class="cb-col-view" type="checkbox"<?= $checked[$view]; ?>></span>
									</div>
									<?php
									break;
								case 'c':
									?>
									<div class="section-col col-cabinet" data-pos="<?= $col_pos; ?>" data-col-type="c">
										<span class="section-draggable col-drag" title="Сортировать">&#8645;</span>
										<span class="child-center">Кабинет</span>
										<span class="section-showhide" title="Видимость"><input class="cb-col-view" type="checkbox"<?= $checked[$view]; ?>></span>
									</div>
									<?php
									break;
								case 'p':
									?>
									<div class="section-col col-teacher" data-pos="<?= $col_pos; ?>" data-col-type="p">
										<span class="section-draggable col-drag" title="Сортировать">&#8645;</span>
										<span class="child-center">Препод</span>
										<span class="section-showhide" title="Видимость"><input class="cb-col-view" type="checkbox"<?= $checked[$view]; ?>></span>
									</div>
									<?php
									break;
							}
							$col_pos++;
						}
						?>
					</div>
				</div>
			<?php
			endfor;
			?>
		</div>
	</div>
<?php
}

function view_timetable_block(){
	global $COOKIE_V, $DB;
	$options = $COOKIE_V->timetable_options;
	$parts = $COOKIE_V->timetable_parts;
	$parts_len = sizeof($parts);

	$table_head_1 = '';
	$table_head_2 = '';
	$sticks = '<div class="stick" data-col="0" data-col-type="number" data-col-part="-1"></div><div class="stick" data-col="1" data-col-type="time" data-col-part="-1"></div>';
	
	$today = new DateTime();
	$first_week_day = (clone $today)->sub(new DateInterval('P'.($today->format('N') - 1).'D'))->format(DB_DATE_FORMAT);

	$table = array();
	for($i=0; $i<$parts_len; $i++){

		$col_name = $parts[$i]['type'].'_id';
		//$parts[$i]['table_name'] = 'stud_'.$parts[$i]['type'].'s';

		//генерация html
		/*
		for($i1 = 2; $i1 <= 6; $i1++){
			$sticks .= '<div class="stick" data-col="'.($i1+$i*5).'" data-col-type="time" data-col-part="-1"></div>';
		}
		*/
		$table_head_1 .= '<th colspan="'.array_sum($parts[$i]['cols_order']).'"><div class="cell part-'.$i.' part-header">'.esc_html($parts[$i]['part_name']).'</div></th>';
		$i1 = 2 + $i*5;
		$cols_headers = array('l' => 'Урок', 't' => 'Тип', 'g' => 'Группа', 'c' => 'Кабинет', 'p' => 'Препод');
		foreach($parts[$i]['cols_order'] as $col => $view){
			$view_str = $view ? '' : ' h';
			$table_head_2 .= '<th class="'.$view_str.'"><div class="cell part-'.$i.' col-'.$col.'" data-col="'.$i1.'">'.$cols_headers[$col].'</div></th>';
			$sticks .= '<div class="stick'.$view_str.'" data-col="'.$i1.'" data-col-type="'.$col.'" data-col-part="'.$i.'"></div>';
			$i1++;
		}

		//генерация данных
		$res = $DB->getAll('
			SELECT 
				`tm_t`.*,
				`les_t`.`parse_text` AS `lesson`,
				`gr_t`.`name` AS `group_name`, `gr_t`.`faculty_id`,
				`cb_t`.`cabinet`, `cb_t`.`additive` AS `cabinet_additive`, `cb_t`.`building`,
				`th_t`.`fio`, `th_t`.`additive` AS `teacher_additive`
			FROM
				`stud_timetable` AS `tm_t`
			LEFT JOIN `stud_groups` AS `gr_t`
				ON `tm_t`.`group_id` = `gr_t`.`id`
			LEFT JOIN `stud_lessons` AS `les_t`
				ON `tm_t`.`lesson_id` = `les_t`.`id`
			LEFT JOIN `stud_cabinets` AS `cb_t`
				ON `tm_t`.`cabinet_id` = `cb_t`.`id`
			LEFT JOIN `stud_teachers` AS `th_t`
				ON `tm_t`.`teacher_id` = `th_t`.`id`
			WHERE
				`tm_t`.?n = ?s AND
				`tm_t`.`date` >= ?s - INTERVAL 7 DAY AND
				`tm_t`.`date` <= ?s + INTERVAL 13 DAY
			ORDER BY
				`tm_t`.`date`, `tm_t`.`week`, `tm_t`.`time`',
			//------------------------------------------------
			$col_name,
			$parts[$i]['id'],
			$first_week_day,
			$first_week_day
		);
		add_data_to_timetable($res, $i, $table);
	}
	if($options['lesson_unite'])
		prepare_data_to_timetable_html($parts_len, $table);
	
?>
	<div class="timetable-block">
		<div class="sticks"><?= $sticks; ?></div>
		<div class="timetable-head">
			<div class="timetable-head-static timetable-table">
				<div class="th"><div class="cell col-number" data-col="0">№ пары</div></div>
				<div class="th"><div class="cell col-time" data-col="1">Время</div></div>
			</div>
			<div class="timetable-head-cont">
				<div class="timetable-head-wrap">
					<table class="timetable-table">
						<thead>
						<tr><?= $table_head_1; ?></tr>
						<tr><?= $table_head_2; ?></tr>
						</thead>
					</table>
				</div>
			</div>
		</div>
		<div class="timetable-body">
			<?php view_timetable_body($table, $parts, $options['lesson_unite']); ?>
		</div>
	</div>
<?php
}

function view_timetable_body($table, $parts, $lesson_unite){
	global $DATA, $DB;

	$parts_len = sizeof($parts);
	$cols_order = array();
	$cols_implode = array();
	for($i=0; $i<$parts_len; $i++){
		$cols_order[] = array_flip(array_keys($parts[$i]['cols_order']));
		$cols_implode[] = $parts[$i]['cols_order'];
	}
	/** 
	 * @var $times - массив массивов времени старта и времени конца уроков
	 * @see get_time_list() 
	 */
	$times = $DB->getAll('SELECT `time_start`, `time_end` FROM `stud_lesson_times` WHERE `type` = 1 ORDER BY `time_start`');
	/** @var $time_list - массив времени длительности уроков для отображения */
	$time_list = get_time_list($times);
	
	$today = new DateTime();
	$f_week_day = (clone $today)->sub(new DateInterval('P'.($today->format('N') - 1).'D'))->format(DB_DATE_FORMAT);
	$curr_week = $DB->getOne('SELECT `week` FROM `stud_timetable` WHERE `date` >= ?s AND `date` <= ?s + INTERVAL 6 DAY LIMIT 1', $f_week_day, $f_week_day);
	if(!$curr_week)
		$curr_week = 0;
	/*
	$curr_week = 0;
	if($f_week_day){
		//разница между таймстампами текущего дня и дня первой недели
		$curr_week = (clone $today)->setTime(0, 0)->getTimestamp() - $f_week_day->getTimestamp();
		//какой день сейчас 0 - 13
		$curr_week = intdiv($curr_week, SECONDS_PER_DAY) % 14;
		//номер текущей недели (1-ая или 2-ая)
		$curr_week = intdiv($curr_week, 7) + 1;
	}
	*/

	//получаем количество секунд с начала текущего дня
	$curr_less = (int)$today->format('G')*SECONDS_PER_HOUR + (int)$today->format('i')*SECONDS_PER_MINUTE + (int)$today->format('s');
	/** @var $type_curr_less - 0: урок еще не начался, но он следующий; 1: урок идет сейчас */
	$type_curr_less = 0;
	$times_len = sizeof($times);
	for($i=0; $i < $times_len; $i++){
		if($curr_less < $times[$i]['time_start']){
			$curr_less = $i;
			$type_curr_less = 0;
			break;
		}
		if($times[$i]['time_start'] <= $curr_less && $times[$i]['time_end'] > $curr_less){
			$curr_less = $i;
			$type_curr_less = 1;
			break;
		}
		if($i == $times_len-1){
			$curr_less = -1;
			$type_curr_less = 0;
		}
	}
	/** @var $curr_less - номер текущего/следующего урока [0, ...], -1 если такой отсутствует*/

	$week_days = array('понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье');
	for($i=0; $i<7; $i++){
		$week_days[$i] = mb_convert_case($week_days[$i], MB_CASE_TITLE);
	}
	/** @var $curr_day - номер текущего дня [0-6] */
	$curr_day = (int) $today->format('N') - 1;
	
	//------------------------------------------------------------------------------------------------------------------
	//генерация html
	$html_body_static = '';
	$html_body_cont = '';

	for($week = 1; $week<=2; $week++){
		$week_class = $curr_week == $week ? ' curr-week-row' : '';
		for($day=0; $day<6; $day++){
			//добаляем строку дня и недели
			$today_class = $week_class.($curr_day == $day && $curr_week == $week ? ' today-row' : '');
			//TODO если ширина дня больше ширины статического столбца, то день обрезается
			//TODO доббавить возможность кастомизации
			$html_body_static .= '<tr class="clean-row row'.$today_class.'"><td colspan="2"><div class="cell day"><div class="day-cont">'.$week_days[$day].' <span class="week">(нед. '.$week.')</span></div></div></td></tr>';
			//if($parts_len)
			$html_body_cont .= '<tr class="clean-row row'.$today_class.'"><td colspan="'.($parts_len*5).'"><div class="cell"></div></td></tr>';
			
			//производим вывод уроков
			for($less=0; $less<$times_len; $less++){
				$less_class = $curr_less == $less && $curr_day == $day && $curr_week == $week ? ($type_curr_less ? ' curr-lesson-row' : ' next-lesson-row') : $today_class;
				$len = isset($table[$week][$day][$less]) ? sizeof($table[$week][$day][$less]) : 1;
				$all_rowspan = array_fill(0, $parts_len, array(
					'row_l' => 0,
					'row_t' => 0,
					'row_g' => 0,
					'row_c' => 0,
					'row_p' => 0
				));
				
				//производим вывод строк
				for($line=0; $line<$len; $line++){
					//вывод времени
					if($lesson_unite){
						if($line){
							$html_body_static .= '<tr class="default-row row'.$less_class.'" data-ind="'.$week.'-'.$day.'-'.$less.'"></tr>';
						}else{
							$html_body_static .= '
								<tr class="default-row row'.$less_class.'" data-ind="'.$week.'-'.$day.'-'.$less.'">
									<td rowspan="'.$len.'"><div class="cell col-number" data-col="0">'.($less+1).'</div></td>
									<td rowspan="'.$len.'"><div class="cell col-time" data-col="1">'.$time_list[$less].'</div></td>
								</tr>';
						}
					}else{
						$html_body_static .= '
							<tr class="default-row row'.$less_class.'" data-ind="'.$week.'-'.$day.'-'.$less.'">
								<td><div class="cell col-number" data-col="0">'.($less+1).'</div></td>
								<td><div class="cell col-time" data-col="1">'.$time_list[$less].'</div></td>
							</tr>';
					}
					
					$html_body_cont .= '<tr class="default-row row'.$less_class.'" data-ind="'.$week.'-'.$day.'-'.$less.'">';
					for($i=0; $i<$parts_len; $i++){
						$tmp = isset($table[$week][$day][$less][$line][$i]) ? $table[$week][$day][$less][$line][$i] : array();
						//$cols = $parts[$i]['cols_order'];
						
						//урок
						$view_str = $parts[$i]['cols_order']['l'] ? '' : ' class="h"';
						$lesson = isset($tmp['lesson']) ? esc_html(mb_strtoupper($tmp['lesson'])) : '';
						if(isset($tmp['row_l'])){
							$all_rowspan[$i]['row_l'] = $tmp['row_l'];
							$cols_implode[$i]['l'] = '<td rowspan="'.$tmp['row_l'].'"'.$view_str.'><div class="cell part-'.$i.' col-l" data-col="'.(2+$cols_order[$i]['l']+$i*5).'">'.$lesson.'</div></td>';
						}else{
							$cols_implode[$i]['l'] = !$all_rowspan[$i]['row_l']
								? '<td'.$view_str.'><div class="cell part-'.$i.' col-l" data-col="'.(2+$cols_order[$i]['l']+$i*5).'">'.esc_html($lesson).'</div></td>'
								: '';
						}
						
						//тип урока
						$view_str = $parts[$i]['cols_order']['t'] ? '' : ' class="h"';
						$lesson_add = isset($tmp['lesson_type']) ? $tmp['lesson_type'] : '';
						if(isset($tmp['row_t'])){
							$all_rowspan[$i]['row_t'] = $tmp['row_t'];
							$cols_implode[$i]['t'] = '<td rowspan="'.$tmp['row_t'].'"'.$view_str.'><div class="cell part-'.$i.' col-t" data-col="'.(2+$cols_order[$i]['t']+$i*5).'">'.$lesson_add.'</div></td>';
						}else{
							$cols_implode[$i]['t'] = !$all_rowspan[$i]['row_t']
								? '<td'.$view_str.'><div class="cell part-'.$i.' col-t" data-col="'.(2+$cols_order[$i]['t']+$i*5).'">'.$lesson_add.'</div></td>'
								: '';
						}
						
						//группа
						$view_str = $parts[$i]['cols_order']['g'] ? '' : ' class="h"';
						$group_name = isset($tmp['group_name']) ? esc_html($tmp['group_name']) : '';
						if(isset($tmp['row_g'])){
							$all_rowspan[$i]['row_g'] = $tmp['row_g'];
							$cols_implode[$i]['g'] = '<td rowspan="'.$tmp['row_g'].'"'.$view_str.'><div class="cell part-'.$i.' col-g" data-col="'.(2+$cols_order[$i]['g']+$i*5).'">'.$group_name.'</div></td>';
						}else{
							$cols_implode[$i]['g'] = !$all_rowspan[$i]['row_g']
								? '<td'.$view_str.'><div class="cell part-'.$i.' col-g" data-col="'.(2+$cols_order[$i]['g']+$i*5).'">'.$group_name.'</div></td>'
								: '';
						}

						//кабинет
						$view_str = $parts[$i]['cols_order']['c'] ? '' : ' class="h"';
						$cabinet = isset($tmp['cabinet_id']) ? esc_html($tmp['cabinet'].$tmp['cabinet_additive'].' '.$tmp['building']) : '';
						if(isset($tmp['row_c'])){
							$all_rowspan[$i]['row_c'] = $tmp['row_c'];
							$cols_implode[$i]['c'] = '<td rowspan="'.$tmp['row_c'].'"'.$view_str.'><div class="cell part-'.$i.' col-c" data-col="'.(2+$cols_order[$i]['c']+$i*5).'">'.$cabinet.'</div></td>';
						}else{
							$cols_implode[$i]['c'] = !$all_rowspan[$i]['row_c']
								? '<td'.$view_str.'><div class="cell part-'.$i.' col-c" data-col="'.(2+$cols_order[$i]['c']+$i*5).'">'.$cabinet.'</div></td>'
								: '';
						}

						//препод
						$view_str = $parts[$i]['cols_order']['p'] ? '' : ' class="h"';
						$teacher = isset($tmp['teacher_id']) ? '<span class="teacher_fio">'.esc_html(mb_convert_case($tmp['fio'], MB_CASE_TITLE)).'</span> <span class="teacher_add">'.esc_html($tmp['teacher_additive']).'</span>' : '<span class="teacher_fio"></span><span class="teacher_add"></span>';
						if(isset($tmp['row_p'])){
							$all_rowspan[$i]['row_p'] = $tmp['row_p'];
							$cols_implode[$i]['p'] = '<td rowspan="'.$tmp['row_p'].'"'.$view_str.'><div class="cell part-'.$i.' col-p" data-col="'.(2+$cols_order[$i]['p']+$i*5).'">'.$teacher.'</div></td>';
						}else{
							$cols_implode[$i]['p'] = !$all_rowspan[$i]['row_p']
								? '<td'.$view_str.'><div class="cell part-'.$i.' col-p" data-col="'.(2+$cols_order[$i]['p']+$i*5).'">'.$teacher.'</div></td>' : '';
						}

						$html_body_cont .= implode(PHP_EOL, $cols_implode[$i]);
						
						//уменшение счетчиков объединения
						if($lesson_unite){
							foreach($all_rowspan[$i] as &$val){
								if($val > 0)
									$val--;
							}
						}
					}
					$html_body_cont .= '</tr>';
				}
			}
		}
	}
	
	//вывод
	echo '<div class="timetable-body-static"><table class="timetable-table">'.$html_body_static.'</table><div class="timetable-body-static-indent"></div></div><div class="timetable-body-cont"><table class="timetable-table">'.$html_body_cont.'</table></div>';
}

/*
 * $table будет иметь структуру 
 * [ - 1-ый уровень: ключи это номер недели из $tm_t;
 *  	[ - 2-ой уровень: ключи это номер дня из $tm_t;
 *  		[ - 3-ий уровень: ключи это номер уроков из $tm_t;
 *  			[ - 4-ый уровень: строки уроков (т.к. в одно время можт быть несколько уроков);
 *  				[ - 5-ый уровень: ключи это номера разделов содержащихся в данной строке
 *  					[ - 6-ой уровень: массив данных из запроса исключая данные о дате
 *  						...
 *  					],
 *  					...
 *  				],
 *  				...
 *  			],
 *  			...,
 * 				'date' => DateTime - дата данного дня
 *  		],
 *  		...
 *  	],
 *  	...
 * ]
 * 
 * представление для данных для одного номера уроков
 * 
 *  $part_n:  $part_0 $part_1 $part_2 $part_3 ← разделы (5-ый уровень)
 *           ┌───────┬───────┬───────┬───────┐         ─┐
 *           │   X   │   X   │   X   │   X   │  line_0  │
 * X - уроки ├───────┼───────┼───────┼───────┤          │
 *  (6-ой    │       │   X   │   X   │   X   │  line_1  │
 *  уровень) │       ├───────┼───────┼───────┤           >   номер урока (время одной пары) (3-ий уровень)
 *           │       │       │   X   │   X   │  line_2  │
 *           │       │       ├───────┼───────┤          │
 *           │       │       │   X   │       │  line_3  │
 *           │       │       ├───────┤       │    ↑    ─┘
 *                                              строки уроков (4-ый уровень)
 * 
 */
/**
 * Производит добавления данных в $table из результата SQL запроса $tm_t, для дальнейшего вывода таблицы на основе $table
 * 
 * @param array $tm_t - данные полученные из запроса для одного раздела (строка 211-231)
 * @param int $part_n - номер тегущего раздела
 * @param array $table - итоговый массив в который добавляется информация
 */
function add_data_to_timetable($tm_t, $part_n, &$table){
	$len = sizeof($tm_t);
	$f_week = -1;
	for($i=0; $i<$len; $i++){
		$tmp = $tm_t[$i];
		$week = $tmp['week'];
		//если первая неделя 2-ая, то пропускаем
		if($f_week == -1 && $week == 2){
			continue;
		//первая неделя должна быть 1-ой
		}else if($f_week == -1 && $week == 1){
			$f_week = 1;
		//за первой неделей должна следовать 2-ая неделя 
		}else if($f_week == 1 && $week == 2){
			$f_week = 2;
		//если за второй неделей следует 1-ая, то выходим из цикла
		}else if($f_week == 2 && $week == 1){
			break;
		}
		/*
		//если отсутствует 1-ая неделя, но пытается добавиться 2-ая
		if($week == 2 && !isset($table[1])){
			//пропускаем
			continue;
		//если имеется 2-ая неделя, но пытается добавиться 1-ая
		}else if($week == 1 && isset($table[2])){
			//пропускаем
			continue;
		}
		*/
		$date = DateTime::createFromFormat(DB_DATE_FORMAT, $tmp['date']);
		$day = $date->format('N') - 1;
		$time = $tmp['time'];
		//$tmp['group_id'] = isset($tmp['group_id']) ? $tmp['group_id'] : '';
		$tmp['teacher_id'] = isset($tmp['teacher_id']) ? $tmp['teacher_id'] : '';
		$tmp['cabinet_id'] = isset($tmp['cabinet_id']) ? $tmp['cabinet_id'] : '';
		unset($tmp['week'], $tmp['date'], $tmp['time']);

		if(!isset($table[$week]))
			$table[$week] = array();
		if(!isset($table[$week][$day]))
			$table[$week][$day] = array('date' => $date);
		if(!isset($table[$week][$day][$time]))
			$table[$week][$day][$time] = array();

		$line_c = sizeof($table[$week][$day][$time]);
		$f = 0;
		for($curr_line=0; $curr_line<$line_c; $curr_line++){
			if(empty($table[$week][$day][$time][$curr_line][$part_n])){
				$table[$week][$day][$time][$curr_line][$part_n] = $tmp;
				$f = 1;
				break;
			}
		}

		if(!$f){
			$table[$week][$day][$time][] = array(
				$part_n => $tmp
			);
		}
	}
}

/*
 * $table - результат работы функции add_data_to_timetable()
 * функция работает с 4-6 уровнем
 * 
 * представление для данных для одного номера уроков и одного раздела ДО обработки
 * 
 *                             part_n   ← раздел (5-ый уровень)
 *           ┌───────────────────^───────────────────┐
 * 
 *             row_l   row_t   row_g   row_c   row_p  ← данные урока (6-ой уровень)
 *           ┌───────┬───────┬───────┬───────┬───────┐         ─┐
 *           │   X   │   X   │   X   │   X   │   X   │  line_0  │
 *           ├───────┼───────┼───────┼───────┼───────┤          │
 *           │       │   X   │   Y   │   Y   │   Y   │  line_1  │
 *           ├───────┼───────┼───────┼───────┼───────┤           >   номер урока (время одной пары) (3-ий уровень)
 *           │       │       │   Y   │   Z   │   Y   │  line_2  │
 *           ├───────┼───────┼───────┼───────┼───────┤          │
 *           │       │       │   X   │       │       │  line_3  │
 *           └───────┴───────┴───────┴───────┴───────┘    ↑    ─┘
 *                                                      строки уроков (4-ый уровень)
 *                             ↓↓↓↓↓
 *                              ↓↓↓
 *                               ↓
 * 
 * представление для данных для одного номера уроков и одного раздела ПОСЛЕ обработки
 * 
 *                             part_n   ← раздел (5-ый уровень)
 *           ┌───────────────────^───────────────────┐
 * 
 *             row_l   row_t   row_g   row_c   row_p  ← данные урока (6-ой уровень)
 *           ┌───────┬───────┬───────┬───────┬───────┐         ─┐
 *           │  X,4  │  X,4  │   X   │   X   │   X   │  line_0  │
 *           │ - - - │ - - - ├───────┼───────┼───────┤          │
 *           │       │       │  Y,2  │   Y   │  Y,3  │  line_1  │
 *           │ - - - │ - - - │ - - - ├───────┤ - - - │           >   номер урока (время одной пары) (3-ий уровень)
 *           │       │       │       │  Z,2  │       │  line_2  │
 *           │ - - - │ - - - ├───────┤ - - - │ - - - │          │
 *           │       │       │   X   │       │       │  line_3  │
 *           └───────┴───────┴───────┴───────┴───────┘    ↑    ─┘
 *                                                      строки уроков (4-ый уровень)
 * 
 */
/**
 * Производит объединение неуникалных строк, удаляя дублируемые данные
 * и добавляя в уникальные строки информацию о количестве объединяемых строк 
 * @param int $part_c - количество разделов 
 * @param array $table - итоговый массив, полученный с помощью add_data_to_timetable()
 * @see add_data_to_timetable()
 */
function prepare_data_to_timetable_html($part_c, &$table){
	foreach($table as &$week){
		foreach($week as &$day){
			foreach($day as &$time){
				//пропуск даты 
				if(is_object($time))
					continue;
				$line_c = sizeof($time);
				//перебор разделов
				for($part_n=0; $part_n<$part_c; $part_n++){
					//$time[$part_n] = array_pad($time[$part_n], $line_c, array());
					$row_l = 0;
					$row_t = 0;
					$row_g = 0;
					$row_c = 0;
					$row_p = 0;
					//перебор строк, пропускаем первую строку
					for($i=1; $i<$line_c; $i++){
						/*
						 * Для каждого столбца производится проверка 
						 * если строка пуста или равна значению предыдущей строки
						 * то предыдущая строка (6-ой уровень) получает параметр 'row_x'
						 * означающий сколько строк нужно объеденить для этого столбца
						 * 
						 * в дальнейшем, при проходе по строкам если данный параметр установлен
						 * и строка пустта или равна значению отмеченной строки
						 * то данные проверяемого столбца удаляются и счетчик увеличивается
						 * 
						 * если встречается срока не удовлетворяющая данным условиям
						 * то отмеченной строкой становится текущая 
						 */
						
						//проверка урока
						if(!isset($time[$i][$part_n]['lesson_id']) || $time[$i][$part_n]['lesson_id'] === $time[$row_l][$part_n]['lesson_id']){
							unset($time[$i][$part_n]['lesson_id'], $time[$i][$part_n]['lesson']);
							if(!isset($time[$row_l][$part_n]['row_l'])){
								$time[$row_l][$part_n]['row_l'] = 1;
							}
							$time[$row_l][$part_n]['row_l']++;
						}else{
							$row_l = $i;
						}

						//проверка типа урока
						if(!isset($time[$i][$part_n]['lesson_type']) || $time[$i][$part_n]['lesson_type'] === $time[$row_t][$part_n]['lesson_type']){
							unset($time[$i][$part_n]['lesson_type']);
							if(!isset($time[$row_t][$part_n]['row_t'])){
								$time[$row_t][$part_n]['row_t'] = 1;
							}
							$time[$row_t][$part_n]['row_t']++;
						}else{
							$row_t = $i;
						}

						//проверка группы
						if(!isset($time[$i][$part_n]['group_id']) || $time[$i][$part_n]['group_id'] === $time[$row_g][$part_n]['group_id']){
							unset($time[$i][$part_n]['group_id'], $time[$i][$part_n]['group_name']);
							if(!isset($time[$row_g][$part_n]['row_g'])){
								$time[$row_g][$part_n]['row_g'] = 1;
							}
							$time[$row_g][$part_n]['row_g']++;
						}else{
							$row_g = $i;
						}

						//проверка кабинета
						if(!isset($time[$i][$part_n]['cabinet_id']) || $time[$i][$part_n]['cabinet_id'] === $time[$row_c][$part_n]['cabinet_id']){
							unset($time[$i][$part_n]['cabinet_id'], $time[$i][$part_n]['cabinet'], $time[$i][$part_n]['cabinet_additive'], $time[$i][$part_n]['building']);
							if(!isset($time[$row_c][$part_n]['row_c'])){
								$time[$row_c][$part_n]['row_c'] = 1;
							}
							$time[$row_c][$part_n]['row_c']++;
						}else{
							$row_c = $i;
						}

						//проверка препода
						if(!isset($time[$i][$part_n]['teacher_id']) || $time[$i][$part_n]['teacher_id'] === $time[$row_p][$part_n]['teacher_id']){
							unset($time[$i][$part_n]['teacher_id'], $time[$i][$part_n]['fio'], $time[$i][$part_n]['teacher_additive']);
							if(!isset($time[$row_p][$part_n]['row_p'])){
								$time[$row_p][$part_n]['row_p'] = 1;
							}
							$time[$row_p][$part_n]['row_p']++;
						}else{
							$row_p = $i;
						}
					}
				}
			}
		}
	}
}

/**
 * Из массива секунд старта и конца урока делает массив для вывода времени 
 * @param array $arr - [['time_start' => sec, 'time_end' => sec], ...]; sec - [0, SECONDS_PER_DAY]
 * @return array - ['00:00-05:17', '06:07-10:02', '22:00-24:00']
 */
function get_time_list($arr){
	$ret = array();
	$len = sizeof($arr);
	for($i=0; $i<$len; $i++){
		$ret[] = seconds_to_time($arr[$i]['time_start']).'-'.seconds_to_time($arr[$i]['time_end']);
	}
	return $ret;
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

function footer_header_data_main_page($data){
	$data['js_data']['cols_min_width'] = array(
		'number' => 25,
		'time'   => 55,
		'l' => 100,
		't' => 35,
		'g' => 50,
		'c' => 35,
		'p' => 50
	);
	$data['js_data']['max_parts_timetable'] = MAX_PARTS_TIMETABLE;
	$data['js_data']['timetable_parts_live_days'] = TIMETABLE_PARTS_LIVE_DAYS;
	
	global $COOKIE_V;
	$options = $COOKIE_V->timetable_options;
	//$parts = $COOKIE_V->timetable_parts;
	if($options['teacher_add_hide']){
		$data['tmp_styles'][] = '.timetable-body .teacher_add{display:none;}';
	}

	if(!$options['cell_word_wrap']){
		$data['tmp_styles'][] = '.timetable-body .cell{white-space:nowrap;}';
	}
	/*
	for($i=0; $i<sizeof($parts); $i++){
		foreach($parts[$i]['cols_order'] as $col_t => $view){
			if(!$view){
				$data['tmp_styles'][] = '.timetable-block .cell.part-'.$i.'.col-'.$col_t.'{display:none}';
				$data['tmp_styles'][] = '.stick[data-col-part='.$i.'][data-col-type='.$col_t.']{display:none}';
			}
		}
	}
	*/
	
	$sizes = $COOKIE_V->timetable_cols_size;
	if(!empty($sizes['number']))
		$data['tmp_styles'][] = '.timetable-block .cell.col-number{width:'.$sizes['number'].'px}';

	if(!empty($sizes['time']))
		$data['tmp_styles'][] = '.timetable-block .cell.col-time{width:'.$sizes['time'].'px}';
	
	if(isset($sizes['parts'])){
		foreach($sizes['parts'] as $part => &$col_sizes){
			foreach($col_sizes as $col_short => $val){
				$data['tmp_styles'][] = '.timetable-block .cell.part-'.$part.'.col-'.$col_short.'{width:'.$val.'px}';
			}
		}
	}
	
	return $data;
}




/**
 * TODO переделать
 * see footer.php
 * @return string
 */
function gen_additor_modal_html(){
	global $DB, $COOKIE_V;
	$opt_fac_html = '';
	$opt_gr_html = '';
	$opt_cab_html = '';
	$opt_tch_html = '';

	$parts = $COOKIE_V->timetable_parts;
	$except = array('group' => array(), 'teacher' => array(), 'cabinet' => array());
	$len = sizeof($parts);
	for($i=0; $i<$len; $i++){
		if(isset($except[$parts[$i]['type']])){
			$except[$parts[$i]['type']][] = $parts[$i]['id'];
		}
	}

	$res = $DB->getAll('SELECT `id`, `name`, `abbr` FROM `stud_faculties` ORDER BY `name`');
	$len = sizeof($res);
	for($i=0; $i<$len; $i++){
		$opt_fac_html .= '<option value="'.$res[$i]['id'].'">'.esc_html($res[$i]['name'].(empty($res[$i]['abbr']) ? '' : ' ('.$res[$i]['abbr'].')')).'</option>';
	}
	$tmp = '';

	$res = $DB->getAll('SELECT `id`, `name`, `faculty_id` FROM `stud_groups` WHERE `status` < '.PARSE_LIMIT_404.' ORDER BY `name`');
	$len = sizeof($res);
	for($i=0; $i<$len; $i++){
		if(($tmp = array_search($res[$i]['id'], $except['group'])) !== false){
			unset($except['group'][$tmp]);
			continue;
		}
		$opt_gr_html .= '<option value="'.$res[$i]['id'].'" data-faculty_id="'.$res[$i]['faculty_id'].'">'.esc_html($res[$i]['name']).'</option>';
	}
	unset($except['group']);

	$res = $DB->getAll('SELECT `id`, `cabinet`, `additive`, `building` FROM `stud_cabinets` ORDER BY `building`, `cabinet`');
	$len = sizeof($res);
	for($i=0; $i<$len; $i++){
		if(($tmp = array_search($res[$i]['id'], $except['cabinet'])) !== false){
			unset($except['cabinet'][$tmp]);
			continue;
		}
		$opt_cab_html .= '<option value="'.$res[$i]['id'].'">'.$res[$i]['cabinet'].$res[$i]['additive'].' ' .$res[$i]['building'].'</option>';
	}
	unset($except['cabinet']);

	$res = $DB->getAll('SELECT `id`, `fio`, DATE(`last_update`) as `update` FROM `stud_teachers` ORDER BY `fio`, `last_update` DESC');
	$len = sizeof($res);
	for($i=0; $i<$len; $i++){
		if(($tmp = array_search($res[$i]['id'], $except['teacher'])) !== false){
			unset($except['teacher'][$tmp]);
			continue;
		}
		$opt_tch_html .= '<option value="'.$res[$i]['id'].'">'.$res[$i]['fio'].' (доб. '.$res[$i]['update'].')</option>';
	}
	unset($except['teacher']);

	return '
<!-- Additor modal -->
<div class="modal fade" id="additor-modal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="additor-modal-title">Добавить раздел</h4>
				<button type="button" class="close" data-dismiss="modal">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="additor-carousel" class="carousel slide" data-ride="carousel" data-interval="false" data-keyboard="false">
					<div class="carousel-inner">
						<div class="item active">
							<div class="additior-item additor-buttons">
								<div class="child-center text-center">
									<h3>Добавить</h3>
									<button class="btn btn-info" type="button" data-additor="group">
										Группу
									</button>
									<button class="btn btn-info" type="button" data-additor="cabinet">
										Кабинет
									</button>
									<button class="btn btn-info" type="button" data-additor="teacher">
										Препода
									</button>
								</div>
							</div>
						</div>
						<div class="item">
							<div class="additior-item additor-block">
								<div class="group-additor-block display-none child-center text-center">
									<h3>Добавить группу</h3>
									<select name="faculty_id" data-placehorder="Факультет">
										<option value="">---</option>'.$opt_fac_html.'
									</select>
									<select name="group_id" data-placehorder="Группа">'.$opt_gr_html.'
									</select>
								</div>
								<div class="cabinet-additor-block display-none child-center text-center">
									<h3>Добавить кабинет</h3>
									<select name="cabinet_id" data-placehorder="Кабинет">'.$opt_cab_html.'
									</select>
								</div>
								<div class="teacher-additor-block display-none child-center text-center">
									<h3>Добавить препода</h3>
									<select name="teacher_id" data-placehorder="Препод">'.$opt_tch_html.'
									</select>
								</div>
							</div>
						</div>
						<div class="item">
							<div class="additior-item">
								<div class="child-center text-center">
									<h3>Название раздела</h3>
									<input type="text" class="form-control" id="additor-gr-name" maxlength="'.MAX_PART_NAME_LEN.'">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-link" data-dismiss="modal">Отмена</button>
				<button type="button" id="additor-modal-submit" class="btn btn-primary disabled" disabled="disabled">Добавить</button>
			</div>
		</div>
	</div>
</div>';
}


?>