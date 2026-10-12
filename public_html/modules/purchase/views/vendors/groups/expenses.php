<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12" id="small-table">
  <div class="row">
      <div class="col-md-4"><h4 class="no-margin font-bold"><i class="fa fa-file-text-o" aria-hidden="true"></i> <?php echo _l('expenses'); ?></h4></div>
       <?php if(is_admin() || has_permission('expenses', '', 'create')){ ?>
      <div class="col-md-8"><a href="<?php echo admin_url('expenses/expense'); ?>" class="btn btn-info pull-right"><?php echo _l('new_expense'); ?></a></div>
    <?php } 
    $base_currency = get_base_currency_pur();

    // Tax and totals come from Expenses_model so this list, the ledger entry and the
    // vendor statement all agree. The model is already loaded by the controller for
    // this tab.
    $CI_exp_tab = & get_instance();
    $CI_exp_tab->load->model('expenses_model');

    $exp_tab_total_amount = 0;
    $exp_tab_total_tax    = 0;
    $exp_tab_total_gross  = 0;
    ?>
    <div class="col-md-12">
       <hr />
    </div>
     
    </div>    

    <table class="table dt-table">
       <thead>
        <th><?php echo _l('category'); ?></th>
         <th><?php echo _l('purchase_expense_invoice_number'); ?></th>
          <th><?php echo _l('name'); ?></th>
          <th><?php echo _l('date'); ?></th>
          <th><?php echo _l('payment_mode'); ?></th>
          <th class="text-right"><?php echo _l('purchase_expense_amount_excl_vat'); ?></th>
          <th class="text-right"><?php echo _l('tax_value'); ?></th>
          <th class="text-right"><?php echo _l('total_included_tax'); ?></th>
       </thead>
      <tbody>
         <?php foreach($expenses as $p) {
            // Expenses_model::get() already joins tblpayment_modes and aliases the
            // name as payment_mode_name. Online gateways have no row in
            // tblpayment_modes, hence the helper fallback, then the raw id as a
            // last resort so the column is never silently empty.
            $expense_payment_mode = '';
            if (!empty($p['payment_mode_name'])) {
                $expense_payment_mode = $p['payment_mode_name'];
            } elseif (!empty($p['paymentmode'])) {
                $expense_payment_mode = get_payment_mode_by_id($p['paymentmode']);
                if ($expense_payment_mode == '') {
                    $expense_payment_mode = $p['paymentmode'];
                }
            }

            // tblexpenses.amount is net of VAT. What the vendor is owed - and what
            // was credited to their payable account - is the gross figure, so both
            // are shown rather than just the net.
            $exp_tab_tax   = $CI_exp_tab->expenses_model->expense_tax_total($p);
            $exp_tab_gross = $CI_exp_tab->expenses_model->expense_total_with_tax($p);

            $exp_tab_total_amount += (float) $p['amount'];
            $exp_tab_total_tax    += $exp_tab_tax;
            $exp_tab_total_gross  += $exp_tab_gross;

            // Rates are worth showing - a 5% line and a zero rated line look
            // identical otherwise
            $exp_tab_rates = [];
            if (!empty($p['taxrate'])) {
                $exp_tab_rates[] = rtrim(rtrim(number_format((float) $p['taxrate'], 2, '.', ''), '0'), '.') . '%';
            }
            if (!empty($p['taxrate2'])) {
                $exp_tab_rates[] = rtrim(rtrim(number_format((float) $p['taxrate2'], 2, '.', ''), '0'), '.') . '%';
            }
            ?>
          <tr>
          <td><a href="<?php echo admin_url('expenses/list_expenses/' . $p['id']); ?>" ><?php echo pur_html_entity_decode($p['category_name']); ?></a></td>
          <td><?php if(!empty($p['expinv_number'])){ ?><a href="<?php echo admin_url('expenses/list_expenses/' . $p['id']); ?>"><?php echo pur_html_entity_decode($p['expinv_number']); ?></a><?php } else { echo '-'; } ?></td>
          <td><?php echo pur_html_entity_decode($p['expense_name']); ?></td>
          <td><?php echo _d($p['date']); ?></td>
          <td><?php echo pur_html_entity_decode($expense_payment_mode); ?></td>
          <td class="text-right"><?php echo app_format_money($p['amount'],$base_currency->name); ?></td>
          <td class="text-right">
            <?php echo app_format_money($exp_tab_tax,$base_currency->name); ?>
            <?php if(count($exp_tab_rates) > 0){ ?>
            <br /><span class="text-muted text-sm"><?php echo implode(' + ', $exp_tab_rates); ?></span>
            <?php } ?>
          </td>
          <td class="text-right bold"><?php echo app_format_money($exp_tab_gross,$base_currency->name); ?></td>
         </tr>
         <?php } ?>
      </tbody>
      <?php if(count($expenses) > 0){ ?>
      <tfoot>
        <tr>
          <td colspan="5" class="text-right bold"><?php echo _l('total'); ?></td>
          <td class="text-right bold"><?php echo app_format_money($exp_tab_total_amount,$base_currency->name); ?></td>
          <td class="text-right bold"><?php echo app_format_money($exp_tab_total_tax,$base_currency->name); ?></td>
          <td class="text-right bold"><?php echo app_format_money($exp_tab_total_gross,$base_currency->name); ?></td>
        </tr>
      </tfoot>
      <?php } ?>
   </table> 
</div>
