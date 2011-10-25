<?php
/**
 * Jfro_Database_Migration_Export, export file to produce a starting migration file of current database structure
 * @author Jeremy Knope <jerome@rainstormconsulting.com>
 * @package Database
 * @subpackage Migration
 */

/**
 * Template strings
 */
$class_template = <<<EOT
<?php
class %s extends Jfro_Database_Migration implements Jfro_Database_Migration_Interface {
	function up() {
%s
	}
	
	function down() {

	}
}
EOT;

$table_template = <<<EOT
		\$t = \$this->create_table('%s');
%s		\$t->save();

%s

EOT;

$column_template = <<<EOT
		\$t->column("%s","%s");

EOT;

$index_template = <<<EOT
		\$this->add_index("%s","%s","%s");

EOT;

/**
 * Jfro_Database_Migration_Export, export class file to produce a starting migration file of current database structure
 * @package Database
 * @subpackage Migration
 */
class Jfro_Database_Migration_Export {
	static $db;
	
	static function export($name) {
		global $class_template;
		$query = 'SHOW TABLES';
		$tables = '';
		$table_names = self::$db->query($query)->fetchAll(PDO::FETCH_NUM);
		foreach($table_names as $t) {
			$tables .= self::dump_table($t[0]);
		}
		return sprintf($class_template, $name, $tables);
	}
	
	static function dump_table($table) {
		global $table_template,$column_template,$index_template;
		$columns = '';
		$indexes = '';
		$query = 'SHOW COLUMNS FROM '.$table;
		$column_infos = self::$db->query($query)->fetchAll(PDO::FETCH_ASSOC);
		foreach($column_infos as $c) {
			$options = '';
			if($c['Key'] == 'PRI') {
				$options = ' PRIMARY KEY';
			}
			if($c['Null'] == 'NO') {
				$options .= ' NOT NULL';
			}
			if($c['Default']) {
				if($c['Default'] == 'NULL') {
					$options .= ' DEFAULT NULL';
				}
				else {
					$options .= ' DEFAULT \''.$c['Default'].'\'';
				}
			}
			if($c['Extra']) {
				$options .= ' '.$c['Extra'];
			}
			$columns .= sprintf($column_template, $c['Field'], $c['Type'].$options);
			if($c['Key']) {
				if($c['Key'] == 'UNI') {
					$key_type = 'UNIQUE';
				}
				else if($c['Key'] == 'MUL') {
					$key_type = 'INDEX';
				}
				if(isset($key_type)) {
					$indexes .= sprintf($index_template, $table, $c['Field'], $key_type);
				}
			}
		}
		
		return sprintf($table_template, $table, $columns, $indexes);
	}
}
?>