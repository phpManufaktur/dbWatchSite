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

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.watchsite.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.cronjob.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.config.php');

global $admin;

$tables = array('dbWatchSiteCfg', 'dbWatchSiteDirectory', 'dbWatchSiteFiles', 'dbWatchSiteLog', 'dbCronjobData', 'dbCronjobErrorLog', 'dbWatchSite404base', 'dbWatchSite404error', 'dbWatchSite404ip', 'dbWatchSite404log');
$error = '';

foreach ($tables as $table) {
	$delete = null;
	$delete = new $table();
	if ($delete->sqlTableExists()) {
		if (!$delete->sqlDeleteTable()) {
			$error .= sprintf('[UNINSTALL] %s', $delete->getError());
		}
	}
}

// Prompt Errors
if (!empty($error)) {
	$admin->print_error($error);
}

?>