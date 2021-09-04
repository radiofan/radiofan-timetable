<?php
/**
 * @author col.shrapnel@gmail.com
 * @link http://phpfaq.ru/safemysql
 * @link https://github.com/colshrapnel/safemysql
 */

class rad_db{
	
	protected $conn;
	protected $stats;
	protected $emode;
	protected $exname;
	
	protected $is_transaction = false;
	
	//protected $autocommit;
	
	protected $defaults = array(
		'host'      => 'localhost',
		'user'      => 'root',
		'pass'      => '',
		'db'        => 'test',
		'port'      => NULL,
		'socket'    => NULL,
		'pconnect'  => FALSE,
		'charset'   => 'utf8',
		'errmode'   => 'exception', //or 'error'
		'exception' => 'Exception', //Exception class name
	);
	
	const RESULT_ASSOC = MYSQLI_ASSOC;
	const RESULT_NUM   = MYSQLI_NUM;
	
	function __construct($opt = array()){
		$opt = array_merge($this->defaults,$opt);
		
		$this->emode  = $opt['errmode'];
		$this->exname = $opt['exception'];
		
		if(isset($opt['mysqli'])){
			if($opt['mysqli'] instanceof mysqli){
				$this->conn = $opt['mysqli'];
				return;
			}else{
				$this->error('mysqli option must be valid instance of mysqli class');
			}
		}
		
		if($opt['pconnect']){
			$opt['host'] = 'p:'.$opt['host'];
		}
		
		@$this->conn = mysqli_connect($opt['host'], $opt['user'], $opt['pass'], $opt['db'], $opt['port'], $opt['socket']);
		if(!$this->conn){
			$this->error(mysqli_connect_errno().' '.mysqli_connect_error());
		}
		
		mysqli_set_charset($this->conn, $opt['charset']) or $this->error(mysqli_error($this->conn));
		unset($opt);
	}


	/**
	 * ?n - название столбца
	 * ?s - строка
	 * ?i - int
	 * ?a - creatIN (массив в строку '','',''....)
	 * ?u - createSet(массив в key='val',...)
	 * ?p - без экранирования
	 * ?d - вставка массива, где ключи это название столбцов
	 * @return mixed
	 */
	public function query(){
		return $this->rawQuery($this->prepareQuery(func_get_args()));
	}
	
	public function fetch($result,$mode=self::RESULT_ASSOC){
		return mysqli_fetch_array($result, $mode);
	}
	
	public function affectedRows(){
		return mysqli_affected_rows($this->conn);
	}
	
	public function insertId(){
		return mysqli_insert_id($this->conn);
	}
	
	public function numRows($result){
		return mysqli_num_rows($result);
	}
	
	public function startTransaction($flag = MYSQLI_TRANS_START_READ_WRITE){
		if($this->is_transaction)
			$this->error('start second transaction');
		$this->is_transaction = true;
		mysqli_autocommit($this->conn, 0);
		return mysqli_begin_transaction($this->conn, $flag);
	}

	public function commit(){
		if(!$this->is_transaction)
			return true;
		$this->is_transaction = false;
		mysqli_autocommit($this->conn, 1);
		return mysqli_commit($this->conn);
	}

	public function rollback(){
		if(!$this->is_transaction)
			return true;
		$this->is_transaction = false;
		mysqli_autocommit($this->conn, 1);
		return mysqli_rollback($this->conn);
	}

	public function free($result){
		mysqli_free_result($result);
	}
	
	public function getOne(){
		$query = $this->prepareQuery(func_get_args());
		if($res = $this->rawQuery($query)){
			$row = $this->fetch($res);
			if(is_array($row)){
				return reset($row);
			}
			$this->free($res);
		}
		return false;
	}
	
	public function getRow(){
		$query = $this->prepareQuery(func_get_args());
		if ($res = $this->rawQuery($query)) {
			$ret = $this->fetch($res);
			$this->free($res);
			return $ret;
		}
		return false;
	}
	
	public function getCol(){
		$ret   = array();
		$query = $this->prepareQuery(func_get_args());
		if($res = $this->rawQuery($query)){
			while($row = $this->fetch($res)){
				$ret[] = reset($row);
			}
			$this->free($res);
		}
		return $ret;
	}
	
	public function getAll(){
		$ret   = array();
		$query = $this->prepareQuery(func_get_args());
		if($res = $this->rawQuery($query)){
			while($row = $this->fetch($res)){
				$ret[] = $row;
			}
			$this->free($res);
		}
		return $ret;
	}
	
	public function getInd(){
		$args  = func_get_args();
		$index = array_shift($args);
		$query = $this->prepareQuery($args);

		$ret = array();
		if($res = $this->rawQuery($query)){
			while($row = $this->fetch($res)){
				$ret[$row[$index]] = $row;
			}
			$this->free($res);
		}
		return $ret;
	}
	
	/**
	 * @param string $index - имя столбца для использования в качестве ключа
	 * @param string $query - запрос
	 * @param mixed $params ....
	 * @return array
	 */
	public function getIndCol(){
		$args  = func_get_args();
		$index = array_shift($args);
		$query = $this->prepareQuery($args);

		$ret = array();
		if($res = $this->rawQuery($query)){
			while($row = $this->fetch($res)){
				$key = $row[$index];
				unset($row[$index]);
				$ret[$key] = reset($row);
			}
			$this->free($res);
		}
		return $ret;
	}
	
	public function parse(){
		return $this->prepareQuery(func_get_args());
	}
	
	public function whiteList($input,$allowed,$default=false){
		$found = array_search($input,$allowed);
		return ($found === false) ? $default : $allowed[$found];
	}
	
