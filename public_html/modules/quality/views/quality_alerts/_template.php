<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if(isset($quality_alert)){ ?>
	<div class="row">
		<div class="col-md-6">
			<div class="row">
				<div class="col-md-12">
					<table class="table border table-striped no-mtop no-mbot">
						<tbody>
							<tr class="project-overview">
								<td class="bold" width="30%"><?php echo _l('quality_code'); ?></td>
								<td><?php echo format_quality_alert_number($quality_alert->id) ; ?></td>
							</tr>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_name'); ?></td>
								<td><?php echo html_entity_decode($quality_alert->name) ; ?></td>
							</tr>
							<tr class="project-overview">
								<td class="bold" width="30%"><?php echo _l('quality_check'); ?></td>
								<td><a href="<?php echo admin_url('quality/quality_check_detail/'.$quality_alert->quality_check_id); ?>" target="_blank"><?php echo format_quality_check_number($quality_alert->quality_check_id) ; ?></a></td>
							</tr>
							<?php if($quality_alert->item_id){ ?>
								<tr class="project-overview">
									<td class="bold"><?php echo _l('quality_item'); ?></td>
									<td><?php echo quality_get_item_description($quality_alert->item_id) ; ?></td>
								</tr>
							<?php } ?>
							<tr class="project-overview">
								<?php
								if($quality_alert->operation == 'receipts'){
									$operation_id = $quality_alert->goods_receipt_id;
								} elseif($quality_alert->operation == 'internal_transfers'){
									$operation_id = $quality_alert->internal_delivery_id;
								} elseif($quality_alert->operation == 'delivery_orders'){
									$operation_id = $quality_alert->goods_delivery_id;
								} elseif($quality_alert->operation == 'manufacturing'){
									$operation_id = $quality_alert->work_order_id;
								}
								$operation_detail = quality_get_operation_detail($quality_alert->operation, $operation_id);
								?>
								<td class="bold"><?php echo _l('quality_operation'); ?></td>
								<td><?php echo _l('quality_'.$quality_alert->operation).': '.$operation_detail ; ?></td>
							</tr>
							
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_created_by'); ?></td>
								<td><?php echo get_staff_full_name($quality_alert->staffid) ; ?></td>
							</tr>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_datecreated'); ?></td>
								<td><?php echo _dt($quality_alert->datecreated) ; ?></td>
							</tr>
							
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="row">
				<div class="col-md-12">

					<table class="table border table-striped no-mtop no-mbot">
						<tbody>

							<tr class="project-overview">
								<td class="bold"><?php echo _l('tags'); ?></td>
								<td>
									<?php
									if (isset($quality_alert)) {
										$tags = get_tags_in($quality_alert->id, 'quality_alert');
										if (count($tags) > 0) {
											echo render_tags($tags);
											echo '<div class="clearfix"></div>';
										} else {
											echo '-';
										}
									}
									?>
								</td>
							</tr>
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_team'); ?></td>
								<td><?php echo get_quality_team_name($quality_alert->team_id) ; ?></td>
							</tr>

							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_responsible'); ?></td>
								<td><?php echo get_staff_full_name($quality_alert->responsible_id) ; ?></td>
							</tr>
							
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_priority'); ?></td>
								<td><?php echo render_quality_alert_priority_html($quality_alert->id, '', $quality_alert->priority) ; ?></td>
							</tr>

							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_root_cause'); ?></td>
								<td><?php echo render_quality_alert_root_cause_html($quality_alert->id, '', $quality_alert->root_cause) ; ?></td>
							</tr>
							
							<tr class="project-overview">
								<td class="bold"><?php echo _l('quality_status'); ?></td>
								<td><?php echo render_quality_alert_status_html($quality_alert->id, '', $quality_alert->status) ; ?></td>
							</tr>
						</tbody>
					</table>

				</div>

			</div>
		</div>
	</div>
	<hr>

	<div class="horizontal-scrollable-tabs preview-tabs-top">
		<div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
		<div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
		<div class="horizontal-tabs">
			<ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
				<li role="presentation" class="<?php if(!$this->input->get('tab') || (!(strpos($this->input->get('tab'),'detail') === false))){echo 'active';} ?>">
					<a href="#detail" aria-controls="detail" role="tab" data-toggle="tab">
						<span class="fa-solid fa-bolt"></span>&nbsp;<?php echo _l('quality_details'); ?>
					</a>
				</li>
				<li role="presentation"  onclick="init_rel_tasks_table(<?php echo new_html_entity_decode($quality_alert->id); ?>,'quality_alert'); return false;" class="<?php if(!$this->input->get('tab') || $this->input->get('tab') == 'task'){echo 'active';} ?>">
					<a href="#task" aria-controls="task" role="tab" data-toggle="tab">
						<span class="fa fa-history"></span>&nbsp;<?php echo _l('tasks'); ?>
					</a>
				</li>

			</ul>
		</div>
	</div>

	<div class="tab-content">
		<div role="tabpanel" class="tab-pane <?php if(!$this->input->get('tab') || (!(strpos($this->input->get('tab'),'detail') === false))){echo 'active';} ?>"  id="detail">

	<?php if(!is_null($quality_alert->description) && strlen($quality_alert->description) > 0){ ?>
		<h4 class="tw-font-semibold"><?php echo _l('quality_description') ?></h4>
		<p class=""><?php echo new_html_entity_decode(check_for_links($quality_alert->description)); ?></p>
	<?php } ?>

	<?php if(!is_null($quality_alert->corrective_actions) && strlen($quality_alert->corrective_actions) > 0){ ?>
		<h4 class="tw-font-semibold"><?php echo _l('quality_corrective_actions') ?></h4>
		<p class=""><?php echo new_html_entity_decode(check_for_links($quality_alert->corrective_actions)); ?></p>
	<?php } ?>

	<?php if(!is_null($quality_alert->preventive_actions) && strlen($quality_alert->preventive_actions) > 0){ ?>
		<h4 class="tw-font-semibold"><?php echo _l('quality_preventive_actions') ?></h4>
		<p class=""><?php echo new_html_entity_decode(check_for_links($quality_alert->preventive_actions)); ?></p>
	<?php } ?>
	
	<!-- attachments -->
	<?php if(isset($quality_alert_attachments) && count($quality_alert_attachments) > 0){ ?>
		<div class="row">
			<div class="col-md-12">
				<h4 class="tw-font-semibold">
					<?php echo _l('quality_attachments'); ?>
				</h4>
				<div id="contract_attachments" class="mtop30 ">

					<?php
					$data = '<div class="row" id="attachment_file">';
					foreach($quality_alert_attachments as $attachment) {
						$data .= '<div class="col-md-4 pdf_attachment">';
						$href_url = site_url('modules/quality/uploads/quality_alerts/'.$attachment['rel_id'].'/'.$attachment['file_name']).'" download';
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
						if(is_admin() || has_permission('quality_alert', '', 'delete') ){
							$data .= '<a href="#" class="text-danger btn btn-sm" onclick="delete_quality_alert_attachment(this,'.$attachment['id'].'); return false;"><i class="fa fa fa-times"></i></a>';
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

</div>
<div role="tabpanel" class="tab-pane <?php if(!$this->input->get('tab') || $this->input->get('tab') == 'task'){echo 'active';} ?>"  id="task">
	<div class="row">
		<div class="col-md-12">
			<?php init_relation_tasks_table(array('data-new-rel-id'=>$quality_alert->id,'data-new-rel-type'=>'quality_alert')); ?>
		</div>
	</div>
</div>
</div>


	<?php } ?>