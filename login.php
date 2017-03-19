	<?php
	// Authenticate
	require("class.AuthenticationManager.php");
	
	//check that this form has been submitted
	if (isset($_POST["username"]) && isset($_POST["password"])) {
		//try logging the user in
		if (!$authenticationManager->login(mysql_real_escape_string($_POST["username"]), mysql_real_escape_string($_POST["password"])))
			$loginFailure = true;
		else {
			if (!empty($_REQUEST["redirect"]))
				header("Location: $_REQUEST[redirect]");
			else
				gotoStartPage();
	
			exit();
		}
	}
	else
		//destroy the session by logging out
		$authenticationManager->logout();
	
	function printMessage($message) {
		print "<tr>" .
					"	<td>&nbsp;</td>" .
					"	<td colspan=\"3\">" .
					"		<table width=\"100%\" border=\"0\" bgcolor=\"black\" cellspacing=\"0\" cellpadding=\"1\">" .
					"			<tr>" .
					"				<td>" .
					"					<table width=\"100%\" border=\"0\" bgcolor=\"yellow\">" .
					"						<tr><td class=\"login_error\">$message</td></tr>" .
					"					</table>" .
					"				</td>" .
					"			</tr>" .
					"		</table>" .
					"	</td>" .
					"</tr>";
	}
	
	$redirect = isset($_REQUEST["redirect"]) ? $_REQUEST["redirect"] : "";
	
	?>
	
	<html>
	<head>
	<title>ProIMAK Login</title>

	<?php include ("header.inc");	?>
	
	</head>
	<div class="row">
	<div class="container">
	
	<body style="background-image:url("images/timesheet.jpg")" onLoad="document.loginForm.username.focus();">
	
	<form action="login.php" method="POST" name="loginForm" style="margin: 0px; padding-top:100px;">
	<input type="hidden" name="redirect" value="<?php echo $redirect; ?>" />


	
		<div class="row"><div class="col-md-offset-3 com-md-6"><img class="login_image img-responsive" src="images/clock-icon.jpg"/></div></div><br>
		<div class="row"><div class="col-md-offset-3 com-md-6"><input type="text" name="username" label="Username" maxlength="25" /></div></div><br>
		<div class="row"><div class="col-md-offset-3 com-md-6"><input type="password" name="password" label="PAssword" maxlength="25" /></div></div><br>	
		<div class="row"><div class="col-md-offset-3 com-md-6"><input type="submit" name="Login" value="submit" /></div></div>		
	
					
	
					<?php	if (isset($loginFailure))
								printMessage($authenticationManager->getErrorMessage());
							else if (isset($_REQUEST["clearanceRequired"]))
								printMessage("$_REQUEST[clearanceRequired] clearance is required for the page you have tried to access.");
					?>
	
	
	
	
	</form>
	
	</body>
	</div>
	</div>
	</html>
	<?php
	// vim:ai:ts=4:sw=4
	?>
