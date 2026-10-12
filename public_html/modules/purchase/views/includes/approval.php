<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="_buttons">
	<a href="#" class="btn btn-info pull-left" onclick="new_approval_setting(); return false;"><?php echo _l('new_approval_setting'); ?></a>
</div>
<div class="clearfix"></div>
<hr class="hr-panel-heading" />
<div class="clearfix"></div>
<table class="table dt-table">
	<thead>
		<th><?php echo _l('id'); ?></th>
		<th><?php echo _l('name'); ?></th>
		<th><?php echo _l('related'); ?></th>
		<th><?php echo _l('options'); ?></th>
	</thead>
	<tbody>
	<?php foreach($approval_setting as $value){ ?>
		<tr>
		   <td><?php echo pur_html_entity_decode($value['id']); ?></td>
		   <td><?php echo pur_html_entity_decode($value['name']); ?></td>
		   <td><?php echo _l($value['related']); ?></td>
		   <td>
		     <a href="#" onclick="edit_approval_setting(this,<?php echo pur_html_entity_decode($value['id']); ?>); return false" data-name="<?php echo pur_html_entity_decode($value['name']); ?>" data-related="<?php echo pur_html_entity_decode($value['related']); ?>" data-department="<?php echo isset($value['department']) ? pur_html_entity_decode($value['department']) : ''; ?>" data-approval_type="<?php echo pur_html_entity_decode($value['approval_type']); ?>" data-setting='<?php echo pur_html_entity_decode($value['setting']); ?>' class="btn btn-default btn-icon"><i class="fa fa-pencil-square"></i></a>
		      <a href="<?php echo admin_url('purchase/delete_approval_setting/'.$value['id']); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
		   </td>
		</tr>
	<?php } ?>
	</tbody>
</table>

<?php
$hr_record_status = 0; 
if(get_status_modules_pur('hr_profile') == true){
	$hr_record_status = 1;
} ?>

