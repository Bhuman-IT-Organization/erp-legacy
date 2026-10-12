<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">

        <!-- Summary Cards -->
        <div class="row mbot15" id="cheque-summary-cards">
          <?php
          // The outgoing lifecycle, which is what this register holds. The cards used to
          // show Received / Verified / Deposited / Post-Dated - all statuses of the
          // INCOMING flow - so every card read 0 no matter how many cheques existed.
          $card_statuses = [12=>'Prepared',8=>'Issued',13=>'Presented',4=>'Cleared',14=>'Returned',6=>'Cancelled'];
          $card_classes  = [12=>'default',8=>'primary',13=>'warning',4=>'success',14=>'danger',6=>'default'];
          foreach($card_statuses as $sid => $slabel): ?>
          <div class="col-md-2">
            <div class="panel_s">
              <div class="panel-body text-center">
                <h4 class="no-margin bold text-<?php echo $card_classes[$sid]; ?>"><?php echo $slabel; ?></h4>
                <h3 class="no-margin bold cheque-count-<?php echo $sid; ?>">-</h3>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="panel_s">
          <div class="panel-body">
            <div class="row mbot10">
              <div class="col-md-6">
                <h4 class="no-margin font-bold"><i class="fa fa-check-square-o"></i> Cheque Management</h4>
              </div>
              <div class="col-md-6 text-right">
                <a href="<?php echo admin_url('accounting/cheque_management_form'); ?>" class="btn btn-info mright5">
                  <i class="fa fa-plus"></i> Add Cheque
                </a>
                <a href="<?php echo admin_url('accounting/cheque_management_reports'); ?>" class="btn btn-default mright5">
                  <i class="fa fa-bar-chart"></i> Reports
                </a>
                <button type="button" class="btn btn-default" onclick="sync_cheques(); return false;">
                  <i class="fa fa-refresh"></i> Sync from Payments
                </button>
              </div>
            </div>

            <!-- Filters -->
            <div class="row mbot10">
              <div class="col-md-2">
                <select id="cheque_type_ft" class="selectpicker" data-width="100%" data-none-selected-text="All Types">
                  <option value=""></option>
                  <option value="incoming">Incoming (Customer)</option>
                  <option value="outgoing">Outgoing (Vendor)</option>
                </select>
              </div>
              <div class="col-md-2">
                <select id="direction_ft" class="selectpicker" data-width="100%" data-none-selected-text="All Parties">
                  <option value=""></option>
                  <option value="customer">Customer Cheques</option>
                  <option value="vendor">Vendor Cheques</option>
                </select>
              </div>
              <div class="col-md-2">
                <?php // From the status map, so the filter cannot fall behind the lifecycle
                      // the way the hardcoded 1..10 list did ?>
                <select id="status_ft" class="selectpicker" data-width="100%" data-none-selected-text="All Statuses">
                  <option value=""></option>
                  <?php foreach($status_map as $s_id => $s_info){ ?>
                  <option value="<?php echo (int) $s_id; ?>"><?php echo e($s_info[0]); ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="col-md-1">
                <select id="is_pdc_ft" class="selectpicker" data-width="100%" data-none-selected-text="PDC?">
                  <option value=""></option>
                  <option value="1">PDC Only</option>
                  <option value="0">Non-PDC</option>
                </select>
              </div>
              <div class="col-md-1">
                <?php // Deleting only flags a cheque, so this is how the removed ones are
                      // found again in order to restore them ?>
                <select id="show_deleted_ft" class="selectpicker" data-width="100%" data-none-selected-text="Active">
                  <option value="0"><?php echo _l('acc_cheque_filter_active'); ?></option>
                  <option value="1"><?php echo _l('acc_cheque_filter_deleted'); ?></option>
                </select>
              </div>
              <div class="col-md-2">
                <?php echo render_date_input('from_date_ft','','',['placeholder'=>'From Date','id'=>'from_date_ft']); ?>
              </div>
              <div class="col-md-2">
                <?php echo render_date_input('to_date_ft','','',['placeholder'=>'To Date','id'=>'to_date_ft']); ?>
              </div>
            </div>

            <!-- DataTable -->
            <?php render_datatable([
              'Cheque #','Type','Direction','Party','Bank','Cheque Date','Amount','Invoice Ref','Status','PDC','Approval'
            ], 'cheque_management'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="cheque-status-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title"><i class="fa fa-refresh"></i> Update Cheque Status</h4>
      </div>
      <div class="modal-body">
        <input type="hidden" id="status_cheque_id">

        <?php // Filled from accounting/cheque_allowed_transitions when the modal opens, so
              // it shows this cheque's approval state and the send-for-approval action
              // right where the status is being changed - the gate was previously invisible
              // here and Issued simply failed. ?>
        <div id="cheque_approval_area"></div>

        <div class="form-group">
          <label>New Status <span class="text-danger">*</span></label>
          <?php // Populated with only the steps the lifecycle accepts from where this
                // cheque stands. The old fixed 1..10 list offered incoming-flow statuses
                // and let Cleared be picked straight from Prepared. ?>
          <select id="new_cheque_status" class="selectpicker form-control" data-width="100%"></select>
        </div>
        <div id="bounce_fields" style="display:none;">
          <div class="form-group">
            <label>Bounce Reason</label>
            <input type="text" id="bounce_reason" class="form-control" placeholder="e.g. Insufficient funds">
          </div>
          <div class="form-group">
            <label>Bounce Charges</label>
            <input type="number" id="bounce_charges" class="form-control" step="0.01" value="0">
          </div>
        </div>
        <div id="deposit_fields" style="display:none;">
          <div class="form-group">
            <label>Deposit Slip No.</label>
            <input type="text" id="deposit_slip_no" class="form-control">
          </div>
        </div>
        <div id="cleared_fields" style="display:none;">
          <div class="form-group">
            <label>Cleared Date</label>
            <input type="text" id="cleared_date_input" class="form-control datepicker">
          </div>
        </div>
        <div class="form-group">
          <label>Notes</label>
          <textarea id="status_notes" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-info" onclick="save_cheque_status(); return false;">Update Status</button>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
$(function(){
  "use strict";
  initDataTable('.table-cheque_management', admin_url + 'accounting/cheque_management_table', undefined, undefined, {
    'cheque_type_ft': '#cheque_type_ft',
    'direction_ft': '#direction_ft',
    'status_ft': '#status_ft',
    'is_pdc_ft': '#is_pdc_ft',
    'show_deleted_ft': '#show_deleted_ft',
    'from_date_ft': '#from_date_ft',
    'to_date_ft': '#to_date_ft'
  }, [5, 'desc']);

  $('#cheque_type_ft, #direction_ft, #status_ft, #is_pdc_ft, #show_deleted_ft').on('changed.bs.select change', function(){
    $('.table-cheque_management').DataTable().ajax.reload();
  });
  $('#from_date_ft, #to_date_ft').on('change', function(){
    $('.table-cheque_management').DataTable().ajax.reload();
  });

  // Show/hide conditional fields in status modal
  $('#new_cheque_status').on('changed.bs.select change', function(){
    var v = $(this).val();
    $('#bounce_fields').toggle(v == '5');
    $('#deposit_fields').toggle(v == '3');
    $('#cleared_fields').toggle(v == '4');
  });

  // Load summary counts
  load_summary_counts();
});

function load_summary_counts(){
  "use strict";
  $.get(admin_url + 'accounting/cheque_management_counts').done(function(resp){
    try {
      var d = JSON.parse(resp);
      for(var sid in d){
        $('.cheque-count-' + sid).text(d[sid]);
      }
    } catch(e){}
  });
}

function open_status_modal(id, current_status){
  "use strict";
  $('#status_cheque_id').val(id);
  $('#bounce_fields, #deposit_fields, #cleared_fields').hide();
  $('#bounce_reason, #bounce_charges, #deposit_slip_no, #cleared_date_input, #status_notes').val('');
  $('#new_cheque_status').empty().selectpicker('refresh');
  $('#cheque_approval_area').html('<p class="text-muted">Loading…</p>');
  $('#cheque-status-modal').modal('show');

  // The steps and the approval state come from the server, from the same rules it
  // enforces, so the modal cannot offer something that will be refused.
  $.get(admin_url + 'accounting/cheque_allowed_transitions/' + id).done(function(resp){
    resp = (typeof resp === 'string') ? JSON.parse(resp) : resp;

    if(!resp.success){
      $('#cheque_approval_area').html('<div class="alert alert-danger">' + (resp.message || 'Error') + '</div>');
      return;
    }

    // ── Approval state ──
    var html = '';

    if(resp.requires_approval){
      if(resp.approval_status == 2){
        html = '<div class="alert alert-success">Approved — this cheque may be issued.</div>';
      } else if(resp.approval_status == 3){
        html = '<div class="alert alert-danger">Approval was rejected.</div>';
      } else if(resp.can_send_approval){
        html = '<div class="alert alert-warning">'
             + 'This cheque has not been sent for approval yet, and cannot be issued until it is approved.'
             + ' <button type="button" class="btn btn-success btn-sm mtop5" onclick="cheque_send_for_approval(' + id + ');">'
             + 'Send for approval</button></div>';
      } else if(resp.approval_status == 1){
        var waiting = [];
        $.each(resp.steps || [], function(i, s){
          if(s.approve === null || s.approve === '' ){ waiting.push(s.staffid); }
        });
        html = '<div class="alert alert-info">Awaiting approval'
             + (waiting.length ? ' (' + waiting.length + ' approver(s) outstanding)' : '')
             + '. Approve it from the cheque page.</div>';
      }
    }

    $('#cheque_approval_area').html(html);

    // ── Legal next steps only ──
    var options = '';
    $.each(resp.next || {}, function(code, meta){
      options += '<option value="' + code + '"' + (meta.allowed ? '' : ' disabled')
               + '>' + meta.label + (meta.allowed ? '' : ' — ' + meta.reason) + '</option>';
    });

    if(options === ''){
      options = '<option value="">No further steps available</option>';
    }

    $('#new_cheque_status').html(options).selectpicker('refresh').trigger('change');
  }).fail(function(){
    $('#cheque_approval_area').html('<div class="alert alert-danger">Could not load the cheque lifecycle.</div>');
  });
}

// Sends the cheque to the chain configured in Accounting > Settings > Approval Settings
function cheque_send_for_approval(id){
  "use strict";
  $('body').append('<div class="dt-loader"></div>');
  $.post(admin_url + 'accounting/cheque_send_for_approval/' + id).done(function(resp){
    $('body').find('.dt-loader').remove();
    resp = JSON.parse(resp);
    alert_float(resp.success ? 'success' : 'danger', resp.message);
    if(resp.success){
      $('#cheque-status-modal').modal('hide');
      $('.table-cheque_management').DataTable().ajax.reload();
      load_summary_counts();
    }
  }).fail(function(){
    $('body').find('.dt-loader').remove();
    alert_float('danger','Request failed');
  });
}

function save_cheque_status(){
  "use strict";
  var id = $('#status_cheque_id').val();
  var data = {
    status: $('#new_cheque_status').val(),
    notes: $('#status_notes').val(),
    bounce_reason: $('#bounce_reason').val(),
    bounce_charges: $('#bounce_charges').val(),
    deposit_slip_no: $('#deposit_slip_no').val(),
    cleared_date: $('#cleared_date_input').val()
  };
  $('body').append('<div class="dt-loader"></div>');
  $.post(admin_url + 'accounting/cheque_management_status/' + id, data).done(function(resp){
    $('body').find('.dt-loader').remove();
    resp = JSON.parse(resp);
    if(resp.success){
      alert_float('success', resp.message);
      $('#cheque-status-modal').modal('hide');
      $('.table-cheque_management').DataTable().ajax.reload();
      load_summary_counts();
    } else {
      alert_float('danger', resp.message || 'Error');
    }
  });
}

function sync_cheques(){
  "use strict";
  $('body').append('<div class="dt-loader"></div>');
  $.post(admin_url + 'accounting/cheque_management_sync').done(function(resp){
    $('body').find('.dt-loader').remove();
    resp = JSON.parse(resp);
    alert_float(resp.success ? 'success' : 'danger', resp.message);
    if(resp.success) { $('.table-cheque_management').DataTable().ajax.reload(); load_summary_counts(); }
  }).fail(function(){ $('body').find('.dt-loader').remove(); alert_float('danger','Sync failed'); });
}
</script>
</body>
</html>
