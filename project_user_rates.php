<?php
// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclProjects')) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclProjects'));
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
</head>
<body <?php include ("body.inc"); ?> >

<?php
include ("banner.inc");
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>
		<table width="100%" border="0">
		<tr>
			<td align="left" nowrap class="outer_table_heading">
				All Projects:
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
				<td class="inner_table_column_heading">&nbsp;Title</td>
				<td class="inner_table_column_heading">&nbsp;Client</td>
				<td class="inner_table_column_heading">&nbsp;<i>Actions</i></td>
				</tr>
<?php

list($qh,$num) = dbQuery(
					"SELECT p.proj_id, p.title, c.organisation ".
					"FROM $PROJECT_TABLE p, $CLIENT_TABLE c ".
					"WHERE p.client_id = c.client_id ".
					"ORDER BY c.organisation");

$n=0;
while ($data = dbResult($qh)) {
	$titleField = empty($data["title"]) ? "&nbsp;": $data["title"];
	$organisationField = empty($data["organisation"]) ? "&nbsp;": $data["organisation"];
	if (($n % 2) == 1)
			print "<tr class=\"diff\">\n";
		else
			print "<tr>\n";
	print "<td class=\"calendar_cell_middle\">&nbsp;$titleField</td>";
	print "<td class=\"calendar_cell_middle\">&nbsp;$organisationField</td>";
	print "<td class=\"calendar_cell_disabled_right\">";
	print "	<a href=\"project_user_rates_action.php?proj_id=$data[proj_id]&amp;action=show_users\">&nbsp;Edit Rates</a>\n";
	print "</td>\n";
	print "</tr>\n";
	$n++;
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

<?php
include ("footer.inc");
?>
</body>
</HTML>
<?php
// vim:ai:ts=4:sw=4
?>
