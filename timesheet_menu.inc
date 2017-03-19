<?php

//get todays values
if(!isset($realToday))
	$realToday = getdate(time());

//load local vars from superglobals
$year = isset($_REQUEST["year"]) ? $_REQUEST["year"]: $realToday["year"];
$month = isset($_REQUEST["month"]) ? $_REQUEST["month"]: $realToday["mon"];
$day = isset($_REQUEST["day"]) ? $_REQUEST["day"]: $realToday["mday"];
$proj_id = isset($_REQUEST["proj_id"]) ? $_REQUEST["proj_id"]: 0;
$task_id = isset($_REQUEST["task_id"]) ? $_REQUEST["task_id"]: 0;
$client_id = isset($_REQUEST["client_id"]) ? $_REQUEST["client_id"]: 0;

if(empty($post)) $post="year=$year&amp;month=$month&amp;day=$day&amp;proj_id=$proj_id&amp;task_id=$task_id&amp;client_id=$client_id";

if($authenticationManager->hasClearance(CLEARANCE_ADMINISTRATOR))
	if(isset($_REQUEST["contextuid"]))
		$_SESSION['contextUser'] = $_REQUEST['contextuid'];


// View mode (monthly, weekly, all)
if (isset($_REQUEST['mode']))
	$mode = $_REQUEST['mode'];
else
	$mode = "all";
if (!($mode == "all" || $mode == "monthly" || $mode == "weekly"))
	$mode = "all";

if($authenticationManager->hasClearance(CLEARANCE_ADMINISTRATOR)) {
	$commandMenu->add(new Command("<form action=\"".$_SERVER['PHP_SELF']."\" method=\"get\">",true,false));
	$commandMenu->add(new Command(
		"<input type=\"hidden\" name=\"year\" value=\"$year\" />" .
		"<input type=\"hidden\" name=\"month\" value=\"$month\" />" .
		"<input type=\"hidden\" name=\"day\" value=\"$day\" />"
	,true,false));
}

//define the command menu
$popup_href = "javascript:void(0)\" onclick=window.open(\"stopwatch.php?client_id=$client_id&proj_id=$proj_id&task_id=$task_id&destination=$_SERVER[PHP_SELF]\",\"Stopwatch\",\"location=0,directories=no,status=no,menubar=no,resizable=1,width=420,height=220\") dummy=\"";
if ($authenticationManager->hasAccess('aclStopwatch'))
	$commandMenu->add(new IconTextCommand("Timer", true, $popup_href, "images/icon_stopwatch.gif"));
if ($authenticationManager->hasAccess('aclDaily'))
	$commandMenu->add(new IconTextCommand("Daily Timesheet", true, "daily.php?$post", "images/icon_daily.gif"));
if ($authenticationManager->hasAccess('aclWeekly'))
	$commandMenu->add(new IconTextCommand("Weekly Timesheet", true, "weekly.php?$post", "images/icon_daily.gif"));
if ($authenticationManager->hasAccess('aclSimple'))
	$commandMenu->add(new IconTextCommand("Simple Timesheet", true, "simple.php?$post", "images/icon_daily.gif"));
if ($authenticationManager->hasAccess('aclMonthly'))
	$commandMenu->add(new IconTextCommand("View Month", true, "monthly.php?$post", "images/icon_calendar.gif"));
if ($authenticationManager->hasAccess('aclAbsences'))
	$commandMenu->add(new IconTextCommand("Absences", true, "absences.php?$post", "images/icon_calendar.gif"));
if ($authenticationManager->hasAccess('aclClients'))
	$commandMenu->add(new IconTextCommand("Clients", true, "client_maint.php", "images/icon_clients.gif"));
if ($authenticationManager->hasAccess('aclProjects'))
	$commandMenu->add(new IconTextCommand("Projects", true, "proj_maint.php", "images/icon_projects.gif"));
if ($authenticationManager->hasAccess('aclRates'))
	$commandMenu->add(new IconTextCommand("Project Rates", true, "project_user_rates.php", "images/icon_rates.gif"));
if ($authenticationManager->hasAccess('aclTasks'))
	$commandMenu->add(new IconTextCommand("Tasks", true, "task_maint.php", "images/icon_tasks.gif"));
if ($authenticationManager->hasAccess('aclReports'))
	$commandMenu->add(new IconTextCommand("Reports", true, "reports.php?$post", "images/icon_reports.gif"));
if ($authenticationManager->hasClearance(CLEARANCE_ADMINISTRATOR)) {
	$commandMenu->add(new IconTextCommand("Users", true, "user_maint.php", "images/icon_users.gif"));
	if ($authenticationManager->hasAccess('aclRates'))
		$commandMenu->add(new IconTextCommand("User Rates", true, "rate_maint.php", "images/icon_rates.gif"));
	$commandMenu->add(new IconTextCommand("Configuration", true, "config.php", "images/icon_configuration.gif"));
}
if (!$tsx_config->get("useLDAP"))
	$commandMenu->add(new IconTextCommand("Change Password", true, "changepwd.php", "images/icon_key.gif"));
$commandMenu->add(new IconTextCommand("Logout", true, "logout.php?logout=true", "images/icon_logout.gif"));
if($authenticationManager->hasClearance(CLEARANCE_ADMINISTRATOR)) {
	if (isset($_SESSION['contextUser'])) 
		$uid=$_SESSION['contextUser'];
	else
		$uid='unknown';
$commandMenu->add(new Command("&nbsp; &nbsp; &nbsp; <nobr>context user:&nbsp".user_select_droplist_string('contextuid',$uid)."</nobr></form>",true,false));
	//<NOBR> is not a valid tag.  Removed for now
$commandMenu->add(new Command("&nbsp; &nbsp; &nbsp; context user:&nbsp".user_select_droplist_string('contextuid',$uid)."</form>",true,false));
}

//disable yourself
$commandMenu->disableSelf();

// vim:ai:ts=4:sw=4:filetype=php
?>
