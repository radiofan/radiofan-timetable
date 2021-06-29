<?php
function gen_timetable_html(){
	global $DB;
	/*
	 * TODO список опций
	 * Переход к текущей неделе/дню
	 * Скрытие пустых дней/строк
	 * Выделение текущих неделя/дня/пары
	 * Добавлять плюшку препода +
	 * Скрытие столбцов
	 * Сохранение размеров столбцов и таблицы +
	 */
	$elems = isset($_COOKIE['timetable']['elements']) ? array_values($_COOKIE['timetable']['elements']) : array();
	$elems_len = sizeof($elems);
	if($elems_len > MAX_ELEMENTS_TIMETABLE){
		//TODO вывод сообщений
		@setcookie('timetable[elements]', array(), time()+86400*30);
		$elems = array();
		$elems_len = 0;
	}
	$options = isset($_COOKIE['timetable']['options']) ? $_COOKIE['timetable']['options'] : array();
	$size_style = isset($options['size']) && is_array($options['size']) ? $options['size'] : array();
	if(sizeof($size_style) > MAX_ELEMENTS_TIMETABLE*5 + 3){
		//TODO вывод сообщений
		@setcookie('timetable[options][size]', array(), time()+86400*30);
		$size_style = array();
	}
	$add_style = get_table_size_html($size_style);
	unset($size_style);
	if(isset($options['teacher_add_hide']) && $options['teacher_add_hide']){
		$add_style['teacher_add_hide'] = '.timetable-body .teacher_add{display:none;}';
	}
	
	
	$uniq = array();
	
	for($i=0; $i<$elems_len; $i++){
		if(!isset($elems[$i]['type'], $elems[$i]['id'])){
			unset($elems[$i]);
			continue;
		}
		$elems[$i]['type'] = mb_strtolower(trim($elems[$i]['type']));
		switch($elems[$i]['type']){
			case 'cabinet':
			case 'group':
			case 'teacher':
				$elems[$i]['col_id'] = $elems[$i]['type'].'_id';
				$elems[$i]['table_name'] = 'stud_'.$elems[$i]['type'].'s';
				$elems[$i]['gr_name'] = empty($elems[$i]['gr_name']) ? false : trim($elems[$i]['gr_name']);
				break;
			default:
				$elems[$i]['type'] = false;
		}
		if(!$elems[$i]['type']){
			unset($elems[$i]);
			continue;
		}
		
		$elems[$i]['id'] = preg_replace('#[^0-9]#u', '', $elems[$i]['id']);
		if(isset($uniq[$elems[$i]['type'].$elems[$i]['id']])){
			unset($elems[$i]);
			continue;
		}
		
		$dat = '';
		//TODO проверить id группы
		if((int) $elems[$i]['id'] != 0 && !($dat = $DB->getRow('SELECT * FROM ?n WHERE `id` = ?s', $elems[$i]['table_name'], $elems[$i]['id']))){
			unset($elems[$i]);
		}
		if($elems[$i]['gr_name'] === '' || $elems[$i]['gr_name'] === false){
			switch($elems[$i]['type']){
				case 'cabinet':
					$elems[$i]['gr_name'] = $elems[$i]['id'] ? $dat['cabinet'].$dat['cabinet_additive'].' '.$dat['building'] : 'Без кабинета';
					break;
				case 'group':
					$elems[$i]['gr_name'] = $dat['name'];
					break;
				case 'teacher':
					$elems[$i]['gr_name'] = $elems[$i]['id'] ? mb_convert_case($dat['fio'], MB_CASE_TITLE) : 'Без учителя';
					break;
			}
		}
		$uniq[$elems[$i]['type'].$elems[$i]['id']] = false;
	}
	unset($uniq);
	
	$table_head_1 = '
									<th rowspan="2"><div class="cell col-number" data-col="1">№ пары</div></th>
									<th rowspan="2"><div class="cell col-time" data-col="2">Время</div></th>';
	$table_head_2 = '';
	$sticks = '
					<div class="stick" data-col="1"></div>
					<div class="stick" data-col="2"></div>';
	
	
	$elems =array_values($elems);
	$elems_len = sizeof($elems);
	$table = array();
	for($i=0; $i<$elems_len; $i++){
		//генерация html
		for($i1 = 3; $i1 <= 7; $i1++){
			$sticks .= '
					<div class="stick" data-col="'.($i1+$i*5).'"></div>';
		}
		$table_head_1 .= '
									<th colspan="5"><div class="cell gr-'.($i+1).'">'.esc_html($elems[$i]['gr_name']).'</div></th>';
		$table_head_2 .= '
									<th><div class="cell gr-'.($i+1).' col-lesson" data-col="'.(3 + $i*5).'">Урок</div></th>
									<th><div class="cell gr-'.($i+1).' col-lesson-add" data-col="'.(4 + $i*5).'">Тип</div></th>
									<th><div class="cell gr-'.($i+1).' col-group" data-col="'.(5 + $i*5).'">Группа</div></th>
									<th><div class="cell gr-'.($i+1).' col-cabinet" data-col="'.(6 + $i*5).'">Кабинет</div></th>
									<th><div class="cell gr-'.($i+1).' col-teacher" data-col="'.(7 + $i*5).'">Препод</div></th>';
		
		//генерация данных
		
		/*
SELECT
	`tm_t`.*,
	`gr_t`.`name` AS `group_name`, `gr_t`.`faculty_id`,
	`cb_t`.`cabinet`, `cb_t`.`additive` AS `cabinet_additive`, `cb_t`.`building`,
    `th_t`.`fio`, `th_t`.`additive` AS `teacher_additive`
FROM
	`stud_timetable` AS `tm_t`
LEFT JOIN `stud_groups` AS `gr_t`
	ON `tm_t`.`group_id` = `gr_t`.`id`
LEFT JOIN `stud_cabinets` AS `cb_t`
	ON `tm_t`.`cabinet_id` = `cb_t`.`id`
LEFT JOIN `stud_teachers` AS `th_t`
	ON `tm_t`.`teacher_id` = `th_t`.`id`

WHERE `tm_t`.`group_id` = '0200013857'

ORDER BY `tm_t`.`week`, `tm_t`.`day`, `tm_t`.`time`
		 */
		$query = array(
			'`tm_t`.*',
			'`gr_t`.`name` AS `group_name`, `gr_t`.`faculty_id`',
			'`cb_t`.`cabinet`, `cb_t`.`additive` AS `cabinet_additive`, `cb_t`.`building`',
			'`th_t`.`fio`, `th_t`.`additive` AS `teacher_additive`'
		);
		/*
		switch($elems[$i]['type']){
			case 'cabinet':
				unset($query[2]);
				break;
			case 'group':
				unset($query[1]);
				break;
			case 'teacher':
				unset($query[3]);
				break;
		}
		*/
		$res = $DB->getAll(
			'SELECT ?p
FROM
	`stud_timetable` AS `tm_t`
LEFT JOIN `stud_groups` AS `gr_t`
	ON `tm_t`.`group_id` = `gr_t`.`id`
LEFT JOIN `stud_cabinets` AS `cb_t`
	ON `tm_t`.`cabinet_id` = `cb_t`.`id`
LEFT JOIN `stud_teachers` AS `th_t`
	ON `tm_t`.`teacher_id` = `th_t`.`id`
WHERE
    `tm_t`.?n = ?s
ORDER BY
	`tm_t`.`week`, `tm_t`.`day`, `tm_t`.`time`',
			implode(', ', $query),
			$elems[$i]['col_id'],
			$elems[$i]['id']
		);
		prepare_timetable_for_html($res, $i+1, $table);
	}
	
	return gen_settings_block_html().'
			<div class="timetable-block">
				'.(sizeof($add_style) ? '<style>'.implode(' ', $add_style).'</style>' : '').'
				<div class="sticks">'.$sticks.'
				</div>
				<div class="timetable-head">
					<div class="timetable-head-wrap">
						<table class="timetable-table">
							<thead>
								<tr>'.$table_head_1.'
								</tr>
								<tr>'.$table_head_2.'
								</tr>
							</thead>
						</table>
					</div>
				</div>
				<div class="timetable-body">
					<table class="timetable-table">'.gen_timetable_body_html($table, $elems_len).'
					</table>
				</div>
				<div class="timetable-extender"><span class="timetable-extender-marker"></span></div>
			</div>';
}

