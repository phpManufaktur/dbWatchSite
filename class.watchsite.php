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

// prevent this file from being accesses directly
if(defined('WB_PATH') == false) {
  exit("Cannot access this file directly");
}

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/initialize.php');

class dbWatchSiteDirectory extends dbConnectLE {
	
	const field_id					= 'dir_id';
	const field_path				= 'dir_path';
	const field_checksum		= 'dir_checksum';
	const field_files				= 'dir_files';
	const field_timestamp		= 'dir_timestamp';
	
	public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_ws_directory');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_path, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_checksum, "VARCHAR(35) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_files, "INT(11) NOT NULL DEFAULT '0'");
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  } // __construct()  
	
} // class dbWatchSiteDirectory

class dbWatchSiteFiles extends dbConnectLE {
	
	const field_id					= 'file_id';
	const field_path				= 'file_path';
	const field_file				= 'file_file';
	const field_checksum		= 'file_checksum';
	const field_file_size		= 'file_size';
	const field_last_change	= 'file_last_change';
	const field_timestamp		= 'file_timestamp';
	
	public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_ws_file');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_path, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_file, "VARCHAR(255) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_checksum, "VARCHAR(35) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_file_size, "INT(11) NOT NULL DEFAULT '0'");
  	$this->addFieldDefinition(self::field_last_change, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
  	$this->setIndexFields(array(self::field_file));
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  } // __construct()  
	
} // class dbWatchSiteFiles

class dbWatchSiteLog extends dbConnectLE {
	
	const field_id					= 'log_id';
	const field_category		= 'log_category';
	const field_group				= 'log_group';
	const field_info				= 'log_info';
	const field_description	= 'log_description';
	const field_timestamp		= 'log_timestamp';
	
	const category_info			= 'info';
	const category_warning	= 'warning';
	const category_error		= 'error';
	const category_hint			= 'hint';
	
	public $category_array = array(
		self::category_error			=> ws_category_error,
		self::category_hint				=> ws_category_hint,
		self::category_info				=> ws_category_info,
		self::category_warning		=> ws_category_warning
	);
	
	const group_directory		= 'directory';
	const group_file				= 'file';
	const group_cronjob			= 'cronjob';
	
	public $group_array = array(
		self::group_directory			=> ws_group_directory,
		self::group_file					=> ws_group_file,
		self::group_cronjob				=> ws_group_cronjob
	);
	
	public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_ws_log');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_category, "VARCHAR(20) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_group, "VARCHAR(20) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_info, "VARCHAR(50) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_description, "TEXT NOT NULL DEFAULT ''", false, false, true);
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
  	$this->setIndexFields(array(self::field_category, self::field_group));
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  } // __construct()  
	
} // class dbWatchSiteLog

?>