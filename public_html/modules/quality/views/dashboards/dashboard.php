
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12" >
				<div class="panel_s">
					<div class="panel-body wshop-dashboard">
						

						<h4 class="text-center"><?php echo _l('quality_check_statistics_by_teams'); ?></h4>
						<div class="row mtop15">
							<?php
							foreach ($quality_check_statistics_by_teams as $team_id => $quality_check) { ?>
								
								<div class="col-md-4 col-lg-4 col-sm-6 item item-card-hira" data-observation="68" >
									<div class="card item-card card-block">
										<div class="card item-card card-block">
											<div class="cardcoliner">
												<div class="catimg">

													<div class="cardworkertag">
														<span class="catdesc"><?php echo new_html_entity_decode($quality_check['team_name']); ?></span><br>
													</div>
													
												</div>

												<div class="cattexts">

													<div class="row">
														<div class="col-md-4">
															<div class="permitworkerdate">
																<div>
																	<a href="<?php echo admin_url('quality/quality_alerts?team_id='.$team_id); ?>" target="_blank" class="btn btn-sm btn-primary"><?php echo isset($quality_check['alert']) ? $quality_check['alert'] : 0 ; ?> <?php echo _l('quality_alerts'); ?></a>
																</div>
															</div>
														</div>

														<div class="col-md-8">
															<div class="col-md-12 panel-padding" >
																<a href="<?php echo admin_url('quality/quality_checks?team_id='.$team_id); ?>" target="_blank">
																	<table class="table  table-margintop">
																		<tbody>
																			<tr class="project-overview">
																				<td class="bold" width="70%"><?php echo _l('quality_draft'); ?></td>
																				<td class="text-right"><b><?php echo isset($quality_check['draft']) ? $quality_check['draft'] : 0 ?></b></td>
																			</tr>
																			<tr class="project-overview">
																				<td class="bold" width="70%"><?php echo _l('quality_to_do'); ?></td>
																				<td class="text-right"><b><?php echo isset($quality_check['to_do']) ? $quality_check['to_do'] : 0 ?></b></td>
																			</tr>
																			<tr class="project-overview">
																				<td class="bold" width="70%"><?php echo _l('quality_completed'); ?></td>
																				<td class="text-right"><b><?php echo isset($quality_check['completed']) ? $quality_check['completed'] : 0 ?></b></td>
																			</tr>
																			<tr class="project-overview">
																				<td class="bold" width="70%"><?php echo _l('quality_cancelled'); ?></td>
																				<td class="text-right"><b><?php echo isset($quality_check['cancelled']) ? $quality_check['cancelled'] : 0 ?></b></td>
																			</tr>
																		</tbody>
																	</table>
																</a>
															</div>
														</div>
													</div>
													
												</div>

											</div>
										</div>
									</div>
								</div>
							<?php } ?>
						</div>
						<div class="row mtop15">
							<div class="col-md-3">
								<div class="form-group" id="report-time">
									<label for="mo_months-report"><?php echo _l('period_datepicker'); ?></label><br />
									<select class="selectpicker" name="mo_months-report" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
										<option value=""><?php echo _l('report_sales_months_all_time'); ?></option>
										<option value="this_month"><?php echo _l('this_month'); ?></option>
										<option value="1"><?php echo _l('last_month'); ?></option>
										<option value="this_year"><?php echo _l('this_year'); ?></option>
										<option value="last_year"><?php echo _l('last_year'); ?></option>
										<option value="3" data-subtext="<?php echo _d(date('Y-m-01', strtotime("-2 MONTH"))); ?> - <?php echo _d(date('Y-m-t')); ?>"><?php echo _l('report_sales_months_three_months'); ?></option>
										<option value="6" data-subtext="<?php echo _d(date('Y-m-01', strtotime("-5 MONTH"))); ?> - <?php echo _d(date('Y-m-t')); ?>"><?php echo _l('report_sales_months_six_months'); ?></option>
										<option value="12" data-subtext="<?php echo _d(date('Y-m-01', strtotime("-11 MONTH"))); ?> - <?php echo _d(date('Y-m-t')); ?>"><?php echo _l('report_sales_months_twelve_months'); ?></option>
										<option value="custom"><?php echo _l('period_datepicker'); ?></option>
									</select>
								</div>
							</div>

							<div id="mo_date-range" class="hide ">
								<div class="row">
									<div class="col-md-3">
										<label for="mo_report-from" class="control-label"><?php echo _l('report_sales_from_date'); ?></label>
										<div class="input-group date">
											<input type="text" class="form-control datepicker" id="mo_report-from" autocomplete="off" name="mo_report-from">
											<div class="input-group-addon">
												<i class="fa fa-calendar calendar-icon"></i>
											</div>
										</div>
									</div>
									<div class="col-md-3">
										<label for="mo_report-to" class="control-label"><?php echo _l('report_sales_to_date'); ?></label>
										<div class="input-group date">
											<input type="text" class="form-control datepicker" disabled="disabled" autocomplete="off" id="mo_report-to" name="mo_report-to">
											<div class="input-group-addon">
												<i class="fa fa-calendar calendar-icon"></i>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div id="report_by_quality_check">
								</div>
							</div>
							<div class="col-md-6">
								<div id="report_by_quality_alert">
								</div>
							</div>
							
							<div class="clearfix"></div>
							<hr>

							
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<input type="hidden" name="inspection_status_filter">

<?php init_tail(); ?>
<?php 
require('modules/quality/assets/js/dashboards/dashboard_js.php');
?>

</body>
</html>
