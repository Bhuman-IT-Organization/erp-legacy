<script>
$(function(){
  "use strict";

  // ── Send mail on approve (if session data present) ──────────────────────
  <?php if(isset($send_mail_approve)){ ?>
  var data_send_mail = <?php echo json_encode($send_mail_approve); ?>;
  data_send_mail.rel_id   = <?php echo pur_html_entity_decode($pur_invoice->id); ?>;
  data_send_mail.rel_type = 'purchase_invoice';
  data_send_mail.addedfrom = <?php echo ($pur_invoice->add_from_type == 'admin') ? pur_html_entity_decode($pur_invoice->add_from) : '""'; ?>;
  $.post(admin_url + 'purchase/send_mail', data_send_mail);
  <?php } ?>

  // ── Sign-type radio toggle ───────────────────────────────────────────────
  $('input[name="sign_type"]').on('click', function(){
    if ($('#sign').is(':checked')) {
      $('#sign_pad').removeClass('hide');
      $('#upload_sign').addClass('hide');
    } else if ($('#upload').is(':checked')) {
      $('#sign_pad').addClass('hide');
      $('#upload_sign').removeClass('hide');
    }
  });

  // ── SignaturePad (only if canvas exists) ─────────────────────────────────
  var canvas = document.getElementById('signature');
  if (canvas && typeof SignaturePad !== 'undefined') {

    SignaturePad.prototype.toDataURLAndRemoveBlanks = function() {
      var c = this._ctx.canvas;
      var cc = document.createElement('canvas'), cx = cc.getContext('2d');
      cc.width = c.width; cc.height = c.height;
      cx.drawImage(c, 0, 0);
      var w = cc.width, h = cc.height,
          pix = {x:[], y:[]},
          img = cx.getImageData(0, 0, w, h), x, y, idx;
      for (y = 0; y < h; y++) {
        for (x = 0; x < w; x++) {
          idx = (y * w + x) * 4;
          if (img.data[idx + 3] > 0) { pix.x.push(x); pix.y.push(y); }
        }
      }
      pix.x.sort(function(a,b){return a-b;});
      pix.y.sort(function(a,b){return a-b;});
      var n = pix.x.length - 1;
      w = pix.x[n] - pix.x[0]; h = pix.y[n] - pix.y[0];
      var cut = cx.getImageData(pix.x[0], pix.y[0], w, h);
      cc.width = w; cc.height = h; cx.putImageData(cut, 0, 0);
      return cc.toDataURL();
    };

    window.signaturePad = new SignaturePad(canvas, {
      maxWidth: 2,
      onEnd: function(){ signaturePadChanged(); }
    });
  }

  // ── Cheque fields: show/hide on payment mode change ──────────────────────
  // selectpicker fires 'changed.bs.select'; also bind native 'change' as fallback
  // The select element id is always the $name param = 'paymentmode'
  $(document).on('changed.bs.select change', '#paymentmode', function(){
    var modal = $(this).closest('#payment_record_pur');
    if(modal.length === 0) return; // only act inside payment modal
    toggleChequeFields(this);
  });

  // ── Reset cheque section when payment modal opens ────────────────────────
  $('#payment_record_pur').on('show.bs.modal', function(){
    $('#cheque_details_section').hide();
    $('#cheque_details_section input').val('');
    $('#cheque_details_section input[type="file"]').val('');
    setTimeout(function(){ $('#paymentmode').selectpicker('refresh'); }, 150);
    // Mark form as clean so are-you-sure doesn't fire when modal closes
    setTimeout(function(){
      $('#purinvoice-add_payment-form').trigger('reinitialize.areYouSure');
    }, 200);
  });

});

// ── Signature helpers ────────────────────────────────────────────────────────
function signaturePadChanged() {
  "use strict";
  var input = document.getElementById('signatureInput');
  var $lbl  = $('#signatureLabel');
  $lbl.removeClass('text-danger');
  if (!window.signaturePad || window.signaturePad.isEmpty()) {
    $lbl.addClass('text-danger');
    if (input) input.value = '';
    return false;
  }
  $('#signatureInput-error').remove();
  var b64 = window.signaturePad.toDataURLAndRemoveBlanks().split(',')[1];
  if (input) input.value = b64;
}

function signature_clear() {
  "use strict";
  if (window.signaturePad) { window.signaturePad.clear(); }
}

