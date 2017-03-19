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

	$time_fmt = "decimal";
} else
	$time_fmt = "time";

// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");

if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclReports')) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclReports'));
	exit;
}

// Connect to database.
$dbh = dbConnect();

include("timesheet_menu.inc");                //define the command menu

//default client
if ($client_id == 0)
	//get the first project
	$client_id = getFirstClient();

//load local vars from superglobals
if (isset($_REQUEST['uid']))
	$uid = $_REQUEST['uid'];
else
	$uid = strtolower($_SESSION['contextUser']);

if (isset($_REQUEST['print']))
	$print = true;
else
	$print = false;

//get start and end dates for the calendars
$start_day   = isset($_GET['start_day'])   && $_GET['start_day']   ? (int)$_GET['start_day']   : 1;
$start_month = isset($_GET['start_month']) && $_GET['start_month'] ? (int)$_GET['start_month'] : $month;
$start_year  = isset($_GET['start_year'])  && $_GET['start_year']  ? (int)$_GET['start_year']  : $year;

$end_day     = isset($_GET['end_day'])     && $_GET['start_day']   ? (int)$_GET['end_day']     : date('t',strtotime("$year-$month-15"));
$end_month   = isset($_GET['end_month'])   && $_GET['start_month'] ? (int)$_GET['end_month']   : $month;
$end_year    = isset($_GET['end_year'])    && $_GET['start_year']  ? (int)$_GET['end_year']    : $year;

if(!checkdate($end_month,$end_day,$end_year)) {
	$end_day=get_last_day($end_month,$end_year);
}

//define working variables
$last_proj_id = -1;
$last_task_id = -1;
$total_time = 0;
$grand_total_time = 0;

$start_time = strtotime($start_year . '/' . $start_month . '/' . $start_day);
$end_time   = strtotime($end_year   . '/' . $end_month   . '/' . $end_day);
$end_time2   = strtotime("+1 day",$end_time);  //need last day to be inclusive...

$startStr = date("Y-m-d H:i:s",$start_time);
$endStr = date("Y-m-d H:i:s",$end_time2);

/**********************************************************************************************************
 * This function assists the routine in common.inc that splits tasks into discrete days
 * called split_data_into_discrete_days();   That function needs to put things into a new 
 * array, and in order to get things out of that array in the order we want them in, we 
 * need to help that function out by telling it how to make an index for that array.
 */

