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
							list($s_prev_year,$s_next_year) = getPrevNextYear($start_time);
							list($e_prev_year,$e_next_year) = getPrevNextYear($end_time);
							if($start_month<7)
								draw_month_year_navigation_with_end_dates($s_prev_year,$e_prev_year,"wide");
							else
								draw_month_year_navigation_with_end_dates($start_time,$end_time,"wide");
						?>
						</td><td width="2">&nbsp;</td><td>
						<?php 
							if($start_month<7)
								draw_month_year_navigation_with_end_dates($start_time,$end_time,"wide");
							else 
								draw_month_year_navigation_with_end_dates($s_next_year,$e_next_year,"wide");
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
