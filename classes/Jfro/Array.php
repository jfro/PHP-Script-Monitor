<?php
/**
 * Jfro_Array Class
 * 
 * @package Array
 */

/**
 * Jfro_Array Class
 * A wrapper around PHP array, allowing for more complex OOP where arrays are involved but still behave like an array
 * 
 * @package Array
 */
class Jfro_Array implements ArrayAccess,Iterator,Countable {
	protected $data = null;
	
	/**
	 * Create new array with given php array
	 * 
	 * @param array $array Array to start with
	 */
	function __construct($array=null) {
		$numargs = func_num_args();
		if(is_array($array) && $numargs == 1) {
			$this->data = $array;
		}
		else if($numargs > 1) {
			$this->data = array();
			for($i=0; $i < $numargs; $i++) {
				$this->data[$i] = func_get_arg($i);
			}
		}
		else {
			$this->data = array();
		}
	}
	
	/**
	 * Converts an entry given it's key to a Jfro_Array instance if it's an array
	 * 
	 * @param mixed $key string or integer array key
	 */
	private function convert($key) {
		if(is_array($this->data[$key])) {
			$this->data[$key] = new Jfro_Array($this->data[$key]);
		}
	}
	
	/**
	 * Returns true if the array has the given key/offset
	 * 
	 * @param mixed $offset key or index
	 */
	function has($offset) {
		return array_key_exists($offset, $this->data);
	}
	
	/**
	 * Returns count of items in the array
	 */
	function count() {
		return count($this->data);
	}
	
	/**
	 * Returns the array keys
	 * 
	 * @return array
	 */
	function keys() {
		return array_keys($this->data);
	}
	
	/**
	 * Returns a string with all array elements glued together with given delim
	 *
	 * @param string $delim string delimeter to separate array elements with
	 * @return string
	 */
	function join($delim=',') {
		return implode($delim, $this->data);
	}
	
	/**
	 * This spits out a string representation if you do print $object;
	 * 
	 * @return string
	 */
	function __toString() {
		$out = 'Jfro_Array(';
		$i = 0;
		foreach($this->data as $key => $value) {
			if($i > 0) {
				$out .= ', ';
			}
			$out .= $key.' => '.$value;
			$i++;
		}
		$out .= ')';
		return $out;
	}
	
	/**
	 * Iterator functions
	 */
	function current() {
	    $keys = array_keys($this->data);
	    $key = $keys[$this->_it_counter];
		$this->convert($key);
	    return $this->data[$key];
	}
	

	function key() {
	    $keys = array_keys($this->data);
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
	    $keys = array_keys($this->data);
	    if(array_key_exists($this->_it_counter, $keys)) {
	        if(array_key_exists($keys[$this->_it_counter], $this->data)) {
	            return true;
	        }
	    }
	    return false;
	}
	/* **************** */

	/**
	 * ArrayAccess functions
	 */
	function offsetExists($offset) {
		if(array_key_exists($offset, $this->data)) {
			return true;
		}
		
		return false;
	}

	function offsetGet($offset) {
		if(array_key_exists($offset, $this->data)) {
			$this->convert($offset);
			return $this->data[$offset];
		}
		return false;
	}

	function offsetSet($offset, $value) {
		$this->data[$offset] = $value;
		$this->convert($offset);
	}

	function offsetUnset($offset) {
		unset($this->data[$offset]);
	}
	/* *************** */
}