<?php
require("class.AuthenticationManager.php");
require("class.CommandMenu.php");

//continue session
session_start();

//get the logged in user
$loggedInUser = $_SESSION['loggedInUser'];

//load local vars from superglobals
$errormsg = stripslashes($_REQUEST['errormsg']);

//define the command menu
$commandMenu->add(new TextCommand("Back", true, "javascript:back()"));

?>
<HTML>
	<head>
	<title>Error, <?php echo $loggedInUser; ?></title>
<?php
include ("header.inc");
?>
</head>
<body <?php include ("body.inc"); ?> >
<?php
include ("banner.inc");
include ("error.inc");
include ("footer.inc");
?>
</body>
</HTML>
<?php
// vim:ai:ts=4:sw=4
?>
