<?php
// NOTE:  The session cache limiter and the excel stuff must appear before the session_start call,
//        or the export to excel won't work in IE
session_cache_limiter('public');

//export data to excel (or not) (IE is broken with respect to buttons, so we have to do it this way)
$export_excel=false;
if (isset($_GET["export_excel"]))
	if($_GET["export_excel"] == "1")
		$export_excel=true;

//Create the excel headers now, if needed
if($export_excel){
	header('Expires: 0');
	header('Cache-control: public');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Content-Description: File Transfer');
	header('Content-Type: application/vnd.ms-excel');
	header("Content-Disposition: attachment; filename=\"Timesheet_" . date("Y-m").".xls" . "\"");

	// When exporting data to excel, ensure the numbers written in the spreadsheet 
	// are in H.F format rather than HH:MI  
	$time_fmt = "decimal";
} else
	$time_fmt = "time";

// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn()) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]");
	exit;
}

// Connect to database.
$dbh = dbConnect();

//define the command menu & we get these variables from $_REQUEST:
//  $month $day $year $client_id $proj_id $task_id
include("timesheet_menu.inc");

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
if (isset($_REQUEST['print']))
	$print = true;
else
	$print = false;

//get the context date
$todayDate = mktime(0, 0, 0,$month, $day, $year);
$dateValues = getdate($todayDate);
$ymdStr = "&amp;year=".$dateValues["year"] . "&amp;month=".$dateValues["mon"] . "&amp;day=".$dateValues["mday"];

$startDate = mktime(0,0,0, $month, 1, $year);
$startStr = date("Y-m-d H:i:s",$startDate);

$endDate = getMonthlyEndDate($dateValues);
$endStr = date("Y-m-d H:i:s",$endDate);

$Location="$_SERVER[PHP_SELF]?&amp;uid=$uid$ymdStr";
//$post="&amp;uid=$uid";

$orderby="date";

function make_index($data,$order) {
	$index=$data["start_stamp"];
	return $index;
}
?>

<script type="text/javascript">
<!--
	//run init function on window load
	window.onload = init;

	function popupPrintWindow() {
		window.open("<?php echo "$Location&print=yes"; ?>", "PopupPrintWindow", "location=0,status=no,menubar=no,resizable=1,width=800,height=450");
	}

	 // Setup Javascript events for date drop-down lists. 

	//gets a DOM object by it's name
	function getObjectByName(sName){				
		if(document.getElementsByName){
			oElements = document.getElementsByName(sName);

			if(oElements.length > 0)
				return oElements[0];
			else
				return null;
		}
		else if(document.all)
			return document.all[sName][0];
		else if(document.layers)
			return document.layers[sName][0];
		else
			return null;
	}

	//apply auto-submit behaviour when changing date values
	function init(){
		var year  = getObjectByName('year');
		year.onchange   = function (){this.form.submit();};
	}
//-->
</script>

<html>
<head>
<title>Timesheet Report: User Hours</title>
<?php include ("header.inc"); ?>
</head>
<?php 
	if($print) {
		echo "<body width=\"100%\" height=\"100%\"";
		include ("body.inc");

		echo "onLoad=window.print();";
		echo ">\n";
	} else {
		echo "<body ";
		include ("body.inc");
		echo ">\n";
		include ("banner.inc");
	}
?>

<form action="report_hours.php" method="get">
<input type="hidden" name="month" value="<?php echo $month; ?>" />
<input type="hidden" name="day" value="<?php echo $day; ?>" />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php if(!$print) include("timesheet_face_part_1.inc"); ?>

			<table width="100%" border="0">
				<tr>
					<td align="left" nowrap width="35%">
						<table width="100%" height="100%" border="0" cellpadding="1" cellspacing="2">
							<tr>
								<td align="right" width="0" class="outer_table_heading">User:</td>
								<td align="left" width="100%">
									<?php user_select_droplist($uid, false); ?>
								</td>
							</tr>
						</table>
					</td>
					<td align="center" nowrap class="outer_table_heading" width="30%">
						<?php echo date('Y',$todayDate); ?>
					</td>
					<?php if(!$print): ?>
						<td  align="center" width="15%">
							<button onclick="popupPrintWindow()">Print Report</button>
						</td> 
						<td align="right" nowrap width="20%">select year:&nbsp;
							<?php
								year_button("year",$year);
							?>
						</td>
					<?php endif; ?>
				</tr>
			</table>

