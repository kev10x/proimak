<?php

// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclAbsences')) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclAbsences'));
	exit;
}

//define the command menu & we get these variables from $_REQUEST:
//  $month $day $year $client_id $proj_id $task_id
include("timesheet_menu.inc");

$contextUser = strtolower($_SESSION['contextUser']);
$loggedInUser = strtolower($_SESSION['loggedInUser']);

if (empty($loggedInUser))
	errorPage("Could not determine the logged in user");

if (empty($contextUser))
	errorPage("Could not determine the context user");

if ($authenticationManager->hasClearance(CLEARANCE_MANAGER))
	$canChangeUser = true;
else
	$canChangeUser = false;

if (isset($_REQUEST['uid']))
	$uid = $_REQUEST['uid'];
else
	$uid = $contextUser;

$action = 0;

//run the query
list($qh,$num) = get_absences($month, $year, $uid);
$ihol = 0;

//define working variables
$last_day = get_last_day($month, $year);

?>
<html>
<head>
<title>Timesheet Absence Entry</title>
<?php include ("header.inc"); ?>
<script type="text/javascript">

	function onSubmit() {
		//set the action
		document.getElementById('action').value = 1;
		document.theForm.submit();
	}

</script>
</head>
<?php
echo "<body width=\"100%\" height=\"100%\"";
include ("body.inc");
echo ">\n";

include ("banner.inc");
$motd = 0;  //don't want the motd printed
include ("navcal/navcal_monthly.inc");
?>
<form name="theForm" id="theForm" action="absences_action.php" method="post">
<input type="hidden" name="month" value=<?php echo $month; ?> />
<input type="hidden" name="day" value=<?php echo $day; ?> />
<input type="hidden" name="year" value=<?php echo $year; ?> />
<input type="hidden" name="last_day" value=<?php echo $last_day; ?> />
<input type="hidden" name="action" id="action" value=<?php echo $action; ?> />
<input type="hidden" name="origin" value="<?php echo $_SERVER["PHP_SELF"]; ?>" />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
					<?php if($canChangeUser) : ?>
						<td align="left" width="38%" nowrap>User: &nbsp; <?php user_select_droplist($uid); ?></td>
					<?php else : ?>
						<td width="38%" nowrap>User: &nbsp;<?php echo "<b>$uid</b>"; ?></td>
					<?php endif; ?>
						<td align="center" nowrap class="outer_table_heading">
							<?php echo date('F Y',mktime(0,0,0,$month, 1, $year)); ?>
						</td>
						<td align="right">&nbsp; </td>
						<td align="right">
							<input type="button" value="Save Changes" name="save" id="save" onclick="onSubmit();" />
						</td>
					</tr>
				</table>

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_2.inc"); ?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<tr>
			<td>
				<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table_body">
				<tr>
					<td class="calendar_cell_disabled_right">&nbsp</td>
					<td align="center" class="calendar_cell_disabled_right"><b>Day</b></td>
					<td align="center" class="calendar_cell_disabled_right" colspan=2 ><b>Morning</b></td>
					<td align="center" class="calendar_cell_disabled_right" colspan=2 ><b>Afternoon</b></td>
				</tr>
		<tr>
<?php
	for ($i=1;$i<=$last_day;$i++) {
		$day = mktime(0,0,0,$month,$i,$year);
		$dow = strftime("%A", $day);
		$daystyle = "calendar_cell_middle";
		if ((date('w', $day) == 6)||(date('w', $day) == 0)) {
			$daystyle = "calendar_cell_holiday_middle";
		}
		$AM_text = "";
		$PM_text = "";
		$AM_type = '';
		$PM_type = '';
		$AMstyle = $daystyle;
		$PMstyle = $daystyle;

		// first try for AM absences
		if ($ihol<$num) {
			$absdata = dbResult($qh,$ihol);
			if ($i==$absdata['day_of_month']) {
				if ($absdata['AM_PM']=='AM')
				{
					$AM_text = urldecode($absdata['subject']);
					$AM_type = $absdata['type'];
					$AMstyle = "calendar_cell_holiday_middle";
					$ihol++;
				}
				else if ($absdata['AM_PM']=='day')
				{
					$AM_text = urldecode($absdata['subject']);
					$AM_type = $absdata['type'];
					$AMstyle = "calendar_cell_holiday_middle";
					// don't increment
				}
			}
		}
		// second try for PM absences
		if ($ihol<$num) {
			$absdata = dbResult($qh,$ihol);
			if ($i==$absdata['day_of_month']) {
				if ($absdata['AM_PM']=='PM')
				{
					$PM_text = urldecode($absdata['subject']);
					$PM_type = $absdata['type'];
					$PMstyle = "calendar_cell_holiday_middle";
					$ihol++;
				}
				else if ($absdata['AM_PM']=='day')
				{
					$PM_text = urldecode($absdata['subject']);
					$PM_type = $absdata['type'];
					$PMstyle = "calendar_cell_holiday_middle";
					// now increment
					$ihol++;
				}
			}
		}

		if (($AM_type=='Public')&&($PM_type=='Public')) {
			$daystyle = "calendar_cell_holiday_middle";
		}
		if ($daystyle == "calendar_cell_holiday_middle")
			$disabled = 'true';
		else
			$disabled = 'false';

?>
			<td align="center" class="<?php echo $daystyle; ?>"><?php echo $dow; ?></td>
			<td align="center" class="<?php echo $daystyle; ?>"><?php echo $i; ?></td>
			<td align="right" class="<?php echo $AMstyle; ?>"><?php absence_select_droplist($AM_type, $disabled, "AMtype".$i); ?></td>
			<td align="left" class="<?php echo $AMstyle; ?>"><input type="text" id="<?php echo "AMtext",$i; ?>" name="<?php echo "AMtext",$i; ?>" class="<?php echo $AMstyle; ?>" value="<?php echo $AM_text; ?>" <?php if ($disabled=='true') echo "readonly"; ?> /></td>
			<td align="right" class="<?php echo $PMstyle; ?>"><?php absence_select_droplist($PM_type, $disabled, "PMtype".$i); ?></td>
			<td align="left" class="<?php echo $PMstyle; ?>"><input type="text" id="<?php echo $i,"_PMtext"; ?>" name="<?php echo "PMtext",$i; ?>" class="<?php echo $PMstyle; ?>" value="<?php echo $PM_text; ?>" <?php if ($disabled=='true') echo "readonly"; ?> /></td>
		</tr>
<?php
	}
?>
						</tr>
					</td>
				</table>
			</td>
		</tr>
	</table>

<!-- include the timesheet face up until the end -->
<?php include("timesheet_face_part_3.inc"); ?>

		</td>
	</tr>
</table>

</form>
<?php
include ("footer.inc");
?>
</body>
</html>
