<?php
//защита от прямого обращения к скрипту
if(!defined('MAIN_DIR'))
	die();
global $URL, $USER, $DB, $PAGE_DATA;
/*
TODO: панель отдладки для админа
<div class="navbar-fixed-bottom row-fluid">
	<div class="navbar-inner">
		<div class="container">
		</div>
	</div>
</div>
*/
?>

<!-- Modal -->
<div class="modal fade" id="main-modal" tabindex="-1" role="dialog" aria-labelledby="main-modal-title" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="main-modal-title"></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
			</div>
			<div class="modal-footer">
				<button type="button" id="main-modal-submit" class="btn btn-primary"></button>
			</div>
		</div>
	</div>
</div>

<?php
/*
if(can_user('view_debug_info')){
	echo '<!--'.print_r($DB->getStats(), 1).'-->';
}
*/
?>

<script src="/scripts/script.js?ver=<?php echo filemtime(MAIN_DIR. 'scripts/script.js'); ?>"></script>
<?php
if($USER->get_user_level() >= rad_user::NEDOADMIN){
	echo '<script src="/scripts/admin_script.js?ver='.filemtime(MAIN_DIR. 'scripts/admin_script.js').'"></script>';
}
echo $PAGE_DATA['addition_scripts'];
?>
</body>
</html>