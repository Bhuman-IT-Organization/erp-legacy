<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="batch-payment-modal">
    <div class="modal-dialog modal-xxl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <?= _l('add_batch_payments') ?>
                </h4>
            </div>
            <?= form_open_multipart('admin/payments/add_batch_payment', ['id' => 'batch-payment-form']); ?>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group select-placeholder">
                            <select id="batch-payment-filter" class="selectpicker" name="client_filter"
                                data-width="100%"
                                data-none-selected-text="<?= _l('batch_payment_filter_by_customer') ?>">
                                <option value=""></option>
                                <?php foreach ($customers as $customer) { ?>
                                <option value="<?= e($customer->userid); ?>">
                                    <?= e($customer->company); ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-12">

                        <!-- ── Shared Cheque Details (shown when any row selects Cheque) ── -->
                        <div id="sales_batch_cheque_section" style="display:none; background:#f0f7ff; border:1px solid #b8d4f0; border-radius:4px; padding:14px; margin-bottom:14px;">
                            <h5 class="no-margin mbot10 bold"><i class="fa fa-money"></i> Cheque Details <small class="text-muted">(applies to all cheque line items)</small></h5>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label class="control-label"><span class="text-danger">* </span>Bank Name</label>
                                        <input type="text" id="sales_batch_cheque_bank_name" class="form-control" placeholder="Bank Name" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label class="control-label"><span class="text-danger">* </span>Cheque Number</label>
                                        <input type="text" id="sales_batch_cheque_number" class="form-control" placeholder="Cheque Number" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label class="control-label"><span class="text-danger">* </span>Cheque Date</label>
                                        <div class="input-group date">
                                            <input type="text" id="sales_batch_cheque_date" class="form-control datepicker" placeholder="Cheque Date" autocomplete="off">
                                            <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="control-label">Cheque Attachment</label>
                                        <input type="file" id="sales_batch_cheque_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.gif">
                                        <small class="text-muted">Upload soft copy of cheque (JPG, PNG, PDF)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><strong><?= _l('batch_payments_table_invoice_number_heading'); ?> #</strong></th>
                                        <th><strong><?= _l('batch_payments_table_payment_date_heading'); ?></strong></th>
                                        <th><strong><?= _l('batch_payments_table_payment_mode_heading'); ?></strong></th>
                                        <th><strong><?= _l('batch_payments_table_transaction_id_heading'); ?></strong></th>
                                        <th><strong><?= _l('batch_payments_table_amount_received_heading'); ?></strong></th>
                                        <th class="text-right"><strong><?= _l('batch_payments_table_invoice_balance_due'); ?></strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $index => $invoice) { ?>
                                    <tr class="batch_payment_item"
                                        data-clientid="<?= e($invoice->clientid); ?>"
                                        data-invoiceId="<?= e($invoice->id) ?>">
                                        <td>
                                            <a href="<?= admin_url('invoices/list_invoices/' . e($invoice->id)); ?>" target="_blank">
                                                <?= e(format_invoice_number($invoice->id)) ?>
                                            </a><br>
                                            <a class="text-dark" href="<?= admin_url('clients/client/' . e($invoice->clientid)); ?>" target="_blank">
                                                <?= e($invoice->company) ?>
                                            </a>
                                            <input type="hidden" name="invoice[<?= $index ?>][invoiceid]" value="<?= $invoice->id ?>">
                                        </td>
                                        <td class="tw-w-48">
                                            <?= render_date_input('invoice[' . $index . '][date]', '', date(get_current_date_format(true))) ?>
                                        </td>
                                        <td class="tw-w-56">
                                            <div class="form-group">
                                                <select class="selectpicker batch-sales-paymentmode"
                                                    name="invoice[<?= $index ?>][paymentmode]"
                                                    data-row-index="<?= $index ?>"
                                                    data-width="100%" data-none-selected-text="-">
                                                    <option></option>
                                                    <?php foreach ($invoice->allowed_payment_modes as $mode) { ?>
                                                    <option value="<?= e($mode->id); ?>"
                                                        data-name="<?= strtolower(e($mode->name)); ?>">
                                                        <?= e($mode->name); ?>
                                                    </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </td>
                                        <td><?= render_input('invoice[' . $index . '][transactionid]') ?></td>
                                        <td><?= render_input('invoice[' . $index . '][amount]', '', '', 'number', ['max' => $invoice->total_left_to_pay]) ?></td>
                                        <td class="text-right">
                                            <?= e(app_format_money($invoice->total_left_to_pay, $invoice->currency)) ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-sm-12 row">
                            <div class="checkbox">
                                <input type="checkbox" name="do_not_send_invoice_payment_recorded" value="1"
                                    id="do_not_send_invoice_payment_recorded">
                                <label for="do_not_send_invoice_payment_recorded">
                                    <?= _l('batch_payments_send_invoice_payment_recorded'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default close_btn" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="button" id="sales_batch_submit_btn" class="btn btn-primary"><?= _l('apply'); ?></button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script>
$(function(){
    "use strict";

    // ── Check if any visible row has cheque mode ──────────────────────────────
    function checkAnyChequeRow() {
        var hasCheque = false;
        $('.batch_payment_item:visible .batch-sales-paymentmode').each(function(){
            var val  = $(this).val();
            var name = $(this).find('option[value="' + val + '"]').data('name') || '';
            if(name.indexOf('cheque') !== -1 || name.indexOf('check') !== -1){
                hasCheque = true;
                return false;
            }
        });
        if(hasCheque){
            $('#sales_batch_cheque_section').slideDown(200, function(){
                if(typeof init_datepicker === 'function') init_datepicker();
            });
        } else {
            $('#sales_batch_cheque_section').slideUp(200);
        }
    }

    // ── Payment mode change — sync to ALL visible rows ───────────────────────
    // Use namespace to allow clean re-registration on modal reload
    $(document).off('changed.bs.select.salesbatch change.salesbatch', '.batch-sales-paymentmode');
    $(document).on('changed.bs.select.salesbatch change.salesbatch', '.batch-sales-paymentmode', function(){
        if($(document).data('salesBatchSyncing')) return;
        $(document).data('salesBatchSyncing', true);

        var val = $(this).val();
        var $src = $(this);

        $('.batch_payment_item:visible .batch-sales-paymentmode').each(function(){
            if($(this).is($src)) return;
            $(this).val(val);
            try { $(this).selectpicker('val', val); } catch(ex){}
        });

        $(document).data('salesBatchSyncing', false);
        checkAnyChequeRow();
    });

    // ── Customer filter ───────────────────────────────────────────────────────
    $('#batch-payment-filter').on('changed.bs.select change', function(){
        var clientId = $(this).val();
        if(!clientId){
            $('.batch_payment_item').show();
        } else {
            $('.batch_payment_item').hide();
            $('.batch_payment_item[data-clientid="' + clientId + '"]').show();
        }
        checkAnyChequeRow();
    });

    // ── Reset when modal opens ────────────────────────────────────────────────
    $('#batch-payment-modal').on('show.bs.modal', function(){
        $('#sales_batch_cheque_section').hide();
        $('#sales_batch_cheque_bank_name, #sales_batch_cheque_number, #sales_batch_cheque_date').val('');
        $('#sales_batch_cheque_attachment').val('');
    });

    // ── Submit via AJAX + FormData ────────────────────────────────────────────
    $(document).on('click', '#sales_batch_submit_btn', function(e){
        e.preventDefault();
        var $btn = $(this);

        var bank     = $.trim($('#sales_batch_cheque_bank_name').val());
        var number   = $.trim($('#sales_batch_cheque_number').val());
        var cdate    = $.trim($('#sales_batch_cheque_date').val());
        var fileInput = document.getElementById('sales_batch_cheque_attachment');
        var hasFile  = fileInput && fileInput.files && fileInput.files.length > 0;

        // Check if any visible row is cheque mode
        var hasChequeRow = false;
        $('.batch_payment_item:visible').each(function(){
            var $sel  = $(this).find('.batch-sales-paymentmode');
            var val   = $sel.val();
            if(!val){ try{ val = $sel.selectpicker('val'); }catch(ex){} }
            var name  = $sel.find('option[value="' + val + '"]').data('name') || '';
            if(name.indexOf('cheque') !== -1 || name.indexOf('check') !== -1){
                hasChequeRow = true;
            }
        });

        // Validate cheque fields
        if(hasChequeRow){
            var errors = [];
            if(!bank)   errors.push('Bank Name');
            if(!number) errors.push('Cheque Number');
            if(!cdate)  errors.push('Cheque Date');
            if(errors.length > 0){
                $('#sales_batch_cheque_section').css({'border-color':'#e74c3c','background':'#fff5f5'});
                setTimeout(function(){ $('#sales_batch_cheque_section').css({'border-color':'#b8d4f0','background':'#f0f7ff'}); }, 3000);
                alert_float('danger', 'Required: ' + errors.join(', '));
                return false;
            }
        }

        // Build FormData
        var fd = new FormData();

        // CSRF + hidden inputs
        $('#batch-payment-form').find('input[type="hidden"]').each(function(){
            var n = $(this).attr('name');
            if(n) fd.append(n, $(this).val());
        });

        // do_not_send checkbox
        if($('#do_not_send_invoice_payment_recorded').is(':checked')){
            fd.append('do_not_send_invoice_payment_recorded', '1');
        }

        // Shared cheque attachment
        if(hasChequeRow && hasFile){
            fd.append('shared_cheque_attachment', fileInput.files[0]);
        }

        // Per-row data
        var rowIndex = 0;
        $('.batch_payment_item:visible').each(function(){
            var $row     = $(this);
            var invoiceId = $row.data('invoiceid');
            var amount   = $.trim($row.find('input[name$="[amount]"]').val());
            if(!amount) return; // skip empty rows

            // Read paymentmode reliably
            var $sel  = $row.find('.batch-sales-paymentmode');
            var pmVal = $sel.val();
            if(!pmVal){ try{ pmVal = $sel.selectpicker('val'); }catch(ex){} }
            if(!pmVal){
                var btnTxt = $row.find('.bootstrap-select button .filter-option-inner-inner').text().trim();
                $sel.find('option').each(function(){
                    if($(this).text().trim() === btnTxt){ pmVal = $(this).val(); return false; }
                });
            }

            var pmName   = $sel.find('option[value="' + pmVal + '"]').data('name') || '';
            var isCheque = (pmName.indexOf('cheque') !== -1 || pmName.indexOf('check') !== -1);
            var dateVal  = $.trim($row.find('input[name$="[date]"]').val());
            var txnid    = $.trim($row.find('input[name$="[transactionid]"]').val());

            var prefix = 'invoice[' + rowIndex + ']';
            fd.append(prefix + '[invoiceid]',     invoiceId);
            fd.append(prefix + '[amount]',        amount);
            fd.append(prefix + '[date]',          dateVal);
            fd.append(prefix + '[paymentmode]',   pmVal || '');
            fd.append(prefix + '[transactionid]', txnid);
            fd.append(prefix + '[is_cheque_row]', isCheque ? '1' : '0');

            if(isCheque){
                fd.append(prefix + '[cheque_bank_name]', bank);
                fd.append(prefix + '[cheque_number]',    number);
                fd.append(prefix + '[cheque_date]',      cdate);
            }

            rowIndex++;
        });

        if(rowIndex === 0){
            alert_float('danger', 'Please fill in at least one payment amount.');
            return false;
        }

        $btn.prop('disabled', true).text('<?= _l('wait_text'); ?>');

        $.ajax({
            url:         $('#batch-payment-form').attr('action'),
            type:        'POST',
            data:        fd,
            processData: false,
            contentType: false,
            success: function(response){
                try {
                    var res = (typeof response === 'string') ? JSON.parse(response) : response;
                    if(res.success){
                        alert_float('success', res.message || '<?= _l('add_batch_payments'); ?>');
                        $('#batch-payment-modal').modal('hide');
                        setTimeout(function(){ window.location.reload(); }, 800);
                    } else {
                        $btn.prop('disabled', false).text('<?= _l('apply'); ?>');
                        alert_float('danger', res.message || 'Failed to add batch payments.');
                    }
                } catch(ex){
                    // Redirect response — treat as success
                    alert_float('success', '<?= _l('add_batch_payments'); ?>');
                    $('#batch-payment-modal').modal('hide');
                    setTimeout(function(){ window.location.reload(); }, 800);
                }
            },
            error: function(xhr){
                $btn.prop('disabled', false).text('<?= _l('apply'); ?>');
                alert_float('danger', 'Error submitting batch payment (HTTP ' + xhr.status + '). Please try again.');
            }
        });
    });
});
</script>
