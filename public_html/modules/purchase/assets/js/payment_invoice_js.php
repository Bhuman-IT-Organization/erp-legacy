<script>


var data_send_mail = {};
  <?php if(isset($send_mail_approve)){ 
    ?>
    data_send_mail = <?php echo json_encode($send_mail_approve); ?>;
    data_send_mail.rel_id = <?php echo pur_html_entity_decode($payment_invoice->id); ?>;
    data_send_mail.rel_type = 'payment_request';
    data_send_mail.addedfrom = <?php echo pur_html_entity_decode($payment_invoice->requester); ?>;
    data_send_mail.csrf_token_name = $('input[id="csrf_token_name"]').val();
    $.post(admin_url+'purchase/send_mail', data_send_mail).done(function(response){
    });
  <?php } ?>
// send_request_approve is defined below (with batch check logic)
$(function(){
  "use strict"; 
   SignaturePad.prototype.toDataURLAndRemoveBlanks = function() {
     var canvas = this._ctx.canvas;
       // First duplicate the canvas to not alter the original
       var croppedCanvas = document.createElement('canvas'),
       croppedCtx = croppedCanvas.getContext('2d');

       croppedCanvas.width = canvas.width;
       croppedCanvas.height = canvas.height;
       croppedCtx.drawImage(canvas, 0, 0);

       // Next do the actual cropping
       var w = croppedCanvas.width,
       h = croppedCanvas.height,
       pix = {
         x: [],
         y: []
       },
       imageData = croppedCtx.getImageData(0, 0, croppedCanvas.width, croppedCanvas.height),
       x, y, index;

       for (y = 0; y < h; y++) {
         for (x = 0; x < w; x++) {
           index = (y * w + x) * 4;
           if (imageData.data[index + 3] > 0) {
             pix.x.push(x);
             pix.y.push(y);

           }
         }
       }
       pix.x.sort(function(a, b) {
         return a - b
       });
       pix.y.sort(function(a, b) {
         return a - b
       });
       var n = pix.x.length - 1;

       w = pix.x[n] - pix.x[0];
       h = pix.y[n] - pix.y[0];
       var cut = croppedCtx.getImageData(pix.x[0], pix.y[0], w, h);

       croppedCanvas.width = w;
       croppedCanvas.height = h;
       croppedCtx.putImageData(cut, 0, 0);

       return croppedCanvas.toDataURL();
     };


     function signaturePadChanged() {

       var input = document.getElementById('signatureInput');
       var $signatureLabel = $('#signatureLabel');
       $signatureLabel.removeClass('text-danger');

       if (signaturePad.isEmpty()) {
         $signatureLabel.addClass('text-danger');
         input.value = '';
         return false;
       }

       $('#signatureInput-error').remove();
       var partBase64 = signaturePad.toDataURLAndRemoveBlanks();
       partBase64 = partBase64.split(',')[1];
       input.value = partBase64;
     }

     var canvas = document.getElementById("signature");
     var signaturePad = new SignaturePad(canvas, {
      maxWidth: 2,
      onEnd:function(){
        signaturePadChanged();
      }
    });

    $('#identityConfirmationForm').submit(function() {
       signaturePadChanged();
     });

    $('input[name="sign_type"]').on('click', function(){

  if($('#sign').is(':checked')){
    $('#sign_pad').removeClass('hide');
    $('#upload_sign').addClass('hide');
  }else if($('#upload').is(':checked')){
    $('#sign_pad').addClass('hide');
    $('#upload_sign').removeClass('hide');
  }

});
    
   });
  
  function signature_clear(){
    "use strict"; 
    var canvas = document.getElementById("signature");
    var signaturePad = new SignaturePad(canvas, {
      maxWidth: 2,
      onEnd:function(){
        //signaturePadChanged();
      }
    });
    signaturePad.clear();
    //signaturePadChanged();
  }
function sign_request(id){
  "use strict";
    

    if($('#upload').is(':checked')){
      if( document.getElementById("sign_attachment_file").files.length == 0 ){
         alert_float('success', '<?php echo _l('please_select_sign_image'); ?>');

         setTimeout(() => {
            $('#sign_button').removeAttr('disabled');
            $('#sign_button').removeClass('disabled');
            $('#sign_button').html('<?php echo _l('e_signature_sign') ?>');
          },"1000");

      }else{
        
        change_request_approval_status(id,2, true);

        $('#sign_attachment-form').submit();

      }
    }else if($('#sign').is(':checked')){

      change_request_approval_status(id,2, true);
    }
}
function approve_request(id){
  "use strict"; 
  // Check if this is a batch payment — show info popup first
  check_batch_before_action(id, 'approve', function(){
    change_request_approval_status(id, 2);
    if( document.getElementById("sign_attachment_file").files.length > 0 ){
      $('#sign_attachment-form').submit();
    }
  });
}
function deny_request(id){
  "use strict"; 
  change_request_approval_status(id,3);
  if( document.getElementById("sign_attachment_file").files.length > 0 ){
    $('#sign_attachment-form').submit();
  }
}
function change_request_approval_status(id, status, sign_code = false){
    var data = {};
    data.rel_id = id;
    data.rel_type = 'payment_request';
    data.approve = status;
    if(sign_code == true){
      data.signature = $('input[name="signature"]').val();

      if($('#sign').is(':checked')){
        data.sign_type = 'sign';
      }else if($('#upload').is(':checked')){
        data.sign_type = 'upload';
      }

    }else{
      data.note = $('textarea[name="reason"]').val();
    }
    $.post(admin_url + 'purchase/approve_request/' + id, data).done(function(response){
       response = JSON.parse(response); 
        if (response.success === true || response.success == 'true') {
            alert_float('success', response.message);

            if(sign_code != true || (sign_code == true && $('#sign').is(':checked'))){
              window.location.reload();
            }

        }
    });
}
function accept_action() {
  "use strict"; 
  $('#add_action').modal('show');
}

