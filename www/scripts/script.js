jQuery(document).ready(function($){
	function rad_alert(message){
		//TODO rad_alert
		alert(message);
	}

	//переключение видимости пароля
	$('.password-view').on('click.password_view', function(e){
		let $this = $(this);
		let pass_inp_selector = $this.data('target');
		let $pass_inp = $(pass_inp_selector);
		if($pass_inp){
			if($pass_inp.attr('type') == 'password'){
				$pass_inp.attr('type', 'text');
				$this.attr('title', 'Скрыть пароль');
			}else{
				$pass_inp.attr('type', 'password');
				$this.attr('title', 'Показать пароль');
			}
			let plh = $this.data('viewPattern');
			$this.data('viewPattern', $this.text());
			$this.text(plh);
			$pass_inp.focus();
		}
	});
	
	$('form').not(DATA.ignoreForms).on('submit', function(e){
		let $this = $(this);
		if($this.data('notAjax'))
			return;
		e.preventDefault();
		if($this.data('exec'))
			return;
		let act = $this.find('input[name = action]').val();
		let data = $this.serializeArray();
		$this.data('exec', true);
		$.ajax({
			url: '/ajax.php',
			type: 'post',
			data: data,
			dataType: 'json',
			cache: false,
			success: function(result){
				if(result.hasOwnProperty(act)){
					if(result[act] === true || result[act].status == 0){
						let out = 'успешно\n';
						if(result[act].hasOwnProperty('message')){
							out += result[act].message;
						}
						rad_alert(out);
					}else{
						let out = 'Ошибка: ';
						if(result[act].hasOwnProperty('message')){
							out += result[act].message;
						}
						rad_alert(out);
					}
				}else{
					rad_alert('Ошибка: ');
				}
				$this.data('exec', false);
			},
			error: function(){
				rad_alert('Ошибка. Попробуйте позже.');
				$this.data('exec', false);
			}
		});
	});
});