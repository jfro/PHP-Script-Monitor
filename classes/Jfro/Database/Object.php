<?php
/**
 * This contains the ORM class built around PHP's PDO extension for database access
 *   the use of PDO provides prepared statements which provide decent SQL injection protection
 *   also implements ArrayAccess and Iterator from the SPL (http://www.php.net/spl) providing array-like behavior
 *   of any instance of the class
 * @author Jeremy Knope <jerome@rainstormconsulting.com>
 * @package Database
 */

Jfro::loadClass('Jfro_Database_Exception');

/**
 * Jfro_Database_Object simple ORM Class
 * ORM class built around PHP's PDO extension for database access
 *   the use of PDO provides prepared statements which provide decent SQL injection protection
 *   also implements ArrayAccess and Iterator from the SPL (http://www.php.net/spl) providing array-like behavior
 *   of any instance of the class
 *   the find function has to be defined in the child class and call the parent with table and class name
 * @author Jeremy Knope <jerome@rainstormconsulting.com>
 * @package Database
 */
class Jfro_Database_Object implements ArrayAccess,Iterator {
	static protected $db;
	static protected $cache = false;
	protected $_attributes;
	private $_it_position = 0; // iterator position
	protected $_errors;
	static $strict_attributes = true;
	//protected $table_name = '';
	
	function __construct($attr=array()) {
		$this->_attributes = array();
		$this->setAttributes($attr);
		$this->_errors = array();
	}
	
	/**
	 * Sets the database instance for Jfro_Database_Object, should be a PDO instance or something that emulates it
	 * @param PDO $db PDO connection instance
	 */
	static function setDatabase($db) {
		self::$db = $db;
	}
	
	static function enableCache($host='localhost', $port=11211) {
		if(!class_exists('Memcache')) {
			throw new Jfro_Database_Exception('To enable caching, the memcached extension must be loaded into PHP');
		}
		if(self::$cache === false) {
			self::$cache = new Memcache();
			self::$cache->connect($host, $port) or trigger_error('Failed to connect to memcached', E_USER_ERROR);
		}
	}
	
