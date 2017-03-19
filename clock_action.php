<?php
// $Header: /cvsroot/tsheet/timesheet.php/clock_action.php,v 1.10 2005/06/17 01:42:51 vexil Exp $

// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
//require("debuglog.php");
if (!$authenticationManager->isLoggedIn()) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]");
	exit;
}

$contextUser = strtolower($_SESSION['contextUser']);

/**
 * Updated by robsearles 26 Jan 2008
 * To enable the "stop" link to work in the "Daily Timesheet" page
 * set a few default values in the list of local vars below
 */
//load local vars from superglobals
$month = isset($_REQUEST['month']) ? $_REQUEST['month'] : false;
$day = isset($_REQUEST['day']) ? $_REQUEST['day'] : false;
$year = isset($_REQUEST['year']) ? $_REQUEST['year'] : false;
$client_id = $_REQUEST['client_id'];
$proj_id = $_REQUEST['proj_id'];
$task_id = $_REQUEST['task_id'];
$origin = isset($_REQUEST['origin']) ? $_REQUEST['origin'] : 'daily.php';
$destination = isset($_REQUEST['destination']) ? $_REQUEST['destination'] : 'daily.php';
$fromPopupWindow = isset($_REQUEST['fromPopupWindow']) ? $_REQUEST['fromPopupWindow']: false;
$clockonoff = isset($_REQUEST['clockonoff']) ? $_REQUEST['clockonoff']: "";
$clock_on_time_hour = isset($_REQUEST['clock_on_time_hour']) ? $_REQUEST['clock_on_time_hour']: 0;
$clock_on_time_min = isset($_REQUEST['clock_on_time_min']) ? $_REQUEST['clock_on_time_min']: 0;
$clock_off_time_hour = isset($_REQUEST['clock_off_time_hour']) ? $_REQUEST['clock_off_time_hour']: 0;
$clock_off_time_min = isset($_REQUEST['clock_off_time_min']) ? $_REQUEST['clock_off_time_min']: 0;
$log_message = isset($_REQUEST['log_message']) ? $_REQUEST['log_message']: "";
$log_message_presented = isset($_REQUEST['log_message_presented']) ? $_REQUEST['log_message_presented']: false;
$clock_on_check = isset($_REQUEST['clock_on_check']) ? $_REQUEST['clock_on_check']: "";
$clock_off_check = isset($_REQUEST['clock_off_check']) ? $_REQUEST['clock_off_check']: "";
$clock_on_radio = isset($_REQUEST['clock_on_radio']) ? $_REQUEST['clock_on_radio']: "";
$clock_off_radio = isset($_REQUEST['clock_off_radio']) ? $_REQUEST['clock_off_radio']: "";

if($fromPopupWindow == 'false')
	$fromPopupWindow = false;

//$debug = new logfile();
//$debug->write("destination = \"$destination\"\n");
//$debug->write("fromPopupWindow = \"$fromPopupWindow\"\n");

/**
 * @todo the &var= stuff needs to be changed to &amp;var= BUT 
 * if the link is used in javascript then this must be carefully checked
 * as I think javascript doesn't like &amp;  
 */ 
//set the return location
$Location = "$destination?month=$month&year=$year&day=$day&destination=$destination";
if ($destination == "stopwatch.php" || $destination == "daily.php")
	$Location = "$destination?client_id=$client_id&proj_id=$proj_id&task_id=$task_id&month=$month&year=$year&day=$day&destination=$destination";

//determine the action
if (empty($clockonoff)) {
	if (!empty($clock_on_check) && !empty($clock_off_check))
		$clockonoff = "clockonandoff";
	else if (!empty($clock_on_check)) {
		if ($clock_on_radio == "now")
			$clockonoff = "clockonnow";
		else
			$clockonoff = "clockonat";
	} else if (!empty($clock_off_check)) {
		if ($clock_off_radio == "now")
			$clockonoff = "clockoffnow";
		else
			$clockonoff = "clockoffat";
	} else
		errorPage("You must select at least one checkbox to indicate your action: clock on, clock off, or both.", $fromPopupWindow);
}

if ($clock_on_radio == "now" && $clock_off_radio == "now")
	errorPage("You cannot clock on and off at with the same clock-on and clock-off time.", $fromPopupWindow);

