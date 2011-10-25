<?php
class Savant3_Plugin_checked extends Savant3_Plugin {
	
	/**
	 * Helper for returning checked="checked" if values are same
	 *
	 * @param mixed $val first value
	 * @param mixed $val2 second value to compare against $val
	 * @return string
	 */
	function checked($val,$val2) {
		return $val == $val2 ? 'checked="checked"' : '';
	}
}