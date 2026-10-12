<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
  // Get current ledger mapping options
  $mappings = [
    'purchase_payment' => [
      'label' => 'Purchase Payment',
      'description' => 'When vendor invoice is paid',
      'debit_option' => 'acc_ledger_purchase_payment_debit',
      'credit_cash_option' => 'acc_ledger_purchase_payment_credit_cash',
      'credit_bank_option' => 'acc_ledger_purchase_payment_credit_bank',
      'credit_cheque_option' => 'acc_ledger_purchase_payment_credit_cheque',
    ],
    'purchase_order' => [
      'label' => 'Purchase Order',
      'description' => 'When purchase order is approved',
      'debit_option' => 'acc_ledger_purchase_order_debit',
      'credit_option' => 'acc_ledger_purchase_order_credit',
    ],
    'purchase_invoice' => [
      'label' => 'Purchase Invoice',
      'description' => 'When purchase invoice is created',
      'debit_option' => 'acc_ledger_purchase_invoice_debit',
      'credit_option' => 'acc_ledger_purchase_invoice_credit',
    ],
    'stock_import' => [
      'label' => 'Inventory Receiving Voucher (GRN)',
      'description' => 'When goods are received into warehouse',
      'debit_option' => 'acc_ledger_stock_import_debit',
      'credit_option' => 'acc_ledger_stock_import_credit',
    ],
    'stock_export' => [
      'label' => 'Goods Delivery Note',
      'description' => 'When goods are delivered/issued from warehouse',
      'debit_option' => 'acc_ledger_stock_export_debit',
      'credit_option' => 'acc_ledger_stock_export_credit',
    ],
    'invoice' => [
      'label' => 'Sales Invoice',
      'description' => 'When sales invoice is created',
      'debit_option' => 'acc_ledger_invoice_debit',
      'credit_option' => 'acc_ledger_invoice_credit',
    ],
    'payment' => [
      'label' => 'Sales Payment Receipt',
      'description' => 'When customer payment is received',
      'debit_cash_option' => 'acc_ledger_payment_debit_cash',
      'debit_bank_option' => 'acc_ledger_payment_debit_bank',
      'debit_cheque_option' => 'acc_ledger_payment_debit_cheque',
      'credit_option' => 'acc_ledger_payment_credit',
    ],
    'expense' => [
      'label' => 'Expense',
      'description' => 'When expense is recorded',
      'debit_option' => 'acc_ledger_expense_debit',
      'credit_option' => 'acc_ledger_expense_credit',
    ],
  ];
?>

<div class="row mtop15">
  <div class="col-md-12">
    <h4 class="font-bold"><i class="fa fa-exchange"></i> <?php echo _l('ledger_account_mapping'); ?></h4>
    <p class="text-muted"><?php echo _l('ledger_account_mapping_description'); ?></p>
    <hr>
  </div>
</div>

<?php echo form_open(admin_url('accounting/update_ledger_mapping'), array('id' => 'ledger-mapping-form')); ?>

