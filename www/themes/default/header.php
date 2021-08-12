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
	if($USER->get_user_level() >= rad_user_roles::NEDOADMIN){
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
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand logo" href="/">
				<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" height="100%" viewBox="0 0 300 150">
					<g id="pnp">
						<circle class="svg-logo-line" cx="150" cy="53.449" r="31.5"/>
						<line class="svg-logo-line" x1="176.55" y1="70.099" x2="161.25" y2="46.699"/>
						<line class="svg-logo-line" stroke-width="5" x1="127.5" y1="46.249" x2="172.5" y2="46.249"/>
						<line class="svg-logo-line" x1="123.45" y1="70.099" x2="138.75" y2="46.699"/>
						<g>
							<polygon class="svg-stroke-white svg-fill-white" points="131.614,51.819 139.272,46.384 137.175,55.538"/>
							<path class="svg-stroke-white svg-fill-white" d="M138.492,47.551l-1.642,7.167l-4.354-2.912L138.492,47.551 M140.053,45.217l-9.32,6.613l6.767,4.526L140.053,45.217 L140.053,45.217z"/>
						</g>
						<line class="svg-logo-line" x1="150" y1="44.449" x2="150" y2="8.449"/>
						<line class="svg-logo-line" x1="177" y1="70.099" x2="195" y2="70.099"/>
						<line class="svg-logo-line" stroke-miterlimit="10" x1="123" y1="70.099" x2="105" y2="70.099"/>
					</g>
					<g id="txt">
						<path class="svg-fill-white" d="M32.004,102.355h6.953c3.808,0,6.52,0.34,8.135,1.02s2.915,1.809,3.898,3.387s1.475,3.445,1.475,5.602
							c0,2.266-0.543,4.16-1.629,5.684s-2.723,2.676-4.909,3.457l8.167,15.328H46.92l-7.753-14.601h-0.601v14.601h-6.563V102.355z
							M38.566,115.832h2.056c2.087,0,3.524-0.274,4.311-0.823c0.786-0.548,1.18-1.458,1.18-2.728c0-0.752-0.195-1.406-0.584-1.963
							c-0.39-0.557-0.912-0.957-1.565-1.199c-0.654-0.244-1.854-0.365-3.598-0.365h-1.799V115.832z"/>
						<path class="svg-fill-white" d="M69.92,102.355h6.655l13.26,34.476h-6.82l-2.697-7.102H66.25l-2.805,7.102h-6.82L69.92,102.355z M73.289,111.496
							l-4.625,11.836h9.224L73.289,111.496z"/>
						<path class="svg-fill-white" d="M94.934,102.355h7.774c5.012,0,8.735,0.621,11.17,1.863c2.436,1.242,4.441,3.262,6.019,6.059
							c1.577,2.797,2.365,6.063,2.365,9.797c0,2.656-0.441,5.098-1.323,7.324c-0.882,2.227-2.1,4.074-3.653,5.543
							c-1.553,1.469-3.235,2.484-5.046,3.047s-4.949,0.844-9.414,0.844h-7.892V102.355z M101.449,108.683v21.75h3.047
							c3,0,5.176-0.344,6.527-1.032s2.457-1.846,3.316-3.472c0.859-1.627,1.289-3.629,1.289-6.007c0-3.659-1.023-6.499-3.07-8.517
							c-1.844-1.814-4.805-2.722-8.883-2.722H101.449z"/>
						<path class="svg-fill-white" d="M128.051,102.355h6.516v34.476h-6.516V102.355z"/>
						<path class="svg-fill-white" d="M158.227,101.488c4.878,0,9.072,1.766,12.582,5.297c3.51,3.531,5.266,7.836,5.266,12.914
							c0,5.031-1.732,9.289-5.195,12.773c-3.463,3.484-7.665,5.227-12.605,5.227c-5.175,0-9.475-1.789-12.899-5.367
							c-3.424-3.578-5.136-7.828-5.136-12.75c0-3.297,0.797-6.328,2.392-9.094s3.788-4.957,6.579-6.574
							C152,102.296,155.005,101.488,158.227,101.488z M158.156,107.91c-3.191,0-5.874,1.109-8.047,3.328
							c-2.174,2.219-3.261,5.039-3.261,8.461c0,3.813,1.369,6.828,4.106,9.046c2.127,1.734,4.567,2.602,7.32,2.602
							c3.112,0,5.764-1.125,7.953-3.375c2.19-2.25,3.285-5.023,3.285-8.32c0-3.281-1.103-6.059-3.309-8.332
							C163.998,109.046,161.315,107.91,158.156,107.91z"/>
						<path class="svg-fill-white" d="M182.355,102.355h17.109v6.398h-10.594v6.258h10.594v6.305h-10.594v15.515h-6.516V102.355z"/>
						<path class="svg-fill-white" d="M215.819,102.355h6.655l13.26,34.476h-6.82l-2.697-7.102h-14.068l-2.805,7.102h-6.82L215.819,102.355z M219.188,111.496
							l-4.625,11.836h9.224L219.188,111.496z"/>
						<path class="svg-fill-white" d="M240.832,102.355h6.295l14.752,22.688v-22.688h6.563v34.476h-6.313l-14.733-22.617v22.617h-6.563V102.355z"/>
					</g>
				</svg>
			</a>
			<a class="navbar-brand logo-addition" href="/">|timetable</a>
			<div class="navbar-brand navbar-event">
			</div>
		</div>
		<div class="collapse navbar-collapse" id="main-navbar">
			<ul class="nav navbar-nav">
				<?php
				//вход
				if($USER->get_user_level() < rad_user_roles::USER || can_user('view_debug_info')){
					echo '
					<li class="nav-item">
						<a class="nav-link'.($URL->get_current_page() == 'login' ? ' active' : '').'" href = "/login" title="Войти/Зарегистрироваться">Войти</a>
					</li>';
				}
				echo '
					<li class="nav-item">
						<a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">Инфо</a>
						<ul class="dropdown-menu">
							<li class="nav-item">
								<a class="nav-link dropdown-item" href="#">Новости</a>
							</li>
							<li class="nav-item">
								<a class="nav-link dropdown-item" href="#" title="Часто задоваемые Вопросы">ЧаВо</a>
							</li>
							<li class="nav-item">
								<a class="nav-link dropdown-item" href="#">О сайте</a>
							</li>
						</ul>
					</li>';
				//Настройки
				if($USER->get_user_level() >= rad_user_roles::USER){
					echo '
					<li class="nav-item">
						<a class="nav-link'.($URL->get_current_page() == 'user_settings' ? ' active' : '').'" href = "/settings">Настройки</a>
					</li>';
				}
				//админка
				if($USER->get_user_level() >= rad_user_roles::NEDOADMIN){
					echo '
					<li class="nav-item">
						<a class="nav-link'.($URL->get_current_page() == 'admin' ? ' active' : '').'" href="/adminka">Админка</a>
					</li>';
				}
				//выход
				if($USER->get_id()){
					echo '
					<li class="nav-item">
						<a class="nav-link nav-link-exit" href="'.$URL->get_current_url().'?action=exit">Выход<span>&#9032;</span></a>
					</li>';
				}
				?>
			</ul>
		</div>
	</div>
</nav>
<?php
	if(can_user('view_debug_info')){
		echo '<!-- page_id: '.$URL->get_current_page().'; breadcrumbs: '.implode(' | ',$URL->get_breadcrumbs()).'; -->';
	}
}
?>