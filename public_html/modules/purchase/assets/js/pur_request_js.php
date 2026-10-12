<script>
var hot;

function on_request_type_change(type_code){
  if(type_code == ''){
    // Hide asset ID column when type is cleared
    $('.asset_id_col').addClass('hide');
    $('.asset_id_cell').addClass('hide');
    $('.main .asset_id_cell').html('');
    window._current_asset_uid = '';
    return;
  }
  // Get the data-id from the selected option
  var type_id = $('#type option:selected').data('id');
  if(!type_id) return;
  
  $.get(admin_url + 'purchase/get_ledger_by_request_type/' + type_id, function(response){
    var data = JSON.parse(response);
    // Rebuild dropdown with returned accounts
    if(data.accounts && data.accounts.length > 0){
      var options = '<option value=""></option>';
      $.each(data.accounts, function(i, acc){
        var label = '';
        if(acc.number) label += acc.number + ' - ';
        if(acc.name) label += acc.name;
        else if(acc.key_name) label += acc.key_name;
        else label += 'Account #' + acc.id;
        var selected = (data.ledger_account && acc.id == data.ledger_account) ? 'selected' : '';
        options += '<option value="' + acc.id + '" ' + selected + '>' + label + '</option>';
      });
      $('#ledger_account').html(options);
      $('#ledger_account').selectpicker('refresh');
    }
    // If no ledger mapped, clear selection
    if(!data.ledger_account || data.ledger_account == ''){
      $('#ledger_account').selectpicker('val', '');
    }
  });

  // Switch item dropdown based on type
  // Clear existing items in grid when type changes
  $('.invoice-item table.invoice-items-table.items tbody').find('tr.item').remove();
  setTimeout(function(){ pur_calculate_total(); }, 15);

  if(type_code == 'AS'){
    // Show asset ID column
    $('.asset_id_col').removeClass('hide');
    $('.asset_id_cell').removeClass('hide');
    // Load asset creation items
    $.get(admin_url + 'purchase/get_asset_creation_items', function(response){
      var data = JSON.parse(response);
      var $wrapper = $('#item_select').closest('.form-group');
      if(!window._original_item_select_html){
        window._original_item_select_html = $wrapper.html();
      }
      $wrapper.html('<select name="item_select" class="selectpicker no-margin" data-width="100%" id="item_select" data-none-selected-text="<?php echo _l("select_item"); ?>" data-live-search="true">' + data.html + '</select>');
      $wrapper.find('.selectpicker').selectpicker();
    });
  } else {
    // Hide asset ID column
    $('.asset_id_col').addClass('hide');
    $('.asset_id_cell').addClass('hide');
    $('.main .asset_id_cell').html('');
    window._current_asset_uid = '';
    // Load items filtered by purchase type
    $.get(admin_url + 'purchase/get_all_items_for_pr/' + type_id, function(response){
      var data = JSON.parse(response);
      var $wrapper = $('#item_select').closest('.form-group');
      if(!window._original_item_select_html){
        window._original_item_select_html = $wrapper.html();
      }
      $wrapper.html('<select name="item_select" class="selectpicker no-margin" data-width="100%" id="item_select" data-none-selected-text="<?php echo _l("select_item"); ?>" data-live-search="true">' + data.html + '</select>');
      $wrapper.find('.selectpicker').selectpicker();
    });
  }
}

