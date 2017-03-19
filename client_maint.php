<?php
// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclClients')) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclClients'));
	exit;
}

//define the command menu & we get these variables from $_REQUEST:
//  $month $day $year $client_id $proj_id $task_id
include("timesheet_menu.inc");

$contextUser = strtolower($_SESSION['contextUser']);

//make sure "No Client exists with client_id of 1
//execute the query
tryDbQuery("INSERT INTO $CLIENT_TABLE VALUES (1,'No Client', 'This is required, do not edit or delete this client record', '', '', '', '', '', '', '', '', '', '', '', '', '', '');");
tryDbQuery("UPDATE $CLIENT_TABLE set organisation='No Client' WHERE client_id='1'");

?>

<HTML>
<head>
<title>Client Management Page</title>
<?php
include ("header.inc");
?>
<script type="text/javascript">

	function delete_client(clientId) {
				if (confirm('Are you sure you want to delete this client?'))
					location.href = 'client_action.php?client_id=' + clientId + '&action=delete';
	}

</script>
</head>
<body <?php include ("body.inc"); ?> >
<?php
include ("banner.inc");
?>
<form action="client_action.php" method="post">

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap class="outer_table_heading">
							Clients
						</td>
						<td align="right">
							<a href="client_add.php" class="outer_table_action">Add new client</a>
						</td>
					</tr>
				</table>

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_2.inc");

?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<tr>
			<td>
				<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_body">
<?php

//execute the query
list($qh,$num) = dbQuery("SELECT * FROM $CLIENT_TABLE WHERE client_id > 1 ORDER BY organisation");

//are there any results?
if ($num == 0) {
	print "<tr><td align=\"center\" colspan=\"5\"><br />There are currently no clients.<br /><br /></td></tr>";
}
else {

?>
					<tr class="inner_table_head">
						<td class="inner_table_column_heading">Organisation</td>
						<td class="inner_table_column_heading">Contact Name</td>
						<td class="inner_table_column_heading">Phone</td>
						<td class="inner_table_column_heading">Contact Email</td>
						<td class="inner_table_column_heading"><i>Actions</i></td>
					</tr>
<?php
$count = 0;
	while ($data = dbResult($qh)) {
		$organisationField = stripslashes($data["organisation"]);
		if (empty($organisationField))
			$organisationField = "&nbsp;";
		$contactNameField = $data["contact_first_name"] . "&nbsp;" . $data["contact_last_name"];
		$phoneField = $data["phone_number"];
		if (empty($phoneField))
			$phoneField = "&nbsp;";
		$emailField = $data["contact_email"];
		if (empty($emailField))
			$emailField = "&nbsp;";
		if (($count % 2) == 1)
			print "<tr class=\"diff\">";
		else
			print "<tr>";
		print "<td class=\"calendar_cell_middle\"><a href=\"javascript:void(0)\" onclick=window.open(\"client_info.php?client_id=$data[client_id]\",\"ClientInfo\",\"location=0,directories=no,status=no,menubar=no,resizable=1,width=480,height=240\")>$organisationField</a></td>";
		print "<td class=\"calendar_cell_middle\">$contactNameField</td>";
		print "<td class=\"calendar_cell_middle\">$phoneField</td>";
		print "<td class=\"calendar_cell_middle\">$emailField</td>";
		print "<td class=\"calendar_cell_disabled_right\">\n";
		print "	<a href=\"client_edit.php?client_id=$data[client_id]\">Edit</a>,&nbsp;\n";
		print "	<a href=\"javascript:delete_client($data[client_id]);\">Delete</a>\n";
		print "</td>\n";
		$count++;
	}
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

</form>
<?php
include ("footer.inc");
?>
</body>
</HTML>
<?php
// vim:ai:ts=4:sw=4
?>
