<?php

/**
 * dbWatchSite
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2010 - 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
  if (defined('LEPTON_VERSION'))
    include(WB_PATH.'/framework/class.secure.php');
}
else {
  $oneback = "../";
  $root = $oneback;
  $level = 1;
  while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
    $root .= $oneback;
    $level += 1;
  }
  if (file_exists($root.'/framework/class.secure.php')) {
    include($root.'/framework/class.secure.php');
  }
  else {
    trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
  }
}
// end include class.secure.php

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