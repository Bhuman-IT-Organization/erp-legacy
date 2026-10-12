<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">

        <!-- Form -->
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin font-bold">
              <i class="fa fa-file-text-o"></i> <?php echo isset($request) ? 'Edit' : 'New'; ?> Asset Code Request Form
            </h4>
            <hr />

            <?php // multipart, otherwise the browser posts only the file names and the
                  // upload never arrives
                  echo form_open_multipart(admin_url('fixed_equipment/asset_request_forms'), ['id' => 'asset-request-form']); ?>
              <?php echo form_hidden('id', isset($request) ? $request->id : ''); ?>

              <!-- Section 1: Request Details -->
              <div class="well well-sm"><strong>1. Request Details</strong></div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('department'); ?> <span class="text-danger">*</span></label>
                    <select name="department" class="selectpicker" data-live-search="true" data-width="100%" required>
                      <option value=""></option>
                      <?php foreach($departments as $d){ ?>
                        <option value="<?php echo $d['departmentid']; ?>" <?php if(isset($request) && $request->department == $d['departmentid']) echo 'selected'; ?>><?php echo $d['name']; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Asset Requested By <span class="text-danger">*</span></label>
                    <select name="requested_by" class="selectpicker" data-live-search="true" data-width="100%" required>
                      <option value=""></option>
                      <?php foreach($staff as $s){ ?>
                        <option value="<?php echo $s['staffid']; ?>" <?php if(isset($request) && $request->requested_by == $s['staffid']){ echo 'selected'; } elseif(!isset($request) && $s['staffid'] == get_staff_user_id()){ echo 'selected'; } ?>><?php echo $s['firstname'] . ' ' . $s['lastname']; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Reason <span class="text-danger">*</span></label>
                    <select name="reason" class="form-control" required>
                      <option value="">-- Select --</option>
                      <?php $reasons = ['New purchase','Transfer','Capitalization','Project close-out','Other'];
                        foreach($reasons as $r){ ?>
                        <option value="<?php echo $r; ?>" <?php if(isset($request) && $request->reason == $r) echo 'selected'; ?>><?php echo $r; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Location</label>
                    <select name="location" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
                      <option value=""></option>
                      <?php if(isset($locations) && !empty($locations)){
                        foreach($locations as $loc){ 
                          $loc_name = is_object($loc) ? $loc->location_name : (isset($loc['location_name']) ? $loc['location_name'] : '');
                        ?>
                          <option value="<?php echo $loc_name; ?>" <?php if(isset($request) && $request->location == $loc_name) echo 'selected'; ?>><?php echo $loc_name; ?></option>
                        <?php }
                      } ?>
                      } ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Section 2: Asset Register Details (items table) -->
              <div class="well well-sm"><strong>2. Asset Register Details</strong></div>
              <div class="table-responsive">
                <table class="table table-bordered" id="items-table">
                  <thead>
                    <tr style="background:#f5f5f5;">
                      <th>Sr.</th>
                      <th>Asset Description <span class="text-danger">*</span></th>
                      <th width="8%">Qty</th>
                    <th width="8%">UOM</th>
                      <th width="12%">Unit Cost</th>
                      <th width="12%">Total</th>
                      <th>Vendor</th>
                      <th width="5%"><button type="button" class="btn btn-success btn-xs" onclick="add_item_row();"><i class="fa fa-plus"></i></button></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if(isset($request_items) && count($request_items) > 0){
                      $sr = 1;
                      foreach($request_items as $item){ ?>
                        <tr>
                          <td class="sr-no"><?php echo $sr++; ?></td>
                          <td><input type="text" name="items[<?php echo $sr; ?>][description]" class="form-control" value="<?php echo $item['description']; ?>" required></td>
                          <td><input type="number" step="0.01" name="items[<?php echo $sr; ?>][qty]" class="form-control item-qty" value="<?php echo $item['qty']; ?>" onchange="calc_row(this);" onkeyup="calc_row(this);"></td>
                          <td><select name="items[<?php echo $sr; ?>][uom]" class="form-control"><?php foreach($units as $u){ ?><option value="<?php echo $u['unit_name']; ?>" <?php if($item['uom'] == $u['unit_name']) echo 'selected'; ?>><?php echo $u['unit_name']; ?></option><?php } ?></select></td>
                          <td><input type="number" step="0.01" name="items[<?php echo $sr; ?>][unit_cost]" class="form-control item-cost" value="<?php echo $item['unit_cost']; ?>" onchange="calc_row(this);" onkeyup="calc_row(this);"></td>
                          <td><input type="text" name="items[<?php echo $sr; ?>][total]" class="form-control item-total" value="<?php echo $item['total']; ?>" readonly></td>
                          <td>
                            <select name="items[<?php echo $sr; ?>][vendor]" class="form-control">
                              <option value=""></option>
                              <?php foreach($vendors as $v){ ?>
                                <option value="<?php echo $v['userid']; ?>" <?php if($item['vendor'] == $v['userid']) echo 'selected'; ?>><?php echo $v['company']; ?></option>
                              <?php } ?>
                            </select>
                          </td>
                          <td><button type="button" class="btn btn-danger btn-xs" onclick="$(this).closest('tr').remove(); reindex_rows();"><i class="fa fa-trash"></i></button></td>
                        </tr>
                      <?php }
                    } else { ?>
                      <tr>
                        <td class="sr-no">1</td>
                        <td><input type="text" name="items[1][description]" class="form-control" required></td>
                        <td><input type="number" step="0.01" name="items[1][qty]" class="form-control item-qty" value="1" onchange="calc_row(this);" onkeyup="calc_row(this);"></td>
                        <td><select name="items[1][uom]" class="form-control"><?php foreach($units as $u){ ?><option value="<?php echo $u['unit_name']; ?>"><?php echo $u['unit_name']; ?></option><?php } ?></select></td>
                        <td><input type="number" step="0.01" name="items[1][unit_cost]" class="form-control item-cost" value="0" onchange="calc_row(this);" onkeyup="calc_row(this);"></td>
                        <td><input type="text" name="items[1][total]" class="form-control item-total" value="0.00" readonly></td>
                        <td>
                          <select name="items[1][vendor]" class="form-control">
                            <option value=""></option>
                            <?php foreach($vendors as $v){ ?>
                              <option value="<?php echo $v['userid']; ?>"><?php echo $v['company']; ?></option>
                            <?php } ?>
                          </select>
                        </td>
                        <td><button type="button" class="btn btn-danger btn-xs" onclick="$(this).closest('tr').remove(); reindex_rows();"><i class="fa fa-trash"></i></button></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>

              <!-- Section 3: Finance Use (only visible after approval) -->
              <?php if(isset($request) && $request->status == 2 && !empty($request->asset_code)){ ?>
              <div class="well well-sm"><strong>Finance Use</strong></div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Asset Code</label>
                    <input type="text" class="form-control" value="<?php echo $request->asset_code; ?>" readonly style="background:#e8f5e9; font-weight:bold;">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Asset Class</label>
                    <input type="text" name="asset_class" class="form-control" value="<?php echo $request->asset_class; ?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Capitalization Date</label>
                    <?php $cap_date = $request->capitalization_date ? _d($request->capitalization_date) : ''; ?>
                    <input type="text" class="form-control" value="<?php echo $cap_date; ?>" readonly>
                  </div>
                </div>
              </div>
              <?php } ?>

              <hr />
              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="attachments"><?php echo _l('fe_asset_request_attachments'); ?></label>
                    <input type="file" name="attachments[]" id="attachments" class="form-control" multiple>
                    <span class="help-block"><?php echo _l('fe_asset_request_attachments_help'); ?></span>
                  </div>

                  <?php
                  // Already attached files, listed so an edit shows what is there rather
                  // than looking as though nothing was ever uploaded
                  if (isset($attachments) && count($attachments) > 0) { ?>
                  <p class="bold"><?php echo _l('fe_asset_request_attached_files'); ?></p>
                  <ul class="list-unstyled">
                    <?php foreach ($attachments as $file) { ?>
                    <li class="mbot5">
                      <i class="fa fa-paperclip text-muted"></i>
                      <a href="<?php echo admin_url('fixed_equipment/asset_request_file/' . (int) $file['id']); ?>"
                         target="_blank">
                        <?php echo e(!empty($file['original_name']) ? $file['original_name'] : $file['file_name']); ?>
                      </a>
                      <?php if (!empty($file['filesize'])) { ?>
                      <span class="text-muted">(<?php echo e(round($file['filesize'] / 1024)); ?> KB)</span>
                      <?php } ?>
                    </li>
                    <?php } ?>
                  </ul>
                  <?php } ?>
                </div>
              </div>

              <div class="text-right mtop15">
                <a href="<?php echo admin_url('fixed_equipment/asset_request_forms'); ?>" class="btn btn-default mright5"><?php echo _l('cancel'); ?></a>
                <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> <?php echo _l('submit'); ?></button>
              </div>
            <?php echo form_close(); ?>

          </div>
        </div>

        <!-- Grid -->
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin font-bold"><i class="fa fa-list"></i> Asset Code Requests</h4>
            <hr />
            <div class="table-responsive">
              <table class="table table-striped table-bordered dt-table">
                <thead>
                  <tr>
                    <th>Request #</th>
                    <th><?php echo _l('department'); ?></th>
                    <th>Requested By</th>
                    <th>Reason</th>
                    <th>Location</th>
                    <th>Asset Code</th>
                    <th>Date</th>
                    <th><?php echo _l('options'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if(isset($requests)){
                    foreach($requests as $r){
                      $dept_name = '';
                      if($r['department'] > 0){
                        $CI =& get_instance();
                        $d = $CI->departments_model->get($r['department']);
                        if($d) $dept_name = $d->name;
                      }
                      $staff_name = $r['requested_by'] > 0 ? get_staff_full_name($r['requested_by']) : '';
                  ?>
                  <tr>
                    <td><?php echo $r['request_number']; ?></td>
                    <td><?php echo $dept_name; ?></td>
                    <td><?php echo $staff_name; ?></td>
                    <td><?php echo $r['reason']; ?></td>
                    <td><?php echo $r['location']; ?></td>
                    <td><?php echo $r['asset_code']; ?></td>
                    <td><?php echo _d($r['date_created']); ?></td>
                    <td>
                      <a href="<?php echo admin_url('fixed_equipment/view_asset_request/' . $r['id']); ?>" class="btn btn-default btn-icon" data-toggle="tooltip" title="<?php echo _l('view'); ?>"><i class="fa fa-eye"></i></a>
                      <?php if($r['status'] != 2){ ?>
                      <a href="<?php echo admin_url('fixed_equipment/asset_request_forms/' . $r['id']); ?>" class="btn btn-default btn-icon" data-toggle="tooltip" title="<?php echo _l('edit'); ?>"><i class="fa fa-pencil-square-o"></i></a>
                      <?php } ?>
                      <a href="<?php echo admin_url('fixed_equipment/delete_asset_request_form/' . $r['id']); ?>" class="btn btn-danger btn-icon _delete" data-toggle="tooltip" title="<?php echo _l('delete'); ?>"><i class="fa fa-remove"></i></a>
                    </td>
                  </tr>
                  <?php } } ?>
                </tbody>
              </table>
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
$(function(){
  $('.dt-table').DataTable({order: [[0, 'desc']]});
});

var item_row_index = <?php echo isset($request_items) ? count($request_items) + 1 : 2; ?>;

function add_item_row(){
  var vendor_options = '<option value=""></option>';
  <?php if(isset($vendors)){
    foreach($vendors as $v){ ?>
      vendor_options += '<option value="<?php echo $v['userid']; ?>"><?php echo addslashes($v['company']); ?></option>';
    <?php }
  } ?>

  var uom_options = '';
  <?php if(isset($units)){
    foreach($units as $u){ ?>
      uom_options += '<option value="<?php echo addslashes($u['unit_name']); ?>"><?php echo addslashes($u['unit_name']); ?></option>';
    <?php }
  } ?>

  var html = '<tr>';
  html += '<td class="sr-no">' + item_row_index + '</td>';
  html += '<td><input type="text" name="items['+item_row_index+'][description]" class="form-control" required></td>';
  html += '<td><input type="number" step="0.01" name="items['+item_row_index+'][qty]" class="form-control item-qty" value="1" onchange="calc_row(this);" onkeyup="calc_row(this);"></td>';
  html += '<td><select name="items['+item_row_index+'][uom]" class="form-control">' + uom_options + '</select></td>';
  html += '<td><input type="number" step="0.01" name="items['+item_row_index+'][unit_cost]" class="form-control item-cost" value="0" onchange="calc_row(this);" onkeyup="calc_row(this);"></td>';
  html += '<td><input type="text" name="items['+item_row_index+'][total]" class="form-control item-total" value="0.00" readonly></td>';
  html += '<td><select name="items['+item_row_index+'][vendor]" class="form-control">' + vendor_options + '</select></td>';
  html += '<td><button type="button" class="btn btn-danger btn-xs" onclick="$(this).closest(\'tr\').remove(); reindex_rows();"><i class="fa fa-trash"></i></button></td>';
  html += '</tr>';
  $('#items-table tbody').append(html);
  item_row_index++;
  reindex_rows();
}

function calc_row(el){
  var $row = $(el).closest('tr');
  var qty = parseFloat($row.find('.item-qty').val()) || 0;
  var cost = parseFloat($row.find('.item-cost').val()) || 0;
  $row.find('.item-total').val((qty * cost).toFixed(2));
}

function reindex_rows(){
  $('#items-table tbody tr').each(function(i){
    $(this).find('.sr-no').text(i + 1);
  });
}
</script>
