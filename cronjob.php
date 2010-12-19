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

// WebsiteBaker config.php einbinden
require_once('../../config.php');

// prevent this file from being accessed directly
if (!defined('WB_PATH')) die('invalid call of '.$_SERVER['SCRIPT_NAME']);

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/initialize.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.cronjob.php');
require_once(WB_PATH.'/framework/class.wb.php');

global $dbLog;
global $dbWScfg;
global $dbDir;
global $dbFile;
global $dbCronjobData;
global $dbCronjobErrorLog;
global $wsTools;
global $wb;

if (!is_object($dbLog)) $dbLog = new dbWatchSiteLog();
if (!is_object($dbWScfg)) $dbWScfg = new dbWatchSiteCfg();
if (!is_object($dbDir)) $dbDir = new dbWatchSiteDirectory();
if (!is_object($dbFile)) $dbFile = new dbWatchSiteFiles();
if (!is_object($dbCronjobData)) $dbCronjobData = new dbCronjobData(true);
if (!is_object($dbCronjobErrorLog)) $dbCronjobErrorLog = new dbCronjobErrorLog(true);
if (!is_object($wsTools)) $wsTools = new wsTools();
if (!is_object($wb)) $wb = new wb();

$cronjob = new cronjob();
$cronjob->action();

class cronjob {
	
	const request_key			= 'key';
	
	private $error = '';
	private $start_script = 0;
	private $dirTree = array();
	
	public function __construct() {
		global $dbWScfg;
		$this->start_script = time(true);
	} // __construct()
	
	private function setError($error) {
		global $dbCronjobErrorLog;
		$this->error = $error;
		// write simply to database - here is no chance to trigger additional errors...
		$dbCronjobErrorLog->sqlInsertRecord(array(dbCronjobErrorLog::field_error => $error));
	} // setError()
	
	private function getError() {
		return $this->error;
	} // getError()
	
	private function isError() {
    return (bool) !empty($this->error);
  } // isError
	
