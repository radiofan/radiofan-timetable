jQuery(document).ready(function($){
	var $table = $('.timetable-block'),
		cols = {},
		sticks = {},
		$table_body,
		$table_head,
		$table_sticks,
		$table_extender,
		$body = $('body'),
		$settings = $('#settings_collapse .setting-row'),
		settings_func = {
			'teacher_add_hide':teacher_add_hide,
			'go2curr_day':go2curr_day
		};

	$table_body = $table.children('.timetable-body');
	$table_head = $table.children('.timetable-head');
	$table_sticks = $table.children('.sticks');
	$table_extender = $table.children('.timetable-extender');

	//генерирование массива столбцов
	let tmp = $table.find('.cell[class *= "col-"]'), len=0;
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

	function go2curr_day(){

	}
});