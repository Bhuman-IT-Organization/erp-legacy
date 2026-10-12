<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-5">
          
          <div class="col-md-12 no-padding">
              <div class="panel_s">
                  <div class="panel-body">
                      <?php echo form_open_multipart($this->uri->uri_string()); ?>

                      <?php
                      // ── Saved data verification summary ──────────────────
                      $pm_name_display = '';
                      foreach($payment_modes as $pm){ if($pm['id'] == $payment_invoice->paymentmode){ $pm_name_display = $pm['name']; break; } }
                      $is_cheque_saved = !empty($payment_invoice->cheque_number);
                      ?>
                      <div class="alert alert-<?php echo $is_cheque_saved ? 'success' : 'info'; ?> mbot10" style="padding:8px 12px; font-size:12px;">
                        <strong><i class="fa fa-database"></i> Saved:</strong>
                        Amount: <strong><?php echo app_format_money($payment_invoice->amount, $base_currency->name); ?></strong> |
                        Mode: <strong><?php echo htmlspecialchars($pm_name_display); ?></strong> |
                        Date: <strong><?php echo _d($payment_invoice->date); ?></strong>
                        <?php if($is_cheque_saved): ?>
                        | <i class="fa fa-check-circle text-success"></i>
                        Bank: <strong><?php echo htmlspecialchars($payment_invoice->cheque_bank_name ?? '-'); ?></strong> |
                        Cheque#: <strong><?php echo htmlspecialchars($payment_invoice->cheque_number); ?></strong> |
                        Date: <strong><?php echo !empty($payment_invoice->cheque_date) ? _d($payment_invoice->cheque_date) : '-'; ?></strong> |
                        Attachment: <strong><?php echo !empty($payment_invoice->cheque_attachment) ? '<i class="fa fa-paperclip text-success"></i> Yes' : '<span class="text-danger">No</span>'; ?></strong>
                        <?php else: ?>
                        | <span class="text-muted">No cheque details</span>
                        <?php endif; ?>
                      </div>


                      <label for="amount"><span class="text-danger">* </span><?php echo _l('payment_edit_amount_received'); ?></label>
                      <?php 
                      $attr = ['required' => 'true'];
                      if($payment_invoice->approval_status == 2){
                        $attr['readonly'] = 'true';

                      }
                      echo render_input('amount', '', $payment_invoice->amount, 'number', $attr); ?>

                      <label for="date"><span class="text-danger">* </span><?php echo _l('payment_edit_date'); ?></label>
                      <?php echo render_date_input('date', '', _d($payment_invoice->date), $attr); ?>
                      <?php echo render_select('paymentmode', $payment_modes, ['id', 'name'], 'payment_mode', $payment_invoice->paymentmode); ?>
                   
                      <?php echo render_input('transactionid', 'payment_transaction_id', $payment_invoice->transactionid); ?>

                      <!-- Cheque Details (shown only when payment mode is Cheque) -->
                      <?php
                      $edit_pm_name = '';
                      foreach($payment_modes as $pm){ if($pm['id'] == $payment_invoice->paymentmode){ $edit_pm_name = strtolower($pm['name']); break; } }
                      $is_cheque_mode_edit = (strpos($edit_pm_name,'cheque') !== false || strpos($edit_pm_name,'check') !== false);
                      $show_cheque = $is_cheque_mode_edit ? '' : 'display:none;';
                      $cheque_missing = $is_cheque_mode_edit && empty($payment_invoice->cheque_number);
                      ?>
                      <?php if($cheque_missing): ?>
                      <div class="alert alert-warning mbot10" style="padding:8px 12px; font-size:12px;">
                          <i class="fa fa-exclamation-triangle"></i>
                          <strong><?php echo _l('cheque_details'); ?></strong> — <?php echo _l('cheque_details_missing_notice'); ?>
                      </div>
                      <?php endif; ?>
                      <div id="edit_cheque_details_section" style="<?php echo $show_cheque; ?> background:#f9f9f9; border:1px solid #ddd; border-radius:4px; padding:12px; margin-bottom:10px;">
                          <h5 class="no-margin mbot10"><i class="fa fa-money"></i> <?php echo _l('cheque_details'); ?> <small class="text-danger">* <?php echo _l('required'); ?></small></h5>
                          <div class="form-group">
                              <label class="control-label"><span class="text-danger">* </span><?php echo _l('cheque_bank_name'); ?></label>
                              <input type="text" name="cheque_bank_name" id="cheque_bank_name" class="form-control" value="<?php echo htmlspecialchars($payment_invoice->cheque_bank_name ?? ''); ?>" autocomplete="off">
                          </div>
                          <div class="form-group">
                              <label class="control-label"><span class="text-danger">* </span><?php echo _l('cheque_number'); ?></label>
                              <input type="text" name="cheque_number" id="cheque_number" class="form-control" value="<?php echo htmlspecialchars($payment_invoice->cheque_number ?? ''); ?>" autocomplete="off">
                          </div>
                          <div class="form-group">
                              <label class="control-label"><span class="text-danger">* </span><?php echo _l('cheque_date'); ?></label>
                              <div class="input-group date">
                                  <input type="text" name="cheque_date" id="cheque_date" class="form-control datepicker" value="<?php echo !empty($payment_invoice->cheque_date) ? _d($payment_invoice->cheque_date) : ''; ?>" autocomplete="off">
                                  <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="control-label"><?php echo _l('cheque_attachment'); ?></label>
                              <?php if(!empty($payment_invoice->cheque_attachment)){ ?>
                              <div class="mbot5">
                                  <a href="<?php echo site_url(PURCHASE_PATH.'payment_cheque/'.$payment_invoice->id.'/'.$payment_invoice->cheque_attachment); ?>" target="_blank" class="btn btn-xs btn-default"><i class="fa fa-paperclip"></i> <?php echo htmlspecialchars($payment_invoice->cheque_attachment); ?></a>
                                  <label class="checkbox-inline mleft10"><input type="checkbox" name="remove_cheque_attachment" value="1"> <?php echo _l('remove_attachment'); ?></label>
                              </div>
                              <?php } ?>
                              <input type="file" name="cheque_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.gif">
                              <small class="text-muted"><?php echo _l('cheque_attachment_hint'); ?></small>
                          </div>
                      </div>

                      <?php echo render_textarea('note', 'note', $payment_invoice->note, ['rows' => 7]); ?>

                      <!-- Hidden paymentmode fallback for selectpicker submit issue -->
                      <input type="hidden" id="paymentmode_hidden_edit" name="paymentmode_hidden" value="<?php echo $payment_invoice->paymentmode; ?>">

                      <div class="btn-bottom-toolbar text-right">
                          <button type="button" id="save_payment_invoice_btn" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                      </div>
                      <?php echo form_close(); ?>
                  </div>
              </div>
          </div>
      </div>


      <div class="col-md-7">
        <div class="panel_s">
          <div class="panel-body">
             <?php $csrf = array(
                'name' => $this->security->get_csrf_token_name(),
                'hash' => $this->security->get_csrf_hash(),
              );
              ?>
              <input type="hidden" id="csrf_token_name" name="<?=$csrf['name'];?>" value="<?=$csrf['hash'];?>" />
          	   <?php if($payment_invoice->approval_status == 1){ ?>
                    <div class="ribbon info"><span class="fontz9" ><?php echo _l('purchase_not_yet_approve'); ?></span></div>
                <?php }elseif($payment_invoice->approval_status == 2){ ?>
                  <div class="ribbon success"><span><?php echo _l('purchase_approved'); ?></span></div>
                <?php }elseif($payment_invoice->approval_status == 3){ ?>  
                  <div class="ribbon danger"><span><?php echo _l('purchase_reject'); ?></span></div>
                <?php } ?>

          	<h4 class="pull-left "><?php echo _l('payment_for').' '; ?><a href="<?php echo admin_url('purchase/purchase_invoice/'. $payment_invoice->pur_invoice); ?>"><?php echo pur_html_entity_decode($invoice->invoice_number); ?></a></h4>
					<div class="clearfix"></div>
				<hr class="hr-panel-heading" />
          	<div class="col-md-12">
          		
				<div class="row">
					<div class="col-md-6 col-sm-6">
						<address>
							<?php echo format_organization_info(); ?>
						</address>
					</div>
				
					</div>
					<div class="col-md-12 text-center">
						<h3 class="text-uppercase"><?php echo _l('payment_receipt'); ?></h3>
					</div>
					<div class="col-md-12 mtop30">
						<div class="row">
							<div class="col-md-6">
								<p><?php echo _l('payment_date'); ?> <span class="pull-right bold"><?php echo _d($payment_invoice->date); ?></span></p>
								<hr />
								<p><?php echo _l('payment_view_mode'); ?>
								<span class="pull-right bold">
									
									<?php if(!empty($payment_invoice->paymentmode)){
										echo  get_payment_mode_name_by_id($payment_invoice->paymentmode);
									}
									?>
								</span></p>
								<?php if(!empty($payment_invoice->transactionid)) { ?>
									<hr />
									<p><?php echo _l('payment_transaction_id'); ?>: <span class="pull-right bold"><?php echo pur_html_entity_decode($payment_invoice->transactionid); ?></span></p>
								<?php } ?>
								<?php
								// Show cheque section if mode is cheque — even if details not yet saved
								$receipt_pm_name = '';
								foreach($payment_modes as $pm){ if($pm['id'] == $payment_invoice->paymentmode){ $receipt_pm_name = strtolower($pm['name']); break; } }
								$is_cheque_receipt = (strpos($receipt_pm_name,'cheque') !== false || strpos($receipt_pm_name,'check') !== false);
								?>
								<?php if($is_cheque_receipt): ?>
									<hr />
									<p class="bold text-info"><i class="fa fa-money"></i> <?php echo _l('cheque_details'); ?></p>
									<?php if(!empty($payment_invoice->cheque_bank_name)): ?>
									<p><?php echo _l('cheque_bank_name'); ?>: <span class="pull-right bold"><?php echo htmlspecialchars($payment_invoice->cheque_bank_name); ?></span></p>
									<?php else: ?><p><?php echo _l('cheque_bank_name'); ?>: <span class="pull-right text-danger"><i class="fa fa-exclamation-circle"></i> <?php echo _l('not_entered'); ?></span></p><?php endif; ?>
									<?php if(!empty($payment_invoice->cheque_number)): ?>
									<p><?php echo _l('cheque_number'); ?>: <span class="pull-right bold"><?php echo htmlspecialchars($payment_invoice->cheque_number); ?></span></p>
									<?php else: ?><p><?php echo _l('cheque_number'); ?>: <span class="pull-right text-danger"><i class="fa fa-exclamation-circle"></i> <?php echo _l('not_entered'); ?></span></p><?php endif; ?>
									<?php if(!empty($payment_invoice->cheque_date)): ?>
									<p><?php echo _l('cheque_date'); ?>: <span class="pull-right bold"><?php echo _d($payment_invoice->cheque_date); ?></span></p>
									<?php else: ?><p><?php echo _l('cheque_date'); ?>: <span class="pull-right text-danger"><i class="fa fa-exclamation-circle"></i> <?php echo _l('not_entered'); ?></span></p><?php endif; ?>
									<?php if(!empty($payment_invoice->cheque_attachment)): ?>
									<hr/>
									<p><?php echo _l('cheque_attachment'); ?>: <span class="pull-right"><a href="<?php echo site_url(PURCHASE_PATH.'payment_cheque/'.$payment_invoice->id.'/'.$payment_invoice->cheque_attachment); ?>" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-paperclip"></i> <?php echo _l('view'); ?></a></span></p>
									<?php endif; ?>
									<?php if(empty($payment_invoice->cheque_number)): ?>
									<div class="alert alert-warning mtop10" style="padding:6px 10px; font-size:11px;">
										<i class="fa fa-exclamation-triangle"></i> <?php echo _l('cheque_details_missing_notice'); ?>
									</div>
									<?php endif; ?>
								<?php endif; ?>
							</div>
							<div class="clearfix"></div>
							<div class="col-md-6">
								<div class="payment-preview-wrapper">
									<?php echo _l('payment_total_amount'); ?><br />
									<?php echo app_format_money($payment_invoice->amount,$base_currency->name); ?>
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-12 mtop30">
					<h4><?php echo _l('payment_for_string'); ?></h4>
					<div class="table-responsive">
					<table class="table table-borderd table-hover">
						<thead>
							<tr>
								<th><?php echo _l('payment_table_invoice_number'); ?></th>
								<th><?php echo _l('payment_table_invoice_date'); ?></th>
								<th><?php echo _l('payment_table_invoice_amount_total'); ?></th>
								<th><?php echo _l('payment_table_payment_amount_total'); ?></th>
								<?php if($invoice->payment_status != 'paid') { ?>
										<th><span class="text-danger"><?php echo _l('invoice_amount_due'); ?></span></th>
									<?php } ?>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><?php echo get_pur_invoice_number($payment_invoice->pur_invoice); ?></td>
									<td><?php echo _d($invoice->invoice_date); ?></td>
									<td><?php echo app_format_money($invoice->total, $base_currency->name); ?></td>
									<td><?php echo app_format_money($payment_invoice->amount, $base_currency->name); ?></td>
									<?php if($invoice->payment_status != 'paid') { ?>
											<td class="text-danger">
												<?php echo app_format_money(purinvoice_left_to_pay($invoice->id), $base_currency->name); ?>
											</td>
										<?php } ?>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
			</div>


        <h4 class="pull-left "><?php echo _l('pur_approval_infor'); ?></h4>
          <div class="clearfix"></div>
        <hr class="hr-panel-heading mtop5" />

      <div class="project-overview-right">
        <?php if(count($list_approve_status) > 0){ ?>
          
         <div class="row">
           <div class="col-md-12 project-overview-expenses-finance">
            <?php 
              $this->load->model('staff_model');
              $enter_charge_code = 0;
            foreach ($list_approve_status as $value) {
              $value['staffid'] = explode(', ',$value['staffid'] ?? '');
              if($value['action'] == 'sign'){
             ?>
             <div class="col-md-6 apr_div">
                 <p class="text-uppercase text-muted no-mtop bold">
                  <?php
                  $staff_name = '';
                  $st = _l('status_0');
                  $color = 'warning';
                  foreach ($value['staffid'] as $key => $val) {
                    if($staff_name != '')
                    {
                      $staff_name .= ' or ';
                    }
                    $staff_name .= $this->staff_model->get($val)->firstname;
                  }
                  echo pur_html_entity_decode($staff_name); 
                  ?></p>
                 <?php if($value['approve'] == 2){ 
                  ?>

                  <?php if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.png')){ ?>
                    <img src="<?php echo site_url(PURCHASE_PATH.'payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.png'); ?>" class="img_style">
                    <?php }else if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.jpg')){ ?>
                       <img src="<?php echo site_url(PURCHASE_PATH.'payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.jpg'); ?>" class="img_style">
                    <?php } ?>
                   <br><br>
                 <p class="bold text-center text-success"><?php echo _l('signed').' '._dt($value['date']); ?></p>
                 <?php } ?> 
                    
            </div>
            <?php }else{ ?>
            <div class="col-md-6 apr_div">
                 <p class="text-uppercase text-muted no-mtop bold">
                  <?php
                  $staff_name = '';
                  foreach ($value['staffid'] as $key => $val) {
                    if($staff_name != '')
                    {
                      $staff_name .= ' or ';
                    }
                    $staff_name .= $this->staff_model->get($val)->firstname;
                  }
                  echo pur_html_entity_decode($staff_name); 
                  ?></p>
                 <?php if($value['approve'] == 2){ 
                  ?>
                  <img src="<?php echo site_url(PURCHASE_PATH.'approval/approved.png'); ?>" class="img_style">

                  <?php if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.png')){ ?>
                    <br><img src="<?php echo site_url(PURCHASE_PATH.'payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.png'); ?>" class="img_style">
                    <?php }else if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.jpg')){ ?>
                       <br><img src="<?php echo site_url(PURCHASE_PATH.'payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.jpg'); ?>" class="img_style">
                    <?php } ?>

                 <?php }elseif($value['approve'] == 3){ ?>
                    <img src="<?php echo site_url(PURCHASE_PATH.'approval/rejected.png'); ?>" class="img_style">

                    <?php if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.png')){ ?>
                    <br><img src="<?php echo site_url(PURCHASE_PATH.'payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.png'); ?>" class="img_style">
                    <?php }else if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.jpg')){ ?>
                       <br><img src="<?php echo site_url(PURCHASE_PATH.'payment_invoice/signature/'.$payment_invoice->id.'/signature_'.$value['id'].'.jpg'); ?>" class="img_style">
                    <?php } ?>
                <?php } ?> 
                <br><br>  
                 <p><?php echo pur_html_entity_decode($value['note']) ?></p>  
                <p class="bold text-center text-<?php if($value['approve'] == 2){ echo 'success'; }elseif($value['approve'] == 3){ echo 'danger'; } ?>"><?php echo _dt($value['date']); ?></p> 
            </div>
            <?php }
            } ?>
           </div>
        </div>
        
        <?php } ?>
        </div>
        <div class="pull-right">
            <?php 
            if($check_appr && $check_appr != false){
            if(pur_can_send_for_approval(
                 $payment_invoice->approval_status,
                 $check_approve_status,
                 isset($appr_setting) ? $appr_setting : null,
                 isset($list_approve_status) ? $list_approve_status : [],
                 [1]
            )){ ?>

        <a data-toggle="tooltip" data-loading-text="<?php echo _l('wait_text'); ?>" class="btn btn-success lead-top-btn lead-view" data-placement="top" href="#" onclick="send_request_approve(<?php echo pur_html_entity_decode($payment_invoice->id); ?>); return false;"><?php echo _l('send_request_approve_pur'); ?></a>
      <?php } }
        if(isset($check_approve_status['staffid'])){
            ?>
            <?php 
        if(in_array(get_staff_user_id(), $check_approve_status['staffid']) && !in_array(get_staff_user_id(), $get_staff_sign) && $payment_invoice->approval_status == 1){ ?>
            <div class="btn-group" >
                   <a href="#" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo _l('approve'); ?><span class="caret"></span></a>
                   <ul class="dropdown-menu dropdown-menu-right ul_style">
                    <li>
                      <div class="col-md-12">
                        <?php echo render_textarea('reason', 'reason'); ?>
                      </div>

                       <?php if(get_option('allow_upload_esign_for_approve_type') == 1){ ?>
                         <div class="col-md-12 mbot15">
                            <?php echo form_open_multipart(admin_url('purchase/sign_attachment'),array('id'=>'sign_attachment-form')); ?>

                               <label for="sign_attachment"><?php echo _l('e_sign'); ?></label>
                               <input type="file" id="sign_attachment_file" accept=".png, .jpg" name="sign_attachment" class="form-control">

                               <?php echo form_hidden('approve_rel_id', $payment_invoice->id) ?>
                               <?php echo form_hidden('approve_rel_type', 'payment_request') ?>

                            <?php echo form_close(); ?>   
                          </div> 
                          <?php } ?>

                    </li>
                      <li>
                        <div class="row text-right col-md-12 pad_right_0 mbot15">
                          <a href="#" data-loading-text="<?php echo _l('wait_text'); ?>" onclick="approve_request(<?php echo pur_html_entity_decode($payment_invoice->id); ?>); return false;" class="btn btn-success mright15"><?php echo _l('approve'); ?></a>
                         <a href="#" data-loading-text="<?php echo _l('wait_text'); ?>" onclick="deny_request(<?php echo pur_html_entity_decode($payment_invoice->id); ?>); return false;" class="btn btn-warning"><?php echo _l('deny'); ?></a></div>
                      </li>
                   </ul>
                </div>
          <?php }
            ?>
            
          <?php
           if(in_array(get_staff_user_id(), $check_approve_status['staffid']) && in_array(get_staff_user_id(), $get_staff_sign) && $payment_invoice->approval_status == 1){ ?>
            <button onclick="accept_action();" class="btn btn-success pull-right action-button"><?php echo _l('e_signature_sign'); ?></button>
          <?php }
            ?>
            <?php 
             }
            ?>
          </div>
			

        </div>
      </div>
    </div>



  </div>
