jQuery(document).ready(function($){
	var $table = $('.timetable-block'),
		cols = {},
		sticks = {},
		$table_body,
		$table_head,
		$table_sticks,
		$table_extender,
		cookie_elements = [],
		$body = $('body'),
		$settings = $('#settings_collapse .setting-row'),
		settings_func = {
			'teacher_add_hide':teacher_add_hide,
			'go2curr_day':go2curr_day,
			'cell_word_wrap':cell_word_wrap
		};

	$table_body = $table.children('.timetable-body');
	$table_head = $table.children('.timetable-head');
	$table_sticks = $table.children('.sticks');
	$table_extender = $table.children('.timetable-extender');

	let tmp;
	for(let i=0; i<DATA.max_elements_timetable; i++){
		tmp = $.cookie('timetable[elements]['+i+'][type]');
		if(!tmp)
			break;
		cookie_elements.push({
			'type': tmp,
			'id': $.cookie('timetable[elements]['+i+'][id]'),
			'gr_name': $.cookie('timetable[elements]['+i+'][gr_name]')
		});
	}

	//генерирование массива столбцов
	tmp = $table.find('.cell[class *= "col-"]'), len=0;
	len = tmp.length;
	for(let i=0; i<len; i++){
		tmp[i] = $(tmp[i]);
		if(cols.hasOwnProperty(tmp[i].data('col'))){
			cols[+tmp[i].data('col')].push(tmp[i]);
		}else{
			cols[+tmp[i].data('col')] = [tmp[i]];
		}
		delete tmp[i];
	}

	//генерирование массива палок
	tmp = $table_sticks.children('.stick');
	len = tmp.length;
	for(let i=0; i<len; i++){
		tmp[i] = $(tmp[i]);
		sticks[+tmp[i].data('col')] = tmp[i];
		delete tmp[i];
	}

	//флаги работы с размерами таблицы
	var curr_stick = false,
		extender = false;
	//установка размера столбца
	$table_sticks.on('mousedown.table', '.stick', function(e){
		if(!curr_stick){
			e.preventDefault();
			curr_stick = $(this).data('col');
			sticks[curr_stick].addClass('hover').data('old-pos', sticks[curr_stick].offset().left);
			$table.css('cursor', 'col-resize');
		}
	});
	//установка высоты таблицы
	$table_extender.on('mousedown.table', function(e){
		if(!extender){
			e.preventDefault();
			extender = true;
			$table_extender.data('old-pos', $table_extender.offset().top);
			$table.css('cursor', 'row-resize');
		}
	});

	//TODO добавить скролл при наведении на палку

	//прокручивание заголовка
	$table_body.on('scroll.table', function(e){
		$table_head.scrollLeft($table_body.scrollLeft());
		set_sticks();
		//$table_sticks.css('left', -$table_body.scrollLeft()+'px');
	});

	//начало и продолжение изменения размеров таблицы
	$body.on({
		'mouseup.table': function(e){
			if(curr_stick){
				let diff_w = sticks[curr_stick].offset().left - sticks[curr_stick].data('old-pos'),
					width = 0;
				width = cols[curr_stick][0].outerWidth() + diff_w;
				$.cookie('timetable[options][size]['+curr_stick+']', width, {raw:1, expires:30});
				width += 'px';
				for(let i in cols[curr_stick]){
					cols[curr_stick][i].css('width', width);
				}
				set_sticks(curr_stick + 1);
				sticks[curr_stick].removeClass('hover');
				$table.css('cursor', '');
				curr_stick = false;
			}
			if(extender){
				extender = false;
				$.cookie('timetable[options][size][height]', $table_body.outerHeight(), {raw:1, expires:30});
				$table.css('cursor', '');
			}
		},
		'mousemove.table': function(e){
			if(curr_stick){
				let page_x = sticks[curr_stick].offset().left,
					pos_x = sticks[curr_stick].position().left,
					diff_w = 0,
					limit = 50;
				if(curr_stick <= 2){
					limit = DATA.cols_min_width[curr_stick];
				}else{
					limit = DATA.cols_min_width[(curr_stick-3)%5+3];
				}
				diff_w = Math.round(e.pageX - sticks[curr_stick].data('old-pos'));
				if(cols[curr_stick][0].outerWidth() + diff_w < limit){
					pos_x += cols[curr_stick][0].offset().left - page_x + limit - 2;
				}else{
					pos_x += Math.round(e.pageX - page_x - 2);
				}
				sticks[curr_stick].css('left', pos_x + 'px');
			}else if(extender){
				let diff = Math.round(e.pageY - $table_extender.data('old-pos')),
					limit = DATA.hasOwnProperty('table_min_height') ? DATA.table_min_height : 150;
				if($table_body.outerHeight() + diff < limit){
					diff = limit;
				}else{
					diff = diff + $table_body.outerHeight();
				}
				$table_body.css('height', diff);
				$table_extender.data('old-pos',  $table_extender.offset().top);

				diff = $(window).height() - e.clientY;
				if(diff < 20){
					$body.scrollTop($body.scrollTop()+20);
				}
			}
		}
	});

	$settings.on('change.table', 'input[type=radio],input[type=checkbox]', function(e){
		let $this= $(this);
		settings_func[$this.data('settings')].apply($this);
	});

	//инициализация

	set_sticks();
	//передвижение к текущему дню/неделе
	tmp = $.cookie('timetable[options][go2curr_day]');
	if(tmp == 'week'){
		tmp = $table_body.find('tr.curr-week-row').first();
	}else if(tmp == 'day'){
		tmp = $table_body.find('tr.today-row').first();
	}else{
		tmp = false;
	}
	if(tmp){
		$table_body.scrollTop(tmp.position().top);
	}

	//инициализация окна добавки
	var $additor_carousel = $('#additor-carousel'),
		$additor_block = $additor_carousel.find('.additor-block'),
		carousel_step = 1,
		$search_select,
		$additor_butt = $('#additor-modal-submit');

	$additor_butt.on('click.additor', function(e){
		if($additor_butt.prop('disabled'))
			return;
		let tmp = $('#additor-gr-name').val(),
			end = cookie_elements.length - 1;
		cookie_elements[end].gr_name = tmp;
		tmp = cookie_elements[end];
		$.cookie('timetable[elements]['+end+'][type]', tmp.type, {raw:1, expires:30});
		$.cookie('timetable[elements]['+end+'][id]', tmp.id, {raw:1, expires:30});
		$.cookie('timetable[elements]['+end+'][gr_name]', tmp.gr_name, {raw:1, expires:30});
		carousel_step++;
		location.reload();
		$('#additor-modal').modal('hide');
	});

	$('#additor-modal').on('hidden.bs.modal', function(e){
		if(carousel_step == 3){
			cookie_elements.pop();
		}
		carousel_step = 1;
		$additor_carousel.carousel(0);
		$additor_block.children().hide();
		$search_select.searchSelect('resetOpt');
		$additor_butt.addClass('disabled').prop('disabled', 1);
	});
	$additor_carousel.find('.additor-buttons').find('button').on('click.additor', function(e){
		if(carousel_step != 1)
			return;
		carousel_step++;
		$additor_block.children('.'+$(this).data('additor')+'-additor-block').show();
		$additor_carousel.carousel('next');
	});
	$search_select = $additor_block.find('select').searchSelect().on('rad_select_complete.additor', function(e, $opt){
		if(carousel_step != 2)
			return;
		let $this = $(this);
		let name = $this.data('searchSelect').name;
		if(name == 'faculty_id'){

		}else{
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
			cookie_elements.push({
				'type': type,
				'id': $opt.data('value'),
				'gr_name': false
			});
			carousel_step++;
			$additor_carousel.carousel('next');
			if(cookie_elements.length <= DATA.max_elements_timetable)
				$additor_butt.removeClass('disabled').prop('disabled', 0);
		}
	});


	//устанавливает положения палок начиная с offset
	function set_sticks(offset = 1){
		let width, pos, start;
		for(let i in sticks){
			if(i < offset)
				continue;
			pos = cols[i][0].offset().left;
			width = cols[i][0].outerWidth();
			start = sticks[i].offset().left-sticks[i].position().left;
			sticks[i].css('left', (pos-start+width-2)+'px');
		}
	}

	function teacher_add_hide(){
		let f = !this.prop('checked');
		for(let i in cols){
			for(let j in cols[i]){
				if(!cols[i][j].hasClass('col-teacher')) break;
				if(f){
					cols[i][j].find('.teacher_add').show();
				}else{
					cols[i][j].find('.teacher_add').hide();
				}
			}
		}
		$.cookie('timetable[options][teacher_add_hide]', +this.prop('checked'), {raw:1, expires:30});
	}

	function cell_word_wrap(){
		let f = !this.prop('checked');
		for(let i in cols){
			for(let j in cols[i]){
				if(f){
					cols[i][j].css('white-space', 'nowrap');
				}else{
					cols[i][j].css('white-space', 'normal');
				}
			}
		}
		$.cookie('timetable[options][cell_word_wrap]', +this.prop('checked'), {raw:1, expires:30});
	}

	function go2curr_day(){
		$.cookie('timetable[options][go2curr_day]', this.val(), {raw:1, expires:30});
	}
});