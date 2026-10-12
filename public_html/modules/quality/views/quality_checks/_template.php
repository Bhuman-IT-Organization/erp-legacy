<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if(isset($quality_check)){ ?>
	<div class="row">
		<div class="col-md-6">
			<div class="row">
				<div class="col-md-12">
					<table class="table border table-striped no-mtop">
						<tbody>
							<tr class="project-overview">
								<td class="bold" width="30%"><?php echo _l('quality_code'); ?></td>
								<td><?php echo format_quality_check_number($quality_check->id) ; ?></td>
							</tr>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_name'); ?></td>
								<td><?php echo html_entity_decode($quality_check->name) ; ?></td>
							</tr>
							<?php if($quality_check->control_point_id){ ?>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_control_point'); ?></td>
									<td><a href="<?php echo admin_url('quality/control_point_detail/'.$quality_check->control_point_id.'?tab=detail'); ?>" target="_blank"><?php echo format_control_point_number($quality_check->control_point_id); ?></a></td>
								</tr>
							<?php } ?>
							<?php if($quality_check->item_id){ ?>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_item'); ?></td>
									<td><?php echo quality_get_item_description($quality_check->item_id) ; ?></td>
								</tr>
							<?php } ?>
							
							<tr class="project-overview">
								<?php
								if($quality_check->operation == 'receipts'){
									$operation_id = $quality_check->goods_receipt_id;
								} elseif($quality_check->operation == 'internal_transfers'){
									$operation_id = $quality_check->internal_delivery_id;
								} elseif($quality_check->operation == 'delivery_orders'){
									$operation_id = $quality_check->goods_delivery_id;
								} elseif($quality_check->operation == 'manufacturing'){
									$operation_id = $quality_check->work_order_id;
								}
								$operation_detail = quality_get_operation_detail($quality_check->operation, $operation_id);
								?>
								<td class="bold"><?php echo _l('quality_operation'); ?></td>
								<td><?php echo _l('quality_'.$quality_check->operation).': '. $operation_detail ; ?></td>
							</tr>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_team'); ?></td>
								<td><?php echo get_quality_team_name($quality_check->team_id) ; ?></td>
							</tr>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_created_by'); ?></td>
								<td><?php echo get_staff_full_name($quality_check->staffid) ; ?></td>
							</tr>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_datecreated'); ?></td>
								<td><?php echo _dt($quality_check->datecreated) ; ?></td>
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
								<td><?php echo _l('quality_'.$quality_check->control_per) ; ?></td>
							</tr>
							<?php if($quality_check->control_per == 'quantity' && $quality_check->partial_transfer_test == 1 ){ ?>
								<tr class="project-overview">
									<td class="bold" width="30%"><?php echo _l('quality_partial_transfer_test'); ?></td>
									<td><?php echo html_entity_decode($quality_check->percentage) ; ?></td>
								</tr>
							<?php } ?>


							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_type'); ?></td>
								<td><?php echo _l('quality_'.$quality_check->type) ; ?></td>
							</tr>
							<?php if($quality_check->operation != 'manufacturing' && $quality_check->control_per == 'quantity'){ ?>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_failure_location'); ?></td>
									<td><?php echo quality_get_failure_location_name($quality_check->failure_location_ids) ; ?></td>
								</tr>
							<?php } ?>
							<?php if($quality_check->type == 'measure'){ ?>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_norm'); ?></td>
									<td><?php echo html_entity_decode($quality_check->norm.' '.$quality_check->unit_of_measurement) ; ?></td>
								</tr>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_tolerance'); ?></td>
									<td><?php echo html_entity_decode($quality_check->tolerance_from.' -> '.$quality_check->tolerance_to.' '.$quality_check->unit_of_measurement) ; ?></td>
								</tr>
								
							<?php } ?>

							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_status'); ?></td>
								<td><?php echo render_quality_check_status_html($quality_check->id, '', $quality_check->status) ; ?></td>
							</tr>
						</tbody>
					</table>

				</div>

			</div>
		</div>
	</div>
	<hr>

	<?php if(!is_null($quality_check->instructions) && strlen($quality_check->instructions) > 0){ ?>
		<h4 class="tw-font-semibold"><?php echo _l('quality_instructions') ?></h4>
		<p class=""><?php echo new_html_entity_decode(check_for_links($quality_check->instructions)); ?></p>
	<?php } ?>

	<?php if(($quality_check->type == 'pass_fail' || $quality_check->type == 'measure') && !is_null($quality_check->message_if_failure) && strlen($quality_check->message_if_failure) > 0){ ?>
		<h4 class="tw-font-semibold"><?php echo _l('quality_message_if_failure') ?></h4>
		<p class=""><?php echo new_html_entity_decode(check_for_links($quality_check->message_if_failure)); ?></p>
	<?php } ?>

	<?php if(!is_null($quality_check->note) && strlen($quality_check->note) > 0){ ?>
		<h4 class="tw-font-semibold"><?php echo _l('quality_note') ?></h4>
		<p class=""><?php echo new_html_entity_decode(check_for_links($quality_check->note)); ?></p>
	<?php } ?>
	
	<!-- attachments -->
	<?php if(isset($quality_check_attachments) && count($quality_check_attachments) > 0){ ?>
		<div class="row">
			<div class="col-md-12">
				<h4 class="tw-font-semibold">
					<?php echo _l('quality_attachments'); ?>
				</h4>
				<div id="contract_attachments" class="mtop30 ">

					<?php
					$data = '<div class="row" id="attachment_file">';
					foreach($quality_check_attachments as $attachment) {
						$data .= '<div class="col-md-4 pdf_attachment">';
						$href_url = site_url('modules/quality/uploads/quality_checks/'.$attachment['rel_id'].'/'.$attachment['file_name']).'" download';
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
						if(is_admin() || has_permission('quality_check', '', 'delete') ){
							$data .= '<a href="#" class="text-danger btn btn-sm" onclick="delete_quality_check_attachment(this,'.$attachment['id'].'); return false;"><i class="fa fa fa-times"></i></a>';
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