<?php
// $Header: /cvsroot/tsheet/timesheet.php/task_maint.php,v 1.11 2005/05/17 03:38:37 vexil Exp $
// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclTasks')) {
        Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclTasks'));
        exit;
}

// Connect to database.
$dbh = dbConnect();

//define the command menu & we get these variables from $_REQUEST:
//  $month $day $year $client_id $proj_id $task_id
include("timesheet_menu.inc");

$contextUser = strtolower($_SESSION['contextUser']);
$assignTasks = isset($_REQUEST["assignTasks"]) ? $_REQUEST["assignTasks"]: false;

function do_query($sql) {
	$result = mysql_query($sql);
	if(!$result) {
		print "Query failed: \"$sql\"\n";
		print get_db_error(mysql_error())."\n";
		return false;
	}
	return $result;
}

?>

<html>
<head>
	<title>Explain Assign all Tasks</title>
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
					<td align="center" nowrap>
						<h2>Explaination of Assign all Tasks to all Project Members</h2>
					</td>
				</tr>
			</table>

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_2.inc"); ?>


			<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
				<tr>
					<td width="40">&nbsp;</td>
					<td>
Ok, suppose we have 15 tasks each for 20 projects.  I don't know about you, but I'm not going to enjoy assigning 15 tasks for each project that a user may be assigned to. So, I wrote a script that would just assign all 15 tasks to each user for every project they were assigned to, and the GUI interface on the last page allows you to run that script.<br /><br />
If that is still not clear enough, suppose we have the following: 
<table><tr><td>
Three Projects
<ol><li> Project A</li>
<li> Project B</li>
<li>Project C</li> 
</ol>
</td>
<td width="40">&nbsp; </td><td>
Each Project has 3 tasks
<ol><li> Task 1</li>
<li> Tast 2</li>
<li> Task 3</li> 
</td>
</tr>
</table>
We then assign a new user to projects A & C. Using this script, 6 tasks, tasks 1-3 for project A & tasks 1-3 for project C, will be assigned to the new user.<br /> 
<b>But it will also assign every other user all the tasks for all the projects they are a part of as well.</b><br /><br />
Now, if you need to, or have ever selectively assigned tasks to users, ie. not allowed people the option of logging time against certain tasks even though they are members of the project, then you do <b>NOT want to EVER</b> run this script.<br /><br />
Short of backing up the database, and then restoring said database, there is no way to undo this once it is run.
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