  /**
   * Action Handler
   * 
   */
	public function action() {
		global $dbCronjobData;
		global $dbWScfg;
		global $wsTools;
		global $dbLog;
		
		if ($dbWScfg->getValue(dbWatchSiteCfg::cfgWatchSiteActive) == false) {
			// dbWatchSite is inactive so leave the script immediate without logging...
			exit(0);
		}
		// Log access to cronjob...
		$where = array(dbCronjobData::field_item => dbCronjobData::item_last_call);
		$data = array();
		if (!$dbCronjobData->sqlSelectRecord($where, $data)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbCronjobData->getError()));
			exit($this->getError());
		}
		if (count($data) < 1) {
			// entry does not exists, create default entries...
			$datas = array(	array(dbCronjobData::field_item => dbCronjobData::item_last_call, dbCronjobData::field_value => ''), 
							 				array(dbCronjobData::field_item => dbCronjobData::item_last_report, dbCronjobData::field_value => ''),
							 				array(dbCronjobData::field_item => dbCronjobData::item_next_report, dbCronjobData::field_value => '')
							 				);
			foreach ($datas as $data) {
				if (!$dbCronjobData->sqlInsertRecord($data)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbCronjobData->getError()));
					exit($this->getError());
				}
			}
		}
		// log this access...
		$data = array(dbCronjobData::field_value => date('Y-m-d H:i:s'));
		if (!$dbCronjobData->sqlUpdateRecord($data, $where)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbCronjobData->getError()));
			exit($this->getError());
		} 
		
		// check if the access is allowed
		$cronjob_key = $dbWScfg->getValue(dbWatchSiteCfg::cfgCronjobKey);
		if (strlen($cronjob_key) < 3) {
			// Cronjob Key does not exist, so create one...
			$cronjob_key = $wsTools->generatePassword();
			$dbWScfg->setValueByName($cronjob_key, dbWatchSiteCfg::cfgCronjobKey); 
		}
		if (!isset($_REQUEST[self::request_key]) || ($_REQUEST[self::request_key] !== $cronjob_key)) {
			// Cronjob key does not match, log denied access...
			$ip = (isset($_SERVER['SERVER_ADDR'])) ? $_SERVER['SERVER_ADDR'] : '000.000.000.000';
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(ws_error_cron_key_invalid, $ip)));
			// dont give attacker any hint, so exit with regular code...
			exit(0);
		}
		
		// Exec the watch job...
		$this->execWatchJob();
		// check index files
		$this->checkIndexFiles(); 
		// sending report?
		$this->sendReport();
		// Job beendet, Ausfuehrungszeit festhalten
		if ($dbWScfg->getValue(dbWatchSiteCfg::cfgLogCronjobExecTime)) {
			$info = ws_log_info_cronjob_finished;
			$desc = sprintf(ws_log_desc_cronjob_finished, (time(true) - $this->start_script));
			$this->addLogEntry(dbWatchSiteLog::category_info, dbWatchSiteLog::group_cronjob, $info, $desc);
		}
		exit(0);
	} // action()
	
	private function execWatchJob() {
		global $dbCronjobData;
		global $dbDir;
		global $dbFile;
		global $dbLog;

		$this->dirTree = array();
		// alle Verzeichnisse der Installation festhalten
		$this->getDirectoryTree(WB_PATH.'/');
		$actualDirectories = $this->dirTree;
		
		// Verzeichnisse aus der Datenbank auslesen
		$where = array();
		$wsDirectories = array();
		if (!$dbDir->sqlSelectRecord($where, $wsDirectories)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbDir->getError()));
			exit($this->getError());
		}
		// bereits gespeicherte Verzeichnisse durchlaufen
		foreach ($wsDirectories as $wsDirectory) {
			// gespeicherte Eintraege mit den aktuellen Ergebnissen vergleichen
			if (in_array($wsDirectory[dbWatchSiteDirectory::field_path], $actualDirectories)) {
				$key = array_search($wsDirectory[dbWatchSiteDirectory::field_path], $actualDirectories);
				$md5 = $this->md5_directory($actualDirectories[$key]);
				if ($md5 !== $wsDirectory[dbWatchSiteDirectory::field_checksum]) {
					// die Pruefsumme ist unterschiedlich
					$info = ws_log_info_dir_md5_different;
					$desc = sprintf(ws_log_desc_dir_md5_different, $wsDirectory[dbWatchSiteDirectory::field_path]);
					$this->addLogEntry(dbWatchSiteLog::category_warning, dbWatchSiteLog::group_directory, $info, $desc);
					// Datensatz aktualisieren
					$data = $wsDirectory;
					$data[dbWatchSiteDirectory::field_checksum] = $md5;
					$data[dbWatchSiteDirectory::field_files] = $this->countFiles($wsDirectory[dbWatchSiteDirectory::field_path]);
					if (!$dbDir->sqlUpdateRecord($data, $wsDirectory)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbDir->getError()));
						exit($this->getError());
					}
					// EINZELNE DATEIEN pruefen!
					// aktuelle Dateien auslesen
					$actualFiles = array();
					$handle = opendir(WB_PATH.$wsDirectory[dbWatchSiteDirectory::field_path]);
  				while (false !== ($file = readdir($handle))) {
    				if (($file !== ".") && ($file !== "..") && !is_dir($file)) {
    					$actualFiles[$file] = array(
    						dbWatchSiteFiles::field_checksum 		=> md5_file(WB_PATH.$wsDirectory[dbWatchSiteDirectory::field_path].$file),
    						dbWatchSiteFiles::field_file				=> $file,
    						dbWatchSiteFiles::field_file_size		=> filesize(WB_PATH.$wsDirectory[dbWatchSiteDirectory::field_path].$file),
    						dbWatchSiteFiles::field_last_change	=> date('Y-m-d H:i:s', filemtime(WB_PATH.$wsDirectory[dbWatchSiteDirectory::field_path].$file)),
    						dbWatchSiteFiles::field_path				=> $wsDirectory[dbWatchSiteDirectory::field_path]
    					); 	
    				}
  				}
  				$where = array(
						dbWatchSiteFiles::field_path	=> $wsDirectory[dbWatchSiteDirectory::field_path]
					);
					$wsFiles = array();
					if (!$dbFile->sqlSelectRecord($where, $wsFiles)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbFile->getError()));
						exit($this->getError());
					}
					foreach ($wsFiles as $wsFile) {
						if (isset($actualFiles[$wsFile[dbWatchSiteFiles::field_file]])) {
							// aktuelle Datei und Datenbankeintrag vergleichen
							if ($actualFiles[$wsFile[dbWatchSiteFiles::field_file]][dbWatchSiteFiles::field_checksum] !== $wsFile[dbWatchSiteFiles::field_checksum]) {
								// die Pruefsummen sind unterschiedlich
								$desc = sprintf(ws_log_desc_file_md5_different, $wsDirectory[dbWatchSiteDirectory::field_path].$wsFile[dbWatchSiteFiles::field_file]);
								$this->addLogEntry(dbWatchSiteLog::category_warning, dbWatchSiteLog::group_file, ws_log_info_file_md5_different, $desc);
								if ($actualFiles[$wsFile[dbWatchSiteFiles::field_file]][dbWatchSiteFiles::field_file_size] !== $wsFile[dbWatchSiteFiles::field_file_size]) {
									// Dateigroesse hat sich geaendert
									$desc = sprintf(ws_log_desc_file_size_different, $wsDirectory[dbWatchSiteDirectory::field_path].$wsFile[dbWatchSiteFiles::field_file]);
									$this->addLogEntry(dbWatchSiteLog::category_info, dbWatchSiteLog::group_file, ws_log_info_file_size_different, $desc);
								}
								if ($actualFiles[$wsFile[dbWatchSiteFiles::field_file]][dbWatchSiteFiles::field_last_change] !== $wsFile[dbWatchSiteFiles::field_last_change]) {
									$desc = sprintf(ws_log_desc_file_mtime_different, $wsDirectory[dbWatchSiteDirectory::field_path].$wsFile[dbWatchSiteFiles::field_file]);
									$this->addLogEntry(dbWatchSiteLog::category_info, dbWatchSiteLog::group_file, ws_log_info_file_mtime_different, $desc);
								}
								// Datensatz aktualisieren
								if (!$dbFile->sqlUpdateRecord($actualFiles[$wsFile[dbWatchSiteFiles::field_file]], $wsFile)) {
									$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbFile->getError));
									exit($this->getError());
								}
							}
						}
						else {
							// Datei existiert nicht mehr, aus Datenbank loeschen
							$desc = sprintf(ws_log_desc_file_no_longer_exists, $wsDirectory[dbWatchSiteDirectory::field_path].$wsFile[dbWatchSiteFiles::field_file]);
							$this->addLogEntry(dbWatchSiteLog::category_info, dbWatchSiteLog::group_file, ws_log_info_file_no_longer_exists, $desc);
							if (!$dbFile->sqlDeleteRecord($wsFile)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbFile->getError()));
								exit($this->getError());
							}
						}
						// aktuelle Datei entfernen
						unset($actualFiles[$wsFile[dbWatchSiteFiles::field_file]]);
					} // foreach
					foreach ($actualFiles as $actualFile) {
						// Dateien sind noch nicht erfasst
						$desc = sprintf(ws_log_desc_file_added, $wsDirectory[dbWatchSiteDirectory::field_path].$actualFile[dbWatchSiteFiles::field_file]);
						$this->addLogEntry(dbWatchSiteLog::category_info, dbWatchSiteLog::group_file, ws_log_info_file_added, $desc);
						if (!$dbFile->sqlInsertRecord($actualFile)) {
							$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbFile->getError()));
							exit($this->getError());
						}
					}					
				} // Checksum unterschiedlich
				// Schluessel loeschen
				unset($actualDirectories[$key]);
			}
			else {
				// das Verzeichnis existiert nicht mehr
				$info = ws_log_info_dir_no_longer_exists;
				$desc = sprintf(ws_log_desc_dir_no_longer_exists, $wsDirectory[dbWatchSiteDirectory::field_path]);
				$this->addLogEntry(dbWatchSiteLog::category_info, dbWatchSiteLog::group_directory, $info, $desc);
				// Datensaetze loeschen
				$where = array(
					dbWatchSiteFiles::field_path	=> $wsDirectory[dbWatchSiteDirectory::field_path]
				);
				// Dateien loeschen
				if (!$dbFile->sqlDeleteRecord($where)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbFile->getError()));
					exit($this->getError());
				}
				// Verzeichnis loeschen
				if (!$dbDir->sqlDeleteRecord($wsDirectory)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbDir->getError()));
					exit($this->getError());
				}
			}
		} // foreach

		foreach ($actualDirectories as $actualDirectory) {
			// Verzeichnisse sind noch nicht erfasst
			$data = array(
				dbWatchSiteDirectory::field_checksum 		=> $this->md5_directory($actualDirectory),
				dbWatchSiteDirectory::field_files				=> $this->countFiles($actualDirectory),
				dbWatchSiteDirectory::field_path				=> $actualDirectory
			);
			// Datensatz einfuegen
			if (!$dbDir->sqlInsertRecord($data)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbDir->getError()));
				return false;
			}
			// Log
			$info = ws_log_info_dir_added;
			$desc = sprintf(ws_log_desc_dir_added, $actualDirectory);
			$this->addLogEntry(dbWatchSiteLog::category_info, dbWatchSiteLog::group_directory, $info, $desc);
			// Dateien erfassen
			$handle = opendir(WB_PATH.$actualDirectory);
  		while (false !== ($file = readdir($handle))) {
    		if (($file !== ".") && ($file !== "..") && !is_dir($file)) {
    			$data = array(
    				dbWatchSiteFiles::field_checksum 		=> md5_file(WB_PATH.$actualDirectory.$file),
    				dbWatchSiteFiles::field_file				=> $file,
    				dbWatchSiteFiles::field_file_size		=> filesize(WB_PATH.$actualDirectory.$file),
    				dbWatchSiteFiles::field_last_change	=> date('Y-m-d H:i:s', filemtime(WB_PATH.$actualDirectory.$file)),
    				dbWatchSiteFiles::field_path				=> $actualDirectory
    			);
    			if (!$dbFile->sqlInsertRecord($data)) {
    				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbFile->getError()));
    				exit($this->getError());
    			}
    			$desc = sprintf(ws_log_desc_file_added, $actualDirectory.$file);
    			$this->addLogEntry(dbWatchSiteLog::category_info, dbWatchSiteLog::group_file, ws_log_info_file_added, $desc); 	
    		}
  		}			
		}
	} // execWatchJob()
	
	private function getDirectoryTree($directory) {
		$dir = dir($directory);
    while (false !== ($entry = $dir->read())) {
      if (($entry !== '.') && ($entry !== '..') && is_dir($directory.$entry)) {
        $this->dirTree[] = substr($directory.$entry.'/', strlen(WB_PATH));
        $this->getDirectoryTree($directory.$entry.'/');
      }
    }
    $dir->close();
    return true;
	} // getDirectoryTree()
	
	private function md5_directory($directory) {
		return md5(implode(' ',array_map(create_function('$d','return is_dir($d)?$d:md5_file($d);'),glob(WB_PATH.$directory.'*'))));
	}
	
	private function addLogEntry($category, $group, $info, $description) {
		global $dbLog;
		$data = array(
			dbWatchSiteLog::field_category		=> $category,
			dbWatchSiteLog::field_group				=> $group,
			dbWatchSiteLog::field_info				=> $info,
			dbWatchSiteLog::field_description	=> $description
		);
		if (!$dbLog->sqlInsertRecord($data)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbLog->getError()));
			exit($this->getError());
		}
		return true;
	} // addLogEntry()
	
	private function countFiles($directory) {
		$handle = opendir(WB_PATH.$directory);
  	$count = 0;
  	while (false !== ($res = readdir($handle))) {
    	if (is_dir($res)) {
    	} else {
      	$count++;
    	}
  	}
  	return $count;
	} // countFiles();
	
	private function checkIndexFiles() { 
		global $dbWScfg;
		if ($dbWScfg->getValue(dbWatchSiteCfg::cfgCheckIndexFiles)) {
			$add_index = $dbWScfg->getValue(dbWatchSiteCfg::cfgAddIndexFiles);
			$idx_file = file_get_contents(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/htt/index.php.htt');
			// der Verzeichnisbaum wurde bereits von execWatchJob() ausgelesen
			foreach ($this->dirTree as $dir) { 
				if (!file_exists(WB_PATH.$dir.'index.php')) { 
					// missing index.php
					$desc = sprintf(ws_log_desc_dir_index_missing, $dir);
					$this->addLogEntry(dbWatchSiteLog::category_warning, dbWatchSiteLog::group_directory, ws_log_info_dir_index_missing, $desc);
					if ($add_index) { 
						// insert missing index.php
						$count = substr_count($dir, '/');
						$idx = '';
						for ($i=1; $i < $count; $i++) $idx .= '../';
						$idx .= 'index.php';
						$index_file = str_replace('{$relative_index}', $idx, $idx_file);
						if (!file_put_contents(WB_PATH.$dir.'index.php', $index_file)) { 
							// Error writing file
							$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(ws_error_cron_creating_index_file, $dir)));
							// continue, dont break
						}
						else {
							$desc = sprintf(ws_log_desc_dir_index_added, $dir);
							$this->addLogEntry(dbWatchSiteLog::category_info, dbWatchSiteLog::group_directory, ws_log_info_dir_index_added, $desc);
						}
					}
				}
			}
		}
	} // checkIndexFiles()
	
	private function sendReport() {
		global $dbWScfg;
		global $dbCronjobData;
		global $wb;
		global $dbLog;
		if ($dbWScfg->getValue(dbWatchSiteCfg::cfgSendReports)) {
			// yes, send reports
			$times = $dbWScfg->getValue(dbWatchSiteCfg::cfgSendReportsAtHours);
			if (count($times) > 0) {
				// check next report sending time
				$where = array(dbCronjobData::field_item => dbCronjobData::item_next_report);
				$next_report = array();
				if (!$dbCronjobData->sqlSelectRecord($where, $next_report)) {
					$this->setError(sprintf('[%s - %s} %s', __METHOD__, __LINE__, $dbCronjobData->getError()));
					exit($this->getError());
				}
				// get actual time
				$now = time();
				if (count($next_report) == 0) {
					// entry does not exists - get next execution time
					$next_dt = $this->getNextReportExecutionTime($now);
					$next_report = array(
						dbCronjobData::field_item 	=> dbCronjobData::item_next_report,
						dbCronjobData::field_value	=> date('Y-m-d H:i:s'));
					if (!$dbCronjobData->sqlInsertRecord($next_report)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbCronjobData->getError()));
						exit($this->getError());
					}
				}
				else {
					$next_report = $next_report[0];
				}
				// get next execution time
				if (false == ($next_time = strtotime($next_report[dbCronjobData::field_value]))) {
					// error invalid time
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(ws_error_cron_time_invalid, $next_report[dbCronjobData::field_value])));
					exit($this->getError());
				} 
				if ($now >= $next_time) {
					// ok - send a report
					$emails = $dbWScfg->getValue(dbWatchSiteCfg::cfgSendReportsToMail);
					if ((count($emails) > 0) && !empty($emails[0])) {
						// send the report NOW
						$where = array(dbCronjobData::field_item => dbCronjobData::item_last_report);
						$last_report = array();
						if (!$dbCronjobData->sqlSelectRecord($where, $last_report)) {
							$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbCronjobData->getError()));
							exit($this->getError());
						}
						if (count($last_report) == 0) {
							// entry does not exists, set actual time
							$last_time = time();
							$data = array(dbCronjobData::field_item => dbCronjobData::item_last_report,
														dbCronjobData::field_value => date('Y-m-d H:i:s', $last_time));
							if (!$dbCronjobData->sqlInsertRecord($data)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbCronjobData->getError()));
								exit($this->getError());
							}
						}
						else {
							$last_time = strtotime($last_report[0][dbCronjobData::field_value]);
						}
						$SQL = sprintf(	"SELECT * FROM %s WHERE %s>='%s' ORDER BY %s DESC",
														$dbLog->getTableName(),
														dbWatchSiteLog::field_timestamp,
														date('Y-m-d H:i:s', $last_time),
														dbWatchSiteLog::field_id);
						$logs = array();
						if (!$dbLog->sqlExec($SQL, $logs)) {
							$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbLog->getError()));
							exit($this->getError());
						}
						$items = '';
						foreach ($logs as $log) {
							$items .= sprintf('%s - %s%s<br />', 
																date(ws_cfg_date_time, strtotime($log[dbWatchSiteLog::field_timestamp])),
																($log[dbWatchSiteLog::field_category] == dbWatchSiteLog::category_warning) ? strtoupper($log[dbWatchSiteLog::field_category]).' - ' : '',
																$log[dbWatchSiteLog::field_description]
																);
						}
						if (empty($items)) {
							$body = ws_mail_body_log_no_items;
						}
						else {
							$body = sprintf(ws_mail_body_log_items, date(ws_cfg_date_time, $last_time), $items);
						}
						foreach ($emails as $email) {
							if (!$wb->mail('', $email, ws_mail_subject_log, $body)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(ws_error_sending_mail, $email)));
								exit($this->getError());
							}
						}
						// all emails are send, update database
						$last_time = $now;
						$next_time = $this->getNextReportExecutionTime($now);
						$where = array(dbCronjobData::field_item => dbCronjobData::item_last_report);
						$data = array(dbCronjobData::field_value => date('Y-m-d H:i:s', $last_time));
						if (!$dbCronjobData->sqlUpdateRecord($data, $where)) {
							$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbCronjobData->getError()));
							exit($this->getError());
						}
						$where = array(dbCronjobData::field_item => dbCronjobData::item_next_report);
						$data = array(dbCronjobData::field_value => date('Y-m-d H:i:s', $next_time));
						if (!$dbCronjobData->sqlUpdateRecord($data, $where)) {
							$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbCronjobData->getError()));
							exit($this->getError());
						}
						$desc = sprintf(ws_log_desc_report_send, implode(', ', $emails));
						$this->addLogEntry(dbWatchSiteLog::category_info, dbWatchSiteLog::group_report, ws_log_info_report_send, $desc);
					}
					else {
						// no email address configured
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, ws_error_cron_email_missing));
						exit($this->getError());
					}
				}				
				
			} // count($times)
			else {
				// error: missing times
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, ws_error_cron_report_time_missing));
				// go ahead with the script!
			}
		}
	} // sendReport()
	
	private function getNextReportExecutionTime($last_execution) {
		global $dbWScfg;
		// get the configured execution times
		$times = $dbWScfg->getValue(dbWatchSiteCfg::cfgSendReportsAtHours);
		if (count($times) > 0) {
			// sort array
			asort($times);
			foreach ($times as $time) {
				$hour = -1;
				$minute = -1;
				$this->checkTime($time, $hour, $minute);
				$day = date('d', $last_execution);
				$month = date('m', $last_execution);
				$year = date('Y', $last_execution);
				$check_time = mktime($hour, $minute, 0, $month, $day, $year);
				if ($check_time > $last_execution) {
					// ok - use this time
					return $check_time;
				}
			}
			// no execution time for this day, try tomorrow...
			foreach ($times as $time) {
			$hour = -1;
				$minute = -1;
				$this->checkTime($time, $hour, $minute);
				$day = date('d', $last_execution)+1;
				$month = date('m', $last_execution);
				$year = date('Y', $last_execution);
				$check_time = mktime($hour, $minute, 0, $month, $day, $year);
				if ($check_time > $last_execution) {
					// ok - use this time
					return $check_time;
				}
			}
		}
		else {
			// error: missing times
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, ws_error_cron_report_time_missing));
			exit($this->getError());
		}
		return false;
	} // getNextReportExecutionTime()
	
	private function checkTime($time, &$hour, &$minute) {
		if (strpos($time, ':') !== false) {
			// assume H:i
			list($hour, $minute) = explode(':', $time);
		}
		else {
			// assume only hour
			$hour = (int) $time;
			$minute = 0;
		}
		if (($hour  < 0) || ($hour > 23) || ($minute < 0) || ($minute > 59)) {
			// invalid time
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(ws_error_cron_report_time_invalid, $time)));
			exit($this->getError());
		}
		return true;
	} // checkTime()
	
} // class cronjob