	/**
	 * Finds a single row or many rows given a table, class and criteria
	 * @param string $table_name name of the table to query
	 * @param string $class class name, ussually the child class like a User class for a users table
	 * @param mixed $id the id or tyep of search, 'all' for a select of many, a primary key for a single row
	 * @param array $conditions conditions for WHERE clause, in array("col1 = ? and col2 = ?",value1,value2) form
	 * @param string $order order by content, excluding the words order by
	 * @param string $limit the limit clause, can be 0 or 0,10 etc.
	 */
	static protected function _find($table_name, $class, $id, $conditions=null, $order=null, $limit=null, $fields='*') {
		if(self::$cache && $id != 'all' && $id != 'first') {
			$result = self::$cache->get($class.':'.$id);
			if($result) {
				print 'Cache result loading<br />';
				return $result;
			}
			else {
				print 'Failed... querying MySQL<br />';
			}
		}
		
		if(method_exists($class, 'getDatabase')) {
			eval("\$db = $class::getDatabase();");
		}
		if(!isset($db)) {
			$db = self::$db;
		}
	    if($id === 'all') {
	        $query = "SELECT %s.%s FROM %s WHERE 1 %s %s %s";
	        if($conditions && !is_array($conditions)) {
	            throw new Jfro_Database_Exception('Conditions must be an array with clause as first entry');
	        }
	        if(is_array($conditions) && array_key_exists(0, $conditions)) {
	            $where_clause = 'AND ('.array_shift($conditions).')';
	        }
	        else {
	        	$where_clause = '';
	        }
	        $query = sprintf($query, $table_name, $fields, $table_name, $where_clause, $order ? 'ORDER BY '.$order : '', $limit ? 'LIMIT '.$limit : '');
	        //print 'Preparing: '.$query.'<br />';
	        try {
	        	$stmt = $db->prepare($query);
				if(!$stmt) {
					throw new Jfro_Database_Exception('Error preparing query: '.implode(', ', $db->errorInfo()));
				}
	        }
	        catch(Exception $e) {
	        	throw new Jfro_Database_Exception('Error with query: '.$query.' Error: '.$e->getMessage());
	        }
	        if(count($conditions) > 0) {
    	        foreach($conditions as $key=>$value) {
    	            $key = $key + 1;
    	            $stmt->bindValue($key, $value, PDO::PARAM_STR);
    	        }
            }
	        
	        if(!$stmt->execute()) {
	            throw new Jfro_Database_Exception('Database query error');
	        }
	        //$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	        $out = array();
	        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	            $out[] = new $class($row);
	        }
	    }
	    else if($id === 'first') {
	    	$query = "SELECT %s.%s FROM %s WHERE 1 %s %s %s";
	        if($conditions && !is_array($conditions)) {
	            throw new Jfro_Database_Exception('Conditions must be an array with clause as first entry');
	        }
	        if(is_array($conditions) && array_key_exists(0, $conditions)) {
	            $where_clause = 'AND ('.array_shift($conditions).')';
	        }
	        else {
	        	$where_clause = '';
	        }
	        $query = sprintf($query, $table_name, $fields, $table_name, $where_clause, $order ? 'ORDER BY '.$order : '', $limit ? 'LIMIT '.$limit : '');
	        //print 'Preparing: '.$query.'<br />';
	        try {
	        	$stmt = $db->prepare($query);
	        }
	        catch(PDOException $e) {
	        	throw new Jfro_Database_Exception('Error with query: '.$query.' Error: '.$e->getMessage());
	        }
	        if(count($conditions) > 0) {
    	        foreach($conditions as $key=>$value) {
    	            $key = $key + 1;
    	            $stmt->bindValue($key, $value, PDO::PARAM_STR);
    	        }
            }
	        
	        if(!$stmt->execute()) {
	            throw new Jfro_Database_Exception('Database query error');
	        }
	        //$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	        $out = array();
	        $row = $stmt->fetch(PDO::FETCH_ASSOC);
	        
	        $out = is_array($row) ? new $class($row) : null;
	        
	        $stmt->fetchAll(); // clear the rest... might be better way?
	    }
	    else {
	    	$query = "SELECT %s.%s FROM %s WHERE id = ? %s";
	        if($conditions && !is_array($conditions)) {
	            throw new Jfro_Database_Exception('Conditions must be an array with clause as first entry');
	        }
	        if(is_array($conditions) && array_key_exists(0, $conditions)) {
	            $where_clause = 'AND ('.array_shift($conditions).')';
	        }
	        else {
	        	$where_clause = '';
	        }
	        $query = sprintf($query, $table_name, $fields, $table_name, $where_clause);
	        //$query = 'SELECT '.$fields.' FROM '.$table_name.' WHERE id = :id';
	        $stmt = $db->prepare($query);
	        if(!$stmt) {
	        	throw new Jfro_Database_Exception('Invalid statement created, Error: '.implode(',',$db->errorInfo()));
	        }
	        if(intval($id) > 0) {
	            $stmt->bindValue(1, $id, PDO::PARAM_INT);
	        }
	        else {
	            $stmt->bindValue(1, $id, PDO::PARAM_STR);
	        }
	        
	        if(count($conditions) > 0) {
    	        foreach($conditions as $key=>$value) {
    	            $key = $key + 2;
    	            $stmt->bindValue($key, $value, PDO::PARAM_STR);
    	        }
            }
	       
	        if(!$stmt->execute()) {
	            throw new Jfro_Database_Exception('Database query error');
	        }
	        $row = $stmt->fetch(PDO::FETCH_ASSOC);
	        $stmt->fetchAll();
	        $out = is_array($row) ? new $class($row) : null;
			if(self::$cache && $out && is_object($out)) {
				self::$cache->set($class.':'.$id, $out, false, 20);
			}
	    }
	    
