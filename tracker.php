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
$errorTracker->action();

class errorTracker {

	const 	request_action 			= 'act';
	const 	action_default			= 'def';
	
	private $error = '';
	private $template_path = '';
	private $language_path = '';
	
	public function __construct() {
		$this->template_path = WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/htt/' ;
		$this->language_path = WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/';
	} // __construct()
	
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
	
  /**
   * Return Version of Module
   *
   * @return FLOAT
   */
  public function getVersion() {
    // read info.php into array
    $info_text = file(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/info.php');
    if ($info_text == false) {
      return -1; 
    }
    // walk through array
    foreach ($info_text as $item) {
      if (strpos($item, '$module_version') !== false) {
        // split string $module_version
        $value = explode('=', $item);
        // return floatval
        return floatval(preg_replace('([\'";,\(\)[:space:][:alpha:]])', '', $value[1]));
      } 
    }
    return -1;
  } // getVersion()
  
  /**
   * Verhindert XSS Cross Site Scripting
   * 
   * @param REFERENCE $_REQUEST Array
   * @return $request
   */
	public function xssPrevent(&$request) {
  	if (is_string($request)) {
	    $request = html_entity_decode($request);
	    $request = strip_tags($request);
	    $request = trim($request);
	    $request = stripslashes($request);
  	}
	  return $request;
  } // xssPrevent()
	
  public function action() {
  	foreach ($_REQUEST as $key => $value) {
 			$_REQUEST[$key] = $this->xssPrevent($value);
  	}
    isset($_REQUEST[self::request_action]) ? $action = $_REQUEST[self::request_action] : $action = self::action_default;
  	switch ($action):
  	case self::action_default:
  	default:
  		$this->checkError();
  	endswitch;
  } // action
  
	public function checkError() {
		global $db404base;
		global $db404log;
		global $db404ip;
		global $wsTools;
		
		$request_uri = urldecode($_SERVER['REQUEST_URI']);
		$http_referer = (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) ? urldecode($_SERVER['HTTP_REFERER']) : '';
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
		$log_data = array(
			dbWatchSite404log::field_request_uri			=> $request_uri,
			dbWatchSite404log::field_referer					=> $http_referer,
			dbWatchSite404log::field_remote_ip				=> $remote_ip,
			dbWatchSite404log::field_remote_host			=> $remote_host,
			dbWatchSite404log::field_user_agent				=> $user_agent
		);
    if (!$db404log->sqlInsertRecord($log_data)) {
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
    	$id = -1;
    	if (!$db404base->sqlInsertRecord($base404, $id)) {
    		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $db404base->getError()));
    		exit($this->getError());
    	}
    	$base404[dbWatchSite404base::field_id] = $id;
    }
    if (($base404[dbWatchSite404base::field_category] == dbWatchSite404base::category_undefined) ||
    		($base404[dbWatchSite404base::field_behaviour] == dbWatchSite404base::behaviour_prompt)) {
    	// send a mail to webmaster	
    	$this->sendBaseMessage($base404, $log_data);	
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
    		$data = array(dbWatchSite404ip::field_count => $ip404[dbWatchSite404ip::field_count]+1, //$ip404[$db404ip]+1,
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
	
	
	private function sendBaseMessage($base404, $log404) {
		global $parser;
		global $dbWScfg;
		global $wb;
		
		$emails = $dbWScfg->getValue(dbWatchSiteCfg::cfg404SendMailsTo);
		if (empty($emails[0])) {
			// no emails defined
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, ws_error_404_email_missing));
			exit($this->getError());
		}
		
		$server_name = $dbWScfg->getValue(dbWatchSiteCfg::cfgServerName);
		if (empty($server_name)) {
			$server_name = $_SERVER['SERVER_ADDR'];
		}
		else {
			$server_name = sprintf('%s [%s]', $server_name, $_SERVER['SERVER_ADDR']);
		}
		if ($base404[dbWatchSite404base::field_category] == dbWatchSite404base::category_undefined) {
			$status = ws_mail_body_404_status_undefined;
		}
		else {
			$status = ws_mail_body_404_status_change;
		}
		$data = array(
			'server_name'				=> $server_name,
			'request_uri'				=> $log404[dbWatchSite404log::field_request_uri],
			'http_referer'			=> $log404[dbWatchSite404log::field_referer],
			'remote_ip'					=> $log404[dbWatchSite404log::field_remote_ip],
			'remote_host'				=> $log404[dbWatchSite404log::field_remote_host],
			'user_agent'				=> $log404[dbWatchSite404log::field_user_agent],
			'status'						=> $status,
			'prompt_link'				=> sprintf('%s/index.php?sw=ep&idx=%d&key=%s', WB_URL, $base404[dbWatchSite404base::field_id], $base404[dbWatchSite404base::field_verification]),
			'ignore_link'				=> sprintf('%s/index.php?sw=ei&idx=%d&key=%s', WB_URL, $base404[dbWatchSite404base::field_id], $base404[dbWatchSite404base::field_verification]),
			'lock_link'					=> sprintf('%s/index.php?sw=xl&idx=%d&key=%s', WB_URL, $base404[dbWatchSite404base::field_id], $base404[dbWatchSite404base::field_verification])
		);
		if (file_exists($this->language_path.LANGUAGE.'.mail.404.htt'))  {
			// language file exists
			$body = $parser->get($this->language_path.LANGUAGE.'.mail.404.htt', $data);
		}
		else {
			// use the default DE file...
			$body = $parser->get($this->language_path.'DE.mail.404.htt', $data);
		}
		$body .= sprintf(	'<div style="padding: 15px 0 0 0;font-size:9pt;text-align:center;color:#800000;background-color:transparent;"><b>dbWatchSite</b> v%s - &copy %d by phpManufaktur - Ralf Hertsch, Berlin (Germany)<br /><a href="http://phpmanufaktur.de">http://phpManufaktur.de</a> - <a href="mailto:ralf.hertsch@phpmanufaktur.de">ralf.hertsch@phpManufaktur.de</a> - <i>+49 (0)30 68813647</i></div>', 
											$this->getVersion(), date('Y'));											
		$subject = sprintf(ws_mail_subject_404, $server_name);
		foreach ($emails as $email) {
			if (!$wb->mail('', $email, $subject, $body)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(ws_error_sending_mail, $email)));
				exit($this->getError());
			}
		}
	} // sendMessage
	
} // class errorTracker

?>