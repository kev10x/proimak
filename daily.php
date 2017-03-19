<?php
// $Header: /cvsroot/tsheet/timesheet.php/daily.php,v 1.7 2005/05/10 11:42:53 vexil Exp $

// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclDaily')) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclDaily'));
	exit;
}

// Connect to database.
$dbh = dbConnect();

//define the command menu & we get these variables from $_REQUEST:
//  $month $day $year $client_id $proj_id $task_id
include("timesheet_menu.inc");

$contextUser = strtolower($_SESSION['contextUser']);

if (empty($contextUser))
	errorPage("Could not determine the context user");

//check that project id is valid
if ($proj_id == 0)
	$task_id = 0;

$startDayOfWeek = $tsx_config->get('weekstartday');  //needed by NavCalendar
$todayDate = mktime(0, 0, 0,$month, $day, $year);

$tomorrowDate = strtotime(date("d M Y H:i:s",$todayDate) . " +1 days");

//get the timeformat
$CfgTimeFormat = $tsx_config->get('timeformat');

//include date input classes
include "form_input.inc";

?>
<html>
<head>
<title>Update timesheet for <?php echo $contextUser; ?></title>
<?php
include("header.inc");
include("client_proj_task_javascript.inc");
?>
<script type="text/javascript">

	function delete_entry(transNum) {
		if (confirm('Are you sure you want to delete this time entry?'))
			location.href = 'delete.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>&day=<?php echo $day; ?>&client_id=<?php echo $client_id; ?>&proj_id=<?php echo $proj_id; ?>&task_id=<?php echo $task_id; ?>&trans_num=' + transNum;
	}

</script>
</head>
<body <?php include ("body.inc"); ?> onload="doOnLoad();">
<?php
	include ("banner.inc");

	$currentDate = $todayDate;
	$fromPopup = "false";
	include("navcal/navcal+clockOnOff.inc"); 
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">
<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>
				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap class="outer_table_heading" nowrap>
							Daily Timesheet
						</td>
						<td align="left" nowrap class="outer_table_heading">
							<?php echo strftime("%A %B %d, %Y", $todayDate); ?>
						</td>
						<td align="right" nowrap>
						<!-- prev / next links were here -->
						</td>
					</tr>
				</table>

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_2.inc"); ?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<tr>
			<td>
				<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table_body">
					<tr class="inner_table_head">
						<td class="inner_table_column_heading" align="center">Client</td>
						<td class="inner_table_column_heading" align="center">Project</td>
						<td class="inner_table_column_heading" align="center">Task</td>
						<td class="inner_table_column_heading" align="center" width="10%">Start</td>
						<td class="inner_table_column_heading" align="center" width="10%">End</td>
						<td class="inner_table_column_heading" align="center" width="10%">Total</td>
						<td class="inner_table_column_heading" align="center" width="15%"><i>Actions</i></td>
					</tr>
<?php

function make_daily_link($ymdStr, $proj_id, $string) {
	echo "<a href=\"daily.php?" .  $ymdStr .  "&proj_id=$proj_id\"><i>" . 
		$string .  "</i></a>"; 
}

function open_cell_middle_td() {
	echo "<td class=\"calendar_cell_middle\" align=\"right\" nowrap>";
}

//Get the data
$startStr = date("Y-m-d H:i:s",$todayDate);
$endStr = date("Y-m-d H:i:s",$tomorrowDate);

$order_by_str = "start_stamp, $CLIENT_TABLE.organisation, $PROJECT_TABLE.title, $TASK_TABLE.name, end_stamp";
list($num, $qh) = get_time_records($startStr, $endStr, $contextUser, 0, 0, $order_by_str);