function prepare_timetable_for_html($tm_t, $gr_n, &$table){
	$len = sizeof($tm_t);
	for($i=0; $i<$len; $i++){
		$week = $tm_t[$i]['week'];
		$day = $tm_t[$i]['day'];
		$time = $tm_t[$i]['time'];
		unset($tm_t[$i]['week'], $tm_t[$i]['day'], $tm_t[$i]['time']);
		
		if(!isset($table[$week]))
			$table[$week] = array();
		if(!isset($table[$week][$day]))
			$table[$week][$day] = array();
		if(!isset($table[$week][$day][$time]))
			$table[$week][$day][$time] = array();
		
		$line_c = sizeof($table[$week][$day][$time]);
		$f = 0;
		for($i1=0; $i1<$line_c; $i1++){
			if(!isset($table[$week][$day][$time][$i1][$gr_n])){
				$table[$week][$day][$time][$i1][$gr_n] = $tm_t[$i];
				$f = 1;
				break;
			}
		}
		
		if(!$f){
			$table[$week][$day][$time][] = array(
				$gr_n => $tm_t[$i]
			);
		}
	}
}

function gen_timetable_body_html($table, $elems_len){
	$html_table = '';
	$week_days = array('понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье');
	for($i=0; $i<7; $i++){
		$week_days[$i] = mb_convert_case($week_days[$i], MB_CASE_TITLE);
	}
	//(8:15)|(9:55)|(11:35)|(13:35)|(15:15)|(16:55)|(18:35)|(20:15)
	$time_list = array('', '08:15-09:45', '09:55-11:25', '11:35-13:05', '13:35-15:05', '15:15-16:45', '16:55-18:25', '18:35-20:05', '20:15-21:45');
	
	for($week = 1; $week<=2; $week++){
		$html_table .= '
						<tr class="clean-row row">
							<td colspan="'.(2+$elems_len*5).'"><div class="cell week">Неделя '.$week.'</div></td>
						</tr>';
		for($day=0; $day<6; $day++){
			$html_table .= '
						<tr class="clean-row row">
							<td colspan="'.(2+$elems_len*5).'"><div class="cell day">'.$week_days[$day].'</div></td>
						</tr>';
			for($less=1; $less<=8; $less++){
				$len = isset($table[$week][$day][$less]) ? sizeof($table[$week][$day][$less]) : 1;
				for($line=0; $line<$len; $line++){
					$html_table .= '
						<tr class="default-row row">
							<td><div class="cell col-number" data-col="1">'.$less.'</div></td>
							<td><div class="cell col-time" data-col="2">'.$time_list[$less].'</div></td>';
					for($i = 1; $i <= $elems_len; $i++){
						$tmp = isset($table[$week][$day][$less][$line][$i]) ? $table[$week][$day][$less][$line][$i] : array();
						$lesson = isset($tmp['lesson']) ? $tmp['lesson'] : '';
						$lesson_add = isset($tmp['lesson_type']) ? $tmp['lesson_type'] : '';
						$group_name = isset($tmp['group_name']) ? $tmp['group_name'] : '';
						$cabinet = isset($tmp['cabinet_id']) ? $tmp['cabinet'].$tmp['cabinet_additive'].' '.$tmp['building'] : '';
						$teacher = isset($tmp['teacher_id']) ? '<span class="teacher_fio">'.mb_convert_case($tmp['fio'], MB_CASE_TITLE).'</span> <span class="teacher_add">'.$tmp['teacher_additive'].'</span>' : '<span class="teacher_fio"></span><span class="teacher_add"></span>';
						$html_table .= '
							<td><div class="cell gr-'.$i.' col-lesson" data-col="'.(3+($i-1)*5).'">'.$lesson.'</div></td>
							<td><div class="cell gr-'.$i.' col-lesson-add" data-col="'.(4+($i-1)*5).'">'.$lesson_add.'</div></td>
							<td><div class="cell gr-'.$i.' col-group" data-col="'.(5+($i-1)*5).'">'.$group_name.'</div></td>
							<td><div class="cell gr-'.$i.' col-cabinet" data-col="'.(6+($i-1)*5).'">'.$cabinet.'</div></td>
							<td><div class="cell gr-'.$i.' col-teacher" data-col="'.(7+($i-1)*5).'">'.$teacher.'</div></td>';
					}
					$html_table .= '
						</tr>';
				}
			}
		}
	}
	return $html_table;
}

