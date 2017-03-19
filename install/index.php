<?php 
define('INSTALLER_VERSION', '1.5.3');
// set up the global variable that holds any error messages
// don't really like using globals, but this is quick and dirty
$_ERROR = '';
// other globals 
$table_inc_file = 'table_names.inc';
$db_inc_file = 'database_credentials.inc';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>TimesheetNextGen :: Installation</title>
<style type="text/css">
body { font-family: verdana, helvetia, arial, sans-serif; font-size: 90%; }
code { font-weight: bold; font-size: 1.1em; font-style: normal; }
th { vertical-align: top; text-align: left; }
.error { color: red; }
</style>
</head>
<body>
<h1>Timesheet Next Gen Installation</h1>
<?php
// check that Timesheet NG isn't already installed
if(!isset($_REQUEST['step'])) {
	switch (check_is_installed()) {
	 	case 4:
	 	case 3:
			display_install_success();
			break;
		case 2:
			display_install_step_3();
			break;
		case 1:
			display_upgrade_step_1();
			break;
		default:
			install();
	}
} else { 
	install(); 
}
?>
</body>
</html>
<?php 
/**
 * check_is_installed()
 * Checks to see if Timesheet NG is already installed
 * @returns int:
 *   0 if not installed, 
 *   1 if installed but lower version, 
 *   2 if installed but no admin user
 *   3 if installed but not locked
 *   4 if installed and up-to-date
 */
function check_is_installed() {
	global $db_inc_file, $table_inc_file;
	if(!file_exists($db_inc_file)) { return 0; }
	if(!file_exists($table_inc_file)) { return 0; }

	include_once($db_inc_file);
	include_once($table_inc_file);
	if(!isset($TIMESHEET_INSTALLED)) { 
		// pre-V1.3.1 installation?
		if(!database_connect($DATABASE_HOST, $DATABASE_DB, $DATABASE_USER, $DATABASE_PASS)) { 
			return 0; 
		}
		$ver = get_old_database_version($CONFIG_TABLE);
		if ($ver==false) { return 0; }
		else { return 1; }
	}
	if($TIMESHEET_INSTALLED == '__INSTALLED__') { return 0; }
	if(version_compare($TIMESHEET_VERSION, INSTALLER_VERSION) == -1) { return 1; }

	if(!check_admin_user()) { return 2; }
	if(!file_exists('lock')) { return 3; }
	else { return 4; }
}
/** 
 * install()
 * Runs the install functionality
 */
function install() {
	global $table_inc_file, $db_inc_file;
	$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 'one';
	if(check_is_installed() >= 3) {
			display_install_success();
			return;
	}
	switch ($step) {
		case 'up-one':
			display_upgrade_step_2();
			break;
		case 'up-two':
			upgrade_installation();
			break;
		case 'two':
			create_new_installation();
			break;
		case 'two-one':
			create_database_one();
			break;
		case 'two-two':
			create_database_one();
			break;
		case 'three':
			install_step_final();
			break;
		case 'one':
		default:
			// check that step one is complete (i.e. the include files are writeable)
			if(check_include_files()) {
				display_install_step_2();
			}
			else {
				display_install_step_1();
			}
	}
}

/**
 * check_include_files()
 * Checks to see if Timesheet NG can find and write the include files
 * @returns bool
 */
function check_include_files() {
	global $table_inc_file, $db_inc_file;
	if(
		file_exists($table_inc_file) && is_writable($table_inc_file) &&
		file_exists($db_inc_file) && is_writable($db_inc_file)
		) {
		return true;
	}
	else { 
		return false;
	}
}

/**
 * display_install_step_1()
 * Output the first step (default) page
 */
function display_install_step_1() {
	global $table_inc_file, $db_inc_file;
	$table_inc_exists = file_exists($table_inc_file); 
	$table_inc_write = is_writable($table_inc_file);
	$db_inc_exists = file_exists($db_inc_file); 
	$db_inc_write = is_writable($db_inc_file);
?>
<p>Thank you for downloading Timesheet Next Gen. 
It'll just take a few more minutes to get it installed and working on your system.</p>
<h3>Things you'll need:</h3>
<ul>
<li>A MySQL database (we recommend version 4.1 or better)</li>
<li>The ability to change permissions of files on your server</li>
<li>The ability to delete directories on your server</li>
</ul>

<h2>Step One: Configuration Files</h2>
<p>Firstly you need to copy the files <code>install/database_credentials.inc.in</code> to <code>install/database_credentials.inc</code>
and <code>install/table_names.inc.in</code> to <code>install/table_names.inc</code>
and make sure that they are writeable by the webserver</p>
<table border="1">
	<tr>
		<th>File name</th>
		<th>File Exists</th>
		<th>Is Writable</th>
	</tr>
	<tr>
		<td><?php echo $table_inc_file; ?>&nbsp;&nbsp;</td>
		<td width="80" align="center"> <?php draw_ok_or_not_image($table_inc_exists); ?> </td>
		<td width="80" align="center"> <?php draw_ok_or_not_image($table_inc_write); ?> </td>
	</tr>
		<td><?php echo $db_inc_file; ?>&nbsp;&nbsp;</td>
		<td width="80" align="center"> <?php draw_ok_or_not_image($db_inc_exists); ?> </td>
		<td width="80" align="center"> <?php draw_ok_or_not_image($db_inc_write); ?> </td>
	</tr>
</table>
<br><p style="font-weight: bold; color: red">Please fix the issues and <a href="./">refresh</a> this page, when they are all fixed you'll proceed to step two.</p>
<?php 
}

/**
 * display_install_step_2()
 * Display the second step - the db configuration
 */
