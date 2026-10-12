<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <div class="row">
              <div class="col-md-6">
                <h4 class="no-margin font-bold">
                  <i class="fa fa-truck"></i> <?php echo _l('inventory_shipping_charges'); ?> <?php echo !empty($shipping_charge->shipping_number) ? $shipping_charge->shipping_number : 'ISC'.str_pad($shipping_charge->id, 5, '0', STR_PAD_LEFT); ?>
                </h4>
              </div>
              <div class="col-md-6 _buttons text-right">
                <a href="<?php echo admin_url('purchase/print_shipping_charge/' . $shipping_charge->id); ?>" target="_blank" class="btn btn-default btn-with-tooltip" data-toggle="tooltip" title="<?php echo _l('print'); ?>"><i class="fa fa-print"></i> <?php echo _l('print'); ?></a>
                <a href="<?php echo admin_url('purchase/shipping_charge_form/' . $shipping_charge->id); ?>" class="btn btn-default btn-with-tooltip" data-toggle="tooltip" title="<?php echo _l('edit'); ?>"><i class="fa fa-pencil-square-o"></i></a>
                <a href="<?php echo admin_url('purchase/reverse_shipping_charge/' . $shipping_charge->id); ?>" class="btn btn-danger btn-with-tooltip _delete" data-toggle="tooltip" title="Reverse"><i class="fa fa-undo"></i></a>
                <a href="<?php echo admin_url('purchase/shipping_charges'); ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> <?php echo _l('back'); ?></a>
              </div>
            </div>
            <hr />

            <!-- Invoice & Shipping Info -->
            <div class="row">
              <div class="col-md-6">
                <table class="table table-striped no-margin">
                  <tbody>
                    <tr>
                      <td class="bold" width="40%"><?php echo _l('inventory_shipping_charges'); ?> Code:</td>
                      <td><?php echo !empty($shipping_charge->shipping_number) ? $shipping_charge->shipping_number : 'ISC'.str_pad($shipping_charge->id, 5, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                      <?php // The freight vendor's own invoice number, separate from the ISC code above ?>
                      <td class="bold"><?php echo _l('pur_shipping_invoice_number'); ?>:</td>
                      <td><?php echo !empty($shipping_charge->shipping_invoice_number) ? e($shipping_charge->shipping_invoice_number) : '-'; ?></td>
                    </tr>
                    <tr>
                      <?php // The date on the freight bill; this is the date the ledger entries carry ?>
                      <td class="bold"><?php echo _l('pur_shipping_invoice_date'); ?>:</td>
                      <td>
                        <?php echo (!empty($shipping_charge->shipping_invoice_date) && $shipping_charge->shipping_invoice_date != '0000-00-00')
                            ? _d($shipping_charge->shipping_invoice_date)
                            : '-'; ?>
                      </td>
                    </tr>
                    <tr>
                      <td class="bold"><?php echo _l('cost_center'); ?>:</td>
                      <td><?php echo $cost_center_name !== '' ? e($cost_center_name) : '-'; ?></td>
                    </tr>
                    <tr>
                      <td class="bold"><?php echo _l('invoice_code'); ?>:</td>
                      <td><a href="<?php echo admin_url('purchase/purchase_invoice/' . $invoice->id); ?>"><?php echo $invoice->invoice_number; ?></a></td>
                    </tr>
                    <tr>
                      <td class="bold"><?php echo _l('invoice_date'); ?>:</td>
                      <td><?php echo _d($invoice->invoice_date); ?></td>
                    </tr>
                    <tr>
                      <td class="bold"><?php echo _l('pur_vendor'); ?>:</td>
                      <td><?php echo $invoice_vendor_name; ?></td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="col-md-6">
                <table class="table table-striped no-margin">
                  <tbody>
                    <tr>
                      <td class="bold" width="40%"><?php echo _l('pur_order'); ?>:</td>
                      <td><?php echo $po_number; ?></td>
                    </tr>
                    <tr>
                      <?php // What was bought on that order - asset, raw material, spares - which is what decides the GL account below ?>
                      <td class="bold"><?php echo _l('pur_shipping_charge_item_type'); ?>:</td>
                      <td><?php echo !empty($debit_po_type_name) ? e($debit_po_type_name) : '-'; ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Shipping Vendor:</td>
                      <td><?php echo $vendor_name; ?></td>
                    </tr>
                    <tr>
                      <?php
                      // The GL account the freight was debited to. Taken from the purchase
                      // order rather than always Inventory Raw Materials, so freight sits
                      // on the same account as the goods it relates to.
                      ?>
                      <td class="bold"><?php echo _l('pur_shipping_charge_debit_account'); ?>:</td>
                      <td>
                        <?php echo e($debit_account_name); ?>
                        <br />
                        <small class="text-muted">
                          <?php
                          // Show which of the three resolution steps produced this account
                          if($debit_account_source === 'po'){
                              echo _l('pur_shipping_charge_debit_from_po');
                          } elseif($debit_account_source === 'type'){
                              echo _l('pur_shipping_charge_debit_from_type');
                          } else {
                              echo _l('pur_shipping_charge_debit_from_default');
                          }
                          ?>
                        </small>
                      </td>
                    </tr>
                    <tr>
                      <?php // The date the ledger entries were posted on ?>
                      <td class="bold"><?php echo _l('pur_shipping_charge_posted_on'); ?>:</td>
                      <td><?php echo _d($accounting_date); ?></td>
                    </tr>
                    <tr>
                      <td class="bold">Created Date:</td>
                      <td><?php echo _dt($shipping_charge->date_created); ?></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Description of Service: the charge exactly as the bill was entered -->
            <?php if(isset($shipping_charge_items) && count($shipping_charge_items) > 0){
              $sc_subtotal = 0;   // service lines only, excluding VAT
              $sc_vat      = 0;   // VAT from each line's VAT %
              $sc_vat_line = 0;   // rows that only restate the VAT total
              foreach($shipping_charge_items as $sc_item){
                  // Same rule the form and controller use
                  if(preg_match('/^vat(?![a-z])/i', trim((string) $sc_item['description']))){
                      $sc_vat_line += (float) $sc_item['amount'];
                  } else {
                      $sc_subtotal += (float) $sc_item['amount'];
                      $sc_vat      += (float) $sc_item['tax_amount'];
                  }
              }
              if(round($sc_vat, 2) == 0 && $sc_vat_line > 0){
                  $sc_vat = $sc_vat_line;
              }
              $sc_net = round($sc_subtotal + $sc_vat, 2);
            ?>
            <div class="mtop15">
              <h5 class="bold"><?php echo _l('pur_shipping_charge_services'); ?></h5>
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead>
                    <tr style="background:#f5f5f5;">
                      <th style="width:50px;">SNO</th>
                      <th><?php echo _l('pur_shipping_charge_description'); ?></th>
                      <th class="text-right" style="width:90px;"><?php echo _l('quantity'); ?></th>
                      <th class="text-right" style="width:120px;"><?php echo _l('pur_shipping_charge_rate'); ?></th>
                      <th class="text-right" style="width:140px;"><?php echo _l('amount'); ?><?php echo $currency_name != '' ? ' (' . $currency_name . ')' : ''; ?></th>
                      <th class="text-right" style="width:90px;"><?php echo _l('pur_shipping_charge_vat_percent'); ?></th>
                      <th class="text-right" style="width:130px;"><?php echo _l('pur_shipping_charge_tax_amount'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $sc_sr = 1; foreach($shipping_charge_items as $sc_item){
                      $sc_is_vat_row = (bool) preg_match('/^vat(?![a-z])/i', trim((string) $sc_item['description']));
                    ?>
                    <tr<?php echo $sc_is_vat_row ? ' style="background:#fbfbe8;"' : ''; ?>>
                      <td><?php echo $sc_sr++; ?></td>
                      <td>
                        <?php echo htmlspecialchars($sc_item['description'] ?? ''); ?>
                        <?php if($sc_is_vat_row){ ?>
                          <br><small class="text-muted"><?php echo _l('pur_shipping_charge_vat_row_detected'); ?></small>
                        <?php } ?>
                      </td>
                      <td class="text-right"><?php echo number_format((float) $sc_item['qty'], 2); ?></td>
                      <td class="text-right"><?php echo number_format((float) $sc_item['rate'], 2); ?></td>
                      <td class="text-right"><?php echo number_format((float) $sc_item['amount'], 2); ?></td>
                      <td class="text-right"><?php echo number_format((float) $sc_item['vat_percent'], 2); ?></td>
                      <td class="text-right"><?php echo number_format((float) $sc_item['tax_amount'], 2); ?></td>
                    </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>

              <div class="row">
                <div class="col-md-7">
                  <p class="no-margin"><span class="bold">TOTAL:</span></p>
                  <p class="text-muted"><?php echo pur_amount_in_words($sc_net, $currency_name); ?></p>
                </div>
                <div class="col-md-5">
                  <table class="table table-bordered no-margin">
                    <tbody>
                      <tr>
                        <td class="bold"><?php echo _l('pur_shipping_charge_amount_excl_vat'); ?></td>
                        <td class="text-right"><?php echo number_format($sc_subtotal, 2); ?></td>
                      </tr>
                      <tr>
                        <td class="bold"><?php echo _l('pur_shipping_charge_vat_total'); ?></td>
                        <td class="text-right"><?php echo number_format($sc_vat, 2); ?></td>
                      </tr>
                      <tr style="background:#f5f5f5;">
                        <td class="bold"><?php echo _l('pur_shipping_charge_net_total'); ?></td>
                        <td class="text-right bold" style="font-size:15px;"><?php echo number_format($sc_net, 2); ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <hr />
            <?php } ?>

            <?php
            // Admin Note, Vendor Note and Terms & Conditions. Admin Note is internal, so
            // it is labelled as such and deliberately left off the printable version.
            // Notes are plain text and escaped; terms hold HTML from the vendor record or
            // a term template, so they are printed as-is.
            $sc_has_notes = !empty($shipping_charge->adminnote)
                         || !empty($shipping_charge->vendor_note)
                         || !empty($shipping_charge->terms);
            if($sc_has_notes){ ?>
            <div class="row mtop15">
              <?php if(!empty($shipping_charge->adminnote)){ ?>
              <div class="col-md-6">
                <h5 class="bold"><?php echo _l('adminnote'); ?></h5>
                <p class="text-muted small"><?php echo _l('pur_shipping_charge_adminnote_internal'); ?></p>
                <div class="well well-sm" style="white-space:pre-wrap;"><?php echo e($shipping_charge->adminnote); ?></div>
              </div>
              <?php } ?>

              <?php if(!empty($shipping_charge->vendor_note)){ ?>
              <div class="col-md-6">
                <h5 class="bold"><?php echo _l('vendor_note'); ?></h5>
                <div class="well well-sm" style="white-space:pre-wrap;"><?php echo e($shipping_charge->vendor_note); ?></div>
              </div>
              <?php } ?>

              <?php if(!empty($shipping_charge->terms)){ ?>
              <div class="col-md-12">
                <h5 class="bold"><?php echo _l('terms_and_conditions'); ?></h5>
                <div class="well well-sm"><?php echo $shipping_charge->terms; ?></div>
              </div>
              <?php } ?>
            </div>
            <hr />
            <?php } ?>

            <!-- Line Items with Landing Cost -->
            <?php if(isset($invoice_items) && count($invoice_items) > 0){ 
              $total_qty = 0;
              foreach($invoice_items as $item){
                $total_qty += (float)$item['quantity'];
              }
              $shipping_amount = (float)$shipping_charge->amount;
              $shipping_tax_pct = (float)$shipping_charge->tax_percent;
              // Prefer the stored VAT total. A bill whose lines carry different rates
              // has no single percentage, so tax_percent is 0 and deriving from it
              // would under-report the tax.
              $shipping_tax_amount = isset($shipping_charge->tax_amount)
                  ? (float)$shipping_charge->tax_amount
                  : $shipping_amount * $shipping_tax_pct / 100;
              $shipping_total = (float)$shipping_charge->total;

              // Landing cost comes from Purchase_model::get_invoice_landed_costs() so
              // this page and the item's Landed Cost column can never disagree.
              //
              // The shipping Net Total (VAT included) is what gets spread. What
              // changed when this moved to the shared engine is the basis: freight is
              // now allocated pro rata by NET LINE VALUE rather than by quantity.
              // Splitting by quantity charged a cheap line the same freight per unit
              // as an expensive one, which distorts item cost on a mixed invoice. On
              // a single line invoice both give the same answer.
              $CI_lc = & get_instance();
              $CI_lc->load->model('purchase/purchase_model');
              $landed_lines = $CI_lc->purchase_model->get_invoice_landed_costs($shipping_charge->invoice_id);

              // Keyed by invoice line id so each row picks up its own allocation
              $landed_by_line = [];
              foreach($landed_lines as $landed_line){
                $landed_by_line[$landed_line['line_id']] = $landed_line;
              }
            ?>
            <div class="mtop15">
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead>
                    <tr style="background:#f5f5f5;">
                      <th>#</th>
                      <th><?php echo _l('invoice_table_item_heading'); ?></th>
                      <th><?php echo _l('item_description'); ?></th>
                      <th class="text-right"><?php echo _l('unit_price'); ?></th>
                      <th class="text-center"><?php echo _l('quantity'); ?></th>
                      <th class="text-right"><?php echo _l('tax_value'); ?></th>
                      <th class="text-right"><?php echo _l('total'); ?></th>
                      <th class="text-right"><?php echo _l('pur_landing_cost_allocated'); ?></th>
                      <th class="text-right">Shipping/Unit</th>
                      <th class="text-right bold" style="background:#e8f5e9;">Landing Cost/Unit</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $sr = 1; $inv_subtotal = 0; $inv_tax_total = 0; $inv_total = 0; $inv_allocated = 0;
                    foreach($invoice_items as $item){
                      $qty = (float)$item['quantity'];
                      $unit_price = (float)$item['unit_price'];
                      $item_tax = (float)$item['tax_value'];
                      $item_total = (float)$item['total_money'];
                      $inv_subtotal += (float)$item['into_money'];
                      $inv_tax_total += $item_tax;
                      $inv_total += $item_total;

                      // Allocation for this exact line. Falls back to zero rather than
                      // guessing if the line cannot be matched.
                      $item_landed = isset($item['id'], $landed_by_line[$item['id']])
                          ? $landed_by_line[$item['id']]
                          : null;

                      $item_allocated        = $item_landed ? $item_landed['allocated_shipping'] : 0;
                      $shipping_per_unit     = $item_landed ? $item_landed['shipping_per_unit'] : 0;
                      $landing_cost_per_unit = $item_landed
                          ? $item_landed['landed_unit_cost']
                          : $unit_price;

                      $inv_allocated += $item_allocated;
                    ?>
                    <tr>
                      <td><?php echo $sr++; ?></td>
                      <td><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                      <td class="text-right"><?php echo app_format_money($unit_price, ''); ?></td>
                      <td class="text-center"><?php echo app_format_number($qty); ?></td>
                      <td class="text-right"><?php echo app_format_money($item_tax, ''); ?></td>
                      <td class="text-right"><?php echo app_format_money($item_total, ''); ?></td>
                      <td class="text-right"><?php echo app_format_money($item_allocated, ''); ?></td>
                      <td class="text-right"><?php echo app_format_money($shipping_per_unit, ''); ?></td>
                      <td class="text-right bold" style="background:#e8f5e9;"><?php echo app_format_money($landing_cost_per_unit, ''); ?></td>
                    </tr>
                    <?php } ?>
                  </tbody>
                  <tfoot>
                    <tr style="background:#f5f5f5;">
                      <td colspan="7" class="text-right bold"><?php echo _l('total'); ?></td>
                      <td class="text-right bold"><?php echo app_format_money($inv_allocated, ''); ?></td>
                      <td colspan="2"></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
              <p class="text-muted tw-text-sm">
                <?php echo _l('pur_landing_cost_basis_note', app_format_money($shipping_charge->total, $currency_name)); ?>
              </p>

              <!-- Totals Section -->
              <div class="row">
                <div class="col-md-5 col-md-offset-7">
                  <table class="table text-right">
                    <tbody>
                      <tr>
                        <td class="bold"><?php echo _l('subtotal'); ?>:</td>
                        <td class="bold"><?php echo app_format_money($inv_subtotal, $currency_name); ?></td>
                      </tr>
                      <?php if($inv_tax_total > 0){ ?>
                      <tr>
                        <td class="bold"><?php echo _l('tax'); ?>:</td>
                        <td class="bold"><?php echo app_format_money($inv_tax_total, $currency_name); ?></td>
                      </tr>
                      <?php } ?>
                      <tr>
                        <td class="bold"><?php echo _l('invoice') . ' ' . _l('total'); ?>:</td>
                        <td class="bold"><?php echo app_format_money((float)$invoice->total, $currency_name); ?></td>
                      </tr>
                      <tr style="border-top:2px solid #000;">
                        <td colspan="2" class="bold" style="font-size:13px; text-align:left;"><?php echo _l('inventory_shipping_charges'); ?></td>
                      </tr>
                      <tr>
                        <td>Shipping Vendor:</td>
                        <td class="bold"><?php echo $vendor_name; ?></td>
                      </tr>
                      <tr>
                        <td class="bold"><?php echo _l('inventory_shipping_charges'); ?>:</td>
                        <td class="bold"><?php echo app_format_money($shipping_amount, $currency_name); ?></td>
                      </tr>
                      <?php if($shipping_tax_amount > 0){ ?>
                      <tr>
                        <td>Shipping VAT<?php echo $shipping_tax_pct > 0 ? ' (' . $shipping_tax_pct . '%)' : ''; ?>:</td>
                        <td><?php echo app_format_money($shipping_tax_amount, $currency_name); ?></td>
                      </tr>
                      <?php } ?>
                      <tr>
                        <td class="bold">Shipping Total:</td>
                        <td class="bold"><?php echo app_format_money($shipping_total, $currency_name); ?></td>
                      </tr>
                      <tr style="background:#e8f5e9; border-top:2px solid #000;">
                        <td class="bold" style="font-size:13px;">Grand Total (Invoice + Shipping):</td>
                        <td class="bold" style="font-size:13px;"><?php echo app_format_money((float)$invoice->total + $shipping_total, $currency_name); ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <?php } ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
</body>
</html>
