<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">

        <?php
        // From the model, so this page and the lifecycle the server enforces agree. The
        // inline copy stopped at 10 and showed every outgoing cheque as "Unknown", because
        // 11 In Clearing, 12 Prepared, 13 Presented and 14 Returned were added later.
        $sm = isset($status_map) ? $status_map : $this->accounting_model->acc_cheque_statuses();

        // 1 = awaiting, 2 = approved, 3 = rejected, matching what the approval chain writes
        $am = [
            0 => ['Not submitted', 'default'],
            1 => ['Awaiting',      'warning'],
            2 => ['Approved',      'success'],
            3 => ['Rejected',      'danger'],
        ];
        $st = $sm[$cheque->status] ?? ['Unknown','default'];
        $ap = $am[$cheque->approval_status] ?? ['Not submitted','default'];
        $tc = $cheque->cheque_type == 'outgoing' ? 'warning' : 'primary';
        $src_map = ['pur_invoice_payment'=>'Purchase Invoice','expense_payment'=>'Expense Payment','sales_payment'=>'Sales Payment','expense'=>'Expense','manual'=>'Manual'];
        ?>

        <?php // A deleted cheque is still readable - that is the point of not removing it -
              // but it says so plainly, and offers the way back.
              if (!empty($cheque->is_deleted)) { ?>
        <div class="alert alert-danger">
          <strong><?php echo _l('acc_cheque_is_deleted'); ?></strong>
          <?php if (!empty($cheque->deleted_at)) { ?>
          <?php echo _dt($cheque->deleted_at); ?>
          <?php } ?>
          <?php if (!empty($cheque->deleted_by)) { ?>
          &middot; <?php echo e(get_staff_full_name($cheque->deleted_by)); ?>
          <?php } ?>
          <a href="<?php echo admin_url('accounting/cheque_management_restore/' . $cheque->id); ?>"
             class="btn btn-success btn-sm pull-right">
            <i class="fa fa-undo"></i> <?php echo _l('acc_cheque_restore'); ?>
          </a>
        </div>
        <?php } ?>

        <!-- Header -->
        <div class="panel_s">
          <div class="panel-body">
            <div class="row mbot10">
              <div class="col-md-7">
                <h4 class="no-margin font-bold">
                  <i class="fa fa-check-square-o"></i>
                  Cheque #<?php echo htmlspecialchars($cheque->cheque_number); ?>
                  <span class="label label-<?php echo $st[1]; ?> mleft5"><?php echo $st[0]; ?></span>
                  <span class="label label-<?php echo $tc; ?> mleft5"><?php echo ucfirst($cheque->cheque_type); ?></span>
                  <?php if($cheque->is_pdc): ?><span class="label label-warning mleft5">PDC</span><?php endif; ?>
                  <span class="label label-<?php echo $ap[1]; ?> mleft5"><?php echo $ap[0]; ?></span>
                </h4>
              </div>
              <div class="col-md-5 text-right">
                <?php // Only offered when the lifecycle actually has somewhere to go, so a
                      // cleared or cancelled cheque no longer shows a button that can only
                      // be refused.
                      if (count($next_statuses) > 0 && empty($cheque->is_deleted)) { ?>
                <button class="btn btn-info btn-sm mright5" onclick="open_status_modal(<?php echo $cheque->id; ?>, <?php echo $cheque->status; ?>); return false;">
                  <i class="fa fa-refresh"></i> Update Status
                </button>
                <?php } ?>

                <?php // Send for approval, Approve and Reject are NOT here. They live with
                      // the approval block further down, the way a Purchase Request and a
                      // Purchase Form place theirs, so approving is done while looking at
                      // who else has signed. ?>
                <?php // A cleared cheque is posted to the ledger, so it cannot be removed
                      // from the register - it has to be reversed through the lifecycle.
                      if (empty($cheque->is_deleted) && (int) $cheque->status !== 4) { ?>
                <a href="<?php echo admin_url('accounting/cheque_management_delete/'.$cheque->id); ?>" class="btn btn-danger btn-sm _delete">
                  <i class="fa fa-trash"></i> Delete
                </a>
                <?php } ?>
              </div>
            </div>
            <hr>

            <div class="row">
              <!-- Left: Cheque Details -->
              <div class="col-md-6">
                <h5 class="bold mbot10"><i class="fa fa-info-circle"></i> Cheque Details</h5>
                <table class="table table-bordered table-condensed">
                  <tr><td class="bold" width="40%">Cheque Number</td><td><strong>#<?php echo htmlspecialchars($cheque->cheque_number); ?></strong></td></tr>
                  <tr><td class="bold">Bank Name</td><td><?php echo htmlspecialchars($cheque->cheque_bank_name ?? '-'); ?></td></tr>
                  <tr><td class="bold">Cheque Date</td><td><?php echo !empty($cheque->cheque_date) ? _d($cheque->cheque_date) : '-'; ?></td></tr>
                  <tr><td class="bold">Amount</td><td><strong class="text-success"><?php echo app_format_money($cheque->amount, $currency->name); ?></strong></td></tr>
                  <tr><td class="bold">Type</td><td><span class="label label-<?php echo $tc; ?>"><?php echo ucfirst($cheque->cheque_type); ?></span></td></tr>
                  <tr><td class="bold">Direction</td><td><?php echo ucfirst($cheque->direction); ?></td></tr>
                  <tr><td class="bold">Post-Dated</td><td><?php echo $cheque->is_pdc ? '<span class="label label-warning">Yes</span>' : 'No'; ?></td></tr>
                  <tr><td class="bold">Source</td><td><?php echo $src_map[$cheque->source_type] ?? ucfirst($cheque->source_type); ?></td></tr>
                  <tr><td class="bold">Attachment</td><td>
                    <?php if(!empty($cheque->cheque_attachment)): ?>
                      <a href="<?php echo admin_url('accounting/cheque_management_file/'.$cheque->id.'/'.$cheque->cheque_attachment); ?>" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-paperclip"></i> View</a>
                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                  </td></tr>
                </table>
              </div>

              <!-- Right: Party & References -->
              <div class="col-md-6">
                <h5 class="bold mbot10"><i class="fa fa-user"></i> Party & References</h5>
                <table class="table table-bordered table-condensed">
                  <tr><td class="bold" width="40%">Party</td><td><?php echo htmlspecialchars($cheque->rel_name ?? '-'); ?></td></tr>
                  <tr><td class="bold">Party Bank</td><td><?php echo htmlspecialchars($cheque->rel_bank ?? '-'); ?></td></tr>
                  <tr><td class="bold">Invoice Ref</td><td><?php echo htmlspecialchars($cheque->invoice_ref ?? '-'); ?></td></tr>
                  <tr><td class="bold">Transaction ID</td><td><?php echo htmlspecialchars($cheque->transaction_id ?? '-'); ?></td></tr>
                  <tr><td class="bold">Deposit Slip</td><td><?php echo htmlspecialchars($cheque->deposit_slip_no ?? '-'); ?></td></tr>
                  <tr><td class="bold">Batch Ref</td><td><?php echo htmlspecialchars($cheque->batch_ref ?? '-'); ?></td></tr>
                  <tr><td class="bold">Deposit Date</td><td><?php echo !empty($cheque->deposit_date) ? _d($cheque->deposit_date) : '-'; ?></td></tr>
                  <tr><td class="bold">Cleared Date</td><td><?php echo !empty($cheque->cleared_date) ? _d($cheque->cleared_date) : '-'; ?></td></tr>
                  <?php if($cheque->status == 5): ?>
                  <tr><td class="bold text-danger">Bounce Date</td><td><?php echo !empty($cheque->bounce_date) ? _d($cheque->bounce_date) : '-'; ?></td></tr>
                  <tr><td class="bold text-danger">Bounce Reason</td><td><?php echo htmlspecialchars($cheque->bounce_reason ?? '-'); ?></td></tr>
                  <tr><td class="bold text-danger">Bounce Charges</td><td><?php echo app_format_money($cheque->bounce_charges ?? 0, $currency->name); ?></td></tr>
                  <?php endif; ?>
                </table>
              </div>
            </div>

            <!-- Approval Info -->
            <div class="row mtop10">
              <div class="col-md-12">
                <h5 class="bold mbot10"><i class="fa fa-shield"></i> Approval (Maker-Checker)</h5>
                <table class="table table-bordered table-condensed">
                  <tr>
                    <td class="bold" width="20%">Maker</td><td><?php echo $cheque->maker_id ? get_staff_full_name($cheque->maker_id) : '-'; ?></td>
                    <td class="bold" width="20%">Checker</td><td><?php echo $cheque->checker_id ? get_staff_full_name($cheque->checker_id) : '-'; ?></td>
                    <td class="bold" width="20%">Approver</td><td><?php echo $cheque->approver_id ? get_staff_full_name($cheque->approver_id) : '-'; ?></td>
                    <td class="bold" width="15%">Status</td><td><span class="label label-<?php echo $ap[1]; ?>"><?php echo $ap[0]; ?></span></td>
                    <td class="bold" width="15%">Approved At</td><td><?php echo !empty($cheque->approved_at) ? _dt($cheque->approved_at) : '-'; ?></td>
                  </tr>
                </table>
              </div>
            </div>

            <?php if(!empty($cheque->notes)): ?>
            <div class="row mtop10">
              <div class="col-md-12">
                <h5 class="bold">Notes</h5>
                <div class="well well-sm"><?php echo nl2br(htmlspecialchars($cheque->notes)); ?></div>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Approval -->
        <?php
            // Laid out exactly as the Purchase Request, Purchase Order and Purchase Form
            // approval blocks are: one column per approver with their name, job position
            // and an approved or rejected stamp, then the actions underneath. The stamps
            // are the purchase module's, guarded so this page still renders if that module
            // is switched off.
            $approval_img = defined('PURCHASE_PATH') ? PURCHASE_PATH : '';

            if (!empty($flow['requires_approval'])) { ?>
        <div class="panel_s mtop15">
          <div class="panel-body">

            <p class="bold"><?php echo _l('acc_cheque_approval_infor'); ?></p>
            <hr class="hr-panel-heading" />

            <?php if (!$approval_chain) { ?>
            <div class="alert alert-warning noprint"><?php echo _l('acc_cheque_no_chain_configured'); ?></div>
            <?php } elseif (count($approval_steps) === 0) { ?>
            <p class="text-muted">
              <?php echo _l('acc_cheque_not_submitted_yet'); ?>
              <strong><?php echo e($approval_chain->name); ?></strong>
            </p>
            <?php } else { ?>
            <div class="row mbot15">
              <?php foreach ($approval_steps as $step) {
                  $step_staff    = trim((string) $step['staffid']);
                  $step_decision = (int) $step['approve'];
                  $step_is_mine  = $my_pending_step && (string) $my_pending_step['id'] === (string) $step['id'];

                  // Job position is a purchase module helper. Autoloaded, but guarded so
                  // this page cannot break if that module is disabled.
                  $step_position = function_exists('pur_get_job_position')
                      ? pur_get_job_position($step_staff)
                      : '';
                  ?>
              <div class="col-md-3 text-center">
                <p class="text-uppercase text-muted no-mtop bold"><?php echo e(get_staff_full_name($step_staff)); ?></p>
                <?php if (!empty($step_position)) { ?>
                <span class="text-muted"><?php echo e($step_position); ?></span><br />
                <?php } ?>

                <?php if ($step_decision === 2) { ?>
                  <?php if ($approval_img !== '') { ?>
                  <img src="<?php echo site_url($approval_img . 'approval/approved.png'); ?>" class="img_style" alt="<?php echo _l('acc_cheque_step_approved'); ?>" />
                  <?php } else { ?>
                  <span class="label label-success"><?php echo _l('acc_cheque_step_approved'); ?></span>
                  <?php } ?>
                <?php } elseif ($step_decision === 3) { ?>
                  <?php if ($approval_img !== '') { ?>
                  <img src="<?php echo site_url($approval_img . 'approval/rejected.png'); ?>" class="img_style" alt="<?php echo _l('acc_cheque_step_rejected'); ?>" />
                  <?php } else { ?>
                  <span class="label label-danger"><?php echo _l('acc_cheque_step_rejected'); ?></span>
                  <?php } ?>
                <?php } elseif ($step_is_mine) { ?>
                <br /><span class="label label-info"><?php echo _l('acc_cheque_awaiting_this_approver'); ?></span>
                <?php } else { ?>
                <br /><span class="label label-warning"><?php echo _l('acc_cheque_step_pending'); ?></span>
                <?php } ?>

                <?php if (!empty($step['note'])) { ?>
                <p class="mtop10"><?php echo e($step['note']); ?></p>
                <?php } ?>
                <?php if (!empty($step['date'])) { ?>
                <p class="bold text-<?php echo $step_decision === 2 ? 'success' : ($step_decision === 3 ? 'danger' : 'muted'); ?>">
                  <?php echo _dt($step['date']); ?>
                </p>
                <?php } ?>
              </div>
              <?php } ?>
            </div>
            <div class="clearfix"></div>
            <?php } ?>

            <?php // The actions sit with the approval block, as they do on a Purchase
                  // Request and a Purchase Form, rather than up in the document header. ?>
            <div class="noprint">
              <?php if ($can_send_approval) { ?>
              <button class="btn btn-success" onclick="cheque_send_for_approval(<?php echo $cheque->id; ?>); return false;">
                <i class="fa fa-paper-plane"></i> <?php echo _l('acc_cheque_send_for_approval_btn'); ?>
              </button>
              <?php } ?>

              <?php // The same Approve dropdown a Purchase Request and a Purchase Form use:
                    // a remark box with Approve and Reject inside it, so a decision is always
                    // recorded with its reason. It replaces a JS prompt(), which no other
                    // approval in the system uses and which cannot be styled or validated. ?>
              <?php if ($my_pending_step) { ?>
              <div class="btn-group">
                <a href="#" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <?php echo _l('acc_cheque_approve_btn'); ?><span class="caret"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-right ul_style">
                  <li>
                    <div class="col-md-12">
                      <?php echo render_textarea('cheque_approval_note', 'acc_cheque_approval_note'); ?>
                    </div>
                  </li>
                  <li>
                    <div class="row text-right col-md-12 pad_right_0 mbot15">
                      <a href="#" class="btn btn-success mright15" data-loading-text="<?php echo _l('wait_text'); ?>"
                         onclick="cheque_decide(<?php echo $cheque->id; ?>,'approve'); return false;">
                        <?php echo _l('acc_cheque_approve_btn'); ?>
                      </a>
                      <a href="#" class="btn btn-warning" data-loading-text="<?php echo _l('wait_text'); ?>"
                         onclick="cheque_decide(<?php echo $cheque->id; ?>,'reject'); return false;">
                        <?php echo _l('acc_cheque_reject_btn'); ?>
                      </a>
                    </div>
                  </li>
                </ul>
              </div>
              <?php } elseif (count($approval_steps) > 0 && (int) $cheque->approval_status === 1) { ?>
              <?php // Names who it is waiting on rather than just showing no button ?>
              <p class="text-muted no-mbot">
                <i class="fa fa-clock-o"></i>
                <?php
                    $waiting = [];
                    foreach ($approval_steps as $step) {
                        if ($step['approve'] === null || $step['approve'] === '') {
                            $waiting[] = get_staff_full_name(trim((string) $step['staffid']));
                        }
                    }
                    echo _l('acc_cheque_awaiting_from') . ' ' . e(implode(', ', $waiting));
                ?>
              </p>
              <?php } ?>
            </div>
          </div>
        </div>
        <?php } ?>

        <div class="mtop10">
          <a href="<?php echo admin_url('accounting/cheque_management'); ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back</a>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="cheque-status-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title">Update Status</h4>
    </div>
    <div class="modal-body">
      <input type="hidden" id="status_cheque_id">
      <div class="form-group">
        <label>New Status</label>
        <?php // Only the steps the lifecycle will actually accept from where this cheque
              // stands. The old fixed 1..10 list let anyone pick Cleared from Prepared and
              // learn it was refused only after posting, and offered statuses that belong
              // to the incoming flow entirely.
              //
              // A step the guard currently refuses - Issued before approval - is shown
              // disabled with the reason, rather than hidden, so the gate is visible. ?>
        <select id="new_cheque_status" class="selectpicker form-control" data-width="100%">
          <?php foreach ($next_statuses as $code => $meta) { ?>
          <option value="<?php echo (int) $code; ?>"<?php echo $meta['allowed'] ? '' : ' disabled'; ?>
                  title="<?php echo e($meta['reason']); ?>">
            <?php echo e($meta['label']); ?><?php echo $meta['allowed'] ? '' : ' — ' . e($meta['reason']); ?>
          </option>
          <?php } ?>
        </select>
      </div>
      <div id="bounce_fields" style="display:none;">
        <div class="form-group"><label>Bounce Reason</label><input type="text" id="bounce_reason" class="form-control"></div>
        <div class="form-group"><label>Bounce Charges</label><input type="number" id="bounce_charges" class="form-control" step="0.01" value="0"></div>
      </div>
      <div id="deposit_fields" style="display:none;">
        <div class="form-group"><label>Deposit Slip No.</label><input type="text" id="deposit_slip_no" class="form-control"></div>
      </div>
      <div id="cleared_fields" style="display:none;">
        <div class="form-group"><label>Cleared Date</label><input type="text" id="cleared_date_input" class="form-control datepicker"></div>
      </div>
      <div class="form-group"><label>Notes</label><textarea id="status_notes" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-info" onclick="save_cheque_status(); return false;">Update</button>
    </div>
  </div></div>
