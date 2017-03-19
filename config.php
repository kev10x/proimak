<?php
// $Header: /cvsroot/tsheet/timesheet.php/config.php,v 1.8 2005/02/03 08:06:10 vexil Exp $
// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasClearance(CLEARANCE_ADMINISTRATOR)) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=Administrator");
	exit;
}

// Connect to database.
$dbh = dbConnect();

//define the command menu & we get these variables from $_REQUEST:
//  $month $day $year $client_id $proj_id $task_id
include("timesheet_menu.inc");

$contextUser = strtolower($_SESSION['contextUser']);

//Get the result set for the config
$resultset = array();
list($qh, $num) = dbQuery("SELECT * FROM $CONFIG_TABLE");
for ($i = 0; $i < $num; $i++) {
	$resultrow = dbResult($qh, $i);
	$resultset[$resultrow[0]] = $resultrow[1];
}

?>
<html>
<head>
<title>Timesheet Configuration Parameters</title>
<?php
include ("header.inc");
?>
</head>
<script type="text/javascript">

//store the current LDAP entry method in this variable
var currentLDAPEntryMethod = 'normal';

function onChangeLDAPEntryMethod() {
	if (document.configurationForm.LDAPEntryMethod.value == 'normal') {
		document.getElementById('normalLDAPEntry').style.display='block';
		document.getElementById('advancedLDAPEntry').style.display='none';
	}
	else {
		document.getElementById('normalLDAPEntry').style.display='none';
		document.getElementById('advancedLDAPEntry').style.display='block';
	}

	//copy data from one to the other when it changes
	if (currentLDAPEntryMethod == 'normal' && document.configurationForm.LDAPEntryMethod.value != 'normal')
		buildLDAPUrlFromForm();
	else if (currentLDAPEntryMethod != 'normal' && document.configurationForm.LDAPEntryMethod.value == 'normal')
		fillOutLDAPFieldsFromUrl();

	//update the current LDAP entry method variable
	currentLDAPEntryMethod = document.configurationForm.LDAPEntryMethod.value;
}

function enableLDAP(value) {
	document.getElementById('LDAPEntryMethod').disabled = !value;
	document.getElementById('LDAPScheme').disabled = !value;
	document.getElementById('LDAPHost').disabled = !value;
	document.getElementById('LDAPPort').disabled = !value;
	document.getElementById('LDAPBaseDN').disabled = !value;
	document.getElementById('LDAPUsernameAttribute').disabled = !value;
	document.getElementById('LDAPSearchScope').disabled = !value;
	document.getElementById('LDAPFilter').disabled = !value;
	document.getElementById('LDAPUrl').disabled = !value;
	document.getElementById('LDAPProtocolVersion').disabled = !value;
	document.getElementById('LDAPBindByUser').disabled = !value;
	document.getElementById('LDAPBindUsername').disabled = !value;
	document.getElementById('LDAPBindPassword').disabled = !value;
	document.getElementById('LDAPReferrals').disabled = !value;
	document.getElementById('LDAPFallback').disabled = !value;
}

function buildLDAPUrlFromDb() {
	//get values from database
	var scheme = '<?php echo $resultset['LDAPScheme']; ?>';
	var host = '<?php echo $resultset['LDAPHost']; ?>';
	var port = '<?php echo $resultset['LDAPPort']; ?>';
	var baseDN = '<?php echo $resultset['LDAPBaseDN']; ?>';
	var usernameAttribute = '<?php echo $resultset['LDAPUsernameAttribute']; ?>';
	var searchScope = '<?php echo $resultset['LDAPSearchScope']; ?>';
	var filter = '<?php echo $resultset['LDAPFilter']; ?>';

	buildLDAPUrl(scheme, host, port, baseDN, usernameAttribute, searchScope, filter);
}

function buildLDAPUrlFromForm() {
	buildLDAPUrl(
		document.getElementById('LDAPScheme').value,
		document.getElementById('LDAPHost').value,
		document.getElementById('LDAPPort').value,
		document.getElementById('LDAPBaseDN').value,
		document.getElementById('LDAPUsernameAttribute').value,
		document.getElementById('LDAPSearchScope').value,
		document.getElementById('LDAPFilter').value);
}

