<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <div class="row mbot15">
              <div class="col-md-6">
                <h4 class="no-margin font-bold"><?php echo _l('pur_bid_waivers'); ?></h4>
              </div>
              <div class="col-md-6 text-right">
                <?php if (has_permission('purchase_bid_waiver', '', 'create')) { ?>
                <a href="<?php echo admin_url('purchase/bid_waiver'); ?>" class="btn btn-info">
                  <i class="fa fa-plus"></i> <?php echo _l('pur_new_bid_waiver'); ?>
                </a>
                <?php } ?>
              </div>
            </div>

            <hr class="hr-panel-heading" />

            <div class="row mbot15">
              <div class="col-md-3">
                <?php echo render_date_input('from_date', 'from_date'); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_date_input('to_date', 'to_date'); ?>
              </div>
              <div class="col-md-3">
                <?php
                $department_options = [];
                foreach ($departments as $department) {
                    $department_options[] = ['id' => $department['departmentid'], 'name' => $department['name']];
                }
                echo render_select('department[]', $department_options, ['id', 'name'], 'pur_bw_department', '', ['multiple' => true, 'data-actions-box' => 'true']);
                ?>
              </div>
              <div class="col-md-3">
                <?php
                $status_options = [
                    ['id' => 1, 'name' => _l('pur_bw_pending')],
                    ['id' => 2, 'name' => _l('pur_bw_approved')],
                    ['id' => 3, 'name' => _l('pur_bw_rejected')],
                ];
                echo render_select('status[]', $status_options, ['id', 'name'], 'pur_bw_status', '', ['multiple' => true, 'data-actions-box' => 'true']);
                ?>
              </div>
            </div>

            <?php
            $table_data = [
                _l('pur_bw_request_no'),
                _l('pur_bw_department'),
                _l('pur_bw_requestor_name'),
                _l('pur_bw_purchase_type'),
                _l('pur_bw_recommended_vendor'),
                _l('pur_bw_total_amount'),
                _l('pur_bw_waiver_required'),
                _l('pur_bw_created_at'),
                _l('pur_bw_status'),
                _l('options'),
            ];

            echo render_datatable($table_data, 'bid_waivers', [], ['id' => 'table-bid_waivers']);
            ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<?php require 'modules/purchase/assets/js/bid_waiver/manage_js.php'; ?>
</body>
</html>
