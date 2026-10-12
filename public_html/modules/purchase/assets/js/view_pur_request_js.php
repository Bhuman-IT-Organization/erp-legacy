<script>
(function($) {
"use strict"; 
var data_send_mail = {};
  <?php if(isset($send_mail_approve)){ 
    ?>
    data_send_mail = <?php echo json_encode($send_mail_approve); ?>;
    data_send_mail.rel_id = <?php echo pur_html_entity_decode($pur_request->id); ?>;
    data_send_mail.rel_type = 'pur_request';
    data_send_mail.addedfrom = <?php echo pur_html_entity_decode($pur_request->requester); ?>;
    data_send_mail.csrf_token_name = $('input[id="csrf_token_name"]').val();
    $.post(admin_url+'purchase/send_mail', data_send_mail).done(function(response){
    });
  <?php } ?>


$('input[name="sign_type"]').on('click', function(){

    if($('#sign').is(':checked')){
      $('#sign_pad').removeClass('hide');
      $('#upload_sign').addClass('hide');
    }else if($('#upload').is(':checked')){
      $('#sign_pad').addClass('hide');
      $('#upload_sign').removeClass('hide');
    }

  });

// ── AJAX Attachment Upload ─────────────────────────────────────────────────
$('#partograph-attachments-upload').on('submit', function(e){
  "use strict";
  e.preventDefault();

  var $form   = $(this);
  var $btn    = $('#obgy_btn2');
  var fileInput = document.getElementById('pur_file_input');

  if(!fileInput || fileInput.files.length === 0){
    alert_float('warning', '<?php echo _l('pur_file'); ?>');
    return false;
  }

  var formData = new FormData();
  formData.append('file', fileInput.files[0]);
  formData.append('<?php echo $this->security->get_csrf_token_name(); ?>', $('input[id="csrf_token_name"]').val());

  $btn.prop('disabled', true).text('<?php echo _l('wait_text'); ?>');

  $.ajax({
    url: admin_url + 'purchase/purchase_request_attachment/<?php echo pur_html_entity_decode($pur_request->id); ?>',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    success: function(resp){
      try { resp = (typeof resp === 'string') ? JSON.parse(resp) : resp; } catch(ex){}

      if(resp && resp.success){
        alert_float('success', '<?php echo _l('uploaded_successfully'); ?>');
        // Refresh page to show the new attachment
        setTimeout(function(){ window.location.reload(); }, 500);
      } else {
        alert_float('danger', '<?php echo _l('something_went_wrong'); ?>');
      }
    },
    error: function(){
      alert_float('danger', '<?php echo _l('something_went_wrong'); ?>');
    },
    complete: function(){
      $btn.prop('disabled', false).text('<?php echo _l('submit'); ?>');
    }
  });
});

function rebuild_attachment_list(attachments){
  "use strict";
  var $container = $('#purrequest_pv_file');
  // Remove old entries (keep the hr if needed)
  $container.find('.mbot15.row.inline-block.full-width').remove();
  $container.find('hr').remove();
  $container.find('.text-muted.text-center.mtop15').remove();

  var currentUserId = <?php echo (int)get_staff_user_id(); ?>;
  var isAdmin       = <?php echo is_admin() ? 'true' : 'false'; ?>;

  if(!attachments || attachments.length === 0){
    $container.prepend('<p class="text-muted text-center mtop15"><?php echo _l('no_attachments_found'); ?></p>');
    return;
  }

  var html = '<hr />';
  $.each(attachments, function(i, f){
    var hrefUrl = site_url + '<?php echo PURCHASE_PATH; ?>pur_request/' + f.rel_id + '/' + f.file_name;

    html += '<div class="mbot15 row inline-block full-width" data-attachment-id="' + f.id + '">';
    html += '<div class="col-md-6">';
    html += '<a name="preview-purorder-btn" onclick="preview_purrequest_btn(this); return false;" rel_id="' + f.rel_id + '" id="' + f.id + '" href="Javascript:void(0);" class="mbot10 mright5 btn btn-success pull-left" data-toggle="tooltip" title="<?php echo _l('preview_file'); ?>"><i class="fa fa-eye"></i></a>';
    html += '<div class="pull-left"><i class="fa fa-file"></i></div>';
    html += '<a href=" ' + hrefUrl + '" target="_blank" download>' + f.file_name + '</a>';
    html += '<br /><small class="text-muted">' + (f.filetype || '') + '</small>';
    html += '</div>';
    html += '<div class="col-md-4">';
    html += '<small class="text-muted"><i class="fa fa-user"></i> ' + (f.staffid_name || '') + '</small>';
    html += '<br /><small class="text-muted"><i class="fa fa-calendar"></i> ' + (f.dateadded || '') + '</small>';
    html += '</div>';
    html += '<div class="col-md-2 text-right">';
    if(parseInt(f.staffid) === currentUserId || isAdmin){
      html += '<a href="#" class="text-danger" onclick="delete_purrequest_attachment(' + f.id + '); return false;" data-toggle="tooltip" title="<?php echo _l('delete'); ?>"><i class="fa fa-times"></i></a>';
    }
    html += '</div></div>';
  });

  $container.prepend(html);
}

})(jQuery); 