function display_install_step_2() {
	global $_ERROR;
	$db_host = (isset($_REQUEST['db_host']) && $_REQUEST['db_host']) ? $_REQUEST['db_host'] : 'localhost';
	$db_name = (isset($_REQUEST['db_name']) && $_REQUEST['db_name']) ? $_REQUEST['db_name'] : 'timesheet';
	$db_name_exist = (isset($_REQUEST['db_name_exist']) && $_REQUEST['db_name_exist']) ? $_REQUEST['db_name_exist'] : 'yes';
	$db_prefix = (isset($_REQUEST['db_prefix']) && $_REQUEST['db_prefix']) ? $_REQUEST['db_prefix'] : 'timesheet_';
	$db_user = (isset($_REQUEST['db_user']) && $_REQUEST['db_user']) ? $_REQUEST['db_user'] : '';
	$db_pass = (isset($_REQUEST['db_pass']) && $_REQUEST['db_pass']) ? $_REQUEST['db_pass'] : '';
	$db_pass_func = (isset($_REQUEST['db_pass_func']) && $_REQUEST['db_pass_func']) ? $_REQUEST['db_pass_func'] : 'SHA1';
?>
<h2>Step Two: MySQL Database Configuration</h2>
<?php if($_ERROR) {?>
<h3 class="error">There was an error</h3>
<p class="error"><?php echo $_ERROR; ?></p>
<?php } ?>
<form method="post">
<p>Please enter your database credentials:</p>
<table border="0">
<tr>
<th>Host</th><td><input type="text" name="db_host" value="<?php echo $db_host; ?>" /></td>
</tr>
<tr>
<th>Database Name</th><td><input type="text" name="db_name" value="<?php echo $db_name; ?>" /></td><td>
<input type="radio" name="db_name_exist" value="yes" <?php if($db_name_exist=="yes") echo "checked"; ?> /> This database exists, no need to create<br />
<input type="radio" name="db_name_exist" value="no"  <?php if($db_name_exist=="no") echo "checked"; ?> /> This database does not exist, please create it now. <i><font color="darkgreen" size="1">You must have DB Admin credentials for next step.</font></i>
</td></tr>
<tr>

<th>Username</th><td><input type="text" name="db_user" value="<?php echo $db_user; ?>" /></td>
</tr>
<tr>
<th>Password</th><td><input type="password" name="db_pass" value="<?php echo $db_pass; ?>" /></td>
</tr>
<tr>
<th>Password Function</th>
<td><select name="db_pass_func">
<option value="SHA1">SHA1</option>
<option value="PASSWORD">PASSWORD</option>
<option value="OLD_PASSWORD">OLD PASSWORD</option>
</select></td>
<td>This is the function the database uses to encrypt the passwords. If your MySQL version is 4.1 or above
you should use SHA1. PASSWORD should be used on MySQL version 4.0 or below, and OLD PASSWORD for MySQL
version 4.1 or above where SHA1 is not available.<br /><em>If in doubt, use SHA1.</em></td></tr>
<tr>
<th>Table Prefix</th><td><input type="text" name="db_prefix" value="<?php echo $db_prefix; ?>" /></td>
<td>This prefix is used for all table names</td>
</tr>
<tr><td colspan="3">
<?php /* <input type="button" value="Test Configuration" onclick="alert('Sorry, this doesn\'t work yet');"/> */ ?>
<input type="submit" value="Proceed to Step Three" />
</td></tr>
</table>
<input type="hidden" name="step" value="two" />
</form>
<?php 
}

/**
 * create_new_installation()
 * 1. Create database if needed
 * 2. Create tables
 * 3. Display confirmation (delete install directory) page
 */
function create_new_installation() {
	global $_ERROR;

	// get the passed data
	$db_host = (isset($_REQUEST['db_host']) && $_REQUEST['db_host']) ? $_REQUEST['db_host'] : false;
	$db_name = (isset($_REQUEST['db_name']) && $_REQUEST['db_name']) ? $_REQUEST['db_name'] : false;
	$db_name_exist = (isset($_REQUEST['db_name_exist']) && $_REQUEST['db_name_exist']) ? $_REQUEST['db_name_exist'] : 'yes';
	$db_prefix = (isset($_REQUEST['db_prefix']) && $_REQUEST['db_prefix']) ? $_REQUEST['db_prefix'] : 'timesheet_';
	$db_user = (isset($_REQUEST['db_user']) && $_REQUEST['db_user']) ? $_REQUEST['db_user'] : false;
	$db_pass = (isset($_REQUEST['db_pass']) && $_REQUEST['db_pass']) ? $_REQUEST['db_pass'] : false;
	$db_pass_func = (isset($_REQUEST['db_pass_func']) && $_REQUEST['db_pass_func']) ? $_REQUEST['db_pass_func'] : 'SHA1';
	$admin_user = (isset($_REQUEST['admin_user']) && $_REQUEST['admin_user']) ? $_REQUEST['admin_user'] : '';
	$admin_pass = (isset($_REQUEST['admin_pass']) && $_REQUEST['admin_pass']) ? $_REQUEST['admin_pass'] : '';

	// check that we have all we need
	$_ERROR = '';
	if(!$db_host) { $_ERROR .= 'You have not specified a database host<br />'; }
	if(!$db_name) { $_ERROR .= 'You have not specified a database name<br />'; }
	if(!$db_user) { $_ERROR .= 'You have not specified a database username<br />'; }
	if($db_user=='root' || $db_user=='sa') { $_ERROR .= 'You may not use \'root\' or \'sa\' as the timesheet database user<br />'; }
	if($_ERROR != '') {
		return display_install_step_2();
	}

	// check to see if we need to create database
	if($db_name_exist == 'no') {
		$_ERROR = '';
		get_dba_info($db_host, $db_name, $db_prefix, $db_user, $db_pass, $db_pass_func, $admin_user, $admin_pass);
		return;
	}

	// connect to the database
	if(!database_connect($db_host, $db_name, $db_user, $db_pass)) { 
		return display_install_step_2(); 
	}

	// now create the tables
	if(!create_tables($db_host, $db_name, $db_user, $db_pass, $db_prefix)) { 
		return display_install_step_2(); 
	}

	// finally write the include files
	if(!create_include_files($db_host, $db_name, $db_user, $db_pass, $db_prefix, $db_pass_func)) {
		return display_fatal_error();
	}
	return display_install_step_3();
}

/**
 * Get DBA info
 */
function get_dba_info($db_host, $db_name, $db_prefix, $db_user, $db_pass, $db_pass_func,$admin_user,$admin_pass) {
	global $_ERROR;
?>
<h2>Step 2.5: MySQL Database Creation</h2>
<?php if($_ERROR) {?>
<h3 class="error">There was an error</h3>
<p class="error"><?php echo $_ERROR; ?></p>
<?php } ?>
<form method="post">
	<input type="hidden" name="db_host" value="<?php echo$db_host?>"> 
	<input type="hidden" name="db_name" value="<?php echo$db_name?>"> 
	<input type="hidden" name="db_prefix" value="<?php echo$db_prefix?>"> 
	<input type="hidden" name="db_name_exist" value="no"> 
	<input type="hidden" name="db_user" value="<?php echo$db_user?>"> 
	<input type="hidden" name="db_pass" value="<?php echo$db_pass?>"> 
	<input type="hidden" name="db_pass_func" value="<?php echo$db_pass_func?>"> 
	<input type="hidden" name="step" value="two-two" />
	<p>Please enter your database <b>admin</b> credentials:</p>
	<table border="0">
		<tr>
			<th>Host</th><td><input type="text" name="db_host" value="<?php echo $db_host; ?>" disabled /></td>
		</tr> <tr>
			<th>DBA Username</th><td><input type="text" name="admin_user" value="<?php echo $admin_user; ?>" /></td>
		</tr> <tr>
			<th>DBA Password</th><td><input type="password" name="admin_pass" value="<?php echo $admin_pass; ?>" /></td>
		</tr> <tr> 
			<td> <input type="submit" value="Create the database" /></td>
		</tr>
	</table>
</form>

<?php 
}

