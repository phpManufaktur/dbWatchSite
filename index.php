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

$protocol = explode('/', $_SERVER['SERVER_PROTOCOL']);
$url = strtolower($protocol[0]).'://'.$_SERVER['SERVER_NAME'];
header("Location: $url");

?>