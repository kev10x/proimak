<?php
// $Header: /cvsroot/tsheet/timesheet.php/config_action.php,v 1.6 2005/02/03 08:06:10 vexil Exp $
// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
//require("debuglog.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasClearance(CLEARANCE_ADMINISTRATOR)) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=Administrator");
	exit;
}

//load local vars from request/post/get
$action = $_REQUEST["action"];
$headerhtml = isset($_REQUEST["headerhtml"]) ? $_REQUEST["headerhtml"]: "";
$bodyhtml = isset($_REQUEST["bodyhtml"]) ? $_REQUEST["bodyhtml"]: "";
$footerhtml = isset($_REQUEST["footerhtml"]) ? $_REQUEST["footerhtml"]: "";
$errorhtml = isset($_REQUEST["errorhtml"]) ? $_REQUEST["errorhtml"]: "";
$bannerhtml = isset($_REQUEST["bannerhtml"]) ? $_REQUEST["bannerhtml"]: "";
$tablehtml = isset($_REQUEST["tablehtml"]) ? $_REQUEST["tablehtml"]: "";
$locale = isset($_REQUEST["locale"]) ? $_REQUEST["locale"]: "";
$timezone = isset($_REQUEST["timezone"]) ? $_REQUEST["timezone"]: "";
$timeformat= isset($_REQUEST["timeformat"]) ? $_REQUEST["timeformat"]: "";
$projectItemsPerPage = isset($_REQUEST["projectItemsPerPage"]) ? $_REQUEST["projectItemsPerPage"]: "";
$taskItemsPerPage = isset($_REQUEST["taskItemsPerPage"]) ? $_REQUEST["taskItemsPerPage"]: "";
$headerReset = isset($_REQUEST["headerReset"]) ? $_REQUEST["headerReset"]: false;
$bodyReset = isset($_REQUEST["bodyReset"]) ? $_REQUEST["bodyReset"]: false;
$footerReset = isset($_REQUEST["footerReset"]) ? $_REQUEST["footerReset"]: false;
$errorReset = isset($_REQUEST["errorReset"]) ? $_REQUEST["errorReset"]: false;
$bannerReset = isset($_REQUEST["bannerReset"]) ? $_REQUEST["bannerReset"]: false;
$tableReset = isset($_REQUEST["tableReset"]) ? $_REQUEST["tableReset"]: false;
$localeReset = isset($_REQUEST["localeReset"]) ? $_REQUEST["localeReset"]: false;
$aclReset = isset($_REQUEST["aclReset"]) ? $_REQUEST["aclReset"]: false;
$timezoneReset = isset($_REQUEST["timezoneReset"]) ? $_REQUEST["timezoneReset"]: false;
$timeformatReset = isset($_REQUEST["timeformatReset"]) ? $_REQUEST["timeformatReset"]: false;
$projectItemsPerPageReset = isset($_REQUEST["projectItemsPerPageReset"]) ? $_REQUEST["projectItemsPerPageReset"]: false;
$taskItemsPerPageReset = isset($_REQUEST["taskItemsPerPageReset"]) ? $_REQUEST["taskItemsPerPageReset"]: false;
$useLDAP = isset($_REQUEST["useLDAP"]) ? $_REQUEST["useLDAP"]: false;
$LDAPFallback = isset( $_REQUEST["LDAPFallback"] ) ? $_REQUEST["LDAPFallback"]: false;
$LDAPReferrals = isset( $_REQUEST["LDAPReferrals"] ) ? $_REQUEST["LDAPReferrals"]: false;
$LDAPScheme = $_REQUEST["LDAPScheme"];
$LDAPHost = $_REQUEST["LDAPHost"];
$LDAPPort = $_REQUEST["LDAPPort"];
$LDAPBaseDN = $_REQUEST["LDAPBaseDN"];
$LDAPUsernameAttribute = $_REQUEST["LDAPUsernameAttribute"];
$LDAPSearchScope = $_REQUEST["LDAPSearchScope"];
$LDAPFilter = $_REQUEST["LDAPFilter"];
$LDAPProtocolVersion = $_REQUEST["LDAPProtocolVersion"];
$LDAPBindByUser = isset($_REQUEST["LDAPBindByUser"]) ? $_REQUEST["LDAPBindByUser"]: false;
$LDAPBindUsername = $_REQUEST["LDAPBindUsername"];
$LDAPBindPassword = $_REQUEST["LDAPBindPassword"];
$weekstartday = isset($_REQUEST["weekstartday"]) ? $_REQUEST["weekstartday"]: 0;
$weekStartDayReset = isset($_REQUEST["weekStartDayReset"]) ? $_REQUEST["weekStartDayReset"]: false;
$aclStopwatch = $_REQUEST["aclStopwatch"];
$aclDaily = $_REQUEST["aclDaily"];
$aclWeekly = $_REQUEST["aclWeekly"];
$aclMonthly = $_REQUEST["aclMonthly"];
$aclSimple = $_REQUEST["aclSimple"];
$aclClients = $_REQUEST["aclClients"];
$aclProjects = $_REQUEST["aclProjects"];
$aclTasks = $_REQUEST["aclTasks"];
$aclReports = $_REQUEST["aclReports"];
$aclRates = $_REQUEST["aclRates"];
$aclAbsences = $_REQUEST["aclAbsences"];
$simpleTimesheetLayout = $_REQUEST["simpleTimesheetLayout"];
$startPage = $_REQUEST["startPage"];

