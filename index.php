<?php
//$Header: /cvsroot/tsheet/timesheet.php/simple.php,v 1.7 2005/05/23 05:39:39 vexil Exp $
error_reporting(E_ALL);
ini_set('display_errors', true);

require("class.AuthenticationManager.php");

if ($authenticationManager->isLoggedIn()) {
	include_once("common.inc");
	gotoStartPage();
} else {
	header("Location: login.php");
}

// vim:ai:ts=4:sw=4
?>
