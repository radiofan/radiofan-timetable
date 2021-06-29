<?php

if(!defined('MAIN_DIR'))
	die();

error_reporting(E_ALL);

class rad_log{
	private $stack_trace;
	private $logfile_path;
	private $error_type = array(
		E_USER_ERROR   => 'E_USER_ERROR',
		E_USER_WARNING => 'E_USER_WARNING',
		E_USER_NOTICE  => 'E_USER_NOTICE',
		E_WARNING      => 'E_WARNING',
		E_NOTICE       => 'E_NOTICE'
	);
	/*
	private $cust_error = array(
		'ok',
		'Try create menu with ununiq ID',
		'Try create menu with udenfined function'
	);
	*/
	public function __construct($logfile = 'debug.log', $stack = 0){
		$this->stack_trace = $stack ? 1: 0;
		$this->logfile_path = $logfile;
		if(!is_file($this->logfile_path)){
			if(!touch($this->logfile_path)){
				return false;
			}
		}else if(!is_writable($this->logfile_path)){
			return false;
		}
		set_error_handler(array($this, 'log_error'));
		set_exception_handler(array($this, 'log_exception'));
	}
	
	public function log_error($errno, $errstr, $errfile, $errline){
		$type = isset($this->error_type[$errno]) ? $this->error_type[$errno] : 'UDENFINED('.$errno.')';
		/*
		if(strcmp(substr($errstr, 0, 11), 'cust_error_')){
			$type .= ' CUSTOM ERROR';
			$errstr = $cust_error[substr($errstr, 11)];
		}
		*/
		$out = '['.date('Y-M-d H:i:s').'] ['.$type.'] '.$errstr.'; File: '.$errfile.', line: '.$errline.PHP_EOL;
		$this->log_write($out);
		if($errno === E_USER_ERROR){
			die();
		}
	}
	
	public function log_exception($exception){
		$out = '['.date('Y-M-d H:i:s').'] [EXCEPTION] '.$exception->getMessage().'; File: '.($exception->getFile()).', line: '.($exception->getLine()).PHP_EOL;
		$this->log_write($out);
		die();
	}
	
	private function log_write($data){
		if($this->stack_trace){
			$data .= 'Backtrace: '.print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 10), 1).PHP_EOL;
		}
		return file_put_contents($this->logfile_path, $data, FILE_APPEND | LOCK_EX);
	}
	
	public function __destruct(){
		restore_error_handler();
		restore_exception_handler();
	}
}
?>