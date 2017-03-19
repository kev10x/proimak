<?php
// $Header: /cvsroot/tsheet/timesheet.php/reports.php,v 1.5 2005/03/02 22:22:38 stormer Exp $

// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
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
$uid = isset($_REQUEST['uid']) ? $_REQUEST['uid']: $contextUser;

$todayDate = mktime(0, 0, 0, $month, $day, $year);
$ymdStr = "&amp;year=$year&amp;month=$month&amp;day=$day";

?>
<html>
<head><title>Timesheet Reports Page</title>
<?php include ("header.inc"); ?>
</head>
<body <?php include ("body.inc"); ?> >
<?php 
include ("banner.inc"); 
$motd=0;
include ("navcal/navcalendars.inc");
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap class="outer_table_heading">
							Reports
						</td>
						<td align="left" nowrap class="outer_table_heading">
							<?php echo date('F d, Y',$todayDate) ?>
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
						<td class="inner_table_column_heading">Report Description</td>
						<td class="inner_table_column_heading">Actions</td>
					</tr>

<?php
function echo_TR_Class($val='') {
//if you want "highlighting" on the first row, make this zero instead of one
static $row = 1;

if(!($val === ''))
	$row=$val;

	if($row%3)
		echo "<tr>";
	else
		echo "<tr class=\"diff\">";

	$row++;
}
?>
					<?php echo_TR_Class();?>
						<td class="calendar_cell_middle">User report</td>
						<td class="calendar_cell_right">
							<a href="report_user.php?<?php print $ymdStr; ?>&amp;mode=monthly">Generate monthly</a> /
							<a href="report_user.php?<?php print $ymdStr; ?>&amp;mode=weekly">Generate weekly</a>
						</td>
					</tr>
					<?php echo_TR_Class();?>
						<td class="calendar_cell_middle">User summary</td>
						<td class="calendar_cell_right">
							<a href="report_user_summ.php?<?php print $ymdStr; ?>">Bi-monthly</a>
						</td>
					</tr>
					<?php echo_TR_Class();?>
						<td class="calendar_cell_middle">Project report</td>
						<td class="calendar_cell_right">
							<a href="report_project.php?<?php print $ymdStr; ?>&amp;mode=monthly">Generate monthly</a> /
							<a href="report_project.php?<?php print $ymdStr; ?>&amp;mode=weekly">Generate weekly</a>
						</td>
					</tr>
					<?php echo_TR_Class();?>
						<td class="calendar_cell_middle">Client report</td>
						<td class="calendar_cell_right">
							<a href="report_client.php?<?php print $ymdStr; ?>&amp;mode=monthly">Generate monthly</a> /
							<a href="report_client.php?<?php print $ymdStr; ?>&amp;mode=weekly">Generate weekly</a>
						</td>
					</tr>
					<?php echo_TR_Class();?>
						<td class="calendar_cell_middle">Client / User - grid report</td>
						<td class="calendar_cell_right">
							<a href="report_grid_client_user.php?<?php print $ymdStr; ?>&amp;mode=monthly">Generate monthly</a>
						</td>
					</tr>
<?php if ($authenticationManager->hasClearance(CLEARANCE_ADMINISTRATOR)) { ?>
					<?php echo_TR_Class();?>
						<td class="calendar_cell_middle">All users & All projects report</td>
						<td class="calendar_cell_right">
							<a href="report_all.php?<?php print $ymdStr; ?>&amp;mode=monthly">Generate monthly</a>
						</td>
					</tr>
<?php } ?>
<?php if ($authenticationManager->hasAccess('aclAbsences')) { ?>
					<tr class="inner_table_head">
						<td class="inner_table_column_heading">Attendance Reports</td>
						<td class="inner_table_column_heading">Actions</td>
					</tr>
					<?php echo_TR_Class(1);?>
						<td class="calendar_cell_middle">Absence Report</td>
						<td class="calendar_cell_right">
							<a href="report_absences.php?<?php print $ymdStr; ?>">Generate monthly</a>
						</td>
					</tr>
					<?php echo_TR_Class();?>
						<td class="calendar_cell_middle">Yearly User Report</td>
						<td class="calendar_cell_right">
							<a href="report_hours.php?<?php print $ymdStr; ?>">Generate yearly</a>
						</td>
					</tr>
<?php } ?>
					</table>
			</td>
		</tr>
	</table>

<!-- include the timesheet face up until the end -->
<?php include("timesheet_face_part_3.inc"); ?>

		</td>
	</tr>
</table>

<?php
include ("footer.inc");
?>
</body>
</HTML>
<?php
// vim:ai:ts=4:sw=4
?>