function get_table_size_html($width){
	$max_width = array(
		0,
		25,
		55,
		100,
		35,
		50,
		35,
		50
	);
	$height = '';
	foreach($width as $key => $value){
		if($key == 'height'){
			$value = absint($value);
			$height = '.timetable-body{height:'.$value.'px}';
			unset($width[$key]);
		}else{
			$col = absint($key);
			$value = absint($value);
			unset($width[$key]);
			if($col < 1 || $col > MAX_ELEMENTS_TIMETABLE * 5 + 2)
				continue;
			$key = $col <= 2 ? $col : ($col - 3) % 5 + 3;
			if($value < $max_width[$key] || $value > 500)
				continue;
			$width[$col] = '.timetable-block .cell[data-col="'.$col.'"]{width:'.$value.'px}';
		}
	}
	ksort($width, SORT_NUMERIC);
	$width['height'] = $height;
	return $width;
}

function gen_settings_block_html(){
	$options = isset($_COOKIE['timetable']['options']) ? $_COOKIE['timetable']['options'] : array();
	
	$checked = array('', ' checked="checked"');
	if(isset($options['teacher_add_hide']) && $options['teacher_add_hide']){
		$options['teacher_add_hide'] = 1;
	}else{
		$options['teacher_add_hide'] = 0;
	}
	return '
			<div class="settings">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0">
							<button class="btn btn-link btn-block" style="text-align:left;" type="button" data-toggle="collapse" data-target="#settings_collapse" aria-expanded="false" aria-controls="settings_collapse">
								Настройки
							</button>
						</h5>
					</div>
					<div id="settings_collapse" class="collapse" aria-labelledby="settings_collapse">
						<div class="card-body">
							<div class="setting-row settings-teacher_add_hide">
								<label>
									<span>Скрыть звание препода</span>
									<input type="checkbox" name="settings[teacher_add_hide]" value="1" data-settings="teacher_add_hide"'.$checked[$options['teacher_add_hide']].'>
								</label>
							</div>
							<hr>
							<div class="setting-row settings-go2curr_day">
								Идти к текущ(ей/ему)
								<label>
									<input type="radio" name="settings[go2curr_day]" value="0" data-settings="go2curr_day" checked="checked">
									<span>Нет</span>
								</label>
								<label>
									<input type="radio" name="settings[go2curr_day]" value="week" data-settings="go2curr_day">
									<span>Неделе</span>
								</label>
								<label>
									<input type="radio" name="settings[go2curr_day]" value="day" data-settings="go2curr_day">
									<span>Дню</span>
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>';
}
?>