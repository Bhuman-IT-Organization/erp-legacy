<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin font-bold">
              <i class="fa fa-truck"></i> <?php echo isset($shipping_charge) ? _l('edit') : _l('new'); ?> <?php echo _l('inventory_shipping_charges'); ?>
            </h4>
            <hr />

            <?php
            // form_open() rather than a raw <form>: CSRF protection is on
            // (APP_CSRF_PROTECTION), and only form_open() emits the hidden token.
            // Without it every submit was rejected with "419 Page Expired".
            echo form_open(
                admin_url('purchase/shipping_charge_form' . (isset($shipping_charge) ? '/' . $shipping_charge->id : '')),
                ['id' => 'shipping-charge-form']
            );
            ?>
              <input type="hidden" name="id" value="<?php echo isset($shipping_charge) ? $shipping_charge->id : ''; ?>">

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label><span class="text-danger">* </span><?php echo _l('invoices'); ?></label>
                    <select name="invoice_id" id="sc_invoice" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>" required onchange="sc_invoice_changed();">
                      <option value=""></option>
                      <?php foreach($invoices as $inv){ ?>
                        <option value="<?php echo $inv['id']; ?>" <?php if(isset($shipping_charge) && $shipping_charge->invoice_id == $inv['id']) echo 'selected'; ?>><?php echo $inv['invoice_number']; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label><span class="text-danger">* </span><?php echo _l('pur_vendor'); ?> (Shipping)</label>
                    <select name="vendor" id="sc_vendor" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>" required>
                      <option value=""></option>
                      <?php foreach($vendors as $v){ ?>
                        <option value="<?php echo $v['userid']; ?>" <?php if(isset($shipping_charge) && $shipping_charge->vendor == $v['userid']) echo 'selected'; ?>><?php echo $v['company']; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label for="shipping_invoice_number"><?php echo _l('pur_shipping_invoice_number'); ?></label>
                    <?php
                    // The freight vendor's own invoice number. Free text and separate from
                    // shipping_number, which is the ISC reference this system generates.
                    ?>
                    <input type="text" class="form-control" name="shipping_invoice_number" id="shipping_invoice_number"
                           value="<?php echo isset($shipping_charge) ? htmlspecialchars((string) ($shipping_charge->shipping_invoice_number ?? '')) : ''; ?>">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label for="shipping_invoice_date"><?php echo _l('pur_shipping_invoice_date'); ?></label>
                    <?php
                    // The date on the freight bill. This drives the accounting entry date,
                    // so a bill keyed in late still posts into the period it belongs to.
                    // Rendered and read back in the admin's display format via _d() /
                    // to_sql_date(), the same as cheque_date elsewhere in this module.
                    ?>
                    <div class="input-group date">
                      <input type="text" class="form-control datepicker" name="shipping_invoice_date" id="shipping_invoice_date"
                             autocomplete="off"
                             value="<?php
                             if (isset($shipping_charge)) {
                                 // Editing: show the stored date, blank for the charges
                                 // raised before this column existed
                                 echo (!empty($shipping_charge->shipping_invoice_date) && $shipping_charge->shipping_invoice_date != '0000-00-00')
                                     ? _d($shipping_charge->shipping_invoice_date)
                                     : '';
                             } else {
                                 // New charge: default to today so the field is never left
                                 // blank by accident, since it drives the posting date
                                 echo _d(date('Y-m-d'));
                             }
                             ?>">
                      <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                    </div>
                    <span class="help-block small text-muted"><?php echo _l('pur_shipping_invoice_date_hint'); ?></span>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label for="cost_center"><?php echo _l('cost_center'); ?></label>
                    <?php
                    // Same list the Purchase Request and Purchase Order forms use, so a
                    // freight bill can be coded to the cost centre that ordered the goods.
                    $CI =& get_instance();
                    $CI->load->model('cost_centers_model');
                    $sc_cost_centers = $CI->cost_centers_model->get();

                    $sc_selected_cc = isset($shipping_charge) && isset($shipping_charge->cost_center)
                        ? $shipping_charge->cost_center
                        : '';
                    ?>
                    <select name="cost_center" id="cost_center" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
                      <option value=""></option>
                      <?php foreach($sc_cost_centers as $cc){ ?>
                        <option value="<?php echo $cc['id']; ?>" <?php if((string) $sc_selected_cc === (string) $cc['id']){ echo 'selected'; } ?>><?php echo htmlspecialchars($cc['name']); ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <?php
              // Which GL account the freight will be debited to. Resolved from the
              // selected invoice's purchase order, so shipping follows the same
              // account the goods were coded to instead of always hitting Inventory
              // Raw Materials. Refreshed by sc_invoice_changed().
              ?>
              <div class="row" id="sc-debit-account-row" style="<?php echo isset($shipping_charge) ? '' : 'display:none;'; ?>">
                <div class="col-md-12">
                  <div class="alert alert-info mbot15" style="padding:8px 12px;">
                    <div>
                      <i class="fa fa-book"></i>
                      <strong><?php echo _l('pur_shipping_charge_debit_account'); ?>:</strong>
                      <span id="sc_debit_account_name"><?php echo isset($debit_account_name) ? e($debit_account_name) : '-'; ?></span>
                    </div>
                    <?php
                    // The order and the item type behind that account, so it is clear the
                    // freight is following the purchase and not a fixed default
                    ?>
                    <div class="small text-muted mtop5">
                      <?php echo _l('pur_order'); ?>:
                      <strong id="sc_debit_po_number"><?php echo !empty($debit_po_number) ? e($debit_po_number) : '-'; ?></strong>
                      &nbsp;&middot;&nbsp;
                      <?php echo _l('pur_shipping_charge_item_type'); ?>:
                      <strong id="sc_debit_po_type"><?php echo !empty($debit_po_type_name) ? e($debit_po_type_name) : '-'; ?></strong>
                      &nbsp;&middot;&nbsp;
                      <span id="sc_debit_source"></span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Invoice Details Panel -->
              <div id="invoice-details-panel" class="row" style="<?php echo isset($shipping_charge) ? '' : 'display:none;'; ?>">
                <div class="col-md-12">
                  <div class="well well-sm" style="background:#f9f9f9; margin-top:5px;">
                    <div class="row">
                      <div class="col-md-3">
                        <strong><?php echo _l('pur_vendor'); ?>:</strong> <span id="inv_detail_vendor"><?php echo isset($inv_vendor_name) ? $inv_vendor_name : '-'; ?></span>
                      </div>
                      <div class="col-md-2">
                        <strong><?php echo _l('invoice_date'); ?>:</strong> <span id="inv_detail_date"><?php echo isset($shipping_charge) ? _d($inv_date) : '-'; ?></span>
                      </div>
                      <div class="col-md-2">
                        <strong><?php echo _l('pur_order'); ?>:</strong> <span id="inv_detail_po"><?php echo isset($inv_po_number) ? $inv_po_number : '-'; ?></span>
                      </div>
                      <div class="col-md-2">
                        <strong><?php echo _l('subtotal'); ?>:</strong> <span id="inv_detail_subtotal">-</span>
                      </div>
                      <div class="col-md-1">
                        <strong><?php echo _l('tax'); ?>:</strong> <span id="inv_detail_tax">-</span>
                      </div>
                      <div class="col-md-2">
                        <strong><?php echo _l('total'); ?>:</strong> <span id="inv_detail_total">-</span>
                      </div>
                    </div>
                  </div>
                  <!-- Invoice line items loaded via AJAX -->
                  <div id="invoice-items-table" class="mtop10"></div>
                </div>
              </div>

              <!-- Charge lines: one row per service on the clearance bill, each with
                   its own rate and its own VAT%. Amount = Qty x Rate,
                   Tax Amount = Amount x VAT%. -->
              <hr />
              <h5 class="bold"><?php echo _l('pur_shipping_charge_services'); ?></h5>
              <p class="text-muted small">
                <i class="fa fa-info-circle"></i> <?php echo _l('pur_shipping_charge_vat_line_warning'); ?>
              </p>
              <div class="table-responsive">
                <table class="table table-bordered" id="sc-items-table">
                  <thead>
                    <tr>
                      <th style="width:40px;">#</th>
                      <th><?php echo _l('pur_shipping_charge_description'); ?></th>
                      <th style="width:90px;" class="text-right"><?php echo _l('quantity'); ?></th>
                      <th style="width:120px;" class="text-right"><?php echo _l('pur_shipping_charge_rate'); ?></th>
                      <th style="width:130px;" class="text-right"><?php echo _l('amount'); ?></th>
                      <th style="width:110px;" class="text-right"><?php echo _l('pur_shipping_charge_vat_percent'); ?></th>
                      <th style="width:130px;" class="text-right"><?php echo _l('pur_shipping_charge_tax_amount'); ?></th>
                      <th style="width:45px;"></th>
                    </tr>
                  </thead>
                  <tbody id="sc-items-body">
                    <?php
                    $sc_rows = (isset($shipping_charge_items) && count($shipping_charge_items) > 0)
                        ? $shipping_charge_items
                        : [['description' => '', 'qty' => 1, 'rate' => '', 'vat_percent' => 5]];
                    foreach($sc_rows as $sc_i => $sc_row){ ?>
                    <tr class="sc-item-row">
                      <td class="sc-row-no text-center"></td>
                      <td>
                        <input type="text" class="form-control sc-desc"
                               name="items[<?php echo $sc_i; ?>][description]"
                               value="<?php echo isset($sc_row['description']) ? e($sc_row['description']) : ''; ?>"
                               placeholder="<?php echo _l('pur_shipping_charge_description'); ?>">
                      </td>
                      <td>
                        <input type="number" step="0.01" min="0" class="form-control text-right sc-qty"
                               name="items[<?php echo $sc_i; ?>][qty]"
                               value="<?php echo isset($sc_row['qty']) ? $sc_row['qty'] : '1.00'; ?>">
                      </td>
                      <td>
                        <input type="number" step="0.01" min="0" class="form-control text-right sc-rate"
                               name="items[<?php echo $sc_i; ?>][rate]"
                               value="<?php echo isset($sc_row['rate']) ? $sc_row['rate'] : ''; ?>">
                      </td>
                      <td class="text-right sc-amount" style="vertical-align:middle;">0.00</td>
                      <td>
                        <select class="form-control sc-vat" name="items[<?php echo $sc_i; ?>][vat_percent]">
                          <option value="0" <?php if(isset($sc_row['vat_percent']) && (float)$sc_row['vat_percent'] == 0) echo 'selected'; ?>>0.00</option>
                          <?php foreach($taxes as $tax){ ?>
                            <option value="<?php echo $tax['taxrate']; ?>"
                              <?php if(isset($sc_row['vat_percent']) && (float)$sc_row['vat_percent'] == (float)$tax['taxrate']) echo 'selected'; ?>>
                              <?php echo $tax['taxrate']; ?>
                            </option>
                          <?php } ?>
                        </select>
                      </td>
                      <td class="text-right sc-tax" style="vertical-align:middle;">0.00</td>
                      <td class="text-center" style="vertical-align:middle;">
                        <a href="#" class="text-danger sc-remove-row" title="<?php echo _l('delete'); ?>"><i class="fa fa-trash"></i></a>
                      </td>
                    </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
              <button type="button" class="btn btn-default btn-sm" id="sc-add-row">
                <i class="fa fa-plus"></i> <?php echo _l('pur_shipping_charge_add_line'); ?>
              </button>

              <!-- Totals -->
              <div class="row mtop15">
                <div class="col-md-5 col-md-offset-7">
                  <table class="table table-bordered">
                    <tbody>
                      <tr>
                        <td class="bold"><?php echo _l('pur_shipping_charge_amount_excl_vat'); ?></td>
                        <td class="text-right sc-total-amount">0.00</td>
                      </tr>
                      <tr>
                        <td class="bold"><?php echo _l('pur_shipping_charge_vat_total'); ?></td>
                        <td class="text-right sc-total-tax">0.00</td>
                      </tr>
                      <tr>
                        <td class="bold"><?php echo _l('pur_shipping_charge_net_total'); ?></td>
                        <td class="text-right sc-total-net bold" style="font-size:15px;">0.00</td>
                      </tr>
                    </tbody>
                  </table>
                  <p class="text-muted small no-margin"><?php echo _l('pur_shipping_charge_net_hint'); ?></p>
                </div>
              </div>

              <!-- Recomputed server side on save; present so the values are visible -->
              <input type="hidden" name="amount"     id="sc_amount"     value="<?php echo isset($shipping_charge) ? $shipping_charge->amount : '0.00'; ?>">
              <input type="hidden" name="tax_amount" id="sc_tax_amount" value="<?php echo isset($shipping_charge->tax_amount) ? $shipping_charge->tax_amount : '0.00'; ?>">
              <input type="hidden" name="total"      id="sc_total"      value="<?php echo isset($shipping_charge) ? $shipping_charge->total : '0.00'; ?>">

              <?php
              // Admin Note, Vendor Note and Terms & Conditions, the same three fields the
              // purchase invoice carries. Admin Note stays internal; Vendor Note is
              // addressed to the freight vendor.
              //
              // Terms follow the purchase order rule: filled from the freight vendor's own
              // payment_terms when it has any, with the box and the template dropdown both
              // locked so the vendor's agreed wording cannot be edited by accident.
              // Otherwise the dropdown is live and a tblpur_terms template can be picked.
              ?>
              <hr />
              <div class="row">
                <div class="col-md-12 bottom-transaction">

                  <?php
                  $sc_adminnote = isset($shipping_charge) ? $shipping_charge->adminnote : '';
                  echo render_textarea('adminnote', 'adminnote', $sc_adminnote, ['rows' => 5]);
                  ?>

                  <?php
                  $sc_vendor_note = isset($shipping_charge)
                      ? $shipping_charge->vendor_note
                      : get_purchase_option('vendor_note');
                  echo render_textarea('vendor_note', 'vendor_note', $sc_vendor_note, ['rows' => 5]);
                  ?>

                  <div class="form-group mtop15">
                    <div class="row">
                      <div class="col-md-6">
                        <label for="terms_template"><?php echo _l('terms_and_conditions'); ?></label>
                      </div>
                      <div class="col-md-6">
                        <select name="terms_template" id="terms_template" class="selectpicker" data-width="100%" data-live-search="true"
                                data-none-selected-text="<?php echo _l('select_term_template'); ?>"
                                onchange="sc_load_term_content(this.value);"
                                <?php echo !empty($vendor_has_own_terms) ? 'disabled' : ''; ?>>
                          <option value=""><?php echo _l('select_term_template'); ?></option>
                          <?php foreach($pur_terms_list as $sc_pt){ ?>
                            <option value="<?php echo $sc_pt['id']; ?>"><?php echo pur_html_entity_decode($sc_pt['name']); ?></option>
                          <?php } ?>
                        </select>
                      </div>
                    </div>
                  </div>

                  <?php
                  $sc_terms = isset($shipping_charge)
                      ? $shipping_charge->terms
                      : get_purchase_option('terms_and_conditions');
                  echo render_textarea('terms', '', $sc_terms, [], [], '', 'tinymce');
                  ?>

                  <p class="text-muted small" id="sc_terms_locked_hint" style="<?php echo !empty($vendor_has_own_terms) ? '' : 'display:none;'; ?>">
                    <i class="fa fa-lock"></i> <?php echo _l('pur_shipping_charge_terms_from_vendor'); ?>
                  </p>
                </div>
              </div>

              <div class="btn-bottom-toolbar text-right">
                <a href="<?php echo admin_url('purchase/shipping_charges'); ?>" class="btn btn-default mright5"><?php echo _l('cancel'); ?></a>
                <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> <?php echo _l('submit'); ?></button>
              </div>
            <?php echo form_close(); ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
$(function(){
  sc_calc();
  <?php if(isset($shipping_charge)){ ?>
  sc_invoice_changed();
  <?php } ?>

  // Terms follow the freight vendor. Changing the vendor re-reads its payment_terms
  // and either fills and locks the box, or frees it up so a template can be chosen.
  $('#sc_vendor').on('change', function(){
    sc_vendor_terms_changed($(this).val());
  });

  // Flush the Terms editor into its textarea before the form posts. tinymce keeps
  // its content in an iframe, so without this the terms could submit as whatever
  // the textarea held at page load - and the box is often locked, which makes the
  // difference easy to miss.
  $('#shipping-charge-form').on('submit', function(){
    if(typeof tinymce !== 'undefined'){
      tinymce.triggerSave();
    }
  });

  <?php if(!empty($vendor_has_own_terms)){ ?>
  // Editing a charge whose vendor has its own terms. tinymce initialises after this
  // handler runs, so the lock has to wait for the instance to exist rather than
  // firing immediately and silently doing nothing.
  sc_wait_for_tinymce(function(){
    sc_set_terms_locked(true);
  });
  <?php } ?>

  // Recalculate whenever any line field changes
  $(document).on('input change', '#sc-items-body .sc-desc, #sc-items-body .sc-qty, #sc-items-body .sc-rate, #sc-items-body .sc-vat', function(){
    sc_calc();
  });

  $('#sc-add-row').on('click', function(){
    var $last = $('#sc-items-body .sc-item-row').last();
    var $row  = $last.clone();

    $row.find('input.sc-desc, input.sc-rate').val('');
    $row.find('input.sc-qty').val('1.00');
    $row.find('.sc-amount, .sc-tax').text('0.00');

    $('#sc-items-body').append($row);
    sc_renumber();
    sc_calc();
    $row.find('input.sc-desc').focus();
  });

  $(document).on('click', '#sc-items-body .sc-remove-row', function(e){
    e.preventDefault();
    // Always leave one row so the form is never empty
    if($('#sc-items-body .sc-item-row').length <= 1){
      $(this).closest('.sc-item-row').find('input').val('');
      $(this).closest('.sc-item-row').find('input.sc-qty').val('1.00');
    } else {
      $(this).closest('.sc-item-row').remove();
    }
    sc_renumber();
    sc_calc();
  });
});

// Keep the visible numbering and the posted field indexes contiguous, so removing
// a middle row cannot leave a gap in items[] for the server to sort out.
function sc_renumber(){
  $('#sc-items-body .sc-item-row').each(function(i){
    var $row = $(this);
    $row.find('.sc-row-no').text(i + 1);
    $row.find('input.sc-desc').attr('name', 'items[' + i + '][description]');
    $row.find('input.sc-qty').attr('name',  'items[' + i + '][qty]');
    $row.find('input.sc-rate').attr('name', 'items[' + i + '][rate]');
    $row.find('select.sc-vat').attr('name', 'items[' + i + '][vat_percent]');
  });
}

// A row that only restates the VAT total, as clearing bills print at the end
// ("VAT 5%", "VAT @ 5%   53.25"). Decided on the description alone: the row is a
// VAT restatement whatever the VAT % dropdown happens to be set to, since that
// value is inherited when a line is added and would otherwise make the row count
// as a service. Matching on the description STARTING with VAT keeps genuine
// services that merely mention it - "TRANSPORTATION (VAT)", "SERVICE CHARGES
// (VAT)" - out of scope.
// Kept in step with _sc_is_vat_line() in the controller.
function sc_is_vat_line(description){
  return /^vat(?![a-z])/i.test((description || '').trim());
}

function sc_calc(){
  sc_renumber();

  var total_amount   = 0;  // service lines only, excluding VAT
  var total_tax      = 0;  // VAT computed from each line's VAT %
  var vat_line_total = 0;  // rows that merely restate the VAT total

  $('#sc-items-body .sc-item-row').each(function(){
    var $row = $(this);
    var desc = $row.find('input.sc-desc').val() || '';
    var qty  = parseFloat($row.find('input.sc-qty').val())  || 0;
    var rate = parseFloat($row.find('input.sc-rate').val()) || 0;
    var vat  = parseFloat($row.find('select.sc-vat').val()) || 0;

    var isVatRow = sc_is_vat_line(desc);

    // A VAT row states tax, so no VAT is charged on top of it. Force the dropdown
    // to 0 so what is shown matches what gets stored.
    if(isVatRow && vat !== 0){
      $row.find('select.sc-vat').val('0');
      vat = 0;
    }
    $row.find('select.sc-vat').prop('disabled', isVatRow);

    var amount = Math.round(qty * rate * 100) / 100;
    var tax    = isVatRow ? 0 : Math.round(amount * vat) / 100;

    $row.find('.sc-amount').text(amount.toFixed(2));
    $row.find('.sc-tax').text(tax.toFixed(2));

    // Show why the row is being left out of the subtotal
    $row.css('background', isVatRow ? '#fbfbe8' : '');
    $row.find('.sc-vat-note').remove();
    if(isVatRow){
      $row.find('.sc-amount').append(
        '<br><small class="text-muted sc-vat-note"><?php echo addslashes(_l('pur_shipping_charge_vat_row_detected')); ?></small>'
      );
    }

    if(isVatRow){
      vat_line_total += amount;
    } else {
      total_amount += amount;
      total_tax    += tax;
    }
  });

  total_amount   = Math.round(total_amount * 100) / 100;
  total_tax      = Math.round(total_tax * 100) / 100;
  vat_line_total = Math.round(vat_line_total * 100) / 100;

  // Where the bill restates VAT as its own line, the tax is already counted in the
  // per-line VAT above, so it must not be added twice. The stated figure is only
  // used when no service line carries a VAT % of its own.
  if(total_tax === 0 && vat_line_total > 0){
    total_tax = vat_line_total;
  }

  var net = Math.round((total_amount + total_tax) * 100) / 100;

  $('.sc-total-amount').text(total_amount.toFixed(2));
  $('.sc-total-tax').text(total_tax.toFixed(2));
  $('.sc-total-net').text(net.toFixed(2));

  // The net total is what gets carried as the shipping charge
  $('#sc_amount').val(total_amount.toFixed(2));
  $('#sc_tax_amount').val(total_tax.toFixed(2));
  $('#sc_total').val(net.toFixed(2));
}

// Explains which of the three resolution steps produced the account, so a surprising
// result can be traced without digging through the ledger.
function sc_debit_source_label(source){
  if(source === 'po')   return '<?php echo addslashes(_l('pur_shipping_charge_debit_from_po')); ?>';
  if(source === 'type') return '<?php echo addslashes(_l('pur_shipping_charge_debit_from_type')); ?>';
  return '<?php echo addslashes(_l('pur_shipping_charge_debit_from_default')); ?>';
}

// tinymce boots asynchronously, so anything touching its body has to wait for the
// instance. Polls briefly instead of a fixed setTimeout, which either fires too
// early on a slow load or wastes a second on a fast one.
function sc_wait_for_tinymce(callback){
  var attempts = 0;
  var timer = setInterval(function(){
    attempts++;
    var ready = (typeof tinymce !== 'undefined' && tinymce.get('terms') && tinymce.get('terms').getBody());
    if(ready || attempts > 40){   // give up after ~8s and fall back to the textarea
      clearInterval(timer);
      callback();
    }
  }, 200);
}

// Read the Terms content whichever editor is actually in play
function sc_set_terms_content(html){
  if(typeof tinymce !== 'undefined' && tinymce.get('terms')){
    tinymce.get('terms').setContent(html || '');
  } else {
    $('textarea[name="terms"]').val(html || '');
  }
}

// Locking covers the editor, the template dropdown and the hint line together, so
// they can never end up disagreeing about whether the terms are the vendor's.
function sc_set_terms_locked(locked){
  var editor = (typeof tinymce !== 'undefined') ? tinymce.get('terms') : null;

  if(editor && editor.getBody()){
    editor.getBody().setAttribute('contenteditable', locked ? 'false' : 'true');
    editor.getBody().style.opacity = locked ? '0.7' : '1';
  } else {
    $('textarea[name="terms"]').prop('readonly', locked);
  }

  $('#terms_template').prop('disabled', locked).selectpicker('refresh');
  $('#sc_terms_locked_hint').toggle(locked);
}

// Load a template's wording into the Terms box. Only reachable when the vendor has
// no terms of its own, since otherwise the dropdown is disabled.
function sc_load_term_content(term_id){
  if(term_id == '' || term_id == null) return;
  $.get(admin_url + 'purchase/get_term_content/' + term_id, function(response){
    var data = JSON.parse(response);
    if(data.content){
      sc_set_terms_content(data.content);
    }
  });
}

// The vendor drives the Terms: its own payment_terms win over any template.
function sc_vendor_terms_changed(vendor_id){
  if(!vendor_id || vendor_id === ''){
    sc_set_terms_locked(false);
    return;
  }
  $.get(admin_url + 'purchase/get_vendor_payment_terms/' + vendor_id, function(resp){
    var data = JSON.parse(resp);
    if(data.payment_terms && data.payment_terms.trim() !== ''){
      sc_set_terms_content(data.payment_terms);
      sc_set_terms_locked(true);
      // Picking a vendor supersedes any template that was chosen first
      $('#terms_template').val('').selectpicker('refresh');
    } else {
      sc_set_terms_locked(false);
    }
  });
}

function sc_invoice_changed(){
  var inv_id = $('#sc_invoice').val();
  if(!inv_id || inv_id == ''){
    $('#invoice-details-panel').hide();
    $('#sc-debit-account-row').hide();
    return;
  }
  $.get(admin_url + 'purchase/get_invoice_details_for_shipping/' + inv_id, function(resp){
    var data = JSON.parse(resp);
    if(data.id){
      var curr = data.currency_name || '';

      // Which GL account the freight will be debited to, and why. Comes from the
      // selected invoice's purchase order, so the user sees it before saving rather
      // than discovering it in the ledger afterwards.
      $('#sc_debit_account_name').text(data.debit_account_name || '-');
      $('#sc_debit_po_number').text(data.debit_po_number || '-');
      $('#sc_debit_po_type').text(data.debit_po_type_name || '-');
      $('#sc_debit_source').text(sc_debit_source_label(data.debit_account_source));
      $('#sc-debit-account-row').show();
      $('#inv_detail_vendor').text(data.vendor_name || '-');
      $('#inv_detail_date').text(data.invoice_date || '-');
      $('#inv_detail_po').text(data.po_number || '-');
      $('#inv_detail_subtotal').text(curr + parseFloat(data.subtotal).toFixed(2));
      $('#inv_detail_tax').text(curr + parseFloat(data.tax).toFixed(2));
      $('#inv_detail_total').text(curr + parseFloat(data.total).toFixed(2));
      $('#invoice-details-panel').show();

      // Load line items
      $.get(admin_url + 'purchase/get_invoice_items_html/' + inv_id, function(html){
        $('#invoice-items-table').html(html);
      });
    }
  });
}
</script>

</body>
</html>
