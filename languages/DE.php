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

// Deutsche Modulbeschreibung
$module_description 	= 'dbWatchSite überwacht die WebsiteBaker Installation und protokolliert Änderungen am Dateisystem';

// name of the person(s) who translated and edited this language file
$module_translation_by = 'Ralf Hertsch (phpManufaktur)';

define('ws_btn_abort',														'Abbruch');
define('ws_btn_edit',															'Bearbeiten');
define('ws_btn_export',														'Exportieren');
define('ws_btn_import',														'Importieren');
define('ws_btn_ok',																'Übernehmen');
define('ws_btn_save',															'Speichern');

define('ws_category_error',												'Fehler');
define('ws_category_hint',												'Hinweis');
define('ws_category_info',												'Information');
define('ws_category_warning',											'Warnung');

define('ws_cfg_thousand_separator',								'.');
define('ws_cfg_date',															'd.m.Y');
define('ws_cfg_date_separator',										'.');
define('ws_cfg_date_time',												'd.m.Y - H:i');
define('ws_cfg_decimal_separator',								',');
define('ws_cfg_price',														'%s €');
define('ws_cfg_euro',															'%s EUR');

define('ws_desc_cfg_add_index_files',							'Fügt automatisch fehlende Index Dateien für den Verzeichnisschutz in die Verzeichnisse ein. Setzt voraus, dass der Schalter "Index Dateien prüfen" gesetzt ist. <b>1=AN</b>, <i>0=AUS</i>');
define('ws_desc_cfg_check_index_files',						'Prüft, ob in allen Verzeichnissen eine index.php für den Verzeichnisschutz vorhanden ist und meldet fehlende Dateien. <b>1=AN</b>, <i>0=AUS</i>');
define('ws_desc_cfg_cronjob_key',									'Um zu verhindern, dass Cronjobs durch einen einfachen Aufruf der <b>cronjob.php</b> ausgeführt werden, muss der angegebene Schlüssel als Parameter übergeben werden. Der Aufruf der Datei lautet <b>cronjob.php?key=<i>SCHLÜSSEL</i></b>.');
define('ws_desc_cfg_log_cronjob_exec_time',				'Die Ausführungsdauer der Cronjobs festhalten und in der Übersicht mit anzeigen. <b>1=AN</b>, <i>0=AUS</i>');
define('ws_desc_cfg_log_show_max',								'Legen Sie fest, wieviele Einträge des Logfiles höchstens in der Übersicht angezeigt werden sollen.');
define('ws_desc_cfg_send_reports',								'LEgen Sie fest, ob täglich Berichte versendet werden sollen. <b>1=AN</b>, <i>0=AUS</i>');
define('ws_desc_cfg_send_reports_at_hours',				'Legen Sie fest, zu welchen täglichen Zeiten (STUNDE:MINUTE) die Berichte an Sie gesendet werden sollen. Trennen Sie mehrere Zeiten durch ein Komma.');
define('ws_desc_cfg_send_reports_to_mail',				'Legen Sie fest, an welche E-Mail Adresse(n) die täglichen Berichte gesendet werden sollen. Trennen Sie mehrere E-Mail Adressen durch ein Komma.');
define('ws_desc_cfg_watch_site_active',						'Legen Sie fest, ob dbWatchSite im Hintergrund ausgeführt wird - <b>1=AN</b>, <i>0=AUS</i>');

define('ws_error_cfg_id',									 				'<p>Der Konfigurationsdatensatz mit der <b>ID %05d</b> konnte nicht ausgelesen werden!</p>');
define('ws_error_cfg_name',								 				'<p>Zu dem Bezeichner <b>%s</b> wurde kein Konfigurationsdatensatz gefunden!</p>');
define('ws_error_cron_creating_index_file',				'Die Datei %sindex.php konnte nicht erstellt werden.');
define('ws_error_cron_email_missing',							'Es ist kein E-Mail Empfänger für das Versenden von Berichten festgelegt, bitte prüfen Sie die Einstellungen!');
define('ws_error_cron_key_invalid',								'Der Zugriff auf den Cronjob von der IP %s wurde verweigert: Es wurde kein oder ein ungültiger Schlüssel als Parameter übergeben!');
define('ws_error_cron_report_time_invalid',				'Ungültige Zeitangabe (%s) für das Versenden von Berichten, bitte prüfen Sie die Zeitangaben in den Einstellungen!');
define('ws_error_cron_report_time_missing',				'Es ist keine Zeit für das Versenden von Berichten festgelegt, bitte prüfen Sie die Zeitangaben in den Einstellungen!');
define('ws_error_cron_time_invalid',							'Ungültige Zeitangabe - %s!');
define('ws_error_sending_mail',										'Die E-Mail an %s konnte nicht versendet werden, es ist ein nicht näher spezifizierter Fehler aufgetreten.');

define('ws_group_cronjob',												'Cronjob');
define('ws_group_directory',											'Verzeichnis');
define('ws_group_file',														'Datei');
define('ws_group_report',													'Bericht');

define('ws_header_cfg',														'Einstellungen');
define('ws_header_cfg_description',								'Beschreibung');
define('ws_header_cfg_identifier',								'Bezeichner');
define('ws_header_cfg_import',										'Daten importieren');
define('ws_header_cfg_label',											'Label');
define('ws_header_cfg_typ',												'Typ');
define('ws_header_cfg_value',											'Wert');
define('ws_header_log',														'Überwachungsprotokoll');
define('ws_header_log_error',											'Fehlerprotokoll');