<!-- include the timesheet face up until the heading start section -->
<?php if(!$print) include("timesheet_face_part_2.inc"); ?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<tr>
			<td>
				<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table_body">
				<tr>
					<td class="calendar_cell_disabled_right">&nbsp</td>
<?php
	//Since we're iterating through the months anyway, we're going to collect the month date stamps in an array structure.
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {
		$currentMonDate = mktime(0,0,0,$currentMonth,1,$year);
		$monthStamps[]=$currentMonDate;
		$currentMonStr = strftime("%b", $currentMonDate);
		print "<td align=\"center\" class=\"calendar_cell_disabled_right\"><b>$currentMonStr</b></td>";
	}
	
	$monthStamps[]=mktime(0,0,0,1,1,$year+1); //also need the first stamp for next year...
?>
					<td class="calendar_cell_disabled_right">&nbsp</td>
				</tr>
				<tr><td class="calendar_cell_middle"><b>Hours in month</b></td>
<?php
	//Let the record show that although I vaguely understand what the holiday/allowances stuff is attempting to accomplish,
	//this is not my itch, so I don't quite understand how to scratch it.  I am just going to attempt to make sure the queries
	//are correct, and fix the logic which figures out how many hours a user has actually worked.  -SLM
	$hours = array("total" => array("working_hours" => 0));
	// Working hours
	$total = 0;
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {
		$last_day = get_last_day($currentMonth, $year);
		$hours[$currentMonth]["working_hours"] = 0;
		$others = count_absences_in_month($currentMonth, $year, '', 'Other'); //Other absences without user are general exceptions
		$hours[$currentMonth]["working_hours"] -= $others;
		$public = count_absences_in_month($currentMonth, $year, '', 'Public'); //Public holidays are without user
		$hours[$currentMonth]["working_hours"] -= $public;
		for ($currentDay=1;$currentDay<=$last_day;$currentDay++) {
			$currentDate = mktime(0,0,0,$currentMonth,$currentDay,$year);
			if ((date('w', $currentDate) != 6)&&(date('w', $currentDate) != 0)) {
				$hours[$currentMonth]["working_hours"] += WORK_DAY;
			}
		}
		$hourstr = format_hours_minutes($hours[$currentMonth]["working_hours"]*SECONDS_PER_HOUR);
		print "<td align=\"right\" class=\"calendar_cell_middle\">$hourstr</td>";
		$hours["total"]["working_hours"] += $hours[$currentMonth]["working_hours"];
	}
	$totalstr = format_hours_minutes($hours["total"]["working_hours"]*SECONDS_PER_HOUR);
	print "<td align=\"right\" class=\"calendar_cell_disabled_right\"><b>$totalstr</b></td>";
	print "</tr>";

	// Worked hours & find weekend and holiday hours worked
	print "<tr><td class=\"calendar_cell_middle\"><b>Total attendance</b></td>";
	$hours["total"]["attendance"] = 0;
	$darray=array();
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {
		$hours[$currentMonth]["weekend"] = 0;
		$startStamp = $monthStamps[$currentMonth-1];
		$startStr = date("Y-m-d H:i:s",$monthStamps[$currentMonth-1]);
		$endStamp = $monthStamps[$currentMonth];
		$endStr = date("Y-m-d H:i:s",$monthStamps[$currentMonth]);
		list($num, $qh) = get_time_records($startStr, $endStr, $uid, $proj_id, $client_id);
		list($qhol, $holnum) = get_holidays($currentMonth, $year);
		$hndx=0;
		if($hndx < $holnum) {
			$holdata = dbResult($qhol, $hndx);
		}
		$total_minutes = 0;
		while ($data = dbResult($qh)) {
			//if entry doesn't have an end time or duration, it's an incomplete entry
			//fixStartEndDuration returns a 0 if the entry is incomplete.
			if(!fixStartEndDuration($data)) continue;

			split_data_into_discrete_days($data,$orderby,$darray,0);
		}

		ksort($darray);
		unset($data);

		foreach($darray as $dary){
			foreach($dary as $data){
				//need to make sure date is in range of what we want...
				if($data["start_stamp"] < $startStamp) continue;
				if($data["start_stamp"] >= $endStamp) continue;

				$total_minutes+=$data["duration"];
				$dow=date('w',$data["start_stamp"]);
				if($dow==0 || $dow==6) {
					$hours[$currentMonth]["weekend"] += $data["duration"];
				} else if($hndx < $holnum) {
					$dti = getdate($start_stamp);
					$curDayStamp=mktime(0,0,0,$dti["mon"],$dti["mday"],$dti["year"]);
					while(($holdata["date"]<$curDayStamp) && ($hndx<$holnum)) {
						$hndx++;
						$holdata = dbResult($qhol, $hndx);
					}
					if($holdata["date"]==$curDayStamp) {
						$hours[$currentMonth]["weekend"] += $data["duration"];
					}
				}
			}
		}
	
		$hours[$currentMonth]["attendance"] = $total_minutes;
		$hourstr = format_minutes($total_minutes);
		print "<td align=\"right\" class=\"calendar_cell_middle\">$hourstr</td>";
		$hours["total"]["attendance"] += $total_minutes;
	}
	$totalstr = format_minutes($hours["total"]["attendance"]);
	print "<td align=\"right\" class=\"calendar_cell_disabled_right\"><b>$totalstr</b></td>";
	print "</tr>";

	// Print Weekend worked hours
	$hours["total"]["weekend"] = 0;
	print "<tr><td class=\"calendar_cell_middle\"><b>Weekend attendance</b></td>";
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {

		$wkendMinutes = $hours[$currentMonth]["weekend"];
		$hours["total"]["weekend"] += $wkendMinutes;
		$hourstr = format_minutes($wkendMinutes);
		print "<td align=\"right\" class=\"calendar_cell_middle\">$hourstr</td>";
	}
	$totalstr = format_minutes($hours["total"]["weekend"]);
	print "<td align=\"right\" class=\"calendar_cell_disabled_right\"><b>$totalstr</b></td>";
	print "</tr>";

	// Compensation taken
	print "<tr><td class=\"calendar_cell_middle\"><b>Compensation taken</b></td>";
	$hours["total"]["compensation"] = 0;
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {
		$holidays = count_absences_in_month($currentMonth, $year, $uid, 'Compensation');
		$hours[$currentMonth]["compensation"] = $holidays;
		$hourstr = format_hours_minutes($hours[$currentMonth]["compensation"]*SECONDS_PER_HOUR);
		print "<td align=\"right\" class=\"calendar_cell_middle\">$hourstr</td>";
		$hours["total"]["compensation"] += $hours[$currentMonth]["compensation"];
	}
	$totalstr = format_hours_minutes($hours["total"]["compensation"]*SECONDS_PER_HOUR);
	print "<td align=\"right\" class=\"calendar_cell_disabled_right\"><b>$totalstr</b></td>";
	print "</tr>";

	// Training taken
	print "<tr><td class=\"calendar_cell_middle\"><b>Training taken</b></td>";
	$hours["total"]["training"] = 0;
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {
		$holidays = count_absences_in_month($currentMonth, $year, $uid, 'Training');
		$hours[$currentMonth]["training"] = $holidays;
		$hourstr = format_hours_minutes($hours[$currentMonth]["training"]*SECONDS_PER_HOUR);
		print "<td align=\"right\" class=\"calendar_cell_middle\">$hourstr</td>";
		$hours["total"]["training"] += $hours[$currentMonth]["training"];
	}
	$totalstr = format_hours_minutes($hours["total"]["training"]*SECONDS_PER_HOUR);
	print "<td align=\"right\" class=\"calendar_cell_disabled_right\"><b>$totalstr</b></td>";
	print "</tr>";

	// Sick time
	print "<tr><td class=\"calendar_cell_middle\"><b>Sick</b></td>";
	$hours["total"]["sick"] = 0;
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {
		$holidays = count_absences_in_month($currentMonth, $year, $uid, 'Sick');
		$hours[$currentMonth]["sick"] = $holidays;
		$hourstr = format_hours_minutes($hours[$currentMonth]["sick"]*SECONDS_PER_HOUR);
		print "<td align=\"right\" class=\"calendar_cell_middle\">$hourstr</td>";
		$hours["total"]["sick"] += $hours[$currentMonth]["sick"];
	}
	$totalstr = format_hours_minutes($hours["total"]["sick"]*SECONDS_PER_HOUR);
	print "<td align=\"right\" class=\"calendar_cell_disabled_right\"><b>$totalstr</b></td>";
	print "</tr>";

	// Military Service
	print "<tr><td class=\"calendar_cell_middle\"><b>Military Service</b></td>";
	$hours["total"]["military"] = 0;
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {
		$holidays = count_absences_in_month($currentMonth, $year, $uid, 'Military');
		$hours[$currentMonth]["military"] = $holidays;
		$hourstr = format_hours_minutes($hours[$currentMonth]["military"]*SECONDS_PER_HOUR);
		print "<td align=\"right\" class=\"calendar_cell_middle\">$hourstr</td>";
		$hours["total"]["military"] += $hours[$currentMonth]["military"];
	}
	$totalstr = format_hours_minutes($hours["total"]["military"]*SECONDS_PER_HOUR);
	print "<td align=\"right\" class=\"calendar_cell_disabled_right\"><b>$totalstr</b></td>";
	print "</tr>";

	// Other absences
	print "<tr><td class=\"calendar_cell_middle\"><b>Other Absences</b></td>";
	$hours["total"]["other"] = 0;
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {
		$holidays = count_absences_in_month($currentMonth, $year, $uid, 'Other');
		$hours[$currentMonth]["other"] = $holidays;
		$hourstr = format_hours_minutes($hours[$currentMonth]["other"]*SECONDS_PER_HOUR);
		print "<td align=\"right\" class=\"calendar_cell_middle\">$hourstr</td>";
		$hours["total"]["other"] += $hours[$currentMonth]["other"];
	}
	$totalstr = format_hours_minutes($hours["total"]["other"]*SECONDS_PER_HOUR);
	print "<td align=\"right\" class=\"calendar_cell_disabled_right\"><b>$totalstr</b></td>";
	print "</tr>";

	// Holiday taken
	print "<tr><td class=\"calendar_cell_middle\"><b>Holiday taken</b></td>";
	$hours["total"]["holiday"] = 0;
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {
		$holidays = count_absences_in_month($currentMonth, $year, $uid);
		$hours[$currentMonth]["holiday"] = $holidays;
		$hourstr = format_hours_minutes($hours[$currentMonth]["holiday"]*SECONDS_PER_HOUR);
		print "<td align=\"right\" class=\"calendar_cell_middle\">$hourstr</td>";
		$hours["total"]["holiday"] += $hours[$currentMonth]["holiday"];
	}
	$totalstr = format_hours_minutes($hours["total"]["holiday"]*SECONDS_PER_HOUR);
	print "<td align=\"right\" class=\"calendar_cell_disabled_right\"><b>$totalstr</b></td>";
	print "</tr>";

	// Holiday remaining
	print "<tr><td class=\"calendar_cell_middle\"><b>Holiday remaining</b></td>";
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {
		$holidays = get_balance(get_last_day($currentMonth, $year), $currentMonth, $year, $uid);
		$holiday_remaining = format_hours_minutes($holidays*SECONDS_PER_HOUR);
		print "<td align=\"right\" class=\"calendar_cell_middle\">$holiday_remaining</td>";
	}
	print "<td align=\"right\" class=\"calendar_cell_disabled_right\"><b>$holiday_remaining</b></td>";
	print "</tr>";

	// I don't understand this, and it returns large negative numbers for me. Very likely because I don't have 
	// data populated in the 'allowances' table, and don't know how to populate it correctly.  -SLM
	// glidetime remaining
	print "<tr><td class=\"calendar_cell_middle\"><b>Glidetime</b></td>";
	for ($currentMonth=1;$currentMonth<=12;$currentMonth++) {
		$remaining = get_balance(get_last_day($currentMonth, $year), $currentMonth, $year, $uid, 'glidetime');
		$glidetime = format_hours_minutes($remaining*SECONDS_PER_HOUR);
		print "<td align=\"right\" class=\"calendar_cell_middle\">$glidetime</td>";
	}
	print "<td align=\"right\" class=\"calendar_cell_disabled_right\"><b>$glidetime</b></td>";
	print "</tr>";

?>
				</table>
			</td>
		</tr>
	</table>

<!-- include the timesheet face up until the end -->
<?php if(!$print) include("timesheet_face_part_3.inc"); ?>

		</td>
	</tr>
</table>
</form>
<?php
if(!$print) include ("footer.inc");
?>
</body>
</HTML>
<?php
// vim:ai:ts=4:sw=4
?>
