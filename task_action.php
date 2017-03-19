<?php
// $Header: /cvsroot/tsheet/timesheet.php/task_action.php,v 1.7 2005/05/23 07:32:00 vexil Exp $
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
$action = $_REQUEST["action"];
$task_id = isset($_REQUEST["task_id"]) ? mysql_real_escape_string($_REQUEST["task_id"]): 0;
$proj_id = $_REQUEST["proj_id"];

if ($action == "add" || $action == "edit") {
	$name = mysql_real_escape_string($_REQUEST["name"]);
	$description = mysql_real_escape_string($_REQUEST["description"]);
	$assigned = isset($_REQUEST["assigned"]) ? $_REQUEST['assigned']: array();
	array_walk($assigned, mysql_real_escape_string);
	$task_status = mysql_real_escape_string($_REQUEST["task_status"]);
}

//create a time string for >>now<<
$time_string = date("Y-m-d H:i:00");

if (!isset($action))
	Header("Location: $HTTP_REFERER");
elseif ($action == "add") {
	$name = addslashes($name);
	$description = addslashes($description);

	list($qh, $num) = dbQuery("INSERT INTO $TASK_TABLE (proj_id, name, description, assigned, started, status) VALUES ".
						"('$proj_id', '$name','$description', ".
						"'$time_string', '$time_string', '$task_status')");
	$task_id = dbLastID($dbh);

	if (isset($assigned)) {
		while (list(,$username) = each($assigned))
			dbQuery("INSERT INTO $TASK_ASSIGNMENTS_TABLE (proj_id, task_id, username) VALUES ($proj_id, $task_id, '$username')");
	}

	// redirect to the task management page (we're done)
	Header("Location: task_maint.php?proj_id=$proj_id");
} elseif ($action == "edit") {
	$name = addslashes($name);
	$description = addslashes($description);

	$query = "UPDATE $TASK_TABLE SET name='$name',description='$description',".
				" status='$task_status' ";
	if ($task_status=='Complete') {
		$query .=	",completed='$time_string'";
	}
	$query .=		" WHERE task_id=$task_id";

	list($qh,$num) = dbquery($query);

	if ($assigned) {
		dbQuery("DELETE FROM $TASK_ASSIGNMENTS_TABLE WHERE task_id = $task_id");
		while (list(,$username) = each($assigned))
			dbQuery("INSERT INTO $TASK_ASSIGNMENTS_TABLE(proj_id, task_id, username) VALUES ($proj_id, $task_id, '$username')");
	}

	// we're done so redirect to the task management page
	Header("Location: task_maint.php?proj_id=$proj_id");
} elseif ($action == 'delete') {
	dbQuery("DELETE FROM $TASK_TABLE WHERE task_id = $task_id");
	dbQuery("DELETE FROM $TASK_ASSIGNMENTS_TABLE WHERE task_id = $task_id");
	Header("Location: task_maint.php?proj_id=$proj_id");
}

// vim:ai:ts=4:sw=4
?>
