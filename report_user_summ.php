<?php
// NOTE:  The session cache limiter and the excel stuff must appear before the session_start call,
//        or the export to excel won't work in IE
session_cache_limiter('public');

//export data to excel (or not)  (ie is broken with respect to buttons, so we have to do it this way)
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
}

// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
//require("debuglog.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclReports')) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclReports'));
	exit;
}

// Connect to database.
$dbh = dbConnect();

//define the command menu & we get these variables from $_REQUEST:
//  $month $day $year $client_id $proj_id $task_id
include("timesheet_menu.inc");

$contextUser = strtolower($_SESSION['contextUser']);

//load local vars from superglobals
if (isset($_REQUEST['uid']))
	$uid = $_REQUEST['uid'];
else
	$uid = $contextUser;
	//get the first user from the database
	//$uid = getFirstUser();

if (isset($_REQUEST['time_fmt']))
	$time_fmt = $_REQUEST['time_fmt'];
else
	$time_fmt = "decimal";

// If exporting data to excel, ensure the numbers written in the spreadsheet 
// are in H.F format rather than HH:MI  
if($export_excel)
	$time_fmt = "decimal";

if (isset($_REQUEST['print']))
	$print = true;
else
	$print = false;

//What bi-monthly period is the context date in?
if($day < 16) {
	$sday=1; 
	$eday=15;
} else {
	$sday=16;
	$eday=date('t',strtotime("$year-$month-15"));
}

//get start and end dates for the calendars
$start_day   = isset($_GET['start_day'])   && $_GET['start_day']   ? (int)$_GET['start_day']   : $sday;
$start_month = isset($_GET['start_month']) && $_GET['start_month'] ? (int)$_GET['start_month'] : $month;
$start_year  = isset($_GET['start_year'])  && $_GET['start_year']  ? (int)$_GET['start_year']  : $year;

$end_day     = isset($_GET['end_day'])     && $_GET['start_day']   ? (int)$_GET['end_day']     : $eday;
//$end_month   = isset($_GET['end_month'])   && $_GET['start_month'] ? (int)$_GET['end_month']   : $month;
//$end_year    = isset($_GET['end_year'])    && $_GET['start_year']  ? (int)$_GET['end_year']    : $year;
//Since we're only allowing the user to choose a start and end day within the cur. context month 
//we can do this:  But note: we can't remove the variables altogether, or we'd need yet another set
//of functions to create the navcal calendars, and at some point we may want to allow more
//freestyle date choosing...
$end_month   = $start_month;
$end_year    = $start_year;

$start_time = strtotime($start_year . '/' . $start_month . '/' . $start_day);
$end_time   = strtotime($end_year   . '/' . $end_month   . '/' . $end_day);
$end_time2   = strtotime("+1 day",$end_time);  //need last day to be inclusive...

$startStr = date("Y-m-d H:i:s",$start_time);
$endStr = date("Y-m-d H:i:s",$end_time2);

$orderby="project";
function make_index($data,$order) {
	$index=sprintf("%05d-%05d-%05d",$data["client_id"], $data["proj_id"], $data["task_id"]);
	$index.="-".$data["start_stamp"];
	return $index;
}

function format_time($time) {
	global $time_fmt;
	if($time > 0) {
		if($time_fmt == "decimal")
			return minutes_to_hours($time);
		else 
			return format_minutes($time);
	} else 
		return "-";
}

$Location="$_SERVER[PHP_SELF]?uid=$uid&amp;time_fmt=$time_fmt&amp;start_year=$start_year&amp;start_month=$start_month&amp;start_day=$start_day&amp;end_year=$end_year&amp;end_month=$end_month&amp;end_day=$end_day";
$post="uid=$uid&amp;time_fmt=$time_fmt";

if(!$export_excel) {
	require("report_javascript.inc");
?>
<script type="text/javascript">
<!--
/*
* Setup Javascript events for date drop-down lists. Again, this should be included in
* an external js file, but for now I want this report to be self-contained
*/

//run init function on window load
window.onload = init;

//apply auto-submit behaviour when changing date values
function init(){
	var start_day   = getObjectByName('start_day');
	//var start_month = getObjectByName('start_month');
	//var start_year  = getObjectByName('start_year');
	var end_day     = getObjectByName('end_day');
	//var end_month   = getObjectByName('end_month');
	//var end_year    = getObjectByName('end_year');

	start_day.onchange   = function (){this.form.submit();};
	//start_month.onchange = function (){this.form.submit();};
	//start_year.onchange  = function (){this.form.submit();};
	end_day.onchange     = function (){this.form.submit();};
	//end_month.onchange   = function (){this.form.submit();};
	//end_year.onchange    = function (){this.form.submit();};
}
//-->
</script>
<?php } //end if !export_excel ?>

