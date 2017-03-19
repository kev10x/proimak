<?php

//Include file for date related functions

	//Base class for a form input. Holds the 'name' parameter
	class FormInput {
		var $name;

		function FormInput($name) {
			$this->name = $name;
		}
	}

	//Base class for a <select...> input. Defines functions which print the start/end tags
	class SelectInput extends FormInput {
		function printSelect() {
			//print the input
			echo "<select name=\"" . $this->name . "\" id=\"" . $this->name . "\">";
		}

		function printEndSelect() {
			echo "</select>";
		}
	}

	class HourInput extends SelectInput {

		function create($selectedIndex = 9) {
			global $tsx_config;
			$this->printSelect();

			//work out whether we want to display time in 12 or 24 hour format
			if ($tsx_config->get('timeformat') == "12")
				$this->create_12hour($selectedIndex);
			else
				$this->create_24hour($selectedIndex);

			$this->printEndSelect();
		}

		function create_12hour($selectedIndex = 9) {
			for ($i=0;$i<24;$i++) {
				if ($i == 0)
					$display_time = "12 am";
				elseif ($i == 12)
					$display_time = "12 pm";
				elseif ($i > 12 && $i<24)
					$display_time = $i-12 . " pm";
				else
					$display_time = "$i am";

				if ($i == $selectedIndex)
					echo "<option value=\"$i\" selected=\"selected\">$display_time</option>";
				else
					echo "<option value=\"$i\">$display_time</option>";
			}
		}

		function create_24hour($selectedIndex = 9) {
			for ($i=0;$i<24;$i++) {
				$display_time = "$i";

				if ($i == $selectedIndex)
					echo "<option value=\"$i\" selected=\"selected\">$display_time</option>";
				else
					echo "<option value=\"$i\">$display_time</option>";
			}
		}
	}

	class MinuteInput extends SelectInput {

		function create($selectedMinute = 0) {
			$this->printSelect();

			for ($i=0;$i<60;$i+=15) {
				echo "<option value=\"$i\"";
					if ($selectedMinute > $i - 2 && $selectedMinute < $i + 3)
						echo " selected=\"selected\"";
				printf(">%02d</option>\n", $i);
			}

			$this->printEndSelect();
		}
	}

	class MonthInput extends SelectInput {
		function create($selectedMonth = 1) {
			$this->printSelect();

			for ($i=1; $i<=12; $i++) {
				echo "<option value=\"$i\"";
				if ($i == $selectedMonth)
					echo " selected=\"selected\"";
				echo ">" . date("F", mktime(0,0,0,$i,1,2000)) . "</option>\n";
			}

			$this->printEndSelect();
		}
	}

	class DayInput extends SelectInput {

		function create($selectedDay = 1) {
			$this->printSelect();

			for ($i=1; $i<=31; $i++) {
				echo "<option value=\"$i\"";
				if ($i == $selectedDay)
					echo " selected=\"selected\"";
				echo ">$i</option>\n";
			}

			$this->printEndSelect();
		}
	}

	class AbsenceInput extends SelectInput {

		function create($selected="") 	{
			$this->printSelect();

			echo "<option value=\"1\"";
			if ("" == $selected)
				echo " selected=\"selected\"";
			echo "></option>\n";
			echo "<option value=\"2\"";
			if ("Holiday" == $selected)
				echo " selected=\"selected\"";
			echo ">Holiday</option>\n";
			echo "<option value=\"3\"";
			if ("Sick" == $selected)
				echo " selected=\"selected\"";
			echo ">Sick</option>\n";
			echo "<option value=\"4\"";
			if ("Military/Civil" == $selected)
				echo " selected=\"selected\"";
			echo ">Military/Civil</option>\n";
			echo "<option value=\"5\"";
			if ("Other Absence" == $selected)
				echo " selected=\"selected\"";
			echo ">Other Absence</option>\n";
			echo "<option value=\"6\"";
			if ("Apprentice School" == $selected)
				echo " selected=\"selected\"";
			echo ">Apprentice School</option>\n";

			$this->printEndSelect();
		}
	}

// vim:ai:ts=4:sw=4:filetype=php
?>
