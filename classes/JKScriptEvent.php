<?php
/**
 * Table access class for JKScriptEvent
 * @author Jeremy Knope <jerome@rainstorminc.com>
 * @package SiteAdmin
 */
class JKScriptEvent extends Jfro_Database_Object {
	protected $table_name = 'script_events';
	
	/**
	 * Returns all script_events rows or single row given an id
	 * @param mixed $id primary key or 'all' or 'first'
	 * @param array $conditions
	 * @param string $order
	 * @param string $limit
	 * @param string $fields
	 * @return mixed
	 */
	static function find($id, $conditions=null, $order=null, $limit=null, $fields='*') {
		return parent::_find('script_events', 'JKScriptEvent', $id, $conditions, $order, $limit, $fields);
	}
	
	static function paginate($id, $conditions=null, $order=null, $per_page, $page=1) {
		$limit = $page * $per_page.','.$per_page;
		$rows = self::countQuery('script_events', 'JKScriptEvent', $id, $conditions);
		$page_count = ceil($rows / $per_page);
		return array(parent::_find('script_events', 'JKScriptEvent', $id, $conditions, $order, $limit),$page_count);
	}
	
	static function countQuery($table_name, $class, $id, $conditions) {
		if(method_exists($class, 'getDatabase')) {
			eval("\$db = $class::getDatabase();");
		}
		if(!isset($db)) {
			$db = self::$db;
		}
		if($id === 'all') {
	        $query = "SELECT count(*) as row_count FROM %s WHERE 1 %s";
	        if($conditions && !is_array($conditions)) {
	            throw new Jfro_Database_Exception('Conditions must be an array with clause as first entry');
	        }
	        if(is_array($conditions) && array_key_exists(0, $conditions)) {
	            $where_clause = 'AND ('.array_shift($conditions).')';
	        }
	        else {
	        	$where_clause = '';
	        }
	        $query = sprintf($query, $table_name, $where_clause);
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
	        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$out = $rows[0]['row_count'];
			return $out;
	    }
	    else if($id === 'first') {
	    	return 1;
	    }
	    else {
	    	return 1;
	    }
	}
	
	/**
	 * Returns all rows matching the query given, using replacement arguments
	 * @param array $query array with query and arguments for it
	 * @return array
	 */
	static function findBySql($query) {
		return parent::_findBySql('JKScriptEvent', $query);
	}
	
	static function typeCounts($perc=false) {
		$out = array();
		$types = array('exception','error','warning','slow','notice','strict');
		$res = self::findBySql(array('SELECT COUNT(*) as acount FROM script_events'));
		if($res) {
			$total = $res[0]->acount;
		}
		else {
			$total = 1;
		}
		foreach($types as $type) {
			if($perc) {
				$out[$type] = round((self::typeCount($type) / $total) * 100);
			}
			else {
				$out[$type] = self::typeCount($type);
			}
		}
		return $out;
	}
	
	static function typeCount($type) {
		$res = self::findBySql(array('SELECT COUNT(*) as acount FROM script_events WHERE type=?',$type));
		if($res) {
			$res = $res[0]->acount;
		}
		else {
			$res = 0; 
			
		}
		return $res;
	}
	
	function source() {
		if(file_exists($this->script)) {
			$out = file($this->script);
			foreach($out as $key=>$line) {
			$out[$key] = '<a name="line'.($key+1).'"></a>'.htmlentities($line);
			}
			if($this->line > 0) {
				$out[$this->line-1] = '<span class="selected-line">'.$out[$this->line-1].'</span>';
			}
			$out = implode("\n", $out);
		}
		else {
			$out = null;
		}
		
		return $out;
	}
	
	function formatOccurredOn($format='F jS, Y h:i:s A') {
		return date($format, strtotime($this->occurred_on));
	}
	
	function formatLastOccurredOn($format='F jS, Y h:i:s A') {
		return date($format, strtotime($this->last_occurred_on));
	}
	
	function __get($name) {
		if($name == 'icon_name') {
			$ret = $this->type == 'slow' ? 'clock_error' : 'script_error';
		}
		else if($name == 'post' || $name == 'session' || $name == 'trace') {
			$value = parent::__get($name);
			$ret = $value ? unserialize($value) : null;
		}
		else {
			$ret = parent::__get($name);
		}
		return $ret;
	}
}
