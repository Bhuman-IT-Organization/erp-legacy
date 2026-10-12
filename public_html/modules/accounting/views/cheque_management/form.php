<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-10 col-md-offset-1">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin font-bold mbot15">
              <i class="fa fa-check-square-o"></i>
              <?php echo isset($cheque) && $cheque ? 'Edit Cheque #'.htmlspecialchars($cheque->cheque_number) : 'Add Cheque'; ?>
            </h4>
            <hr>
            <?php
            $fid = isset($cheque) && $cheque ? $cheque->id : '';
            $c   = isset($cheque) && $cheque ? $cheque : null;
            echo form_open_multipart(admin_url('accounting/cheque_management_form/'.$fid), ['id'=>'cheque-form']);
            ?>

            <!-- Section 1: Classification -->
            <h5 class="bold mbot10 mtop10"><i class="fa fa-tag"></i> Classification</h5>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label class="control-label"><span class="text-danger">* </span>Cheque Type</label>
                  <select name="cheque_type" id="cheque_type" class="selectpicker form-control" data-width="100%" required>
                    <option value="incoming" <?php echo ($c && $c->cheque_type=='incoming')?'selected':''; ?>>Incoming (Received from Customer)</option>
                    <option value="outgoing" <?php echo ($c && $c->cheque_type=='outgoing')?'selected':''; ?>>Outgoing (Issued to Vendor)</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label class="control-label"><span class="text-danger">* </span>Status</label>
                  <select name="status" id="cheque_status" class="selectpicker form-control" data-width="100%">
                    <option value="1" <?php echo ($c && $c->status==1)?'selected':''; ?>>Received</option>
                    <option value="2" <?php echo ($c && $c->status==2)?'selected':''; ?>>Verified</option>
                    <option value="3" <?php echo ($c && $c->status==3)?'selected':''; ?>>Deposited</option>
                    <option value="4" <?php echo ($c && $c->status==4)?'selected':''; ?>>Cleared</option>
                    <option value="5" <?php echo ($c && $c->status==5)?'selected':''; ?>>Bounced</option>
                    <option value="6" <?php echo ($c && $c->status==6)?'selected':''; ?>>Cancelled</option>
                    <option value="7" <?php echo ($c && $c->status==7)?'selected':''; ?>>Post-Dated (PDC)</option>
                    <option value="8" <?php echo ($c && $c->status==8)?'selected':''; ?>>Issued</option>
                    <option value="9" <?php echo ($c && $c->status==9)?'selected':''; ?>>Printed</option>
                    <option value="10" <?php echo ($c && $c->status==10)?'selected':''; ?>>Void</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label class="control-label">Source</label>
                  <select name="source_type" class="selectpicker form-control" data-width="100%">
                    <option value="manual" <?php echo (!$c||$c->source_type=='manual')?'selected':''; ?>>Manual Entry</option>
                    <option value="pur_invoice_payment" <?php echo ($c&&$c->source_type=='pur_invoice_payment')?'selected':''; ?>>Purchase Invoice Payment</option>
                    <option value="sales_payment" <?php echo ($c&&$c->source_type=='sales_payment')?'selected':''; ?>>Sales Payment</option>
                    <option value="expense" <?php echo ($c&&$c->source_type=='expense')?'selected':''; ?>>Expense</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Section 2: Cheque Details -->
            <h5 class="bold mbot10 mtop15"><i class="fa fa-money"></i> Cheque Details</h5>
            <div class="row">
              <div class="col-md-3">
                <?php echo render_input('cheque_number','Cheque Number',$c?$c->cheque_number:'','text',['required'=>'required']); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_input('cheque_bank_name','Bank Name',$c?($c->cheque_bank_name??''):''); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_date_input('cheque_date','Cheque Date',$c&&$c->cheque_date?_d($c->cheque_date):''); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_input('amount','Amount',$c?$c->amount:'','number',['step'=>'0.01','required'=>'required']); ?>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <?php echo render_date_input('deposit_date','Deposit Date',$c&&$c->deposit_date?_d($c->deposit_date):''); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_input('deposit_slip_no','Deposit Slip No.',$c?($c->deposit_slip_no??''):''); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_date_input('cleared_date','Cleared Date',$c&&$c->cleared_date?_d($c->cleared_date):''); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_input('batch_ref','Batch Reference',$c?($c->batch_ref??''):''); ?>
              </div>
            </div>

            <!-- Bank Account -->
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label class="control-label">Bank Account</label>
                  <select name="bank_account_id" class="selectpicker form-control" data-width="100%" data-none-selected-text="Select Bank Account" data-live-search="true">
                    <option value=""></option>
                    <?php foreach($bank_accounts as $ba): ?>
                    <option value="<?php echo $ba['id']; ?>" <?php echo ($c&&$c->bank_account_id==$ba['id'])?'selected':''; ?>><?php echo htmlspecialchars($ba['name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>

            <!-- Section 3: Party -->
            <h5 class="bold mbot10 mtop15"><i class="fa fa-user"></i> Party Information</h5>
            <div class="row">
              <div class="col-md-4">
                <?php echo render_input('rel_name','Party Name (Customer/Vendor)',$c?($c->rel_name??''):''); ?>
              </div>
              <div class="col-md-4">
                <?php echo render_input('rel_bank','Party Bank',$c?($c->rel_bank??''):''); ?>
              </div>
              <div class="col-md-4">
                <?php echo render_input('invoice_ref','Invoice Reference',$c?($c->invoice_ref??''):''); ?>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <?php echo render_input('transaction_id','Transaction ID',$c?($c->transaction_id??''):''); ?>
              </div>
            </div>

            <!-- Section 4: Bounce (shown when status=Bounced) -->
            <div id="bounce_section" style="<?php echo ($c&&$c->status==5)?'':'display:none;'; ?>">
              <h5 class="bold mbot10 mtop15 text-danger"><i class="fa fa-exclamation-triangle"></i> Bounce Details</h5>
              <div class="row">
                <div class="col-md-4">
                  <?php echo render_date_input('bounce_date','Bounce Date',$c&&$c->bounce_date?_d($c->bounce_date):''); ?>
                </div>
                <div class="col-md-4">
                  <?php echo render_input('bounce_reason','Bounce Reason',$c?($c->bounce_reason??''):''); ?>
                </div>
                <div class="col-md-4">
                  <?php echo render_input('bounce_charges','Bounce Charges',$c?($c->bounce_charges??0):0,'number',['step'=>'0.01']); ?>
                </div>
              </div>
            </div>

            <!-- Section 5: Attachment & Notes -->
            <h5 class="bold mbot10 mtop15"><i class="fa fa-paperclip"></i> Attachment & Notes</h5>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label">Cheque Image / Attachment</label>
                  <?php if($c && !empty($c->cheque_attachment)): ?>
                  <div class="mbot5">
                    <a href="<?php echo admin_url('accounting/cheque_management_file/'.$c->id.'/'.$c->cheque_attachment); ?>" target="_blank" class="btn btn-xs btn-default">
                      <i class="fa fa-paperclip"></i> <?php echo htmlspecialchars($c->cheque_attachment); ?>
                    </a>
                  </div>
                  <?php endif; ?>
                  <input type="file" name="cheque_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.gif">
                  <small class="text-muted">Upload cheque image or soft copy (JPG, PNG, PDF)</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label">Notes</label>
                  <textarea name="notes" class="form-control" rows="4"><?php echo $c?htmlspecialchars($c->notes??''):''; ?></textarea>
                </div>
              </div>
            </div>

            <div class="btn-bottom-toolbar text-right">
              <a href="<?php echo admin_url('accounting/cheque_management'); ?>" class="btn btn-default mright5"><?php echo _l('cancel'); ?></a>
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
  "use strict";
  // Show bounce section when status = Bounced
  $('#cheque_status').on('changed.bs.select change', function(){
    $('#bounce_section').toggle($(this).val() == '5');
  });
  // Auto-detect PDC when cheque date is future
  $('input[name="cheque_date"]').on('change', function(){
    var d = $(this).val();
    if(d){
      var today = new Date(); today.setHours(0,0,0,0);
      var cd = new Date(d);
      if(cd > today && $('#cheque_status').val() != '5'){
        $('#cheque_status').selectpicker('val','7');
        alert_float('info','Cheque date is in the future — marked as Post-Dated (PDC)');
      }
    }
  });
});
</script>
</body>
</html>
