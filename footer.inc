<?php
parse_and_echo(stripslashes($tsx_config->get("footerhtml")));
?>
<p>
<table width="100%" class="grey_area">
	<tr>
		<td width="10px"> </td>
		<td class="timesheet_logo" width="30%">
			<?php 
			$ver = $tsx_config->get("version");
			echo "INQB8 Business Technologies; v$ver";
			?>
		</td>
		<td> </td>
		<td align="center" width="30%">
			&copy; 2008-2017 <a href="https://inqb8.co.za">INQB8 Development Team</a><br />
		</td>
		<td> </td>
		<td align="right" width="30%">
			<!--Usage under terms of <a href="http://www.gnu.org/copyleft/gpl.html">GPL</a>.-->
		</td>
		<td width="10px"> </td>
	</tr>
</table>
