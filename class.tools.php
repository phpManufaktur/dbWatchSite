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

class wsTools {
	
	/**
   * Generiert ein neues Passwort der Laenge $length
   *
   * @param INT $length
   * @return STR
   */
  public function generatePassword($length=7) {
		$new_pass = '';
		$salt = 'abcdefghjkmnpqrstuvwxyz123456789';
		srand((double)microtime()*1000000);
		$i=0;
		while ($i <= $length) {
			$num = rand() % 33;
			$tmp = substr($salt, $num, 1);
			$new_pass = $new_pass . $tmp;
			$i++; }
		return $new_pass;
  } // generatePassword()

  public function getDisplayName() {
  	return (isset($_SESSION['DISPLAY_NAME'])) ? $_SESSION['DISPLAY_NAME'] : 'SYSTEM';
  }
  
} // class wsTools
?>