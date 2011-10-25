<?php
/**
 * File: Request.php
 * 
 * @author Jeremy Knope <jerome@rainstorminc.com>
 * @package Request
 */

/**
 * Load Jfro_Array class
 */
Jfro::loadClass('Jfro_Array');

/**
 * Class: Jfro_Request
 * 
 * @author Jeremy Knope <jerome@rainstorminc.com>
 * @package Request
 */
class Jfro_Request implements ArrayAccess,Iterator {
	protected $_data;
	protected $_post;
	protected $_get;
	protected $_it_counter;
	
	/**
	 * Constuctor, if we have POST vars, we give them priority.  GET & POST don't co-exist well usually anyway (at least with IE)
	 */
	function __construct() {
		$temp = $_POST;
		foreach($_GET as $key => $value) {
			if(!array_key_exists($key, $temp)) {
				$temp[$key] = $value;
			}
		}
		$this->_data = new Jfro_Array($temp);
		$this->_post = new Jfro_Array($_POST);
		$this->_get  = new Jfro_Array($_GET);
	}
	
	/**
	 * Returns true if this is a POST request
	 */
	function isPost() {
		if($_POST && count($_POST) > 0) {
			return true;
		}
		return false;
	}
	
	/**
	 * Returns true if this was an ajax call
	 *
	 * @return bool
	 */
	function isXr() {
		if(array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
			return true;
		}
		return false;
	}
	
	/**
	 * Returns true if the key exists in post or get
	 * 
	 * @param string $name Attribute name
	 */
	function has($name) {
		if($this->_data->has($name)) {
			return true;
		}
		return false;
	}
	
	/**
	 * Returns true if the key exists in GET only
	 *
	 * @param string $name key name
	 * @return bool
	 */
	function hasGet($name) {
		if(array_key_exists($name, $_POST)) {
			return true;
		}
		return false;
	}
	
	/**
	 * Returns true if the key exists in POST only
	 *
	 * @param string $name key name
	 * @return bool
	 */
	function hasPost($name) {
		if($this->isPost() && $this->_post->has($name)) {
			return true;
		}
		return false;
	}
	
	/**
	 * Return a post only value for given key if it exists
	 *
	 * @param string $key associative array key name
	 * @return mixed
	 */
	function post($key) {
		if($this->hasPost($key)) {
			return $_POST[$key];
		}
		return null;
	}
	
	/**
	 * Return a get only value for given key if it exists
	 *
	 * @param string $key associative array key name
	 * @return mixed
	 */
	function get($key) {
		if($this->hasGet($key)) {
			return $_GET[$key];
		}
		return null;
	}
	
	/**
	 * Returns a $_SERVER variable given it's key
	 * 
	 * @param string $var server associative array key
	 * @return string
	 */
	function server($var=null) {
		if($var && array_key_exists(strtoupper($var), $_SERVER)) {
			return $_SERVER[strtoupper($var)];
		}
		else if(!$var) {
			return $_SERVER;
		}
		return null;
	}
	
	/**
	 * Return attribute
	 * 
	 * @param string $name attribute name
	 */
	function __get($name) {
		trigger_error('This might be deprecated soon', E_USER_WARNING);
		if($name == '_post') {
			return $_POST ? $_POST : array();
		}
		else if($name == '_get') {
			return $_GET ? $_GET : array();
		}
		else if($name == '_server') {
			return $_SERVER;
		}
		else if(array_key_exists($name, $_POST)) {
			return $_POST[$name];
		}
		else if(array_key_exists($name, $_GET)) {
			return $_GET[$name];
		}
		return null;
	}
	
	/**
	 * ArrayAccess functions
	 */
	function offsetExists($offset) {
		return $this->_data->has($offset);
	}

	function offsetGet($offset) {
		if($this->_data->has($offset)) {
			return $this->_data[$offset];
		}
		return false;
	}

	function offsetSet($offset, $value) {
		throw new Jfro_Exception('You may not write to Jfro_Request at this time');
		//$this->_post[$offset] = $value;
	}

	function offsetUnset($offset) {
		if($this->_data->has($offset)) {
			unset($this->_data[$offset]);
		}
	}
	/* *************** */

	/**
	 * Iterator functions
	 */
	function current() {
	    $keys = $this->_data->keys();
	    $key = $keys[$this->_it_counter];
	    return $this->_data[$key];
	}

	function key() {
	    $keys = $this->_data->keys();
	    $key = $keys[$this->_it_counter];
	    return $key;
	}

	function next() {
	    $this->_it_counter += 1;
	}

	function rewind() {
	    $this->_it_counter = 0;
	}

	function valid() {
	    $keys = $this->_data->keys();
	    if(array_key_exists($this->_it_counter, $keys)) {
	        if($this->_data->has($keys[$this->_it_counter])) {
	            return true;
	        }
	    }
	    return false;
	}
	/* **************** */

	
}
