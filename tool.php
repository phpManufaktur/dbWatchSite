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
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.cronjob.php');

global $dbLog;
if (!is_object($dbLog)) $dbLog = new dbWatchSiteLog();

global $dbCronjobData;
if (!is_object($dbCronjobData)) $dbCronjobData = new dbCronjobData();

global $dbCronjobErrorLog;
if (!is_object($dbCronjobErrorLog)) $dbCronjobErrorLog = new dbCronjobErrorLog();

global $db404base;
if (!is_object($db404base)) $db404base = new dbWatchSite404base(true);

global $db404log;
if (!is_object($db404log)) $db404log = new dbWatchSite404log(true);

global $db404ip;
if (!is_object($db404ip)) $db404ip = new dbWatchSite404ip(true);

global $db404error;
if (!is_object($db404error)) $db404error = new dbWatchSite404error(true);


$tool = new toolWatchSite();
$tool->action();

class toolWatchSite {
	
	const request_action 						= 'act';
	const request_items							= 'its';
	const request_tab								= 'tab';
	
	const action_404								= '404';
	const action_404_log						= '4log';
	const action_404_basis					= '4bas';
	const action_404_basis_check		= '4bsc';
	const action_404_ips						= '4ip';
	const action_404_error					= '4err';
	const action_about							= 'abt';
	const action_config							= 'cfg';
	const action_config_check				= 'cc';
	const action_default						= 'def';
	const action_log								= 'log';
	const action_log_tab_watch			= 'ltw';
	const action_log_tab_error			= 'lte';
	
	private $tab_navigation_array = array(
		self::action_log								=> ws_tab_log,
		self::action_404								=> ws_tab_404,
		self::action_config							=> ws_tab_config,
		self::action_about							=> ws_tab_about
	);
	
	private $tab_watch_array = array(
		self::action_log_tab_watch			=> ws_tab_watch_log,
		self::action_log_tab_error			=> ws_tab_watch_error
	);
	
	private $tab_404_array = array(
		self::action_404_log						=> ws_tab_404_log,
		self::action_404_basis					=> ws_tab_404_basis,
		self::action_404_ips						=> ws_tab_404_ips,
		self::action_404_error					=> ws_tab_404_error
	);
	
	private $page_link 					= '';
	private $img_url						= '';
	private $template_path			= '';
	private $error							= '';
	private $message						= '';
	
	public function __construct() {
		$this->page_link = ADMIN_URL.'/admintools/tool.php?tool=watch_site';
		$this->template_path = WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/htt/' ;
		$this->img_url = WB_URL. '/modules/'.basename(dirname(__FILE__)).'/img/';
	} // __construct()
	
	/**
    * Set $this->error to $error
    * 
    * @param STR $error
    */
  public function setError($error) {
    $this->error = $error;
  } // setError()

  /**
    * Get Error from $this->error;
    * 
    * @return STR $this->error
    */
  public function getError() {
    return $this->error;
  } // getError()

  /**
    * Check if $this->error is empty
    * 
    * @return BOOL
    */
  public function isError() {
    return (bool) !empty($this->error);
  } // isError

  /**
   * Reset Error to empty String
   */
  public function clearError() {
  	$this->error = '';
  }

  /** Set $this->message to $message
    * 
    * @param STR $message
    */
  public function setMessage($message) {
    $this->message = $message;
  } // setMessage()

  /**
    * Get Message from $this->message;
    * 
    * @return STR $this->message
    */
  public function getMessage() {
    return $this->message;
  } // getMessage()

