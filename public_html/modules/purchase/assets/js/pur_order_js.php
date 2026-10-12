<script>

var transaction_currency_rate = 0;

function on_request_type_change(type_code){
  if(type_code == ''){
    // Hide asset ID column when type is cleared
    $('.asset_id_col').addClass('hide');
    $('.asset_id_cell').addClass('hide');
    $('.main .asset_id_cell').html('');
    window._current_asset_uid = '';
    return;
  }
  var type_id = $('#type option:selected').data('id');
  if(!type_id) return;

  // Skip ledger override if PR is being loaded (flag set by coppy_pur_request)
  var skipLedgerOverride = window._skip_type_ledger_override || false;

  $.get(admin_url + 'purchase/get_ledger_by_request_type/' + type_id, function(response){
    var data = JSON.parse(response);

    if(!skipLedgerOverride){
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
    }
    // Reset the flag
    window._skip_type_ledger_override = false;
  });

  // Switch item dropdown based on type
  var $wrapper = $('#item_select').closest('.form-group');
  if(type_code == 'AS'){
    // Show asset ID column
    $('.asset_id_col').removeClass('hide');
    $('.asset_id_cell').removeClass('hide');
    $.get(admin_url + 'purchase/get_asset_creation_items', function(response){
      var data = JSON.parse(response);
      $wrapper.html('<select name="item_select" class="selectpicker no-margin" data-width="100%" id="item_select" data-none-selected-text="<?php echo _l("select_item"); ?>" data-live-search="true">' + data.html + '</select>');
      $wrapper.find('.selectpicker').selectpicker();
    });
  } else {
    // Hide asset ID column and clear asset data
    $('.asset_id_col').addClass('hide');
    $('.asset_id_cell').addClass('hide');
    $('.main .asset_id_cell').html('');
    window._current_asset_uid = '';
    $.get(admin_url + 'purchase/get_all_items_for_pr', function(response){
      var data = JSON.parse(response);
      $wrapper.html('<select name="item_select" class="selectpicker no-margin" data-width="100%" id="item_select" data-none-selected-text="<?php echo _l("select_item"); ?>" data-live-search="true">' + data.html + '</select>');
      $wrapper.find('.selectpicker').selectpicker();
    });
  }
}

function load_term_content(term_id){
  if(term_id == '' || term_id == null) return;
  $.get(admin_url + 'purchase/get_term_content/' + term_id, function(response){
    var data = JSON.parse(response);
    if(data.content){
      if(typeof tinymce !== 'undefined' && tinymce.get('terms')){
        tinymce.get('terms').setContent(data.content);
      } else {
        $('textarea[name="terms"]').val(data.content);
      }
    }
  });
}