/**
 * display_fatal_error()
 * Displays page when there was a non-recoverable error
 */
function display_fatal_error() {
	global $_ERROR;
?>
<h2>Installation Error</h2>
<p class="error">There has been an error that couldn't be recovered from</p>
<h3 class="error">Error Message</h3>
<p class="error"><?php echo $_ERROR;?></p>
<?php 
}


/**
 * display_upgrade_step_1()
 * Initial page for the upgrade script
 */
function display_upgrade_step_1() {
	global $table_inc_file, $db_inc_file;
	$table_inc_exists = file_exists($table_inc_file); 
	$table_inc_write = is_writable($table_inc_file);
	$db_inc_exists = file_exists($db_inc_file); 
	$db_inc_write = is_writable($db_inc_file);
	?>
<p>Thank you for downloading Timesheet Next Gen. 
It'll just take a few more minutes to get it installed and working on your system.</p>
<h2>Upgrade</h2>
<p>This script will help you upgrade from version 1.2.0 or later to the current version <?php echo INSTALLER_VERSION; ?>.</p>
<p>If you are trying to upgrade from a version before 1.2.0, please 
<a href="http://wiki.timesheetng.org/user-docs/update">see our wiki</a> for more details.</p>
<h2>Step One: Configuration Files</h2>
<p>Confirming that the configuration files <code>install/database_credentials.inc</code> and 
<code>install/table_names.inc</code> exist and are both writeable by the webserver.</p>
<table border="1">
	<tr>
		<th>File name</th>
		<th>File Exists</th>
		<th>Is Writable</th>
	</tr>
	<tr>
		<td><?php echo $table_inc_file; ?>&nbsp;&nbsp;</td>
		<td width="80" align="center"> <?php draw_ok_or_not_image($table_inc_exists); ?> </td>
		<td width="80" align="center"> <?php draw_ok_or_not_image($table_inc_write); ?> </td>
	</tr>
		<td><?php echo $db_inc_file; ?>&nbsp;&nbsp;</td>
		<td width="80" align="center"> <?php draw_ok_or_not_image($db_inc_exists); ?> </td>
		<td width="80" align="center"> <?php draw_ok_or_not_image($db_inc_write); ?> </td>
	</tr>
</table>
		<?php if($table_inc_exists && $table_inc_write && $db_inc_exists && $db_inc_write) {
			echo '<br><table><tr><td width=\"90"></td><td><a href="?step=up-one">Proceed to step 2</a></td></tr></table>';
		} else { ?>
			<br><p style="font-weight: bold; color: red">Please fix the issues and <a href="./">refresh</a> this page.</p>
		<?php }
}


/**
 * display_upgrade_step_2()
 * Step two of the upgrade script
 */
function display_upgrade_step_2() {
	global $_ERROR, $table_inc_file, $db_inc_file;
	include($db_inc_file);

	if(isset($_REQUEST['db_host'])) { $DATABASE_HOST = $_REQUEST['db_host']; }
	if(isset($_REQUEST['db_name'])) { $DATABASE_DB = $_REQUEST['db_name']; }
	if(isset($_REQUEST['db_user'])) { $DATABASE_USER = $_REQUEST['db_user']; }
	if(isset($_REQUEST['db_pass'])) { $DATABASE_PASS = $_REQUEST['db_pass']; }
	if(isset($_REQUEST['db_pass_func'])) { $DATABASE_PASSWORD_FUNCTION = $_REQUEST['db_pass_func']; }

	// try to work out the prefix
	if(isset($_REQUEST['db_prefix'])) {
		$db_prefix = $_REQUEST['db_prefix'];
	} else {
		include($table_inc_file);
		$pos = strpos(strtolower($CONFIG_TABLE), 'config');
		$db_prefix = substr($CONFIG_TABLE, 0, $pos);
	}
	if(!$db_prefix) $db_prefix = 'timesheet_';
	?>
<h2>Upgrade Step Two: Database Configuration</h2>
<form method="post">
<p>Please confirm your database credentials below are correct</p>
<?php if($_ERROR) {?>
<h3 class="error">There was an error</h3>
<p class="error"><?php echo $_ERROR; ?></p>
<?php } ?>
<table border="0">
<tr>
<th>Host</th><td><input type="text" name="db_host" value="<?php echo $DATABASE_HOST; ?>" /></td>
</tr>
<tr>
<th>Database Name</th><td><input type="text" name="db_name" value="<?php echo $DATABASE_DB; ?>" /></td>
<td>Please make sure that this database exists and you have sufficient permissions to create and alter tables</td></tr>
<tr>
<th>Username</th><td><input type="text" name="db_user" value="<?php echo $DATABASE_USER; ?>"/></td>
</tr>
<tr>
<th>Password</th><td><input type="password" name="db_pass" value="<?php echo $DATABASE_PASS; ?>" /></td>
</tr>
<tr>
<th>Password Function</th>
<td><select name="db_pass_func">
<option value="SHA1" <?php if ($DATABASE_PASSWORD_FUNCTION == 'SHA1') { echo 'selected="selected"'; } ?>>SHA1</option>
<option value="PASSWORD" <?php if ($DATABASE_PASSWORD_FUNCTION == 'PASSWORD') { echo 'selected="selected"'; } ?>>PASSWORD</option>
<option value="OLD_PASSWORD" <?php if ($DATABASE_PASSWORD_FUNCTION == 'OLD_PASSWORD') { echo 'selected="selected"'; } ?>>OLD PASSWORD</option>
</select></td>
<td>This is the function the database uses to encrypt the passwords. If your MySQL version is 4.1 or above
you should use SHA1. PASSWORD should be used on MySQL version 4.0 or below, and OLD PASSWORD for MySQL
version 4.1 or above where SHA1 is not available.<br /><em>If in doubt, use SHA1.</em></td></tr>
<tr>
<th>Table Prefix</th><td><input type="text" name="db_prefix" value="<?php echo $db_prefix; ?>" /></td>
<td>This prefix is used for all table names</td>
</tr>
<tr><td colspan="2">
<?php /*<input type="button" value="Test Configuration" onclick="alert('Sorry, this doesn\'t work yet');"/> */ ?>
<input type="submit" value="Proceed to Step Three" />
</td></tr>
</table>
<input type="hidden" name="step" value="up-two" />
</form>
	<?php 
}

/**
 * create_database_one()
 * Check if database exists
 */
