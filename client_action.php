<?php
// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn() || !$authenticationManager->hasAccess('aclClients')) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]&amp;clearanceRequired=" . get_acl_level('aclClients'));
	exit;
}

$contextUser = strtolower($_SESSION['contextUser']);

//load local vars from superglobals
$action = $_REQUEST["action"];
$client_id = isset($_REQUEST["client_id"]) ? mysql_real_escape_string($_REQUEST["client_id"]): 0;
$organisation = isset($_POST["organisation"]) ? mysql_real_escape_string($_POST["organisation"]): "";
$description = isset($_POST['description']) ? mysql_real_escape_string($_POST['description']): "";
$address1 = isset($_POST['address1']) ? mysql_real_escape_string($_POST['address1']): "";
$address2 = isset($_POST['address2']) ? mysql_real_escape_string($_POST['address2']): "";
$city = isset($_POST['city']) ? mysql_real_escape_string($_POST['city']): "";
$country = isset($_POST['country']) ? mysql_real_escape_string($_POST['country']): "";
$postal_code = isset($_POST['postal_code']) ? mysql_real_escape_string($_POST['postal_code']): "";
$contact_first_name = isset($_POST['contact_first_name']) ? mysql_real_escape_string($_POST['contact_first_name']): "";
$contact_last_name = isset($_POST['contact_last_name']) ? mysql_real_escape_string($_POST['contact_last_name']): "";
$client_username = isset($_POST['client_username']) ? mysql_real_escape_string($_POST['client_username']): "";
$contact_email = isset($_POST['contact_email']) ? mysql_real_escape_string($_POST['contact_email']): "";
$phone_number = isset($_POST['phone_number']) ? mysql_real_escape_string($_POST['phone_number']): "";
$fax_number = isset($_POST['fax_number']) ? mysql_real_escape_string($_POST['fax_number']): "";
$gsm_number = isset($_POST['gsm_number']) ? mysql_real_escape_string($_POST['gsm_number']): "";
$http_url = isset($_POST['http_url']) ? mysql_real_escape_string($_POST['http_url']): "";

if ($_REQUEST['action'] == "add") {
	dbquery("INSERT INTO $CLIENT_TABLE VALUES ('$client_id','$organisation','$description','$address1','$city'," .
	"'L','$country','$postal_code','$contact_first_name','$contact_last_name','$client_username'," .
	"'$contact_email','$phone_number','$fax_number','$gsm_number','$http_url','$address2')");
}
elseif ($action == "edit") {
	//create the query
	$query = "UPDATE $CLIENT_TABLE SET organisation='$organisation',".
		"description='$description',address1='$address1',city='$city',".
		"country='$country',postal_code='$postal_code',".
		"contact_first_name='$contact_first_name',".
		"contact_last_name='$contact_last_name',username='$client_username',".
		"contact_email='$contact_email',phone_number='$phone_number',".
		"fax_number='$fax_number',gsm_number='$gsm_number',".
		"http_url='$http_url',address2='$address2' ".
		"WHERE client_id=$client_id ";

	//run the query
	list($qh,$num) = dbquery($query);
}
elseif ($action == "delete") {
	//find out if this client is in use
	list($qh,$num) = dbQuery("SELECT * FROM $PROJECT_TABLE WHERE client_id='$client_id'");
	if ($num > 0)
		errorPage("You cannot delete a client for which there are projects. Please delete the projects first.");
	else
		dbquery("DELETE FROM $CLIENT_TABLE WHERE client_id='$client_id'");
}

Header("Location: client_maint.php");

// vim:ai:ts=4:sw=4
?>
