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

global $database;
if ((isset($_REQUEST['sw'])) && (isset($_REQUEST['idx'])) && (isset($_REQUEST['key']))) {
	// it's a call from an 404 error message
	$query = "SELECT * FROM ".TABLE_PREFIX."mod_ws_404_base WHERE base_id='".$_REQUEST['idx']."' AND base_verification='".$_REQUEST['key']."'";
	if ((false !== ($result = $database->query($query))) && ($result->numRows() > 0)) {
		// ok - record exists
		$data = $result->fetchRow(MYSQL_ASSOC);
		switch ($_REQUEST['sw']):
		case 'ep':
			$cat = 'error';
			$bhv = 'prompt';
			break;
		case 'ei':
			$cat = 'error';
			$bhv = 'ignore';
			break;
		case 'xl':
			$cat = 'xss';
			$bhv = 'lock';
			break;
		default:
			$cat = 'undefined';
			$bhv = 'prompt';
		endswitch;
		// update 404 base...
		$query = "UPDATE ".TABLE_PREFIX."mod_ws_404_base SET base_category='".$cat."', base_behaviour='".$bhv."' WHERE base_id='".$_REQUEST['idx']."'";
		if (!$database->query($query)) {
			// error executing query - try to log error
			$error = sprintf('[check.php - %s] %s', __LINE__, $database->get_error());
			$query = "INSERT ".TABLE_PREFIX."mod_ws_404_error SET cj_error_str='".$error."'";
			$database->query($query);
		}
	}
	elseif ($database->is_error()) {
		// error executing query - try to log error
		$error = sprintf('[check.php - %s] %s', __LINE__, $database->get_error());
		$query = "INSERT ".TABLE_PREFIX."mod_ws_404_error SET cj_error_str='".$error."'";
		$database->query($query);
	}
}
else {
	// check calling ip against blacklist
	$query = "SELECT cfg_value FROM ".TABLE_PREFIX."mod_ws_config WHERE cfg_name='cfg404LockIpTime'";
	if ((false !== ($result = $database->query($query))) && ($result->numRows() > 0)) {
		$data = $result->fetchRow();
		$ipLockTime = $data['cfg_value'];
		if ($ipLockTime !== 0) {
			$query = "SELECT * FROM ".TABLE_PREFIX."mod_ws_404_ip WHERE ip_remote_ip='".$_SERVER['REMOTE_ADDR']."'";
			if ((false !== ($result = $database->query($query))) && ($result->numRows() > 0)) {
				$data = $result->fetchRow(MYSQL_ASSOC);
				if (($ipLockTime < 0) || ((strtotime($data['ip_locked_since'])+(60*$ipLockTime)) > time())) {
					// ip is permanent or temporary locked - update count
					$query = "UPDATE ".TABLE_PREFIX."mod_ws_404_ip SET ip_count='".($data['ip_count']+1)."' WHERE ip_id='".$data['ip_id']."'";
					if (!$database->query($query)) {
						// error executing query - try to log error
						$error = sprintf('[check.php - %s] %s', __LINE__, $database->get_error());
						$query = "INSERT ".TABLE_PREFIX."mod_ws_404_error SET cj_error_str='".$error."'";
						$database->query($query);
					}
					else {
						// LOCK the IP now...
						header($_SERVER['SERVER_PROTOCOL']." 403 Forbidden");
						exit();
					}
				}
			}
		}
	}
}