// ── Approval actions ─────────────────────────────────────────────────────────
function send_request_approve(id) {
  "use strict";
  var data = {
    rel_id   : <?php echo pur_html_entity_decode($pur_invoice->id); ?>,
    rel_type : 'purchase_invoice',
    addedfrom: <?php echo ($pur_invoice->add_from_type == 'admin') ? pur_html_entity_decode($pur_invoice->add_from) : '""'; ?>
  };
  $('body').append('<div class="dt-loader"></div>');
  $.post(admin_url + 'purchase/send_request_approve', data).done(function(response){
    response = JSON.parse(response);
    $('body').find('.dt-loader').remove();
    if (response.success === true || response.success == 'true') {
      alert_float('success', response.message);
    } else {
      alert_float('warning', response.message);
    }
    window.location.reload();
  });
}

function approve_request(id) {
  "use strict";
  change_request_approval_status(id, 2);
  if (document.getElementById('sign_attachment_file') &&
      document.getElementById('sign_attachment_file').files.length > 0) {
    $('#sign_attachment-form').submit();
  }
}

function deny_request(id) {
  "use strict";
  change_request_approval_status(id, 3);
  if (document.getElementById('sign_attachment_file') &&
      document.getElementById('sign_attachment_file').files.length > 0) {
    $('#sign_attachment-form').submit();
  }
}

function change_request_approval_status(id, status, sign_code) {
  "use strict";
  var data = { rel_id: id, rel_type: 'purchase_invoice', approve: status };
  if (sign_code === true) {
    data.signature = $('input[name="signature"]').val();
    data.sign_type = $('#sign').is(':checked') ? 'sign' : 'upload';
  } else {
    data.note = $('textarea[name="pur_reason"]').val();
  }
  $.post(admin_url + 'purchase/approve_request/' + id, data).done(function(response){
    response = JSON.parse(response);
    if (response.success === true || response.success == 'true') {
      alert_float('success', response.message);
      if (sign_code !== true || (sign_code === true && $('#sign').is(':checked'))) {
        window.location.reload();
      }
    }
  });
}

function accept_action() {
  "use strict";
  $('#add_action').modal('show');
}

function sign_request(id) {
  "use strict";
  if ($('#upload').is(':checked')) {
    if (!document.getElementById('sign_attachment_file') ||
        document.getElementById('sign_attachment_file').files.length === 0) {
      alert_float('warning', '<?php echo _l('please_select_sign_image'); ?>');
      setTimeout(function(){
        $('#sign_button').removeAttr('disabled').removeClass('disabled')
          .html('<?php echo _l('e_signature_sign'); ?>');
      }, 1000);
    } else {
      change_request_approval_status(id, 2, true);
      $('#sign_attachment-form').submit();
    }
  } else if ($('#sign').is(':checked')) {
    change_request_approval_status(id, 2, true);
  }
}

// ── Payment modal ────────────────────────────────────────────────────────────
function add_payment(id) {
  "use strict";
  $('#payment_record_pur').modal('show');
  $('.edit-title').addClass('hide');
  $('.add-title').removeClass('hide');
  $('#additional').html('');
}

// ── Delete payment with batch check ─────────────────────────────────────────
function confirm_delete_payment(payment_id, invoice_id) {
  "use strict";
  $.getJSON(admin_url + 'purchase/check_payment_batch/' + payment_id, function(resp){
    if(resp.has_batch && resp.count > 0){
      // Batch payment — show count and confirm
      var msg = 'This payment is part of a batch payment (Transaction ID: ' + resp.transaction_id + ').\n'
              + resp.count + ' payment(s) share the same Transaction ID.\n\n'
              + 'All ' + resp.count + ' batch payment(s) will be deleted. Continue?';
      if(confirm(msg)){
        window.location.href = admin_url + 'purchase/delete_payment_pur_invoice/' + payment_id + '/' + invoice_id;
      }
    } else {
      // Single payment — standard confirm
      if(confirm_delete()){
        window.location.href = admin_url + 'purchase/delete_payment_pur_invoice/' + payment_id + '/' + invoice_id;
      }
    }
  }).fail(function(){
    // Fallback
    if(confirm_delete()){
      window.location.href = admin_url + 'purchase/delete_payment_pur_invoice/' + payment_id + '/' + invoice_id;
    }
  });
}

// ── Cheque fields toggle ─────────────────────────────────────────────────────
function toggleChequeFields(selectEl) {
  "use strict";
  var $sel = $(selectEl);
  var selectedVal = $sel.val();
  var txt = '';
  if(selectedVal){
    // Try to get text from the native select option
    var $opt = $sel.find('option[value="' + selectedVal + '"]');
    if($opt.length){
      txt = $opt.text().toLowerCase();
    }
  }
  if (txt.indexOf('cheque') !== -1 || txt.indexOf('check') !== -1) {
    $('#cheque_details_section').slideDown(200, function(){
      // Re-init datepicker after section becomes visible so calendar works
      if(typeof init_datepicker === 'function'){
        init_datepicker();
      } else if(typeof appDatepicker === 'function'){
        appDatepicker({ element_date: '#cheque_date' });
      }
      if(typeof init_selectpicker === 'function'){ init_selectpicker(); }
    });
  } else {
    $('#cheque_details_section').slideUp(200);
    $('#cheque_details_section input[type="text"]').val('');
    $('#cheque_details_section input[type="file"]').val('');
  }
}