function change_status_pur_request(invoker,id){
  "use strict"; 
  $.post(admin_url+'purchase/change_status_pur_request/'+invoker.value+'/'+id).done(function(reponse){
    reponse = JSON.parse(reponse);
    window.location.href = admin_url + 'purchase/view_pur_request/'+id;
    alert_float('success',reponse.result);
  });
}

function send_request_approve(id){
  "use strict"; 
    <?php if((float)$pur_request->total > 5000){ ?>
    // Check vendor recommendations mandatory for value > 5000
    $.get(admin_url + 'purchase/check_vendor_recommendations/' + <?php echo pur_html_entity_decode($pur_request->id); ?>, function(resp){
      var result = JSON.parse(resp);
      if(!result.has_recommendations){
        alert_float('warning', '<?php echo _l("vendor_recommendations_required"); ?>');
        $("body").find('.dt-loader').remove();
        // Switch to vendor recommendations tab
        $('a[href="#vendor_recommendations"]').tab('show');
        return false;
      } else {
        proceed_send_request_approve();
      }
    });
    <?php } else { ?>
    proceed_send_request_approve();
    <?php } ?>
}

function proceed_send_request_approve(){
  "use strict";
    var data = {};
    data.rel_id = <?php echo pur_html_entity_decode($pur_request->id); ?>;
    data.rel_type = 'pur_request';
    data.addedfrom = <?php echo pur_html_entity_decode($pur_request->requester); ?>;
  $("body").append('<div class="dt-loader"></div>');
    $.post(admin_url + 'purchase/send_request_approve', data).done(function(response){
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
  change_request_approval_status(id,2);


  if( document.getElementById("sign_attachment_file").files.length > 0 ){
    $('#sign_attachment-form').submit();
  }
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
    data.rel_type = 'pur_request';
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

function copy_public_link(pur_order){
  "use strict";
  var link = $('#link_public').val();
  if(link != ''){
    var copyText = document.getElementById("link_public");
    copyText.select();
    copyText.setSelectionRange(0, 99999)
    document.execCommand("copy");
    alert_float('success','<?php echo _l('pur_copied'); ?>');

  }else{
    $.post(admin_url+'purchase/copy_public_link_pur_request/'+pur_order).done(function(reponse){
      reponse = JSON.parse(reponse);
      if(reponse.copylink != ''){
        $('#link_public').val(reponse.copylink);
        
      }

      if($('#link_public').val() == reponse.copylink){
          var copyText = document.getElementById("link_public");
          copyText.select();
          copyText.setSelectionRange(0, 99999)
          document.execCommand("copy");
          alert_float('success','Created!');
        }
    });
  }
}  

// Mark task status
function purchase_request_mark_as(status, pur_request_id) {
    var url = 'purchase/change_status_pur_request/' + status + '/' + pur_request_id;

    $("body").append('<div class="dt-loader"></div>');
    requestGetJSON(url).done(function (response) {
        $("body").find('.dt-loader').remove();
        if (response.result != '') {
            alert_float('success',response.result);
            window.location.reload();
        }
    });
}

if(typeof(commonTaskPopoverMenuOptions) == 'undefined') {
      var commonTaskPopoverMenuOptions = {
         html: true,
         placement: 'bottom',
         trigger: 'click',
         template: '<div class="popover"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"></div></div></div>',
      };
   }

if(typeof(taskPopoverMenus) == 'undefined'){
    var taskPopoverMenus = [{
          selector: '.task-menu-options',
          title: "<?php echo _l('actions'); ?>",
       },
       {
          selector: '.task-menu-status',
          title: "<?php echo _l('ticket_single_change_status'); ?>",
       },
       {
          selector: '.task-menu-priority',
          title: "<?php echo _l('task_single_priority'); ?>",
       },
       {
          selector: '.task-menu-milestones',
          title: "<?php echo _l('task_milestone'); ?>",
       },
    ];
 }

 for (var i = 0; i < taskPopoverMenus.length; i++) {
    $(taskPopoverMenus[i].selector + ' .trigger').popover($.extend({}, commonTaskPopoverMenuOptions, {
       title: taskPopoverMenus[i].title,
       content: $('body').find(taskPopoverMenus[i].selector + ' .content-menu').html()
    }));
 }


function preview_purrequest_btn(invoker){
  "use strict"; 
    var id = $(invoker).attr('id');
    var rel_id = $(invoker).attr('rel_id');
    view_purrequest_file(id, rel_id);
}

function view_purrequest_file(id, rel_id) {
  "use strict"; 
      $('#purrequest_file_data').empty();
      $("#purrequest_file_data").load(admin_url + 'purchase/file_purrequest/' + id + '/' + rel_id, function(response, status, xhr) {
          if (status == "error") {
              alert_float('danger', xhr.statusText);
          }
      });
}
function close_modal_preview(){
  "use strict"; 
 $('._project_file').modal('hide');
}

function delete_purrequest_attachment(id) {
  "use strict"; 
    if (confirm_delete()) {
        requestGetJSON('purchase/delete_purrequest_attachment/' + id).done(function(response) {
            if (response.success == true || response.success == 'true') {
                $("#purrequest_pv_file").find('[data-attachment-id="' + id + '"]').remove();
            }
        }).fail(function(error) {
            alert_float('danger', error.responseText);
        });
    }
  }

// ── Logic Notes AJAX save (no page reload, no leave warning) ─────────────────
$(document).on('click', '#save_logic_notes_btn', function(){
  "use strict";
  var content = $('#logic_notes_content').val();
  var $btn = $(this);
  $btn.addClass('disabled').text('<?php echo _l('wait_text'); ?>');

  $.post(admin_url + 'purchase/save_logic_notes_ajax/<?php echo pur_html_entity_decode($pur_request->id); ?>', {
    logic_notes: content
  }).done(function(resp){
    try { resp = JSON.parse(resp); } catch(e){}
    if(resp && resp.success){
      alert_float('success', '<?php echo _l('updated_successfully', _l('logic_notes')); ?>');
      // Refresh page after save
      setTimeout(function(){ window.location.reload(); }, 500);
    } else {
      alert_float('danger', resp && resp.message ? resp.message : 'Error saving notes');
    }
  }).fail(function(){
    alert_float('danger', 'Error saving notes');
  }).always(function(){
    $btn.removeClass('disabled').text('<?php echo _l('submit'); ?>');
  });
});

// Track original value to suppress leave warning when nothing changed
$(document).ready(function(){
  "use strict";
  var $ta = $('#logic_notes_content');
  if($ta.length){
    $ta.data('original-value', $ta.val());
    // Override beforeunload for this textarea only
    $(window).on('beforeunload.logic_notes', function(){
      if($ta.val() !== $ta.data('original-value')){
        return 'You have unsaved logic notes.';
      }
    });
    // Clear warning after save
    $ta.on('change keyup', function(){
      // handled by beforeunload check above
    });
  }
});

// Vendor Recommendation Form Submit
$('#vendor-recommendation-form').on('submit', function(e){
  e.preventDefault();
  var formData = new FormData(this);
  var pr_id = '<?php echo isset($pur_request) ? $pur_request->id : 0; ?>';
  formData.append('<?php echo $this->security->get_csrf_token_name(); ?>', '<?php echo $this->security->get_csrf_hash(); ?>');
  
  $.ajax({
    url: admin_url + 'purchase/add_vendor_recommendation/' + pr_id,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(resp){
      window.location.href = admin_url + 'purchase/view_pur_request/' + pr_id + '?tab=vendor_recommendations';
    },
    error: function(){
      alert_float('danger', 'Failed to add. Please refresh the page and try again.');
    }
  });
});

// Preview function for vendor recommendation files
function preview_vr_file(invoker){
  var file_url = $(invoker).data('url');
  var file_name = $(invoker).data('name');
  var ext = file_name.split('.').pop().toLowerCase();
  var preview_html = '<div class="modal fade" id="vr_preview_modal" tabindex="-1" role="dialog"><div class="modal-dialog modal-lg" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h4 class="modal-title">' + file_name + '</h4></div><div class="modal-body" style="min-height:400px;">';
  if(['jpg','jpeg','png','gif','bmp','webp'].indexOf(ext) !== -1){
    preview_html += '<img src="' + file_url + '" class="img img-responsive" style="max-width:100%;">';
  } else if(ext == 'pdf'){
    preview_html += '<iframe src="' + file_url + '" width="100%" style="min-height:500px;" frameborder="0"></iframe>';
  } else {
    preview_html += '<p class="text-muted">No preview available. <a href="' + file_url + '" download>Download file</a></p>';
  }
  preview_html += '</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div></div></div></div>';
  $('#vr_preview_data').html(preview_html);
  $('#vr_preview_modal').modal('show');
}
</script>