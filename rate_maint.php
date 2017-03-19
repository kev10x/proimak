<?php
// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasClearance(CLEARANCE_ADMINISTRATOR)) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=Administrator");
	exit;
}

// Connect to database.
$dbh = dbConnect();

//define the command menu
include("timesheet_menu.inc");

?>
<head><title>Rates Management Page</title>
<?php
include ("header.inc");
?>
<script type="text/javascript" type="text/javascript">

	function editRate(rateId, billRate)
	{
		document.rateForm.rate_id.value = rateId;
		document.rateForm.bill_rate.value = billRate;
		document.location.href = "#AddEdit";
	}

	function addRate()
	{
		//validation
		if (document.rateForm.bill_rate.value == "")
			alert("You must enter a billing rate.");
		else
		{
			document.rateForm.action.value = "addupdate";
			document.rateForm.submit();
		}
	}
</script>
</head>
<body <?php include ("body.inc"); ?> >
<?php
include ("banner.inc");
?>
<form action="rate_action.php" name="rateForm" method="post">
<input type="hidden" name="action" value="" />
<input type="hidden" name="rate_id" value="" />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap class="outer_table_heading">
								Rates:
						</td>
					</tr>
				</table>

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_2.inc"); ?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<tr>
			<td>
				<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_body">
					<tr class="inner_table_head">
						<td class="inner_table_column_heading">&nbsp;Rate Id</td>
						<td class="inner_table_column_heading">&nbsp;Bill Rate(per hour)</td>
						<td class="inner_table_column_heading">&nbsp;<i>Actions</i></td>
					</tr>
<?php

list($qh,$num) = dbQuery("select * from $RATE_TABLE where rate_id != 1 order by rate_id");

$count = 0;
while ($data = dbResult($qh)) {
	$billRateIdField = empty($data["rate_id"]) ? "&nbsp;": $data["rate_id"];
	$billRateField = empty($data["bill_rate"]) ? "&nbsp;": $data["bill_rate"];
	if (($count % 2) == 1)
			print "<tr class=\"diff\">\n";
		else
			print "<tr>\n";
	print "<td class=\"calendar_cell_middle\">&nbsp;$billRateIdField</td>";
	print "<td class=\"calendar_cell_middle\">&nbsp;$billRateField</td>";
	print "<td class=\"calendar_cell_disabled_right\">";
	print "	<a href=\"javascript:editRate('$data[rate_id]', '$data[bill_rate]')\">&nbsp;Edit</a>\n";
	print "</td>\n";
	print "</tr>\n";
	$count++;
}
?>
				</table>
			</td>
		</tr>
	</table>

<!-- include the timesheet face up until the end -->
<?php include("timesheet_face_part_3.inc"); ?>

		</td>
	</tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap class="outer_table_heading">
							<a name="AddEdit">	Add/Update Rates:</a>
						</td>
					</tr>
				</table>

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_2.inc"); ?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<tr>
			<td>
				<table width="100%" border="0" class="table_body">
					<tr>
						<td>Bill rate(per hour):&nbsp;<input size="5" name="bill_rate" style="width: 25%;" />
							<input type="button" name="addupdate" value="Add/Update Rates" onclick="javascript:addRate()" class="bottom_panel_button" /></td>
					</tr>
					<tr>
					<td align="left">
					    <a href="project_user_rates.php">Rate Selection</a>
					</td>
					</tr>
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
</HTML>
<?php
// vim:ai:ts=4:sw=4
?>
