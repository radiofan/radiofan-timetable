jQuery(document).ready(function($){
	
	//переключение форм регистрации и входа
	$('input[name="in-form-type"]').on('change.form_toggle', function(e){
		let $this = $(this);
		let form_type = $this.val();
		let $forms = $this.closest('.form-toggle').siblings();
		$forms.filter('*:visible').hide();
		$forms.filter('.in-form-'+form_type).show();
	});
	
	//переключение видимости пароля
	$('.password-view').on('click.password_view', function(e){
		let $this = $(this);
		let pass_inp_selector = $this.data('target');
		let $pass_inp = $(pass_inp_selector);
		if($pass_inp){
			if($pass_inp.attr('type') == 'password'){
				$pass_inp.attr('type', 'text');
			}else{
				$pass_inp.attr('type', 'password');
			}
			let plh = $this.data('viewPattern');
			$this.data('viewPattern', $this.text());
			$this.text(plh);
		}
	});
	
});