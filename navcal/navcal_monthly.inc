<?php
include("navcal/common.inc");
?>
<!-- Monthly Navigation Calendars > -->
	<table width="100%">
		<tr>
			<td width="25%">
				<table>
					<tr>
						<td>
						<?php 
							if($month<7)
								draw_month_year_navigation($year-1,"wide");
							else
								draw_month_year_navigation($year,"wide");
						?>
						</td><td width="2">&nbsp;</td><td>
						<?php 
							if($month<7)
								draw_month_year_navigation($year,"wide");
							else
								draw_month_year_navigation($year+1,"wide");
						?>
						</td>
					</tr>
				</table>
			</td>
			<?php if($motd) include("navcal/motd.inc"); ?>
		</tr>
	</table>
<!-- End Montly Navigation Calendars -->
<?php
// vim:ai:ts=4:sw=4:filetype=php
?>