// ── Batch payment check — show info popup before approving ───────────────────
function check_batch_before_action(payment_id, action_type, proceed_callback) {
  "use strict";
  $.getJSON(admin_url + 'purchase/get_batch_payment_info/' + payment_id, function(resp){
    if(!resp.is_batch){
      // Not a batch — proceed directly
      proceed_callback();
      return;
    }

    // Build table rows
    var tbody = '';
    $.each(resp.payments, function(i, p){
      var rowClass = p.is_current ? 'info' : '';
      var currentBadge = p.is_current ? ' <span class="label label-primary">Current</span>' : '';
      tbody += '<tr class="' + rowClass + '">';
      tbody += '<td>' + (i+1) + currentBadge + '</td>';
      tbody += '<td><a href="' + p.payment_url + '" target="_blank">' + p.invoice_number + '</a></td>';
      tbody += '<td>' + p.amount + '</td>';
      tbody += '<td>' + p.date + '</td>';
      tbody += '<td>' + p.paymentmode + '</td>';
      tbody += '<td>' + p.status_label + '</td>';
      tbody += '<td><a href="' + p.payment_url + '" target="_blank" class="btn btn-xs btn-default"><i class="fa fa-eye"></i></a></td>';
      tbody += '</tr>';
    });
    $('#batch_payments_tbody').html(tbody);

    // Set notice text
    var noticeText = '';
    if(action_type === 'approve'){
      noticeText = 'This is a batch payment with <strong>' + resp.total_count + '</strong> payment(s) sharing Transaction ID <strong>"' + resp.txn_id + '"</strong>. '
                 + 'Approving this payment will automatically approve all <strong>' + resp.pending_count + '</strong> pending payment(s) in this batch.';
      $('#batch_modal_proceed_btn').show().text('Approve All ' + resp.pending_count + ' Pending Payment(s)');
    } else {
      noticeText = 'This is a batch payment with <strong>' + resp.total_count + '</strong> payment(s) sharing Transaction ID <strong>"' + resp.txn_id + '"</strong>. '
                 + 'Sending for approval will send all <strong>' + resp.pending_count + '</strong> pending payment(s) for approval.';
      $('#batch_modal_proceed_btn').show().text('Send All ' + resp.pending_count + ' for Approval');
    }
    $('#batch_modal_notice_text').html(' ' + noticeText);

    // Wire up proceed button
    $('#batch_modal_proceed_btn').off('click').on('click', function(){
      $('#batch_payment_info_modal').modal('hide');
      proceed_callback();
    });

    $('#batch_payment_info_modal').modal('show');
  }).fail(function(){
    // On AJAX failure — proceed without popup
    proceed_callback();
  });
}

// ── send_request_approve: check batch first ──────────────────────────────────
function send_request_approve(id){
  "use strict";
  check_batch_before_action(id, 'send', function(){
    var data = {};
    data.rel_id = <?php echo pur_html_entity_decode($payment_invoice->id); ?>;
    data.rel_type = 'payment_request';
    data.addedfrom = <?php echo pur_html_entity_decode($payment_invoice->requester); ?>;
    $("body").append('<div class="dt-loader"></div>');
    $.post(admin_url + 'purchase/send_request_approve', data).done(function(response){
      response = JSON.parse(response);
      $("body").find('.dt-loader').remove();
      if (response.success === true || response.success == 'true') {
        alert_float('success', response.message);
        window.location.reload();
      } else {
        alert_float('warning', response.message);
        window.location.reload();
      }
    });
  });
}

// Cheque payment mode show/hide on edit payment form
$(document).on('changed.bs.select change', '#paymentmode', function() {
  "use strict";
  var selectedText = $(this).find('option:selected').text().toLowerCase();
  if (selectedText.indexOf('cheque') !== -1 || selectedText.indexOf('check') !== -1) {
    $('#edit_cheque_details_section').slideDown(200, function(){
      // Re-init datepicker after section becomes visible
      if(typeof init_datepicker === 'function'){ init_datepicker(); }
      else if(typeof appDatepicker === 'function'){ appDatepicker({ element_date: '#cheque_date' }); }
    });
  } else {
    $('#edit_cheque_details_section').slideUp(200);
  }
  // Keep hidden fallback in sync
  $('#paymentmode_hidden_edit').val($(this).val());
});

