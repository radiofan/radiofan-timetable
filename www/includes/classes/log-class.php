<?php

if(!defined('MAIN_DIR'))
	die();

error_reporting(E_ALL);

class rad_log{
	private $stack_trace;
	private $path;
	private $error_type = array(
		E_USER_ERROR	=> 'E_USER_ERROR',
		E_USER_WARNING	=> 'E_USER_WARNING',
		E_USER_NOTICE	=> 'E_USER_NOTICE',
		E_WARNING		=> 'E_WARNING',
		E_NOTICE		=> 'E_NOTICE'
	);
	const MAX_LOG_SIZE = 5*BYTES_PER_MB;
	
	private $curr_error_f;

	/**
	 * @param string $path - путь до папки с логами (должен оканчиваться на /)
	 * @param int $stack
	 */
	public function __construct($path, $stack = 0){
		$this->stack_trace = $stack ? 1: 0;
		$this->path = $path;
		if(!check_dir($this->path))
			throw new Exception('undefined log path');
		$error_files_list = file_list($path, '.log', '^error-[0-9]+');
		$len = sizeof($error_files_list);
		if($len == 0){
			$this->curr_error_f = 'error-'.str_pad('1', 5, '0', STR_PAD_LEFT).'.log';
		}else{
			$this->curr_error_f = $error_files_list[$len-1];
			$this->while_test_error_f();
		}
		
		set_error_handler(array($this, 'log_error'));
		set_exception_handler(array($this, 'log_exception'));
	}


	/**
	 * обновляет имя файла на следующее, если текущий переполнен
	 * @return bool - true если обновление произошло
	 */
	private function test_error_f(){
		if(get_filesize($this->path.$this->curr_error_f) >= self::MAX_LOG_SIZE){
			$number = int_clear($this->curr_error_f);
			$number++;
			$this->curr_error_f = 'error-'.str_pad($number, 5, '0', STR_PAD_LEFT).'.log';
			return true;
		}
		return false;
	}

	/**
	 * обновляет имя файла на последний не полный файл
	 */
	private function while_test_error_f(){
		$number = (int)int_clear($this->curr_error_f);
		while(get_filesize($this->path.$this->curr_error_f) >= self::MAX_LOG_SIZE){
			$number++;
			$this->curr_error_f = 'error-'.str_pad($number, 5, '0', STR_PAD_LEFT).'.log';
		}
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
		$this->while_test_error_f();
		return file_put_contents($this->path.$this->curr_error_f, $data, FILE_APPEND | LOCK_EX);
	}
	
	public function __destruct(){
		restore_error_handler();
		restore_exception_handler();
	}
}
?>