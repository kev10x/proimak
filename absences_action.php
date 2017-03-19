<?php

// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclAbsences')) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclAbsences'));
	exit;
}

$contextUser = strtolower($_SESSION['contextUser']);
$loggedInUser = strtolower($_SESSION['loggedInUser']);

if (empty($loggedInUser))
        errorPage("Could not determine the logged in user");

if (empty($contextUser))
        errorPage("Could not determine the context user");

//load local vars from superglobals
if (isset($_REQUEST['uid']))
	$uid = $_REQUEST['uid'];
else
	$uid = $contextUser;

//load local vars from superglobals
$month = $_REQUEST['month'];
$day = $_REQUEST['day'];
$year = $_REQUEST['year'];
$last_day = isset($_REQUEST['last_day']) ? $_REQUEST['last_day']: "31";
$action = isset($_REQUEST['action']) ? $_REQUEST['action']: 0;

//set the return location
$Location = "absences.php?month=$month&amp;year=$year&amp;day=$day&amp;uid=$uid";

if ($action!=0) {
	$endMonth = $month + 1;
	$endYear = $year;
	if ($endMonth > 12) {
		$endMonth = 1;
		$endYear++;

	}
	//clear the absences for this user in the month
	dbQuery("DELETE FROM $ABSENCE_TABLE WHERE user='$uid' AND ".
				"date >= '$year-$month-01 00:00:00' AND ".
				"date < '$endYear-$endMonth-01 00:00:00'");

	for ($i=1; $i<=$last_day; $i++) {
		$AMtype = mysql_real_escape_string($_POST["AMtype".$i]);
		$AMtext = urlencode($_POST["AMtext".$i]);
		$PMtype = mysql_real_escape_string($_POST["PMtype".$i]);
		$PMtext = urlencode($_POST["PMtext".$i]);

		if (($AMtype!='')&&($AMtype!='Public')) {
			dbquery("INSERT INTO $ABSENCE_TABLE VALUES ".
				"(0,'$year-$month-$i 00:00:00','AM','$AMtext','$AMtype','$uid')");
		}
		if (($PMtype!='')&&($PMtype!='Public')) {
			dbquery("INSERT INTO $ABSENCE_TABLE VALUES ".
				"(0,'$year-$month-$i 00:00:00','PM','$PMtext','$PMtype','$uid')");
		}
	}
}
Header("Location: $Location");
?>
