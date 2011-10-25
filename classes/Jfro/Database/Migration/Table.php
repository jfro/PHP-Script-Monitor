<?php
/**
 * Jfro_Database_Migration_Table, class that handles new table creations
 * @author Jeremy Knope <jerome@rainstormconsulting.com>
 * @package Database
 * @subpackage Migration
 */

/**
 * Jfro_Database_Migration_Table, class that handles new table creations
 * @author Jeremy Knope <jerome@rainstormconsulting.com>
 * @package Database
 * @subpackage Migration
 */
class Jfro_Database_Migration_Table {
	private $table_name;
	private $db;
	private $columns;
	private $engine = 'MYISAM';
	
	/**
	 * Create a new table instance, saved to db with save() call
	 *
	 * @param string $name table name
	 * @param PDO $db a PDO connection object
	 */
	function __construct($name,$db) {
		$this->columns = array();
		$this->table_name = $name;
		$this->db = $db;
	}
	
	/**
	 * Column definition
	 *
	 * @param string $name name of the column
	 * @param string $type the type of column
	 * @param string $options any extra options that come after type
	 */
	function column($name, $type, $options=null) {
		$this->columns[] = $name.' '.$type.' '.$options;
	}
	
	/**
	 * Saves table to database
	 */
	function save() {
		$query = 'CREATE TABLE '.$this->table_name.' (';
		$query .= $this->columns[0];
		for($i=1; $i < count($this->columns); $i++) {
			$query .= ','.$this->columns[$i];
		}
		$query .= ') ENGINE = '.$this->engine.';';
		try {
			$this->db->query($query);
		}
		catch(PDOException $e) {
			$msg = 'Failed to run query: '.$query.'<br />';
			$msg .= $e->getMessage().'<br /><hr />';
			//print "<pre>";
			throw new Jfro_Database_Migration_Exception($msg);
			//print "</pre><hr />";
		}
		
	}
	
	/**
	 * Sets the table engine, like MyISAM
	 *
	 * @param string $eng Engine name, INNODB or MyISAM etc.
	 */
	function setEngine($eng) {
		$this->engine = $eng;
	}
}