function create_database_one() {
	global $_ERROR;

	// get the passed data
	$db_host = (isset($_REQUEST['db_host']) && $_REQUEST['db_host']) ? $_REQUEST['db_host'] : false;
	$db_name = (isset($_REQUEST['db_name']) && $_REQUEST['db_name']) ? $_REQUEST['db_name'] : false;
	$db_prefix = (isset($_REQUEST['db_prefix']) && $_REQUEST['db_prefix']) ? $_REQUEST['db_prefix'] : false;
	$db_user = (isset($_REQUEST['db_user']) && $_REQUEST['db_user']) ? $_REQUEST['db_user'] : false;
	$db_pass = (isset($_REQUEST['db_pass']) && $_REQUEST['db_pass']) ? $_REQUEST['db_pass'] : false;
	$db_pass_func = (isset($_REQUEST['db_pass_func']) && $_REQUEST['db_pass_func']) ? $_REQUEST['db_pass_func'] : 'SHA1';

	$admin_user = (isset($_REQUEST['admin_user']) && $_REQUEST['admin_user']) ? $_REQUEST['admin_user'] : '';
	$admin_pass = (isset($_REQUEST['admin_pass']) && $_REQUEST['admin_pass']) ? $_REQUEST['admin_pass'] : '';

	$db_purge = (isset($_REQUEST['db_purge']) && $_REQUEST['db_purge']) ? $_REQUEST['db_purge'] : false;
	$user_exists = (isset($_REQUEST['user_exists']) && $_REQUEST['user_exists']) ? $_REQUEST['user_exists'] : false;
	$user_purge = (isset($_REQUEST['user_purge']) && $_REQUEST['user_purge']) ? $_REQUEST['user_purge'] : 'no';

	// check that we have all we need
	$_ERROR = '';
	if(!$admin_user) { $_ERROR .= 'You have not specified a DBA username<br />'; }
	if(!$admin_pass) { $_ERROR .= 'You have not specified a DBA password<br />'; }
	if($_ERROR != '') {
		return get_dba_info($db_host, $db_name, $db_prefix, $db_user, $db_pass, $db_pass_func, $admin_user, $admin_pass);
	}
/*?>
<table border="0">
	<tr>
		<th>DB Host</th><td><?php echo $db_host; ?></td>
	</tr> <tr>
		<th>DB Name</th><td><?php echo $db_name; ?></td>
	</tr> <tr>
		<th>DB Prefix</th><td><?php echo $db_prefix; ?></td>
	</tr> <tr>
		<th>DB User</th><td><?php echo $db_user; ?></td>
	</tr> <tr>
		<th>DB Pass</th><td><?php echo $db_pass; ?></td>
	</tr> <tr>
		<th>DB Pass Func</th><td><?php echo $db_pass_func; ?></td>
	</tr> <tr>
		<th>DBA User</th><td><?php echo $admin_user; ?></td>
	</tr> <tr>
		<th>DBA Pass</th><td><?php echo $admin_pass; ?></td>
	</tr>
</table>
<?php */
	$need_validation=0;
	$validation_string='';
	if(!database_connect($db_host, 'mysql', $admin_user, $admin_pass)) { 
		$_ERROR .= 'Couldn\'t connect to the database.  Check host and DBA credentials.';
		return get_dba_info($db_host, $db_name, $db_prefix, $db_user, $db_pass, $db_pass_func, $admin_user, $admin_pass);
	}

	$db_prefix = mysql_real_escape_string($db_prefix);
	$db_host = mysql_real_escape_string($db_host);
	$db_name = mysql_real_escape_string($db_name);
	$db_user = mysql_real_escape_string($db_user);
	$db_pass = mysql_real_escape_string($db_pass);
	$admin_user = mysql_real_escape_string($admin_user);
	$admin_pass = mysql_real_escape_string($admin_pass);

	$sql = "SHOW TABLES FROM $db_name;";
	$result = mysql_query($sql);
	if(!$result) {
		if(strpos(mysql_error(),"Unknown database") === false ) {
			$_ERROR .= 'Show Tables failed <br />';
			$_ERROR .= get_db_error(mysql_error(),$sql);
			return get_dba_info($db_host, $db_name, $db_prefix, $db_user, $db_pass, $db_pass_func, $admin_user, $admin_pass);
		}
		//echo "<br>No database found with that name - Good!<br>\n";
	} else {
		if(mysql_num_rows($result) > 0) {
			if($db_purge!="on"){
				$items=array(0,0,0,0,0,0,0,0);
				while($row = mysql_fetch_row($result) ) {
					if(strpos($row[0],"assignments") !== false) $items[0]=1;
					if(strpos($row[0],"client") !== false) $items[1]=1;
					if(strpos($row[0],"config") !== false) $items[2]=1;
					if(strpos($row[0],"project") !== false) $items[3]=1;
					if(strpos($row[0],"task") !== false) $items[4]=1;
					if(strpos($row[0],"task_assignments") !== false) $items[5]=1;
					if(strpos($row[0],"times") !== false) $items[6]=1;
					if(strpos($row[0],"user") !== false) $items[7]=1;
					// 8 tables from 1.2.1
				}
				$tables=0;
				foreach ($items as $item) {
					if($item==1) $tables++;
				}
				if($tables < 8) {
					if($tables < 4) {
						$_ERROR .= 'The database &quot;'.$db_name.'&quot; already exists and it is NOT a timesheet database; you\'ll have to delete that DB manually if you really want to use that name.<br />';
						return display_install_step_2(); 
					}
					if($tables < 6) {
						$_ERROR .= 'The database &quot;'.$db_name.'&quot; already exists, but it doesn\'t look like a timesheet database, you\'ll need to delete that DB manually if you really want to use that name.<br />';
						return display_install_step_2(); 
					}
					echo "<br><font color=\"orangered\"><b>A Database already with the name &quot;$db_name&quot; already exists, and it appears to be a timesheet database</b></font><br>\n";
				} else {
					echo "<br><font color=\"orangered\"><b>A timesheet database with the name &quot;$db_name&quot; already exists</b></font><br>\n";
				}
				$need_validation=1;
				?>
					<form method="post">
					<table> <tr> <td width="20">&nbsp;</td> <td>
						If you're SURE you want to <b>Purge, Delete, and Drop</b> all the data that exists, check this box
					<input type="checkbox" name="db_purge">.</td> </tr>
					<?php if($tables>=6) { ?>
					<tr><td width="20">&nbsp;</td> <td>
						or, if you need to upgrade, go copy the database_credentials.inc file from the existing installation, then
						<a href="<?php echo $_SERVER['PHP_SELF']; ?>">click here</a></td>
					<?php } ?>
					</table><br>
				<?php
			}
		}
	}

	if($user_exists === false) {
		$sql = "SELECT * FROM user where User=\"$db_user\";";
		$result = mysql_query($sql);
		if(!$result) {
			$_ERROR .= 'SELECT from user table failed <br />';
			$_ERROR .= get_db_error(mysql_error(),$sql);
			return get_dba_info($db_host, $db_name, $db_prefix, $db_user, $db_pass, $db_pass_func, $admin_user, $admin_pass);
		}

		if(mysql_num_rows($result) > 0) {
			echo "<br><font color=\"orangered\"><b>A DB user with the name &quot;$db_user&quot; already exists, we have two options:</b></font><br>\n";
			if(!$need_validation) print "<form method=\"post\">";
			$need_validation=1;
			?>
				<input type="hidden" name="user_exists" value="yes"> 
				<table> <tr> <td width="20">&nbsp;</td> <td>
				<input type="radio" name="user_purge" value="no" <?php if ($user_purge=='no') echo "checked"; ?>></td><td>
				leave the existing user, password, and it's existing DB permissions alone, but grant it rights to the timesheet database, or<br>
				</td></tr> <tr> <td width="20">&nbsp;</td> <td>
				<input type="radio" name="user_purge" value="yes" <?php if ($user_purge=='yes') echo "checked"; ?>></td><td>
				<b>delete</b> the existing user, it's existing DB permissions, and create a new instance with the specified password and rights to the timesheet database.
				</td> </tr> </table>
			<?php
			
		} else {
			if($need_validation) print "<input type=\"hidden\" name=\"user_exists\" value=\"no\">";
			$user_exists='no';
		}
	}

	if($need_validation) {
	?>
			<input type="hidden" name="db_host" value="<?php echo$db_host?>"> 
			<input type="hidden" name="db_name" value="<?php echo$db_name?>"> 
			<input type="hidden" name="db_prefix" value="<?php echo$db_prefix?>"> 
			<input type="hidden" name="db_name_exist" value="no"> 
			<input type="hidden" name="db_user" value="<?php echo$db_user?>"> 
			<input type="hidden" name="db_pass" value="<?php echo$db_pass?>"> 
			<input type="hidden" name="db_pass_func" value="<?php echo$db_pass_func?>"> 
			<input type="hidden" name="admin_user" value="<?php echo$admin_user?>"> 
			<input type="hidden" name="admin_pass" value="<?php echo$admin_pass?>"> 
			<input type="hidden" name="step" value="two-two" />
			<br><br><table><tr><td width="20">&nbsp;</td><td><input type="submit" value="continue"></td></tr></table>
		</form>
	<?php 
	} else {
		return create_database_two($db_host, $db_name, $db_prefix, $db_user, $db_pass, $db_pass_func, $db_purge, $user_exists, $user_purge);
	}
}