<div class="modal fade" id="approval_setting_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog withd_1k" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">
					<span class="edit-title"><?php echo _l('edit_approval_setting'); ?></span>
					<span class="add-title"><?php echo _l('new_approval_setting'); ?></span>
				</h4>
			</div>
			<?php echo form_open('purchase/approval_setting',array('id'=>'approval-setting-form')); ?>
			<?php echo form_hidden('approval_setting_id'); ?>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<?php echo render_input('name','subject','','text'); ?>
						<?php
						// Every value here must match a string the code actually passes to
						// get_approve_setting(), otherwise a setting can be saved that is
						// never consulted. Conversely anything the code looks up but that is
						// missing from this list can never be configured, and editing an
						// existing row of that kind would blank its Related field on save.
						//
						// asset_request and order_return were both in the second category:
						// asset_request already had configured rows in the database, and
						// order_return is looked up in four places.
						$related = [
								0 => ['id' => 'pur_request', 'name' => _l('pur_request')],
								1 => ['id' => 'pur_quotation', 'name' => _l('pur_quotation')],
								2 => ['id' => 'pur_order', 'name' => _l('pur_order')],
								3 => ['id' => 'payment_request', 'name' => _l('payment_request')],
								4 => ['id' => 'purchase_invoice', 'name' => _l('pur_invoice')],
								5 => ['id' => 'faf_requests', 'name' => _l('pur_faf_requests')],
								6 => ['id' => 'asset_request', 'name' => _l('asset_request')],
								7 => ['id' => 'order_return', 'name' => _l('order_return')],
								8 => ['id' => 'pur_bid_waiver', 'name' => _l('pur_bid_waiver')],
								9 => ['id' => 'pur_petty_cash', 'name' => _l('pur_petty_cash_advance')],
							]; ?>
						<?php echo render_select('related',$related,array('id','name'),'task_single_related'); ?>

						<?php 
						$CI =& get_instance();
						$CI->load->model('departments_model');
						$departments = $CI->departments_model->get();
						?>
						<div class="form-group select-placeholder">
							<label for="department"><?php echo _l('department'); ?></label>
							<select name="department" id="approval_department" class="selectpicker" data-width="100%" data-live-search="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" onchange="filter_staff_by_department(this.value);">
								<option value=""><?php echo _l('all'); ?></option>
								<?php foreach($departments as $dept){ ?>
								<option value="<?php echo $dept['departmentid']; ?>"><?php echo $dept['name']; ?></option>
								<?php } ?>
							</select>
						</div>

						<div class="checkbox checkbox-primary">
					        <input type="checkbox" id="approval_type" name="approval_type" value="approval_type">
					        <label for="approval_type"><?php echo _l('only_1_person_needs_to_approve_the_transaction'); ?>

					        <a href="#" class="pull-right display-block input_method">&nbsp;<i class="fa fa-question-circle" data-toggle="tooltip" title="" data-original-title="<?php echo _l('approval_type_tooltip'); ?>"></i></a>
					        </label>
					    </div>

					    <hr>
					
					    <h4 >
							<span class=""><?php echo _l('pur_approvers'); ?></span>
						</h4>
				
						<div class="list_approve">
							<div id="item_approve">
                                <div class="col-md-11">
                                <div class="col-md-<?php if($hr_record_status == 1){ echo '4'; }else{ echo '1'; } ?> <?php if($hr_record_status == 0){ echo 'hide'; } ?>">
                                	<div class="select-placeholder form-group">
		                                <label for="approver[0]"><?php echo _l('approver'); ?></label>
		                            <select name="approver[0]" id="approver[0]" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" data-hide-disabled="true" required>
		                                <option value=""></option>
		                                <option value="direct_manager"><?php echo _l('direct_manager'); ?></option>
		                                <option value="head_of_department"><?php echo _l('department_manager'); ?></option>
		                                <option value="staff" <?php if($hr_record_status == 0){ echo 'selected'; } ?> ><?php echo _l('staff'); ?></option>
		                            </select>
		                           </div> 
                          		</div>
                          		<div class="col-md-<?php if($hr_record_status == 1){ echo '4'; }else{ echo '8'; } ?>">
                                	<div class="select-placeholder form-group">
		                                <label for="staff[0]"><?php echo _l('staff'); ?></label>
		                            <select name="staff[0]" id="staff[0]" class="selectpicker" data-width="100%" data-live-search="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" data-hide-disabled="true">
		                                <option value=""></option>
		                                <?php foreach($staffs as $val){
		                                 $selected = '';
		                                  ?>
		                              <option value="<?php echo pur_html_entity_decode($val['staffid']); ?>">
		                                 <?php echo get_staff_full_name($val['staffid']); ?>
		                              </option>
		                              <?php } ?>
		                            </select>
		                           </div> 
                          		</div>
                          		<div class="col-md-3" id="is_staff_0">
                                	<div class="select-placeholder form-group">
		                                <label for="action[0]"><?php echo _l('action'); ?></label>
		                            <select name="action[0]" id="action[0]" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" data-hide-disabled="true">
		                                <option value="approve" selected><?php echo _l('approve'); ?></option>
		                                <option value="sign"><?php echo _l('sign'); ?></option>
		                            </select>
		                           </div> 
                          		</div>
                          		<div class="col-md-3">
                                	<div class="form-group">
		                                <label for="amount[0]"><?php echo _l('amount'); ?></label>
		                                <input type="number" name="amount[0]" id="amount[0]" class="form-control" placeholder="<?php echo _l('amount'); ?>" min="0" step="any">
		                           </div> 
                          		</div>
                          		</div>
                                <div class="col-md-1 bot_div">
                                <span class="pull-bot">
                                    <button name="add" class="btn new_vendor_requests btn-success" data-ticket="true" type="button"><i class="fa fa-plus"></i></button>
                                    </span>
                              </div>
							</div>
						</div>

					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
				<?php echo form_close(); ?>
			</div>
		</div>
	</div>
</div>


<script>
function filter_staff_by_department(department_id){
	$.get(admin_url + 'purchase/get_staff_by_department/' + department_id, function(response){
		var staffs = JSON.parse(response);
		var options = '<option value=""></option>';
		$.each(staffs, function(i, staff){
			var staffid = staff.staffid;
			var name = staff.full_name || (staff.firstname + ' ' + staff.lastname);
			options += '<option value="' + staffid + '">' + name + '</option>';
		});
		$('#approval_setting_modal select[name*="staff"]').each(function(){
			$(this).html(options);
			$(this).selectpicker('refresh');
		});
	});
}
</script>
