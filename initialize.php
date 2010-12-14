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

if (!defined('DEBUG')) define('DEBUG', true);

// Sprachdateien einbinden
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.php'); 
}
else {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php'); 
}

if (!class_exists('dbConnectLE')) require_once(WB_PATH.'/modules/dbconnect_le/include.php');

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.tools.php');
global $wsTools;
if (!is_object($wsTools)) $wsTools = new wsTools();

if (!class_exists('Dwoo')) 				require_once(WB_PATH.'/modules/dwoo/include.php');
global $parser;
if (!is_object($parser)) $parser = new Dwoo();

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.config.php');
global $dbWScfg;
if (!is_object($dbWScfg)) $dbWScfg = new dbWatchSiteCfg();
// pruefen ob cronjob key gesetzt ist
$ck = $dbWScfg->getValue(dbWatchSiteCfg::cfgCronjobKey);
if (empty($ck)) {
	$dbWScfg->setValueByName($wsTools->generatePassword(), dbWatchSiteCfg::cfgCronjobKey);	
}

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.watchsite.php');


?>