/**
 * create_database_two()
 * Delete existing DB and user as needed, then create the database, and create the user if needed.
 */
function create_database_two($db_host, $db_name, $db_prefix, $db_user, $db_pass, $db_pass_func, $db_purge, $user_exists, $user_purge) {
	global $_ERROR;

	if($db_purge=='on') {
		$sql="DROP DATABASE IF EXISTS $db_name";
		//echo htmlentities($sql) . '<br />';
		if(!mysql_query($sql)) {
			$_ERROR .= 'Could not drop the database<br />';
			$_ERROR .= get_db_error(mysql_error(),$sql);
			return display_install_step_2(); 
		}
		$sql="DELETE FROM db WHERE db=\"$db_name\"";
		//echo htmlentities($sql) . '<br />';
		if(!mysql_query($sql)) {
			$_ERROR .= 'Could not clear database permissions<br />';
			$_ERROR .= get_db_error(mysql_error(),$sql);
			return display_install_step_2(); 
		}
	}

	$sql="CREATE DATABASE $db_name";
	//echo htmlentities($sql) . '<br />';
	if(!mysql_query($sql)) {
		$_ERROR .= 'Could not drop the database<br />';
		$_ERROR .= get_db_error(mysql_error(),$sql);
		return display_install_step_2(); 
	}

	if($user_exists=='yes') {
		if($user_purge='yes') {
			$sql="DELETE FROM user WHERE User=\"$db_user\"";
			//echo htmlentities($sql) . '<br />';
			if(!mysql_query($sql)) {
				$_ERROR .= 'Could not delete the user<br />';
				$_ERROR .= get_db_error(mysql_error(),$sql);
				echo $_ERROR;
				return display_install_step_2(); 
			}
			$sql="DELETE FROM db WHERE user=\"$db_user\"";
			//echo htmlentities($sql) . '<br />';
			if(!mysql_query($sql)) {
				$_ERROR .= 'Could not clear the users\' permissions<br />';
				$_ERROR .= get_db_error(mysql_error(),$sql);
				echo $_ERROR;
				return display_install_step_2(); 
			}

			create_db_user($db_host,$db_user,$db_pass,$db_pass_func);
			grant_user_permissions($db_host,$db_name,$db_user);

		} else {
			grant_user_permissions($db_host,$db_name,$db_user);
			
		}
	} else {
		create_db_user($db_host,$db_user,$db_pass,$db_pass_func);
		grant_user_permissions($db_host,$db_name,$db_user);
	}

	// connect to the database
	if(!database_connect($db_host, $db_name, $db_user, $db_pass)) { 
		return display_install_step_2(); 
	}

	// now create the tables
	if(!create_tables($db_host, $db_name, $db_user, $db_pass, $db_prefix)) { 
		return display_install_step_2(); 
	}

	// finally write the include files
	if(!create_include_files($db_host, $db_name, $db_user, $db_pass, $db_prefix, $db_pass_func)) {
		return display_fatal_error();
	}

	return display_install_step_3();

}

/**
 * grant_user_permissions()
 * Grant appropriate permissions for DB user
 */
function grant_user_permissions($db_host,$db_name,$db_user) {
	$sql="INSERT INTO db(Host,Db,User,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Grant_priv,References_priv, Index_priv, Alter_priv, Lock_tables_priv)" .
	     "VALUES('$db_host','$db_name','$db_user','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y')";
	//echo htmlentities($sql) . '<br />';
	if(!mysql_query($sql)) {
		$_ERROR .= 'Could not add user permissions<br />';
		$_ERROR .= get_db_error(mysql_error(),$sql);
		echo $_ERROR;
		return display_install_step_2(); 
	}
	$sql="FLUSH PRIVILEGES";
	//echo htmlentities($sql) . '<br />';
	if(!mysql_query($sql)) {
		$_ERROR .= 'Could not flush privileges<br />';
		$_ERROR .= get_db_error(mysql_error(),$sql);
		echo $_ERROR;
		return display_install_step_2(); 
	}
}

/**
 * create_db_user()
 * Create the database user the timesheet system will use to access the database
 */