$(function(){
  "use strict";

    init_ajax_search("customer", ".client-ajax-search");
    init_po_currency();
    // Maybe items ajax search
    <?php if(get_purchase_option('item_by_vendor') != 1){ ?>
      init_ajax_search('items','#item_select.ajax-search',undefined,admin_url+'purchase/pur_commodity_code_search');
    <?php } ?>

    pur_calculate_total();

    // On edit: if vendor has payment_terms, disable template dropdown and editor
    <?php if(isset($pur_order) && isset($pur_order->vendor) && $pur_order->vendor > 0){
      $CI_pt =& get_instance();
      $CI_pt->db->select('payment_terms');
      $CI_pt->db->where('userid', $pur_order->vendor);
      $_ven_pt = $CI_pt->db->get(db_prefix().'pur_vendor')->row();
      if($_ven_pt && !empty($_ven_pt->payment_terms)){ ?>
        $('#terms_template').prop('disabled', true);
        $('#terms_template').selectpicker('refresh');
        setTimeout(function(){
          if(typeof tinymce !== 'undefined' && tinymce.get('terms')){
            tinymce.get('terms').getBody().setAttribute('contenteditable', false);
            tinymce.get('terms').getBody().style.opacity = '0.7';
          } else {
            $('textarea[name="terms"]').prop('readonly', true);
          }
        }, 1000);
      <?php }
    } ?>

    validate_purorder_form();

    // Auto-generate PO number based on type
    var po_prefix = '<?php echo get_purchase_option('pur_order_prefix'); ?>';
    var po_next_number = <?php echo get_purchase_option('next_po_number'); ?>;
    var po_pad_length = 10 - po_prefix.length;
    var po_type_numbers = {};
    window.po_vendor_code = '';

    $('#type').on('change changed.bs.select', function(){
      var type = $(this).val();
      // Also trigger ledger auto-select
      on_request_type_change(type);

      var $po_input = $('input[name="pur_order_number"]');

      if(type == '') {
        $po_input.val(po_prefix + String(po_next_number).padStart(po_pad_length,'0')).trigger('change');
        $('input[name="number"]').val(po_next_number);
        return;
      }
      if(!po_type_numbers[type]) {
        $.get(admin_url + 'purchase/get_next_po_number_by_type/' + type, function(res){
          po_type_numbers[type] = parseInt(res) || 1;
          var type_pad_length = 10 - po_prefix.length;
          $po_input.removeAttr('readonly').val(po_prefix + String(po_type_numbers[type]).padStart(type_pad_length,'0')).attr('readonly','readonly');
          $('input[name="number"]').val(po_type_numbers[type]);
        });
      } else {
        var type_pad_length = 10 - po_prefix.length;
        $po_input.removeAttr('readonly').val(po_prefix + String(po_type_numbers[type]).padStart(type_pad_length,'0')).attr('readonly','readonly');
        $('input[name="number"]').val(po_type_numbers[type]);
      }
    });
    function validate_purorder_form(selector) {

        selector = typeof(selector) == 'undefined' ? '#pur_order-form' : selector;

        <?php if(get_option('pur_order_project_required_condition') == 1){ ?>
          appValidateForm($(selector), {
              pur_request: 'required',
              project: 'required',
              pur_order_number: 'required',
              pur_order_name: 'required',
              order_date: 'required',
              vendor: 'required',
          });
        <?php }else{ ?>
          appValidateForm($(selector), {
              pur_request: 'required',
              pur_order_number: 'required',
              pur_order_name: 'required',
              order_date: 'required',
              vendor: 'required',
          });
        <?php } ?>

        // Sync TinyMCE and validate terms before submit
        $(selector).on('submit', function(e){
          if(typeof tinymce !== 'undefined' && tinymce.get('terms')){
            tinymce.get('terms').save();
          }
          var termsVal = $.trim($('textarea[name="terms"]').val());
          if(!termsVal || termsVal == '' || termsVal == '<p></p>' || termsVal == '<p>&nbsp;</p>' || termsVal == '<br>'){
            alert_float('warning', '<?php echo _l('terms_and_conditions_required'); ?>');
            $(selector).find('.transaction-submit').prop('disabled', false);
            e.preventDefault();
            return false;
          }
        });
    }

    $("body").on('change', 'select[name="item_select"]', function () {
      var itemid = $(this).selectpicker('val');
      if (itemid != '') {
        var type_code = $('#type').val();
        if(type_code == 'AS'){
          // Load asset item details
          requestGetJSON('purchase/get_asset_creation_item_by_id/' + itemid).done(function(response){
            clear_item_preview_values();
            $('.main input[name="commodity_code"]').val(response.itemid);
            $('.main textarea[name="commodity_name"]').val(response.description);
            $('.main input[name="unit_price"]').val(0);
            $('.main input[name="unit_name"]').val('');
            $('.main input[name="unit_id"]').val('');
            $('.main input[name="quantities"]').val(response.qty);
          });
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
      var data = {};
      data.clients = $('select[name="clients[]"]').val();
      if(currency_id != ''){
        $.post(admin_url + 'purchase/get_currency_rate/'+currency_id, data).done(function(response){
          response = JSON.parse(response);
          if(response.currency_rate != 1){
            $('#currency_rate_div').removeClass('hide');

            $('input[name="currency_rate"]').val(response.currency_rate).change();
            if(transaction_currency_rate != 0){
              $('input[name="currency_rate"]').val(transaction_currency_rate).change();
              transaction_currency_rate = 0;

            }

            $('#convert_str').html(response.convert_str);
            $('.th_currency').html(response.currency_name);
          }else{
            $('input[name="currency_rate"]').val(response.currency_rate).change();
            if(transaction_currency_rate != 0){
              $('input[name="currency_rate"]').val(transaction_currency_rate).change();
              transaction_currency_rate = 0;
            }
            $('#currency_rate_div').addClass('hide');
            $('#convert_str').html(response.convert_str);
            $('.th_currency').html(response.currency_name);

          }

          $('select[name="sale_invoice[]"]').html(response.html);
          $('select[name="sale_invoice[]"]').selectpicker('refresh');


        });
      }else{
        alert_float('warning', "<?php echo _l('please_select_currency'); ?>" )
      }
      init_po_currency();
    });

    $("input[name='currency_rate']").on('change', function () { 
        var currency_rate = $(this).val();
        if(!currency_rate || currency_rate == 0 || isNaN(currency_rate)) return;
        var rows = $('.table.has-calculations tbody tr.item');
        $.each(rows, function () { 
          var old_price = $(this).find('td.rate input[name="og_price"]').val();
          if(!old_price || old_price == 0) return true; // skip if no original price
          var new_price = currency_rate*old_price;
          $(this).find('td.rate input[type="number"]').val(accounting.toFixed(new_price, app.options.decimal_places)).change();

        });
    });


     $("body").on("change", 'select[name="discount_type"]', function () {
        // if discount_type == ''
        if ($(this).val() === "") {
          $('input[name="order_discount"]').val(0);
        }
        // Recalculate the total
        pur_calculate_total();
      });

     <?php if(isset($pur_order)){ ?>
      $.post(admin_url + 'purchase/get_html_approval_setting_for_po/'+ <?php echo pur_html_entity_decode($pur_order->id); ?>).done(function(response) {
         response = JSON.parse(response);

          $('.list_approve').html('');
          $('.list_approve').append(response);
      init_selectpicker();

      });
     <?php } ?>

      var addMoreVendorsInputKey = $('.list_approve select[name*="approver"]').length;
     $("body").on('click', '.new_vendor_requests', function() {
       if ($(this).hasClass('disabled')) { return false; }
      
         addMoreVendorsInputKey = $('.list_approve select[name*="approver"]').length;
        var newattachment = $('.list_approve').find('#item_approve').eq(0).clone().appendTo('.list_approve');
        newattachment.find('button[data-toggle="dropdown"]').remove();
        newattachment.find('select').selectpicker('refresh');

        newattachment.find('button[data-id="approver[0]"]').attr('data-id', 'approver[' + addMoreVendorsInputKey + ']');
        newattachment.find('label[for="approver[0]"]').attr('for', 'approver[' + addMoreVendorsInputKey + ']');
        newattachment.find('select[name="approver[0]"]').attr('name', 'approver[' + addMoreVendorsInputKey + ']');
        newattachment.find('select[id="approver[0]"]').attr('id', 'approver[' + addMoreVendorsInputKey + ']').selectpicker('refresh');
        newattachment.find('select[data-id="0"]').attr('data-id', addMoreVendorsInputKey);

        newattachment.find('button[data-id="staff[0]"]').attr('data-id', 'staff[' + addMoreVendorsInputKey + ']');
        newattachment.find('label[for="staff[0]"]').attr('for', 'staff[' + addMoreVendorsInputKey + ']');
        newattachment.find('select[name="staff[0]"]').attr('name', 'staff[' + addMoreVendorsInputKey + ']');
        newattachment.find('select[id="staff[0]"]').attr('id', 'staff[' + addMoreVendorsInputKey + ']').selectpicker('refresh');

        newattachment.find('button[data-id="action[0]"]').attr('data-id', 'action[' + addMoreVendorsInputKey + ']');
        newattachment.find('label[for="action[0]"]').attr('for', 'action[' + addMoreVendorsInputKey + ']');
        newattachment.find('select[name="action[0]"]').attr('name', 'action[' + addMoreVendorsInputKey + ']');
        newattachment.find('select[id="action[0]"]').attr('id', 'action[' + addMoreVendorsInputKey + ']').selectpicker('refresh');

        newattachment.find('#is_staff_0').attr('id', 'is_staff_' + addMoreVendorsInputKey);
        newattachment.find('button[name="add"] i').removeClass('fa-plus').addClass('fa-minus');
        newattachment.find('button[name="add"]').removeClass('new_vendor_requests').addClass('remove_vendor_requests').removeClass('btn-success').addClass('btn-danger');

        $('select[name="approver[' + addMoreVendorsInputKey + ']"]').on('change', function(){
          let index = $(this).attr('data-id');
          if($(this).val() == 'staff'){
            $('#is_staff_' + index).removeClass('hide');
            $('select[name="staff['+ index +']"').attr('required', 'required');
          }else{
            $('#is_staff_' + index).addClass('hide');
            $('select[name="staff['+ index +']"').removeAttr('required');
          }
        });

        addMoreVendorsInputKey++;

    });
    $("body").on('click', '.remove_vendor_requests', function() {
        $(this).parents('#item_approve').remove();
    });

    $('body').on('change', '.approver_class' , function(){
        addMoreVendorsInputKey = $('.list_approve select[name*="approver"]').length;

        for(let i = 0; i < addMoreVendorsInputKey; i++){
          if($('select[name="approver['+i+']"]').val() == 'staff'){
            $('#is_staff_' + i).removeClass('hide');
            $('select[name="staff['+ i +']"').attr('required', 'required');
        }else{
            $('#is_staff_' + i).addClass('hide');
            $('select[name="staff['+ i +']"').removeAttr('required');
        }
        }
    });

});

var lastAddedItemKey = null;

function estimate_by_vendor(invoker){
  "use strict";
  var po_number = '<?php echo pur_html_entity_decode( $pur_order_number); ?>';
  if(invoker.value != 0){
    $.post(admin_url + 'purchase/estimate_by_vendor/'+invoker.value).done(function(response){
      response = JSON.parse(response);
      $('select[name="estimate"]').html('');
      $('select[name="estimate"]').append(response.result);
      $('select[name="estimate"]').selectpicker('refresh');
      $('#vendor_data').html('');
      $('#vendor_data').append(response.ven_html);
      
      // Only set vendor currency if NOT loading from PR (PR currency takes priority)
      if(!window._skip_vendor_currency_override){
        $('select[name="currency"]').val(response.currency_id).change();
      }
      window._skip_vendor_currency_override = false;

      // Prefill Terms & Conditions from vendor payment_terms
      if(response.payment_terms && response.payment_terms.trim() != ''){
        // Set terms content from vendor
        if(typeof tinymce !== 'undefined' && tinymce.get('terms')){
          tinymce.get('terms').setContent(response.payment_terms);
          // Disable the editor
          tinymce.get('terms').getBody().setAttribute('contenteditable', false);
          tinymce.get('terms').getBody().style.opacity = '0.7';
        } else {
          $('textarea[name="terms"]').val(response.payment_terms);
          $('textarea[name="terms"]').prop('readonly', true);
        }
        // Disable template dropdown since vendor has terms
        $('#terms_template').prop('disabled', true);
        $('#terms_template').selectpicker('refresh');
      } else {
        // Vendor has no terms - enable template dropdown and editor
        $('#terms_template').prop('disabled', false);
        $('#terms_template').selectpicker('refresh');
        if(typeof tinymce !== 'undefined' && tinymce.get('terms')){
          tinymce.get('terms').getBody().setAttribute('contenteditable', true);
          tinymce.get('terms').getBody().style.opacity = '1';
        } else {
          $('textarea[name="terms"]').prop('readonly', false);
        }
      }

      <?php if(get_option('po_only_prefix_and_number') != 1){ ?>
      // PO number is fixed 10-digit format, no vendor suffix needed
      <?php } ?>
      <?php if(get_purchase_option('item_by_vendor') == 1){ ?>
        if(response.option_html != ''){
         $('#item_select').html(response.option_html);
         $('.selectpicker').selectpicker('refresh');
        }else if(response.option_html == ''){
          init_ajax_search('items','#item_select.ajax-search',undefined,admin_url+'purchase/pur_commodity_code_search/purchase_price/can_be_purchased/'+invoker.value);
        }
        
       <?php } ?>
    });
  }
}

function coppy_pur_estimate(){
  "use strict";
  var pur_estimate = $('select[name="estimate"]').val();
  if(pur_estimate != ''){
    $.post(admin_url + 'purchase/coppy_pur_estimate/'+pur_estimate).done(function(response){
        response = JSON.parse(response);
        if(response){ 
          transaction_currency_rate = response.currency_rate;



          $('select[name="currency"]').val(response.currency).change();
          $('input[name="currency_rate"]').val(response.currency_rate).change();
          $('input[name="shipping_fee"]').val(response.shipping_fee).change();

          $('select[name="discount_type"]').val(response.discount_type).change();

          $('.invoice-item table.invoice-items-table.items tbody').find('.main').remove();
          $('.invoice-item table.invoice-items-table.items tbody').find('.delete-po-item').click();
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
  }
}

function coppy_pur_request(){
  "use strict";
  var pur_request = $('select[name="pur_request"]').val();
  var vendor = $('select[name="vendor"]').val();

  // Fields to disable/enable based on PR selection
  var pr_fields = ['select[name="buyer"]', 'select[name="department"]', 'select[name="cost_center"]', 'select[name="ledger_account"]', 'select[name="project"]', 'select[name="type"]', 'select[name="currency"]'];

  if(pur_request != ''){
    $.post(admin_url + 'purchase/coppy_pur_request_for_po/'+pur_request+'/'+vendor).done(function(response){
        response = JSON.parse(response);
        if(response){ 
          // Prevent vendor change from overriding PR currency
          window._skip_vendor_currency_override = true;
          
          transaction_currency_rate = response.currency_rate;

          $('select[name="estimate"]').html(response.estimate_html);
          $('select[name="estimate"]').selectpicker('refresh');

          $('select[name="currency"]').val(response.currency).change();
          $('input[name="currency_rate"]').val(response.currency_rate).change();

          $('select[name="type"]').val(response.type);
          $('select[name="type"]').selectpicker('refresh');
          // Set flag to prevent type change from overriding ledger_account from PR
          window._skip_type_ledger_override = true;
          // Trigger type change to generate PO number and auto-select ledger
          $('#type').trigger('change').trigger('changed.bs.select');

          // Prefill cost center and ledger from PR
          if(response.cost_center && response.cost_center != ''){
            $('select[name="cost_center"]').val(response.cost_center);
            $('select[name="cost_center"]').selectpicker('refresh');
          }

          // Prefill Terms & Conditions from vendor payment terms
          if(response.payment_terms && response.payment_terms != ''){
            if(typeof tinymce !== 'undefined' && tinymce.get('terms')){
              tinymce.get('terms').setContent(response.payment_terms);
              // Disable the editor
              tinymce.get('terms').getBody().setAttribute('contenteditable', false);
            } else {
              $('textarea[name="terms"]').val(response.payment_terms);
              $('textarea[name="terms"]').prop('readonly', true);
            }
            // Disable T&C dropdown
            $('select[name="term_id"]').prop('disabled', true);
            $('select[name="term_id"]').selectpicker('refresh');
          } else {
            // Enable T&C dropdown and editor if payment terms is empty
            if(typeof tinymce !== 'undefined' && tinymce.get('terms')){
              tinymce.get('terms').getBody().setAttribute('contenteditable', true);
            } else {
              $('textarea[name="terms"]').prop('readonly', false);
            }
            $('select[name="term_id"]').prop('disabled', false);
            $('select[name="term_id"]').selectpicker('refresh');
          }
          if(response.ledger_account && response.ledger_account != '' && response.ledger_account != 'null'){
            // Add the account to dropdown if not already there, then select it
            var $ledger = $('select[name="ledger_account"]');
            if($ledger.find('option[value="'+response.ledger_account+'"]').length == 0){
              // Account not in dropdown - fetch its details and add it
              $.get(admin_url + 'purchase/get_account_label/' + response.ledger_account, function(acc_resp){
                var acc_data = JSON.parse(acc_resp);
                $ledger.append('<option value="'+response.ledger_account+'">'+acc_data.label+'</option>');
                $ledger.val(response.ledger_account);
                $ledger.selectpicker('refresh');
                // Update hidden field after async set
                $('input.pr-hidden-field[name="ledger_account"]').remove();
                $ledger.closest('form').append('<input type="hidden" name="ledger_account" value="'+response.ledger_account+'" class="pr-hidden-field">');
              });
            } else {
              $ledger.val(response.ledger_account);
              $ledger.selectpicker('refresh');
            }
          }
          if(response.department && response.department != ''){
            $('select[name="department"]').val(response.department);
            $('select[name="department"]').selectpicker('refresh');
          }
          if(response.project && response.project != ''){
            $('select[name="project"]').val(response.project);
            $('select[name="project"]').selectpicker('refresh');
          }

          // Disable fields after prefill and add hidden inputs for submission
          $.each(pr_fields, function(i, selector){
            $(selector).prop('disabled', true);
            $(selector).selectpicker('refresh');
            // Add hidden input to submit the value
            var field_name = $(selector).attr('name');
            var field_val = $(selector).val();
            // For ledger_account, also check the response value directly (async set may not be done yet)
            if(field_name == 'ledger_account' && (!field_val || field_val == '') && response.ledger_account){
              field_val = response.ledger_account;
            }
            // For currency, use the PR currency value directly
            if(field_name == 'currency' && (!field_val || field_val == '') && response.currency){
              field_val = response.currency;
            }
            $('input.pr-hidden-field[name="'+field_name+'"]').remove();
            if(field_val){
              $(selector).closest('form').append('<input type="hidden" name="'+field_name+'" value="'+field_val+'" class="pr-hidden-field">');
            }
          });

          // Also lock currency_rate
          $('input[name="currency_rate"]').prop('readonly', true);
          $('input.pr-hidden-field[name="currency_rate"]').remove();
          var cr_val = $('input[name="currency_rate"]').val() || response.currency_rate;
          $('input[name="currency_rate"]').closest('form').append('<input type="hidden" name="currency_rate" value="'+cr_val+'" class="pr-hidden-field">');

          $('.invoice-item table.invoice-items-table.items tbody').find('.main').remove();
          $('.invoice-item table.invoice-items-table.items tbody').find('.delete-po-item').click();
          $('.invoice-item table.invoice-items-table.items tbody').append(response.list_item);

          // Populate vendor notes from PR description
          if(response.vendornote && response.vendornote != ''){
            if(typeof tinymce !== 'undefined' && tinymce.get('vendornote')){
              tinymce.get('vendornote').setContent(response.vendornote);
            } else {
              $('textarea[name="vendornote"]').val(response.vendornote);
            }
          }

          setTimeout(function () {
            pur_calculate_total();
          }, 15);

          init_selectpicker();
          pur_reorder_items('.invoice-item');
          pur_clear_item_preview_values('.invoice-item');
          $('body').find('#items-warning').remove();
          $("body").find('.dt-loader').remove();
          $('#item_select').selectpicker('val', '');

          // If type is AS, show asset_uid in rows
          if(response.type == 'AS' && response.result){
            $.each(response.result, function(i, item){
              if(item.asset_uid && item.asset_uid != '' && item.asset_uid != null){
                var $row = $('.invoice-item table.invoice-items-table.items tbody tr.item').eq(i);
                $row.find('td.dragger').after('<td class="asset_id_cell">' + item.asset_uid + '<input type="hidden" name="newitems[' + (i+1) + '][asset_uid]" value="' + item.asset_uid + '"></td>');
              }
            });
          }
        }   
    });
  } else {
    // PR cleared - enable all fields, remove hidden inputs, and reset items
    $.each(pr_fields, function(i, selector){
      $(selector).prop('disabled', false);
      $(selector).selectpicker('val', '');
      $(selector).selectpicker('refresh');
    });
    $('.pr-hidden-field').remove();
    // Re-enable currency_rate
    $('input[name="currency_rate"]').prop('readonly', false);
    // Clear items table
    $('.invoice-item table.invoice-items-table.items tbody').find('.item').remove();
    setTimeout(function(){ pur_calculate_total(); }, 15);
  }
}


function client_change(el){
  "use strict";

  var client = $(el).val();
  var data = {};
  data.client = client;
  data.currency = $('select[name="currency"]').val();

  $.post(admin_url + 'purchase/inv_by_client', data).done(function(response){
    response = JSON.parse(response);
    $('select[name="sale_invoice[]"]').html(response.html);
    $('select[name="sale_invoice[]"]').selectpicker('refresh');
  });
  
}

/**
 * { coppy sale invoice }
 */
function coppy_sale_invoice(){
  "use strict";
  var sale_invoice = $('select[name="sale_invoice[]"]').val();
  var data = {};
  data.sale_invoice = sale_invoice;

  if(sale_invoice != ''){
    $.post(admin_url + 'purchase/coppy_sale_invoice_po', data).done(function(response){
        response = JSON.parse(response);

        if(response){ 
          
          $('.invoice-item table.invoice-items-table.items tbody').find('.main').remove();
          $('.invoice-item table.invoice-items-table.items tbody').find('.delete-po-item').click();
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
    $('.invoice-item table.invoice-items-table.items tbody').find('.delete-po-item').click();
    alert_float('warning', '<?php echo _l('please_chose_sale_invoice'); ?>');
  }

}

function pur_calculate_total(from_discount_money){
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
    total_money = 0,
    total_tax_money = 0,
    quantity = 1,
    total_discount_calculated = 0,
    item_total_payment,
    rows = $('.table.has-calculations tbody tr.item'),
    subtotal_area = $('#subtotal'),
    discount_area = $('#discount_area'),
    adjustment = $('input[name="adjustment"]').val(),
    // discount_percent = $('input[name="discount_percent"]').val(),
    discount_percent = 'before_tax',
    discount_fixed = $('input[name="discount_total"]').val(),
    discount_total_type = $('.discount-total-type.selected'),
    discount_type = $('select[name="discount_type"]').val(),
    additional_discount = $('input[name="additional_discount"]').val(),
    add_discount_type = $('select[name="add_discount_type"]').val();

    var shipping_fee = $('input[name="shipping_fee"]').val();
    if(shipping_fee == ''){
      shipping_fee = 0;
      $('input[name="shipping_fee"]').val(0);
    }

  $('.wh-tax-area').remove();

    $.each(rows, function () {
    var item_discount = 0;
    var item_discount_money = 0;
    var item_discount_from_percent = 0;
    var item_discount_percent = 0;
    var item_tax = 0,
        item_amount  = 0;

    quantity = $(this).find('[data-quantity]').val();
    if (quantity === '') {
      quantity = 1;
      $(this).find('[data-quantity]').val(1);
    }
    item_discount_percent = $(this).find('td.discount input').val();
    item_discount_money = $(this).find('td.discount_money input').val();

    if (isNaN(item_discount_percent) || item_discount_percent == '') {
      item_discount_percent = 0;
    }

    if (isNaN(item_discount_money) || item_discount_money == '') {
      item_discount_money = 0;
    }

    if(from_discount_money == 1 && item_discount_money > 0){
      $(this).find('td.discount input').val('');
    }

    _amount = accounting.toFixed($(this).find('td.rate input').val() * quantity, app.options.decimal_places);
    item_amount = _amount;
    _amount = parseFloat(_amount);

    $(this).find('td.into_money').html(format_money(_amount));
    $(this).find('td._into_money input').val(_amount);

    subtotal += _amount;
    row = $(this);
    item_taxes = $(this).find('select.taxes').val();

    if(discount_type == 'after_tax'){
      if (item_taxes) {
        $.each(item_taxes, function (i, taxname) {
          taxrate = row.find('select.taxes [value="' + taxname + '"]').data('taxrate');
          calculated_tax = (_amount / 100 * taxrate);
          item_tax += calculated_tax;
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
              });
      }
    }
    
      //Discount of item
      if( item_discount_percent > 0 && from_discount_money != 1){

        if(discount_type == 'after_tax'){
          item_discount_from_percent = (parseFloat(item_amount) + parseFloat(item_tax) ) * parseFloat(item_discount_percent) / 100;
        }else if(discount_type == 'before_tax'){
          item_discount_from_percent = parseFloat(item_amount) * parseFloat(item_discount_percent) / 100;
        }

        if(item_discount_from_percent != item_discount_money){
          item_discount_money = item_discount_from_percent;
        }
      }

      if( item_discount_money > 0){
        item_discount = parseFloat(item_discount_money);
      }

     
      // Append value to item
      total_discount_calculated += parseFloat(item_discount);
      $(this).find('td.discount_money input').val(item_discount);


      if(discount_type == 'before_tax'){ 
        if (item_taxes) {
          var after_dc_amount = _amount - parseFloat(item_discount);
          $.each(item_taxes, function (i, taxname) {
              taxrate = row.find('select.taxes [value="' + taxname + '"]').data('taxrate');
              calculated_tax = (after_dc_amount / 100 * taxrate);
              item_tax += calculated_tax;
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
          });
        }
      }

      var after_tax = _amount + item_tax;
      var before_tax = _amount;

      item_total_payment = parseFloat(item_amount) + parseFloat(item_tax) - parseFloat(item_discount);

      $(this).find('td.total_after_discount input').val(item_total_payment);

    $(this).find('td.label_total_after_discount').html(format_money(item_total_payment));

    $(this).find('td._total').html(format_money(after_tax));
    $(this).find('td._total_after_tax input').val(after_tax);

    $(this).find('td.tax_value input').val(item_tax);

  });

  var order_discount_percent = $('input[name="order_discount"]').val();  
  var order_discount_percent_val = 0;
  // Discount by percent
  if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'before_tax' && add_discount_type == 'percent') {
    total_discount_calculated += parseFloat((subtotal * order_discount_percent) / 100);
    order_discount_percent_val = (subtotal * order_discount_percent) / 100;
  } else if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'before_tax' && add_discount_type == 'amount') {
    total_discount_calculated += parseFloat(order_discount_percent);
    order_discount_percent_val = order_discount_percent;
  }

  $.each(taxes, function (taxname, total_tax) {
    if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'before_tax' && add_discount_type == 'percent') {
      var total_tax_calculated = (total_tax * order_discount_percent) / 100;
      total_tax = (total_tax - total_tax_calculated);
    } else if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'before_tax' && add_discount_type == 'amount') {
      var t = (order_discount_percent / subtotal) * 100;
      total_tax = (total_tax - (total_tax * t) / 100);
    }

    total += total_tax;
    total_tax_money += total_tax;
    total_tax = format_money(total_tax);
    $('#tax_id_' + slugify(taxname)).html(total_tax);
  });


  total = (total + subtotal);
  total_money = total;
  // Discount by percent

  if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'after_tax' && add_discount_type == 'percent') {
    total_discount_calculated += parseFloat((total * order_discount_percent) / 100);
    order_discount_percent_val = (total * order_discount_percent) / 100;
  } else if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'after_tax' && add_discount_type == 'amount') {
    total_discount_calculated += parseFloat(order_discount_percent);
    order_discount_percent_val = order_discount_percent;
  }

  
  //total_discount_calculated = total_discount_calculated;

  total = parseFloat(total) - parseFloat(total_discount_calculated) - parseFloat(additional_discount);
  adjustment = parseFloat(adjustment);

  // Check if adjustment not empty
  if (!isNaN(adjustment)) {
    total = total + adjustment;
  }

  total+= parseFloat(shipping_fee);

  var discount_html = '-' + format_money(parseFloat(total_discount_calculated)+ parseFloat(additional_discount));
    $('input[name="discount_total"]').val(accounting.toFixed(total_discount_calculated, app.options.decimal_places));
    
  // Append, format to html and display
  $('.shiping_fee').html(format_money(shipping_fee));
  $('.order_discount_value').html(format_money(order_discount_percent_val));
  $('.wh-total_discount').html(discount_html + hidden_input('dc_total', accounting.toFixed(order_discount_percent_val, app.options.decimal_places))  );
  $('.adjustment').html(format_money(adjustment));
  $('.wh-subtotal').html(format_money(subtotal) + hidden_input('total_mn', accounting.toFixed(subtotal, app.options.decimal_places)));
  $('.wh-total').html(format_money(total) + hidden_input('grand_total', accounting.toFixed(total, app.options.decimal_places)));

  $(document).trigger('purchase-quotation-total-calculated');

}


function pur_add_item_to_preview(id) {
  "use strict";

  var currency_rate = $('input[name="currency_rate"]').val();

  requestGetJSON('purchase/get_item_by_id/' + id+'/'+ currency_rate ).done(function (response) {
    clear_item_preview_values();

    $('.main input[name="item_code"]').val(response.itemid);
    $('.main textarea[name="item_name"]').val(response.code_description);
    $('.main textarea[name="description"]').val(response.long_description);
    $('.main input[name="unit_price"]').val(response.purchase_price);
    // Set UOM dropdown (selectpicker) or input
    if($('.main select[name="unit_name"]').length > 0){
      $('.main select[name="unit_name"]').selectpicker('val', response.unit_name);
    } else {
      $('.main input[name="unit_name"]').val(response.unit_name);
    }
    $('.main input[name="unit_id"]').val(response.unit_id);
    $('.main input[name="quantity"]').val(1);

    $('.selectpicker').selectpicker('refresh');


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
      $rateInputPreview.val(response.purchase_price);
    } else {
      var itemCurrencyRate = response['rate_currency_' + selectedCurrency];
      if (!itemCurrencyRate || parseFloat(itemCurrencyRate) === 0) {
        $rateInputPreview.val(response.purchase_price);
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

function pur_add_item_to_table(data, itemid) {
  "use strict";

  data = typeof (data) == 'undefined' || data == 'undefined' ? pur_get_item_preview_values() : data;

  if (data.quantity == "" || data.item_code == "" ) {
    
    return;
  }
  if(parseFloat(data.unit_price) <= 0 || data.unit_price == ''){
    alert_float('warning', '<?php echo _l("unit_price"); ?> must be greater than zero');
    return;
  }
  if(!data.unit_name || data.unit_name.trim() == ''){
    alert_float('warning', 'UOM is required. Please select a unit.');
    return;
  }
  var currency_rate = $('input[name="currency_rate"]').val();
  var to_currency = $('select[name="currency"]').val();
  var table_row = '';
  var item_key = lastAddedItemKey ? lastAddedItemKey += 1 : $("body").find('.invoice-items-table tbody .item').length + 1;
  lastAddedItemKey = item_key;
  $("body").append('<div class="dt-loader"></div>');
  pur_get_item_row_template('newitems[' + item_key + ']',data.item_name, data.description, data.quantity, data.unit_name, data.unit_price, data.taxname, data.item_code, data.unit_id, data.tax_rate, data.discount, itemid, currency_rate, to_currency).done(function(output){
    table_row += output;

    $('.invoice-item table.invoice-items-table.items tbody').append(table_row);

    setTimeout(function () {
      pur_calculate_total();
    }, 15);
    init_selectpicker();
    pur_reorder_items('.invoice-item');
    pur_clear_item_preview_values('.invoice-item');
    // Clear asset ID from the main preview row
    $('.main .asset_id_cell').html('').addClass('hide');
    $('body').find('#items-warning').remove();
    $("body").find('.dt-loader').remove();
        $('#item_select').selectpicker('val', '');

    return true;
  });
  return false;
}

function pur_get_item_preview_values() {
  "use strict";

  var response = {};
  response.item_name = $('.invoice-item .main textarea[name="item_name"]').val();
  response.description = $('.invoice-item .main textarea[name="description"]').val();
  response.quantity = $('.invoice-item .main input[name="quantity"]').val();
  response.unit_name = $('.invoice-item .main input[name="unit_name"]').val();
  if(!response.unit_name || response.unit_name == ''){
    response.unit_name = $('.invoice-item .main select[name="unit_name"]').val();
  }
  response.unit_price = $('.invoice-item .main input[name="unit_price"]').val();
  response.taxname = $('.main select.taxes').selectpicker('val');
  response.item_code = $('.invoice-item .main input[name="item_code"]').val();
  response.unit_id = $('.invoice-item .main input[name="unit_id"]').val();
  response.tax_rate = $('.invoice-item .main input[name="tax_rate"]').val();
  response.discount = $('.invoice-item .main input[name="discount"]').val();


  return response;
}


function pur_clear_item_preview_values(parent) {
  "use strict";

  var previewArea = $(parent + ' .main');
  previewArea.find('input').val('');
  previewArea.find('textarea').val('');
  previewArea.find('select').val('').selectpicker('refresh');
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

function pur_get_item_row_template(name, item_name, description, quantity, unit_name, unit_price, taxname,  item_code, unit_id, tax_rate, discount, item_key, currency_rate, to_currency)  {
  "use strict";

  jQuery.ajaxSetup({
    async: false
  });

  var d = $.post(admin_url + 'purchase/get_purchase_order_row_template', {
    name: name,
    item_name : item_name,
    item_description : description,
    quantity : quantity,
    unit_name : unit_name,
    unit_price : unit_price,
    taxname : taxname,
    item_code : item_code,
    unit_id : unit_id,
    tax_rate : tax_rate,
    discount : discount,
    item_key : item_key,
    currency_rate: currency_rate,
    to_currency: to_currency
  });
  jQuery.ajaxSetup({
    async: true
  });
  return d;
}

// Set the currency for accounting
function init_po_currency(id, callback) {
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

                pur_calculate_total();

                if(callback) {
                    callback();
                }
            });
    }
}

// Show asset_uid in PO grid if type is AS
$(function(){
  <?php if(isset($pur_order) && $pur_order->type == 'AS'){ ?>
  setTimeout(function(){
    // Show asset ID column header
    $('.asset_id_col').removeClass('hide');
    // For each item row, show asset_uid from the response data
    $('.invoice-item table.invoice-items-table.items tbody tr.item').each(function(){
      var $row = $(this);
      var item_id = $row.find('td.dragger input.ids').val();
      if(item_id){
        $.get(admin_url + 'purchase/get_po_detail_asset_uid/' + item_id, function(resp){
          var data = JSON.parse(resp);
          if(data.asset_uid && data.asset_uid != ''){
            $row.find('td.dragger').after('<td class="asset_id_cell">' + data.asset_uid + '</td>');
          }
        });
      }
    });
    // Load asset items in dropdown
    on_request_type_change('AS');
  }, 800);
  <?php } ?>
});

</script>