<html>
<head>
<title>User Summary Report</title>
<?php 
	if(!$export_excel) include ("header.inc");
	else {
		print "<style type=\"text/css\"> ";
		include ("css/timesheet.css");
		print "</style>";
	}
?>
</head>
<?php
	if($print) {
		echo "<body width=\"100%\" height=\"100%\"";
		include ("body.inc");
			echo "onLoad=window.print();";
		echo ">\n";
	} else if($export_excel) {
		echo "<body ";
		include ("body.inc");
		echo ">\n";
	} else {
		echo "<body ";
		include ("body.inc");
		echo ">\n";
		echo "<div id=\"header\">";
		include ("banner.inc");
		$motd = 0;  //don't want the motd printed
		include("navcal/navcal_monthly_with_end_dates.inc");
		echo "</div>";
	}
?>

<?php if(!$export_excel) { ?>
<form action="<?php print $_SERVER['PHP_SELF'] ?>" method="get">
<input type="hidden" name="start_month" value="<?php echo $start_month; ?>" />
<input type="hidden" name="start_year" value="<?php echo $start_year; ?>" />
<input type="hidden" name="end_month" value="<?php echo $end_month; ?>" />
<input type="hidden" name="end_year" value="<?php echo $end_year; ?>" />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php if(!$print) include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap>
							<b>User:</b>&nbsp;
							<?php user_select_droplist($uid, false); ?>
						</td>
						<td align="center" nowrap class="outer_table_heading">
						<?php
							echo date("F", $start_time)." ";
							day_button("start_day",$start_time);
							echo "&nbsp;&nbsp;to&nbsp;&nbsp;";
							day_button("end_day",$end_time);
							echo ", $end_year";
						?> 
						</td>
						<?php if (!$print): ?>
							<td align="right" width="10%" nowrap>
								<input type="radio" name="time_fmt" value="decimal" onclick="this.form.submit()"
									<?php if($time_fmt == "decimal") print " checked=\"checked\""; ?> /> Hrs.dec&nbsp;<br />
								<input type="radio" name="time_fmt" value="hrsMins" onclick="this.form.submit()"
									<?php if($time_fmt != "decimal") print " checked=\"checked\""; ?> /> Hrs:Min&nbsp;
							</td>
							<td align="right" nowrap>
							<?php
								$p1post="uid=$uid&amp;time_fmt=$time_fmt&amp;start_year=$start_year&amp;start_month=$start_month&amp;start_day=1&amp;end_year=$end_year&amp;end_month=$end_month&amp;end_day=15";
								$p2post="uid=$uid&amp;time_fmt=$time_fmt&amp;start_year=$start_year&amp;start_month=$start_month&amp;start_day=16&amp;end_year=$end_year&amp;end_month=$end_month&amp;end_day=".date('t',strtotime("$end_year-$end_month-15"));
							?>
								<a href="<?PHP print $_SERVER['PHP_SELF']."?".$p1post; ?>" class="outer_table_action">Bi-monthly period 1</a><br />
								<a href="<?PHP print $_SERVER['PHP_SELF']."?".$p2post; ?>" class="outer_table_action">Bi-monthly period 2</a>
							</td>
							<td  align="right" width="15%" nowrap >
								<button name="export_excel" onclick="reload2Export(this.form)"><img src="images/icon_xport-2-excel.gif" alt="Export to Excel" align="absmiddle" /></button> &nbsp;
								<button onclick="popupPrintWindow()"><img src="images/icon_printer.gif" alt="Print Report" align="absmiddle" /></button>
							</td>
						<?php endif; ?>
					</tr>
				</table>

<!-- include the timesheet face up until the heading start section -->
<?php if(!$print) include("timesheet_face_part_2.inc"); ?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<tr>
			<td>

<?php } // end if !export_excel
else {  //create Excel header
	list($fn,$ln) = get_users_name($uid);
	echo "<h4>Report for $ln, $fn<br />";
	echo date("F d", $start_time)."  to  ";
	echo date("d", $end_time);
	echo ", $end_year";
	echo "</h4>";
}
?>
				<table width="100%" border="0" cellpadding="4" cellspacing="0" class="table_body">
<?php

// ==============================================================================================
// Fetch data records
list($num, $qh) = get_time_records($startStr, $endStr, $uid, 0, $client_id);

