<?php
// $Header: /cvsroot/tsheet/timesheet.php/user_action.php,v 1.7 2005/04/17 12:19:31 vexil Exp $
// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
//require("debuglog.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasClearance(CLEARANCE_ADMINISTRATOR)) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=Administrator");
	exit;
}

// Connect to database.
$dbh = dbConnect();

//$debug = new logfile();

//load local vars from superglobals
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
$cloneFrom = isset($_REQUEST['cloneFrom']) ? $_REQUEST['cloneFrom'] : "";
$cloneTo = isset($_REQUEST['cloneTo']) ? $_REQUEST['cloneTo'] : "";
$projary = isset($_REQUEST['proj']) ? $_REQUEST['proj'] : array();

if($cloneFrom=="0") $cloneFrom='';
if($cloneTo=="0") $cloneTo='';

//$cfs & $cts used when printing text, when one or the other may not have been selected yet
if($cloneFrom=='') 
	$cfs="??"; 
else 
	$cfs = $cloneFrom; 
if($cloneTo=='') 
	$cts="??"; 
else 
	$cts = $cloneTo; 

if($action!='performCopy') {
	include("timesheet_menu.inc");
} else {
	$commandMenu->add(new TextCommand("Back", true, "$_SERVER[PHP_SELF]?cloneFrom=$cloneFrom&amp;cloneTo=$cloneTo"));
}

//$debug->write("status = \"$status\"  isActive=\"".$_REQUEST["isActive"]."\"\n");

include("install/table_names.inc");

?>
<div id="header">
<head>
	<title>Copy Project and Task Assignments</title> 
	<?php include ("header.inc"); ?>

	<script type="text/javascript">
	<!--
	//gets a DOM object by it's name
	function getObjectByName(sName){				
		if(document.getElementsByName){
			oElements = document.getElementsByName(sName);

			if(oElements.length > 0)
				return oElements[0];
			else
				return null;
		}
		else if(document.all)
			return document.all[sName][0];
		else if(document.layers)
			return document.layers[sName][0];
		else
			return null;
	}
		//run init function on window load
		window.onload = init;

		//apply auto-submit behaviour when changing date values
		function init() {
			var clone_from  = getObjectByName('cloneFrom');
			var clone_to    = getObjectByName('cloneTo');

			if(clone_from == null) return;
			clone_from.onchange  = function (){document.userForm.submit();};
			clone_to.onchange    = verify_can_copy;

			verify_can_copy();
		}

		function verify_can_copy() {
				var clone_from   = getObjectByName('cloneFrom');
				var clone_to     = getObjectByName('cloneTo');
				var clone_button = getObjectByName('cloneUser');

				if (clone_from.value == "0" || clone_to.value == "0" || clone_from.value == clone_to.value)
					clone_button.disabled=true;
				else
					clone_button.disabled=false;
		}


		function onClone() {
			//validation
			var clone_from  = getObjectByName('cloneFrom');
			var clone_to    = getObjectByName('cloneTo');
			if (clone_from.value == "0")
				alert("You must select a user FROM which to copy the assignments");
			else {
				if (clone_to.value == "0")
					alert("You must select a user to copy the assignments TO");
				else {
					var action = getObjectByName('action');
					action.value="performCopy";
					document.userForm.submit();
				}
			}
		}
	//-->
	</script>
</head>

<body <?php include ("body.inc"); ?> >
<?php
print "</div>";
include ("banner.inc");
//print "Action: $action<br />";
//print_r($projary);

