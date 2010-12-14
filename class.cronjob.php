<?php

/**
 * dbWatchSite
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2010
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */

// prevent this file from being accessed directly
if (!defined('WB_PATH')) die('invalid call of '.$_SERVER['SCRIPT_NAME']);

require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/initialize.php');

class dbCronjobData extends dbConnectLE {
	
	const field_id							= 'cj_id';
	const field_item						= 'cj_item';
	const field_value						= 'cj_value';
	const field_timestamp				= 'cj_timestamp';

	const item_last_call				= 'last_call';
	const item_last_job					= 'last_job';
	
	public $create_tables = false;
	
	public function __construct($create_tables=false) {
		parent::__construct();
		$this->create_tables = $create_tables;
		$this->setTableName('mod_ws_cronjob_data');
		$this->addFieldDefinition(self::field_id, "INT NOT NULL AUTO_INCREMENT", true);
		$this->addFieldDefinition(self::field_item, "VARCHAR(30) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_value, "TEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
		// check field definitions
		$this->checkFieldDefinitions();
		// create tables
		if ($this->create_tables) {
			if (!$this->sqlTableExists()) {
				if (!$this->sqlCreateTable()) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
					return false;
				}
			}
		}	
		return true;
	} // __construct()
	
	/**
	 * Return the last Call of cronjob.php as UNIX timestamp or FALSE on error
	 * @return INT timestamp
	 */
	public function getLastCronjobCall() {
		$where = array(self::field_item => self::item_last_call);
		$result = array();
		if (!$this->sqlSelectRecord($where, $result)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
			return false;
		}
		if (count($result) > 0) {
			return strtotime($result[0][self::field_value]);
		}
		return false;
	} // getLastCronjobCall()
	
} // class dbCronjobData

class dbCronjobErrorLog extends dbConnectLE {
	
	const field_id						= 'cj_error_id';
	const field_error					= 'cj_error_str';
	const field_timestamp			= 'cj_error_stamp';
	
	public $create_tables = false;
	
	function __construct($create_tables=false) {
		parent::__construct();
		$this->create_tables = $create_tables;
		$this->setTableName('mod_ws_cronjob_error');
		$this->addFieldDefinition(self::field_id, "INT NOT NULL AUTO_INCREMENT", true);
		$this->addFieldDefinition(self::field_error, "TEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
		// check field definitions
		$this->checkFieldDefinitions();
		// create tables
		if ($this->create_tables) {
			if (!$this->sqlTableExists()) {
				if (!$this->sqlCreateTable()) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
					return false;
				}
			}
		}	
		return true;
	} // __construct()
	
} // class dbCronjobErrorLog


?>