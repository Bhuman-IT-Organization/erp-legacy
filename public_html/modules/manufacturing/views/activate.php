<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-12">
            <div class="panel_s">
               <div class="panel-body">
			   <h4>Module License Activation</h4>
			   <hr class="hr-panel-heading">
			   <?php echo base64_decode('UGxlYXNlIGFjdGl2YXRlIHlvdXIgbW9kdWxlIGxpY2Vuc2UgdXNpbmcgeW91ciBsaWNlbnNlIHB1cmNoYXNlIGtleSAoPGEgdGFyZ2V0PSJfYmxhbmsiIGhyZWY9Imh0dHBzOi8vaGVscC5tYXJrZXQuZW52YXRvLmNvbS9oYy9lbi11cy9hcnRpY2xlcy8yMDI4MjI2MDAtV2hlcmUtSXMtTXktUHVyY2hhc2UtQ29kZS0iPndoZXJlIGNhbiBJIGZpbmQgbXkgcHVyY2hhc2Uga2V5PzwvYT4p');?>
			   <br><br>
			   <?php echo form_open($submit_url, ['autocomplete'=>'off', 'id'=>'verify-form']); ?>
                        <?php echo form_hidden('original_url', $original_url); ?> 
                  		<?php echo form_hidden('module_name', $module_name); ?> 
								<?php echo render_input('purchase_key', 'purchase_key', '', 'text', ['required'=>true]); ?>
                        <?php echo render_input('username', 'Envato Username', '', 'text', ['required'=>true]); ?>
                  		<button id="submit" type="submit" class="btn btn-info pull-right"><?php echo _l('submit'); ?></button>
                  	<?php echo form_close(); ?>
               </div>
            </div>
         </div>
         <div class="col-md-6">
		 </div>
      </div>
   </div>
</div>
<?php init_tail(); ?>
<script type="text/javascript">
   appValidateForm($('#verify-form'), {
        purchase_key: 'required',
        username: 'required'
    }, manage_verify_form);

   function manage_verify_form(form) {
      var data = $(form).serialize();
      var url = form.action;
      $("#submit").prop('disabled', true).prepend('<i class="fa fa-spinner fa-pulse"></i> ');
      $.post(url, data).done(function(response) {
         var response = $.parseJSON(response);
         if(!response.status){
            alert_float("danger",response.message);
         }
         if(response.status){
            alert_float("success","Activating....");
            window.location.href = response.original_url;
         }
         $("#submit").prop('disabled', false).find('i').remove();
      });
   }
</script>