<?php
// $Header: /cvsroot/tsheet/timesheet.php/user_maint.php,v 1.7 2005/02/03 09:15:44 vexil Exp $
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
<head><title>User Management Page</title>
<?php
include ("header.inc");
?>
<script type="text/javascript">

	function deleteUser(uid, username) {
		//get confirmation
		if (confirm("Note:  It is recommended that users be marked INACTIVE rather than deleted.\nDeleting user '" + username + "' will also remove all related project and task assignments.\n\tClicking OK will DELETE this user!")) {
			document.userForm.action.value = "delete";
			document.userForm.uid.value = uid;
			document.userForm.username.value = username;
			document.userForm.submit();
		}
	}

	function editUser(uid, firstName, lastName, username, emailAddress, password, isAdministrator, isManager, isActive) {
		document.userForm.uid.value = uid;
		document.userForm.first_name.value = firstName;
		document.userForm.last_name.value = lastName;
		document.userForm.username.value = username;
		document.userForm.email_address.value = emailAddress;
		document.userForm.password.value = password;
		document.userForm.checkAdmin.checked = isAdministrator;
		document.userForm.isAdministrator.value = document.userForm.checkAdmin.checked=='1' ? true : false;
		document.userForm.checkManager.checked = isManager;
		document.userForm.isManager.value = document.userForm.checkManager.checked=='1' ? true : false;
		document.userForm.checkActive.checked = isActive;
		document.userForm.isActive.value = document.userForm.checkActive.checked=='1' ? true : false;
		onCheckClearance();
		document.location.href = "#AddEdit";
	}

	function addUser() {
		//validation
		if (document.userForm.username.value == "")
			alert("You must enter a username that the user will log on with.");
		else if (document.userForm.password.value == "")
			alert("You must enter a password that the user will log on with.");
		else {
			document.userForm.action.value = "addupdate";
			document.userForm.submit();
		}
	}

	function goClone() {
		var cloneTo = document.userForm.username.value;
		var location = "user_clone.php";
		if(cloneTo.length > 0)
			location+="?cloneTo=" + cloneTo;
		window.location.href=location;
	}

	function onCheckClearance() {
		document.userForm.isAdministrator.value =
			document.userForm.checkAdmin.checked;
		document.userForm.isManager.value =
			document.userForm.checkManager.checked;
	}

	function onCheckActive() {
		document.userForm.isActive.value =
			document.userForm.checkActive.checked;
	}

</script>
</head>
<body <?php include ("body.inc"); ?> >
<?php
include ("banner.inc");
?>
<form action="user_action.php" name="userForm" method="post">
<input type="hidden" name="action" value="" />
<input type="hidden" name="uid" value="" />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap class="outer_table_heading">
							Employees/Contractors:
						</td>
						<td align="right" nowrap >
							<a href="javascript:goClone()">Copy Projects/Tasks between users</a></td>
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
						<td class="inner_table_column_heading">First Name</td>
						<td class="inner_table_column_heading">Last Name</td>
						<td align="center" class="inner_table_column_heading">Active</td>
						<td class="inner_table_column_heading">Access</td>
						<td class="inner_table_column_heading">Login Username</td>
						<td class="inner_table_column_heading">Email Address</td>
						<td class="inner_table_column_heading"><i>Actions</i></td>
					</tr>
<?php

list($qh,$num) = dbQuery("SELECT * FROM $USER_TABLE WHERE username!='guest' ORDER BY status desc, last_name, first_name");

while ($data = dbResult($qh)) {
	$firstNameField = empty($data["first_name"]) ? "&nbsp;": $data["first_name"];
	$lastNameField = empty($data["last_name"]) ? "&nbsp;": $data["last_name"];
	$usernameField = empty($data["username"]) ? "&nbsp;": $data["username"];
	$isActive = ($data["status"]=='ACTIVE');
	$emailAddressField = empty($data["email_address"]) ? "&nbsp;": $data["email_address"];
	$isAdministrator = ($data["level"] >= 10);
	$isManager = ($data["level"] >= 5);

	print "<tr>\n";
	print "<td class=\"calendar_cell_middle\">$firstNameField</td>";
	print "<td class=\"calendar_cell_middle\">$lastNameField</td>";
	if ($isActive)
		print "<td align=\"center\" class=\"calendar_cell_middle\"><img src=\"images/green-check-mark.gif\" height=\"12\" border=\"0\" alt=\"\" /></td>";
	else
		print "<td align=\"center\" class=\"calendar_cell_middle\"><img src=\"images/red-x.gif\" height=\"12\" border=\"0\" alt=\"\" /></td>";

	if ($isAdministrator)
		print "<td class=\"calendar_cell_middle\"><span class=\"calendar_total_value_weekly\">Admin</span></td>";
	else if ($isManager)
		print "<td style=\"color:blue; font-weight:bold;\" class=\"calendar_cell_middle\">Manager</td>";
	else
		print "<td class=\"calendar_cell_middle\">Basic</td>";

	print "<td class=\"calendar_cell_middle\">$usernameField</td>";
	print "<td class=\"calendar_cell_middle\">$emailAddressField</td>";
	print "<td class=\"calendar_cell_disabled_right\">";
	print "	<a href=\"javascript:deleteUser('$data[uid]', '$data[username]')\">Delete</a>,&nbsp;\n";
	print "	<a href=\"javascript:editUser('$data[uid]', '$data[first_name]', '$data[last_name]', '$data[username]', '$data[email_address]', '$data[password]', '$isAdministrator', '$isManager', '$isActive')\">Edit</a>\n";
	print "</td>\n";
	print "</tr>\n";
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
							<a name="AddEdit">	Add/Update Employee/Contractor:</a>
						</td>
						<td align="right" nowrap >
							<a href="javascript:goClone()">Copy Projects/Tasks between users</a></td>
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
						<td>First name:<br><input size="20" name="first_name" style="width: 100%;"></td>
						<td>Last name:<br><input size="20" name="last_name" style="width: 100%;"></td>
						<td>Login username:<br /><input size="15" name="username" style="width: 100%;" /></td>
						<td>Email address:<br /><input size="35" name="email_address" style="width: 100%;" /></td>
						<td>Password:<br /><input type="password" size="20" name="password" style="width: 100%;" AUTOCOMPLETE="OFF" /></td>
					</tr>
					<tr>
						<td colspan="2" align="left">
							<input type="checkbox" name="checkAdmin" id="checkAdmin" value="" onclick="onCheckClearance();" />This user is an administrator
							<input type="hidden" name="isAdministrator" id="isAdministrator" value="false" />
						</td>
						<td colspan="2" align="left">
							<input type="checkbox" name="checkManager" id="checkManager" value="" onclick="onCheckClearance();" />This user is a project manager
							<input type="hidden" name="isManager" id="isManager" value="false" />
						</td>
						<td align="left">
							<input type="checkbox" name="checkActive" id="checkActive" value="" onclick="onCheckActive();" />is Active
							<input type="hidden" name="isActive" id="isActive" value="false" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<table width="100%" border="0" class="table_bottom_panel">
					<tr>
						<td align="center">
							<input type="button" name="addupdate" value="Add/Update Employee/Contractor" onclick="javascript:addUser()" class="bottom_panel_button" />
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
