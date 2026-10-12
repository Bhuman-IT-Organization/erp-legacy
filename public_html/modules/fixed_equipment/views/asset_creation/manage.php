<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin font-bold"><i class="fa fa-plus-circle"></i> <?php echo _l('fe_asset_creation'); ?></h4>
            <hr>

            <!-- Form -->
            <?php echo form_open(admin_url('fixed_equipment/asset_creation'), array('id' => 'asset-creation-form')); ?>
            <input type="hidden" name="id" value="<?php echo isset($asset_item) ? $asset_item->id : ''; ?>">

            <?php if(isset($asset_item) && $asset_item->asset_uid != ''){ ?>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label><?php echo _l('asset_id'); ?></label>
                  <input type="text" class="form-control" name="asset_uid" value="<?php echo $asset_item->asset_uid; ?>" readonly>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label><?php echo _l('fe_code'); ?></label>
                  <input type="text" class="form-control" value="<?php echo $asset_item->code; ?>" readonly>
                </div>
              </div>
            </div>
            <?php } ?>

            <div class="row">
              <div class="col-md-6">
                <?php $company = isset($asset_item) ? $asset_item->company : get_option('invoice_company_name');
                if(empty($company)) $company = get_option('companyname');
                echo render_input('company', 'Company/Plant', $company); ?>
              </div>
              <div class="col-md-6">
                <?php $department = isset($asset_item) ? $asset_item->department : ''; ?>
                <div class="form-group">
                  <label>Department/Location</label>
                  <select name="department" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                    <option value=""></option>
                    <?php if(isset($departments)){ foreach($departments as $dept){ ?>
                      <option value="<?php echo $dept['name']; ?>" <?php if($department == $dept['name']) echo 'selected'; ?>><?php echo $dept['name']; ?></option>
                    <?php } } ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <?php $asset_title = isset($asset_item) ? $asset_item->asset_title : '';
                echo render_input('asset_title', 'Asset Title', $asset_title, 'text', ['required' => true]); ?>
              </div>
              <div class="col-md-6">
                <?php $descriptions = isset($asset_item) ? $asset_item->descriptions : '';
                echo render_input('descriptions', 'Descriptions', $descriptions); ?>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <?php $qty = isset($asset_item) ? $asset_item->qty : 1;
                echo render_input('qty', 'Qty', $qty, 'number', ['min' => 1]); ?>
              </div>
              <div class="col-md-3">
                <?php $cost_centre = isset($asset_item) ? $asset_item->cost_centre : ''; ?>
                <div class="form-group">
                  <label>Cost Centre</label>
                  <select name="cost_centre" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                    <option value=""></option>
                    <?php if(isset($cost_centres)){ foreach($cost_centres as $cc){ ?>
                      <option value="<?php echo $cc['name']; ?>" <?php if($cost_centre == $cc['name']) echo 'selected'; ?>><?php echo $cc['name']; ?></option>
                    <?php } } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <?php $vendor_name = isset($asset_item) ? $asset_item->vendor_name : ''; ?>
                <div class="form-group">
                  <label>Vendor Name</label>
                  <select name="vendor_name" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                    <option value=""></option>
                    <?php if(isset($vendors)){ foreach($vendors as $v){ ?>
                      <option value="<?php echo $v['company']; ?>" <?php if($vendor_name == $v['company']) echo 'selected'; ?>><?php echo $v['company']; ?></option>
                    <?php } } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <?php $manufacturer_name = isset($asset_item) ? $asset_item->manufacturer_name : '';
                echo render_input('manufacturer_name', 'Manufacturer Name', $manufacturer_name); ?>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <?php $asset_class = isset($asset_item) ? $asset_item->asset_class : '';
                echo render_input('asset_class', 'Asset Class', $asset_class); ?>
              </div>
              <div class="col-md-6">
                <?php
                $cap_date_val = '';
                if(isset($asset_item) && !empty($asset_item->capitalization_date) && $asset_item->capitalization_date != '0000-00-00'){
                    $cap_date_val = _d($asset_item->capitalization_date);
                }
                echo render_date_input('capitalization_date', 'Capitalization Date', $cap_date_val); ?>
              </div>
            </div>

            <div class="row">
              <div class="col-md-3">
                <?php $useful_life = isset($asset_item) ? $asset_item->useful_life : '';
                echo render_input('useful_life', 'Useful Life (Years)', $useful_life, 'number', ['min' => 0]); ?>
              </div>
              <div class="col-md-4">
                <?php $reason = isset($asset_item) ? $asset_item->reason : '';
                echo render_input('reason', 'Reason', $reason); ?>
              </div>
              <div class="col-md-5">
                <?php $location = isset($asset_item) ? $asset_item->location : ''; ?>
                <div class="form-group">
                  <label>Location</label>
                  <select name="location" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                    <option value=""></option>
                    <?php if(isset($locations) && !empty($locations)){ ?>
                      <optgroup label="<?php echo _l('fe_locations'); ?>">
                      <?php foreach($locations as $loc){ ?>
                        <option value="<?php echo $loc->location_name; ?>" <?php if($location == $loc->location_name) echo 'selected'; ?>><?php echo $loc->location_name; ?></option>
                      <?php } ?>
                      </optgroup>
                    <?php } ?>
                    <?php if(isset($warehouses) && !empty($warehouses)){ ?>
                      <optgroup label="<?php echo _l('warehouse'); ?>">
                      <?php foreach($warehouses as $wh){ ?>
                        <option value="<?php echo $wh['warehouse_name']; ?>" <?php if($location == $wh['warehouse_name']) echo 'selected'; ?>><?php echo $wh['warehouse_name']; ?></option>
                      <?php } ?>
                      </optgroup>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="text-right">
              <?php if(isset($asset_item)){ ?>
                <a href="<?php echo admin_url('fixed_equipment/asset_creation'); ?>" class="btn btn-default mright5"><?php echo _l('cancel'); ?></a>
              <?php } ?>
              <button type="submit" class="btn btn-info"><?php echo isset($asset_item) ? _l('update') : _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>

            <hr>
            <!-- Table -->
            <div class="table-responsive">
              <table class="table table-striped dt-table">
                <thead>
                  <tr>
                    <th><?php echo _l('asset_id'); ?></th>
                    <th><?php echo _l('fe_code'); ?></th>
                    <th>Asset Title</th>
                    <th>Asset Class</th>
                    <th>Capitalization Date</th>
                    <th>Department</th>
                    <th>Manufacturer</th>
                    <th>Qty</th>
                    <th>Useful Life</th>
                    <th>Created By</th>
                    <th>Created At</th>
                    <th><?php echo _l('fe_action'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($asset_creations as $ac){ ?>
                  <tr>
                    <?php
                    // Escaped with e(), the same as the detail view already does. These are
                    // all free text the user types, so echoing them raw let anything in the
                    // asset title, class, department or manufacturer render as HTML.
                    ?>
                    <td><?php echo e($ac['asset_uid']); ?></td>
                    <td><?php echo e($ac['code']); ?></td>
                    <td><?php echo e($ac['asset_title']); ?></td>
                    <td><?php echo !empty($ac['asset_class']) ? e($ac['asset_class']) : '-'; ?></td>
                    <td><?php echo (!empty($ac['capitalization_date']) && $ac['capitalization_date'] != '0000-00-00') ? _d($ac['capitalization_date']) : '-'; ?></td>
                    <td><?php echo e($ac['department']); ?></td>
                    <td><?php echo e($ac['manufacturer_name']); ?></td>
                    <td><?php echo $ac['qty']; ?></td>
                    <td><?php echo $ac['useful_life']; ?> yrs</td>
                    <td><?php echo get_staff_full_name($ac['created_by']); ?></td>
                    <td><?php echo _dt($ac['created_at']); ?></td>
                    <td>
                      <a href="<?php echo admin_url('fixed_equipment/view_asset_creation/'.$ac['id']); ?>" class="btn btn-default btn-icon btn-xs" data-toggle="tooltip" title="<?php echo _l('view'); ?>"><i class="fa fa-eye"></i></a>
                      <?php if(is_admin() || has_permission('fixed_equipment_assets', '', 'edit')){ ?>
                      <a href="<?php echo admin_url('fixed_equipment/asset_creation/'.$ac['id']); ?>" class="btn btn-info btn-icon btn-xs" data-toggle="tooltip" title="<?php echo _l('edit'); ?>"><i class="fa fa-pencil"></i></a>
                      <?php } ?>
                      <a href="<?php echo admin_url('fixed_equipment/delete_asset_creation/'.$ac['id']); ?>" class="btn btn-danger btn-icon btn-xs _delete" data-toggle="tooltip" title="<?php echo _l('delete'); ?>"><i class="fa fa-trash"></i></a>
                    </td>
                  </tr>
                  <?php } ?>
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
