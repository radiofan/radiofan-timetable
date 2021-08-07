jQuery(document).ready(function($){
	
	//переключение форм регистрации и входа
	$('input[name="in-form-type"]').on('change.form_toggle', function(e){
		let $this = $(this);
		let form_type = $this.val();
		let $forms = $this.closest('.form-toggle').siblings();
		$forms.filter('*:visible').hide();
		$forms.filter('.in-form-'+form_type).show();
	});
	
	//переключение окна восстановления пароля
	$('#show-pass-recovery').on('click.show_hide_pr', function(e){
		e.preventDefault();
		$('.in-form-pass-rec').fadeIn(200);
	});
	$('#hide-pass-recovery').on('click.show_hide_pr', function(e){
		e.preventDefault();
		$('.in-form-pass-rec').fadeOut(200);
	});
	
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
	
	//валидация формы регистрации
	$('.in-form-signin').on('input.signin', 'input', function(e){
		let $this = $(this),
			name = $this.attr('name'),
			value = $this.val();
		switch(name){
			case 'login':
				if(/^[a-zA-Z0-9_\-]{1,}$/.test(value)){
					check_login($this);
					break;
				}
				check_login_abort();
				$this.removeClass('is-valid').addClass('is-invalid');
				$this.nextAll('.invalid-feedback').first().text('Логин должен состоять из a-z A-Z 0-9 _ -').show();
				break;
			case 'password':
				if(/^[a-zA-Z0-9!@\$%&\?\*]{6,}$/.test(value)){
					$this.removeClass('is-invalid').addClass('is-valid');
					$this.nextAll('.invalid-feedback').first().hide();
					break;
				}
				$this.removeClass('is-valid').addClass('is-invalid');
				$this.nextAll('.invalid-feedback').first().show();
				break;
			case 'email':
				if(/.+$/.test(value)){
					$this.removeClass('is-invalid').addClass('is-valid');
					$this.nextAll('.invalid-feedback').first().hide();
					break;
				}
				$this.removeClass('is-valid').addClass('is-invalid');
				$this.nextAll('.invalid-feedback').first().show();
				break;
		}
	}).on('submit.signin', function(e){
		let $this = $(this);
		if($this.find('.is-valid').length != 3){
			e.preventDefault();
			return;
		}
	});
	
	var check_login_timer;
	function check_login($login_input){
		if(check_login_timer)
			clearTimeout(check_login_timer);
		
		check_login_timer = setTimeout(function(){
			$.post(
				'/ajax.php',
				{'action':'check_login', 'login':$login_input.val()},
				function(result, status){
					if(result.hasOwnProperty('check_login') && result.check_login){
						$login_input.removeClass('is-invalid').addClass('is-valid');
						$login_input.nextAll('.invalid-feedback').first().hide();
					}else{
						$login_input.removeClass('is-valid').addClass('is-invalid');
						$login_input.nextAll('.invalid-feedback').first().text('Логин занят').show();
					}
				},
				'json'
			);
		}, 1000);
	}
	function check_login_abort(){
		clearTimeout(check_login_timer);
	}
});