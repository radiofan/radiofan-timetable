<?php
function gen_timetable_html(){
	global $DB, $COOKIE_V;
	/*
	 * TODO список опций
	 * Переход к текущей неделе/дню +
	 * Скрытие пустых дней/строк
	 * Перенос слов +
	 * Выделение текущих неделя/дня/пары +
	 * Добавлять плюшку препода +
	 * Скрытие столбцов
	 * Сохранение размеров столбцов и таблицы +
	 * Выгрузка загрузка кук
	 * Режим печати
	 * Дата последенего обновления
	 * Очистка БД
	 * Рабочие столы
	 * скрытие нетекущей недели
	 */
	$elems_add = $COOKIE_V->timetable_validation(1);
	$elems_add = $elems_add['elements'];
	
	$elems = $_COOKIE['timetable']['elements'];
	$elems_len = sizeof($elems);
	
	$options = $_COOKIE['timetable']['options'];
	
	$add_style = get_table_size_html($options['size']);
	unset($size_style);
	
	//скрытие звания препода
	if($options['teacher_add_hide']){
		$add_style['teacher_add_hide'] = '.timetable-body .teacher_add{display:none;}';
	}
	
	if(!$options['cell_word_wrap']){
		$add_style['cell_word_wrap'] = '.timetable-body .cell{white-space:nowrap;}';
	}
	
	$table_head_1 = '<th rowspan="2"><div class="cell col-number" data-col="1">№ пары</div></th>
		<th rowspan="2"><div class="cell col-time" data-col="2">Время</div></th>';
	$table_head_2 = '';
	$sticks = '<div class="stick" data-col="1"></div><div class="stick" data-col="2"></div>';
	
	
	$elems = array_values($elems);
	$elems_len = sizeof($elems);
	$table = array();
	for($i=0; $i<$elems_len; $i++){
		
		$elems[$i]['col_id'] = $elems[$i]['type'].'_id';
		$elems[$i]['table_name'] = 'stud_'.$elems[$i]['type'].'s';
		
		//генерация html
		for($i1 = 3; $i1 <= 7; $i1++){
			$sticks .= '<div class="stick" data-col="'.($i1+$i*5).'"></div>';
		}
		$table_head_1 .= '<th colspan="5"><div class="cell gr-'.($i+1).'">'.esc_html($elems_add[$i]['gr_name']).'</div></th>';
		$table_head_2 .= '<th><div class="cell gr-'.($i+1).' col-lesson" data-col="'.(3 + $i*5).'">Урок</div></th>
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
		add_data_to_timetable($res, $i+1, $table);
	}
	if($options['cell_rowspan'])
		prepare_data_to_timetable_html($elems_len, $table);
	
	
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
					<table class="timetable-table">'.gen_timetable_body_html($table, $elems_len, $options['cell_rowspan']).'
					</table>
				</div>
				<div class="timetable-extender"><span class="timetable-extender-marker"></span></div>
			</div>';
}