(function($) {
"use strict";  

init_pr_currency();
// Maybe items ajax search
init_ajax_search('items','#item_select.ajax-search',undefined,admin_url+'purchase/pur_commodity_code_search');

init_pur_ajax_search('vendors', '#send_to_vendors' );

validate_purrequest_form();

// Auto-generate PR code based on type
var pr_prefix = '<?php echo get_purchase_option('pur_request_prefix'); ?>';
var pr_next_number = <?php echo get_purchase_option('next_pr_number'); ?>;
var pr_year = '<?php echo date('Y'); ?>';
var pr_pad_length = 10 - pr_prefix.length;
var pr_type_numbers = {};

$('#type').on('change', function(){
  var type = $(this).val();
  if(type == '') {
    $('#pur_rq_code').val(pr_prefix + String(pr_next_number).padStart(pr_pad_length,'0'));
    $('input[name="number"]').val(pr_next_number);
    return;
  }
  // Always fetch fresh count from server
  $.get(admin_url + 'purchase/get_next_pr_number_by_type/' + type, function(res){
    var next_num = parseInt(res) || 1;
    pr_type_numbers[type] = next_num;
    var type_pad_length = 10 - type.length;
    $('#pur_rq_code').val(type + String(next_num).padStart(type_pad_length,'0'));
    $('input[name="number"]').val(next_num);
  });
});
function validate_purrequest_form(selector) {

    selector = typeof(selector) == 'undefined' ? '#add_edit_pur_request-form' : selector;

    appValidateForm($(selector), {
        pur_rq_code:'required', pur_rq_name:'required', currency:'required', ledger_account:'required'
    });
}



// Add item to preview from the dropdown for invoices estimates
$("body").on('change', 'select[name="item_select"]', function () {
  var itemid = $(this).selectpicker('val');
  if (itemid != '') {
    var type_code = $('#type').val();
    if(type_code == 'AS'){
      pur_add_asset_item_to_preview(itemid);
    } else {
      pur_add_item_to_preview(itemid);
    }
  }
});

$("body").on('change', 'select.taxes', function () {
  pur_calculate_total();
});

$("body").on('change', 'select[name="currency"]', function () {

  var currency_id = $(this).val();
  if(currency_id != ''){
    $.post(admin_url + 'purchase/get_currency_rate/'+currency_id).done(function(response){
      response = JSON.parse(response);
      if(response.currency_rate != 1){
        $('#currency_rate_div').removeClass('hide');

        $('input[name="currency_rate"]').val(response.currency_rate).change();

        $('#convert_str').html(response.convert_str);
        $('.th_currency').html(response.currency_name);
      }else{
        $('input[name="currency_rate"]').val(response.currency_rate).change();
        $('#currency_rate_div').addClass('hide');
        $('#convert_str').html(response.convert_str);
        $('.th_currency').html(response.currency_name);

      }

    });
  }else{
    alert_float('warning', "<?php echo _l('please_select_currency'); ?>" )
  }

  init_pr_currency();
});

$("input[name='currency_rate']").on('change', function () { 
    var currency_rate = $(this).val();
    var rows = $('.table.has-calculations tbody tr.item');
    $.each(rows, function () { 
      var old_price = $(this).find('td.rate input[name="og_price"]').val();
      var new_price = currency_rate*old_price;
      $(this).find('td.rate input[type="number"]').val(accounting.toFixed(new_price, app.options.decimal_places)).change();

    });
});

})(jQuery); 

var lastAddedItemKey = null;

function is_Numeric(num) {
  "use strict";
  return !isNaN(parseFloat(num)) && isFinite(num);
}

function get_tax_name_by_id(tax_id){
  "use strict";
  var taxe_arr = <?php echo json_encode($taxes); ?>;
  var name_of_tax = '';
  $.each(taxe_arr, function(i, val){
    if(val.id == tax_id){
      name_of_tax = val.label;
    }
  });
  return name_of_tax;
}

function numberWithCommas(x) {
  "use strict";
    x = x.toString().replace('.', "<?php echo get_option('decimal_separator'); ?>");

    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "<?php echo get_option('thousand_separator'); ?>");
}  



// function department_change(invoker){
//   "use strict";
//   if(invoker.value != ''){
//     $.post(admin_url + 'purchase/dpm_name_in_pur_request_number/'+invoker.value).done(function(response){
//       response = JSON.parse(response);
//       $('#pur_rq_code').html('');
//       $('#pur_rq_code').val('<?php echo pur_html_entity_decode($pur_rq_code); ?>-' + response.rs);
//     });
//   }
// }



/**
 * { coppy sale invoice }
 */
function coppy_sale_invoice(){
  "use strict";
  var sale_invoice = $('select[name="sale_invoice"]').val();

  if(sale_invoice != ''){
    $('input[id="from_items"]').prop("checked", false);
    $('#tax_area_body').html('');

    $.post(admin_url + 'purchase/coppy_sale_invoice/'+sale_invoice).done(function(response){
        response = JSON.parse(response);

        if(response){
          $('select[name="currency"]').val(response.currency).change();
          $('input[name="currency_rate"]').val(response.currency_rate).change();
          
          $('.invoice-item table.invoice-items-table.items tbody').html('');
          $('.invoice-item table.invoice-items-table.items tbody').append(response.list_item);

          setTimeout(function () {
            pur_calculate_total();
          }, 15);

          init_selectpicker();
          pur_reorder_items('.invoice-item');
          pur_clear_item_preview_values('.invoice-item');
          $('body').find('#items-warning').remove();
          $("body").find('.dt-loader').remove();
          $('#item_select').selectpicker('val', '');
        }

    });
  }else{
    alert_float('warning', '<?php echo _l('please_chose_sale_invoice'); ?>');
  }

}