function create_db_user($db_host,$db_user,$db_pass,$db_pass_func) {
	$sql="INSERT INTO user (Host,User,Password)" .
	     "VALUES('$db_host','$db_user',$db_pass_func('$db_pass'))";
	//echo htmlentities($sql) . '<br />';
	if(!mysql_query($sql)) {
		$_ERROR .= 'Could not create user<br />';
		$_ERROR .= get_db_error(mysql_error(),$sql);
		echo $_ERROR;
		return display_install_step_2(); 
	}
}

/**
 * display_install_step_3()
 * outputs the actual "Step Three" page
 */
function display_install_step_3() {
	global $_ERROR;
?>
<h2>Step Three: Create Admin User</h2>
<form method="post">
	<p class="success">Database and setup files have been successfully created</p>

	<p>We now need to create a Timesheet admin user for this installation of Timesheet Next Gen</p>
<?php if($_ERROR) {?>
	<h3 class="error">There was an error</h3>
	<p class="error"><?php echo $_ERROR; ?></p>
<?php } ?>
	<table border="0">
		<tr>
			<th>Admin User Name</th><td><input type="text" name="username" value="<?php if (isset($_REQUEST['username'])) echo $_REQUEST['username'];?>" /></td>
		</tr>
		<tr>
			<th>Admin Password</th><td><input type="password" name="password" /></td>
		</tr>
		<tr>
			<th>Admin Password (again)</th><td><input type="password" name="password2" /></td>
		</tr>
		<tr><td colspan="2">
			<input type="submit" value="Install Now" />
		</td></tr>
	</table>
	<input type="hidden" name="step" value="three" />
</form>
<?php 
}

/**
 * install_step_final()
 * Creates the admin user for the installation, displays the "completed" page
 * @return unknown_type
 */
function install_step_final() {
	global $_ERROR;
	// get the passed data
	$username = (isset($_REQUEST['username']) && $_REQUEST['username']) ? $_REQUEST['username'] : false;
	$password = (isset($_REQUEST['password']) && $_REQUEST['password']) ? $_REQUEST['password'] : false;
	$password2 = (isset($_REQUEST['password2']) && $_REQUEST['password2']) ? $_REQUEST['password2'] : false;

	// check that we have all we need
	$_ERROR = '';
	if(!$username) { $_ERROR .= 'You have not specified an admin username<br />'; }
	if(!$password) { $_ERROR .= 'You have not specified an admin password<br />'; }
	if($password != $password2) { $_ERROR .= 'The passwords do not match<br />'; }
	if($_ERROR != '') {
		return display_install_step_3();
	}

	// add the admin user to the database
	if(!create_admin_user($username, $password)) {
		return display_fatal_error();
	}
	return display_install_success();
}

/**
 * draw_ok_or_not_image($ok)
 * draw a small green clock when $ok is true,
 * draw a small red clock when $ok is false
 */
function draw_ok_or_not_image($ok) {
	if($ok) { ?>
		<!--img src="../images/clock-green-sml.gif" border="0"-->
		<img src="../images/green-check-mark.gif" height="30" border="0">
	<?php } else { ?>
		<!--img src="../images/clock-red-sml.gif" border="0"-->
		<img src="../images/red-x.gif" height="30" border="0">
	<?php }
}

/**
 * display_install_success()
 * Displays the "Success" page when install has completed
 */
function display_install_success() {
	global $db_inc_file, $table_inc_file;
	$db_inc_ok = 1;  $table_inc_ok = 1;
	if(is_writable($db_inc_file)) 	$db_inc_ok = 0;
	if(is_writable($table_inc_file))	$table_inc_ok = 0;
	if(!file_exists('lock')) touch('lock');
?>
<h2>Installation Complete</h2>
<p>Installation was successful</p>
<h3>Final Bits</h3>
<p>There are just a few things to do before you can start using Timesheet Next Gen</p>
<p>Both the <?php echo "$db_inc_file and $table_inc_file"; ?></td>s should be made <stong>Read Only</strong></p>

<table border="1">
	<tr>
		<th>Task</th>
		<th>Complete?</th>
	</tr>
	<tr>
		<td><?php echo "make $db_inc_file read only"; ?>&nbsp;&nbsp;</td>
		<td width="30" align="center"> <? draw_ok_or_not_image($db_inc_ok); ?> </td>
	</tr><tr>
		<td><?php echo "make $table_inc_file read only"; ?>&nbsp;&nbsp;</td>
		<td align="center"> <? draw_ok_or_not_image($table_inc_ok); ?> </td>
	</tr>
</table>
<p>Once you have done those you can then <a href="../">continue to Timesheet Next Gen</a></p>
<?php 
}

/**
 * upgrade_installation()
 * This does the main bulk of the upgrading:
 *  1. Writes the config files
 *  2. upgrades the database
 *  3. reports success or fail
 */
function upgrade_installation() {
	global $_ERROR;

echo "<h2>Attempting upgrade</h2><br>";
	// get the passed data
	$db_host = (isset($_REQUEST['db_host']) && $_REQUEST['db_host']) ? $_REQUEST['db_host'] : false;
	$db_name = (isset($_REQUEST['db_name']) && $_REQUEST['db_name']) ? $_REQUEST['db_name'] : false;
	//$db_name_exist = (isset($_REQUEST['db_name_exist']) && $_REQUEST['db_name_exist']) ? $_REQUEST['db_name_exist'] : 'yes';
	$db_prefix = (isset($_REQUEST['db_prefix']) && $_REQUEST['db_prefix']) ? $_REQUEST['db_prefix'] : false;
	$db_user = (isset($_REQUEST['db_user']) && $_REQUEST['db_user']) ? $_REQUEST['db_user'] : false;
	$db_pass = (isset($_REQUEST['db_pass']) && $_REQUEST['db_pass']) ? $_REQUEST['db_pass'] : false;
	$db_pass_func = (isset($_REQUEST['db_pass_func']) && $_REQUEST['db_pass_func']) ? $_REQUEST['db_pass_func'] : 'SHA1';

	// check that we have all we need
	$_ERROR = '';
	if(!$db_host) { $_ERROR .= 'You have not specified a database host<br />'; }
	if(!$db_name) { $_ERROR .= 'You have not specified a database name<br />'; }
	if(!$db_user) { $_ERROR .= 'You have not specified a database username<br />'; }
	if($_ERROR != '') {
		return display_upgrade_step_2();
	}

	// connect to the database
	if(!database_connect($db_host, $db_name, $db_user, $db_pass)) { 
		return display_upgrade_step_2(); 
	} else {
		echo "Connection to the database successful<br>";
	}

	// now create the tables
	if(!upgrade_tables($db_prefix, $db_pass_func)) { 
		return display_upgrade_step_2(); 
	} else {
		echo "Tables updated successfully<br>";
	}

	// finally write the include files
	if(!create_include_files($db_host, $db_name, $db_user, $db_pass, $db_prefix, $db_pass_func)) {
		return display_fatal_error();
	} else {
		echo "Include files updated successfully<br>";
	}
	return display_install_success();
}