</div>


<div class="modal fade" id="add_action" tabindex="-1" role="dialog">
   <div class="modal-dialog">
      <div class="modal-content">
         
        <div class="modal-body">
         <p class="bold" id="signatureLabel"><?php echo _l('signature'); ?></p>

         <div class="form-group">
             <div class="radio radio-primary radio-inline">
                 <input type="radio" id="sign" name="sign_type" value="1" checked>
                 <label for="sign">
                    <?php echo _l('sign'); ?>      
                  </label>
             </div>
             <div class="radio radio-primary radio-inline">
                 <input type="radio" id="upload" name="sign_type" value="0" >
                 <label for="upload">
                     <?php echo _l('pur_upload'); ?>    
                  </label>
             </div>
         </div>


            <div id="upload_sign" class="mbot15 hide">
               <?php echo form_open_multipart(admin_url('purchase/sign_attachment'),array('id'=>'sign_attachment-form')); ?>

                  <input type="file" id="sign_attachment_file" accept=".png, .jpg" name="sign_attachment" class="form-control">

                  <?php echo form_hidden('approve_rel_id', $payment_invoice->id) ?>
                  <?php echo form_hidden('approve_rel_type', 'payment_request') ?>

               <?php echo form_close(); ?>    
            </div>

            <div id="sign_pad" >
               <div class="signature-pad--body">
                 <canvas id="signature" height="130" width="550"></canvas>
               </div>
               <input type="text" class="ip_style" tabindex="-1" name="signature" id="signatureInput">

               <div class="dispay-block">
                 <button type="button" class="btn btn-default btn-xs clear" tabindex="-1" onclick="signature_clear();"><?php echo _l('clear'); ?></button>
               
               </div>
            </div>
            

           

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
           <button id="sign_button" onclick="sign_request(<?php echo pur_html_entity_decode($payment_invoice->id); ?>);" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off" class="btn btn-success"><?php echo _l('e_signature_sign'); ?></button>
          </div>

      </div><!-- /.modal-content -->
   </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php init_tail(); ?>