function make_index($data,$order) {
	if($order == "date") {
		$index=$data["start_stamp"] . sprintf("-%05d",$data["proj_id"]) . 
			sprintf("-%05d",$data["task_id"]);
	} else {
		$index=sprintf("%05d",$data["proj_id"]) .  sprintf("-%05d-",$data["task_id"]) .
			$data["start_stamp"];
	}
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

$ymdStr = "&amp;start_year=$start_year&amp;start_month=$start_month&amp;start_day=$start_day".
		  "&amp;end_year=$end_year&amp;end_month=$end_month&amp;end_day=$end_day";
$Location="$_SERVER[PHP_SELF]?$ymdStr&amp;client_id=$client_id";

?>
<html>
<head>
<title>Report: Timesheet Summary, <?php echo date('F Y');?></title>
<?php if(!$export_excel){include ("header.inc");} ?>
	<style type="text/css">
		/*
		 * These CSS styles should be moved to an external stylesheet. For now,
		 * I wanted this report to be stand-alone, so I have included them inline.
		 */
		table.report {
			border-collapse:collapse;
			margin:1em;
		}
		table.report th.project{
			text-align:center;
			font-style:italic;
		}
		table.report td, table.report th{
			padding:0.2em 0.7em;
			text-align:right;
			border:1px solid #5271CC;
		}
		table.report th.first{
			border:none;
		}
		table.report tr.weekend td{
			background-color:#CCCCCC;
		}
		table.report th {
			text-align:left;
			font-size:0.7em;
		}
		table.report td.grandtotal{
			color:red;
			font-size:0.8em;
		}

		@media print{
			div#header { display:none; }
			div#footer { display:none; }
			a.export { display:none; }
			td.print { display:none; }
			td.next_prev_links {display:none;}
		}
	</style>
	<?php if(!$export_excel) {
		require("report_javascript.inc");
	?>
	<script type="text/javascript">
	   /*
		* Setup Javascript events for date drop-down lists. Again, this should be included in
		* an external js file, but for now I want this report to be self-contained
		*/

		//run init function on window load
		window.onload = init;

		//apply auto-submit behaviour when changing date values
		function init(){
			var start_day   = getObjectByName('start_day');
			var start_month = getObjectByName('start_month');
			var start_year  = getObjectByName('start_year');
			var end_day     = getObjectByName('end_day');
			var end_month   = getObjectByName('end_month');
			var end_year    = getObjectByName('end_year');

			start_day.onchange   = function (){this.form.submit();};
			start_month.onchange = function (){this.form.submit();};
			start_year.onchange  = function (){this.form.submit();};
			end_day.onchange     = function (){this.form.submit();};
			end_month.onchange   = function (){this.form.submit();};
			end_year.onchange    = function (){this.form.submit();};
		}
	</script>
	<?php } ?>
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
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php if(!$print) include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap width="25%">
							<table width="100%" height="100%" border="0" cellpadding="1" cellspacing="2">
								<tr>
									<td align="right" width="0" class="outer_table_heading">Client:</td>
									<td align="left" width="100%">
										<?php client_select_droplist($client_id, false, !$print); ?>
									</td>
								</tr>
								<tr>
									<td align="right" width="0" class="outer_table_heading">User:</td>
									<td align="left" width="100%">
										<?php user_select_droplist($uid, false, "100%"); ?>
									</td>
								</tr>
							</table>
						</td>
						<td width="25%">
							<table border="0" cellpadding="1" cellspacing="2">
								<tr nowrap>
									<td align="right" width="0" class="outer_table_heading">Start Date:</td>
									<td align="left"><?php day_button("start_day",$start_time); month_button("start_month",$start_month); year_button("start_year",$start_year); ?></td>
								</tr>
								<tr nowrap>
									<td align="right" width="0" class="outer_table_heading">End Date:</td>
									<td align="left"><?php day_button("end_day",$end_time); month_button("end_month",$end_month); year_button("end_year",$end_year); ?></td>
								</tr>
							</table>
						</td>
						<td align="center" nowrap class="outer_table_heading" width="10%">
							<?php echo date('F Y',strtotime($start_year . '/' . $start_month . '/' . $start_day));?>
						</td>
						<?php if (!$print): ?>
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
<?php
} //end if(!$export_excel)
else {  //create Excel header
	list($fn,$ln) = get_users_name($uid);
	$cn = get_client_name($client_id);
	echo "<h4>Report for $cn<br />";
	echo "User: $ln, $fn<br />";
	$sdStr = date("M d, Y",$start_time);
	//just need to go back 1 second most of the time, but DST 
	//could mess things up, so go back 6 hours...
	$edStr = date("M d, Y",$end_time2 - 6*60*60); 
	echo "$sdStr&nbsp;&nbsp;-&nbsp;&nbsp;$edStr"; 
	echo "</h4>";
}

// ==========================================================================================================
// FETCH REPORT DATA AND DISPLAY

list($num, $qh) = get_time_records($startStr, $endStr, $uid, 0, $client_id);

