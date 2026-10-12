<script>
var signaturePad;
var croppedCtx;

  (function($) {
    "use strict";

  var data_send_mail = {};
  <?php if(isset($send_mail_approve)){ 
    ?>
    data_send_mail = <?php echo json_encode($send_mail_approve); ?>;
    data_send_mail.rel_id = <?php echo new_html_entity_decode($goods_receipt->id); ?>;
    data_send_mail.rel_type = '1';
    
    data_send_mail.addedfrom = <?php echo new_html_entity_decode($goods_receipt->addedfrom); ?>;

    $.get(admin_url+'warehouse/send_mail', data_send_mail).done(function(response){
      response = JSON.parse(response);

    }).fail(function(error) {

    });
  <?php } ?>

   SignaturePad.prototype.toDataURLAndRemoveBlanks = function() {
     var canvas = this._ctx.canvas;
       // First duplicate the canvas to not alter the original
       var croppedCanvas = document.createElement('canvas');
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

      var canvas = document.getElementById("signature");
      signaturePad = new SignaturePad(canvas, {
      maxWidth: 2,
      onEnd:function(){
        signaturePadChanged();
      }
    });

    $('#identityConfirmationForm').submit(function() {
       signaturePadChanged();
     });
    

  })(jQuery);


     function signaturePadChanged() {
      "use strict";

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

  
  function signature_clear(){
    "use strict";
    var canvas = document.getElementById("signature");
    var signaturePad = new SignaturePad(canvas, {
      maxWidth: 2,
      onEnd:function(){
        
      }
    });
    signaturePad.clear();
    $('input[name="signature"]').val('');
    
  }

  function sign_request(id){
    "use strict";
    var signature_val = $('input[name="signature"]').val();
    if(signature_val.length > 0){
      change_request_approval_status(id,1, true);
      $('.sign_request_class').prop('disabled', true);
      $('.sign_request_class').html('<?php echo _l('wait_text'); ?>');
      $('.clear').prop('disabled', true);
    }else{
      alert_float('warning', '<?php echo _l('please_sign_the_form'); ?>');
      $('.sign_request_class').prop('disabled', false);
      $('.clear').prop('disabled', false);
    }
  }
  function approve_request(id){
    "use strict";
    change_request_approval_status(id,1);
  }
  function deny_request(id){
    "use strict";
      change_request_approval_status(id,-1);
  }

  function change_request_approval_status(id, status, sign_code){
    "use strict";

      var data = {};
      data.rel_id = id;
      data.rel_type = '1';
      
      data.approve = status;
      if(sign_code == true){
        data.signature = $('input[name="signature"]').val();
      }else{
        data.note = $('textarea[name="reason"]').val();
      }
      $.post(admin_url + 'warehouse/approve_request/' + id, data).done(function(response){
          response = JSON.parse(response); 
          if (response.success === true || response.success == 'true') {
              alert_float('success', response.message);
              window.location.reload();
          }
      });
  }

  function send_request_approve(id){
    "use strict";
      var data = {};
      data.rel_id = <?php echo new_html_entity_decode($goods_receipt->id); ?>;
      data.rel_type = '1';
     
      data.addedfrom = <?php echo new_html_entity_decode($goods_receipt->addedfrom); ?>;
    $("body").append('<div class="dt-loader"></div>');
      $.post(admin_url + 'warehouse/send_request_approve', data).done(function(response){
          response = JSON.parse(response);
          $("body").find('.dt-loader').remove();
          if (response.success === true || response.success == 'true') {
              alert_float('success', response.message);
              window.location.reload();
          }else{
            alert_float('warning', response.message);
              window.location.reload();
          }
      });
  }

function accept_action() {
  "use strict";
      $('#add_action').modal('show');
    }

 function send_goods_received(id) {
  "use strict"; 
  $('#additional_goods_received').html('');
  $('#additional_goods_received').append(hidden_input('po_id',id));
  $('#send_goods_received').modal('show');
 }

function preview_grn_attachment(invoker){
  "use strict";
  var rel_id = $(invoker).attr('rel_id');
  var file_name = $(invoker).attr('file_name');
  var file_type = $(invoker).attr('file_type');
  var file_url = site_url + 'modules/warehouse/uploadsgoods_receipt/' + rel_id + '/' + file_name;
  
  var preview_html = '<div class="modal fade _project_file" tabindex="-1" role="dialog"><div class="modal-dialog full-screen-modal" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h4 class="modal-title">' + file_name + '</h4></div><div class="modal-body">';
  
  var ext = file_name.split('.').pop().toLowerCase();
  if(['jpg','jpeg','png','gif','bmp','webp'].indexOf(ext) !== -1){
    preview_html += '<img src="' + file_url + '" class="img img-responsive">';
  } else if(ext == 'pdf'){
    preview_html += '<iframe src="' + file_url + '" height="100%" width="100%" style="min-height:500px;" frameborder="0"></iframe>';
  } else if(['xls','xlsx','doc','docx'].indexOf(ext) !== -1){
    preview_html += '<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(file_url) + '" width="100%" height="100%" style="min-height:500px;" frameborder="0"></iframe>';
  } else {
    preview_html += '<p class="text-muted">No preview available. <a href="' + file_url + '" download>Download file</a></p>';
  }
  
  preview_html += '</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l("close"); ?></button></div></div></div></div>';
  
  $('#grn_file_preview_data').html(preview_html);
  $('#grn_file_preview_data .modal').modal('show');
}

</script>