//get the clock on/off "now" dates and times
//We're forced to recalculate the date as well as the time because the dialog
//box that says "now" could have been opened during the previous day
//(ie. clock on at 11:30pm, clock off at 2:15am)
if ($clock_on_radio == "now" || $clockonoff == "clockonnow") {
	$realToday = getdate(time());
	$month = $realToday['mon']; 
	$day = $realToday['mday'];
	$year = $realToday['year'];
	$clock_on_time_hour = $realToday["hours"];
	$clock_on_time_min = $realToday["minutes"];
}

if ($clock_off_radio == "now" || $clockonoff == "clockoffnow") {
	$realToday = getdate(time());
	$month = $realToday['mon']; 
	$day = $realToday['mday'];
	$year = $realToday['year'];
	$clock_off_time_hour = $realToday["hours"];
	$clock_off_time_min = $realToday["minutes"];
}

$onStamp = mktime($clock_on_time_hour, $clock_on_time_min, 0, $month, $day, $year);
$offStamp = mktime($clock_off_time_hour, $clock_off_time_min, 0, $month, $day, $year);

//call appropriate functions
if ($clockonoff == "clockonandoff")
	clockonandoff();
else if ($clockonoff == "clockonat") {
	clockon();
} else if ($clockonoff == "clockoffat") {
	clockoff();
} else if ($clockonoff == "clockonnow") {
	//if we're coming from the popup window then set the return location to the origin
	if ($fromPopupWindow == "true")
		//set the return location
		$Location = "$origin?client_id=$client_id&proj_id=$proj_id&task_id=$task_id&month=$month&year=$year&day=$day&destination=$destination";

	clockon();
} else if ($clockonoff == "clockoffnow") {
	clockoff();
} else	 //redirects to a page where the user can enter the log message. Then returns here.
	errorPage("Could not determine the clock on/off action. Please report this as a bug", $fromPopupWindow);

//This is functionally the end of this file...

function getLogMessage() {
	//import global vars
	global $contextUser, $year, $month, $day, $task_id, $proj_id, $client_id, $Location;
	global $origin, $destination, $clock_on_time_hour, $clock_off_time_hour,
						$clock_on_time_min, $clock_off_time_min, $clockonoff;
	global $log_message, $log_message_presented, $fromPopupWindow;

	if ($log_message_presented == false) {
		$targetWindowLocation = "log_message.php".
					"?origin=$origin&destination=$destination".
					"&clock_on_time_hour=$clock_on_time_hour".
					"&clock_off_time_hour=$clock_off_time_hour".
					"&clock_on_time_min=$clock_on_time_min".
					"&clock_off_time_min=$clock_off_time_min".
					"&year=$year".
					"&month=$month".
					"&day=$day".
					"&client_id=$client_id".
					"&proj_id=$proj_id".
					"&task_id=$task_id".
					"&clockonoff=$clockonoff";

		if ($fromPopupWindow == "true") {
			//close this popup window and load the log message page in the main window.
			loadMainPageAndCloseWindow($targetWindowLocation);
		}
		else {
			Header("Location: $targetWindowLocation");
			exit;
		}
	}
}

function clockon() {
	include("install/table_names.inc");

	//import global vars
	global $contextUser, $onStamp, $task_id, $proj_id, $Location, $fromPopupWindow;

	if (empty($Location))
		errorPage("failed sanity check, location empty");

	//check that we are not already clocked on
	$querystring = "SELECT $TIMES_TABLE.start_time, $TASK_TABLE.name FROM ".
			"$TIMES_TABLE, $TASK_TABLE WHERE ".
			"username='$contextUser' AND ".
			"end_time='0' AND ".
			//"start_time>='$year-$month-$day' AND ".
			//"start_time<='$year-$month-$day 23:59:59' AND ".
			"$TIMES_TABLE.task_id=$task_id AND ".
			"$TIMES_TABLE.proj_id=$proj_id AND ".
			"$TASK_TABLE.task_id=$task_id AND ".
			"$TASK_TABLE.proj_id=$proj_id";

	list($qh,$num) = dbQuery($querystring);
	$resultset = dbResult($qh);

	if ($num > 0)
		errorPage("You have already clocked on for task '$resultset[name]' at $resultset[start_time].  Please clock off first.", $fromPopupWindow);

	$onStr = strftime("%Y-%m-%d %H:%M:%S", $onStamp);

	//now insert the record for this clock on
	$querystring = "INSERT INTO $TIMES_TABLE (username, start_time, proj_id,task_id) ".
			"VALUES ('$contextUser','$onStr', $proj_id, $task_id)";
	list($qh,$num) = dbQuery($querystring);

	//now output an ok page, the redirect back
	print "<html>\n";
	print "	<head>\n";
	print " 	<script language=\"javascript\">\n";
	print "				function alertAndLoad()\n";
	print "				{\n";
	print "					alert('Clocked on successfully');\n";
	if ($fromPopupWindow)
		print "					window.opener.location.reload();\n";
	print "					window.location=\"$Location\";\n";
	print "				}\n";
	print "			</script>\n";
	print "		</head>\n";
	print "		<body onLoad=\"javascript:alertAndLoad();\">\n";
	print "		</body>\n";
	print "	</html>\n";
	exit;
}

