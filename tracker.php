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

global $db404base;
if (!is_object($db404base)) $db404base = new dbWatchSite404base(true);

global $db404log;
if (!is_object($db404log)) $db404log = new dbWatchSite404log(true);

global $db404ip;
if (!is_object($db404ip)) $db404ip = new dbWatchSite404ip(true);

global $db404error;
if (!is_object($db404error)) $db404error = new dbWatchSite404error(true);

global $wsTools;
if (!is_object($wsTools)) $wsTools = new wsTools();

$errorTracker = new errorTracker();
$errorTracker->checkError();

class errorTracker {

	private $error = '';
	
	private function setError($error) {
		global $db404error;
		$this->error = $error;
		// write simply to database - here is no chance to trigger additional errors...
		$db404error->sqlInsertRecord(array(dbWatchSite404error::field_error => $error));
	} // setError()
	
	private function getError() {
		return $this->error;
	} // getError()
	
	private function isError() {
    return (bool) !empty($this->error);
  } // isError
	
	public function checkError() {
		global $db404base;
		global $db404log;
		global $db404ip;
		global $wsTools;
		
		$request_uri = $_SERVER['REQUEST_URI'];
		$http_referer = (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
		$remote_ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '000.000.000.000';
		$remote_host = gethostbyaddr($remote_ip);
		if (ini_get('browscap')) {
  		$browser = get_browser();
  		$user_agent = sprintf('%s v%s, %s', $browser->browser, $browser->version, $browser->platform); 
		} 
		else {
  		$user_agent = $_SERVER['HTTP_USER_AGENT'];
    }
    // log this 404 
		$data = array(
			dbWatchSite404log::field_request_uri			=> $request_uri,
			dbWatchSite404log::field_referer					=> $http_referer,
			dbWatchSite404log::field_remote_ip				=> $remote_ip,
			dbWatchSite404log::field_remote_host			=> $remote_host,
			dbWatchSite404log::field_user_agent				=> $user_agent
		);
    if (!$db404log->sqlInsertRecord($data)) {
    	$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $db404log->getError()));
    	exit($this->getError());
    }
    // check if this 404 is already registered
    $where = array(dbWatchSite404base::field_request_uri => $request_uri);
    $base404 = array();
    if (!$db404base->sqlSelectRecord($where, $base404)) {
    	$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $db404base->getError()));
    	exit($this->getError());
    }
    if (count($base404) > 0) {
    	// got an entry
    	$base404 = $base404[0];
    	// increase counter
    	$where = array(dbWatchSite404base::field_id => $base404[dbWatchSite404base::field_id]);
    	$data = array(dbWatchSite404base::field_count => $base404[dbWatchSite404base::field_count]+1);
    	if (!$db404base->sqlUpdateRecord($data, $where)) {
    		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $db404base->getError()));
    		exit($this->getError());
    	}
    }
    else {
    	// create a new base entry
    	$base404 = array(
    		dbWatchSite404base::field_behaviour			=> dbWatchSite404base::behaviour_prompt,
    		dbWatchSite404base::field_category			=> dbWatchSite404base::category_undefined,
    		dbWatchSite404base::field_count					=> 1,
    		dbWatchSite404base::field_request_uri		=> $request_uri,
    		dbWatchSite404base::field_verification	=> $wsTools->generatePassword(10)
    	);
    	if (!$db404base->sqlInsertRecord($base404)) {
    		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $db404base->getError()));
    		exit($this->getError());
    	}
    }
    if (($base404[dbWatchSite404base::field_category] == dbWatchSite404base::category_undefined) ||
    		($base404[dbWatchSite404base::field_behaviour] == dbWatchSite404base::behaviour_prompt)) {
    	// send a mail to webmaster	
    	$this->sendBaseMessage($base404);	
    }
    elseif ($base404[dbWatchSite404base::field_behaviour] == dbWatchSite404base::behaviour_lock) {
    	// lock the ip...
    	$where = array(dbWatchSite404ip::field_remote_ip => $remote_ip);
    	$ip404 = array();
    	if (!$db404ip->sqlSelectRecord($where, $ip404)) {
    		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $db404ip->getError()));
    		exit($this->getError());
    	}
    	if (count($ip404) > 0) {
    		// update record
    		$ip404 = $ip404[0];
    		$where = array(dbWatchSite404ip::field_id => $ip404[dbWatchSite404ip::field_id]);
    		$data = array(dbWatchSite404ip::field_count => $ip404[$db404ip]+1,
    									dbWatchSite404ip::field_locked_since => date('Y-m-d H:i:s'));
    		if (!$db404ip->sqlUpdateRecord($data, $where)) {
    			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $db404ip->getError()));
    			exit($this->getError());
    		}
    	}
    	else {
    		// add new record
    		$ip404 = array(
    			dbWatchSite404ip::field_remote_ip			=> $remote_ip,
    			dbWatchSite404ip::field_count					=> 1,
    			dbWatchSite404ip::field_locked_since	=> date('Y-m-d H:i:s')
    		);
    		if (!$db404ip->sqlInsertRecord($ip404)) {
    			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $db404ip->getError()));
    			exit($this->getError());
    		} 
    	}
    }
    return true;
	} // checkError()
	
	
	private function sendBaseMessage($base404) {
		echo "send message!";
	} // sendMessage
	
} // class errorTracker

?>