  /**
    * Check if $this->message is empty
    * 
    * @return BOOL
    */
  public function isMessage() {
    return (bool) !empty($this->message);
  } // isMessage
  
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
  	$html_allowed = array();
  	foreach ($_REQUEST as $key => $value) {
  		if (!in_array($key, $html_allowed)) {
  			// Sonderfall: Value Felder der Konfiguration werden durchnummeriert und duerfen HTML enthalten...
  			if (strpos($key, dbWatchSiteCfg::field_value) != 0) {
    			$_REQUEST[$key] = $this->xssPrevent($value);
  			}
  		} 
  	}
    isset($_REQUEST[self::request_action]) ? $action = $_REQUEST[self::request_action] : $action = self::action_default;
  	switch ($action):
  	case self::action_config:
  		$this->show(self::action_config, $this->dlgConfig());
  		break;
  	case self::action_config_check:
  		$this->show(self::action_config, $this->checkConfig());
  		break;
  	case self::action_about:
  		$this->show(self::action_about, $this->dlgAbout());
  		break;
  	case self::action_404:
  		$this->show(self::action_404, $this->dlg404());
  		break;
  	case self::action_log:
  	default:
  		$this->show(self::action_log, $this->dlgLog());
  		break;
  	endswitch;
  } // action
	
  	
  /**
   * Erstellt eine Navigationsleiste
   * 
   * @param $action - aktives Navigationselement
   * @return STR Navigationsleiste
   */
  public function getNavigation($action) {
  	$result = '';
  	foreach ($this->tab_navigation_array as $key => $value) {
   		($key == $action) ? $selected = ' class="selected"' : $selected = ''; 
	 		$result .= sprintf(	'<li%s><a href="%s">%s</a></li>', 
	 												$selected,
	 												sprintf('%s&%s=%s', $this->page_link, self::request_action, $key),
	 												$value
	 												);
  	}
  	$result = sprintf('<ul class="nav_tab">%s</ul>', $result);
  	return $result;
  } // getNavigation()
  
  /**
   * Ausgabe des formatierten Ergebnis mit Navigationsleiste
   * 
   * @param $action - aktives Navigationselement
   * @param $content - Inhalt
   * 
   * @return ECHO RESULT
   */
  public function show($action, $content) {
  	global $parser;
  	if ($this->isError()) {
  		$content = $this->getError();
  		$class = ' class="error"';
  	}
  	else {
  		$class = '';
  	}
  	$data = array(
  		'navigation'			=> $this->getNavigation($action),
  		'class'						=> $class,
  		'content'					=> $content
  	);
  	$parser->output($this->template_path.'backend.body.htt', $data);
  } // show()
	
	/**
	 * Konfigurationsdialg fuer die allgemeinen Einstellungen
	 * 
	 * @return STR DIALOG dlgConfigGeneral()
	 */
	public function dlgConfig() {
  	global $parser;
  	global $dbWScfg;
		$SQL = sprintf(	"SELECT * FROM %s WHERE NOT %s='%s' ORDER BY %s",
										$dbWScfg->getTableName(),
										dbWatchSiteCfg::field_status,
										dbWatchSiteCfg::status_deleted,
										dbWatchSiteCfg::field_name);
		$config = array();
		if (!$dbWScfg->sqlExec($SQL, $config)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbWScfg->getError()));
			return false;
		}
		$count = array();
		$items = sprintf(	'<tr><th>%s</th><th>%s</th><th>%s</th></tr>',
											ws_header_cfg_identifier,
											ws_header_cfg_value,
											ws_header_cfg_description );
		$row = '<tr><td>%s</td><td>%s</td><td>%s</td></tr>';
		// bestehende Eintraege auflisten
		foreach ($config as $entry) {
			$id = $entry[dbWatchSiteCfg::field_id];
			$count[] = $id;
			$label = constant($entry[dbWatchSiteCfg::field_label]);
			(isset($_REQUEST[dbWatchSiteCfg::field_value.'_'.$id])) ? 
				$val = $_REQUEST[dbWatchSiteCfg::field_value.'_'.$id] : 
				$val = $entry[dbWatchSiteCfg::field_value];
				// Hochkommas maskieren 
				$val = str_replace('"', '&quot;', stripslashes($val));
			$value = sprintf(	'<input type="text" name="%s_%s" value="%s" />', dbWatchSiteCfg::field_value, $id,	$val);
			$desc = constant($entry[dbWatchSiteCfg::field_description]);
			$items .= sprintf($row, $label, $value, $desc);
		}
		$items_value = implode(",", $count);
		// Mitteilungen anzeigen
		if ($this->isMessage()) {
			$intro = sprintf('<div class="message">%s</div>', $this->getMessage());
		}
		else {
			$intro = sprintf('<div class="intro">%s</div>', ws_intro_cfg);
		}		
		$data = array(
			'form_name'						=> 'konfiguration',
			'form_action'					=> $this->page_link,
			'action_name'					=> self::request_action,
			'action_value'				=> self::action_config_check,
			'items_name'					=> self::request_items,
			'items_value'					=> $items_value,
			'header'							=> ws_header_cfg,
			'intro'								=> $intro,
			'items'								=> $items,
			'btn_ok'							=> ws_btn_ok,
			'btn_abort'						=> ws_btn_abort,
			'abort_location'			=> $this->page_link
		);
		return $parser->get($this->template_path.'backend.cfg.htt', $data);
	} // dlgConfig()
	
	/**
	 * Ueberprueft Aenderungen die im Dialog dlgConfig() vorgenommen wurden
	 * und aktualisiert die entsprechenden Datensaetze.
	 * Fuegt neue Datensaetze ein.
	 * 
	 * @return STR DIALOG dlgConfig()
	 */
	public function checkConfig() {
		global $wsTools;
		global $dbWScfg;
		$message = '';
		// ueberpruefen, ob ein Eintrag geaendert wurde
		if ((isset($_REQUEST[self::request_items])) && (!empty($_REQUEST[self::request_items]))) {
			$ids = explode(",", $_REQUEST[self::request_items]);
			foreach ($ids as $id) {
				if (isset($_REQUEST[dbWatchSiteCfg::field_value.'_'.$id])) {
					$value = $_REQUEST[dbWatchSiteCfg::field_value.'_'.$id];
					$where = array();
					$where[dbWatchSiteCfg::field_id] = $id; 
					$config = array();
					if (!$dbWScfg->sqlSelectRecord($where, $config)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbWScfg->getError()));
						return false;
					}
					if (sizeof($config) < 1) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(ws_error_cfg_id, $id)));
						return false;
					}
					$config = $config[0];
					if ($config[dbWatchSiteCfg::field_value] != $value) {
						// Wert wurde geaendert
							if (!$dbWScfg->setValue($value, $id) && $dbWScfg->isError()) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbWScfg->getError()));
								return false;
							}
							elseif ($dbWScfg->isMessage()) {
								$message .= $dbWScfg->getMessage();
							}
							else {
								// Datensatz wurde aktualisiert
								$message .= sprintf(ws_msg_cfg_id_updated, $id, $config[dbWatchSiteCfg::field_name]);
							}
					}
				}
			}		
		}		
		// ueberpruefen, ob ein neuer Eintrag hinzugefuegt wurde
		if ((isset($_REQUEST[dbWatchSiteCfg::field_name])) && (!empty($_REQUEST[dbWatchSiteCfg::field_name]))) {
			// pruefen ob dieser Konfigurationseintrag bereits existiert
			$where = array();
			$where[dbWatchSiteCfg::field_name] = $_REQUEST[dbWatchSiteCfg::field_name];
			$where[dbWatchSiteCfg::field_status] = dbWatchSiteCfg::status_active;
			$result = array();
			if (!$dbWScfg->sqlSelectRecord($where, $result)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbWScfg->getError()));
				return false;
			}
			if (sizeof($result) > 0) {
				// Eintrag existiert bereits
				$message .= sprintf(ws_msg_cfg_add_exists, $where[dbWatchSiteCfg::field_name]);
			}
			else {
				// Eintrag kann hinzugefuegt werden
				$data = array();
				$data[dbWatchSiteCfg::field_name] = $_REQUEST[dbWatchSiteCfg::field_name];
				if (((isset($_REQUEST[dbWatchSiteCfg::field_type])) && ($_REQUEST[dbWatchSiteCfg::field_type] != dbWatchSiteCfg::type_undefined)) &&
						((isset($_REQUEST[dbWatchSiteCfg::field_value])) && (!empty($_REQUEST[dbWatchSiteCfg::field_value]))) &&
						((isset($_REQUEST[dbWatchSiteCfg::field_label])) && (!empty($_REQUEST[dbWatchSiteCfg::field_label]))) &&
						((isset($_REQUEST[dbWatchSiteCfg::field_description])) && (!empty($_REQUEST[dbWatchSiteCfg::field_description])))) {
					// Alle Daten vorhanden
					unset($_REQUEST[dbWatchSiteCfg::field_name]);
					$data[dbWatchSiteCfg::field_type] = $_REQUEST[dbWatchSiteCfg::field_type];
					unset($_REQUEST[dbWatchSiteCfg::field_type]);
					$data[dbWatchSiteCfg::field_value] = stripslashes(str_replace('&quot;', '"', $_REQUEST[dbWatchSiteCfg::field_value]));
					unset($_REQUEST[dbWatchSiteCfg::field_value]);
					$data[dbWatchSiteCfg::field_label] = $_REQUEST[dbWatchSiteCfg::field_label];
					unset($_REQUEST[dbWatchSiteCfg::field_label]);
					$data[dbWatchSiteCfg::field_description] = $_REQUEST[dbWatchSiteCfg::field_description];
					unset($_REQUEST[dbWatchSiteCfg::field_description]);
					$data[dbWatchSiteCfg::field_status] = dbWatchSiteCfg::status_active;
					$data[dbWatchSiteCfg::field_update_by] = $wsTools->getDisplayName();
					$data[dbWatchSiteCfg::field_update_when] = date('Y-m-d H:i:s');
					$id = -1;
					if (!$dbWScfg->sqlInsertRecord($data, $id)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbWScfg->getError()));
						return false; 
					}
					$message .= sprintf(ws_msg_cfg_add_success, $id, $data[dbWatchSiteCfg::field_name]);		
				}
				else {
					// Daten unvollstaendig
					$message .= ws_msg_cfg_add_incomplete;
				}
			}
		}
		if (!empty($message)) $this->setMessage($message);
		return $this->dlgConfig();
	} // checkConfig()
    
	/**
   *
   * @return STR dialog
   */
  public function dlgLog() {
  	$tab = '';
  	(isset($_REQUEST[self::request_tab])) ? $action = $_REQUEST[self::request_tab] : $action = self::action_log_tab_watch;
  	foreach ($this->tab_watch_array as $key => $value) {
  		($key== $action) ? $selected = ' class="selected"' : $selected = '';
  		$tab .= sprintf(	'<li%s><a href="%s">%s</a></li>',
	  														$selected,
	  														sprintf('%s&%s=%s&%s=%s', $this->page_link, self::request_action, self::action_log, self::request_tab, $key),
	  														$value
	  													);
  	}
  	$tab = sprintf('<ul class="nav_tab">%s</ul>', $tab);

  	switch ($action):
		default:
		case self::action_log_tab_error:
			$result = $this->dlgLogError();
			break;
		case self::action_log_tab_watch:
		default:
			$result = $this->dlgLogWatch();
			break;
  	endswitch;
  	$result = sprintf('<div class="log_container">%s%s</div>', $tab, $result);
  	return $result;  	
	} // dlgLog()
	
	
  public function dlgLogWatch() {
  	global $dbLog;
  	global $dbWScfg;
  	global $parser;
  	global $dbCronjobData;
  	
  	$group = ($dbWScfg->getValue(dbWatchSiteCfg::cfgLogCronjobExecTime) == false) ? sprintf(" WHERE %s!='%s'", dbWatchSiteLog::field_group,	dbWatchSiteLog::group_cronjob) : ''; 
  	$SQL = sprintf(	"SELECT * FROM %s%s ORDER BY %s DESC LIMIT %d",
  									$dbLog->getTableName(),
  									$group,
  									dbWatchSiteLog::field_id,
  									$dbWScfg->getValue(dbWatchSiteCfg::cfgLogShowMax));
  	$logs = array();
  	if (!$dbLog->sqlExec($SQL, $logs)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbLog->getError()));
  		return false;
  	}
  	$row = new Dwoo_Template_File($this->template_path.'backend.log.row.htt');
  	$items = '';
  	
  	$flipflop = true;
		foreach ($logs as $log) {
			$flipflop ? $flipper = 'flip' : $flipper = 'flop';
  		$flipflop ? $flipflop = false : $flipflop = true;
			$data = array(
				'flipflop'					=> $flipper,
				'id'								=> sprintf('%08d', $log[dbWatchSiteLog::field_id]),
				'category'					=> ($log[dbWatchSiteLog::field_category] == dbWatchSiteLog::category_warning) ? sprintf('<img src="%swarning.png" />', $this->img_url) : '',
				'group'							=> ($log[dbWatchSiteLog::field_group] == dbWatchSiteLog::group_directory) ? sprintf('<img src="%sfolder.png" />', $this->img_url) : sprintf('<img src="%sfile.png" />', $this->img_url),
				'description'				=> $log[dbWatchSiteLog::field_description],
				'timestamp'					=> date(ws_cfg_date_time, strtotime($log[dbWatchSiteLog::field_timestamp]))
			);
			$items .= $parser->get($row, $data);
		}
		
		$where = array(dbCronjobData::field_item => dbCronjobData::item_last_call);
		$data = array();
		if (!$dbCronjobData->sqlSelectRecord($where, $data)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbCronjobData->getError()));
			return false;
		}
		if (count($data) > 0) {
			$intro = sprintf(ws_intro_log_last_call, date(ws_cfg_date_time, strtotime($data[0][dbCronjobData::field_value])));
		}
		else {
			$intro = ws_intro_log_no_call;
		}
		// Mitteilungen anzeigen
		if ($this->isMessage()) {
			$intro = sprintf('<div class="message">%s</div>', $this->getMessage());
		}
		else {
			$intro = sprintf('<div class="intro">%s</div>', $intro);
		}
		
		$data = array(
			'header'		=> ws_header_log,
			'intro'			=> $intro,
			'items'			=> $items
		);
		return $parser->get($this->template_path.'backend.log.htt', $data);
  } // dlgLogWatch()
  
  public function dlgLogError() {
  	global $dbCronjobErrorLog;
  	global $parser;
  	
  	$SQL = sprintf(	"SELECT * FROM %s ORDER BY %s DESC",
  									$dbCronjobErrorLog->getTableName(),
  									dbCronjobErrorLog::field_timestamp 
  								);
  	$logs = array();
 		if (!$dbCronjobErrorLog->sqlExec($SQL, $logs)) {
 			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbCronjobErrorLog->getError()));
 			return false;
 		}
 		
 		$row = new Dwoo_Template_File($this->template_path.'backend.log.error.row.htt');
  	$items = '';
  	
  	$flipflop = true;
		foreach ($logs as $log) {
			$flipflop ? $flipper = 'flip' : $flipper = 'flop';
  		$flipflop ? $flipflop = false : $flipflop = true;
			$data = array(
				'flipflop'		=> $flipper,
				'timestamp'		=> date(ws_cfg_date_time, strtotime($log[dbCronjobErrorLog::field_timestamp])),
				'description'	=> $log[dbCronjobErrorLog::field_error]
 			);
 			$items .= $parser->get($row, $data);
		}
		
		if (empty($items)) {
			// es liegen keine Fehlermeldungen vor
			$intro = sprintf('<div class="intro">%s</div>', ws_intro_log_no_error);
		}
		else {
			$intro = sprintf('<div class="intro">%s</div>', ws_intro_log_error);
		}
		$data = array(
			'header'		=> ws_header_log_error,
			'intro'			=> $intro,
			'items'			=> $items
		);
		return $parser->get($this->template_path.'backend.log.error.htt', $data);
  } // dlgLogError()
  
  public function dlgAbout() {
  	global $parser;
  	$data = array(
  		'version'					=> $this->getVersion(),
  		'img_url'					=> WB_URL.'/modules/'.basename(dirname(__FILE__)).'/img/dbWatchSite_600.jpg',
  		'release_notes'		=> file_get_contents(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/info.txt'),
  	);
  	return $parser->get($this->template_path.'backend.about.htt', $data);
  } // dlgAbout()
  
  public function dlg404() {
  	$tab = '';
  	(isset($_REQUEST[self::request_tab])) ? $action = $_REQUEST[self::request_tab] : $action = self::action_404_log;
  	foreach ($this->tab_404_array as $key => $value) {
  		($key== $action) ? $selected = ' class="selected"' : $selected = '';
  		$tab .= sprintf(	'<li%s><a href="%s">%s</a></li>',
	  														$selected,
	  														sprintf('%s&%s=%s&%s=%s', $this->page_link, self::request_action, self::action_404, self::request_tab, $key),
	  														$value
	  													);
  	}
  	$tab = sprintf('<ul class="nav_tab">%s</ul>', $tab);

  	switch ($action):
  	case self::action_404_basis:
  		$result = $this->dlg404basis();
  		break;
  	case self::action_404_ips:
  		$result = $this->dlg404ips();
  		break;
  	case self::action_404_error:
  		$result = $this->dlg404error();
  		break;
  	case self::action_404_log:
		default:
			$result = $this->dlg404protocol();
			break;
  	endswitch;
  	$result = sprintf('<div class="log_container">%s%s</div>', $tab, $result);
  	return $result;  	  	
  } // dlg404()
  
  public function dlg404protocol() {
  	global $db404log;
  	global $dbWScfg;
  	global $parser;
  	
  	$SQL = sprintf(	"SELECT * FROM %s ORDER BY %s DESC LIMIT %d",
  									$db404log->getTableName(),
  									dbWatchSite404log::field_id,
  									$dbWScfg->getValue(dbWatchSiteCfg::cfg404LogShowMax));
  	$logs = array();
  	if (!$db404log->sqlExec($SQL, $logs)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $db404log->getError()));
  		return false;
  	}
  	$row = new Dwoo_Template_File($this->template_path.'backend.404.log.row.htt');
  	$items = '';
  	
  	$flipflop = true;
		foreach ($logs as $log) {
			$flipflop ? $flipper = 'flip' : $flipper = 'flop';
  		$flipflop ? $flipflop = false : $flipflop = true;
			$data = array(
				'flipflop'					=> $flipper,
				'timestamp'					=> date(ws_cfg_date_time, strtotime($log[dbWatchSite404log::field_timestamp])),
				'entry'							=> sprintf(	ws_text_404_log_entry,
																				$log[dbWatchSite404log::field_request_uri],
																				$log[dbWatchSite404log::field_referer],
																				$log[dbWatchSite404log::field_remote_ip],
																				$log[dbWatchSite404log::field_remote_host],
																				$log[dbWatchSite404log::field_user_agent])
			);
			$items .= $parser->get($row, $data);
		}
		$data = array(
			'header'		=> ws_header_404_log,
			'intro'			=> sprintf('<div class="intro">%s</div>', ws_intro_404_log),
			'items'			=> $items
		);
		return $parser->get($this->template_path.'backend.404.log.htt', $data);
  } // dlg404protocoll()
  
  public function dlg404basis() {
  	return '404 basis';
  } // dlg404basis()
  
  public function dlg404ips() {
  	return 'gesperrte IPs';
  } // dlg404ips()
  
  public function dlg404error() {
  	return 'tracking error';
  } // dlg404error()
  
} // class toolWatchSite

?>