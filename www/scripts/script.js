jQuery(document).ready(function($){
	$('form').not(DATA.ignoreForms).on('submit', function(e){
		e.preventDefault();
		let $this = $(this);
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
						alert(out);
					}else{
						let out = 'Ошибка: ';
						if(result[act].hasOwnProperty('message')){
							out += result[act].message;
						}
						alert(out);
					}
				}else{
					alert('Ошибка: ');
				}
				$this.data('exec', false);
			},
			error: function(){
				alert('Ошибка. Попробуйте позже.');
				$this.data('exec', false);
			}
		});
	});
});