/**
 * create_tables()
 * Creates the database tables and populates
 * @param $db_host
 * @param $db_name
 * @param $db_user
 * @param $db_pass
 * @param $db_prefix
 * @return bool true on successful creation
 */
function create_tables($db_host, $db_name, $db_user, $db_pass, $db_prefix) {
	global $_ERROR, $db_pass_func;
	$db_prefix = mysql_real_escape_string($db_prefix);

	$contents = file_get_contents('sql/timesheet.sql.in');
	// finalise the file
	$key_words = array('__TIMESHEET_VERSION__', '__TABLE_PREFIX__', '__DBPASSWORDFUNCTION__');
	$key_values = array(INSTALLER_VERSION, $db_prefix, $db_pass_func);
	$contents = str_replace($key_words, $key_values, $contents);

	//regex is a mess: $queries = preg_split("/;+(?=([^'|^\\']*['|\\'][^'|^\\']*['|\\'])*[^'|^\\']*[^'|^\\']$)/", $contents);
	$queries = preg_split("#(;\s*\n)|(;\s*\r\n)#", $contents);
	foreach ($queries as $sql) {
		if (strlen(trim($sql)) > 0) {
			if(!mysql_query($sql)) {
				$_ERROR .= 'Could not create <strong>Tables</strong><br />';
				$_ERROR .= get_db_error(mysql_error(),$sql);
				return false;
			}
		}
	}
	return true;
}

/**
 * create_admin_user()
 * @param $username
 * @param $password
 * @return bool true if admin user was created
 */
function create_admin_user($username, $password) {
	global $_ERROR, $db_inc_file, $table_inc_file;

	include($db_inc_file);
	include($table_inc_file);

	// connect to the database
	if(!database_connect($DATABASE_HOST, $DATABASE_DB, $DATABASE_USER, $DATABASE_PASS)) { 
		return display_fatal_error(); 
	}
	// clean up input
	$username = mysql_real_escape_string($username);
	$password = mysql_real_escape_string($password);

	$sql = 'INSERT INTO '.$USER_TABLE.' (username,level,password,first_name,last_name) 
		VALUES ("'.$username.'",10,'.$DATABASE_PASSWORD_FUNCTION.'("'.$password.'"),"Timesheet","Admin")';
	if(!mysql_query($sql)) {
		$_ERROR .= 'Could not create the admin user <br />';
		$_ERROR .= get_db_error(mysql_error());
		return false;
	}
	$sql = 'INSERT INTO '.$ASSIGNMENTS_TABLE.' VALUES(1,"'.$username.'", 1)';
	if(!mysql_query($sql)) {
		$_ERROR .= 'Could not add user to default assignment<br />';
		$_ERROR .= get_db_error(mysql_error());
		return false;
	}
	$sql = 'INSERT INTO '.$TASK_ASSIGNMENTS_TABLE.' VALUES(1,"'.$username.'", 1)';
	if(!mysql_query($sql)) {
		$_ERROR .= 'Could not add user to default assignment<br />';
		$_ERROR .= get_db_error(mysql_error());
		return false;
	}
	return true;
}
/**
 * check_admin_user()
 * Checks to see if an admin user has been created. 
 * @return bool true if created
 */
function check_admin_user() {
	global $_ERROR, $db_inc_file, $table_inc_file;

	include($db_inc_file);
	include($table_inc_file);

	if(!database_connect($DATABASE_HOST, $DATABASE_DB, $DATABASE_USER, $DATABASE_PASS)) { 
		return display_fatal_error(); 
	}

	$sql = 'SELECT COUNT(*) FROM '.$USER_TABLE.' WHERE level>=10';
	$result = mysql_query($sql);
	if(!$result) {
		$_ERROR .= 'Could not check admin user<br />';
		$_ERROR .= get_db_error(mysql_error());
		return false;
	}
	$row = mysql_fetch_row($result);
	return $row[0];
}

/**
 * get_db_error()
 * get DB error info 
 */
function get_db_error($error,$query='') {
	$estring='';
	if($query) $estring= 'Query said: ' . htmlentities($query) . '<br />';
	return  $estring .  'Database said: ' . $error . '<br />';
}

/**
 * get_old_database_version()
 * Check the status in the DB 
 * @return version number
 */
function get_old_database_version($cfg_table) {
	global $_ERROR;

	$sql = 'SHOW TABLES LIKE \''.$cfg_table.'\'';
	$result = mysql_query($sql);
	if(!$result) {
		$_ERROR .= 'Could not find the config table<br />';
		$_ERROR .= get_db_error(mysql_error());
		return false;
	}

	$sql = 'SELECT version FROM '.$cfg_table.' WHERE config_set_id=\'1\'';
	$result = mysql_query($sql);
	if(!$result) {
		$_ERROR .= 'Could not check version<br />';
		$_ERROR .= get_db_error(mysql_error());
		return false;
	}
	$row = mysql_fetch_row($result);

	// hack for V1.3.1
	if ($row[0]=='__timesheet_VERSION__')
		$row[0] = '1.3.1';

	return $row[0];
}

/**
 * get_new_database_version()
 * Check the status in the DB 
 * @return version number
 */
function get_new_database_version($cfg_table) {
	global $_ERROR;

	$sql = 'SHOW TABLES LIKE \''.$cfg_table.'\'';
	$result = mysql_query($sql);
	if(!$result) {
		$_ERROR .= 'Could not find the config table<br />';
		$_ERROR .= get_db_error(mysql_error());
		return false;
	}

	$sql = 'SELECT value FROM '.$cfg_table.' WHERE name=\'version\'';
	$result = mysql_query($sql);
	if(!$result) {
		$_ERROR .= 'Could not check version<br />';
		$_ERROR .= get_db_error(mysql_error());
		return false;
	}
	$row = mysql_fetch_row($result);
	return $row[0];
}

function database_connect($db_host, $db_name, $db_user, $db_pass) {
	global $_ERROR;
	$link = @mysql_connect($db_host, $db_user, $db_pass);
	if (!$link) {
		$_ERROR .= 'Could not connect to the database.<br />';
		$_ERROR .= get_db_error(mysql_error());
		return false;
	}
	if(!mysql_select_db($db_name)) {
		$_ERROR .= 'Could not select the database.<br />';
		$_ERROR .= get_db_error(mysql_error());
		return false;
	}
	return true;
}

/**
 * create_include_files()
 * Writes the configuration to the include files 
 * @param $db_host
 * @param $db_name
 * @param $db_user
 * @param $db_pass
 * @param $db_prefix
 * @param $db_pass_func
 * @return bool true if successfully written
 */