	    return $out;
	}
	
	static function _findBySql($class, $queryArray) {
		if(method_exists($class, 'getDatabase')) {
			eval("\$db = $class::getDatabase();");
		}
		if(!isset($db)) {
			$db = self::$db;
		}
	    if(is_array($queryArray)) {
	        $out = array();
	        $query = array_shift($queryArray);
	        $stmt = $db->prepare($query);
	        if(count($queryArray) > 0) {
    	        foreach($queryArray as $key=>$value) {
    	            $key = $key + 1;
    	            $stmt->bindValue($key, $value, PDO::PARAM_STR);
    	        }
            }
            if(!$stmt->execute()) {
	            throw new Jfro_Database_Exception('Database query error, query: '.$query);
	        }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($rows as $row) {
	            $out[] = new $class($row);
            }
	    }
	    /*if(count($out) == 1) {
	        $out = $out[0];
	    }*/
	    
	    return $out;
	}
	
	/**
	 * Set the attributes to given associative array, should not be escaped for DB, this is done later
	 * @param array $attr associative array of attributes
	 */
	function setAttributes($attr) {
		//if(get_magic_quotes_gpc() && !$filter) {
		if($attr == null) {
			return;
		}
		if(!is_array($attr)) {
		    throw new Jfro_Database_Exception('Invalid attributes given, must be an array');
		}
		//$this->_attributes = $attr; // no checking yet, laziness alert
		$this->_attributes = array_merge($this->_attributes, $attr); // non destructive
		/*}
		else {
			foreach(array_keys($attr) as $key) {
				$this->_attributes[$key] = mysql_escape_string($attr[$key]);
			}
		}*/
	}
	
	function getAttributes() {
		return $this->_attributes;
	}
	
	function __get($name) {
		if(array_key_exists($name, $this->_attributes)) {
			$val = $this->_attributes[$name];
		}
		else {
			$val = null;
		}
		return $val;
	}
	
	function __set($name,$value) {
		$this->_attributes[$name] = $value;
	}

	/**
	 * Save function for storing the object back into the database
	 * @return bool
	 */
	function save() {
		if(!$this->id && !$this->validate_on_create()) {
			return false;
		}
		else if(!$this->validate_on_update()) {
			return false;
		}
		else if(!$this->validate_on_save()) {
			return false;
		}
		//$values = array();
		$keys = array_keys($this->_attributes);
		$query = 'REPLACE INTO '.$this->table_name.' SET ';
		$skip_keys = array();
		$query .= $keys[0].'=:'.$keys[0];
		for($i=1; $i<count($keys); $i++) {
			
			if(intval($this->id) > 0 && $keys[$i] == 'updated_on') {
				$query .= ",".$keys[$i]."=NOW()";
				$skip_keys[] = $keys[$i];
			}
			else if(!$this->id && $keys[$i] == 'created_on') {
				$query .= ",".$keys[$i]."=NOW()";
				$skip_keys[] = $keys[$i];
			}
			else if($keys[$i] == 'created_on' && !$this->created_on) {
				$query .= ",".$keys[$i]."=NOW()";
				$skip_keys[] = $keys[$i];
			}
			else if(($this->_attributes[$keys[$i]] === 'NULL' || $this->_attributes[$keys[$i]] === '')) {
				$query .= ",".$keys[$i]."=NULL";
				$skip_keys[] = $keys[$i];
			}
			else if($keys[$i]) {
				$value = $this->_attributes[$keys[$i]];
				$query .= ",".$keys[$i].'=:'.$keys[$i];
			}
		}
		try {
			$stmt = self::$db->prepare($query);
		}
		catch(PDOException $e) {
			throw new Jfro_Database_Exception('Failed to prepare query: '.$query.' Error: '.$e->getMessage());
		}
        
        foreach($keys as $key) {
        	try {
        		if(!in_array($key, $skip_keys)) {
        			$stmt->bindValue(':'.$key, $this->_attributes[$key]);
        		}
        	}
        	catch(PDOException $e) {
        		print 'Message: '.$e->getMessage();
        		throw new Jfro_Database_Exception('Failed to bind param: '.$key.' with value: '.$this->_attributes[$key].' query: '.$query);
        	}
            
        }
		if(!$stmt->execute()) {
			throw new Jfro_Database_Exception('Failure to execute: '.$query.' Error info: '.implode(',',self::$db->errorInfo()));
		}
        
        if(!$this->id) {
            $this->id = self::$db->lastInsertId();
            $this->after_create();
        }
		return true;
	}
	
	function delete($condition=null) {
		if(intval($this->id) > 0) {
			$query = 'DELETE FROM '.$this->table_name.' WHERE id = :id'; // LIMIT 1';
			$stmt = self::$db->prepare($query);
			$stmt->bindValue(':id',$this->id, PDO::PARAM_INT);
			
			if ($stmt->execute()) {
				$this->after_delete();
				return true;
			}
			
		}
		elseif ($condition != '') {
		
			$query = 'DELETE FROM '.$this->table_name.' WHERE id = :id '.$condition; //.' LIMIT 1';
			$stmt = self::$db->prepare($query);
			$stmt->bindValue(':id',$this->id, PDO::PARAM_INT);
			
			if ($stmt->execute()) {
				$this->after_delete();
				return true;
			}
			
		}
		
		return false;
	}
	
	/**
	 * Returns errors array
	 *
	 * @return array
	 */
	function errors() {
		return $this->_errors;
	}
	
	/**
	 * Validates a non-decimal number
	 *
	 * @param string $attr attribute name
	 * @param string $msg error message on failure
	 * @return bool
	 */
	function validate_number($attr, $msg) {
		if(preg_match('/\d+/', trim($this->_attributes[$attr]))) {
			return true;
		}
		else {
			$this->_errors[] = array($attr, $msg);
			return false;
		}
	}
	
	/**
	 * Validates a decimal number only, has to have 1 or more digits on both sides
	 *
	 * @param string $attr attribute name
	 * @param string $msg error message to display on failure
	 * @return bool
	 */
	function validate_decimal($attr, $msg) {
		if(preg_match('/\d+\.\d+/', trim($this->_attributes[$attr]))) {
			return true;
		}
		else {
			$this->_errors[] = array($attr, $msg);
			return false;
		}
	}
	
	/**
	 * Validates length of attribute
	 *
	 * @param string $attr attribute name
	 * @param integer $min minimum length
	 * @param integer $max max length
	 * @param string $msg error message if failed
	 * @return bool
	 */
	function validate_length($attr, $min, $max, $msg) {
		if(strlen($this->_attributes[$attr]) >= $min && strlen($this->_attributes[$attr]) <= $max) {
			return true;
		}
		else {
			$this->_errors[] = array($attr, $msg);
			return false;
		}
	}
	
	/**
	 * Checks if attribute exists and isn't empty
	 *
	 * @param string $attr attribute name
	 * @param string $msg error message on failure
	 * @return bool
	 */
	function validate_exists($attr, $msg) {
		if(array_key_exists($attr, $this->_attributes) && $this->_attributes[$attr]) {
			return true;
		}
		else {
			$this->_errors[] = array($attr, $msg);
			return false;
		}
	}
	
	/**
	 * Validates an email address in an attribute
	 *
	 * @param string $attr attribute name to check for a valid email in
	 * @param string $msg error message to display on failure
	 * @return bool
	 */
	function validate_email($attr, $msg) {
		if(preg_match(
				'/^[-!#$%&\'*+\\.\/0-9=?A-Z^_`{|}~]+'.   // the user name
				'@'.                                     // the ubiquitous at-sign
				'([-0-9A-Z]+\.)+' .                      // host, sub-, and domain names
				'([0-9A-Z]){2,4}$/i',                    // top-level domain (TLD)
				trim($this->_attributes[$attr]))) {
			return true;
		}
		else {
			$this->_errors[] = array($attr, $msg);
			return false;
		}
	}
	
	/**
	 * Validates a date based on the month, day, and year and checks to make sure it is in a numeric format
	 * separated by slashes
	 *
	 * @param string $attr attribute name
	 * @param string $msg error message to display on failure
	 * @return bool
	 */
	function validate_date($attr, $msg) {
		//split up the month, day, and year to pass to checkdate()
		if(strpos($this->_attributes[$attr], '/') !== false) {
			$splitdate = explode('/', $this->_attributes[$attr]);
		}

		if(preg_match('([0-9]{1,2}/([0-9]{1,2})/([0-9]{2,4}))', trim($this->_attributes[$attr])) && 
		  is_array($splitdate) && checkdate($splitdate[0],$splitdate[1],$splitdate[2]) == true) {
			return true;
		}
		else {
			$this->_errors[] = array($attr, $msg);
			return false;
		}
	}
	
	function after_delete() {
		
	}
	
	function after_create() {
		
	}
	
	function validate_on_save() {
		return true;
	}
	
	/**
	 * Override to verify information is correct
	 * @return bool
	 */
	function validate_on_create() {
		return true;
	}
	
	/**
	 * Override to verify information is correct
	 * @return bool
	 */
	function validate_on_update() {
		return true;
	}
	
	static function sql_error($msg) {
		throw new Jfro_Database_Exception($msg);
	}
	
	/** 
	 * ArrayAccess functions
	 */
	function offsetExists($offset) {
		if(array_key_exists($offset, $this->_attributes)) {
			return true;
		}
		
		return false;
	}
	
	function offsetGet($offset) {
		if(array_key_exists($offset, $this->_attributes)) {
			return $this->_attributes[$offset];
		}
		return false;
	}
	
	function offsetSet($offset, $value) {
		$this->_attributes[$offset] = $value;
	}
	
	function offsetUnset($offset) {
		unset($this->_attributes[$offset]);
	}
	/* *************** */
	
	/**
	 * Iterator functions
	 */
	function current() {
	    $keys = array_keys($this->_attributes);
	    $key = $keys[$this->_it_position];
	    return $this->_attributes[$key];
	}
	
	function key() {
	    $keys = array_keys($this->_attributes);
	    $key = $keys[$this->_it_position];
	    return $key;
	}
	
	function next() {
	    $this->_it_position += 1;
	}
	
	function rewind() {
	    $this->_it_position = 0;
	}
	
	function valid() {
	    $keys = array_keys($this->_attributes);
	    if(array_key_exists($this->_it_position, $keys)) {
	        if(array_key_exists($keys[$this->_it_position], $this->_attributes)) {
	            return true;
	        }
	    }
	    return false;
	}
	
	/** **************
	 * End iterator functions
	 */
}