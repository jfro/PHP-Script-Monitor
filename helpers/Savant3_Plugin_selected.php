<?php
class Savant3_Plugin_selected extends Savant3_Plugin {
	
	/**
	 * Helper for returning selected="selected" if values are same
	 *
	 * @param mixed $val first value
	 * @param mixed $val2 second value to compare against $val
	 * @return string
	 */
	function selected($val,$val2) {
		return $val == $val2 ? 'selected="selected"' : '';
	}
}