function buildLDAPUrl(scheme, host, port, baseDN, usernameAttribute, searchScope, filter) {
	//fill out defaults for those which are empty
	if (scheme == '')
		scheme = 'ldaps';
	if (host == '')
		host = 'localhost';
	if (port == '')
		port = 389;
	if (baseDN == '')
		baseDN = 'dc=yourOrganisation, dc=com, ou=yourOrganisationalUnit';
	if (usernameAttribute == '')
		usernameAttribute = 'uid';
	if (searchScope == '')
		searchScope = 'base';

	//combine into one string
	var url = scheme + '://' + host + ':' + port + '/' + baseDN + '?' + usernameAttribute + '?'
		+ searchScope;

	if (filter != '')
		url += '?' + filter;

	//set in the form
	document.getElementById('LDAPUrl').value = url;
}

function fillOutLDAPFieldsFromUrl() {

	//get the url from the form
	var url = document.getElementById('LDAPUrl').value;

	if (url.indexOf('ldaps') == 0)
		document.getElementById('LDAPScheme').selectedIndex = 1;
	else
		document.getElementById('LDAPScheme').selectedIndex = 0;

	//find the host
	var pos1 = url.indexOf('://') + 2;
	if (pos1 == -1)
		return false;
	var pos2 = url.indexOf(':', pos1+1);
	if (pos2 == -1)
		return;
	document.getElementById('LDAPHost').value = url.substring(pos1+1, pos2);

	//find the port
	var pos3 = url.indexOf('/', pos2+1);
	if (pos3 == -1)
		return false;
	document.getElementById('LDAPPort').value = url.substring(pos2+1, pos3);

	//find the base dn
	var pos4 = url.indexOf('?', pos3+1);
	if (pos4 == -1)
		return false;
	document.getElementById('LDAPBaseDN').value = url.substring(pos3+1, pos4);

	//find the username attribute
	var pos5 = url.indexOf('?', pos4+1);
	if (pos5 == -1)
		return false;
	document.getElementById('LDAPUsernameAttribute').value = url.substring(pos4+1, pos5);

	//find the search scope
	var pos6 = url.indexOf('?', pos5+1);
	if (pos6 == -1)
		pos6 = url.length;
	var searchScope = url.substring(pos5+1, pos6);
	if (searchScope == 'one')
		document.getElementById('LDAPSearchScope').selectedIndex = 1;
	else if (searchScope == 'sub')
		document.getElementById('LDAPSearchScope').selectedIndex = 2;
	else
		document.getElementById('LDAPSearchScope').selectedIndex = 0;
	if (pos6 == -1)
		return true;

	//the filter
	document.getElementById('LDAPFilter').value = url.substring(pos6+1, url.length);
	return true;
}

function onSubmit() {
	if (document.configurationForm.LDAPEntryMethod.value != 'normal') {
		if (!fillOutLDAPFieldsFromUrl()) {
			alert('There was an error parsing the LDAP Url. Please correct it and try again.');
			return;
		}
	}

	if (document.getElementById('useLDAPCheck').checked)
		document.getElementById('useLDAP').value = 1;
	else
		document.getElementById('useLDAP').value = 0;

	if (document.getElementById('LDAPBindByUsercheck').checked)
		document.getElementById('LDAPBindByUser').value = 1;
	else
		document.getElementById('LDAPBindByUser').value = 0;

	if (document.getElementById('LDAPReferralsCheck').checked)
		document.getElementById('LDAPReferrals').value = 1;
	else
		document.getElementById('LDAPReferrals').value = 0;

	if (document.getElementById('LDAPFallbackCheck').checked)
		document.getElementById('LDAPFallback').value = 1;
	else
		document.getElementById('LDAPFallback').value = 0;

	//re-enable the fields just before submitting because otherwise they are not send in mozilla
	enableLDAP(true);

	//submit the form
	document.configurationForm.submit();
}

</script>
<body <?php include ("body.inc"); ?> onload="enableLDAP(<?php echo $resultset["useLDAP"]?>);">
<?php
include ("banner.inc");
?>
<form action="config_action.php" name="configurationForm" method="post">
<input type="hidden" name="action" value="edit" />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" class="face_padding_cell">

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_1.inc"); ?>

				<table width="100%" border="0">
					<tr>
						<td align="left" nowrap class="outer_table_heading" nowrap>
							Configuration Parameters:
						</td>
						<td align="right">
							<input type="button" value="Save Changes" name="submitButton" id="submitButton" onclick="onSubmit();" />
						</td>
					</tr>
					<tr>
						<td>
						This form allows you to change the basic operating parameters of TimesheetNextGen.
						Please be careful here, as errors may cause pages not to display properly.
						Somewhere in one of these, you should include the placeholder %commandMenu%.
						This is where TimesheetNextGen will place the menu options.
						</td>
					</tr>
				</table>

