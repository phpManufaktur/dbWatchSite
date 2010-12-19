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

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.watchsite.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.cronjob.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.config.php');

global $admin;

$tables = array('dbWatchSiteCfg', 'dbWatchSiteDirectory', 'dbWatchSiteFiles', 'dbWatchSiteLog', 'dbCronjobData', 'dbCronjobErrorLog', 'dbWatchSite404base', 'dbWatchSite404error', 'dbWatchSite404ip', 'dbWatchSite404log');
$error = '';

foreach ($tables as $table) {
	$create = null;
	$create = new $table();
	if (!$create->sqlTableExists()) {
		if (!$create->sqlCreateTable()) {
			$error .= sprintf('[INSTALLATION %s] %s', $table, $create->getError());
		}
	}
}

// Prompt Errors
if (!empty($error)) {
	$admin->print_error($error);
}

?>