	public function filterArray($input,$allowed){
		foreach(array_keys($input) as $key){
			if(!in_array($key,$allowed)){
				unset($input[$key]);
			}
		}
		return $input;
	}
	
	public function lastQuery(){
		$last = end($this->stats);
		return $last['query'];
	}
	
	public function getStats(){
		return $this->stats;
	}
	
	protected function rawQuery($query){
		$start = microtime(true);
		$res   = mysqli_query($this->conn, $query);
		$timer = microtime(true) - $start;

		$this->stats[] = array(
			'query' => $query,
			'start' => $start,
			'timer' => $timer,
		);
		if(!$res){
			$error = mysqli_error($this->conn);
			
			end($this->stats);
			$key = key($this->stats);
			$this->stats[$key]['error'] = $error;
			$this->cutStats();
			
			$this->error($error.' Full query: ['.$query.']');
		}
		$this->cutStats();
		return $res;
	}

	protected function prepareQuery($args){
		$query = '';
		$raw   = array_shift($args);
		$array = preg_split('~(\?[nsiuapd])~u',$raw,null,PREG_SPLIT_DELIM_CAPTURE);
		$anum  = count($args);
		$pnum  = floor(count($array) / 2);
		if($pnum != $anum){
			$this->error('Number of args ('.$anum.') doesn\'t match number of placeholders ('.$pnum.') in ['.$raw.']');
		}

		foreach($array as $i => $part){
			if(($i % 2) == 0){
				$query .= $part;
				continue;
			}

			$value = array_shift($args);
			switch ($part){
				case '?n':
					$part = $this->escapeIdent($value);
					break;
				case '?s':
					$part = $this->escapeString($value);
					break;
				case '?i':
					$part = $this->escapeInt($value);
					break;
				case '?a':
					$part = $this->createIN($value);
					break;
				case '?u':
					$part = $this->createSET($value);
					break;
				case '?p':
					$part = $value;
					break;
				case '?d':
					$part = $this->createINSERT($value);
			}
			$query .= $part;
		}
		return $query;
	}

	protected function escapeInt($value){
		if(is_null($value)){
			return 'NULL';
		}
		if(!is_numeric($value)){
			$this->error('Integer (?i) placeholder expects numeric value, '.gettype($value).' given');
			return false;
		}
		if(is_float($value)){
			$value = number_format($value, 0, '.', ''); // may lose precision on big numbers
		}
		return $value;
	}

	protected function escapeString($value){
		if(is_null($value)){
			return 'NULL';
		}
		return	'\''.mysqli_real_escape_string($this->conn,$value).'\'';
	}

	protected function escapeIdent($value){
		if($value){
			return '`'.str_replace('`','``',$value).'`';
		}else{
			$this->error('Empty value for identifier (?n) placeholder');
		}
	}

	protected function createIN($data){
		if(!is_array($data)){
			$this->error('Value for IN (?a) placeholder should be array');
			return;
		}
		if(!$data){
			return 'NULL';
		}
		$query = $comma = '';
		foreach($data as $value){
			$query .= $comma.$this->escapeString($value);
			$comma  = ',';
		}
		return $query;
	}

	protected function createSET($data){
		if(!is_array($data)){
			$this->error('SET (?u) placeholder expects array, '.gettype($data).' given');
			return;
		}
		if(!$data){
			$this->error('Empty array for SET (?u) placeholder');
			return;
		}
		$query = $comma = '';
		foreach($data as $key => $value){
			$query .= $comma.$this->escapeIdent($key).'='.$this->escapeString($value);
			$comma  = ',';
		}
		return $query;
	}
	
	protected function createINSERT($data){
		if(!is_array($data)){
			$this->error('INSERT (?d) placeholder expects array, '.gettype($data).' given');
			return;
		}
		if(!$data){
			$this->error('Empty array for INSERT (?d) placeholder');
			return;
		}
		if(!is_array($data[0])){
			$this->error('INSERT (?d) placeholder expects array(array()), '.gettype($data[0]).' given');
			return;
		}
		$column = array_keys($data[0]);
		$len = sizeof($column);
		if(!$len){
			$this->error('INSERT (?d) placeholder expects data, empty data given');
			return;
		}
		$esc_column = array();
		for($i=0; $i<$len; $i++){
			$esc_column[$i] = $this->escapeIdent($column[$i]);
		}
		
		$query = '('.implode(',', $esc_column).') VALUES ';
		$comma = '';
		foreach($data as &$val){
			$query .= $comma.'(';
			$comma1 = '';
			for($i=0; $i<$len; $i++){
				$query .= $comma1.(isset($val[$column[$i]]) ? $this->escapeString($val[$column[$i]]) : 'NULL');
				$comma1  = ', ';
			}
			$query .= ')';
			$comma  = ', ';
		}
		return $query;
	}

	protected function error($err){
		$err  = __CLASS__ .': '.$err;
		$err .= '. Error initiated in '.$this->caller();

		if($this->emode == 'error'){
			trigger_error($err,E_USER_ERROR);
		}else{
			throw new $this->exname($err);
		}
	}

	protected function caller(){
		$trace  = debug_backtrace();
		$caller = '';
		foreach($trace as $t){
			if(isset($t['class']) && $t['class'] == __CLASS__ ){
				$caller = $t['file'].' on line '.$t['line'];
			}else{
				break;
			}
		}
		return $caller;
	}

	protected function cutStats(){
		if(count($this->stats) > 100){
			reset($this->stats);
			$first = key($this->stats);
			unset($this->stats[$first]);
		}
	}
}