//no records were found
if ($num == 0) {
	print '<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table_body">';
	print "	<tr>\n";
	print "		<td align=\"center\">\n";
	print "			<i><br />No hours recorded.<br /></i>\n";
	if($end_time2 <= $start_time)
		print "			<i><b><br /><font color=\"red\">End time is before Start time!</font><br /></b></i>\n";
	print "		<br /></td>\n";
	print "	</tr>\n";
	print " </table>\n";
} else {
	$darray = array();
	$crosstab = array();
	$projects = array();

	//=========================================================================================================================
	//sort the data into an array which we can more easily traverse in order to build the cross-tab report
	while ($data = dbResult($qh)) {
		//if entry doesn't have an end time or duration, it's an incomplete entry
		//fixStartEndDuration returns a 0 if the entry is incomplete.
		if(!fixStartEndDuration($data)) continue;

		//Since we're allowing entries that may span date boundaries, this complicates
		//our life quite a lot.  We need to "pre-process" the results to split those
		//entries that do span date boundaries into multiple entries that stop and then
		//re-start on date boundaries.
		//NOTE: there must be a make_index() function defined in this file for the following function to, well, function
		$orderby = 'date';
		split_data_into_discrete_days($data,$orderby,$darray,0);
	}

	ksort($darray);
	unset($data);

	foreach($darray as $dary){
		foreach($dary as $data){
			//need to make sure date is in range of what we want...
			if($data["start_stamp"] < $start_time) continue;
			if($data["start_stamp"] >= $end_time2) continue;

			if(!isset($crosstab[$data['start_stamp']]))
				$crosstab[$data['start_stamp']] = array();

			$crosstab[$data['start_stamp']][$data['task_id']] = $data['duration'];

			if(!isset($crosstab[$data['start_stamp']]['total']))
				$crosstab[$data['start_stamp']]['total'] = 0;

			$crosstab[$data['start_stamp']]['total'] += $data['duration'];

			if(!array_key_exists($data['proj_id'], $projects))
				$projects[$data['proj_id']] = array('title' => $data['projectTitle'], 'total' => 0, 'tasks' => array());

			if(!array_key_exists($data['task_id'], $projects[$data['proj_id']]['tasks']))
				$projects[$data['proj_id']]['tasks'][$data['task_id']] = array('title' => $data['taskName'], 'total' => 0);
		}
	}

	asort($projects);

	echo '<table border="1" cellpadding="0" cellspacing="0" class="table_body report">';
	echo '<thead>';

	echo '<tr>';
	echo '<th class="first">&nbsp;</th>';
	echo '<th class="first">&nbsp;</th>';
		foreach($projects as $project_id => $project){
			echo '<th class="project" colspan="' . count($project['tasks']) . '">' . htmlentities($project['title']) . '</th>';
		}
	echo '</tr>';

	echo '<tr>';
	echo '<th class="first">&nbsp;</th>';
	echo '<th>Total</th>';

	foreach($projects as $project_id => $project){
		foreach($project['tasks'] as $task_id => $task)
			echo '<th>' . htmlentities($task['title']) . '</th>';
	}

	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';

	while($start_time <= $end_time){
		if(array_key_exists($start_time,$crosstab))
			$data = $crosstab[$start_time];
		else
			$data = array();

		if(in_array(date('N',$start_time), array(6,7)))
			echo '<tr class="weekend">';
		else
			echo '<tr>';

		echo '<td class="date"><strong>' . date('D, jS M, Y',$start_time) . '</strong></td>';

		if($data)
			echo '<td><strong>' . format_time($data['total']) . '</strong></td>';
		else
			echo '<td>&nbsp;</td>';

		foreach($projects as $project_id => $project){
			foreach($project['tasks'] as $task_id => $task){
				echo '<td class="cell">';

				if(array_key_exists($task_id, $data)){
					echo htmlentities(format_time($data[$task_id]));

					$projects[$project_id]['tasks'][$task_id]['total'] += $data[$task_id];
					$grand_total_time                                  += $data[$task_id];
				}
				else
					echo '&nbsp;';

				echo '</td>';
			}
		}

		echo '</tr>';

		$start_time = strtotime(date('d-M-Y',$start_time) . ' + 1 Day');
	}

	echo '</tbody>';
	echo '<tfoot>';
	echo '<tr>';
	echo '<td class="grandtotal"><strong>TOTAL</strong></td>';
	echo '<td class="grandtotal"><strong>' . format_time($grand_total_time) . '</strong></td>';

	foreach($projects as $project_id => $project){
		foreach($project['tasks'] as $task_id => $task)
			echo '<td class="total"><strong>' . htmlentities(format_time($task['total'])) . '</strong></td>';
	}

	echo '</tr>';
	echo '</tfoot>';
	echo '</table>';
}

//====================================================================================================================
// close off report
if(!$export_excel){
?>
					</td>
				</tr>
			</table>

<!-- include the timesheet face up until the end -->
<?php if (!$print) include("timesheet_face_part_3.inc"); ?>

		</td>
	</tr>
</table>
<?php if ($print) { ?>
	<table width="100%" border="1" cellspacing="0" cellpadding="0">
		<tr>
			<td width="30%"><table><tr><td>Employee Signature:</td></tr></table></td>
			<td width="70%"><img src="images/spacer.gif" width="150" height="1" alt="" /></td>
		</tr>
		<tr>
			<td width="30%"><table><tr><td>Manager Signature:</td></tr></table></td>
			<td width="70%"><img src="images/spacer.gif" width="150" height="1" alt="" /></td>
		</tr>
		<tr>
			<td width="30%"><table><tr><td>Client Signature:</td></tr></table></td>
			<td width="70%"><img src="images/spacer.gif" width="150" height="1" alt="" /></td>
		</tr>
	</table>		
<?php } //end if($print) ?>
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