function prepare_data_to_timetable_html($gr_c, &$table){
	foreach($table as &$week){
		foreach($week as &$day){
			foreach($day as &$time){
				$line_c = sizeof($time);
				for($gr_n=1; $gr_n<=$gr_c; $gr_n++){
					//$time[$gr_n] = array_pad($time[$gr_n], $line_c, array());
					$row_la = 0;
					$row_l = 0;
					$row_g = 0;
					$row_c = 0;
					$row_t = 0;
					for($i=1; $i<$line_c; $i++){
						//проверка урока
						if(!isset($time[$i][$gr_n]['lesson']) || $time[$i][$gr_n]['lesson'] === $time[$row_l][$gr_n]['lesson']){
							unset($time[$i][$gr_n]['lesson']);
							if(!isset($time[$row_l][$gr_n]['row_l'])){
								$time[$row_l][$gr_n]['row_l'] = 1;
							}
							$time[$row_l][$gr_n]['row_l']++;
						}else{
							$row_l = $i;
						}
						
						//проверка типа урока
						if(!isset($time[$i][$gr_n]['lesson_type']) || $time[$i][$gr_n]['lesson_type'] === $time[$row_la][$gr_n]['lesson_type']){
							unset($time[$i][$gr_n]['lesson_type']);
							if(!isset($time[$row_la][$gr_n]['row_la'])){
								$time[$row_la][$gr_n]['row_la'] = 1;
							}
							$time[$row_la][$gr_n]['row_la']++;
						}else{
							$row_la = $i;
						}
						
						//проверка группы
						if(!isset($time[$i][$gr_n]['group_id']) || $time[$i][$gr_n]['group_id'] === $time[$row_g][$gr_n]['group_id']){
							unset($time[$i][$gr_n]['group_id'], $time[$i][$gr_n]['group_name']);
							if(!isset($time[$row_g][$gr_n]['row_g'])){
								$time[$row_g][$gr_n]['row_g'] = 1;
							}
							$time[$row_g][$gr_n]['row_g']++;
						}else{
							$row_g = $i;
						}
						
						//проверка кабинета
						if(!isset($time[$i][$gr_n]['cabinet_id']) || $time[$i][$gr_n]['cabinet_id'] === $time[$row_c][$gr_n]['cabinet_id']){
							unset($time[$i][$gr_n]['cabinet_id'], $time[$i][$gr_n]['cabinet'], $time[$i][$gr_n]['cabinet_additive'], $time[$i][$gr_n]['building']);
							if(!isset($time[$row_c][$gr_n]['row_c'])){
								$time[$row_c][$gr_n]['row_c'] = 1;
							}
							$time[$row_c][$gr_n]['row_c']++;
						}else{
							$row_c = $i;
						}
						
						//проверка препода
						if(!isset($time[$i][$gr_n]['teacher_id']) || $time[$i][$gr_n]['teacher_id'] === $time[$row_t][$gr_n]['teacher_id']){
							unset($time[$i][$gr_n]['teacher_id'], $time[$i][$gr_n]['fio'], $time[$i][$gr_n]['teacher_additive']);
							if(!isset($time[$row_t][$gr_n]['row_t'])){
								$time[$row_t][$gr_n]['row_t'] = 1;
							}
							$time[$row_t][$gr_n]['row_t']++;
						}else{
							$row_t = $i;
						}
					}
				}
			}
		}
	}
}

