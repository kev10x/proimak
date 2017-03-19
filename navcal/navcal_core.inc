<!-- Daily Navigation Calendar -->
<?php
$todayDate = mktime(0,0,0,$month,$day,$year);
$startDateCalendar = mktime(0,0,0, $month, 1, $year);

$startDayOfWeek = $tsx_config->get("weekstartday");
$dowForFirstOfMonth = date('w',mktime(0,0,0,$month,1,$year));
$leadInDays = $dowForFirstOfMonth - $startDayOfWeek;
if ($leadInDays < 0)
	$leadInDays += 7;
$firstPrintedDate = strtotime(date("d M Y H:i:s",$startDateCalendar) . " -$leadInDays days");

if(!isset($post)) $post ="";

?>

	<table width="224" border="1" bordercolor="black" cellspacing="0" cellpadding="0">
		<tr>
			<td width="100%" class="face_padding_cell" style="background-color: #000788">

			<!-- print calendar header (prev month year next) -->

				<table width="100%" border="0">
					<tr >
						<td align="left" nowrap class="navcal_header">
							<?php 
							list($prev_month,$next_month) = getPrevNextMonth($todayDate);
							print "<a href=\"$_SERVER[PHP_SELF]?$post";
							$dti=getdate($prev_month);
							print 	"&amp;year=".$dti["year"].	
									"&amp;month=".$dti["mon"].
									"&amp;day=".$dti["mday"].
									"\">Prev</a>";
							?>
						</td>
						<td align="center" nowrap class="navcal_header">
							<?php echo date('M Y',$startDateCalendar); ?>
						</td>
						<td align="right" nowrap class="navcal_header">
							<?php
							print "<a href=\"$_SERVER[PHP_SELF]?$post";
							$dti=getdate($next_month);
							print 	"&amp;year=".$dti["year"].	
									"&amp;month=".$dti["mon"].
									"&amp;day=".$dti["mday"].
									"\">Next</a>";
							?>
						</td>
					</tr>
				</table>

				<!-- print calendar dates  -->
				<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
					<tr>
						<td>
							<table border="0" cellspacing="0" cellpadding="2" class="table_body">
								<tr class="inner_table_head">
									<?php
										//print the days of the week
										$currentDayDate = $firstPrintedDate;
										for ($i=0; $i<7; $i++) {
											$currentDayStr = strftime("%a", $currentDayDate);
											print " <td width=\"25px\" class=\"inner_table_column_heading\" align=\"center\">$currentDayStr</td>\n";
											$currentDayDate = strtotime(date("d M Y H:i:s",$currentDayDate) . " +1 days");
										}
									?>
								</tr>
								<tr>
									<?php {

										//define the variable dayRow
										$dayRow = 0;

										// Print last months' days spots.
										for ($i=0; $i<$leadInDays; $i++) {
											print "<td width=\"25px\" height=\"25%\" class=\"calendar_cell_disabled_middle\">&nbsp;</td>\n ";
											$dayRow++;
										}

										$i=0; $navday = 1;
										while (checkdate($month, $navday, $year)) {

											// New Week.
											if ((($dayRow % 7) == 0) && ($dowForFirstOfMonth != 0)) {
												print "</tr>\n<tr>\n";
											} else
												$dowForFirstOfMonth = 1;

											//define subtable
											if (($dayRow % 7) == 6)
												print "<td width=\"25px\" height=\"25%\" align=\"center\" valign=\"top\" class=\"calendar_cell_right\">";
											else
												print "<td width=\"25px\" height=\"25%\" align=\"center\" valign=\"top\" class=\"calendar_cell_middle\">";

											if($navday == $day) 
												print "<font color=\"#CC9900\"><b>$navday</b></font>";
											else
												print "<a href=\"".$_SERVER["PHP_SELF"]."?$post".
													"&amp;year=$year&amp;month=$month&amp;day=$navday\">$navday</a>";

											print " </td>\n";

											$navday++;
											$dayRow++;
										}
										// Print the rest of the calendar.
										while (($dayRow % 7) != 0) {
											if (($dayRow % 7) == 6)
												print " <td width=\"25px\" height=\"25%\" class=\"calendar_cell_disabled_right\">&nbsp;</td>\n ";
											else
												print " <td width=\"25px\" height=\"25%\" class=\"calendar_cell_disabled_middle\">&nbsp;</td>\n ";
											$dayRow++;
										}
									} ?>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<!-- End calendar dates -->
			</td>
		</tr>
	</table>
<!-- End Daily Navigation Calendar -->
<?php
// vim:ai:ts=4:sw=4:filetype=php
?>