define('ws_intro_cfg',														'<p>Bearbeiten Sie die Einstellungen für <b>dbWatchSite</b>.</p>');
define('ws_intro_log_error',											'<p>Übersicht über Fehler, die während der Ausführung des Cronjob aufgetreten sind.</p>');
define('ws_intro_log_last_call',									'<p>Der Cronjob für die Überwachung der Verzeichnisse und Dateien wurde zuletzt am <b>%s</b> aufgerufen.</p>');
define('ws_intro_log_no_call',										'<p>Es konnte nicht festgestellt werden, wann der Cronjob zuletzt aufgerufen wurde.</p>');
define('ws_intro_log_no_error',										'<p>Es liegen keine Fehlermeldungen durch den Cronjob vor.</p>');

define('ws_label_cfg_add_index_files',						'Index Dateien hinzufügen');
define('ws_label_cfg_check_index_files',					'Index Dateien prüfen');
define('ws_label_cfg_cronjob_key',								'Cronjob Schlüssel');
define('ws_label_cfg_log_cronjob_exec_time',			'Cronjob Ausführungsdauer anzeigen');
define('ws_label_cfg_log_show_max',								'max. Logeinträge anzeigen');
define('ws_label_cfg_send_reports',								'Berichte versenden');
define('ws_label_cfg_send_reports_at_hours',			'Berichte versenden (Zeiten)');
define('ws_label_cfg_send_reports_to_mail',				'Berichte versenden (E-Mail)');
define('ws_label_cfg_watch_site_active',					'dbWatchSite ausführen');
define('ws_label_csv_export',											'CSV Export');
define('ws_label_csv_import',											'CSV Import');

define('ws_log_desc_cronjob_finished',						'Der Cronjob wurde in <b>%s</b> Sekunden durchgeführt.');
define('ws_log_desc_dir_added',										'Das Verzeichnis <b>%s</b> wurde hinzugefügt.');
define('ws_log_desc_dir_index_added',							'Im Verzeichnis <b>%s</b> wurde die <b>index.php</b> hinzugefügt.');
define('ws_log_desc_dir_index_missing',						'Im Verzeichnis <b>%s</b> fehlt die <b>index.php</b>.');
define('ws_log_desc_dir_md5_different',						'Bei dem Verzeichnis <b>%s</b> hat sich die Prüfsumme verändert.');
define('ws_log_desc_dir_no_longer_exists',				'Das Verzeichnis <b>%s</b> wurde entfernt.');
define('ws_log_desc_file_added',									'Die Datei <b>%s</b> wurde hinzugefügt.');
define('ws_log_desc_file_md5_different',					'Bei der Datei <b>%s</b> hat sich die Prüfsumme verändert.');
define('ws_log_desc_file_mtime_different',				'Bei der Datei <b>%s</b> hat sich das Datum geändert.');
define('ws_log_desc_file_no_longer_exists',				'Die Datei <b>%s</b> wurde entfernt.');
define('ws_log_desc_file_size_different',					'Bei der Datei <b>%s</b> hat sich die Größe verändert.');
define('ws_log_desc_report_send',									'Der Überwachungsprotokoll wurde an %s versendet');
define('ws_log_info_cronjob_finished',						'Cronjob beendet');
define('ws_log_info_dir_added',										'Verzeichnis hinzugefügt');
define('ws_log_info_dir_index_added',							'index.php hinzugefügt');
define('ws_log_info_dir_index_missing',						'index.php fehlt');
define('ws_log_info_dir_md5_different',						'Prüfsumme unterschiedlich');
define('ws_log_info_dir_no_longer_exists',				'Verzeichnis existiert nicht mehr');
define('ws_log_info_file_added',									'Datei hinzugefügt');
define('ws_log_info_file_md5_different',					'Prüfsumme unterschiedlich');
define('ws_log_info_file_mtime_different',				'Dateidatum unterschiedlich');
define('ws_log_info_file_no_longer_exists',				'Datei existiert nicht mehr');
define('ws_log_info_file_size_different',					'Dateigröße unterschiedlich');
define('ws_log_info_report_send',									'Überwachungsbericht versendet');

define('ws_mail_body_log_no_items',								'- Es befinden sich keine Einträge im Überwachungsprotokoll -');
define('ws_mail_body_log_items',									'<p>Letzte Übermittlung: %s</p><p>Protokoll:</p><p>%s</p>');
define('ws_mail_subject_log',											'Überwachungsbericht');

define('ws_msg_cfg_add_exists',										'<p>Der Konfigurationsdatensatz mit dem Bezeichner <b>%s</b> existiert bereits und kann nicht noch einmal hinzugefügt werden!</p>');
define('ws_msg_cfg_add_incomplete',								'<p>Der neu hinzuzuf�gende Konfigurationsdatensatz ist unvollständig! Bitte prüfen Sie Ihre Angaben!</p>');
define('ws_msg_cfg_add_success',									'<p>Der Konfigurationsdatensatz mit der <b>ID #%05d</b> und dem Bezeichner <b>%s</b> wurde hinzugefügt.</p>');
define('ws_msg_cfg_csv_export',										'<p>Die Konfigurationsdaten wurden als <b>%s</b> im /MEDIA Verzeichnis gesichert.</p>');
define('ws_msg_cfg_id_updated',										'<p>Der Konfigurationsdatensatz mit der <b>ID #%05d</b> und dem Bezeichner <b>%s</b> wurde aktualisiert.</p>');
define('ws_msg_invalid_email',										'<p>Die E-Mail Adresse <b>%s</b> ist nicht gültig, bitte prüfen Sie Ihre Eingabe.</p>');

define('ws_tab_about',														'?');
define('ws_tab_config',														'Einstellungen');
define('ws_tab_log',															'Überwachung');
define('ws_tab_watch_log',												'Protokoll');
define('ws_tab_watch_error',											'Fehler');

?>