<!-- ── Batch Payment Info Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="batch_payment_info_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:#f0f7ff; border-bottom:2px solid #3498db;">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">
          <i class="fa fa-info-circle text-info"></i>
          <?php echo _l('batch_payment'); ?> — <?php echo _l('approval_info'); ?>
        </h4>
      </div>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:15px;">
          <i class="fa fa-exclamation-circle"></i>
          <strong><?php echo _l('batch_payment_notice'); ?>:</strong>
          <span id="batch_modal_notice_text"></span>
        </div>
        <div id="batch_payments_table_wrap">
          <table class="table table-bordered table-hover" style="font-size:13px;">
            <thead style="background:#eaf3fb;">
              <tr>
                <th>#</th>
                <th><?php echo _l('payment_table_invoice_number'); ?></th>
                <th><?php echo _l('amount'); ?></th>
                <th><?php echo _l('date'); ?></th>
                <th><?php echo _l('payment_mode'); ?></th>
                <th><?php echo _l('approval_status'); ?></th>
                <th><?php echo _l('options'); ?></th>
              </tr>
            </thead>
            <tbody id="batch_payments_tbody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
        <button type="button" id="batch_modal_proceed_btn" class="btn btn-success">
          <i class="fa fa-check"></i> <?php echo _l('approve'); ?> <?php echo _l('all'); ?> <?php echo _l('pending'); ?>
        </button>
      </div>
    </div>
  </div>
</div>
</body>
</html>
<?php require 'modules/purchase/assets/js/payment_invoice_js.php';?>