// ── Single payment modal submit button — validate then submit via AJAX ───────
$(document).on('click', '#submit_payment_modal_btn', function(e){
  "use strict";
  e.preventDefault();
  e.stopImmediatePropagation();

  var $btn  = $(this);
  var $form = $('#purinvoice-add_payment-form');

  // ── Read values directly (don't rely on native form submit) ──────────────
  var amount  = $.trim($form.find('input[name="amount"]').val());
  var date    = $.trim($form.find('input[name="date"]').val());

  // Read paymentmode — try native select first, then selectpicker API
  var $pmSel  = $form.find('select[name="paymentmode"]');
  var paymode = $pmSel.val();
  if(!paymode && $pmSel.length){
    try { paymode = $pmSel.selectpicker('val'); } catch(ex){}
  }
  // Last resort: read from the selectpicker button text to find matching option
  if(!paymode){
    var btnTxt = $form.find('.bootstrap-select button .filter-option-inner-inner').text().trim();
    $pmSel.find('option').each(function(){
      if($(this).text().trim() === btnTxt){ paymode = $(this).val(); return false; }
    });
  }

  // ── Basic required validation ─────────────────────────────────────────────
  var gl_acc = $form.find('select[name="gl_account"]').val();
  if(!gl_acc){ try { gl_acc = $form.find('select[name="gl_account"]').selectpicker('val'); } catch(ex){} }
  
  if(!amount || !date || !paymode || !gl_acc){
    var missing = [];
    if(!amount)  missing.push('<?php echo _l('amount'); ?>');
    if(!date)    missing.push('<?php echo _l('date'); ?>');
    if(!paymode) missing.push('<?php echo _l('payment_mode'); ?>');
    if(!gl_acc)  missing.push('GL Account');
    alert_float('danger', missing.join(', ') + ' <?php echo _l('is_required'); ?>');
    return false;
  }

  // ── Sufficient funds on the selected GL account ───────────────────────────
  // Uses the balance already fetched into the hint below the GL select. Blocks
  // here for immediate feedback; the server re-checks before saving.
  var glApplies = $('#gl_balance_hint').data('applies');
  var glBalance = parseFloat($('#gl_balance_hint').data('balance'));
  if(glApplies === true && !isNaN(glBalance)){
    var payAmt = parseFloat(amount) || 0;
    if(glBalance <= 0 || glBalance < payAmt){
      alert_float('danger', '<?php echo _l('gl_account_insufficient_balance_short'); ?>' +
        ' (' + $('#gl_balance_hint').data('name') + ': ' + glBalance.toFixed(2) + ')');
      $('#gl_balance_hint').css('color', '#e74c3c');
      return false;
    }
  }

  // ── Cheque validation ─────────────────────────────────────────────────────
  var isCheque = false;
  $pmSel.find('option[value="' + paymode + '"]').each(function(){
    var t = $(this).text().toLowerCase();
    if(t.indexOf('cheque') !== -1 || t.indexOf('check') !== -1){ isCheque = true; }
  });

  var bank   = $.trim($form.find('input[name="cheque_bank_name"]').val());
  var number = $.trim($form.find('input[name="cheque_number"]').val());
  var cdate  = $.trim($form.find('input[name="cheque_date"]').val());

  if(isCheque){
    var errors = [];
    if(!bank)   errors.push('<?php echo _l('cheque_bank_name'); ?>');
    if(!number) errors.push('<?php echo _l('cheque_number'); ?>');
    if(!cdate)  errors.push('<?php echo _l('cheque_date'); ?>');
    if(errors.length > 0){
      $('#cheque_details_section').css({'border-color':'#e74c3c','background':'#fff5f5'});
      setTimeout(function(){ $('#cheque_details_section').css({'border-color':'#ddd','background':'#f9f9f9'}); }, 3000);
      alert_float('danger', '<?php echo _l('required'); ?>: ' + errors.join(', '));
      return false;
    }
  }

  // ── Build FormData manually so every field is captured correctly ──────────
  var fd = new FormData();

  // CSRF token (CodeIgniter requires this)
  var csrfName  = $('meta[name="csrf-token-name"]').attr('content')  || $form.find('input[name^="csrf"]').attr('name');
  var csrfValue = $('meta[name="csrf-token-value"]').attr('content') || $form.find('input[name^="csrf"]').val();
  if(csrfName && csrfValue){ fd.append(csrfName, csrfValue); }
  // Also append all hidden inputs from the form (catches CI csrf hidden field)
  $form.find('input[type="hidden"]').each(function(){
    fd.append($(this).attr('name'), $(this).val());
  });

  fd.append('amount',      amount);
  fd.append('date',        date);
  fd.append('paymentmode', paymode);
  fd.append('gl_account',  gl_acc);
  fd.append('transactionid', $.trim($form.find('input[name="transactionid"]').val()));
  fd.append('note',        $form.find('textarea[name="note"]').val() || '');

  // ── Debit note ────────────────────────────────────────────────────────────
  // This is a <select>, so the hidden-input sweep above does not pick it up.
  // Without appending it explicitly the id never reaches the server and the
  // debit note is silently not applied - the amount box just looks reduced.
  var $dnSel = $form.find('select[name="debit_note_id"]');
  var dnId   = $dnSel.val();
  if(!dnId && $dnSel.length){
    try { dnId = $dnSel.selectpicker('val'); } catch(ex){}
  }
  if(dnId){
    fd.append('debit_note_id', dnId);
    // Amount to apply = the debit note's remaining balance, capped server-side
    // against the invoice balance.
    var dnAmt = parseFloat($dnSel.find('option:selected').data('amount')) || 0;
    fd.append('debit_note_amount', dnAmt.toFixed(2));
  }

  if(isCheque){
    fd.append('cheque_bank_name', bank);
    fd.append('cheque_number',    number);
    fd.append('cheque_date',      cdate);
    var fileInput = document.getElementById('cheque_attachment_file');
    if(fileInput && fileInput.files.length > 0){
      fd.append('cheque_attachment', fileInput.files[0]);
    }
  }

  // ── Disable button and submit ─────────────────────────────────────────────
  $btn.prop('disabled', true).text('<?php echo _l('wait_text'); ?>');

  $.ajax({
    url:         $form.attr('action'),
    type:        'POST',
    data:        fd,
    processData: false,
    contentType: false,
    success: function(response){
      try {
        var res = (typeof response === 'string') ? JSON.parse(response) : response;
        if(res.success === true || res.success === 'true'){
          alert_float('success', res.message || '<?php echo _l('added_successfully', _l('payment')); ?>');
          setTimeout(function(){ window.location.reload(); }, 800);
        } else {
          $btn.prop('disabled', false).text('<?php echo _l('submit'); ?>');
          alert_float('danger', res.message || 'Failed to save payment.');
        }
      } catch(ex){
        // Controller returned a redirect (HTML) — that means success
        alert_float('success', '<?php echo _l('added_successfully', _l('payment')); ?>');
        setTimeout(function(){ window.location.reload(); }, 800);
      }
    },
    error: function(xhr){
      $btn.prop('disabled', false).text('<?php echo _l('submit'); ?>');
      alert_float('danger', 'Error saving payment (HTTP ' + xhr.status + '). Please try again.');
    }
  });
});