/**
 * { coppy sale estimate }
 */
function coppy_sale_estimate(){
  "use strict";
  var sale_estimate = $('select[name="sale_estimate"]').val();

  if(sale_estimate != ''){
    $('input[id="from_items"]').prop("checked", false);
    $('#tax_area_body').html('');

    $.post(admin_url + 'purchase/coppy_sale_estimate/'+sale_estimate).done(function(response){
        response = JSON.parse(response);

        if(response){
          $('select[name="currency"]').val(response.currency).change();
          $('input[name="currency_rate"]').val(response.currency_rate).change();
          
          $('.invoice-item table.invoice-items-table.items tbody').html('');
          $('.invoice-item table.invoice-items-table.items tbody').append(response.list_item);

          setTimeout(function () {
            pur_calculate_total();
          }, 15);

          init_selectpicker();
          pur_reorder_items('.invoice-item');
          pur_clear_item_preview_values('.invoice-item');
          $('body').find('#items-warning').remove();
          $("body").find('.dt-loader').remove();
          $('#item_select').selectpicker('val', '');
        }

    });
  }else{
    alert_float('warning', '<?php echo _l('please_chose_sale_invoice'); ?>');
  }

}

function pur_add_item_to_preview(id) {
  "use strict";
  var currency_rate = $('input[name="currency_rate"]').val();

  requestGetJSON('purchase/get_item_by_id/' + id+'/'+ currency_rate).done(function (response) {
    clear_item_preview_values();

    $('.main input[name="item_code"]').val(response.itemid);
    $('.main textarea[name="item_text"]').val(response.code_description);
    $('.main textarea[name="item_description"]').val(response.long_description || '');
    $('.main input[name="unit_price"]').val(response.purchase_price);
    $('.main input[name="unit_name"]').val(response.unit_name);
    $('.main select[name="unit_id"]').val(response.unit_id).change();
    $('.main input[name="quantity"]').val();

    var taxSelectedArray = [];
    if (response.taxname && response.taxrate) {
      taxSelectedArray.push(response.taxname + '|' + response.taxrate);
    }
    if (response.taxname_2 && response.taxrate_2) {
      taxSelectedArray.push(response.taxname_2 + '|' + response.taxrate_2);
    }

    $('.main select.taxes').selectpicker('val', taxSelectedArray);
    $('.main input[name="unit"]').val(response.unit_name);

    var $currency = $("body").find('.accounting-template select[name="currency"]');
    var baseCurency = $currency.attr('data-base');
    var selectedCurrency = $currency.find('option:selected').val();
    var $rateInputPreview = $('.main input[name="rate"]');

    if (baseCurency == selectedCurrency) {
      $rateInputPreview.val(response.rate);
    } else {
      var itemCurrencyRate = response['rate_currency_' + selectedCurrency];
      if (!itemCurrencyRate || parseFloat(itemCurrencyRate) === 0) {
        $rateInputPreview.val(response.rate);
      } else {
        $rateInputPreview.val(itemCurrencyRate);
      }
    }

    $(document).trigger({
      type: "item-added-to-preview",
      item: response,
      item_type: 'item',
    });
  });
}

function pur_add_asset_item_to_preview(id) {
  "use strict";
  requestGetJSON('purchase/get_asset_creation_item_by_id/' + id).done(function (response) {
    clear_item_preview_values();
    $('.main input[name="item_code"]').val(response.itemid);
    $('.main textarea[name="item_text"]').val(response.description);
    $('.main input[name="unit_price"]').val(0);
    $('.main input[name="unit_name"]').val('');
    $('.main select[name="unit_id"]').val('').change();
    if(typeof $.fn.selectpicker !== 'undefined'){
      $('.main select[name="unit_id"]').selectpicker('refresh');
    }
    $('.main input[name="quantity"]').val(response.qty);
    // Store asset_uid for the row
    window._current_asset_uid = response.asset_uid || '';
    // Also show in the main preview cell
    $('.main .asset_id_cell').html(window._current_asset_uid).removeClass('hide');
  });
}

