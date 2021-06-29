<?php
//защита от прямого обращения к скрипту
if(!defined('MAIN_DIR'))
	die();
global $URL, $USER, $PAGE_DATA;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $PAGE_DATA['title']; ?></title>
	<meta name="description" content="Рандомизатор текста позволяет с помощью синонимов и перестановки создавать уникальный текст">
	<meta name="keywords" content="Рандомизатор текста, рандомизатор, текст, текста">
	<link href="/favicon.ico" rel="shortcut icon" type="image/x-icon">
	<link rel="stylesheet" href="/styles/bootstrap-3.3.2.css">
	<?php echo $PAGE_DATA['addition_styles']; ?>
	<link rel="stylesheet" href="/styles/style.css?ver=<?php echo filemtime(MAIN_DIR. 'styles/style.css'); ?>">
	<?php
	if($USER->get_user_level() >= rad_user::NEDOADMIN){
		echo '<link rel="stylesheet" href="/styles/admin_style.css?ver='.filemtime(MAIN_DIR. 'styles/admin_style.css').'">';
	}
	?>
	<script src="/libs/jquery-3.4.1.min.js"></script>
	<script src="/scripts/bootstrap-3.3.2.min.js"></script>
	<script type="text/javascript">
		/* <![CDATA[ */
		var DATA = {
			"ignoreForms":".login",
			"cols_min_width": {
				"1":25,
				"2":55,
				"3":100,
				"4":50,
				"5":35,
				"6":50
			}
		};
		/* ]]> */
	</script>
	<?php echo $PAGE_DATA['addition_libs']; ?>
</head>
<body>
<nav class="navbar navbar-inverse navbar-fixed-top">
	<div class="container">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
		</div>
		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			<div class="navbar-right navbar-text">
			
			</div>
			<?php
			if(is_login()){
				echo '<div class="navbar-right navbar-text"><a href="/?action=exit" class="navbar-link">Выход</a></div>';
			}
			?>
		</div>
	</div>
</nav>
<?php
if(can_user('view_debug_info')){
	echo '<!-- template: '.$URL->get_current_page().', page_id: '.$URL->get_current_id().' -->';
}
?>