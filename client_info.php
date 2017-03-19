<?php
// Authenticate
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");
if (!$authenticationManager->isLoggedIn()) {
	Header("Location: login.php?redirect=$_SERVER[PHP_SELF]");
	exit;
}

//load local vars from superglobals
$client_id = $_REQUEST['client_id'];

// Connect to database.
$dbh = dbConnect();
$contextUser = strtolower($_SESSION['contextUser']);

?>
<HTML>
<head>
<title>Client Info</title>
<?php
include ("header.inc");
?>
</head>
<body <?php include ("body.inc"); ?> >
<?php
	$query = "SELECT organisation, description, address1, address2,".
				"city, country, postal_code, contact_first_name, contact_last_name,".
				"username, contact_email, phone_number, fax_number, gsm_number, ".
				"http_url ".
			"FROM $CLIENT_TABLE ".
			"WHERE $CLIENT_TABLE.client_id=$client_id";

	print "<center><table border=\"0\" ";
	include("table.inc");
	print " width=\"100%\">\n";

	list($qh, $num) = dbQuery($query);
	if ($num > 0) {

		$data = dbResult($qh);
		print "<tr><td COLSPAN=3><font SIZE=+1><B>$data[organisation]</B></font></td></tr>\n";
		print "<tr><td COLSPAN=3><I>$data[description]</I></td></tr>\n";
		print "<tr><td>Address1:</td><td COLSPAN=2 WIDTH=80%> $data[address1]</td></tr>\n";
		print "<tr><td>Address2:</td><td COLSPAN=2> $data[address2]</td></tr>\n";
		print "<tr><td>ZIP, City:</td><td COLSPAN=2> $data[postal_code] $data[city]</td></tr>\n";
		print "<tr><td>Country:</td><td COLSPAN=2> $data[country]</td></tr>\n";
		print "<tr><td>Contract:</td><td COLSPAN=2> $data[contact_first_name] $data[contact_last_name]</td></tr>\n";
		print "<tr><td>Phone:</td><td COLSPAN=2> $data[phone_number]</td></tr>\n";
		print "<tr><td>Fax::</td><td COLSPAN=2> $data[fax_number]</td></tr>\n";
		print "<tr><td>GSM:</td><td COLSPAN=2> $data[gsm_number]</td></tr>\n";
	} else {
		print "None.";
	}
	print "</td></tr>";
	print "</table></center>\n";
?>
</body>
</HTML>
<?php
// vim:ai:ts=4:sw=4
?>