function clockoff() {
	include("install/table_names.inc");

	//import global vars
	global $contextUser, $year, $month, $day, $offStamp, $task_id, $proj_id, $Location;
	global $log_message, $log_message_presented, $fromPopupWindow;

	$offStr = strftime("%Y-%m-%d %H:%M:%S", $offStamp);

	//check that we are actually clocked on
	$querystring = "SELECT start_time, start_time < '$offStr' AS valid FROM $TIMES_TABLE WHERE ".
			"username='$contextUser' AND ".
			"end_time=0 AND ".
			//"start_time >= '$year-$month-$day' AND ".
			//"start_time <= '$year-$month-$day 23:59:59' AND ".
			"proj_id=$proj_id AND ".
			"task_id=$task_id";

	list($qh,$num) = dbQuery($querystring);
	$data = dbResult($qh);

	if ($num == 0)
		errorPage("You are not currently clocked on. You must clock on before you can clock off. If you have just clocked on please wait at least one minute before clocking off", $fromPopupWindow);	
	//also check that the clockoff time is after the clockon time
	else if ($data["valid"] == 0)
		errorPage("You must clock off <i>after</i> you clock on.", $fromPopupWindow);

	$onStamp = strtotime($data["start_time"]);
	$duration = ($offStamp-$onStamp)/60;

	//do we need to present the user with a log message screen?
	if ($log_message_presented == false)
		getLogMessage();

	//now insert the record for this clock off
	$log_message = addslashes($log_message);
	$querystring = "UPDATE $TIMES_TABLE SET log_message='$log_message', end_time='$offStr', duration='$duration' WHERE ".
			"username='$contextUser' AND ".
			"proj_id=$proj_id AND ".
			"end_time=0 AND ".
			//"start_time >= '$year-$month-$day' AND ".
			//"start_time < '$year-$month-$day 23:59:59' AND ".
			"task_id=$task_id";
	list($qh,$num) = dbQuery($querystring);
	Header("Location: $Location");
}

function clockonandoff() {
	include("install/table_names.inc");

	//import global vars
	global $contextUser, $year, $month, $day, $task_id, $proj_id, $Location;
	global $destination, $clock_on_time_hour, $clock_off_time_hour, $clock_on_time_min, $clock_off_time_min;
	global $log_message, $log_message_presented, $onStamp, $offStamp;
	global $clock_on_radio, $clock_off_radio, $fromPopupWindow;

	//make sure we're not clocking on after clocking off
	if ($offStamp < $onStamp)
		errorPage("You cannot have your clock on time ($clock_on_time_hour:$clock_on_time_min) ".
			"later than your clock off time ($clock_off_time_hour:$clock_off_time_min)", $fromPopupWindow);
	else if ($onStamp == $offStamp)
		//errorPage("You cannot clock on and off with the same time. ($clock_on_time_hour:$clock_on_time_min = $clock_off_time_hour:$clock_off_time_min)", $fromPopupWindow);
		errorPage("You cannot clock on and off with the same time. ($onStamp == $offStamp)", $fromPopupWindow);

	if ($log_message_presented == false)
		getLogMessage();

	$duration=($offStamp - $onStamp)/60; //get duration in minutes
	$onStr = strftime("%Y-%m-%d %H:%M:%S", $onStamp);
	$offStr = strftime("%Y-%m-%d %H:%M:%S", $offStamp);
	
	$log_message = addslashes($log_message);
	$queryString = "INSERT INTO $TIMES_TABLE (username, start_time, end_time, duration, proj_id, task_id, log_message) ".
			"VALUES ('$contextUser','$onStr', '$offStr', '$duration', " .
			"$proj_id, $task_id, '$log_message')";
	list($qh,$num) = dbQuery($queryString);

	Header("Location: $Location");
	exit;
}
?>
<?php
// vim:ai:ts=4:sw=4
?>