function tax_rate_by_id(tax_id){
  "use strict";
  var taxe_arr = <?php echo json_encode($taxes); ?>;
  var tax_rate = 0;
  $.each(taxe_arr, function(i, val){
    if(val.id == tax_id){
      tax_rate = val.taxrate;
    }
  });
  return tax_rate;
}

function pur_get_item_row_template(name, item_code, item_text, unit_price, quantity, unit_name, unit_id, into_money, item_key, tax_value, total, taxname, currency_rate, to_currency)  {
  "use strict";

  // Get item_description from the main preview row
  var item_description = $('.invoice-item .main textarea[name="item_description"]').val() || '';

  jQuery.ajaxSetup({
    async: false
  });

  var d = $.post(admin_url + 'purchase/get_purchase_request_row_template', {
    name: name,
    item_text : item_text,
    unit_price : unit_price,
    quantity : quantity,
    unit_name : unit_name,
    unit_id : unit_id,
    into_money : into_money,
    item_key : item_key,
    tax_value : tax_value,
    taxname : taxname,
    total : total,
    item_code : item_code,
    currency_rate: currency_rate,
    to_currency: to_currency,
    item_description: item_description
  });
  jQuery.ajaxSetup({
    async: true
  });
  return d;
}

function pur_add_item_to_table(data, itemid) {
  "use strict";

  data = typeof (data) == 'undefined' || data == 'undefined' ? pur_get_item_preview_values() : data;

  // Validate item was selected from dropdown (item_code must be set)
  if (!data.item_code || data.item_code == '') {
    alert_float('warning', '<?php echo _l('please_select_item_from_dropdown'); ?>');
    return;
  }

  if (data.warehouse_id == "" || data.quantities == "" || data.commodity_code == "" ) {
    if(data.warehouse_id == ""){
      alert_float('warning', '<?php echo _l('please_select_a_warehouse') ?>');
    }
    return;
  }
  if(parseFloat(data.unit_price) <= 0 || data.unit_price == ''){
    alert_float('warning', '<?php echo _l('unit_price'); ?> must be greater than zero');
    return;
  }
  if(!data.unit_id || data.unit_id == '' || data.unit_id == '0'){
    alert_float('warning', '<?php echo _l('please_select_unit'); ?>');
    return;
  }
  var item_description = $('.invoice-item .main textarea[name="item_description"]').val() || '';
  if(!item_description || item_description.trim() == ''){
    alert_float('warning', '<?php echo _l('item_description'); ?> is required');
    return;
  }
  var currency_rate = $('input[name="currency_rate"]').val();
  var to_currency = $('select[name="currency"]').val();
  var table_row = '';
  var item_key = lastAddedItemKey ? lastAddedItemKey += 1 : $("body").find('.invoice-items-table tbody .item').length + 1;
  lastAddedItemKey = item_key;
  $("body").append('<div class="dt-loader"></div>');
  pur_get_item_row_template('newitems[' + item_key + ']', data.item_code, data.item_text,data.unit_price,data.quantity, data.unit_name, data.unit_id, data.into_money, item_key, data.tax_value, data.total, data.taxname, currency_rate, to_currency).done(function(output){
    table_row += output;

    // If asset type, show asset ID column before appending
    var type_code = $('#type').val();
    if(type_code == 'AS'){
      $('.asset_id_col').removeClass('hide');
    }

    $('.invoice-item table.invoice-items-table.items tbody').append(table_row);

    // If asset type and we have asset_uid, ensure it's visible in the row
    if(type_code == 'AS' && window._current_asset_uid){
      var $lastRow = $('.invoice-item table.invoice-items-table.items tbody tr.item:last');
      var $cell = $lastRow.find('.asset_id_cell');
      $cell.html(window._current_asset_uid + '<input type="hidden" name="newitems[' + item_key + '][asset_uid]" value="' + window._current_asset_uid + '">');
      $cell.removeClass('hide');
      window._current_asset_uid = '';
    } else if(type_code == 'AS'){
      // Still show the cell even if no asset_uid
      var $lastRow = $('.invoice-item table.invoice-items-table.items tbody tr.item:last');
      $lastRow.find('.asset_id_cell').removeClass('hide');
    } else {
      // Non-asset type: ensure asset_id_cell is hidden and cleared
      var $lastRow = $('.invoice-item table.invoice-items-table.items tbody tr.item:last');
      $lastRow.find('.asset_id_cell').addClass('hide').html('');
    }

    setTimeout(function () {
      pur_calculate_total();
    }, 15);
    init_selectpicker();
    init_datepicker();
    pur_reorder_items('.invoice-item');
    pur_clear_item_preview_values('.invoice-item');
    // Clear asset ID from the main preview row
    //$('.main .asset_id_cell').html('').addClass('hide');
    $('.main .asset_id_cell').html('');
    $('body').find('#items-warning').remove();
    $("body").find('.dt-loader').remove();
        $('#item_select').selectpicker('val', '');

    return true;
  });
  return false;
}