if ($num == 0) {
	print "	<tr>\n";
	print "		<td class=\"calendar_cell_middle\"><i>No hours recorded.</i></td>\n";
	print "		<td class=\"calendar_cell_middle\">&nbsp;</td>\n";
	print "		<td class=\"calendar_cell_middle\">&nbsp;</td>\n";
	print "		<td class=\"calendar_cell_middle\" width=\"10%\">&nbsp;</td>\n";
	print "		<td class=\"calendar_cell_middle\" width=\"10%\">&nbsp;</td>\n";
	print "		<td class=\"calendar_cell_middle\" width=\"10%\">&nbsp;</td>\n";
	print "		<td class=\"calendar_cell_disabled_right\" width=\"15%\">&nbsp;</td>\n";
	print "	</tr>\n";
	print "</table>\n";
}
else {
	$last_task_id = -1;
	$taskTotal = 0;
	$todaysTotal = 0;

	$count = 0;
	while ($data = dbResult($qh)) {
		//There are several potential problems with the date/time data comming from the database
		//because this application hasn't taken care to cast the time data into a consistent TZ.
		//See: http://jokke.dk/blog/2007/07/timezones_in_mysql_and_php & read comments
		//So, we handle it as best we can for now...
		fixStartEndDuration($data);

		$dateValues = getdate($data["start_stamp"]);
		$ymdStrSd = "&amp;year=".$dateValues["year"] . "&amp;month=".$dateValues["mon"] . "&amp;day=".$dateValues["mday"];
		$dateValues = getdate($data["end_stamp"]);
		$ymdStrEd = "&amp;year=".$dateValues["year"] . "&amp;month=".$dateValues["mon"] . "&amp;day=".$dateValues["mday"];

		//get the project title and task name
		$projectTitle = stripslashes($data["projectTitle"]);
		$taskName = stripslashes($data["taskName"]);
		$clientName = stripslashes($data["clientName"]);

		//start printing details of the task
		if (($count % 2) == 1)
			print "<tr class=\"diff\">\n";
		else
			print "<tr>\n";

		print "<td class=\"calendar_cell_middle\"><a href=\"javascript:void(0)\" onclick=\"javascript:window.open('client_info.php?client_id=$data[client_id]','Client Info','location=0,directories=no,status=no,scrollbar=yes,menubar=no,resizable=1,width=500,height=200')\">$clientName</a></td>\n";
		print "<td class=\"calendar_cell_middle\"><a href=\"javascript:void(0)\" onclick=\"javascript:window.open('proj_info.php?proj_id=$data[proj_id]','Project Info','location=0,directories=no,status=no,scrollbar=yes,menubar=no,resizable=1,width=500,height=200')\">$projectTitle</a></td>\n";
		print "<td class=\"calendar_cell_middle\"><a href=\"javascript:void(0)\" onclick=\"javascript:window.open('task_info.php?task_id=$data[task_id]','Task Info','location=0,directories=no,status=no,scrollbar=yes,menubar=no,resizable=1,width=300,height=150')\">$taskName</a></td>\n";

		if ($data["duration"] > 0) {
			//format printable times
			if ($CfgTimeFormat == "12") {
				$formattedStartTime = date("g:iA",$data["start_stamp"]);
				$formattedEndTime = date("g:iA",$data["end_stamp"]);
			} else {
				$formattedStartTime = date("G:i",$data["start_stamp"]);
				$formattedEndTime = date("G:i",$data["end_stamp"]);
			}

			//if both start and end time are not today
			if ($data["start_stamp"] < $todayDate && $data["end_stamp"] > $tomorrowDate) {
				//all day - no one should work this hard!
				$taskTotal += get_duration($todayDate, $tomorrowDate);  

				open_cell_middle_td(); //<td....>
				echo "<font color=\"#909090\"><i>" . $formattedStartTime . ",";
				make_daily_link($ymdStrSd,$proj_id,date("d-M",$data["start_stamp"])); 
				echo "</i></font></td>" ;

				open_cell_middle_td(); //<td....>
				echo "<font color=\"#909090\"><i>" . $formattedEndTime . ",";
				make_daily_link($ymdStrEd,$proj_id,date("d-M",$data["end_stamp"])); 
				echo "</i></font></td>" ;

				open_cell_middle_td(); //<td....>
				echo formatMinutes($taskTotal). "<font color=\"#909090\"><i> of " .
					formatMinutes($data["duration"]) . "</i></font></td>\n";
			} //if end time is not today
			  elseif ($data["end_stamp"] > $tomorrowDate) {
				$taskTotal = get_duration($data["start_stamp"],$tomorrowDate);

				open_cell_middle_td(); //<td....>
				echo $formattedStartTime . "</td>" ;

				open_cell_middle_td(); //<td....>
				echo "<font color=\"#909090\"><i>" . $formattedEndTime . "," ;
				make_daily_link($ymdStrEd,$proj_id,date("d-M",$data["end_stamp"])); 
				echo "</i></font></td>" ;

				open_cell_middle_td(); //<td....>
				echo  formatMinutes($taskTotal). "<font color=\"#909090\"><i> of " . formatMinutes($data["duration"]) . "</i></font></td>\n";
			} //elseif start time is not today
			  elseif ($data["start_stamp"] < $todayDate) {
				$taskTotal = get_duration($todayDate,$data["end_stamp"]);

				open_cell_middle_td(); //<td....>
				echo "<font color=\"#909090\"><i>" . $formattedStartTime . "," ;
				make_daily_link($ymdStrSd,$proj_id,date("d-M",$data["start_stamp"])); 
				echo "</i></font></td>"; 

				open_cell_middle_td(); //<td....>
				echo $formattedEndTime . "</td>" ;

				open_cell_middle_td(); //<td....>
				echo formatMinutes($taskTotal). "<font color=\"#909090\"><i> of " .
					formatMinutes($data["duration"]) . "</i></font></td>\n";
			} else {
				$taskTotal = $data["duration"];
				open_cell_middle_td(); //<td....>
				print "$formattedStartTime</td>\n";
				open_cell_middle_td(); //<td....>
				print "$formattedEndTime</td>\n";
				open_cell_middle_td(); //<td....>
				print formatMinutes($data["duration"]) . "</td>\n";
			}

			print "<td class=\"calendar_cell_disabled_right\" align=\"right\" nowrap>\n";
			print "	<a href=\"edit.php?client_id=$client_id&proj_id=$proj_id&task_id=$task_id&trans_num=$data[trans_num]&year=$year&month=$month&day=$day\" class=\"action_link\">Edit</a>,&nbsp;\n";
			//print "	<a href=\"delete.php?client_id=$client_id&proj_id=$proj_id&task_id=$task_id&trans_num=$data[trans_num]\" class=\"action_link\">Delete</a>\n";
			print "	<a href=\"javascript:delete_entry($data[trans_num]);\" class=\"action_link\">Delete</a>\n";
			print "</td>";

			//add to todays total
			$todaysTotal += $taskTotal;
		} else {
			if ($CfgTimeFormat == "12") 
				$formattedStartTime = date("g:iA",$data["start_stamp"]);
			else
				$formattedStartTime = date("G:i",$data["start_stamp"]);
			
			open_cell_middle_td(); //<td....>
			print "$formattedStartTime</td>\n";
			open_cell_middle_td(); //<td....>
			print "&nbsp;</td>\n";
			open_cell_middle_td(); //<td....>
			print "&nbsp;</td>\n";
			print "<td class=\"calendar_cell_disabled_right\" align=\"right\" nowrap>\n";
			/**
			 * Update by robsearles 26 Jan 2008
			 * Added a "Clock Off" link to make it easier to stop timing a task
			 * $realTodayDate is defined in common.inc
			 */
			if ($data["start_stamp"] == $realTodayDate) {
				$stop_link = '<a href="clock_action.php?client_id='.$data['client_id'].'&amp;proj_id='.
						$data['proj_id'].'&amp;task_id='.$data['task_id'].
						'&amp;clock_off_check=on&amp;clock_off_radio=now" class="action_link\">Clock Off</a>, ';
				print $stop_link;
			}
			print "	<a href=\"javascript:delete_entry($data[trans_num]);\" class=\"action_link\">Delete</a>\n";
			print "</td>";
		}

		print "</tr>";
		$count++;
	}
	print "<tr>\n";
	print "	<td class=\"calendar_totals_line_weekly_right\" colspan=\"6\" align=\"right\">";
	print " Daily Total: <span class=\"calendar_total_value_weekly\" nowrap>" . formatMinutes($todaysTotal) . "</span></td>\n";
	print "	<td class=\"calendar_cell_disabled_right\" align=\"right\" nowrap>&nbsp;</td>\n";
	print "</tr>\n";
	print "</table>";
}
?>

			</td>
		</tr>
	</table>


<!-- include the timesheet face up until the end -->
<?php include("timesheet_face_part_3.inc"); ?>

		</td>
	</tr>
</table>

<!-- ?php include("clockOnOff.inc"); ?-->

<?php
include ("footer.inc");
?>
</body>
</HTML>
<?php
// vim:ai:ts=4:sw=4
?>