<!-- include the timesheet face up until the heading start section -->
<?php include("timesheet_face_part_2.inc"); ?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<tr>
			<td>
				<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_body">
					<tr>
						<td>

				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">


			<!-- LDAP configurationForm -->
			<tr>
				<td align="left" valign="top">
					<b>LDAP</b>:
				</td>
				<td align="left" width="100%">
					<input type="checkbox" name="useLDAPCheck" id="useLDAPCheck" onclick="enableLDAP(this.checked);" <?php if ($resultset['useLDAP'] == 1) echo "checked=\"checked\""; ?> />Use LDAP for authentication
					<input type="hidden" name="useLDAP" id="useLDAP" />
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">&nbsp;</td>
				<td align="left" width="100%">
					<fieldset>
						<legend>Connection Details</legend>
						<table width="100%">
							<tr>
								<td>
									<b>&nbsp;Data entry style:</b>
									<select id="LDAPEntryMethod" name="LDAPEntryMethod" onChange="onChangeLDAPEntryMethod();">
										<option value="normal" selected="selected">Normal</option>
										<option value="advanced">RFC 2255 URL</option>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									<div id="normalLDAPEntry">
										<table width="100%" cellpadding="2">
											<tr>
												<td colspan="3">
													<span class="label">Scheme:</span>
													<select id="LDAPScheme" name="LDAPScheme">
														<option value="ldap" <?php if ($resultset["LDAPScheme"] == "ldap") echo "selected=\"selected\"";?>>LDAP</option>
														<option value="ldaps" <?php if ($resultset["LDAPScheme"] == "ldaps") echo "selected=\"selected\"";?>>LDAPS</option>
													</select>
													(LDAP=Non SSL, LDAPS=Use SSL)
												</td>
											</tr>
											<tr>
												<td width="50%">
													<span class="label">Host:</span>
													<input id="LDAPHost" name="LDAPHost" type="text" value="<?php echo $resultset['LDAPHost']; ?>" style="width:100%;" />
												</td>
												<td width="20">&nbsp;</td>
												<td width="50%">
													<span class="label">Port:</span>
													<input id="LDAPPort" name="LDAPPort" type="text" size="10" maxlength="10" value="<?php echo $resultset['LDAPPort']; ?>" />
												</td>
											</tr>
											<tr>
												<td colspan="3">
													<table width="100%" cellpadding="0" cellspacing="0">
														<tr>
															<td nowrap>
																<span class="label" nowrap>LDAP search base (Distinguished Name):</span>
															</td>
															<td>&nbsp;</td>
															<td width="100%">
																<input id="LDAPBaseDN" type="text" name="LDAPBaseDN" value="<?php echo $resultset["LDAPBaseDN"]; ?>" style="width:100%;" />
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td colspan="3">
													<table width="100%" cellpadding="0" cellspacing="0">
														<tr>
															<td nowrap>
																<span class="label" nowrap>Username attribute to query:</span>
															</td>
															<td>&nbsp;</td>
															<td width="100%">
																<input id="LDAPUsernameAttribute" name="LDAPUsernameAttribute" type="text" value="<?php echo $resultset["LDAPUsernameAttribute"]; ?>" size="30"/>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td colspan="3">
													<table width="100%" cellpadding="0" cellspacing="0">
														<tr>
															<td nowrap>
																<span class="label" nowrap>Search scope:</span>
															</td>
															<td>&nbsp;</td>
															<td width="100%">
																<select id="LDAPSearchScope" name="LDAPSearchScope">
																	<option value="base" <?php if ($resultset["LDAPSearchScope"] == "base") echo "selected=\"selected\""; ?>>Base DN search only (LDAPRead)</option>
																	<option value="one" <?php if ($resultset["LDAPSearchScope"] == "one") echo "selected=\"selected\""; ?>>One level search (LDAPList)</option>
																	<option value="sub" <?php if ($resultset["LDAPSearchScope"] == "sub") echo "selected=\"selected\""; ?>>Full sub-tree search (LDAPSearch)</option>
																</select>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td colspan="3">
													<table width="100%" cellpadding="0" cellspacing="0">
														<tr>
															<td nowrap>
																<span class="label" nowrap>Filter:</span>
															</td>
															<td>&nbsp;</td>
															<td width="100%">
																<input id="LDAPFilter" type="text" name="LDAPFilter" value="<?php echo $resultset["LDAPFilter"]; ?>" style="width:100%;" />
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td colspan="3">
													<table width="100%" cellpadding="0" cellspacing="0">
														<tr>
															<td nowrap>
																<span class="label" nowrap>Protocol Version:</span>
															</td>
															<td>&nbsp;</td>
															<td width="100%">
																<select id="LDAPProtocolVersion" name="LDAPProtocolVersion">
																	<option value="3" <?php if ($resultset["LDAPProtocolVersion"] == "3") echo "selected=\"selected\""; ?>>3</option>
																	<option value="2" <?php if ($resultset["LDAPProtocolVersion"] == "2") echo "selected=\"selected\""; ?>>2</option>
																	<option value="1" <?php if ($resultset["LDAPProtocolVersion"] == "1") echo "selected=\"selected\""; ?>>1</option>
																</select>
															</td>
															<td nowrap>
																<span class="label" nowrap>Use LDAP Referrals:</span>
															</td>
															<td>&nbsp;</td>
															<td width="100%">
																<input type="checkbox" name="LDAPReferralsCheck" id="LDAPReferralsCheck" <?php if ( $resultset['LDAPReferrals'] == 1 ) echo "checked=\"checked\""; ?> />
																<input type="hidden" name="LDAPReferrals" id="LDAPReferrals" />
															</td>
															<td>&nbsp;</td>
															<td nowrap>
																<span class="label" nowrap>Fallback to local Authentication on fail:</span>
															</td>
															<td>&nbsp;</td>
															<td width="100%">
																<input type="checkbox" name="LDAPFallbackCheck" id="LDAPFallbackCheck" <?php if ( $resultset['LDAPFallback'] == 1 ) echo "checked=\"checked\""; ?> />
																<input type="hidden" name="LDAPFallback" id="LDAPFallback" />
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td colspan="3">
													<table width="100%" cellpadding="0" cellspacing="0">
														<tr>
															<td nowrap>
																<span class="label_grey" nowrap><i>The following fields are normally only required for Microsoft's Active Directory LDAP Server:</i></span>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td colspan="3">
													<table width="100%" cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td width="50%">
																<table width="100%" cellpadding="0" cellspacing="0" border="0">
																	<tr>
																		<td nowrap>
																			<span class="label" nowrap>Use LDAP by user authentication:</span>
																		</td>
																		<td width="5">&nbsp;</td>
																		<td width="100%">
																			<input type="checkbox" name="LDAPBindByUsercheck" id="LDAPBindByUsercheck" <?php if ($resultset['LDAPBindByUser'] == 1) echo "checked=\"checked\""; ?> />
																			<input type="hidden" name="LDAPBindByUser" id="LDAPBindByUser" />
																		</td>
																	</tr>
																</table>
																<table width="100%" cellpadding="0" cellspacing="0" border="0">
																	<tr>
																		<td nowrap>
																			<span class="label" nowrap>Bind Username:</span>
																		</td>
																		<td width="5">&nbsp;</td>
																		<td width="50%">
																			<input id="LDAPBindUsername" type="text" name="LDAPBindUsername" value="<?php echo $resultset["LDAPBindUsername"]; ?>" style="width:100%;" />
																		</td>
																		<td nowrap>
																			<span class="label" nowrap>&nbsp;&nbsp;&nbsp;Bind Password:</span>
																		</td>
																		<td width="5">&nbsp;</td>
																		<td width="50%">
																			<input id="LDAPBindPassword" type="password" name="LDAPBindPassword" value="<?php echo $resultset["LDAPBindPassword"]; ?>" style="width:100%;" AUTOCOMPLETE="OFF" />
																		</td>
																	</tr>
																</table>
															</td>
														</tr>
													</table>
												</td>
											</tr>

										</table>
									</div>
									<div id="advancedLDAPEntry" style="display:none;">
										<table width="100%" cellpadding="0">
											<tr>
												<td colspan="3">
													<table width="100%" cellpadding="0" cellspacing="0">
														<tr>
															<td nowrap>
																<span class="label" nowrap>RFC 2255 URL:</span>
															</td>
															<td>&nbsp;</td>
															<td width="100%">
																<input id="LDAPUrl" name="LDAPUrl" type="text" value="" style="width:100%;" />
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</div>
								</td>
							</tr>
						</table>
					</fieldset>
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

		<!-- ACL-->
			<tr>
				<td align="left" valign="top">
					<b>ACL</b>:
				</td>
				<td align="left" width="100%">
					The ACL defines the access given to pages for the different user roles defined. It is possible to disable a page by giving no access.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="aclReset" value="off" valign="absmiddle" />Reset
				</td>
				<td align="left" width="100%">
				<?php
         /**
    		  *@todo - find a way to replace the nobr tags whilst keeping the menu icons next to the names
    		  */       	        
        ?>
					<nobr><span class="label" nowrap>Stopwatch:</span><?php acl_select_droplist("aclStopwatch", $resultset["aclStopwatch"]); ?>&nbsp;</nobr>
					<nobr><span class="label" nowrap>Daily:</span><?php acl_select_droplist("aclDaily", $resultset["aclDaily"]); ?>&nbsp;</nobr>
					<nobr><span class="label" nowrap>Weekly:</span><?php acl_select_droplist("aclWeekly", $resultset["aclWeekly"]); ?>&nbsp;</nobr>
					<nobr><span class="label" nowrap>Monthly:</span><?php acl_select_droplist("aclMonthly", $resultset["aclMonthly"]); ?>&nbsp;</nobr>
					<nobr><span class="label" nowrap>Simple:</span><?php acl_select_droplist("aclSimple", $resultset["aclSimple"]); ?>&nbsp;</nobr>
					<nobr><span class="label" nowrap>Clients:</span><?php acl_select_droplist("aclClients", $resultset["aclClients"]); ?>&nbsp;</nobr>
					<nobr><span class="label" nowrap>Projects:</span><?php acl_select_droplist("aclProjects", $resultset["aclProjects"]); ?>&nbsp;</nobr>
					<nobr><span class="label" nowrap>Tasks:</span><?php acl_select_droplist("aclTasks", $resultset["aclTasks"]); ?>&nbsp;</nobr>
					<nobr><span class="label" nowrap>Reports:</span><?php acl_select_droplist("aclReports", $resultset["aclReports"]); ?>&nbsp;</nobr>
					<nobr><span class="label" nowrap>Rates:</span><?php acl_select_droplist("aclRates", $resultset["aclRates"]); ?>&nbsp;</nobr>
					<nobr><span class="label" nowrap>Absences:</span><?php acl_select_droplist("aclAbsences", $resultset["aclAbsences"]); ?>&nbsp;</nobr>
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

		<!-- simple timesheet layout -->
			<tr>
				<td align="left" valign="top">
					<b>simple layout</b>:
				</td>
				<td align="left" width="100%">
					Select a layout of the simple timesheet plugin. You can choose to show a text field to describe the work you did with plain text and additionally choose between two different sizes of that field.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="aclReset" value="off" valign="absmiddle" onclick="document.configurationForm.simpleTimesheetLayout.selectedIndex = 0; document.configurationForm.simpleTimesheetLayout.disabled=(this.checked);"  />Reset
				</td>
				<td align="left" width="100%">
					<select name="simpleTimesheetLayout" id="simpleTimesheetLayout">
						<option value="small work description field" <?php if ($resultset["simpleTimesheetLayout"] == 'small work description field') echo "selected=\"selected\"";?>>small work description field</option>
						<option value="big work description field" <?php if ($resultset["simpleTimesheetLayout"] == 'big work description field') echo "selected=\"selected\"";?>>big work description field</option>
						<option value="no work description field" <?php if ($resultset["simpleTimesheetLayout"] == 'no work description field') echo "selected=\"selected\"";?>>no work description field</option>
					</select>
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

		<!-- start page -->
			<tr>
				<td align="left" valign="top">
					<b>start page</b>:
				</td>
				<td align="left" width="100%">
					Select a default page to go to after login.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="aclReset" value="off" valign="absmiddle" onclick="document.configurationForm.simpleTimesheetLayout.selectedIndex = 0; document.configurationForm.simpleTimesheetLayout.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<select name="startPage" id="startPage">
						<option value="stopwatch" <?php if ($resultset["startPage"] == 'stopwatch') echo "selected=\"selected\"";?>>Stopwatch</option>
						<option value="daily" <?php     if ($resultset["startPage"] == 'daily')     echo "selected=\"selected\"";?>>Daily Timesheet</option>
						<option value="weekly" <?php    if ($resultset["startPage"] == 'weekly')    echo "selected=\"selected\"";?>>Weekly Timesheet</option>
						<option value="monthly" <?php   if ($resultset["startPage"] == 'monthly')   echo "selected=\"selected\"";?>>Monthly View</option>
						<option value="simple" <?php    if ($resultset["startPage"] == 'simple')    echo "selected=\"selected\"";?>>Simple Timesheet</option>
					</select>
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

		<!-- locale -->
			<tr>
				<td align="left" valign="top">
					<b>locale</b>:
				</td>
				<td align="left" width="100%">
					The locale in which you want TimesheetNextGen to work. This affects regional settings. Leave it blank if you want to use the system locale. An example locale is <code>en_AU</code>, for Australia.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="localeReset" value="off" valign="absmiddle" onclick="document.configurationForm.locale.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<input type="text" name="locale" size="75" maxlength="254" value="<?php echo htmlentities(trim(stripslashes($resultset["locale"]))); ?>" style="width: 100%;" />
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

		<!-- timezone -->
			<tr>
				<td align="left" valign="top">
					<b>Time Zone</b>:
				</td>
				<td align="left" width="100%">
					The timezone to use when generating dates. Leave it blank to use the system timezone. Example timezones: <code>Australia/Melbourne</code> &nbsp;<code>US/Central</code> &nbsp;<code>America/Chicago</code> &nbsp;<code>Europe/Zurich</code> &nbsp;<code>GMT+2</code>.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="timezoneReset" value="off" onclick="document.configurationForm.timezone.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<input type="text" name="timezone" size="75" maxlength="254" value="<?php echo htmlentities(trim(stripslashes($resultset["timezone"]))); ?>" style="width: 100%;" />
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

			<!-- timeformat -->
			<tr>
				<td align="left" valign="top">
					<b>Time Format</b>:
				</td>
				<td align="left" width="100%">
					The format in which times should be displayed.	For example:<br />
					&nbsp;&nbsp;&nbsp;&nbsp;<i> 12 hour format:</i><code>&nbsp;5:35 pm</code>
					&nbsp;&nbsp;&nbsp;&nbsp;<i> 24 hour format:</i><code>&nbsp;17:35</code>
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="timeformatReset" value="off" onclick="document.configurationForm.timeformat.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<select name="timeformat" style="width: 100%;">
						<?php if ($resultset["timeformat"] == "12") { ?>
							<option value="12" selected="selected">12 hour format</option>
							<option value="24">24 hour format</option>
						<?php } else { ?>
							<option value="12">12 hour format</option>
							<option value="24" selected="selected">24 hour format</option>
						<?php } ?>
					</select>
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

			<!-- weekstartday -->
			<tr>
				<td align="left" valign="top">
					<b>Week Start Day</b>:
				</td>
				<td align="left" width="100%">
					The starting day of the week. Some people prefer to calculate the week starting
					from Monday rather than Sunday.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="weekStartDayReset" value="off" onclick="document.configurationForm.weekstartday.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<select name="weekstartday" style="width: 100%;">
						<?php
							//get the current time
							$dowDate = time();

							//make it sunday
							$dowDate = strtotime(date("d M Y H:i:s",$dowDate) . " -" . date("w",$dowDate) . " days");

							//for each day of the week
							for ($i=0; $i<7; $i++) {
								$dowString = strftime("%A", $dowDate);
								print "<option value=\"$i\"";
								if ($resultset["weekstartday"] == $i)
									print " selected=\"selected\"";
								print ">$dowString</option>";
								//increment the day
								$dowDate = strtotime(date("d M Y H:i:s",$dowDate) . " +1 days");
							}
						?>
					</select>
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

			<!-- Items per page in Projects-->
			<tr>
				<td align="left" valign="top">
					<b>Items per page in Projects</b>:
				</td>
				<td align="left" width="100%">
					Type the number of elements your want per page, when viewing projects.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="projectItemsPerPageReset" value="off" onclick="document.configurationForm.projectItemsPerPage.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<input type="text" name="projectItemsPerPage" size="75" maxlength="254" value="<?php echo htmlentities(trim(stripslashes($resultset["project_items_per_page"]))); ?>" style="width: 100%;" />
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

			<!-- Items per page in Tasks-->
			<tr>
				<td align="left" valign="top">
					<b>Items per page in Tasks</b>:
				</td>
				<td align="left" width="100%">
					Type the number of elements your want per page, when viewing tasks.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="taskItemsPerPageReset" value="off" onclick="document.configurationForm.taskItemsPerPage.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<input type="text" name="taskItemsPerPage" size="75" maxlength="254" value="<?php echo htmlentities(trim(stripslashes($resultset["task_items_per_page"]))); ?>" style="width: 100%;" />
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">


			<!-- headerhtml -->
			<tr>
				<td align="left" valign="top">
					<b>headerhtml</b>:
				</td>
				<td align="left" width="100%">
					Additional HTML to add to the HEAD area of documents, eg. links to stylesheets.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="headerReset" value="off" onclick="document.configurationForm.headerhtml.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<textarea rows="5" cols="73" name="headerhtml" style="width: 100%;"><?php echo htmlentities(trim(stripslashes($resultset["headerhtml"]))); ?></textarea>
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

			<!-- bodyhtml -->
			<tr>
				<td align="left" valign="top">
					<b>bodyhtml</b>:
				</td>
				<td align="left" width="100%">
					Additional parameters to add to the BODY tag at the beginning of documents, eg. background image/colors, link colors, etc
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="bodyReset" value="off" onclick="document.configurationForm.bodyhtml.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<textarea rows="5" cols="73" name="bodyhtml"  style="width: 100%;"><?php echo htmlentities(trim(stripslashes($resultset["bodyhtml"]))); ?></textarea>
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

			<!-- bannerhtml -->
			<tr>
				<td align="left" valign="top">
					<b>bannerhtml</b>:
				</td>
				<td align="left" width="100%">
					The html that gets emitted at the head of every page. This is a good place to insert the placeholder %commandMenu%. You may also want to include the placeholder %username% as part of a welcome message.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="bannerReset" value="off" onclick="document.configurationForm.bannerhtml.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<textarea rows="5" cols="73" name="bannerhtml" style="width: 100%;"><?php echo htmlentities(trim(stripslashes($resultset["bannerhtml"]))); ?></textarea>
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

			<!-- footerhtml -->
			<tr>
				<td align="left" valign="top">
					<b>footerhtml</b>:
				</td>
				<td align="left" width="100%">
					HTML to add to the bottom of every page. If you include %time%, %date%, and %timezone% here, it will print the time and date the page was loaded.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="footerReset" value="off" onclick="document.configurationForm.footerhtml.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<textarea rows="5" cols="73" name="footerhtml" style="width: 100%;"><?php echo htmlentities(trim(stripslashes($resultset["footerhtml"]))); ?></textarea>
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">

			<!-- errorhtml -->
			<tr>
				<td align="left" valign="top">
					<b>errorhtml</b>:
				</td>
				<td align="left" width="100%">
					This is what is printed out when a form is improperly filled out. %errormsg% is replaced by the actual error itself.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="errorReset" value="off" onclick="document.configurationForm.errorhtml.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<textarea rows="5" cols="73" name="errorhtml" style="width: 100%;"><?php echo htmlentities(trim(stripslashes($resultset["errorhtml"]))); ?></textarea>
				</td>
			</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="5" class="section_body">


			<!-- tablehtml -->
			<tr>
				<td align="left" valign="top">
					<b>tablehtml</b>:
				</td>
				<td align="left" width="100%">
					Additional parameters to add to the table tag when displaying sheets, calenders, etc. This is often used to set the background color or background image of the table.
				</td>
			</tr>
			<tr>
				<td align="left" class="label" nowrap width="90">
					<input type="checkbox" name="tableReset" value="off" onclick="document.configurationForm.tablehtml.disabled=(this.checked);" />Reset
				</td>
				<td align="left" width="100%">
					<textarea rows="5" cols="73" name="tablehtml" style="width: 100%;"><?php echo htmlentities(trim(stripslashes($resultset["tablehtml"]))); ?></textarea>
				</td>
			</tr>

						</table>
					</td>
				</tr>

				</table>
				<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_bottom_panel">

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