function add_data_to_timetable($tm_t, $gr_n, &$table){
	$len = sizeof($tm_t);
	for($i=0; $i<$len; $i++){
		$week = $tm_t[$i]['week'];
		$day = $tm_t[$i]['day'];
		$time = $tm_t[$i]['time'];
		$tm_t[$i]['group_id'] = isset($tm_t[$i]['group_id']) ? $tm_t[$i]['group_id'] : '';
		$tm_t[$i]['teacher_id'] = isset($tm_t[$i]['teacher_id']) ? $tm_t[$i]['teacher_id'] : '';
		$tm_t[$i]['cabinet_id'] = isset($tm_t[$i]['cabinet_id']) ? $tm_t[$i]['cabinet_id'] : '';
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
			if(empty($table[$week][$day][$time][$i1][$gr_n])){
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

function gen_timetable_body_html($table, $elems_len, $cell_rowspan = 1){
	global $DATA;
	$html_table = '';
	$week_days = array('понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье');
	for($i=0; $i<7; $i++){
		$week_days[$i] = mb_convert_case($week_days[$i], MB_CASE_TITLE);
	}
	//(8:15)|(9:55)|(11:35)|(13:35)|(15:15)|(16:55)|(18:35)|(20:15)
	$time_list = array('', '08:15-09:45', '09:55-11:25', '11:35-13:05', '13:35-15:05', '15:15-16:45', '16:55-18:25', '18:35-20:05', '20:15-21:45');
	$timestamp_list = array(
		array(
			'start' => 29100,//8*3600 + 5*60
			'end' => 35100,//9*3600 + 45*60
		),
		array(
			'start' => 35100,//9*3600 + 45*60
			'end' => 41100,//11*3600 + 25*60
		),
		array(
			'start' => 41100,//11*3600 + 25*60
			'end' => 47100,//13*3600 + 5*60
		),
		array(
			'start' => 48300,//13*3600 + 25*60
			'end' => 54300,//15*3600 + 5*60
		),
		array(
			'start' => 54300,//15*3600 + 5*60
			'end' => 60300,//16*3600 + 45*60
		),
		array(
			'start' => 60300,//16*3600 + 45*60
			'end' => 66300,//18*3600 + 25*60
		),
		array(
			'start' => 66300,//18*3600 + 25*60
			'end' => 72300,//20*3600 + 5*60
		),
		array(
			'start' => 72300,//20*3600 + 5*60
			'end' => 78300,//21*3600 + 45*60
		)
	);
	$today = new DateTime();
	$f_week_day = $DATA->get('first_week_day');
	
	$curr_week = 0;
	if($f_week_day){
		$curr_week = (new DateTime())->setTime(0, 0)->getTimestamp() - $f_week_day->getTimestamp();
		$curr_week = ($curr_week / 86400) % 14;
		$curr_week = (int)($curr_week / 7) + 1;
	}
	
	$curr_less = (int)$today->format('G')*3600 + (int)$today->format('i')*60 + (int)$today->format('s');
	$len = sizeof($timestamp_list);
	for($i=0; $i < $len; $i++){
		if($curr_less < $timestamp_list[$i]['start']){
			$curr_less = 0;
			break;
		}
		if($timestamp_list[$i]['start'] <= $curr_less && $timestamp_list[$i]['end'] > $curr_less){
			$curr_less = $i+1;
			break;
		}
		if($i == $len-1){
			$curr_less = 0;
		}
	}
	
	$curr_day = (int) $today->format('N') - 1;
	
	
	for($week = 1; $week<=2; $week++){
		$week_class = $curr_week == $week ? ' curr-week-row' : '';
		$html_table .= '
						<tr class="clean-row row'.$week_class.'">
							<td colspan="'.(2+$elems_len*5).'"><div class="cell week">Неделя '.$week.'</div></td>
						</tr>';
		for($day=0; $day<6; $day++){
			$today_class = $curr_day == $day && $curr_week == $week ? ' today-row' : $week_class;
			$html_table .= '
						<tr class="clean-row row'.$today_class.'">
							<td colspan="'.(2+$elems_len*5).'"><div class="cell day">'.$week_days[$day].'</div></td>
						</tr>';
			for($less=1; $less<=8; $less++){
				$less_class = $curr_less == $less && $curr_day == $day && $curr_week == $week ? ' curr-lesson-row' : $today_class;
				$len = isset($table[$week][$day][$less]) ? sizeof($table[$week][$day][$less]) : 1;
				$all_rowspan = array_fill(1, $elems_len, array(
					'row_l' => 0,
					'row_la' => 0,
					'row_g' => 0,
					'row_c' => 0,
					'row_t' => 0
				));
				for($line=0; $line<$len; $line++){
					$html_table .= '
						<tr class="default-row row'.$less_class.'">';
					if($cell_rowspan){
						if(!$line){
							$rowspan = $len > 1 ? ' rowspan ="'.$len.'"' : '';
							$html_table .= '
							<td'.$rowspan.'><div class="cell col-number" data-col="1">'.$less.'</div></td>
							<td'.$rowspan.'><div class="cell col-time" data-col="2">'.$time_list[$less].'</div></td>';
						}
					}else{
						$html_table .= '
							<td><div class="cell col-number" data-col="1">'.$less.'</div></td>
							<td><div class="cell col-time" data-col="2">'.$time_list[$less].'</div></td>';
					}
					for($i=1; $i<=$elems_len; $i++){
						$tmp = isset($table[$week][$day][$less][$line][$i]) ? $table[$week][$day][$less][$line][$i] : array();
						
						$lesson = isset($tmp['lesson']) ? mb_strtoupper($tmp['lesson']) : '';
						if(isset($tmp['row_l'])){
							$all_rowspan[$i]['row_l'] = $tmp['row_l'];
							$lesson = '
							<td rowspan="'.$tmp['row_l'].'"><div class="cell gr-'.$i.' col-lesson" data-col="'.(3+($i-1)*5).'">'.$lesson.'</div></td>';
						}else{
							$lesson = !$all_rowspan[$i]['row_l'] ? '
							<td><div class="cell gr-'.$i.' col-lesson" data-col="'.(3+($i-1)*5).'">'.$lesson.'</div></td>' : '';
						}
						
						$lesson_add = isset($tmp['lesson_type']) ? $tmp['lesson_type'] : '';
						if(isset($tmp['row_la'])){
							$all_rowspan[$i]['row_la'] = $tmp['row_la'];
							$lesson_add = '
							<td rowspan="'.$tmp['row_la'].'"><div class="cell gr-'.$i.' col-lesson-add" data-col="'.(4+($i-1)*5).'">'.$lesson_add.'</div></td>';
						}else{
							$lesson_add = !$all_rowspan[$i]['row_la'] ? '
							<td><div class="cell gr-'.$i.' col-lesson-add" data-col="'.(4+($i-1)*5).'">'.$lesson_add.'</div></td>' : '';
						}
						
						$group_name = isset($tmp['group_name']) ? $tmp['group_name'] : '';
						if(isset($tmp['row_g'])){
							$all_rowspan[$i]['row_g'] = $tmp['row_g'];
							$group_name = '
							<td rowspan="'.$tmp['row_g'].'"><div class="cell gr-'.$i.' col-group" data-col="'.(5+($i-1)*5).'">'.$group_name.'</div></td>';
						}else{
							$group_name = !$all_rowspan[$i]['row_g'] ? '
							<td><div class="cell gr-'.$i.' col-group" data-col="'.(5+($i-1)*5).'">'.$group_name.'</div></td>' : '';
						}
						
						$cabinet = isset($tmp['cabinet_id']) ? $tmp['cabinet'].$tmp['cabinet_additive'].' '.$tmp['building'] : '';
						if(isset($tmp['row_c'])){
							$all_rowspan[$i]['row_c'] = $tmp['row_c'];
							$cabinet = '
							<td rowspan="'.$tmp['row_c'].'"><div class="cell gr-'.$i.' col-cabinet" data-col="'.(6+($i-1)*5).'">'.$cabinet.'</div></td>';
						}else{
							$cabinet = !$all_rowspan[$i]['row_c'] ? '
							<td><div class="cell gr-'.$i.' col-cabinet" data-col="'.(6+($i-1)*5).'">'.$cabinet.'</div></td>' : '';
						}
						
						$teacher = isset($tmp['teacher_id']) ? '<span class="teacher_fio">'.mb_convert_case($tmp['fio'], MB_CASE_TITLE).'</span> <span class="teacher_add">'.$tmp['teacher_additive'].'</span>' : '<span class="teacher_fio"></span><span class="teacher_add"></span>';
						if(isset($tmp['row_t'])){
							$all_rowspan[$i]['row_t'] = $tmp['row_t'];
							$teacher = '
							<td rowspan="'.$tmp['row_t'].'"><div class="cell gr-'.$i.' col-teacher" data-col="'.(7+($i-1)*5).'">'.$teacher.'</div></td>';
						}else{
							$teacher = !$all_rowspan[$i]['row_t'] ? '
							<td><div class="cell gr-'.$i.' col-teacher" data-col="'.(7+($i-1)*5).'">'.$teacher.'</div></td>' : '';
						}
						$html_table .= $lesson.$lesson_add.$group_name.$cabinet.$teacher;
						if($cell_rowspan){
							foreach($all_rowspan[$i] as &$val){
								if($val > 0)
									$val--;
							}
						}
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

/**
 *
 * see footer.php
 * @return string
 */
function gen_additor_modal_html(){
	global $DB;
	$opt_fac_html = '';
	$opt_gr_html = '';
	$opt_cab_html = '';
	$opt_tch_html = '';
	
	//TODO добавить нулевки
	
	$elems = $_COOKIE['timetable']['elements'];
	$except = array('group' => array(), 'teacher' => array(), 'cabinet' => array());
	$len = sizeof($elems);
	for($i=0; $i<$len; $i++){
		if(isset($except[$elems[$i]['type']])){
			$except[$elems[$i]['type']][] = $elems[$i]['id'];
		}
	}
	
	$res = $DB->getAll('SELECT `id`, `name`, `abbr` FROM `stud_faculties` ORDER BY `name`');
	$len = sizeof($res);
	for($i=0; $i<$len; $i++){
		$opt_fac_html .= '<option value="'.$res[$i]['id'].'">'.esc_html($res[$i]['name']).(empty($res[$i]['abbr']) ? '' : ' ('.$res[$i]['abbr'].')').'</option>';
	}
	$tmp = '';
	
	$res = $DB->getAll('SELECT `id`, `name`, `faculty_id` FROM `stud_groups` ORDER BY `name`');
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
	
	$res = $DB->getAll('SELECT `id`, `fio` FROM `stud_teachers` ORDER BY `fio`');
	$len = sizeof($res);
	for($i=0; $i<$len; $i++){
		if(($tmp = array_search($res[$i]['id'], $except['teacher'])) !== false){
			unset($except['teacher'][$tmp]);
			continue;
		}
		$opt_tch_html .= '<option value="'.$res[$i]['id'].'">'.$res[$i]['fio'].'</option>';
	}
	unset($except['teacher']);
	
	return '
<!-- Additor modal -->
<div class="modal fade" id="additor-modal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="main-modal-title">Добавить раздел</h4>
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
									<input type="text" class="form-control" id="additor-gr-name" maxlength="'.MAX_SECTION_NAME_LEN.'">
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

function gen_settings_block_html(){
	global $COOKIE_V;
	$options = $_COOKIE['timetable']['options'];
	$elems = $_COOKIE['timetable']['elements'];
	$elems_type = $COOKIE_V->timetable_validation();
	$elems_type = $elems_type['elements'];
	$elems_len = sizeof($elems);
	$sections_html = '';
	for($i=0; $i<$elems_len; $i++){
		$sections_html .= '
							<div class="section-wrap" data-gr="'.($i+1).'">
								<div class="section-header">
									<span class="section-draggable gr-drag" title="Сортировать">&#8645;</span>
									<span class="section-data">
										<span class="section-type" data-toggle="collapse" data-target="#section-gr-'.($i+1).'" aria-expanded="false" aria-controls="section-gr-'.($i+1).'">'.$elems_type[$i]['html_type'].'</span> - <input style="display:inline-block;width:auto;" type="text" maxlength="'.MAX_SECTION_NAME_LEN.'" value="'.(isset($elems[$i]['gr_name']) ? htmlspecialchars($elems[$i]['gr_name']) : '').'" class="form-control edit-section-name" data-gr="'.($i+1).'">
									</span>
									<span class="section-delete" title="Удалить">&#10007;</span>
								</div>
								<div class="section-cols-wrap collapse" id="section-gr-'.($i+1).'">
									<div class="section-col col-lesson" data-pos="" data-col-type="lesson">
										<span class="section-draggable col-drag" title="Сортировать">&#8645;</span>
										<span class="child-center">Урок</span>
										<span class="section-showhide" title="Видимость"><input type="checkbox"></span>
									</div>
									<div class="section-col col-lesson-add" data-pos="" data-col-type="lesson-add">
										<span class="section-draggable col-drag" title="Сортировать">&#8645;</span>
										<span class="child-center">Тип</span>
										<span class="section-showhide" title="Видимость"><input type="checkbox"></span>
									</div>
									<div class="section-col col-group" data-pos="" data-col-type="group">
										<span class="section-draggable col-drag" title="Сортировать">&#8645;</span>
										<span class="child-center">Группа</span>
										<span class="section-showhide" title="Видимость"><input type="checkbox"></span>
									</div>
									<div class="section-col col-cabinet" data-pos="" data-col-type="cabinet">
										<span class="section-draggable col-drag" title="Сортировать">&#8645;</span>
										<span class="child-center">Кабинет</span>
										<span class="section-showhide" title="Видимость"><input type="checkbox"></span>
									</div>
									<div class="section-col col-teacher" data-pos="" data-col-type="teacher">
										<span class="section-draggable col-drag" title="Сортировать">&#8645;</span>
										<span class="child-center">Препод</span>
										<span class="section-showhide" title="Видимость"><input type="checkbox"></span>
									</div>
								</div>
							</div>';
	}
	
	$checked = array('', ' checked="checked"');
	
	return '
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
							<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#sections_collapse" aria-expanded="false" aria-controls="sections_collapse">
								Управлять разделами
							</button>
						</div>
					</div>
					<div id="settings_collapse" class="collapse settings-collapse">
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
									<input type="radio" name="settings[go2curr_day]" value="0" data-settings="go2curr_day"'.($options['go2curr_day'] == 0 ? $checked[1] : '').'>
									<span>Нет</span>
								</label>
								<label>
									<input type="radio" name="settings[go2curr_day]" value="1" data-settings="go2curr_day"'.($options['go2curr_day'] == 1 ? $checked[1] : '').'>
									<span>Неделе</span>
								</label>
								<label>
									<input type="radio" name="settings[go2curr_day]" value="2" data-settings="go2curr_day"'.($options['go2curr_day'] == 2 ? $checked[1] : '').'>
									<span>Дню</span>
								</label>
							</div>
							<hr>
							<div class="setting-row settings-cell_word_wrap">
								<label>
									<span>Переносить слова</span>
									<input type="checkbox" name="settings[cell_word_wrap]" value="1" data-settings="cell_word_wrap"'.$checked[$options['cell_word_wrap']].'>
								</label>
							</div>
							<hr>
							<div class="setting-row settings-cell_rowspan">
								<label>
									<span>Объединять уроки</span>
									<input type="checkbox" name="settings[cell_rowspan]" value="1" data-settings="cell_rowspan"'.$checked[$options['cell_rowspan']].'>
								</label>
							</div>
						</div>
					</div>
					<div id="sections_collapse" class="collapse settings-collapse">
						<div class="card-body">'.$sections_html.'
						</div>
					</div>
				</div>
			</div>';
}
?>