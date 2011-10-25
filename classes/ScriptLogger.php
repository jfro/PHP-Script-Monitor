<?php
class ScriptLogger {
	protected $db;
	protected $start;
	protected $elapsed = 0;
	protected $limit = 1.0;
	protected $show_errors = null;
	protected $log_errors = null;
	
	/**
	 * Constructor, should not be called directly, getInstance() should be called instead
	 */
	function __construct($db_path='/tmp', $show_errors=null, $log_errors=null, $time_limit=1.0) {
		self::compatibilityCheck($db_path);
		$db_path .= '/script_events.'.self::dbExt();
		$ext = substr($db_path, strrpos($db_path, '.')+1);
		if($ext == 'sq2') {
			$dsn = 'sqlite2:'.$db_path;
		}
		else {
			$dsn = 'sqlite:'.$db_path;
		}
		$this->db = new PDO($dsn);
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->setupTable();
		$this->limit = $time_limit;
		if(!$show_errors) {
			$this->show_errors = E_ALL | E_STRICT;
		}
		else {
			$this->show_errors = $show_errors;
		}
		if(!$log_errors) {
			$this->log_errors = E_ALL | E_STRICT;
		}
		else {
			$this->log_errors = $log_errors;
		}
		$this->start();
	}
	
	/**
	 * Returns sqlite db extension depending on available PDO drivers
	 * @return string
	 */
	private static function dbExt() {
		$drivers = PDO::getAvailableDrivers();
		if(in_array('sqlite', $drivers)) {
			return 'sq3';
		}
		return 'sq2';
	}
	
	/**
	 * Runs check on db directory and PDO availability to report issues before continuing
	 */
	static function compatibilityCheck($db_path) {
		$error = false;
		if(!is_writable($db_path)) {
			print '<p><strong>Script Logger Error: </strong> The db directory, '.$db_path.', in script logger needs to be writable, please correct this</p>';
			$error = true;
		}
		if(class_exists('PDO')) {
			$drivers = PDO::getAvailableDrivers();
			if(!in_array('sqlite', $drivers) && !in_array('sqlite2', $drivers)) {
				print '<p><strong>Script Logger Error: </strong>There is no PDO SQLite drivers installed, ScriptLogger currently requires sqlite driver for PDO, see <a href="http://www.php.net/pdo">http://www.php.net/pdo</a> for more information about PDO and it\'s drivers</p>';
				$error = true;
			}
		}
		else {
			print '<p><strong>Script Logger Error: </strong>The <a href="http://www.php.net/pdo">PDO</a> extension is not loaded, this is currently required by script logger to access an SQLite 3 database</p>';
			$error = true;
		}
		if($error) {
			exit;
		}
	}
	
	/**
	 * Call to get a logger instance, or the already created instance, given the path to database folder, show and log error options and the slow-script time limit, these are ignored if the logger is already created.
	 * @param string $db_path Path to where the sqlite database will be stored
	 * @param integer $show_errors What errors to show, this uses the error constants, E_ERROR, E_ALL etc. see error_reporting()
	 * @param integer $log_errors Like show_errors except governs what errors get actually logged, E_ALL | E_STRICT will save strict along with all the others.
	 * @param integer $time_limit Limit in seconds for when a script is flagged as slow
	 */
	static function getInstance($db_path='/tmp', $show_errors=null, $log_errors=null, $time_limit=0) {
		static $instance;
		if(!isset($instance)) {
			$c = __CLASS__;
			$instance = new $c($db_path, $show_errors, $log_errors, $time_limit);
		}
		return $instance;
	}
	
	function getDatabase() {
		return $this->db;
	}
	
	private function setupTable() {
		$stmt = $this->db->prepare('SELECT name FROM sqlite_master WHERE name=\'script_events\' AND type=\'table\'');
		$stmt->execute();
		$rows = $stmt->fetchAll();
		if(count($rows) == 0) {
			$query = <<<EOT
CREATE TABLE  script_events (
  id integer PRIMARY KEY,
  type text,
  script text,
  time real,
  query text,
  post text,
  session text,
  error text,
  occurred_on text,
  trace text,
  line integer,
  url text,
  domain text,
  server text,
  occurrences integer,
  last_occurred_on text
)
EOT;
			$this->db->exec($query);
		}
	}
	
