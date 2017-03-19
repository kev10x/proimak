<?php
// $Header: /cvsroot/tsheet/timesheet.php/simple_action.php,v 1.2 2005/03/09 01:40:46 vexil Exp $

// Two potential problems exist, both are caused by this file not being able to 
// easily modify data that does not belong to the current context week.
//
// Setup: Time is entered using daily/popup screens; a task's time begins 
//        during week 1, but ends during week2.
//
// Example: worked some Sunday from 10 PM till 3 AM Monday
//
// Bug 1:  Week 1 is edited with the simple form, the two hours for Sunday
//         will be continue to exist as it should, but the 3 hours for Monday
//         will have disappeared.
//
// Bug 2:  Week 2 is edited with the simple form, the 3 hours for Monday will
//		   continuously be added to that task for Monday every time the "Save"
//         button is clicked.
//
// The problem cannot occur if only daily or simple forms are able to be
// used.  So, disable one type of entry or the other.
//
// Other Possible Resolutions if that's not acceptable to you:
//    a) total rewrite of this to update affected entries instead of trying
//       to delete all the records and re-creating them
//    b) rework entire system and automatically split all tasks that span
//       days into multiple discrete day tasks.
//    c} figure out logic to split up tasks that would be tickled by this bug
//       and save and update the extra day's times as needed.

// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
//require("debuglog.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclSimple')) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclSimple'));
	exit;
}

// Connect to database.
$dbh = dbConnect();
$contextUser = strtolower($_SESSION['contextUser']);

//$debug = new logfile();

$totalRows = $_POST["totalRows"];
$year = $_POST["year"];
$month = $_POST["month"];
$day = $_POST["day"];
$startDate = $_POST["startStamp"];
$startStr = date("Y-m-d H:i:s",$startDate);

$endDate = strtotime(date("d M Y H:i:s",$startDate) . " +7 days");
$endStr = date("Y-m-d H:i:s",$endDate);

//$debug->write("startStr = \"$startStr\"");
//$debug->write("  endStr = \"$endStr\"\n");

//$debug->write("  totalRows = \"$totalRows\"\n");
//$debug->write("  totalRows = \"".$_POST["totalRows"]."\"\n");
//$debug->write("  existingRows = \"".$_POST["existingRows"]."\"\n");

//clear the tasks which start on this week
$queryString = "DELETE FROM $TIMES_TABLE " . 
					"WHERE username='$contextUser' AND " .
							"start_time >= '$startStr' AND ".
							"start_time < '$endStr'";

// to prevent deleteion when nothing is to be inserted
if (isset($_POST["totalRows"]) && $contextUser != "") {
	dbQuery($queryString);
	//$debug->write("   Query = \"$queryString\"\n");
}

//TODO: write meaningful todos ;)
for ($i=0; $i<$totalRows; $i++) {
	//$debug->write("i=$i ". $_POST["projectSelect_row".$i]."  ".$_POST["taskSelect_row".$i]."\n");
	$projectId = $_POST["projectSelect_row" . $i];
	if ($projectId < 1)
		continue;
	$taskId = $_POST["taskSelect_row" . $i];
	if ($taskId < 1)
		continue;
	$workDescription = '';
	if (array_key_exists("description_row" . $i, $_POST)) {
		// does not exist if simple timesheet layout = "no work description field"!
		$workDescription = mysql_real_escape_string($_POST["description_row" . $i]);
	}
	//$debug->write("proj=$projectId  task=$taskId  log=$workDescription\n");

	$curDaysTimestamp = $startDate;
	for ($j=1; $j<=7; $j++) {
		//get the timestamp for this day
		$nextDaysTimestamp = strtotime(date("d M Y H:i:s",$curDaysTimestamp) . " +1 days");

		//Create the starting timestamp string
		$stsStr = strftime("%Y-%m-%d %H:%M:%S", $curDaysTimestamp);

		//get the number of hours and minutes entered into the form
		$hours = $_POST["hours_row" . $i . "_col" . $j];
		$mins = $_POST["mins_row" . $i . "_col" . $j];

		//$debug->write("  $stsStr:  hours=$hours  mins=$mins \n");

		if ((!empty($hours) && $hours != 0) || (!empty($mins) && $mins != 0)) {

			//fix by Tyler Schacht
			if (empty($hours) || $hours == "") $hours = "00";

			//change hours & minutes into total number of minutes
			$minutes = $hours*60 + $mins;

			//calculate and set ending timestamp string
			$ets = strtotime(date("d M Y H:i:s",$curDaysTimestamp) . " +$minutes Minutes");
			$etsStr = strftime("%Y-%m-%d %H:%M:%S", $ets);
			
			//add to database
			$queryString = "INSERT INTO $TIMES_TABLE (username, start_time, end_time, duration, proj_id, task_id, log_message) ".
									"VALUES ('$contextUser','$stsStr', ".
									"'$etsStr', ".
									"'$minutes', ".
									"$projectId, $taskId, '$workDescription')";
			list($qh,$num) = dbQuery($queryString);
			//$debug->write("   Query = \"$queryString\"\n");
		}
		$curDaysTimestamp = $nextDaysTimestamp;
	}
}

$Location = "simple.php?year=$year&amp;month=$month&amp;day=$day";

Header("Location: $Location");
exit;
// vim:ai:ts=4:sw=4
?>
