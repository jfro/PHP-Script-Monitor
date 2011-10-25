<?php
/**
 * Jfro_Database_Migration, database migrations system for helping schema upgrades and versioning
 * @author Jeremy Knope <jerome@rainstormconsulting.com>
 * @package Database
 * @subpackage Migration
 */

/**
 * Interface for migration files
 */
Jfro::loadInterface('Jfro_Database_Migration_Interface');
Jfro::loadClass('Jfro_Database_Migration_Table');

/**
 * Jfro_Database_Migration class, base class and behavior for doing database migrations
 * 
 * @package Database
 * @subpackage Migration
 */
class Jfro_Database_Migration {
	static $db;
	static $path;
	
	/**
	 * Returns Jfro_Database_Migration_Table that you can add columsn to then save
	 *
	 * @param string $table table name
	 * @return Jfro_Database_Migration_Table
	 */
	function create_table($table) {
		return new Jfro_Database_Migration_Table($table,self::$db);
	}
	
	function rename_table($table, $new_table) {
		$query = 'ALTER TABLE '.$table.' RENAME '.$new_table;
		self::$db->exec($query);
	}
	
	function add_column($table, $column, $type) {
		$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$column.' '.$type;
		self::$db->exec($query);
	}
	
	function drop_column($table, $name) {
		$query = 'ALTER TABLE '.$table.' DROP COLUMN '.$name;
		self::$db->exec($query);
	}
	
	function add_index($table, $name, $type) {
		$query = 'ALTER TABLE '.$table.' ADD '.$type.'('.$name.');';
		try {
			self::$db->exec($query);
		}
		catch(PDOException $e) {
			$msg = 'Query: '.$query.'<br />';
			$msg .= 'Error: '.$e->getMessage();
			$msg .= '<br />';
			throw new Jfro_Database_Migration_Exception($msg);
		}
	}
	
	function drop_primary($table) {
		$query = 'ALTER TABLE '.$table.' DROP PRIMARY KEY;';
		self::$db->exec($query);
	}
	
	function drop_table($table) {
		$query = 'DROP TABLE '.$table.';';
		self::$db->exec($query);
	}
	
	function rename_column($table, $column, $new_column) {
		$query = "ALTER TABLE ".$table." CHANGE COLUMN ".$column.' '.$new_column.' '.$this->column_definition($table, $column);
		try {
			self::$db->exec($query);
		}
		catch(PDOException $e) {
			$msg = 'Query: '.$query.'<br />';
			$msg .= 'Error: '.$e->getMessage();
			$msg .= '<br />';
			throw new Jfro_Database_Migration_Exception($msg);
		}
	}
	
	function change_column($table, $column, $new_type) {
		$query = "ALTER TABLE ".$table." MODIFY COLUMN ".$column.' '.$new_type;
		self::$db->exec($query);
	}
	
	private function column_definition($table, $column) {
		$query = 'DESCRIBE '.$table.' '.$column;
		$row = self::$db->query($query)->fetch(PDO::FETCH_ASSOC);
		$type = $row['Type'];
		if($row['Null'] != 'YES') {
			$type .= ' NOT NULL';
		}
		if($row['Default'] == 'NULL') {
			$type .= ' DEFAULT NULL';
		}
		else if($row['Default']) {
			$type .= ' DEFAULT \''.$row['Default'].'\'';
		}
		if($row['Extra']) {
			$type .= ' '.$row['Extra'];
		}
		return $type;
	}
	
	static function current_version() {
		require_once str_replace('migrations','',self::$path).'/database.php';
		$query = 'SELECT version FROM schema_info WHERE module = :module LIMIT 1';
		try {
			$stmt = self::$db->prepare($query);
			if(!$stmt) {
				throw new Exception('Invalid statement error: '.implode(',',self::$db->errorInfo()));
			}
			$m = SCHEMA_MODULE;
			$stmt->bindParam(':module', $m);
			if($stmt->execute()) {
				$rows = $stmt->fetchAll();
				if(count($rows) > 0) {
					$row = $rows[0];
					$version = $row['version'];
				}
				else {
					$version = 0;
				}
			}
			else {
				$version = 0;
			}
		}
		catch(PDOException $e) {
			$version = 0;
		}
		return intval($version);
	}
	
