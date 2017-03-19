<?php
//$Header: /cvsroot/tsheet/timesheet.php/proj_edit.php,v 1.7 2005/05/16 01:39:57 vexil Exp $
// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclProjects')) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclProjects'));
	exit;
}

// Connect to database.
$dbh = dbConnect();
$contextUser = strtolower($_SESSION['contextUser']);

//load local vars from superglobals
$proj_id = $_REQUEST['proj_id'];

//define the command menu
$commandMenu->add(new TextCommand("Back", true, "javascript:history.back()"));
$commandMenu->add(new TextCommand("&nbsp; &nbsp; &nbsp;", false, ""));
$commandMenu->add(new TextCommand("Copy Projects/Tasks between users", true, "user_clone.php"));

$dbh = dbConnect();
list($qh, $num) = dbQuery("SELECT proj_id, " .
								"title, " .
								"client_id, " .
								"description, " .
								"unix_timestamp(start_date) AS start_stamp, ".
								"unix_timestamp(deadline) AS end_stamp, ".
								"http_link, " .
								"proj_status, " .
								"proj_leader " .
							"FROM $PROJECT_TABLE " .
							"WHERE proj_id = $proj_id " .
							"ORDER BY proj_id");
$data = dbResult($qh);

$dti=getdate($data["start_stamp"]);
$start_month = $dti["mon"];
$start_year = $dti["year"];

$dti=getdate($data["end_stamp"]);
$end_month = $dti["mon"];
$end_year = $dti["year"];

list($qh, $num) = dbQuery("SELECT username FROM $ASSIGNMENTS_TABLE WHERE proj_id = $proj_id");
$selected_array = array();
$i = 0;
while ($datanext = dbResult($qh)) {
	$selected_array[$i] = $datanext["username"];
	$i++;
}
?>

<html>
<head>
<title>Edit Project</title>
<?php include ("header.inc"); ?>
</head>
<body <?php include ("body.inc"); ?> >
<?php include ("banner.inc"); ?>

<form action="proj_action.php" method="post">
<input type="hidden" name="action" value="edit" />
<input type="hidden" name="proj_id" value="<?php echo $data["proj_id"]; ?>" />

<table width="600" align="center" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap class="outer_table_heading" nowrap>
							Edit Project: <?php echo stripslashes($data["title"]); ?>
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
						<td align="right">Project Title:</td>
						<td><input type="text" name="title" size="42" value="<?php echo stripslashes($data["title"]); ?>" style="width: 100%;" maxlength="200" /></td>
					</tr>
					<tr>
						<td align="right">Client:</td>
						<td><?php client_select_list($data["client_id"], 0, false, false, false, true, "", false); ?></td>
					</tr>
					<tr>
						<td align="right" valign="top">Description:</td>
						<td><textarea name="description" rows="4" cols="40" wrap="virtual" style="width: 100%;"><?php $data["description"] = stripslashes($data["description"]); echo $data["description"]; ?></textarea></td>
					</tr>
					<tr>
						<td align="right">Start Date:</td>
						<td><?php day_button("start_day",$data["start_stamp"],0); month_button("start_month",$start_month); year_button("start_year",$start_year); ?></td>
					</tr>
					<tr>
						<td align="right">Deadline:</td>
						<td><?php day_button("end_day",$data["end_stamp"],0); month_button("end_month",$end_month); year_button("end_year",$end_year); ?></td>
					</tr>
					<tr>
						<td align="right">Status:</td>
						<td><?php proj_status_list("proj_status", $data["proj_status"]); ?></td>
					</tr>
					<tr>
						<td align="right">URL:</td>
						<td><input type="text" name="url" size="42" value="<?php echo $data["http_link"]; ?>" style="width: 100%;" /></td>
					</tr>
					<tr>
						<td align="right" valign="top">Assignments:</td>
						<td><?php multi_user_select_list("assigned[]",$selected_array); ?></td>
					</tr>
					<tr>
						<td align="right">Project Leader:</td>
						<td><?php single_user_select_list("project_leader", $data["proj_leader"]); ?></td>
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

<?php include ("footer.inc"); ?>
</body>
</HTML>
<?php
// vim:ai:ts=4:sw=4
?>
