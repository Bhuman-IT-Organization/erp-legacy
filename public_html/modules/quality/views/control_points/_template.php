<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if(isset($control_point)){ ?>
	<div class="row">
		<div class="col-md-6">
			<div class="row">
				<div class="col-md-12">
					<table class="table border table-striped no-mtop">
						<tbody>
							<tr class="project-overview">
								<td class="bold" width="30%"><?php echo _l('quality_code'); ?></td>
								<td><?php echo format_control_point_number($control_point->id) ; ?></td>
							</tr>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_name'); ?></td>
								<td><?php echo html_entity_decode($control_point->name) ; ?></td>
							</tr>
							<?php if($control_point->item_id){ ?>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_item'); ?></td>
									<td><?php echo quality_get_item_description($control_point->item_id) ; ?></td>
								</tr>
							<?php } ?>
							
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_operation'); ?></td>
								<td><?php echo _l('quality_'.$control_point->operation) ; ?></td>
							</tr>
							<?php if($control_point->operation == 'manufacturing' && quality_get_status_modules('manufacturing')){ ?>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_work_order'); ?></td>
									<td><?php echo mrp_get_routing_detail_name($control_point->mrp_routing_detail_id) ; ?></td>
								</tr>
							<?php } ?>
							
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_team'); ?></td>
								<td><?php echo get_quality_team_name($control_point->team_id) ; ?></td>
							</tr>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_responsible'); ?></td>
								<td><?php echo get_staff_full_name($control_point->responsible_id) ; ?></td>
							</tr>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_datecreated'); ?></td>
								<td><?php echo _dt($control_point->datecreated) ; ?></td>
							</tr>
							
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="row">
				<div class="col-md-12">

					<table class="table border table-striped no-mtop">
						<tbody>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_control_per'); ?></td>
								<td><?php echo _l('quality_'.$control_point->control_per) ; ?></td>
							</tr>
							<?php if($control_point->control_per == 'quantity' && $control_point->partial_transfer_test == 1 ){ ?>
								<tr class="project-overview">
									<td class="bold" width="30%"><?php echo _l('quality_partial_transfer_test'); ?></td>
									<td><?php echo html_entity_decode($control_point->percentage) ; ?></td>
								</tr>
							<?php } ?>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_control_frequency'); ?></td>
								<td><?php echo _l('quality_'.$control_point->control_frequency) ; ?></td>
							</tr>
							<?php if($control_point->control_frequency == 'randomly'){ ?>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_every_percent_of_transfer'); ?></td>
									<td><?php echo html_entity_decode($control_point->every_percent_of_transfer) ; ?></td>
								</tr>
							<?php } ?>
							<?php if($control_point->control_frequency == 'periodically'){ ?>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_periodically_every_custom'); ?></td>
									<td><?php echo html_entity_decode($control_point->periodically_every_custom.' '.$control_point->periodically_type_custom) ; ?></td>
								</tr>
							<?php } ?>


							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_type'); ?></td>
								<td><?php echo _l('quality_'.$control_point->type) ; ?></td>
							</tr>
							<?php if($control_point->operation != 'manufacturing' && $control_point->control_per == 'quantity'){ ?>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_failure_location'); ?></td>
									<td><?php echo quality_get_failure_location_name($control_point->failure_location_ids) ; ?></td>
								</tr>
							<?php } ?>
							
							<?php if($control_point->type == 'measure'){ ?>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_norm'); ?></td>
									<td><?php echo html_entity_decode($control_point->norm.' '.$control_point->unit_of_measurement) ; ?></td>
								</tr>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_tolerance'); ?></td>
									<td><?php echo html_entity_decode($control_point->tolerance_from.' -> '.$control_point->tolerance_to.' '.$control_point->unit_of_measurement) ; ?></td>
								</tr>
								
							<?php } ?>

							
						</tbody>
					</table>

				</div>

			</div>
		</div>
	</div>
	<hr>

	<?php if(!is_null($control_point->instructions) && strlen($control_point->instructions) > 0){ ?>
		<h4 class="tw-font-semibold"><?php echo _l('quality_instructions') ?></h4>
		<p class=""><?php echo new_html_entity_decode(check_for_links($control_point->instructions)); ?></p>
	<?php } ?>

	<?php if(($control_point->type == 'pass_fail' || $control_point->type == 'measure') && !is_null($control_point->message_if_failure) && strlen($control_point->message_if_failure) > 0){ ?>
		<h4 class="tw-font-semibold"><?php echo _l('quality_message_if_failure') ?></h4>
		<p class=""><?php echo new_html_entity_decode(check_for_links($control_point->message_if_failure)); ?></p>
	<?php } ?>

	<?php if(!is_null($control_point->note) && strlen($control_point->note) > 0){ ?>
		<h4 class="tw-font-semibold"><?php echo _l('quality_note') ?></h4>
		<p class=""><?php echo new_html_entity_decode(check_for_links($control_point->note)); ?></p>
	<?php } ?>
	
	<!-- attachments -->
	<?php if(isset($control_point_attachments) && count($control_point_attachments) > 0){ ?>
		<div class="row">
			<div class="col-md-12">
				<h4 class="tw-font-semibold">
					<?php echo _l('quality_attachments'); ?>
				</h4>
				<div id="contract_attachments" class="mtop30 ">

					<?php
					$data = '<div class="row" id="attachment_file">';
					foreach($control_point_attachments as $attachment) {
						$data .= '<div class="col-md-4 pdf_attachment">';
						$href_url = site_url('modules/quality/uploads/control_points/'.$attachment['rel_id'].'/'.$attachment['file_name']).'" download';
						if(!empty($attachment['external'])){
							$href_url = $attachment['external_link'];
						}
						$data .= '<div class="col-md-9">';

						$data .= '<div>';
						$data .= '<div class="pull-left"><i class="'.get_mime_class($attachment['filetype']).'"></i></div>';
						$data .= '<a href="'.$href_url.'>'.$attachment['file_name'].'</a>';
						$data .= '</div>';
						$data .= '</div>';
						$data .= '<div class="col-md-3 text-right">';
						$data .= '<a class="btn btn-sm" name="preview-btn" onclick="preview_file(this); return false;" rel_id = "'.$attachment['rel_id'].'" id = "'.$attachment['id'].'" data-toggle="tooltip" title data-original-title="'._l("preview_file").'"><i class="fa fa-eye"></i></a>';
						if(is_admin() || has_permission('wshop_control_points', '', 'delete') ){
							$data .= '<a href="#" class="text-danger btn btn-sm" onclick="delete_control_point_attachment(this,'.$attachment['id'].'); return false;"><i class="fa fa fa-times"></i></a>';
						}
						$data .= '</div>';
						$data .= '<div class="clearfix"></div><hr class="mtop1 mbot5">';
						$data .= '</div>';
					}
					$data .= '</div>';
					echo new_html_entity_decode($data);
					?>

					<!-- check if edit contract => display attachment file end-->
				</div>
				<div id="pdf_file_data"></div>
			</div>
		</div>
	<?php } ?>

	<?php } ?>