</div>

<?php init_tail(); ?>
<script>
function open_status_modal(id, current_status){
  "use strict";
  $('#status_cheque_id').val(id);
  // Not preselected to the current status any more: the dropdown now holds only the steps
  // ahead of this cheque, so the current status is not in it.
  $('#new_cheque_status').selectpicker('refresh');
  $('#bounce_fields,#deposit_fields,#cleared_fields').hide();
  $('#cheque-status-modal').modal('show');
}
$('#new_cheque_status').on('changed.bs.select change', function(){
  var v = $(this).val();
  $('#bounce_fields').toggle(v=='5');
  $('#deposit_fields').toggle(v=='3');
  $('#cleared_fields').toggle(v=='4');
});
function save_cheque_status(){
  "use strict";
  var id = $('#status_cheque_id').val();
  $.post(admin_url+'accounting/cheque_management_status/'+id, {
    status: $('#new_cheque_status').val(),
    notes: $('#status_notes').val(),
    bounce_reason: $('#bounce_reason').val(),
    bounce_charges: $('#bounce_charges').val(),
    deposit_slip_no: $('#deposit_slip_no').val(),
    cleared_date: $('#cleared_date_input').val()
  }).done(function(resp){
    resp = JSON.parse(resp);
    alert_float(resp.success?'success':'danger', resp.message);
    if(resp.success){ $('#cheque-status-modal').modal('hide'); setTimeout(function(){ location.reload(); },800); }
  });
}
// Sends the cheque to the chain configured in Accounting > Settings > Approval Settings.
// The chain is snapshotted onto the cheque, so editing the settings later cannot swap the
// approvers underneath a cheque already in flight.
function cheque_send_for_approval(id){
  "use strict";
  $('body').append('<div class="dt-loader"></div>');
  $.post(admin_url+'accounting/cheque_send_for_approval/'+id).done(function(resp){
    $('body').find('.dt-loader').remove();
    resp = JSON.parse(resp);
    alert_float(resp.success?'success':'danger', resp.message);
    if(resp.success) setTimeout(function(){ location.reload(); },800);
  }).fail(function(){
    $('body').find('.dt-loader').remove();
    alert_float('danger','Request failed');
  });
}

// One approver's decision. The server checks that the caller owns an outstanding step, so
// these buttons carry no authority of their own - replaces approve_cheque(), which posted
// to the old maker-checker endpoint and wrote approval codes the lifecycle misreads.
function cheque_decide(id, decision){
  "use strict";
  // Read from the remark box in the dropdown, the same way the Purchase Request approval
  // reads its reason textarea
  var note = $('textarea[name="cheque_approval_note"]').val() || '';

  // A rejection without a reason is not useful to whoever raised the cheque
  if(decision === 'reject' && note.trim() === ''){
    alert_float('warning', '<?php echo _l('acc_cheque_reject_needs_note'); ?>');
    return;
  }

  $('body').append('<div class="dt-loader"></div>');
  $.post(admin_url+'accounting/cheque_approval_decision/'+id, {decision:decision, note:note}).done(function(resp){
    $('body').find('.dt-loader').remove();
    resp = JSON.parse(resp);
    alert_float(resp.success?'success':'danger', resp.message);
    if(resp.success) setTimeout(function(){ location.reload(); },800);
  }).fail(function(){
    $('body').find('.dt-loader').remove();
    alert_float('danger','Request failed');
  });
}
</script>
</body>
</html>
