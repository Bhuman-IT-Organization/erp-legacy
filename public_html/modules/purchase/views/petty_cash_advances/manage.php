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
                <h4 class="no-margin font-bold"><?php echo _l('pur_petty_cash_advances'); ?></h4>
              </div>
              <div class="col-md-6 text-right">
                <?php if (has_permission('purchase_petty_cash', '', 'create')) { ?>
                <a href="<?php echo admin_url('purchase/petty_cash_advance'); ?>" class="btn btn-info">
                  <i class="fa fa-plus"></i> <?php echo _l('pur_new_petty_cash_advance'); ?>
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
              <div class="col-md-2">
                <?php
                $department_options = [];
                foreach ($departments as $department) {
                    $department_options[] = ['id' => $department['departmentid'], 'name' => $department['name']];
                }
                echo render_select('department[]', $department_options, ['id', 'name'], 'pur_pca_department', '', ['multiple' => true, 'data-actions-box' => 'true']);
                ?>
              </div>
              <div class="col-md-2">
                <?php
                $priority_options = [];
                foreach (pur_pca_priorities() as $key => $label) {
                    $priority_options[] = ['id' => $key, 'name' => $label];
                }
                echo render_select('priority[]', $priority_options, ['id', 'name'], 'pur_pca_payment_priority', '', ['multiple' => true, 'data-actions-box' => 'true']);
                ?>
              </div>
              <div class="col-md-2">
                <?php
                $status_options = [
                    ['id' => 1, 'name' => _l('pur_pca_pending')],
                    ['id' => 2, 'name' => _l('pur_pca_approved')],
                    ['id' => 3, 'name' => _l('pur_pca_rejected')],
                ];
                echo render_select('status[]', $status_options, ['id', 'name'], 'pur_pca_status', '', ['multiple' => true, 'data-actions-box' => 'true']);
                ?>
              </div>
            </div>

            <?php
            $table_data = [
                _l('pur_pca_request_no'),
                _l('pur_pca_employee_name'),
                _l('pur_pca_department'),
                _l('pur_pca_expense_category'),
                _l('pur_pca_amount_requested'),
                _l('pur_pca_payment_priority'),
                _l('pur_pca_required_date'),
                _l('pur_pca_created_at'),
                _l('pur_pca_status'),
                _l('options'),
            ];

            echo render_datatable($table_data, 'petty_cash_advances', [], ['id' => 'table-petty_cash_advances']);
            ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<?php require 'modules/purchase/assets/js/petty_cash_advance/manage_js.php'; ?>
</body>
</html>