// Submit button handler — AJAX + FormData (same as add payment, avoids selectpicker issues)
$(document).on('click', '#save_payment_invoice_btn', function(e){
  "use strict";
  e.preventDefault();

  var $btn  = $(this);
  var $form = $btn.closest('form');

  // ── Read paymentmode reliably ─────────────────────────────────────────────
  var $pmSel  = $form.find('select[name="paymentmode"]');
  var paymode = $pmSel.val();
  if(!paymode && $pmSel.length){
    try { paymode = $pmSel.selectpicker('val'); } catch(ex){}
  }
  if(!paymode){
    // Last resort: match selectpicker button text to option
    var btnTxt = $form.find('.bootstrap-select button .filter-option-inner-inner').text().trim();
    $pmSel.find('option').each(function(){
      if($(this).text().trim() === btnTxt){ paymode = $(this).val(); return false; }
    });
  }
  // Final fallback: use the hidden input value
  if(!paymode){ paymode = $('#paymentmode_hidden_edit').val(); }

  // ── Determine if cheque mode ──────────────────────────────────────────────
  var isCheque = false;
  if(paymode){
    var pmTxt = '';
    $pmSel.find('option[value="' + paymode + '"]').each(function(){ pmTxt = $(this).text().toLowerCase(); });
    if(!pmTxt){ pmTxt = $pmSel.find('option:selected').text().toLowerCase(); }
    isCheque = (pmTxt.indexOf('cheque') !== -1 || pmTxt.indexOf('check') !== -1);
  }

  // ── Validate cheque fields ────────────────────────────────────────────────
  if(isCheque){
    var errors = [];
    var bank   = $.trim($form.find('input[name="cheque_bank_name"]').val());
    var number = $.trim($form.find('input[name="cheque_number"]').val());
    var cdate  = $.trim($form.find('input[name="cheque_date"]').val());
    if(!bank)   errors.push('<?php echo _l('cheque_bank_name'); ?>');
    if(!number) errors.push('<?php echo _l('cheque_number'); ?>');
    if(!cdate)  errors.push('<?php echo _l('cheque_date'); ?>');
    if(errors.length > 0){
      $('#edit_cheque_details_section').css({'border-color':'#e74c3c','background':'#fff5f5'});
      setTimeout(function(){
        $('#edit_cheque_details_section').css({'border-color':'#ddd','background':'#f9f9f9'});
      }, 3000);
      alert_float('danger', '<?php echo _l('required'); ?>: ' + errors.join(', '));
      return false;
    }
  }

  // ── Build FormData — capture every field explicitly ───────────────────────
  var fd = new FormData();

  // All hidden inputs (CSRF token etc.)
  $form.find('input[type="hidden"]').each(function(){
    var n = $(this).attr('name');
    if(n) fd.append(n, $(this).val());
  });

  // Core fields
  fd.append('amount',        $.trim($form.find('input[name="amount"]').val()));
  fd.append('date',          $.trim($form.find('input[name="date"]').val()));
  fd.append('paymentmode',   paymode || '');
  fd.append('transactionid', $.trim($form.find('input[name="transactionid"]').val()));
  fd.append('note',          $form.find('textarea[name="note"]').val() || '');

  // Cheque fields — always send when cheque mode
  if(isCheque){
    fd.append('cheque_bank_name', $.trim($form.find('input[name="cheque_bank_name"]').val()));
    fd.append('cheque_number',    $.trim($form.find('input[name="cheque_number"]').val()));
    fd.append('cheque_date',      $.trim($form.find('input[name="cheque_date"]').val()));
    // File attachment (optional)
    var fileInput = $form.find('input[type="file"][name="cheque_attachment"]')[0];
    if(fileInput && fileInput.files.length > 0){
      fd.append('cheque_attachment', fileInput.files[0]);
    }
    // Remove attachment checkbox
    var removeChk = $form.find('input[name="remove_cheque_attachment"]');
    if(removeChk.length && removeChk.is(':checked')){
      fd.append('remove_cheque_attachment', '1');
    }
  }

  // ── Submit via AJAX ───────────────────────────────────────────────────────
  $btn.prop('disabled', true).text('<?php echo _l('wait_text'); ?>');
  $(window).off('beforeunload');

  $.ajax({
    url:         $form.attr('action'),
    type:        'POST',
    data:        fd,
    processData: false,
    contentType: false,
    success: function(){
      // Controller redirects on success — reload to show updated data
      alert_float('success', '<?php echo _l('updated_successfully', _l('payment')); ?>');
      setTimeout(function(){ window.location.reload(); }, 600);
    },
    error: function(xhr){
      $btn.prop('disabled', false).text('<?php echo _l('submit'); ?>');
      alert_float('danger', 'Error saving payment (HTTP ' + xhr.status + '). Please try again.');
    }
  });
});
</script> 