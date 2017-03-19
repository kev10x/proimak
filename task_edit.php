<?php
// $Header: /cvsroot/tsheet/timesheet.php/task_edit.php,v 1.6 2004/07/02 14:15:56 vexil Exp $
// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclTasks')) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclTasks'));
	exit;
}

// Connect to database.
$dbh = dbConnect();
$contextUser = strtolower($_SESSION['contextUser']);

//load local vars from superglobals
$task_id = $_REQUEST['task_id'];

//define the command menu
$commandMenu->add(new TextCommand("Back", true, "javascript:history.back()"));
$commandMenu->add(new TextCommand("&nbsp; &nbsp; &nbsp;", false, ""));
$commandMenu->add(new TextCommand("Copy Projects/Tasks between users", true, "user_clone.php"));

//query database for existing task values
list($qh, $num) = dbQuery("SELECT task_id, proj_id, name, description, status FROM $TASK_TABLE WHERE task_id = $task_id ");
$data = dbResult($qh);

list($qh, $num) = dbQuery("SELECT username FROM $TASK_ASSIGNMENTS_TABLE WHERE proj_id = $data[proj_id] AND task_id = $task_id");
$selected_array = array();
$i = 0;
while ($datanext = dbResult($qh)) {
	$selected_array[$i] = $datanext["username"];
	$i++;
}

?>
<html>
<head>
	<title>Edit Task</title>
<?php include ("header.inc"); ?>
</head>
<body <?php include ("body.inc"); ?> >
<?php include ("banner.inc"); ?>

<form action="task_action.php" method="post">
<input type="hidden" name="action" value="edit" />
<input type="hidden" name="proj_id" value="<?php echo $data["proj_id"]; ?>" />
<input type="hidden" name="task_id" value="<?php echo $data["task_id"]; ?>" />

<table width="600" align="center" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap class="outer_table_heading" nowrap>
							Edit Task: <?php echo $data["name"]; ?>
						</td>
					</tr>
				</table>

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_2.inc"); ?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<tr>
			<td>
				<table width="100%" border="0" cellpadding="1" cellspacing="2" class="table_body">
					<tr>
						<td align="right">Task Name:</td>
						<td><input type="text" name="name" size="42" value="<?php echo $data["name"]; ?>" style="width: 100%" /></td>
					</tr>
					<tr>
						<td align="right" valign="top">Description:</td>
						<td><textarea name="description" rows="4" cols="40" wrap="virtual" style="width: 100%"><?php $data["description"] = stripslashes($data["description"]); echo $data["description"]; ?></textarea></td>
					</tr>
					<tr>
						<td align="right">Status:</td>
						<td><?php proj_status_list("task_status", $data["status"]); ?></td>
					</tr>
					<tr>
						<td align="right" valign="top">Assignments:</td>
						<td><?php multi_user_select_list("assigned[]",$selected_array); ?></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<table width="100%" border="0" class="table_bottom_panel">
					<tr>
						<td align="center">
							<input type="submit" value="Update" />
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

<?php include("footer.inc"); ?>
</body>
</html>
<?php
// vim:ai:ts=4:sw=4
?>