// ── GL account available balance ─────────────────────────────────────────────
// Shows the balance under the GL Account picker and stores it on the hint element
// so the submit handler can block an over-payment. Only asset accounts report
// applies=true; liability accounts (e.g. Post Dated Cheque) are never blocked.
function pi_load_gl_balance(){
  "use strict";

  var $hint = $('#gl_balance_hint');
  if(!$hint.length){ return; }

  var $sel = $('#purinvoice-add_payment-form').find('select[name="gl_account"]');
  var acc  = $sel.val();
  if(!acc){ try { acc = $sel.selectpicker('val'); } catch(ex){} }

  $hint.css('color', '').data('applies', '').data('balance', '').data('name', '').text('');

  if(!acc){ return; }

  $.getJSON(admin_url + 'purchase/gl_account_balance/' + acc, function(res){
    if(!res || !res.found){ return; }

    $hint.data('applies', res.applies === true || res.applies === 'true')
         .data('balance', res.balance)
         .data('name', res.name);

    if(res.applies === true || res.applies === 'true'){
      var bal = parseFloat(res.balance) || 0;
      $hint.text('<?php echo _l('available_balance'); ?>: ' + res.balance_money);
      $hint.css('color', bal <= 0 ? '#e74c3c' : '#777');
    } else {
      // Not a fund account - balance is informational only
      $hint.text('');
    }
  }).fail(function(){
    $hint.text('');
  });
}

$(document).on('changed.bs.select.glbal change.glbal', '#purinvoice-add_payment-form select[name="gl_account"]', function(){
  pi_load_gl_balance();
});

$(document).on('shown.bs.modal', '#payment_record_pur', function(){
  pi_load_gl_balance();
});
</script>
