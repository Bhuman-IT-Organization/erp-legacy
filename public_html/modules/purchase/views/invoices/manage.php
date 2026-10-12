<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <div class="row">
                     <div class="col-md-12">
                      <h4 class="no-margin font-bold"><i class="fa fa-clipboard" aria-hidden="true"></i> <?php echo _l($title); ?></h4>
                      <hr />
                     </div>
                    </div>
                    <div class="row">    
                          <div class="_buttons col-md-12">
                            <?php if (has_permission('purchase_invoices', '', 'create') || is_admin()) { ?>
                            <a href="<?php echo admin_url('purchase/pur_invoice'); ?>"class="btn btn-info pull-left mright10 display-block">
                                <?php echo _l('new'); ?>
                            </a>
                            <button id="add-batch-payment" onclick="pur_add_batch_payment()" class="btn btn-primary pull-left mright10">
                                <i class="fa-solid fa-file-invoice tw-mr-1"></i>
                                <?php echo _l('batch_payments'); ?>
                            </button>
                            <button type="button" onclick="$('#batch-print-modal').modal('show');" class="btn btn-default pull-left">
                                <i class="fa fa-print"></i> <?php echo _l('batch_print'); ?>
                            </button>

                            <?php } ?>
                            
                        </div>
                    </div>
                    <div class="row mtop15 mbot15">
                      <div class="col-md-2">
                            <?php echo render_date_input('from_date','','',array('placeholder' => _l('from_date') )); ?>
                        </div>
                        <div class="col-md-2">
                            <?php echo render_date_input('to_date','','',array('placeholder' => _l('to_date') )); ?>
                        </div>
                        <div class="col-md-2 form-group">
                         
                          <select name="contract[]" id="contract" class="selectpicker" multiple="true"  data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('contract'); ?>">
                             <?php foreach($contracts as $ct){ ?>
                              <option value="<?php echo pur_html_entity_decode($ct['id']); ?>" ><?php echo pur_html_entity_decode($ct['contract_number']); ?></option>
                             <?php } ?>
                          </select>


                         </div>
                         <div class="col-md-3 form-group">
                           <select name="pur_orders[]" id="pur_orders" class="selectpicker" multiple="true"  data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('purchase_order'); ?>">
                             <?php foreach($pur_orders as $ct){ ?>
                              <option value="<?php echo pur_html_entity_decode($ct['id']); ?>" <?php if($this->input->get('po') != null && $this->input->get('po') == $ct['id']){ echo 'selected'; } ?> ><?php echo pur_html_entity_decode($ct['pur_order_number']); ?></option>
                             <?php } ?>
                          </select>
                         </div>
                         <div class="col-md-3 form-group">

                        <select name="vendor_ft[]" id="vendor_ft" class="ajax-sesarch" multiple="true"  data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('vendor'); ?>" >
                         
                        </select>
                        </div>

                        <div class="col-md-2">
                          <?php 
                          $statuses = [0 => ['id' => 'unpaid', 'name' => _l('unpaid')],
                          1 => ['id' => 'paid', 'name' => _l('paid')],
                          2 => ['id' => 'partially_paid', 'name' => _l('partially_paid')],
                          ];

                          echo render_select('payment_status[]',$statuses,array('id','name'),'','',array('data-width'=>'100%','data-none-selected-text'=>_l('payment_status'),'multiple'=>true,'data-actions-box'=>true),array(),'no-mbot','',false); ?>
                        </div>

                        <div class="col-md-2">
                          <select class="selectpicker" name="currency[]"  multiple="true" data-width="100%" data-none-selected-text="<?php echo _l('currency'); ?>">

                              <?php foreach($currencies as $currency){
                                 
                                 ?>
                                 <option value="<?php echo pur_html_entity_decode($currency['id']); ?>" ><?php echo pur_html_entity_decode($currency['name']); ?></option>
                                 <?php } ?>
                            </select>
                        </div>
                    </div>
                    
                    <?php 

                    $table_data = array(
                        _l('invoice_code'),
                        _l('invoice_number'),
                        _l('grn'),
                        _l('transaction_id'),
                        _l('vendor'),
                        _l('contract'),
                        _l('pur_order'),
                        _l('pur_request'),
                        _l('invoice_date'),
                        _l('recurring_from'),
                        _l('invoice_amount'),
                        _l('tax_value'),
                        _l('total_included_tax'),
                        _l('approval_status'),
                        _l('payment_request_status'),
                        _l('payment_status'),
                        _l('debit_note'),
                        _l('tag'),
                        );

                    $custom_fields = get_custom_fields('pur_invoice',array('show_on_table'=>1));
                    foreach($custom_fields as $field){
                     array_push($table_data,$field['name']);
                    }
                    
                    render_datatable($table_data,'table_pur_invoices'); ?>
            
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="modal-wrapper"></div>

<!-- Batch Print Modal -->
<div class="modal fade" id="batch-print-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title"><i class="fa fa-print"></i> <?php echo _l('batch_print'); ?></h4>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-8">
            <div class="form-group">
              <label><?php echo _l('transaction_id'); ?></label>
              <input type="text" id="batch_print_txn_id" class="form-control" placeholder="<?php echo _l('transaction_id'); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-info btn-block" onclick="batch_print_search();"><?php echo _l('search'); ?></button>
          </div>
        </div>
        <div id="batch-print-results" class="mtop15"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
        <a href="#" id="batch-print-pdf-btn" class="btn btn-success hide" target="_blank"><i class="fa fa-file-pdf-o"></i> <?php echo _l('print'); ?> PDF</a>
      </div>
    </div>
  </div>
</div>

<script>
function batch_print_search(){
  var txn_id = $('#batch_print_txn_id').val();
  if(txn_id == ''){
    alert_float('warning', 'Please enter Transaction ID');
    return;
  }
  $.post(admin_url + 'purchase/batch_print_search', {transaction_id: txn_id}, function(resp){
    var data = JSON.parse(resp);
    $('#batch-print-results').html(data.html);
    if(data.count > 0){
      $('#batch-print-pdf-btn').removeClass('hide').attr('href', admin_url + 'purchase/batch_print_pdf?transaction_id=' + encodeURIComponent(txn_id));
    } else {
      $('#batch-print-pdf-btn').addClass('hide');
    }
  });
}

function confirm_delete_pur_invoice(invoice_id) {
  "use strict";
  $.getJSON(admin_url + 'purchase/check_invoice_batch_txn/' + invoice_id, function(resp){
    if(!resp.can_delete){
      // Blocked — active payments exist
      alert_float('danger', resp.message);
    } else if(resp.has_batch && resp.count > 0){
      // Allowed but batch warning
      if(confirm(resp.message)){
        window.location.href = admin_url + 'purchase/delete_pur_invoice/' + invoice_id;
      }
    } else {
      // No payments, no batch — standard confirm
      if(confirm_delete()){
        window.location.href = admin_url + 'purchase/delete_pur_invoice/' + invoice_id;
      }
    }
  }).fail(function(){
    if(confirm_delete()){
      window.location.href = admin_url + 'purchase/delete_pur_invoice/' + invoice_id;
    }
  });
}
</script>

<?php init_tail(); ?>
</body>
</html>