<?php
class Savant3_Plugin_tree extends Savant3_Plugin {
	
	/**
	 * Creates a javascript tree view with given object or array
	 *
	 * @param mixed $tree
	 * @return string
	 */
	function tree($tree) {
		$meat = $this->_rec($tree, true);
		$start = <<<EOT
<ul class="tree">
	$meat
</ul>
EOT;
		return $start;
	}
	
	private function _type($var) {
		switch(1) {
			case is_array($var):
				return 'array';
				break;
			case is_object($var):
				return 'object';
				break;
			case is_int($var):
				return 'int';
				break;
			case is_float($var):
				return 'float';
				break;
			case is_string($var):
				return 'string';
				break;
			case is_resource($var):
				return 'resource';
				break;
			default:
				return 'unknown';
		}
	}
	
	private function _rec($obj, $skip_ul=false) {
		$out = '';
		if(!$skip_ul) {
			$out .= '<ul>';
		}
		if(is_array($obj) || (is_object($obj) && $obj instanceof Iterator)) {
			foreach($obj as $key=>$value) {
				$out .= '<li>';
				$type = $this->_type($value);
				$out .= '<a href="#">'.$key.'</a> ('.$type.') &rarr; ';
				if(is_object($value) || is_array($value)) {
					$out .= $this->_rec($value);
				}
				else if(is_object($obj) || is_resource($obj)) {
					$out .= 'non-iteratable object/resource';
				}
				else if(is_scalar($obj)) {
					$out .= $value;
				}
				$out .= '</li>';
			}
		}
		else {
			$out .= '(object)';
		}
		if(!$skip_ul) {
			$out .= '</ul>';
		}
		return $out;
	}
}