if ($num == 0) {
	print "	<tr>\n";
	print "		<td align=\"center\">\n";
	print "			<i><br />No hours recorded.<br /><br /></i>\n";
	print "		</td>\n";
	print "	</tr>\n";
} else {
	$darray = array();
	while ($data = dbResult($qh)) {
		//if entry doesn't have an end time or duration, it's an incomplete entry
		//fixStartEndDuration returns a 0 if the entry is incomplete.
		if(!fixStartEndDuration($data)) continue;

		//Since we're allowing entries that may span date boundaries, this complicates
		//our life quite a lot.  We need to "pre-process" the results to split those
		//entries that do span date boundaries into multiple entries that stop and then
		//re-start on date boundaries.
		//NOTE: there must be a make_index() function defined in this file for the following function to, well, function
		split_data_into_discrete_days($data,$orderby,$darray,0);
	}

	ksort($darray);
	unset($data);

?>
					<!-- Table headers -->
					<tr>
						<td class="calendar_cell_disabled_right">Client&nbsp;/ Project&nbsp;/ Task</td>
						<!--td class="calendar_cell_disabled_right">Project</td>
						<td class="calendar_cell_disabled_right">Task</td-->
<?php 
	$daytotals=array();
	for ($mm = $start_day; $mm <= $end_day; $mm++) { 
		$dayStamp = mktime(0,0,0,$start_month,$mm,$start_year);
		$stamp_to_day_array[$dayStamp]=$mm;
		$daytotals[$mm]=0;

?>
						<td class="calendar_cell_disabled_right" align="right" width="50">
							<?php echo $start_month; ?>/<?php echo $mm; ?>
						</td>
<?php } //end for ?>
						<td class="calendar_cell_disabled_right" align="right">
							Totals
						</td>
					</tr>
	<?php
		$cptarray=array();
		foreach($darray as $dary){
			foreach($dary as $data){
				//need to make sure date is in range of what we want...
				if($data["start_stamp"] < $start_time) continue;
				if($data["start_stamp"] >= $end_time2) continue;

				$cptndx = $data["clientName"]."&nbsp;/ ".$data["projectTitle"]."&nbsp;/ ".$data["taskName"];
				if(!array_key_exists($cptndx,$cptarray)) {
					for ($mm = $start_day; $mm <= $end_day; $mm++) {
						$cptarray[$cptndx][$mm]=0;
					}
				}
				$dndx = $stamp_to_day_array[$data["start_stamp"]];
				$cptarray[$cptndx][$dndx]+=$data["duration"];
				$daytotals[$dndx]+=$data["duration"];
			}
		}

		$grandtotal=0;
		foreach($cptarray as $cptname => $cptary){
	?>
					<tr>
						<td class="calendar_cell_right">
							<?php print $cptname; ?>
						</td>
		<?php
			$tasktotal=0;
			foreach($cptary as $day => $duration) {
				$tasktotal+=$duration;
				$grandtotal+=$duration;
				$time = format_time($duration);
		?>
						<td class="calendar_cell_right" align="right" width="50">
							<?php echo $time; ?>
						</td>
		<?php } //end foreach $cptary ?>
						<td class="calendar_cell_right" align="right" style="font-weight: bold;">
							<?php print format_time($tasktotal); ?>
						</td></tr>
	<?php } //end foreach $cptarray ?>

					<tr>
						<td class="calendar_cell_right" style="font-weight: bold;  border-bottom: 0px;">
							Totals
						</td>
	<?php
		for ($mm = $start_day; $mm <= $end_day; $mm++) {
			$time = format_time($daytotals[$mm]);
	?>
						<td class="calendar_cell_right" align="right" style="font-weight: bold; border-bottom: 0px;">
							<?php echo $time; ?>
						</td>
	<?php }?>
						<td class="calendar_cell_right" align="right" style="font-weight: bold; border-bottom: 0px;">
							<?php print format_time($grandtotal); ?>
						</td>
					</tr>
<?php } //end if $num==0 ?>
				</table>
			</td>
		</tr>
	</table>

<?php if(!$export_excel) { ?>
<!-- include the timesheet face up until the end -->
<?php if(!$print) include("timesheet_face_part_3.inc"); ?>

		</td>
	</tr>
</table>

</form>
<?php if (!$print) {
		echo "<div id=\"footer\">"; 
		include ("footer.inc"); 
		echo "</div>";
	}
} //end if !export_excel 
?>
</body>
</HTML>
<?php
// vim:ai:ts=4:sw=4
?>