function create_include_files($db_host, $db_name, $db_user, $db_pass, $db_prefix, $db_pass_func) {
	global $_ERROR, $table_inc_file, $db_inc_file;
	// make sure the values are safe
	$db_host = mysql_real_escape_string($db_host);
	$db_name = mysql_real_escape_string($db_name);
	$db_user = mysql_real_escape_string($db_user);
	$db_pass = mysql_real_escape_string($db_pass);
	$db_prefix = mysql_real_escape_string($db_prefix);
	$db_pass_func = mysql_real_escape_string($db_pass_func);

	// first write the database credentials: read in current file
	$contents = file_get_contents($db_inc_file.'.in');
	// edit the file
	$contents = str_replace("__INSTALLED__", 1, $contents);
	$contents = str_replace("__TIMESHEET_VERSION__", INSTALLER_VERSION, $contents);
	$contents = str_replace("__DBHOST__", $db_host, $contents);
	$contents = str_replace("__DBUSER__", $db_user, $contents);
	$contents = str_replace("__DBPASS__", $db_pass, $contents);
	$contents = str_replace("__DBNAME__", $db_name, $contents);
	$contents = str_replace("__DBPASSWORDFUNCTION__", $db_pass_func, $contents);
	// re-write it
	if (!$handle = fopen($db_inc_file, 'w')) {
		$_ERROR .= 'Could not open <code>'.$db_inc_file.'</code> file for writing';
		return false;
    }
    if (fwrite($handle, $contents) === FALSE) {
		$_ERROR .= 'Could not write to <code>'.$db_inc_file.'</code>';
		return false;
    }
    fclose($handle);

	// now the table names
	$contents = file_get_contents($table_inc_file.'.in');
	$contents = str_replace("__TABLE_PREFIX__", $db_prefix, $contents);
	if (!$handle = fopen($table_inc_file, 'w')) {
		$_ERROR .= 'Could not open <code>'.$table_inc_file.'</code> file for writing';
		return false;
    }
    if (fwrite($handle, $contents) === FALSE) {
		$_ERROR .= 'Could not write to <code>'.$table_inc_file.'</code>';
		return false;
    }
    fclose($handle);
	return true;
}

/* update DB version number */
function update_old_db_version($db_prefix, $version) {
	global $_ERROR;
	$sql = 'UPDATE '.$db_prefix.'config set version=\''.$version.'\';';
	if(!mysql_query($sql)) {
		$_ERROR .= '<strong>Could not update DB version</strong><br />';
		$_ERROR .= 'Your query said:   '.htmlentities($sql).'<br />';
		$_ERROR .= 'Our database said: '.mysql_error().'<br />';
		return false;
	}
}

/* update DB version number */
function update_new_db_version($db_prefix, $version) {
	global $_ERROR;
	$sql = 'UPDATE '.$db_prefix.'configuration set value=\''.$version.'\' WHERE \'name\'=\'version\';';
	if(!mysql_query($sql)) {
		$_ERROR .= '<strong>Could not update DB version</strong><br />';
		$_ERROR .= 'Your query said:   '.htmlentities($sql).'<br />';
		$_ERROR .= 'Our database said: '.mysql_error().'<br />';
		return false;
	}
}

/* Database Upgrade */
function upgrade_tables($db_prefix, $db_pass_func) {
	global $_ERROR;
	$result = true;

	$sql = 'SHOW TABLES LIKE \''.$db_prefix.'config\'';
	$result = mysql_query($sql);
	if(!$result) {
		$db_version = get_new_database_version($db_prefix.'configuration');
		if($db_version === false) return false;
	}
	else {
		$db_version = get_old_database_version($db_prefix.'config');
		if($db_version === false) return false;
	}

	switch ($db_version) {
	//If any SQL statements fail, we don't want to continue, and we want to mark the DB
	//with the version that last succeeded so we can hopefully continue the upgrades later
	case '1.2.0' :
		$result = run_sql_script($db_prefix, $db_pass_func, 'sql/timesheet_upgrade_to_1.2.1.sql.in');
		if($result === false) return $result;
		$result = update_old_db_version($db_prefix, '1.2.1');
		if($result === false) return $result;
	case '1.2.1' :
		$result = run_sql_script($db_prefix, $db_pass_func, 'sql/timesheet_upgrade_to_1.3.1.sql.in');
		if($result === false) return $result;
		$result = update_old_db_version($db_prefix, '1.3.1');
		if($result === false) return $result;
	case '1.3.1' :
		$result = run_sql_script($db_prefix, $db_pass_func, 'sql/timesheet_upgrade_to_1.4.1.sql.in');
		if($result === false) return $result;
		$result = update_old_db_version($db_prefix, '1.4.1');
		if($result === false) return $result;
	case '1.4.1' :
		$result = run_sql_script($db_prefix, $db_pass_func, 'sql/timesheet_upgrade_to_1.5.0.sql.in');
		if($result === false) return $result;
		$result = update_old_db_version($db_prefix, '1.5.0');
		if($result === false) return $result;
	case '1.5.0' :
		$result = run_sql_script($db_prefix, $db_pass_func, 'sql/timesheet_upgrade_to_1.5.1.sql.in');
		if($result === false) return $result;
		$result = update_old_db_version($db_prefix, '1.5.1');
		if($result === false) return $result;
	case '1.5.1' :
	case '1.5.2' :
		$result = run_sql_script($db_prefix, $db_pass_func, 'sql/timesheet_upgrade_to_1.5.3.sql.in');
		if($result === false) return $result;
		$result = update_new_db_version($db_prefix, '1.5.3');
		if($result === false) return $result;
	}
	return $result;
}

function run_sql_script($db_prefix, $db_pass_func, $sqlfile) {
	global $_ERROR;
	$db_prefix = mysql_real_escape_string($db_prefix);

	$contents = file_get_contents($sqlfile);
	// finalise the script
	$key_words = array("__TIMESHEET_VERSION__", "__TABLE_PREFIX__", "__DBPASSWORDFUNCTION__");
	$key_values = array(INSTALLER_VERSION, $db_prefix, $db_pass_func);
	$contents = str_replace($key_words, $key_values, $contents);

	$queries = preg_split("/;+(?=([^'|^\\']*['|\\'][^'|^\\']*['|\\'])*[^'|^\\']*[^'|^\\']$)/", $contents);
	foreach ($queries as $sql) {
		if (strlen(trim($sql)) > 0) {
			if(!mysql_query($sql)) {
				$_ERROR .= '<strong>Could not complete script</strong><br />';
				$_ERROR .= 'Your query said:   '.htmlentities($sql).'<br />';
				$_ERROR .= 'Our database said: '.mysql_error().'<br />';
				return false;
			}
		}
	}
	return true;
}
// vim:ai:ts=4:sw=4
