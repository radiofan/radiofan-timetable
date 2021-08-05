<?php
function view_header($PAGE_DATA){
	global $URL, $USER;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $PAGE_DATA['title']; ?></title>
	<link href="/favicon.ico" rel="shortcut icon" type="image/x-icon">
	<link rel="stylesheet" href="/styles/bootstrap-3.3.2.css">
	<?= implode(PHP_EOL, $PAGE_DATA['addition_styles']); ?>
	<link rel="stylesheet" href="/styles/style.css?ver=<?= filemtime(MAIN_DIR. 'styles/style.css'); ?>">
	<?php
	if($USER->get_user_level() >= rad_user::NEDOADMIN){
		echo '<link rel="stylesheet" href="/styles/admin_style.css?ver='.filemtime(MAIN_DIR. 'styles/admin_style.css').'">';
	}
	?>
	<script src="/libs/jquery-3.4.1.min.js"></script>
	<script src="/libs/jquery.cookie.js"></script>
	<script src="/libs/jquery.searchSelect.js"></script>
	<script src="/scripts/bootstrap-3.3.2.min.js"></script>
	<script type="text/javascript">
		/* <![CDATA[ */
		var DATA = {
			"ignoreForms":".login",
			"cols_min_width": [
				0,
				25,
				55,
				100,
				35,
				50,
				35,
				50
			],
			"table_min_height": 150,
			"max_elements_timetable": <?php echo MAX_ELEMENTS_TIMETABLE; ?>
		};
		/* ]]> */
	</script>
	<?= implode(PHP_EOL, $PAGE_DATA['addition_libs']); ?>
</head>
<body>
<nav class="navbar navbar-inverse navbar-fixed-top">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>
		<a class="navbar-brand" style="font-family:Verdana,sans-serif" href="/">RADIOFAN | timetable</a>
	</div>
	<div class="collapse navbar-collapse" id="main-navbar">
		<ul class="navbar-nav">
			<?php
			if($USER->get_id()):
				?>
				<li class="nav-item">
					<a class="nav-link" href="<?= $URL->get_current_url(); ?>?action=exit">Выход</a>
				</li>
			<?php
			endif;
			?>
		</ul>
	</div>
</nav>
<?php
	if(can_user('view_debug_info')){
		echo '<!-- page_id: '.$URL->get_current_page().'; breadcrumbs: '.implode(' | ',$URL->get_breadcrumbs()).'; -->';
	}
}
?>