//$debug->write("startPage is $startPage\n");

if (!isset($action)) {
	Header("Location: $HTTP_REFERER");
}
elseif ($action == "edit") {
	$headerhtml = mysql_real_escape_string(trim($headerhtml));
	$bodyhtml = mysql_real_escape_string(trim($bodyhtml));
	$footerhtml = mysql_real_escape_string(trim($footerhtml));
	$errorhtml = mysql_real_escape_string(trim($errorhtml));
	$bannerhtml = mysql_real_escape_string(trim($bannerhtml));
	$tablehtml = mysql_real_escape_string(trim($tablehtml));
	$locale = mysql_real_escape_string(trim($locale));
	$timezone = mysql_real_escape_string(trim($timezone));
	$projectItemsPerPage = mysql_real_escape_string(trim($projectItemsPerPage));
	$taskItemsPerPage = mysql_real_escape_string(trim($taskItemsPerPage));

	// now change values in new configuration table

	$tsx_config->set("headerhtml", $headerhtml);
	$tsx_config->set("bodyhtml", $bodyhtml);
	$tsx_config->set("footerhtml", $footerhtml);
	$tsx_config->set("errorhtml", $errorhtml);
	$tsx_config->set("bannerhtml", $bannerhtml);
	$tsx_config->set("tablehtml", $tablehtml);
	$tsx_config->set("locale", $locale);
	$tsx_config->set("timezone", $timezone);
	$tsx_config->set("timeformat", $timeformat);
	$tsx_config->set("weekstartday", $weekstartday);
	$tsx_config->set("project_items_per_page", $projectItemsPerPage);
	$tsx_config->set("task_items_per_page", $taskItemsPerPage);
	$tsx_config->set("useLDAP", $useLDAP);
	$tsx_config->set("LDAPScheme", $LDAPScheme);
	$tsx_config->set("LDAPHost", $LDAPHost);
	$tsx_config->set("LDAPPort", $LDAPPort);
	$tsx_config->set("LDAPBaseDN", $LDAPBaseDN);
	$tsx_config->set("LDAPUsernameAttribute", $LDAPUsernameAttribute);
	$tsx_config->set("LDAPSearchScope", $LDAPSearchScope);
	$tsx_config->set("LDAPFilter", $LDAPFilter);
	$tsx_config->set("LDAPProtocolVersion", $LDAPProtocolVersion);
	$tsx_config->set("LDAPBindUsername", $LDAPBindUsername);
	$tsx_config->set("LDAPBindPassword", $LDAPBindPassword);
	$tsx_config->set("LDAPBindByUser", $LDAPBindByUser);
	$tsx_config->set("LDAPReferrals", $LDAPReferrals);
	$tsx_config->set("LDAPFallback", $LDAPFallback);
	$tsx_config->set("aclStopwatch", $aclStopwatch);
	$tsx_config->set("aclDaily", $aclDaily);
	$tsx_config->set("aclWeekly", $aclWeekly);
	$tsx_config->set("aclMonthly", $aclMonthly);
	$tsx_config->set("aclSimple", $aclSimple);
	$tsx_config->set("aclClients", $aclClients);
	$tsx_config->set("aclProjects", $aclProjects);
	$tsx_config->set("aclTasks", $aclTasks);
	$tsx_config->set("aclReports", $aclReports);
	$tsx_config->set("aclRates", $aclRates);
	$tsx_config->set("aclAbsences", $aclAbsences);
	$tsx_config->set("SimpleTimesheetLayout", $simpleTimesheetLayout);
	$tsx_config->set("startPage", $startPage);

	//$debug->write("$query\n");

	if ($headerReset == true)
		$tsx_config->set("headerhtml", '<meta name="description" content="Timesheet Next Gen">\r\n<link href="css/timesheet.css" rel="stylesheet" type="text/css">\r\n<link rel="shortcut icon" href="images/favicon.ico">');
	if ($bodyReset == true)
		$tsx_config->set("bodyhtml", 'link=\"#004E8A\" vlink=\"#171A42\"');
	if ($footerReset == true)
		$tsx_config->set("footerhtml", '<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\r\n<tr><td style=\"background-color: #000788; padding: 3;\" class=\"bottom_bar_text\" align=\"center\">\r\nTimesheetNextGen\r\n<br /><span style=\"font-size: 9px;\"><b>Page generated %time% %date% (%timezone% time)</b></span>\r\n</td></tr></table>');
	if ($errorReset == true)
		$tsx_config->set("errorhtml", '<table border=0 cellpadding=5 width=\"100%\">\r\n<tr>\r\n  <td><font size=\"+2\" color=\"red\">%errormsg%</font></td>\r\n</tr></table>\r\n<p>Please go <a href=\"javascript:history.back()\">Back</a> and try again.</p>');
	if ($bannerReset == true)
		$tsx_config->set("bannerhtml", '<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>\r\n<td colspan=\"2\" style=\"background-image: url(\'images/timesheet_background_pattern.gif\');\"><img class=\"img-responsive\" src=\"images/timesheet_banner.gif\" alt=\"Timesheet Banner\" /></td>\r\n</tr><tr>\r\n<td style=\"background-color: #fff; padding: 3;\">%commandmenu%</td>\r\n<td style=\"background-color: #F2F3FF; padding: 3;\" align=\"right\" width=\"145\" valign=\"top\">You are logged in as %username%</td>\r\n</tr><tr>\r\n<td colspan=\"2\" height=\"1\" style=\"background-color: #758DD6;\"><img src=\"images/spacer.gif\" alt=\"\" width=\"1\" height=\"1\" /></td>\r\n</tr></table>');
	if ($tableReset == true)
		$tsx_config->set("tablehtml", '');
	if ($localeReset == true)
		$tsx_config->set("locale", 'C');
	if ($timezoneReset == true)
		$tsx_config->set("timezone", 'Europe/Zurich');
	if ($timeformatReset == true)
		$tsx_config->set("timeformat", '12');
	if ($weekStartDayReset == true)
		$tsx_config->set("weekstartday", '1');
	if ($projectItemsPerPageReset == true)
		$tsx_config->set("project_items_per_page", '10');
	if ($taskItemsPerPageReset == true)
		$tsx_config->set("task_items_per_page", '10');
	if ($aclReset == true)
	{
		$tsx_config->set("aclStopwatch", 'Basic');
		$tsx_config->set("aclDaily", 'Basic');
		$tsx_config->set("aclWeekly", 'Basic');
		$tsx_config->set("aclMonthly", 'Basic');
		$tsx_config->set("aclSimple", 'Basic');
		$tsx_config->set("aclClients", 'Basic');
		$tsx_config->set("aclProjects", 'Basic');
		$tsx_config->set("aclTasks", 'Basic');
		$tsx_config->set("aclReports", 'Basic');
		$tsx_config->set("aclRates", 'Basic');
		$tsx_config->set("aclAbsences", 'Basic');
	}
}

//return to the config.php page
Header("Location: config.php");

// vim:ai:ts=4:sw=4
?>
