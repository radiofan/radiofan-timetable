<?php
function view_footer($PAGE_DATA){
	global $URL, $USER, $OPTIONS;
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
	echo implode(PHP_EOL, $PAGE_DATA['addition_scripts']);
?>
</body>
</html>

<?php
	if(can_user('view_debug_info'))
		echo '<!--
		memory_peak: '.round_memsize(memory_get_peak_usage(1)).';
		exec_time: '.(microtime(1) - $OPTIONS['time_start']).' sec
		disk_space: '.round_memsize(disk_free_space(MAIN_DIR)).'/'.round_memsize(disk_total_space(MAIN_DIR)).'
		-->';
}
?>