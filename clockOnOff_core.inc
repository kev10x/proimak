<!-- core of clock on/off form -->

<?php include("timesheet_face_part_2.inc"); ?>

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" class="outer_table">
		<form action="clock_action.php" method="post" name="mainForm" id="theForm">
		<input type="hidden" name="year" value="<?php echo $year; ?>" />
		<input type="hidden" name="month" value="<?php echo $month; ?>" />
		<input type="hidden" name="day" value="<?php echo $day; ?>" />
		<input type="hidden" id="client_id" name="client_id" value="<?php echo $client_id; ?>" />
		<input type="hidden" id="proj_id" name="proj_id" value="<?php echo $proj_id; ?>" />
		<input type="hidden" id="task_id" name="task_id" value="<?php echo $task_id; ?>" />
		<input type="hidden" name="clockonoff" value="" />
		<input type="hidden" name="fromPopupWindow" value="<?php echo $fromPopup; ?>" />
		<input type="hidden" name="origin" value="<?php echo $_SERVER["PHP_SELF"]; ?>" />
		<input type="hidden" name="destination" value="<?php echo $destination; ?>" />

		<tr>
			<td>
				<table width="100%" border="0" cellpadding="1" cellspacing="2" class="table_body">
					<tr>
						<td>
							<table width="100%" border="0">
								<tr>
									<td align="left" width="100%" nowrap>
											<table width="100%" border="0" cellspacing="0" cellpadding="0">
												<tr>
													<td><table width="50"><tr><td>Client:</td></tr></table></td>
													<td width="100%">
														<select id="clientSelect" name="clientSelect" onChange="onChangeClientSelect();" style="width: 100%;" />
													</td>
												</tr>
											</table>
									</td>
								</tr>
								<tr>
									<td align="left" width="100%" nowrap>
											<table width="100%" border="0" cellspacing="0" cellpadding="0">
												<tr>
													<td><table width="50"><tr><td>Project:</td></tr></table></td>
													<td width="100%">
														<select id="projectSelect" name="projectSelect" onChange="onChangeProjectSelect();" style="width: 100%;" />
													</td>
												</tr>
											</table>
									</td>
								</tr>
								<tr>
									<td align="left" width="100%">
											<table width="100%" border="0" cellspacing="0" cellpadding="0">
												<tr>
													<td><table width="50"><tr><td>Task:</td></tr></table></td>
													<td width="100%">
														<select id="taskSelect" name="taskSelect" onChange="onChangeTaskSelect();" style="width: 100%;" />
													</td>
												</tr>
											</table>
									</td>
								</tr>
								<tr>
									<td>
									<?php if(isset($stopwatch)) { ?>
										<table width="100%" height="100%" border="0" cellpadding="0" cellaspacing="0">
											<tr height="100%">
												<td valign="center">
													<table width="100%" height="100%" border="0" cellpadding="0" cellaspacing="0">
														<tr height="100%">
															<td align="right">
																<a href="javascript:doClockonoff('clockonnow')"><img src="images/clock-green.gif" alt="" width="48" height="48" border="0" align="absmiddle" /></a>
															</td>
															<td nowrap>
																<a href="javascript:doClockonoff('clockonnow')"><font size="4" color="#0DB400" face="Arial">Clock on now</font></a>
															</td>
															<td align="right">
																<a href="javascript:doClockonoff('clockoffnow')"><img src="images/clock-red.gif" width="48" height="48" border="0" align="absmiddle" alt="" /></a>
															</td>
															<td nowrap>
																<a href="javascript:doClockonoff('clockoffnow')"><font size="4" color="#E81500" face="Arial">Clock off now</font></a>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
										<?php } else { ?>
										<table width="100%" border="0">
											<tr>
												<td align="center">
													<table width="300" border="0" class="clock_on_box">
														<tr>
															<td valign="top" align="left" class="clock_on_text">
																<input type="checkbox" name="clock_on_check" id="clock_on_check" onclick="enableClockOn();" />Clock on at:
															</td>
															<td valign="middle">
																<?php // If the current day is today:
																if ($currentDate == $realTodayDate): ?>
																	<input type="radio" name="clock_on_radio" id="clock_on_radio_date" value="date" onclick="enableClockOn();" checked="checked"  />
																<?php endif; ?>
																<?php $hourInput = new HourInput("clock_on_time_hour");
																	$hourInput->create(10); ?>
																:
																<?php $minuteInput = new MinuteInput("clock_on_time_min");
																	$minuteInput->create(); ?>
															</td>
															<td>
																<img src="images/clock-green-sml.gif" border="0" alt="" />
															</td>
														</tr>
														<?php // If the current day is today:
														if ($currentDate == $realTodayDate): ?>
														<tr>
															<td>&nbsp;</td>
															<td valign="middle" align="left" class="clock_on_text">
																<input type="radio" name="clock_on_radio" id="clock_on_radio_now" value="now" onclick="enableClockOn();" />	now
															</td>
															<td>&nbsp;</td>
														</tr>
														<?php endif; ?>
													</table>
												</td>
											</tr>
											<tr>
												<td align="center">
													<table width="300" border="0" class="clock_off_box">
														<tr>
															<td valign="top" align="left" class="clock_off_text">
																<input type="checkbox" name="clock_off_check" id="clock_off_check" onclick="enableClockOff();" />Clock off at:
															</td>
															<td valign="middle">
																<?php // If the current day is today:
																if ($currentDate == $realTodayDate): ?>
																	<input type="radio" name="clock_off_radio" id="clock_off_radio_date" value="date" onclick="enableClockOff();" />
																<?php endif; ?>
																<?php $hourInput = new HourInput("clock_off_time_hour");
																	$hourInput->create(17); ?>
																:
																<?php $minuteInput = new MinuteInput("clock_off_time_min");
																	$minuteInput->create(); ?>
															</td>
															<td>
																<img src="images/clock-red-sml.gif" border="0" alt="" />
															</td>
														</tr>
														<?php // If the current day is today:
														if ($currentDate == $realTodayDate): ?>
														<tr>
															<td>&nbsp;</td>
															<td valign="middle" align="left" class="clock_off_text">
																<input type="radio" name="clock_off_radio" id="clock_off_radio_now" value="now" onclick="enableClockOff();" checked="checked"  />now
															</td>
															<td>&nbsp;</td>
														</tr>
														<?php endif; ?>
													</table>
												</td>
											</tr>
											<tr>
												<td align="center">
													<input type="button" value="Clock on and/or off" name="submitButton" id="submitButton" onclick="onSubmit();" />
												</td>
											</tr>
										</table>
										<?php } ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</form>
	</table>

<!-- include the timesheet face up until the end -->
<?php 
include("timesheet_face_part_3.inc"); 

// vim:ai:ts=4:sw=4:filetype=php
?>