function pur_reorder_items(parent) {
  "use strict";

  var rows = $(parent + ' .table.has-calculations tbody tr.item');
  var i = 1;
  $.each(rows, function () {
    $(this).find('input.order').val(i);
    i++;
  });
}

function pur_clear_item_preview_values(parent) {
  "use strict";

  var previewArea = $(parent + ' .main');
  previewArea.find('input').val('');
  previewArea.find('textarea').val('');
  previewArea.find('select').val('').selectpicker('refresh');
}

function pur_get_item_preview_values() {
  "use strict";

  var response = {};
  response.item_text = $('.invoice-item .main textarea[name="item_text"]').val();
  response.item_code = $('.invoice-item .main input[name="item_code"]').val();
  response.quantity = $('.invoice-item .main input[name="quantity"]').val();
  response.unit_name = $('.invoice-item .main input[name="unit_name"]').val();
  response.unit_id = $('.invoice-item .main select[name="unit_id"]').val();
  response.unit_price = $('.invoice-item .main input[name="unit_price"]').val();
  response.taxname = $('.main select.taxes').selectpicker('val');
  response.tax_rate = $('.invoice-item .main input[name="tax_rate"]').val();
  response.tax_value = $('.invoice-item .main input[name="tax_value"]').val();
  response.into_money = $('.invoice-item .main input[name="into_money"]').val();
  response.total = $('.invoice-item .main input[name="total"]').val();

  return response;
}