	function __destruct() {
		$this->end();
	}
	
	protected function start() {
		$this->start = microtime(true);
	}
	
	protected function end() {
		$this->elapsed = microtime(true) - $this->start;
		$this->check();
	}
	
	protected function check() {
		if($this->elapsed > $this->limit) {
			$this->store(true, false);
		}
	}
	
	function recentEvents() {
		$stmt = $this->db->prepare('SELECT * FROM script_events ORDER BY occurred_on DESC');
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	
	function cleanOldEvents() {
		$date = date('Y-m-d H:i:s', strtotime('- 7 days'));
		$stmt = $this->db->prepare('DELETE FROM script_events WHERE occurred_on < ?');
		$stmt->bindValue(1, $date);
		$stmt->execute();
	}
	
	protected function store($store_post=false, $store_session=false) {
		if($this->db) {
			$query = 'INSERT INTO script_events (type, script, time, query, post, session, occurred_on, url, domain, server, occurrences, last_occurred_on) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)';
			$stmt = $this->db->prepare($query);
			if(!$stmt) {
				print 'Error: '.implode(', ',$this->db->errorInfo());
			}
			$post_data = $store_post ? serialize($_POST) : '';
			$sess_data = $store_session ? serialize($_SESSION) : '';
			$occur_date = date('Y-m-d H:i:s');
			$stmt->bindValue(1, 'slow');
			$stmt->bindValue(2, $_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF']);
			$stmt->bindValue(3, $this->elapsed);
			$stmt->bindValue(4, $_SERVER['QUERY_STRING']);
			$stmt->bindValue(5, $post_data);
			$stmt->bindValue(6, $sess_data);
			$stmt->bindValue(7, $occur_date);
			$stmt->bindValue(8, $_SERVER['PHP_SELF']);
			$stmt->bindValue(9, $_SERVER['HTTP_HOST']);
			$stmt->bindValue(10, $_SERVER['SERVER_NAME']);
			$stmt->bindValue(11, 1);
			$stmt->bindValue(12, $occur_date);
			$stmt->execute();
		}
	}
	
	/**
	 * Store a basic, non-exception, error into the logger database
	 * @param integer $type One of the error types, E_ERROR etc.
	 * @param string $script the script path and filename
	 * @param integer $line line number error occurred on
	 * @param $trace trace data if any (probably not)
	 */
	function storeError($type, $script, $line, $error, $trace=null) {
		if($this->db) {
			$query = 'SELECT id,occurrences FROM script_events WHERE type = ? AND script = ? AND line = ? LIMIT 1';
			try {
				$stmt = $this->db->prepare($query);
				$stmt->bindValue(1, $type);
				$stmt->bindValue(2, $script);
				$stmt->bindValue(3, $line);
				$stmt->execute();
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				if(count($rows) > 0) {
					$existing_row = $rows[0];
				}
				else {
					$existing_row = false;
				}
			}
			catch(Exception $e) {
				print '<p><strong>Script Logger Error:<strong> Failure to query for existing event for: '.$script.' Error: '.$e->getMessage().'</p>';
				return;
			}
			if($existing_row) {
				$query = 'UPDATE script_events SET occurrences=?, last_occurred_on=? WHERE id=?';
				try {
					$stmt = $this->db->prepare($query);
					$stmt->bindValue(1, $existing_row['occurrences'] + 1);
					$stmt->bindValue(2, date('Y-m-d H:i:s'));
					$stmt->bindValue(3, $existing_row['id']);
					$stmt->execute();
				}
				catch(Exception $e) {
					print '<p><strong>Script Logger Error:<strong> Failure to update existing event: '.$e->getMessage().'</p>';
					return;
				}
			}
			else { // doesn't exist, insert
				$query = 'INSERT INTO script_events (type, script, time, query, post, session, occurred_on, error, trace, line, url, domain, server, occurrences, last_occurred_on) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
				try {
					$stmt = $this->db->prepare($query);
				}
				catch(Exception $e) {
					print '<p><strong>Script Logger Error:<strong> Failure to prepare error storage query: '.$e->getMessage().'</p>';
					return;
				}
				if(!$stmt) {
					print '<p><strong>Script Logger Error:<strong> Statement invalid, db error: '.implode(', ',$this->db->errorInfo()).'</p>';
				}
				$post_data = count($_POST) ? serialize($_POST) : '';
				$sess_data = session_id() ? serialize($_SESSION) : '';
				$occur_date = date('Y-m-d H:i:s');
				$stmt->bindValue(1, $type);
				$stmt->bindValue(2, $script);
				$stmt->bindValue(3, $this->elapsed);
				$stmt->bindValue(4, $_SERVER['QUERY_STRING']);
				$stmt->bindValue(5, $post_data);
				$stmt->bindValue(6, $sess_data);
				$stmt->bindValue(7, $occur_date);
				$stmt->bindValue(8, $error);
				$stmt->bindValue(9, $trace ? serialize($trace) : '');
				$stmt->bindValue(10, $line);
				$stmt->bindValue(11, $_SERVER['PHP_SELF']);
				$stmt->bindValue(12, $_SERVER['HTTP_HOST']);
				$stmt->bindValue(13, $_SERVER['SERVER_NAME']);
				$stmt->bindValue(14, 1);
				$stmt->bindValue(15, $occur_date);
				try {
					$stmt->execute();
				}
				catch(Exception $e) {
					print '<p><strong>Script Logger Error:<strong> Exception with storing error: '.$e->getMessage().'</p>';
					return;
				}
			}
		}
	}
	
	function handleError($errno, $errstr, $errfile=null, $errline=null, $errcontext=null) {
		switch($errno) {
			case E_USER_NOTICE:
			case E_NOTICE:
				$type = 'notice';
				break;
			case E_USER_WARNING:
			case E_WARNING:
				$type = 'warning';
				break;
			case E_USER_ERROR:
			case E_ERROR:
				$type = 'error';
				break;
			case E_STRICT:
				$type = 'strict';
				break;
			default:
				$type = $errno;
		}
		if($errno & $this->log_errors) {
			$this->storeError($type, $errfile, $errline, $errstr);
		}
		if($errno & $this->show_errors) {
			print '<p><strong>'.ucfirst($type).': </strong> '.$errstr.' in '.$errfile.' on line <strong>'.$errline.'</strong></p>';
		}
	}
	
	function handleException($exception) {
		$this->storeError('exception', $exception->getFile(), $exception->getLine(), $exception->getMessage(), $exception->getTrace());
		if($this->show_errors) {
			print '<p><strong>Exception: </strong> '.$exception->getMessage().' in '.$exception->getFile().' on line <strong>'.$exception->getLine().'</strong></p>';
			print '<ul>';
			foreach($exception->getTrace() as $key=>$line) {
				if(array_key_exists('class', $line)) {
					$function = $line['class'].'::'.$line['function'];
				}
				else {
					$function = $line['function'];
				}
				print '<li>'.$key.' - '.$function.' - '.$line['file'].':'.$line['line'].'</li>';
			}
			print '</ul>';
		}
	}
	
	protected function log($file, $time) {
		fwrite($fp, 'Slow PHP page: '.$_SERVER['REQUEST_URI'].' execution time: '.$this->elapsed.', File: '.$_SERVER['PHP_SELF']);
	}
}


function sl_error_handler($errno, $errstr, $errfile=null, $errline=null, $errcontext=null) {
	$st = ScriptLogger::getInstance();
	$st->handleError($errno, $errstr, $errfile, $errline, $errcontext);
}
function sl_exception_handler($exception) {
	$st = ScriptLogger::getInstance();
	$st->handleException($exception);
}

set_exception_handler('sl_exception_handler');
set_error_handler('sl_error_handler');