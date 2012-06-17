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

class wsTools {

	private $old_pass = array();

	/**
   * Generiert ein neues Passwort der Laenge $length
   *
   * @param INT $length
   * @return STR
   */
  public function generatePassword($length=7) {
    $r = array_merge(
      range("a", "z"),
      range("a", "z"),
      range("A", "Z"),
      range(1, 9),
      range(1, 9)
    );
		$not = array_merge(
			array('i', 'l', 'o', 'I','O'),
			$this->old_pass
		);
		$r = array_diff($r, $not);
    shuffle($r);
		$this->old_pass = array_slice($r, 0, intval($length) );
    return implode("", $this->old_pass );
  } // generatePassword()

  public function getDisplayName() {
  	return (isset($_SESSION['DISPLAY_NAME'])) ? $_SESSION['DISPLAY_NAME'] : 'SYSTEM';
  }

} // class wsTools
?>