function pur_calculate_total(){
  "use strict";
  if ($('body').hasClass('no-calculate-total')) {
    return false;
  }

  var calculated_tax,
    taxrate,
    item_taxes,
    row,
    _amount,
    _tax_name,
    taxes = {},
    taxes_rows = [],
    subtotal = 0,
    total = 0,
    total_tax_money = 0,
    quantity = 1,
    total_discount_calculated = 0,
    rows = $('.table.has-calculations tbody tr.item'),
    subtotal_area = $('#subtotal'),
    discount_area = $('#discount_area'),
    adjustment = $('input[name="adjustment"]').val(),
    // discount_percent = $('input[name="discount_percent"]').val(),
    discount_percent = 'before_tax',
    discount_fixed = $('input[name="discount_total"]').val(),
    discount_total_type = $('.discount-total-type.selected'),
    discount_type = $('select[name="discount_type"]').val();

  $('.wh-tax-area').remove();

    $.each(rows, function () {

    quantity = $(this).find('[data-quantity]').val();
    if (quantity === '') {
      quantity = 1;
      $(this).find('[data-quantity]').val(1);
    }

    _amount = accounting.toFixed($(this).find('td.rate input').val() * quantity, app.options.decimal_places);
    _amount = parseFloat(_amount);
    var tax_value = 0;
    var row_total = _amount;

    $(this).find('td.into_money input').val(_amount);

    subtotal += _amount;
    row = $(this);
    item_taxes = $(this).find('select.taxes').val();

    if (item_taxes) {
      $.each(item_taxes, function (i, taxname) {
        taxrate = row.find('select.taxes [value="' + taxname + '"]').data('taxrate');
        calculated_tax = (_amount / 100 * taxrate);
        if (!taxes.hasOwnProperty(taxname)) {
          if (taxrate != 0) {
            _tax_name = taxname.split('|');
            var tax_row = '<tr class="wh-tax-area"><td>' + _tax_name[0] + '(' + taxrate + '%)</td><td id="tax_id_' + slugify(taxname) + '"></td></tr>';
            $(subtotal_area).after(tax_row);
            taxes[taxname] = calculated_tax;
          }
        } else {
          // Increment total from this tax
          taxes[taxname] = taxes[taxname] += calculated_tax;
        }
        
        tax_value += calculated_tax;
      });

      row_total = row_total + tax_value;
    }

    $(this).find('td.tax_value input').val(tax_value);
    $(this).find('td._total input').val(row_total);

  });

  // Discount by percent
  if ((discount_percent !== '' && discount_percent != 0) && discount_type == 'before_tax' && discount_total_type.hasClass('discount-type-percent')) {
    total_discount_calculated = (subtotal * discount_percent) / 100;
  } else if ((discount_fixed !== '' && discount_fixed != 0) && discount_type == 'before_tax' && discount_total_type.hasClass('discount-type-fixed')) {
    total_discount_calculated = discount_fixed;
  }

  $.each(taxes, function (taxname, total_tax) {
    if ((discount_percent !== '' && discount_percent != 0) && discount_type == 'before_tax' && discount_total_type.hasClass('discount-type-percent')) {
      total_tax_calculated = (total_tax * discount_percent) / 100;
      total_tax = (total_tax - total_tax_calculated);
    } else if ((discount_fixed !== '' && discount_fixed != 0) && discount_type == 'before_tax' && discount_total_type.hasClass('discount-type-fixed')) {
      var t = (discount_fixed / subtotal) * 100;
      total_tax = (total_tax - (total_tax * t) / 100);
    }

    total += total_tax;
    total_tax_money += total_tax;
    total_tax = format_money(total_tax);
    $('#tax_id_' + slugify(taxname)).html(total_tax);
  });

  total = (total + subtotal);

  // Discount by percent
  if ((discount_percent !== '' && discount_percent != 0) && discount_type == 'after_tax' && discount_total_type.hasClass('discount-type-percent')) {
    total_discount_calculated = (total * discount_percent) / 100;
  } else if ((discount_fixed !== '' && discount_fixed != 0) && discount_type == 'after_tax' && discount_total_type.hasClass('discount-type-fixed')) {
    total_discount_calculated = discount_fixed;
  }

  total = total - total_discount_calculated;
  adjustment = parseFloat(adjustment);

  // Check if adjustment not empty
  if (!isNaN(adjustment)) {
    total = total + adjustment;
  }

  var discount_html = '-' + format_money(total_discount_calculated);
    $('input[name="discount_total"]').val(accounting.toFixed(total_discount_calculated, app.options.decimal_places));

  // Append, format to html and display
  $('.discount-total').html(discount_html);
  $('.adjustment').html(format_money(adjustment));
  $('.wh-subtotal').html(format_money(subtotal) + hidden_input('total_goods_money', accounting.toFixed(subtotal, app.options.decimal_places)) + hidden_input('value_of_inventory', accounting.toFixed(subtotal, app.options.decimal_places)));

  $('.inventory_value').remove();
  
  
  $('input[name="subtotal"]').val(format_money(subtotal, true));

  $('.total_tax_value').remove();
  var total_tax_value = '<tr class="total_tax_value"><td><span class="bold"><?php echo _l('total_tax_money'); ?> :</span></td><td class="">'+format_money(total_tax_money)+'</td></tr>';
  $('#totalmoney').before(total_tax_value);

  $('.wh-total').html(format_money(total) + hidden_input('total_tax_money', accounting.toFixed(total_tax_money, app.options.decimal_places)) + hidden_input('total_money', accounting.toFixed(total, app.options.decimal_places)));

  $('input[name="total_mn"]').val(format_money(total, true));

  $(document).trigger('purchase-request-total-calculated');

}

function pur_delete_item(row, itemid,parent) {
  "use strict";

  $(row).parents('tr').addClass('animated fadeOut', function () {
    setTimeout(function () {
      $(row).parents('tr').remove();
      pur_calculate_total();
    }, 50);
  });
  if (itemid && $('input[name="isedit"]').length > 0) {
    $(parent+' #removed-items').append(hidden_input('removed_items[]', itemid));
  }
}

// Set the currency for accounting
function init_pr_currency(id, callback) {
    var $accountingTemplate = $("body").find('.accounting-template');

    if ($accountingTemplate.length || id) {
        var selectedCurrencyId = !id ? $accountingTemplate.find('select[name="currency"]').val() : id;

        requestGetJSON('misc/get_currency/' + selectedCurrencyId)
            .done(function (currency) {
                // Used for formatting money
                accounting.settings.currency.decimal = currency.decimal_separator;
                accounting.settings.currency.thousand = currency.thousand_separator;
                accounting.settings.currency.symbol = currency.symbol;
                accounting.settings.currency.format = currency.placement == 'after' ? '%v %s' : '%s%v';

                $('.currency_span').html(currency.symbol);

                pur_calculate_total();

                if(callback) {
                    callback();
                }
            });
    }
}

</script>