<!-- Payment Mode → Ledger Mapping Table -->
<div class="row">
  <div class="col-md-12">
    <h5 class="font-bold text-uppercase text-primary"><i class="fa fa-credit-card"></i> Payment Mode Mapping - Purchase Payments</h5>
    <p class="text-muted">When vendor invoice/PO is paid: Dr Accounts Payable, Cr Bank/Cash</p>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr class="bg-primary text-white">
            <th width="20%">Payment Mode</th>
            <th width="40%">Debit Account (AP - reduces payable)</th>
            <th width="40%">Credit Account (Bank/Cash - money goes out)</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $CI =& get_instance();
          $CI->load->model('payment_modes_model');
          $payment_modes = $CI->payment_modes_model->get();
          foreach($payment_modes as $mode) {
            $mapping = $this->accounting_model->get_payment_mode_mapping($mode['id']);
            $exp_payment_acct = $mapping ? $mapping->expense_payment_account : '';
            $exp_deposit_to = $mapping ? $mapping->expense_deposit_to : '';
          ?>
          <tr>
            <td><strong><?php echo $mode['name']; ?></strong>
              <input type="hidden" name="pm_mode_id[]" value="<?php echo $mode['id']; ?>">
            </td>
            <td>
              <?php echo render_select('pm_debit_account_'.$mode['id'], $accounts, array('id','name','account_type_name'), '', $exp_payment_acct, array(), array(), '', '', false); ?>
            </td>
            <td>
              <?php echo render_select('pm_credit_account_'.$mode['id'], $accounts, array('id','name','account_type_name'), '', $exp_deposit_to, array(), array(), '', '', false); ?>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<hr>

<!-- Sales Payment Mode Mapping -->
<div class="row">
  <div class="col-md-12">
    <h5 class="font-bold text-uppercase text-info"><i class="fa fa-money"></i> Payment Mode Mapping - Sales Receipts</h5>
    <p class="text-muted">When customer payment is received: Dr Bank/Cash, Cr Accounts Receivable</p>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr class="bg-info text-white">
            <th width="20%">Payment Mode</th>
            <th width="40%">Debit Account (Bank/Cash - money comes in)</th>
            <th width="40%">Credit Account (AR - reduces receivable)</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          foreach($payment_modes as $mode) {
            $mapping = $this->accounting_model->get_payment_mode_mapping($mode['id']);
            $sales_debit = $mapping ? $mapping->deposit_to : '';
            $sales_credit = $mapping ? $mapping->payment_account : '';
          ?>
          <tr>
            <td><strong><?php echo $mode['name']; ?></strong></td>
            <td>
              <?php echo render_select('pm_sales_debit_'.$mode['id'], $accounts, array('id','name','account_type_name'), '', $sales_debit, array(), array(), '', '', false); ?>
            </td>
            <td>
              <?php echo render_select('pm_sales_credit_'.$mode['id'], $accounts, array('id','name','account_type_name'), '', $sales_credit, array(), array(), '', '', false); ?>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<hr>

<!-- Transaction Type → Ledger Mapping -->
<div class="row">
  <div class="col-md-12">
    <h5 class="font-bold text-uppercase text-success"><i class="fa fa-book"></i> Transaction Type Default Accounts</h5>
    <p class="text-muted">Default debit and credit accounts for each transaction type. Payment-based transactions use payment mode mapping above.</p>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr class="bg-success text-white">
            <th width="25%">Transaction Type</th>
            <th width="35%">Debit Account</th>
            <th width="35%">Credit Account</th>
            <th width="5%"><i class="fa fa-info-circle"></i></th>
          </tr>
        </thead>
        <tbody>
          <!-- Purchase Invoice -->
          <tr>
            <td><strong>Purchase Invoice</strong><br><small class="text-muted">When invoice is received from vendor</small></td>
            <td><?php echo render_select('ledger_purchase_invoice_debit', $accounts, array('id','name','account_type_name'), '', get_option('acc_pur_invoice_payment_account'), array(), array(), '', '', false); ?></td>
            <td><?php echo render_select('ledger_purchase_invoice_credit', $accounts, array('id','name','account_type_name'), '', get_option('acc_pur_invoice_deposit_to'), array(), array(), '', '', false); ?></td>
            <td><i class="fa fa-question-circle" data-toggle="tooltip" title="Debit: GR/IR Clearing | Credit: Accounts Payable"></i></td>
          </tr>
          <!-- GRN / Stock Import -->
          <tr>
            <td><strong>Goods Receipt (GRN)</strong><br><small class="text-muted">When goods received into warehouse</small></td>
            <td><?php echo render_select('ledger_stock_import_debit', $accounts, array('id','name','account_type_name'), '', get_option('acc_wh_import_payment_account'), array(), array(), '', '', false); ?></td>
            <td><?php echo render_select('ledger_stock_import_credit', $accounts, array('id','name','account_type_name'), '', get_option('acc_wh_import_deposit_to'), array(), array(), '', '', false); ?></td>
            <td><i class="fa fa-question-circle" data-toggle="tooltip" title="Debit: Inventory | Credit: GR/IR Clearing"></i></td>
          </tr>
          <!-- Stock Export / Delivery -->
          <tr>
            <td><strong>Goods Delivery</strong><br><small class="text-muted">When goods issued from warehouse</small></td>
            <td><?php echo render_select('ledger_stock_export_debit', $accounts, array('id','name','account_type_name'), '', get_option('acc_wh_stock_export_deposit_to'), array(), array(), '', '', false); ?></td>
            <td><?php echo render_select('ledger_stock_export_credit', $accounts, array('id','name','account_type_name'), '', get_option('acc_wh_stock_export_payment_account'), array(), array(), '', '', false); ?></td>
            <td><i class="fa fa-question-circle" data-toggle="tooltip" title="Debit: COGS | Credit: Inventory"></i></td>
          </tr>
          <!-- Sales Invoice -->
          <tr>
            <td><strong>Sales Invoice</strong><br><small class="text-muted">When invoice is raised to customer</small></td>
            <td><?php echo render_select('ledger_invoice_debit', $accounts, array('id','name','account_type_name'), '', get_option('acc_invoice_deposit_to'), array(), array(), '', '', false); ?></td>
            <td><?php echo render_select('ledger_invoice_credit', $accounts, array('id','name','account_type_name'), '', get_option('acc_invoice_payment_account'), array(), array(), '', '', false); ?></td>
            <td><i class="fa fa-question-circle" data-toggle="tooltip" title="Debit: Accounts Receivable | Credit: Revenue"></i></td>
          </tr>
          <!-- Expense -->
          <tr>
            <td><strong>Expense</strong><br><small class="text-muted">When expense is recorded</small></td>
            <td><?php echo render_select('ledger_expense_debit', $accounts, array('id','name','account_type_name'), '', get_option('acc_expense_payment_account'), array(), array(), '', '', false); ?></td>
            <td><?php echo render_select('ledger_expense_credit', $accounts, array('id','name','account_type_name'), '', get_option('acc_expense_deposit_to'), array(), array(), '', '', false); ?></td>
            <td><i class="fa fa-question-circle" data-toggle="tooltip" title="Debit: Expense Category | Credit: Cash/Bank"></i></td>
          </tr>
          <!-- Shipping -->
          <tr>
            <td><strong>Purchase Shipping</strong><br><small class="text-muted">Shipping/Freight charges on purchase</small></td>
            <td><?php echo render_select('ledger_shipping_debit', $accounts, array('id','name','account_type_name'), '', get_option('acc_pur_invoice_shipping_deposit_to'), array(), array(), '', '', false); ?></td>
            <td><?php echo render_select('ledger_shipping_credit', $accounts, array('id','name','account_type_name'), '', get_option('acc_pur_invoice_shipping_payment_account'), array(), array(), '', '', false); ?></td>
            <td><i class="fa fa-question-circle" data-toggle="tooltip" title="Debit: Freight Expense | Credit: AP"></i></td>
          </tr>
          <!-- Discount -->
          <tr>
            <td><strong>Purchase Discount</strong><br><small class="text-muted">Discount received on purchase</small></td>
            <td><?php echo render_select('ledger_discount_debit', $accounts, array('id','name','account_type_name'), '', get_option('acc_pur_invoice_discount_deposit_to'), array(), array(), '', '', false); ?></td>
            <td><?php echo render_select('ledger_discount_credit', $accounts, array('id','name','account_type_name'), '', get_option('acc_pur_invoice_discount_payment_account'), array(), array(), '', '', false); ?></td>
            <td><i class="fa fa-question-circle" data-toggle="tooltip" title="Debit: AP (reduces payable) | Credit: Discount Income"></i></td>
          </tr>
          <!-- Sales Discount -->
          <tr>
            <td><strong>Sales Discount</strong><br><small class="text-muted">Discount given to customer on sales</small></td>
            <td><?php echo render_select('ledger_sales_discount_debit', $accounts, array('id','name','account_type_name'), '', get_option('acc_invoice_discount_payment_account'), array(), array(), '', '', false); ?></td>
            <td><?php echo render_select('ledger_sales_discount_credit', $accounts, array('id','name','account_type_name'), '', get_option('acc_invoice_discount_deposit_to'), array(), array(), '', '', false); ?></td>
            <td><i class="fa fa-question-circle" data-toggle="tooltip" title="Debit: Discount Expense | Credit: AR (reduces receivable)"></i></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<hr>

<!-- VAT/Tax Account Mapping -->
<div class="row">
  <div class="col-md-12">
    <h5 class="font-bold text-uppercase text-warning"><i class="fa fa-percent"></i> VAT / Tax Account Mapping</h5>
    <p class="text-muted">Map VAT accounts for automatic tax posting in double-entry system.</p>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr class="bg-warning">
            <th width="20%">Tax</th>
            <th width="25%">Sales VAT (Credit)</th>
            <th width="25%">Purchase VAT (Debit)</th>
            <th width="25%">Purchase VAT Credit To</th>
            <th width="5%"><i class="fa fa-info-circle"></i></th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $CI_tax =& get_instance();
          $taxes = $CI_tax->db->get(db_prefix().'taxes')->result_array();
          foreach($taxes as $tax){
            $tax_map = $this->accounting_model->get_tax_mapping($tax['id']);
            $sales_vat_cr = $tax_map ? $tax_map->payment_account : '';
            $purchase_vat_dr = $tax_map ? $tax_map->purchase_payment_account : '';
            $purchase_vat_cr = $tax_map ? $tax_map->purchase_deposit_to : '';
          ?>
          <tr>
            <td><strong><?php echo $tax['name']; ?> (<?php echo $tax['taxrate']; ?>%)</strong>
              <input type="hidden" name="tax_map_id[]" value="<?php echo $tax['id']; ?>">
            </td>
            <td>
              <?php echo render_select('tax_sales_credit_'.$tax['id'], $accounts, array('id','name','account_type_name'), '', $sales_vat_cr, array(), array(), '', '', false); ?>
              <small class="text-muted">Cr: VAT Received (liability)</small>
            </td>
            <td>
              <?php echo render_select('tax_purchase_debit_'.$tax['id'], $accounts, array('id','name','account_type_name'), '', $purchase_vat_dr, array(), array(), '', '', false); ?>
              <small class="text-muted">Dr: VAT Paid (asset)</small>
            </td>
            <td>
              <?php echo render_select('tax_purchase_credit_'.$tax['id'], $accounts, array('id','name','account_type_name'), '', $purchase_vat_cr, array(), array(), '', '', false); ?>
              <small class="text-muted">Cr: Accounts Payable</small>
            </td>
            <td><i class="fa fa-question-circle" data-toggle="tooltip" title="Sales: Dr AR, Cr VAT Received | Purchase: Dr VAT Paid, Cr AP"></i></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="row mtop15">
  <div class="col-md-12 text-right">
    <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> <?php echo _l('save'); ?></button>
  </div>
</div>
<?php echo form_close(); ?>