	static function latest_version() {
		$it = new DirectoryIterator(self::$path);
		$files = array();
		foreach($it as $file) {
			if(substr($file, 0,1) != '.' && substr($file, -3) == 'php') {
				$files[] =$file->getFilename();
			}
		}
		sort($files);
		$last_version = null;
		foreach($files as $file) {
			$class = ucfirst(str_replace('.php','',substr($file, strpos($file,'_')+1)));
			$v = intval(substr($file,0,strpos($file,'_')));
			if($file && $v) {
				$last_version = $v;
			}
		}
		return $last_version;
	}
	
	static function migrate() {
		self::check_schema_table();
		$version = self::current_version();
		$it = new DirectoryIterator(self::$path);
		$files = array();
		foreach($it as $file) {
			if(substr($file, 0,1) != '.' && substr($file, -3) == 'php') {
				$files[] =$file->getFilename();
			}
		}
		sort($files);
		$last_version = null;
		foreach($files as $file) {
			$class = ucfirst(str_replace('.php','',substr($file, strpos($file,'_')+1)));
			$v = intval(substr($file,0,strpos($file,'_')));
			if($file && $v > $version) {
				$last_version = $v;
				require_once self::$path.'/'.$file;
				$m = new $class();
				self::$db->beginTransaction();
				try {
					$m->up();
				}
				catch(Exception $e) {
					self::$db->rollBack();
					print $e->getMessage();
					exit();
				}
				self::$db->commit();
				// update version since we successfully did one...
				$query = 'REPLACE INTO schema_info SET version='.$last_version.',module = :module';
				$stmt = self::$db->prepare($query);
				$m = SCHEMA_MODULE;
				$stmt->bindParam(':module', $m);

				$stmt->execute();
			}
		}
		if($last_version) {
			require_once str_replace('migrations','',self::$path).'/database.php';
			print 'Done, upgraded '.SCHEMA_MODULE.' to: '.$last_version."\n";
			$query = 'REPLACE INTO schema_info SET version='.$last_version.',module = :module';
			$stmt = self::$db->prepare($query);
			$m = SCHEMA_MODULE;
			$stmt->bindParam(':module', $m);
			
			$stmt->execute();
		}
		else {
			print 'No upgrade needed'."\n";
		}
	}
	
	static function check_schema_table() {
		$query = 'SELECT * FROM schema_info';
		try {
			$stmt = self::$db->prepare($query);
			$stmt->execute();
		}
		catch(PDOException $e) {
			$query = <<<HEREDOC
CREATE TABLE  `schema_info` (
  `module` varchar(64) NOT NULL default '',
  `version` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`module`)
) ENGINE=MyISAM
HEREDOC;
			self::$db->exec($query);
		}
	}
	
	static function convert2innodb($tables=null) {
		if(!$tables) {
			$query = 'SHOW TABLES';
			foreach(self::$db->exec($query) as $row) {
				$tables[] = $row[0];
			}
		}
		$query = 'ALTER TABLE %s ENGINE=InnoDB';
		foreach($tables as $t) {
			self::$db->exec(sprintf($query, $t));
		}
	}
	
	static function convert2myisam($tables=null) {
		if(!$tables) {
			$query = 'SHOW TABLES';
			foreach(self::$db->query($query) as $row) {
				$tables[] = $row[0];
			}
		}
		$query = 'ALTER TABLE %s ENGINE=MyISAM';
		foreach($tables as $t) {
			self::$db->query(sprintf($query, $t));
		}
	}
}
?>