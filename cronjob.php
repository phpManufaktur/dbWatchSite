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

global $dbLog;
global $dbWScfg;
global $dbDir;
global $dbFile;
global $dbCronjobData;
global $dbCronjobErrorLog;
global $wsTools;

if (!is_object($dbLog)) $dbLog = new dbWatchSiteLog();
if (!is_object($dbWScfg)) $dbWScfg = new dbWatchSiteCfg();
if (!is_object($dbDir)) $dbDir = new dbWatchSiteDirectory();
if (!is_object($dbFile)) $dbFile = new dbWatchSiteFiles();
if (!is_object($dbCronjobData)) $dbCronjobData = new dbCronjobData(true);
if (!is_object($dbCronjobErrorLog)) $dbCronjobErrorLog = new dbCronjobErrorLog(true);
if (!is_object($wsTools)) $wsTools = new wsTools();

$cronjob = new cronjob();
$cronjob->action();

class cronjob {
	
	const request_key			= 'key';
	
	private $error = '';
	private $start_script = 0;
	//private $max_exec_time = 0;
	private $dirTree = array();
	
	public function __construct() {
		global $dbWScfg;
		$this->start_script = time(true);
		//$this->max_exec_time = $dbWScfg->getValue(dbWatchSiteCfg::cfgCronjobExecTime);
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
							 array(dbCronjobData::field_item => dbCronjobData::item_last_job, dbCronjobData::field_value => ''));
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
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf('Access denied from IP %s: invalid or missing cronjob key!', $ip)));
			// dont give attacker any hint, so exit with regular code...
			exit(0);
		}
		
		// Exec the watch job...
		$this->execWatchJob(); 
		// Job beendet, Ausfuehrungszeit festhalten
		$info = ws_log_info_cronjob_finished;
		$desc = sprintf(ws_log_desc_cronjob_finished, (time(true) - $this->start_script));
		$this->addLogEntry(dbWatchSiteLog::category_info, dbWatchSiteLog::group_cronjob, $info, $desc);
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
        //$this->dirTree[] = str_replace(WB_PATH, '', $directory.$entry.'/');
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
	
} // class cronjob