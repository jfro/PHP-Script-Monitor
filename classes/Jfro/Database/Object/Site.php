<?php
/**
 * Jfro_Database_Object_Site site based model base class
 * @author Jeremy Knope <jerome@rainstorminc.com>
 * @package Database
 */

/**
 * Jfro_Database_Object_Site site based model base class
 * 
 * @author Jeremy Knope <jerome@rainstorminc.com>
 * @package Database
 */
class Jfro_Database_Object_Site extends Jfro_Database_Object {
	protected $table_name = false;
	static $site = false;

	/**
	 * Constructor, overrides JfroDataObject's to validate and set the site_id attribute
	 *
	 * @param array $attr attributes array, useful for taking from forms
	 */
	function __construct($attr=array()) {
		self::validate_site();
		
		parent::__construct($attr);
		$this->site_id = self::$site->id;
	}
	/**
	 * Returns all table rows or single row given an id
	 * @param string $table table name to use when fetching
	 * @param string $class Class name to use when creating new instances
	 * @param mixed $id primary key or 'all' or 'first'
	 * @param array $conditions
	 * @param string $order
	 * @param string $limit
	 * @param string $fields
	 * @return mixed
	 */
	static function _find($table, $class, $id, $conditions=null, $order=null, $limit=null, $fields='*') {
		self::validate_site();
		
		if($conditions) {
			$newconditions = array('('.array_shift($conditions).') AND site_id = ?');
			$conditions = array_merge($newconditions, $conditions);
			$conditions[] = self::$site->id;
		}
		else {
			$conditions = array('site_id = ?', self::$site->id);
		}
		return parent::_find($table, $class, $id, $conditions, $order, $limit, $fields);
	}
	
	/**
	 * Returns all rows matching the query given, using replacement arguments
	 * @param array $query array with query and arguments for it
	 * @return array
	 */
	static function _findBySql($class, $query) {
		return parent::_findBySql($class, $query);
	}
	
	/**
	 * Validates the static $site variable to make sure we don't continue unless we have a valid site
	 *
	 * @throws Exception
	 */
	private static function validate_site() {
		if(!self::$site || !(self::$site instanceof SMSite) || !self::$site->id) {
			throw new Exception('Invalid site specified for JfroSiteModel');
		}
	}
	
}