if($action!='performCopy') {
//==========================================================================================
//we need to display the copy setup form
//==========================================================================================
?>
<form action="user_clone.php" name="userForm" method="post">
<input type="hidden" name="action" id="action" value="" />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap class="outer_table_heading">
								Copy Project and Task Assignments &nbsp; &nbsp;From: 
						<?php
							single_user_select_list('cloneFrom',$cloneFrom,'',true);
						?>
								&nbsp; &nbsp; &nbsp;To: 
						<?php
							single_user_select_list('cloneTo',$cloneTo,'',true);
						?>
						</td>
						<td align="right" nowrap class="outer_table_heading">
						<?php
							echo "&nbsp; &nbsp;<input type=\"button\" name=\"cloneUser\" value=\"Copy Assignments\" onclick=\"javascript:onClone()\" disabled=\"disabled\" class=\"bottom_panel_button\" /> ";
						?>
						</td>
					</tr>
				</table>

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_2.inc"); ?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<tr>
			<td>
				Copy selected (checked) projects and their associated task assignments from <?php echo $cfs; ?> to <?php echo $cts; ?><br />
				Any projects un-checked below will not be assigned to <?php echo $cts; ?>. (But they won't be removed if already assigned to them.)<br />
				<table width="100%" border="1" cellspacing="0" cellpadding="3" class="table_body">
					<tr class="inner_table_head">
						<td>&nbsp;</td>
						<td class="inner_table_column_heading">Project</td>
						<td class="inner_table_column_heading">Tasks</td>
					</tr>
<?php
	function get_task_name($task_id) {
		include("table_names.inc");

		$sql = "SELECT name FROM $TASK_TABLE WHERE task_id=$task_id";
		list($my_qh, $num) = dbQuery($sql);
		$result = dbResult($my_qh);
		return $result['name'];
	}

	$proj_list = array();
	global $TASK_ASSIGNMENTS_TABLE, $ASSIGNMENTS_TABLE;
	list($qh,$num) = dbQuery(" SELECT * FROM $ASSIGNMENTS_TABLE WHERE username = '$cloneFrom' AND  proj_id!=1");
	while ($data = dbResult($qh)) {
		$p_id=$data['proj_id'];
		$p_name=get_project_name($p_id);
		$proj_list[$p_name]=$p_id;
	}
	ksort($proj_list);

	foreach($proj_list as $p_name => $p_id) {
		print "<tr><td><input type=\"checkbox\" name=\"proj[]\" id=\"proj_$p_id\" value=\"$p_id\" CHECKED />&nbsp;</td>\n";
		//print "<tr><td><input type=\"checkbox\" name=\"proj[]\" id=\"proj_$p_id\" value=\"$p_id\"";
		//if(in_array($p_id,$projary)) echo ' CHECKED';
		//print " />&nbsp;</td>\n";

		print "<td>$p_name</td><td>";

		$sql = "SELECT * FROM  $TASK_ASSIGNMENTS_TABLE WHERE username ='$cloneFrom' AND task_id!=1 AND proj_id=$p_id";
		list($qh2,$num2) = dbQuery($sql);

		$ntasks=0;
		while ($data2 = dbResult($qh2)) {
			$t_id=$data2['task_id'];
			$t_name=get_task_name($t_id);
			if($ntasks>0)
				print ", ";
			print "$t_name";
			$ntasks++;
		}
		print "</td></tr>\n";
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
} else {  
//==========================================================================================
//we need to actually perform the copy
//==========================================================================================
?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap class="outer_table_heading">
							Copying Project and Task Assignments &nbsp; &nbsp;From: 
							<?php echo $cloneFrom; ?>
							&nbsp; &nbsp; &nbsp;To: 
							<?php echo $cloneTo; ?>
						</td>
					</tr>
				</table>

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_2.inc"); ?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<tr>
			<td>
				Copying the selected projects and their associated task assignments from <?php echo $cloneFrom; ?> to <?php echo $cloneTo; ?><br />
				Project op (P op) = means project already existed for the user, + means project was added<br />
				Tasks ignored means user was already assigned that task, added means those tasks were assigned to the user
				<table width="100%" border="1" cellspacing="0" cellpadding="3" class="table_body">
					<tr class="inner_table_head">
						<td class="inner_table_column_heading">Project</td>
						<td class="inner_table_column_heading">P op</td>
						<td class="inner_table_column_heading">Tasks</td>
					</tr>
<?php
	function get_task_name($task_id) {
		include("install/table_names.inc");

		$sql = "SELECT name FROM $TASK_TABLE WHERE task_id=$task_id";
		list($my_qh, $num) = dbQuery($sql);
		$result = dbResult($my_qh);
		return $result['name'];
	}

	$proj_list = array();
	global $TASK_ASSIGNMENTS_TABLE, $ASSIGNMENTS_TABLE;
	list($qh,$num) = dbQuery(" SELECT * FROM $ASSIGNMENTS_TABLE WHERE username = '$cloneTo' AND  proj_id!=1");
	while ($data = dbResult($qh)) {
		$p_id=$data['proj_id'];
		$proj_list[]=$p_id;
	}

	foreach($projary as $p_id) {
		$task_list = array();
		$cf_task_list = array();
		$ignored=0;
		$added=0;
		$ignstr='';
		$addstr='';
		$p_name = get_project_name($p_id);
		print "<tr><td>$p_name</td><td> ";
		if(in_array($p_id,$proj_list)) { //if user is already a member of the project, we must check task assignments...
			$sql = "SELECT * FROM  $TASK_ASSIGNMENTS_TABLE WHERE username ='$cloneTo' AND proj_id=$p_id";
			list($qh,$num) = dbQuery($sql);
			while ($data = dbResult($qh)) {
				$t_id=$data['task_id'];
				$task_list[]=$t_id;
			}
			print "=</td><td>";
		} else {
			//dbquery("INSERT INTO $ASSIGNMENTS_TABLE VALUES ('$p_id','$cloneTo', 1)");
			print "+</td><td>";
		}

		$sql = "SELECT * FROM  $TASK_ASSIGNMENTS_TABLE WHERE username ='$cloneFrom' AND proj_id=$p_id";
		list($qh,$num) = dbQuery($sql);
		while ($data = dbResult($qh)) {
			$t_id=$data['task_id'];
			$cf_task_list[]=$t_id;
		}

		foreach($cf_task_list as $t_id) {
			if(in_array($t_id,$task_list)) {
				if($ignored>0)
					$ignstr.=", ";
				$ignored++;
				$ignstr .= get_task_name($t_id);
			} else {
				//dbquery("INSERT INTO $TASK_ASSIGNMENTS_TABLE VALUES ('$t_id','$cloneTo','$p_id')");
				if($added>0)
					$addstr.=", ";
				$added++;
				$addstr .= get_task_name($t_id);
			}
		}
		print "ignored $ignored: $ignstr<br />";
		print "added &nbsp; $added: $addstr</td>";
		print "</tr>";
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
} 
//==========================================================================================
//  End of form or perform copy if statement
//==========================================================================================

echo "<div id=\"footer\">"; 
include ("footer.inc"); 
echo "</div>";
?>
</body>
</HTML>
<?php
// vim:ai:ts=4:sw=4
?>
