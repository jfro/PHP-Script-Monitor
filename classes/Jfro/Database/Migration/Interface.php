<?php
/**
 * Jfro_Database_Migration_Interface, interface a migration file must conform to
 * @author Jeremy Knope <jerome@rainstormconsulting.com>
 * @package Database
 * @subpackage Migration
 */

/**
 * Jfro_Database_Migration_Interface, interface a migration file must conform to
 * @package Database
 * @subpackage Migration
 */
interface Jfro_Database_Migration_Interface {
	function up();
	function down();
}