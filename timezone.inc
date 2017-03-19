<?php
	$locale = $tsx_config->get("locale");
	$timezone = $tsx_config->get("timezone");
	
	//set the locale
	if ($locale != "")
		setlocale(LC_ALL, stripslashes($locale));

	//set the timezone
	if ($timezone != "") {
		//for >= PHP 5.1
		if(function_exists("date_default_timezone_set")) {
			date_default_timezone_set($timezone);
		// for PHP < 5.1
		} else {
			putenv("PHP_TZ=" . stripslashes($timezone));
			putenv("TZ=" . stripslashes($timezone));
		}
	} else {
		//for >= PHP 5.1
		if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get"))
			date_default_timezone_set(date_default_timezone_get());
	}

// vim:ai:ts=4:sw=4:filetype=php
?>
