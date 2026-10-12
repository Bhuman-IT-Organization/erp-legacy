<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <div class="row">
              <div class="col-md-8">
                <h4 class="no-margin font-bold">
                  <i class="fa fa-plus-circle"></i>
                  <?php echo _l('fe_asset_creation'); ?>
                  <span class="text-muted"><?php echo e($asset_item->asset_uid); ?></span>
                </h4>
              </div>
              <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('fixed_equipment/asset_creation'); ?>" class="btn btn-default btn-sm">
                  <i class="fa fa-arrow-left"></i> <?php echo _l('fe_back'); ?>
                </a>
                <?php if(is_admin() || has_permission('fixed_equipment_assets', '', 'edit')){ ?>
                <a href="<?php echo admin_url('fixed_equipment/asset_creation/'.$asset_item->id); ?>" class="btn btn-info btn-sm">
                  <i class="fa fa-pencil-square-o"></i> <?php echo _l('edit'); ?>
                </a>
                <?php } ?>
              </div>
            </div>

            <hr />

            <div class="row">
              <div class="col-md-6">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                      <td class="bold" width="45%"><?php echo _l('asset_id'); ?></td>
                      <td><?php echo e($asset_item->asset_uid); ?></td>
                    </tr>
                    <tr>
                      <td class="bold"><?php echo _l('fe_code'); ?></td>
                      <td><?php echo e($asset_item->code); ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Company/Plant</td>
                      <td><?php echo e($asset_item->company); ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Department/Location</td>
                      <td><?php echo e($asset_item->department); ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Asset Title</td>
                      <td><?php echo e($asset_item->asset_title); ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Descriptions</td>
                      <td><?php echo e($asset_item->descriptions); ?></td>
                    </tr>
                    <tr class="active">
                      <td class="bold">Asset Class</td>
                      <td>
                        <?php echo !empty($asset_item->asset_class)
                            ? '<strong>' . e($asset_item->asset_class) . '</strong>'
                            : '<span class="text-muted">-</span>'; ?>
                      </td>
                    </tr>
                    <tr class="active">
                      <td class="bold">Capitalization Date</td>
                      <td>
                        <?php echo (!empty($asset_item->capitalization_date) && $asset_item->capitalization_date != '0000-00-00')
                            ? '<strong>' . _d($asset_item->capitalization_date) . '</strong>'
                            : '<span class="text-muted">-</span>'; ?>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="col-md-6">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                      <td class="bold" width="45%">Qty</td>
                      <td><?php echo e($asset_item->qty); ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Cost Centre</td>
                      <td><?php echo e($asset_item->cost_centre); ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Vendor Name</td>
                      <td><?php echo e($asset_item->vendor_name); ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Manufacturer Name</td>
                      <td><?php echo e($asset_item->manufacturer_name); ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Useful Life</td>
                      <td><?php echo $asset_item->useful_life !== null && $asset_item->useful_life !== '' ? e($asset_item->useful_life) . ' yrs' : '-'; ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Reason</td>
                      <td><?php echo e($asset_item->reason); ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Location</td>
                      <td><?php echo e($asset_item->location); ?></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                      <td class="bold" width="22%">Created By</td>
                      <td width="28%"><?php echo e(get_staff_full_name($asset_item->created_by)); ?></td>
                      <td class="bold" width="22%">Created At</td>
                      <td><?php echo $asset_item->created_at ? _dt($asset_item->created_at) : '-'; ?></td>
                    </tr>
                    <?php if(!empty($asset_item->updated_at)){ ?>
                    <tr>
                      <td class="bold">Updated By</td>
                      <td><?php echo e(get_staff_full_name($asset_item->updated_by)); ?></td>
                      <td class="bold">Updated At</td>
                      <td><?php echo _dt($asset_item->updated_at); ?></td>
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
</div>
<?php init_tail(); ?>
</body>
</html>
