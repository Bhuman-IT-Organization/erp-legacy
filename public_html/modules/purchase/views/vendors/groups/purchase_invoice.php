<div class="col-md-12" id="small-table">
	<div class="row">
      <h4 class="no-margin font-bold"><i class="fa fa-clipboard" aria-hidden="true"></i> <?php echo _l('purchase_invoice'); ?></h4>
      <hr />
  	</div> 
    <?php if (has_permission('purchase_invoices', '', 'create') || is_admin()) { ?>
      <a href="<?php echo admin_url('purchase/pur_invoice'); ?>"class="btn btn-info pull-left mright10 display-block">
        <i class="fa fa-plus"></i>&nbsp;<?php echo _l('new_pur_order'); ?>
      </a>
    <?php } ?> 	
    <br><br><br>
        <?php $table_data = array(
                        _l('invoice_code'),
                        _l('invoice_no'),
                        _l('grn'),
                        _l('transaction_id'),
                        _l('vendor'),
                        _l('contract'),
                        _l('pur_order'),
                        _l('pur_request'),
                        _l('invoice_date'),
                        _l('recurring_from'),
                        _l('invoice_amount'),
                        _l('tax_value'),
                        _l('total_included_tax'),
                        _l('approval_status'),
                        _l('payment_request_status'),
                        _l('payment_status'),
                        _l('tag'),
                        );

                    $custom_fields = get_custom_fields('pur_invoice',array('show_on_table'=>1));
                    foreach($custom_fields as $field){
                     array_push($table_data,$field['name']);
                    }
                    
                    render_datatable($table_data,'table_pur_invoices'); ?>

  <?php
  // Expenses bought on credit are vendor bills as well, but they live in
  // tblexpenses rather than tblpur_invoices so they cannot be rows in the
  // datatable above. Listed separately here so the tab shows everything the
  // vendor is owed. Amounts are tax inclusive, matching what was credited to the
  // vendor's payable account.
  $expense_invoices = isset($expense_invoices) ? $expense_invoices : [];

  if (count($expense_invoices) > 0) {
      $CI_exp_inv = & get_instance();
      $CI_exp_inv->load->model('expenses_model');

      $base_currency_exp_inv = get_base_currency_pur();

      $exp_inv_total_net   = 0;
      $exp_inv_total_tax   = 0;
      $exp_inv_total_gross = 0;
      ?>
  <div class="row mtop30">
    <div class="col-md-12">
      <hr />
      <h4 class="no-margin font-bold">
        <i class="fa fa-file-text-o" aria-hidden="true"></i>
        <?php echo _l('purchase_expense_invoices_heading'); ?>
      </h4>
      <p class="text-muted"><?php echo _l('purchase_expense_invoices_note'); ?></p>
      <hr />
    </div>
    <div class="col-md-12">
      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th><?php echo _l('purchase_expense_invoice_number'); ?></th>
              <th><?php echo _l('category'); ?></th>
              <th><?php echo _l('name'); ?></th>
              <th><?php echo _l('date'); ?></th>
              <th class="text-right"><?php echo _l('purchase_expense_amount_excl_vat'); ?></th>
              <th class="text-right"><?php echo _l('tax_value'); ?></th>
              <th class="text-right"><?php echo _l('total_included_tax'); ?></th>
              <th><?php echo _l('payment_status'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($expense_invoices as $exp_inv) {
                $exp_inv_tax   = $CI_exp_inv->expenses_model->expense_tax_total($exp_inv);
                $exp_inv_gross = $CI_exp_inv->expenses_model->expense_total_with_tax($exp_inv);

                $exp_inv_total_net   += (float) $exp_inv['amount'];
                $exp_inv_total_tax   += $exp_inv_tax;
                $exp_inv_total_gross += $exp_inv_gross;

                // Same three states purchase invoices use, so the column reads the
                // same way down the page
                $exp_inv_status = !empty($exp_inv['payment_status']) ? $exp_inv['payment_status'] : 'unpaid';
                $exp_inv_status_classes = [
                    'unpaid'         => 'label-danger',
                    'partially_paid' => 'label-warning',
                    'paid'           => 'label-success',
                ];
                $exp_inv_status_class = isset($exp_inv_status_classes[$exp_inv_status])
                    ? $exp_inv_status_classes[$exp_inv_status]
                    : 'label-default';
                ?>
            <tr>
              <td>
                <a href="<?php echo admin_url('expenses/list_expenses/' . $exp_inv['id']); ?>" target="_blank">
                  <?php echo pur_html_entity_decode($exp_inv['expinv_number']); ?>
                </a>
              </td>
              <td><?php echo pur_html_entity_decode($exp_inv['category_name']); ?></td>
              <td><?php echo pur_html_entity_decode($exp_inv['expense_name']); ?></td>
              <td><?php echo _d($exp_inv['date']); ?></td>
              <td class="text-right"><?php echo app_format_money($exp_inv['amount'], $base_currency_exp_inv->name); ?></td>
              <td class="text-right"><?php echo app_format_money($exp_inv_tax, $base_currency_exp_inv->name); ?></td>
              <td class="text-right bold"><?php echo app_format_money($exp_inv_gross, $base_currency_exp_inv->name); ?></td>
              <td>
                <span class="label <?php echo $exp_inv_status_class; ?>">
                  <?php echo _l($exp_inv_status); ?>
                </span>
              </td>
            </tr>
            <?php } ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" class="text-right bold"><?php echo _l('total'); ?></td>
              <td class="text-right bold"><?php echo app_format_money($exp_inv_total_net, $base_currency_exp_inv->name); ?></td>
              <td class="text-right bold"><?php echo app_format_money($exp_inv_total_tax, $base_currency_exp_inv->name); ?></td>
              <td class="text-right bold"><?php echo app_format_money($exp_inv_total_gross, $base_currency_exp_inv->name); ?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
  <?php } ?>
</div>