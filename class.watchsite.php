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
	
	private $createTables 	= false;
	
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
	
	private $createTables 	= false;
	
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
	const group_report			= 'report';
	
	public $group_array = array(
		self::group_directory			=> ws_group_directory,
		self::group_file					=> ws_group_file,
		self::group_cronjob				=> ws_group_cronjob,
		self::group_report				=> ws_group_report
	);
	
	private $createTables 	= false;
	
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

class dbWatchSite404base extends dbConnectLE {
	
	const field_id						= 'base_id';
	const field_request_uri		= 'base_request_uri';
  const field_category			= 'base_category';
  const field_behaviour			=	'base_behaviour'; 
  const field_count					= 'base_count';
  const field_verification	= 'base_verification';
  const field_timestamp			= 'base_timestamp';
  
  const category_undefined		= 'undefined';
  const category_error				= 'error';
  const category_xss					= 'xss';
  
  public $category_array = array(
  	self::category_undefined		=> ws_category_undefined,
  	self::category_error				=> ws_category_error,
  	self::category_xss					=> ws_category_xss,
  );
	
  const behaviour_prompt			= 'prompt';
  const behaviour_lock				= 'lock';
  const behaviour_ignore			= 'ignore';
  
  public $behaviour_array = array(
  	self::behaviour_prompt			=> ws_behaviour_prompt,
  	self::behaviour_lock				=> ws_behaviour_lock,
  	self::behaviour_ignore			=> ws_behaviour_ignore
  );
  
  private $createTables 		= false;
  
  public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_ws_404_base');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_request_uri, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_category, "VARCHAR(20) NOT NULL DEFAULT '".self::category_undefined."'");
  	$this->addFieldDefinition(self::field_behaviour, "VARCHAR(20) NOT NULL DEFAULT '".self::behaviour_prompt."'");
  	$this->addFieldDefinition(self::field_count, "INT(11) NOT NULL DEFAULT '1'");
  	$this->addFieldDefinition(self::field_verification, "VARCHAR(35) NOT NULL DEFAULT ''");
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
	
} // class dbWatchSite404base

class dbWatchSite404log extends dbConnectLE {
	
	const field_id						= 'log_id';
	const field_request_uri		= 'log_request_uri';
	const field_referer				= 'log_referer';
	const field_remote_ip			= 'log_remote_ip';
	const field_remote_host		= 'log_remote_host';
	const field_user_agent		= 'log_user_agent';
	const field_timestamp			= 'log_timestamp';
	
	private $createTables 		= false;
	
	public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_ws_404_log');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_request_uri, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_referer, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_remote_ip, "VARCHAR(20) NOT NULL DEFAULT '000.000.000.000'");
  	$this->addFieldDefinition(self::field_remote_host, "VARCHAR(255) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_user_agent, "VARCHAR(128) NOT NULL DEFAULT ''");
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
	
} // class dbWatchSite404log

class dbWatchSite404ip extends dbConnectLE {
	
	const field_id						= 'ip_id';
	const field_remote_ip			= 'ip_remote_ip';
	const field_locked_since	= 'ip_locked_since';
	const field_count					= 'ip_count';
	const field_timestamp			= 'ip_timestamp';
	
	private $createTables 		= false;
	
	public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_ws_404_ip');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_remote_ip, "VARCHAR(20) NOT NULL DEFAULT '000.000.000.000'");
  	$this->addFieldDefinition(self::field_locked_since, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->addFieldDefinition(self::field_count, "INT(11) NOT NULL DEFAULT '1'");
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
  	$this->setIndexFields(array(self::field_remote_ip));
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
	
} // class dbWatchSite404ip

class dbWatchSite404error extends dbConnectLE {
	
	const field_id						= 'cj_error_id';
	const field_error					= 'cj_error_str';
	const field_timestamp			= 'cj_error_stamp';
	
	public $create_tables = false;
	
	function __construct($create_tables=false) {
		parent::__construct();
		$this->create_tables = $create_tables;
		$this->setTableName('mod_ws_404_error');
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
	
} // class dbWatchSite404error


?>