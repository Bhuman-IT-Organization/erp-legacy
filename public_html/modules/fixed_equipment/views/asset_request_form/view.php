<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <!-- Status ribbon -->
            <?php if($request->status == 0){ ?>
              <div class="ribbon info"><span>Draft</span></div>
            <?php } elseif($request->status == 5){ ?>
              <div class="ribbon warning"><span>Pending Approval</span></div>
            <?php } elseif($request->status == 2){ ?>
              <div class="ribbon success"><span>Approved</span></div>
            <?php } elseif($request->status == 3){ ?>
              <div class="ribbon danger"><span>Rejected</span></div>
            <?php } ?>

            <h4 class="no-margin font-bold pull-left">
              <i class="fa fa-file-text-o"></i> Asset Code Request: <?php echo $request->request_number; ?>
            </h4>
            <div class="pull-right">
              <div class="btn-group mleft5">
                <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa-regular fa-file-pdf"></i> <span class="caret"></span></a>
                <ul class="dropdown-menu dropdown-menu-right">
                  <li class="hidden-xs"><a href="<?php echo admin_url('fixed_equipment/asset_request_pdf/' . $request->id . '?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a></li>
                  <li class="hidden-xs"><a href="<?php echo admin_url('fixed_equipment/asset_request_pdf/' . $request->id . '?output_type=I'); ?>" target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
                  <li><a href="<?php echo admin_url('fixed_equipment/asset_request_pdf/' . $request->id); ?>"><?php echo _l('download'); ?></a></li>
                  <li><a href="<?php echo admin_url('fixed_equipment/asset_request_pdf/' . $request->id . '?print=true'); ?>" target="_blank"><?php echo _l('print'); ?></a></li>
                </ul>
              </div>
            </div>
            <div class="clearfix"></div>
            <hr />

            <!-- Request Details -->
            <div class="row">
              <div class="col-md-4">
                <p><strong><?php echo _l('department'); ?>:</strong> 
                <?php 
                  $dept_name = '';
                  if($request->department > 0){
                    $d = $this->departments_model->get($request->department);
                    if($d) $dept_name = $d->name;
                  }
                  echo $dept_name;
                ?></p>
              </div>
              <div class="col-md-4">
                <p><strong>Requested By:</strong> <?php echo $request->requested_by > 0 ? get_staff_full_name($request->requested_by) : '-'; ?></p>
              </div>
              <div class="col-md-4">
                <p><strong>Reason:</strong> <?php echo $request->reason; ?></p>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <p><strong>Location:</strong> <?php echo $request->location; ?></p>
              </div>
              <div class="col-md-4">
                <p><strong>Date:</strong> <?php echo _d($request->date_created); ?></p>
              </div>
              <?php if($request->asset_code != ''){ ?>
              <div class="col-md-4">
                <p><strong>Asset Code:</strong> <span class="text-success bold"><?php echo $request->asset_code; ?></span></p>
              </div>
              <?php } ?>
            </div>

            <!-- Items Table -->
            <div class="table-responsive mtop15">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr style="background:#f5f5f5;">
                    <th>Sr.</th>
                    <th>Asset Description</th>
                    <th>Qty</th>
                    <th>UOM</th>
                    <th>Unit Cost</th>
                    <th>Total</th>
                    <th>Vendor</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $sr=1; $grand_total = 0; foreach($request_items as $item){
                    $vendor_name = '';
                    if(isset($item['vendor']) && $item['vendor'] > 0){
                      foreach($vendors as $v){
                        if($v['userid'] == $item['vendor']){ $vendor_name = $v['company']; break; }
                      }
                    }
                    $grand_total += (float)$item['total'];
                  ?>
                  <tr>
                    <td><?php echo $sr++; ?></td>
                    <td><?php echo $item['description']; ?></td>
                    <td><?php echo app_format_number((float)$item['qty']); ?></td>
                    <td><?php echo $item['uom']; ?></td>
                    <td><?php echo app_format_money((float)$item['unit_cost'], ''); ?></td>
                    <td><?php echo app_format_money((float)$item['total'], ''); ?></td>
                    <td><?php echo $vendor_name; ?></td>
                  </tr>
                  <?php } ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="5" class="text-right bold">Grand Total:</td>
                    <td class="bold"><?php echo app_format_money($grand_total, ''); ?></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Finance Use -->
            <?php if($request->asset_code != '' || $request->asset_class != '' || $request->capitalization_date != ''){ ?>
            <div class="well well-sm mtop15">
              <strong>Finance Use:</strong>
              <?php if($request->asset_code != ''){ ?> Asset Code: <strong><?php echo $request->asset_code; ?></strong> | <?php } ?>
              <?php if($request->asset_class != ''){ ?> Asset Class: <strong><?php echo $request->asset_class; ?></strong> | <?php } ?>
              <?php if($request->capitalization_date != '' && $request->capitalization_date != '0000-00-00'){ ?> Capitalization Date: <strong><?php echo _d($request->capitalization_date); ?></strong><?php } ?>
            </div>
            <?php } ?>

            <!-- Attachments -->
            <?php if (isset($attachments) && count($attachments) > 0) { ?>
            <div class="mtop15">
              <p class="bold"><?php echo _l('fe_asset_request_attachments'); ?></p>
              <hr />
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th><?php echo _l('file_name'); ?></th>
                    <th><?php echo _l('size'); ?></th>
                    <th><?php echo _l('uploaded_by'); ?></th>
                    <th><?php echo _l('date'); ?></th>
                    <th><?php echo _l('options'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($attachments as $file) {
                      $display_name = !empty($file['original_name']) ? $file['original_name'] : $file['file_name'];
                      $url = admin_url('fixed_equipment/asset_request_file/' . (int) $file['id']);
                      ?>
                  <tr>
                    <td>
                      <i class="fa fa-paperclip text-muted"></i>
                      <a href="<?php echo $url; ?>" target="_blank"><?php echo e($display_name); ?></a>
                    </td>
                    <td><?php echo !empty($file['filesize']) ? e(round($file['filesize'] / 1024)) . ' KB' : '-'; ?></td>
                    <td>
                      <?php
                      // Named where the staff record still exists, so a deleted uploader
                      // does not leave the cell blank
                      $uploader = !empty($file['staffid']) ? trim((string) get_staff_full_name($file['staffid'])) : '';
                      echo $uploader !== '' ? e($uploader) : '<span class="text-muted">-</span>';
                      ?>
                    </td>
                    <td><?php echo !empty($file['dateadded']) ? _dt($file['dateadded']) : '-'; ?></td>
                    <td>
                      <a href="<?php echo $url; ?>" target="_blank"><?php echo _l('view'); ?></a>
                      <?php if (is_admin() || has_permission('fixed_equipment_assets', '', 'delete')) { ?>
                      |
                      <a href="<?php echo admin_url('fixed_equipment/delete_asset_request_file/' . (int) $file['id']); ?>"
                         class="text-danger _delete"><?php echo _l('delete'); ?></a>
                      <?php } ?>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <?php } ?>

            <!-- Approval Panel -->
            <div class="mtop15">
              <?php if(count($list_approve_status) > 0){ ?>
              <p class="bold"><?php echo _l('pur_approval_infor'); ?></p>
              <hr />
              <div class="row">
                <div class="col-md-12 project-overview-expenses-finance">
                <?php 
                $this->load->model('staff_model');
                foreach($list_approve_status as $value){
                  // Split on the comma and trim, rather than requiring exactly ', '.
                  // The separator is not guaranteed, and a stray space used to produce a
                  // non numeric id, which is what made this panel render blank names.
                  $value['staffid'] = array_filter(
                    array_map('trim', explode(',', (string) ($value['staffid'] ?? ''))),
                    'strlen'
                  );
                ?>
                <div class="col-md-3 text-center">
                  <p class="text-uppercase text-muted no-mtop bold">
                  <?php
                    $staff_names = [];
                    $staff_position = '';
                    foreach($value['staffid'] as $val){
                      // Staff_model::get() only looks up by id when the value is numeric.
                      // Given anything else it returns every staff row instead, and
                      // reading ->full_name off that array yielded an empty name - so a
                      // blank or malformed id silently wiped the approver out.
                      if(!is_numeric($val)){
                        continue;
                      }

                      // get_staff_full_name() rather than Staff_model::get(): the model
                      // also loads permissions on its way out, and that lookup reads an
                      // unprefixed table name, so resolving an approver who was not
                      // already cached failed and the name came back empty. This is the
                      // same helper the Requested By line above uses.
                      $full_name = trim((string) get_staff_full_name($val));

                      if($full_name !== ''){
                        $staff_names[] = e($full_name);
                      }else{
                        // The approver was configured and then deleted from staff. Naming
                        // the id is what lets someone find and fix the flow; a blank cell
                        // just looks like the panel is broken.
                        $staff_names[] = '<span class="text-danger" data-toggle="tooltip" title="'
                          . _l('fe_asset_request_approver_missing_hint') . '">#'
                          . e($val) . ' &mdash; ' . _l('fe_asset_request_approver_missing') . '</span>';
                      }

                      if(function_exists('pur_get_job_position')) $staff_position = pur_get_job_position($val);
                    }

                    echo count($staff_names) > 0
                      ? implode(' or ', $staff_names)
                      : '<span class="text-danger">' . _l('fe_asset_request_approver_none') . '</span>';
                  ?></p>
                  <?php if($staff_position != ''){ ?>
                    <span><?php echo $staff_position; ?></span><br>
                  <?php } ?>
                  <?php if($value['approve'] == 2){ ?>
                    <img src="<?php echo site_url('modules/purchase/uploads/approval/approved.png'); ?>" style="max-width:80px;">
                    <br><br>
                    <p class="bold text-center text-success"><?php echo _dt($value['date']); ?></p>
                  <?php } elseif($value['approve'] == 3){ ?>
                    <img src="<?php echo site_url('modules/purchase/uploads/approval/rejected.png'); ?>" style="max-width:80px;">
                    <br><br>
                    <p class="bold text-center text-danger"><?php echo _dt($value['date']); ?></p>
                  <?php } else { ?>
                    <p class="text-warning bold"><?php echo _l('not_yet_approve') ?: 'Pending'; ?></p>
                  <?php } ?>
                  <?php if(!empty($value['note'])){ ?>
                    <p class="text-muted"><?php echo $value['note']; ?></p>
                  <?php } ?>
                </div>
                <?php } ?>
                </div>
              </div>
              <?php } ?>
            </div>

            <!-- Action Buttons -->
            <div class="text-right mtop15">
              <a href="<?php echo admin_url('fixed_equipment/asset_request_forms'); ?>" class="btn btn-default mright5"><i class="fa fa-arrow-left"></i> Back</a>

              <?php if($request->status != 2){ ?>
                <a href="<?php echo admin_url('fixed_equipment/asset_request_forms/' . $request->id); ?>" class="btn btn-default mright5"><i class="fa fa-pencil"></i> <?php echo _l('edit'); ?></a>
              <?php } ?>

              <?php // Send for Approval button
              if($check_appr && $check_appr != false && in_array($request->status, [0, 3])){ ?>
                <a href="#" class="btn btn-success mright5" onclick="send_for_approval(); return false;"><i class="fa fa-paper-plane"></i> Send for Approval</a>
              <?php } ?>

              <?php // Approve/Deny buttons for the current approver
              if(isset($check_approve_status['staffid']) && is_array($check_approve_status['staffid']) && in_array(get_staff_user_id(), $check_approve_status['staffid']) && in_array($request->status, [5])){ 
                // Check if this is the last pending approver (only 1 pending left = this user)
                $CI_pend =& get_instance();
                $CI_pend->db->where('rel_id', $request->id);
                $CI_pend->db->where('rel_type', 'asset_request');
                $CI_pend->db->group_start();
                $CI_pend->db->where('approve', 0);
                $CI_pend->db->or_where('approve IS NULL', null, false);
                $CI_pend->db->group_end();
                $pending_count = $CI_pend->db->count_all_results(db_prefix().'pur_approval_details');
                $is_last_approver = ($pending_count <= 1);
              ?>
                <div class="btn-group">
                  <a href="#" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo _l('approve'); ?> <span class="caret"></span></a>
                  <ul class="dropdown-menu dropdown-menu-right" style="padding:15px; min-width:350px;">
                    <li>
                      <?php if($is_last_approver){ ?>
                      <div class="form-group">
                        <label>Asset Class <small class="text-muted">(<?php echo _l('optional'); ?>)</small></label>
                        <input type="text" id="approve_asset_class" class="form-control" placeholder="e.g. Furniture, IT Equipment">
                      </div>
                      <div class="form-group">
                        <label>Capitalization Date <small class="text-muted">(<?php echo _l('optional'); ?>)</small></label>
                        <div class="input-group date">
                          <input type="text" id="approve_cap_date" class="form-control datepicker" placeholder="<?php echo _d(date('Y-m-d')); ?>">
                          <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                        </div>
                      </div>
                      <?php } ?>
                      <div class="form-group">
                        <label>Reason/Note</label>
                        <textarea id="approve_reason" class="form-control" rows="2"></textarea>
                      </div>
                    </li>
                    <li>
                      <div class="text-right">
                        <a href="#" class="btn btn-success btn-sm" onclick="approve_request(); return false;"><i class="fa fa-check"></i> <?php echo _l('approve'); ?></a>
                        <a href="#" class="btn btn-warning btn-sm" onclick="deny_request(); return false;"><i class="fa fa-times"></i> Deny</a>
                      </div>
                    </li>
                  </ul>
                </div>
              <?php } ?>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
</body>
</html>
<script>
function send_for_approval(){
  var data = {
    rel_id: <?php echo $request->id; ?>,
    rel_type: 'asset_request',
    addedfrom: <?php echo $request->created_by ?: get_staff_user_id(); ?>
  };
  $('body').append('<div class="dt-loader"></div>');
  $.post(admin_url + 'fixed_equipment/send_asset_request_approve', data, function(response){
    $('body').find('.dt-loader').remove();
    response = JSON.parse(response);
    if(response.success){
      alert_float('success', response.message);
      setTimeout(function(){ window.location.reload(); }, 1000);
    } else {
      alert_float('warning', response.message);
    }
  });
}

function approve_request(){
  // Asset Class and Capitalization Date are optional.
  // Whatever is filled in gets saved; blank values are simply skipped
  // server-side, so approval is never blocked on them.
  var data = {
    rel_id: <?php echo $request->id; ?>,
    reason: $('#approve_reason').val(),
    asset_class: $('#approve_asset_class').length > 0 ? $('#approve_asset_class').val() : '',
    capitalization_date: $('#approve_cap_date').length > 0 ? $('#approve_cap_date').val() : ''
  };
  $.post(admin_url + 'fixed_equipment/approve_asset_request', data, function(response){
    response = JSON.parse(response);
    if(response.success){
      alert_float('success', response.message);
      setTimeout(function(){ window.location.reload(); }, 1000);
    } else {
      alert_float('danger', response.message);
    }
  });
}

function deny_request(){
  var data = {
    rel_id: <?php echo $request->id; ?>,
    reason: $('#approve_reason').val()
  };
  $.post(admin_url + 'fixed_equipment/deny_asset_request', data, function(response){
    response = JSON.parse(response);
    if(response.success){
      alert_float('success', response.message);
      setTimeout(function(){ window.location.reload(); }, 1000);
    } else {
      alert_float('danger', response.message);
    }
  });
}
</script>
