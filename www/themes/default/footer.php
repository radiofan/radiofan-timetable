<?php
function view_footer($PAGE_DATA){
	global $URL, $USER;
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
<div class="modal fade" id="main-modal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="main-modal-title"></h4>
				<button type="button" class="close" data-dismiss="modal">
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
	if($URL->get_current_page() === 'main_page'){
		echo gen_additor_modal_html();
	}

/*
if(can_user('view_debug_info')){
	echo '<!--'.print_r($DB->getStats(), 1).'-->';
}
*/
?>

<script src="/scripts/script.js?ver=<?php echo filemtime(MAIN_DIR. 'scripts/script.js'); ?>"></script>
<?php
	if($USER->get_user_level() >= rad_user_roles::NEDOADMIN){
		echo '<script src="/scripts/admin_script.js?ver='.filemtime(MAIN_DIR. 'scripts/admin_script.js').'"></script>';
	}
	echo implode(PHP_EOL, $PAGE_DATA['addition_scripts']);
?>
</body>
</html>

<?php
}
?>