jQuery(document).ready(function($){
	var $table = $('.timetable-block'),
		cols = {
			'number':[],
			'time'  :[],
			'parts' :{}
		},
		table_rows = {'static':[], 'cont':[]},
		sticks = [],
		$table_body_static,
		$table_body_cont,
		$table_head,
		$table_sticks,
		$sections = $('#parts_collapse').children('.card-body'),
		cookie_parts = [],
		$body = $('body');

	$table_body_static = $table.children('.timetable-body').children('.timetable-body-static');
	$table_body_cont = $table.children('.timetable-body').children('.timetable-body-cont');
	$table_head = $table.children('.timetable-head').children('.timetable-head-cont').children('.timetable-head-wrap');
	$table_sticks = $table.children('.sticks');

	//считывает данные из куки для каждого раздела
	let tmp;
	for(let i=0; i<DATA.max_parts_timetable; i++){
		tmp = $.cookie('tmt[p]['+i+']', undefined, {'array':1});
		if(!tmp)
			break;
		cookie_parts.push(tmp);
	}

	//генерирование массива столбцов
	tmp = $table.find('.cell[class *= "col-"]');
	let len = tmp.length;
	for(let i=0; i<len; i++){
		let classes = tmp[i].classList,
			part_n = null,
			col_t = null;
		for(let i=0; i<classes.length; i++){
			if(part_n === null)
				part_n = classes[i].match(/^part-([0-9]+)$/);
			if(col_t === null)
				col_t = classes[i].match(/^col-([a-z]+)$/);
		}
		if(col_t === null){
			delete tmp[i];
			continue;
		}else{
			part_n = part_n===null ? -1 : +part_n[1];
			col_t = col_t[1];
		}

		tmp[i] = $(tmp[i]);
		if(col_t === 'number'){
			cols.number.push(tmp[i]);
		}else if(col_t === 'time'){
			cols.time.push(tmp[i]);
		}else{
			if(cols.parts.hasOwnProperty(part_n)){
				if(cols.parts[part_n].hasOwnProperty(col_t)){
					cols.parts[part_n][col_t].push(tmp[i]);
				}else{
					cols.parts[part_n][col_t] = [tmp[i]];
				}
			}else{
				cols.parts[part_n] = {};
				cols.parts[part_n][col_t] = [tmp[i]]
			}
		}
		delete tmp[i];
	}
	
	//генерирование массива строк
	tmp = $table_body_static.find('tr[data-ind]');
	len = tmp.length;
	for(let i=0; i<len; i++){
		table_rows.static.push($(tmp[i]));
		delete tmp[i];
	}
	tmp = $table_body_cont.find('tr[data-ind]');
	len = tmp.length;
	for(let i=0; i<len; i++){
		table_rows.cont.push($(tmp[i]));
		delete tmp[i];
	}

	//генерирование массива палок
	tmp = $table_sticks.children('.stick');
	len = tmp.length;
	for(let i=0; i<len; i++){
		tmp[i] = $(tmp[i]);
		sticks.push(tmp[i]);
		delete tmp[i];
	}

	//TODO добавить скролл при наведении на палку

	//прокручивание заголовка
	$table_body_cont.on('scroll.table', function(e){
		//console.log($table_body.scrollLeft());
		$table_head.css('left', (-$table_body_cont.scrollLeft())+'px');
		set_sticks();
		$table_body_static.scrollTop($table_body_cont.scrollTop());
	});

	//флаги работы с размерами таблицы
	var curr_stick = false;
	//начало изменения ширины столбца
	$table_sticks.on('mousedown.table', '.stick', function(e){
		if(curr_stick === false){
			e.preventDefault();
			curr_stick = $(this).data('col');
			sticks[curr_stick].addClass('hover').data('old-pos', sticks[curr_stick].offset().left);
			$table.css('cursor', 'col-resize');
		}
	});
	
	//конец и продолжение изменения размера столбца
	$body.on({
		'mouseup.table': function(e){
			if(curr_stick !== false){
				let diff_w = sticks[curr_stick].offset().left - sticks[curr_stick].data('old-pos'),
					width = 0,
					col_t = sticks[curr_stick].data('colType'),
					part_n = sticks[curr_stick].data('colPart'),
					tmp;
				
				if(part_n == -1){
					width = cols[col_t][0].outerWidth() + diff_w;
					width += 'px';
					if(col_t === 'number'){
						$.cookie('tmt[s][nsz]', width, {'raw':1, 'expires':DATA.timetable_parts_live_days});
					}else if(col_t === 'time'){
						$.cookie('tmt[s][tsz]', width, {'raw':1, 'expires':DATA.timetable_parts_live_days});
					}
					tmp = cols[col_t];
				}else{
					width = cols.parts[part_n][col_t][0].outerWidth() + diff_w;
					width += 'px';
					$.cookie('tmt[s][p]['+part_n+']['+col_t+']', width, {'raw':1, 'expires':DATA.timetable_parts_live_days});
					tmp = cols.parts[part_n][col_t];
				}
				for(let i in tmp){
					tmp[i].css('width', width);
				}
				set_rows_height();
				set_sticks(curr_stick + 1);
				sticks[curr_stick].removeClass('hover');
				$table.css('cursor', '');
				curr_stick = false;
			}
		},
		'mousemove.table': function(e){
			if(curr_stick !== false){
				let page_x = sticks[curr_stick].offset().left,
					pos_x = sticks[curr_stick].position().left,
					diff_w = 0,
					limit = 50,
					col_t = sticks[curr_stick].data('colType'),
					part_n = sticks[curr_stick].data('colPart'),
					$tmp;
				
				limit = DATA.cols_min_width.hasOwnProperty(col_t) ? DATA.cols_min_width[col_t] : limit;
				if(part_n == -1){
					$tmp = cols[col_t][0];
				}else{
					$tmp = cols.parts[part_n][col_t][0];
				}
				diff_w = Math.round(e.pageX - sticks[curr_stick].data('old-pos'));
				if($tmp.outerWidth() + diff_w < limit){
					pos_x += $tmp.offset().left - page_x + limit - 2;
				}else{
					pos_x += Math.round(e.pageX - page_x - 2);
				}
				sticks[curr_stick].css('left', pos_x + 'px');
			}
		}
	});

	var settings_func = {
		'teacher_add_hide': teacher_add_hide,
		'go2curr_day': go2curr_day,
		'cell_word_wrap': cell_word_wrap,
		'lesson_unite': lesson_unite
	};

	$('#settings_collapse .setting-row').on('change.table', 'input[type=radio],input[type=checkbox]', function(e){
		let $this= $(this);
		settings_func[$this.data('settings')].apply($this);
	});

	//инициализация

	set_sticks();
	set_rows_height();
	//передвижение к текущему дню/неделе
	tmp = $.cookie('tmt[o][gcd]');
	if(tmp == 1){//week
		tmp = $table_body_cont.find('tr.curr-week-row').first();
	}else if(tmp == 2){//day
		tmp = $table_body_cont.find('tr.today-row').first();
	}else{
		tmp = [];
	}
	if(tmp.length){
		$table_body_cont.scrollTop(tmp.position().top);
	}

	//инициализация окна добавки
	var $additor_carousel = $('#additor-carousel'),
		$additor_block = $additor_carousel.find('.additor-block'),
		carousel_step = 1,
		$search_select,
		$additor_butt = $('#additor-modal-submit');

	//добавление раздела по нажатию кнопки
	//шаг 3
	$additor_butt.on('click.additor', function(e){
		if($additor_butt.prop('disabled') || carousel_step != 3)
			return;
		let tmp = $('#additor-gr-name').val(),
			end = cookie_parts.length - 1;
		cookie_parts[end].gr_name = tmp;
		$.cookie('timetable[elements]['+end+']', cookie_parts[end], {'raw':1, 'expires':DATA.timetable_parts_live_days, 'array':1});
		carousel_step++;
		location.reload();
		//$('#additor-modal').modal('hide');
	});

	//Очистка окна добавки
	$('#additor-modal').on('hidden.bs.modal', function(e){
		if(carousel_step == 3){
			cookie_parts.pop();
		}
		carousel_step = 1;
		$additor_carousel.carousel(0);
		$additor_block.children().hide();
		$search_select.searchSelect('resetOpt');
		$additor_butt.addClass('disabled').prop('disabled', 1);
	});
	//шаг 1
	$additor_carousel.find('.additor-buttons').find('button').on('click.additor', function(e){
		if(carousel_step != 1)
			return;
		carousel_step++;
		$additor_block.children('.'+$(this).data('additor')+'-additor-block').show();
		$additor_carousel.carousel('next');
	});
	//шаг 2
	$search_select = $additor_block.find('select').searchSelect().on('rad_select_complete.additor', function(e, $opt){
		if(carousel_step != 2)
			return;
		let $this = $(this);
		let name = $this.data('searchSelect').name;
		if(name == 'faculty_id'){
			let f_id = false,
				$gr = $search_select.filter('div[data-name=group_id]');
			if($opt){
				f_id = $opt.data('value');
			}
			if(f_id){
				//[data-faculty_id='+f_id+']
				$gr.find('li').data('is_use', false).filter('li[data-faculty_id='+f_id+']').data('is_use', true);
			}else{
				$gr.find('li').data('is_use', true);
			}
			$gr.searchSelect('resetOpt');
		}else{
			if(!$opt){
				return;
			}
			let type;
			switch(name){
				case 'group_id':
					type = 'group';
					break;
				case 'cabinet_id':
					type = 'cabinet';
					break;
				case 'teacher_id':
					type = 'teacher';
					break;
			}
			cookie_parts.push({
				'type': type,
				'id': $opt.data('value'),
				'gr_name': false
			});
			carousel_step++;
			$additor_carousel.carousel('next');
			if(cookie_parts.length <= DATA.max_parts_timetable)
				$additor_butt.removeClass('disabled').prop('disabled', 0);
		}
	});


	//перетаскивание разделов, настройка разделов
	$sections.sortable({'handle':'.part-drag'}).on('sortupdate.part_sort', function(e, ui){
		let new_cookie_parts = [], new_cookie_cols_size = [];
		$sections.children().each(function(ind, el){
			let $el = $(el);
			let old_ind = $el.data('part');
			new_cookie_parts.push(cookie_parts[old_ind]);
			new_cookie_cols_size.push($.cookie('tmt[s][p]['+old_ind+']', undefined, {'array':1}));
			$el.data('part', ind);
			$el.children('.section-cols-wrap').data('part', ind);
		});

		cookie_parts = new_cookie_parts;
		$.removeCookie('tmt[p]', {'array':1});
		$.removeCookie('tmt[s][p]', {'array':1});
		$.cookie('tmt[p]', cookie_parts, {'raw':1, 'array':1, 'expires':DATA.timetable_parts_live_days});
		$.cookie('tmt[s][p]', new_cookie_cols_size, {'raw':1, 'array':1, 'expires':DATA.timetable_parts_live_days});
		$('#section-edited').show();
	});
	//перетаскивание столбцов, настройка разделов
	$sections.find('.section-cols-wrap').sortable({'handle':'.col-drag'}).on('sortupdate.col_sort', function(e, ui){
		let str_col_sort = '', $this = $(this), part_n;
		$this.children().each(function(ind, el){
			let $el = $(el);
			$el.data('pos', ind);
			str_col_sort += $el.find('.cb-col-view').prop('checked') ? $el.data('colType').toUpperCase() : $el.data('colType').toLowerCase();
		});
		part_n = $this.data('part');
		cookie_parts[part_n].o = str_col_sort;
		$.cookie('tmt[p]['+part_n+'][o]', str_col_sort, {'raw':1, 'expires':DATA.timetable_parts_live_days});
		e.stopPropagation();
		$('#section-edited').show();
	});

	//удаление разделов
	$sections.children('.section-wrap').on('click.section_del', '.section-delete', function(e){
		let $sec_wrap = $(e.delegateTarget);
		let part_n = $sec_wrap.data('part');
		cookie_parts.splice(part_n, 1);
		$.removeCookie('tmt[p]', {'array':1});
		$.cookie('tmt[p]', cookie_parts, {'raw':1, 'array':1, 'expires':DATA.timetable_parts_live_days});
		$.removeCookie('tmt[s][p]['+part_n+']', {'array':1});
		let tmp = $.cookie('tmt[s][p]', undefined, {'array':1});
		for(let i in tmp){
			if(i>part_n){
				tmp[i-1] = tmp[i];
				delete tmp[i];
			}
		}
		$.removeCookie('tmt[s][p]', {'array':1});
		$.cookie('tmt[s][p]', tmp, {'raw':1, 'array':1, 'expires':DATA.timetable_parts_live_days});
		
		$sec_wrap.fadeOut(300, function(){
			$sec_wrap.remove();
			$sections.children().each(function(ind, el){
				let $el = $(el);
				$el.data('part', ind);
				$el.children('.section-cols-wrap').data('part', ind);
			});
		});
		$('#section-edited').show();
	//скрытие отображение столбцов
	}).on('click.section_col_view', '.cb-col-view', function(e){
		let $this = $(this);
		let is_view = $this.prop('checked'),
			$section_col = $this.closest('.section-col');
		//проверка на последнюю колнку
		let view_c = +is_view;
		$section_col.siblings().find('.cb-col-view').each(function(ind, el){
			if($(el).prop('checked'))
				view_c++;
		});
		if(!view_c){
			$this.prop('checked', true);
			return;
		}
		let col_t = $section_col.data('colType'),
			part_n = $section_col.parent().data('part'),
			str_col_sort = $.cookie('tmt[p]['+part_n+'][o]'),
			pos_n;
		pos_n = 2 + $section_col.data('pos') + part_n*5
		str_col_sort = str_col_sort.replace(new RegExp(col_t, 'i'), (is_view ? col_t.toUpperCase() : col_t.toLowerCase()));
		$.cookie('tmt[p]['+part_n+'][o]', str_col_sort, {'raw':1});
		//починим colspan
		$table_head.find('.part-header.part-'+part_n).parent().attr('colspan', view_c);
		//скроем столбцы
		for(let i in cols.parts[part_n][col_t]){
			if(is_view){
				cols.parts[part_n][col_t][i].parent().show();
			}else{
				cols.parts[part_n][col_t][i].parent().hide();
			}
		}
		//скроем палку
		if(is_view){
			sticks[pos_n].show();
		}else{
			sticks[pos_n].hide();
		}
		set_sticks();
		set_rows_height();
	//сохранение имени раздела
	}).on('change.section_name_edit', '.input-part-name', function(e){
		let val = $(this).val();
		let $sec_wrap = $(e.delegateTarget);
		let part_n = $sec_wrap.data('part');
		$.cookie('tmt[p]['+part_n+'][n]', val, {'raw':1});
		$table_head.find('.part-header.part-'+part_n).text(val);
	});


	//устанавливает положения палок начиная с offset
	function set_sticks(offset = 0){
		let width, pos, start, part_n, col_t;
		for(let i in sticks){
			if(i < offset)
				continue;
			part_n = sticks[i].data('colPart');
			col_t = sticks[i].data('colType');
			if(part_n == -1){
				pos = cols[col_t][0].offset().left;
				width = cols[col_t][0].outerWidth();
			}else{
				pos = cols.parts[part_n][col_t][0].offset().left;
				width = cols.parts[part_n][col_t][0].outerWidth();
			}
			start = sticks[i].offset().left-sticks[i].position().left;
			sticks[i].css('left', (pos-start+width-2)+'px');
		}
	}
	
	//синхронизирует высоты строк таблиц
	function set_rows_height(){
		let len, cont_height, static_height;
		len = table_rows.cont.length;
		for(let i=0; i<len; i++){
			table_rows.cont[i].css('height', '');
			table_rows.static[i].css('height', '');
			cont_height = table_rows.cont[i].outerHeight();
			static_height = table_rows.static[i].outerHeight();
			if(static_height < cont_height){
				table_rows.static[i].outerHeight(cont_height);
			}else if(static_height > cont_height){
				table_rows.cont[i].outerHeight(static_height);
			}
		}
	}
	
	//настройка скрытия плюшки препода
	function teacher_add_hide(){
		let f = !this.prop('checked');
		for(let i in cols.parts){
			if(!cols.parts[i].hasOwnProperty('p'))
				continue;
			for(let j in cols.parts[i].p){
				if(f){
					cols.parts[i].p[j].find('.teacher_add').show();
				}else{
					cols.parts[i].p[j].find('.teacher_add').hide();
				}
			}
		}
		set_rows_height();
		
		$.cookie('tmt[o][tah]', +this.prop('checked'), {'raw':1, 'expires':DATA.timetable_parts_live_days});
	}
	
	//настройка переноса слов
	function cell_word_wrap(){
		let f = !this.prop('checked');
		for(let i of ['number', 'time']){
			for(let j in cols[i]){
				if(f){
					cols[i][j].css('white-space', 'nowrap');
				}else{
					cols[i][j].css('white-space', 'normal');
				}
			}
		}
		for(let i in cols.parts){
			for(let j in cols.parts[i]){
				for(let h in cols.parts[i][j]){

					if(f){
						cols.parts[i][j][h].css('white-space', 'nowrap');
					}else{
						cols.parts[i][j][h].css('white-space', 'normal');
					}
				}
			}
		}
		set_rows_height();
		$.cookie('tmt[o][wwr]', +this.prop('checked'), {'raw':1, 'expires':DATA.timetable_parts_live_days});
	}
	
	//настройка идти к ...
	function go2curr_day(){
		$.cookie('tmt[o][gcd]', this.val(), {'raw':1, 'expires':DATA.timetable_parts_live_days});
	}
	
	//настройка объединения уроков
	function lesson_unite(){
		$.cookie('tmt[o][lun]', +this.prop('checked'), {'raw':1, 'expires':DATA.timetable_parts_live_days});
		location.reload();
	}
});