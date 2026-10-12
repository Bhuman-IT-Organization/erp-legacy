<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
// Prepare debit notes JSON for JavaScript
$vendor_debit_notes_js = [];
if(isset($vendor_debit_notes)){
    foreach($vendor_debit_notes as $vid => $notes){
        $vendor_debit_notes_js[$vid] = [];
        foreach($notes as $dn){
            $vendor_debit_notes_js[$vid][] = [
                'id' => $dn->id,
                'number' => $dn->formatted_number,
                'remaining' => (float)$dn->remaining,
            ];
        }
    }
}

// Flat, vendor-labelled list of every open debit note in this batch.
// The selector is rendered server side from this and grouped by vendor, so the
// notes are visible the moment the modal opens instead of only after the vendor
// filter has been used.
$debit_notes_grouped = [];
if(isset($vendor_debit_notes) && isset($vendors)){
    $vendor_names = [];
    foreach($vendors as $v){
        $vendor_names[$v->userid] = $v->company;
    }
    foreach($vendor_debit_notes as $vid => $notes){
        foreach($notes as $dn){
            $label = $vendor_names[$vid] ?? ('#' . $vid);
            $debit_notes_grouped[$label][] = [
                'id'        => $dn->id,
                'number'    => $dn->formatted_number,
                'remaining' => (float) $dn->remaining,
                'vendorid'  => $vid,
            ];
        }
    }
    ksort($debit_notes_grouped);
}

$has_any_debit_note = count($debit_notes_grouped) > 0;
?>
<div class="modal fade" id="batch-payment-modal">
    <div class="modal-dialog modal-xxl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('add_batch_payments'); ?></h4>
            </div>
            <?php echo form_open_multipart('admin/purchase/add_batch_payment', ['id' => 'batch-payment-form']); ?>
            <div class="modal-body">

                <!-- Vendor filter -->
                <div class="row mbot10">
                    <div class="col-sm-4">
                        <div class="form-group select-placeholder">
                            <select id="batch-payment-filter" class="selectpicker" name="client_filter"
                                data-width="100%"
                                data-none-selected-text="<?php echo _l('batch_payment_filter_by_vendor'); ?>">
                                <option value=""></option>
                                <?php foreach ($vendors as $vendor) { ?>
                                <option value="<?php echo $vendor->userid; ?>"><?php echo $vendor->company; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Shared Cheque Details Section (shown once, hidden until cheque mode selected) -->
                <div id="batch_shared_cheque_section" style="display:none; background:#f0f7ff; border:1px solid #b8d4f0; border-radius:4px; padding:14px; margin-bottom:14px;">
                    <h5 class="no-margin mbot10 bold"><i class="fa fa-money"></i> <?php echo _l('cheque_details'); ?> <small class="text-muted">(<?php echo _l('applies_to_all_cheque_payments'); ?>)</small></h5>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label"><span class="text-danger">* </span><?php echo _l('cheque_bank_name'); ?></label>
                                <input type="text" id="shared_cheque_bank_name" class="form-control" placeholder="<?php echo _l('cheque_bank_name'); ?>">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label"><span class="text-danger">* </span><?php echo _l('cheque_number'); ?></label>
                                <input type="text" id="shared_cheque_number" class="form-control" placeholder="<?php echo _l('cheque_number'); ?>">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label"><span class="text-danger">* </span><?php echo _l('cheque_date'); ?></label>
                                <div class="input-group date">
                                    <input type="text" id="shared_cheque_date" class="form-control datepicker" placeholder="<?php echo _l('cheque_date'); ?>" autocomplete="off">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label"><span class="text-danger">* </span><?php echo _l('cheque_attachment'); ?></label>
                                <input type="file" name="shared_cheque_attachment" id="shared_cheque_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.gif">
                                <small class="text-muted"><?php echo _l('cheque_attachment_hint'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shared Debit Note Section (shown once, only after a vendor is chosen) -->
                <!-- A debit note belongs to one vendor, so it can only be applied once the
                     list is narrowed to that vendor. Options are filled in by JS from the
                     per-vendor map below. -->
                <div id="batch_shared_debitnote_section" style="<?php echo $has_any_debit_note ? '' : 'display:none; '; ?>background:#fff8ef; border:1px solid #f0d9b8; border-radius:4px; padding:14px; margin-bottom:14px;">
                    <h5 class="no-margin mbot10 bold">
                        <i class="fa fa-file-text-o"></i> <?php echo _l('debit_note'); ?>
                        <small class="text-muted">(<?php echo _l('applied_to_selected_invoices'); ?>)</small>
                    </h5>
                    <p class="no-margin mbot10 text-muted"><?php echo _l('debit_note_batch_hint'); ?></p>
                    <div class="row" id="batch_debitnote_controls">
                        <div class="col-sm-5">
                            <div class="form-group select-placeholder">
                                <label class="control-label"><?php echo _l('debit_note'); ?></label>
                                <select id="shared_debit_note" class="selectpicker" data-width="100%"
                                    data-live-search="true"
                                    data-none-selected-text="- <?php echo _l('none'); ?> -">
                                    <option value="" data-remaining="0" data-vendorid="">- <?php echo _l('none'); ?> -</option>
                                    <?php foreach($debit_notes_grouped as $vendor_label => $notes_for_vendor){ ?>
                                    <optgroup label="<?php echo e($vendor_label); ?>">
                                        <?php foreach($notes_for_vendor as $dn){ ?>
                                        <option value="<?php echo (int) $dn['id']; ?>"
                                            data-remaining="<?php echo $dn['remaining']; ?>"
                                            data-vendorid="<?php echo (int) $dn['vendorid']; ?>">
                                            <?php echo e($dn['number']); ?> (<?php echo number_format($dn['remaining'], 2); ?>)
                                        </option>
                                        <?php } ?>
                                    </optgroup>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('debit_amount'); ?></label>
                                <input type="number" id="shared_debit_note_amount" class="form-control"
                                    step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label">&nbsp;</label>
                                <p class="no-margin" style="padding-top:7px;">
                                    <?php echo _l('batch_payments_table_invoice_balance_due'); ?>:
                                    <strong id="batch_total_balance">0.00</strong><br>
                                    <?php echo _l('debit_note'); ?>:
                                    <strong id="batch_total_debit" class="text-danger">0.00</strong><br>
                                    <?php echo _l('total'); ?>:
                                    <strong id="batch_total_net" class="text-success">0.00</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice table -->
                <div class="table-responsive">
                    <table class="table table-bordered" id="batch-payment-table">
                        <thead>
                            <tr>
                                <th><strong><?php echo _l('batch_payments_table_invoice_number_heading'); ?> #</strong></th>
                                <th><strong><?php echo _l('batch_payments_table_payment_date_heading'); ?></strong></th>
                                <th><strong><?php echo _l('batch_payments_table_payment_mode_heading'); ?></strong></th>
                                <th><strong><?php echo _l('batch_payments_table_transaction_id_heading'); ?></strong></th>
                                <th><strong><?php echo _l('batch_payments_table_amount_received_heading'); ?></strong></th>
                                <th class="text-right"><strong><?php echo _l('debit_note'); ?></strong></th>
                                <th><strong><?php echo _l('batch_payments_table_invoice_balance_due'); ?></strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $index => $invoice) { ?>
                            <tr class="batch_payment_item" data-vendorid="<?php echo $invoice->vendor; ?>"
                                data-invoiceid="<?php echo $invoice->id; ?>"
                                data-balance="<?php echo $invoice->total_left_to_pay; ?>">
                                <td>
                                    <a href="<?php echo admin_url('purchase/purchase_invoice/' . $invoice->id); ?>" target="_blank">
                                        <?php echo pur_html_entity_decode($invoice->invoice_number); ?>
                                    </a><br>
                                    <a class="text-dark" href="<?php echo admin_url('purchase/vendor/' . $invoice->vendor); ?>" target="_blank">
                                        <?php echo $invoice->company; ?>
                                    </a>
                                    <input type="hidden" name="invoice[<?php echo $index; ?>][invoiceid]" value="<?php echo $invoice->id; ?>">
                                    <!-- Hidden cheque fields — populated by JS before submit -->
                                    <input type="hidden" name="invoice[<?php echo $index; ?>][cheque_bank_name]" class="row-cheque-bank">
                                    <input type="hidden" name="invoice[<?php echo $index; ?>][cheque_number]"    class="row-cheque-number">
                                    <input type="hidden" name="invoice[<?php echo $index; ?>][cheque_date]"      class="row-cheque-date">
                                    <input type="hidden" name="invoice[<?php echo $index; ?>][is_cheque_row]"    class="row-is-cheque" value="0">
                                    <!-- Debit note allocation — driven by the single shared
                                         selector above, apportioned across rows by JS -->
                                    <input type="hidden" name="invoice[<?php echo $index; ?>][debit_note_id]"     class="row-debitnote-id">
                                    <input type="hidden" name="invoice[<?php echo $index; ?>][debit_note_amount]" class="row-debitnote-amount">
                                </td>
                                <td><?php echo render_date_input('invoice[' . $index . '][date]', '', date(get_current_date_format(true))); ?></td>
                                <td>
                                    <div class="form-group">
                                        <select class="selectpicker batch-paymentmode-select"
                                            name="invoice[<?php echo $index; ?>][paymentmode]"
                                            data-row-index="<?php echo $index; ?>"
                                            data-width="100%"
                                            data-none-selected-text="-"
                                            data-live-search="true">
                                            <option></option>
                                            <?php foreach ($payment_modes as $mode) { ?>
                                            <option value="<?php echo pur_html_entity_decode($mode['id']); ?>"
                                                data-name="<?php echo strtolower(pur_html_entity_decode($mode['name'])); ?>">
                                                <?php echo pur_html_entity_decode($mode['name']); ?>
                                            </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </td>
                                <td><?php echo render_input('invoice[' . $index . '][transactionid]'); ?></td>
                                <td><?php echo render_input('invoice[' . $index . '][amount]', '', '', 'number', ['max' => $invoice->total_left_to_pay]); ?></td>
                                <!-- Amount of the shared debit note allocated to this row -->
                                <td class="text-right row-debitnote-display text-muted">-</td>
                                <td><?php echo app_format_money($invoice->total_left_to_pay, $invoice->currency); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div><!-- /.modal-body -->

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary" id="batch-payment-submit"><?php echo _l('apply'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
// Kept for reference by any external script; the selector itself is now rendered
// server side and grouped by vendor.
var vendorDebitNotes = <?php echo json_encode($vendor_debit_notes_js); ?>;

$(function(){
    "use strict";

    // ── Shared debit note ────────────────────────────────────────────────────
    // One selector for the whole batch instead of one per row. Every open note is
    // rendered server side, grouped by vendor, so they are visible as soon as the
    // modal opens. Only visible rows are submitted, so picking a note also filters
    // the batch to that note's vendor. The amount is then apportioned across that
    // vendor's rows (in order, oldest first) into the hidden per-row fields the
    // server already understands, and each row's payment is reduced to match.

    function batchVisibleRows(){
        return $('.batch_payment_item:visible');
    }

    function batchTotalBalance(){
        var total = 0;
        batchVisibleRows().each(function(){
            total += parseFloat($(this).data('balance')) || 0;
        });
        return total;
    }

    // Vendor of the debit note currently selected ('' when none)
    function selectedDebitNoteVendor(){
        return String($('#shared_debit_note').find('option:selected').attr('data-vendorid') || '');
    }

    // Show/hide rows for a vendor and keep their inputs enabled accordingly.
    // Only visible rows are read when the form is submitted, so a debit note can
    // only be allocated to rows that are on screen.
    function filterRowsByVendor(vendorId){
        if(!vendorId){
            $('.batch_payment_item').show().find('input, select').prop('disabled', false);
        } else {
            $('.batch_payment_item').each(function(){
                var $row  = $(this);
                var match = String($row.attr('data-vendorid')) === String(vendorId);
                $row.toggle(match).toggleClass('hide', !match)
                    .find('input, select').prop('disabled', !match);
            });
        }
    }

    // Reset the selector back to "none" without firing the change handler
    function clearSharedDebitNote(){
        var $sel = $('#shared_debit_note');
        $sel.val('');
        try { $sel.selectpicker('val', ''); } catch(ex){}
        $('#shared_debit_note_amount').val('');
    }

    // Spread the shared debit note across visible rows and refresh the totals
    function applySharedDebitNote(){
        var $sel      = $('#shared_debit_note');
        var dnId      = $sel.val() || '';
        var remaining = parseFloat($sel.find('option:selected').attr('data-remaining')) || 0;
        var balance   = batchTotalBalance();
        var dnVendor  = selectedDebitNoteVendor();

        // A note can only go against its own vendor's visible rows, so the ceiling is
        // that vendor's balance rather than the whole batch's.
        var vendorBalance = 0;
        batchVisibleRows().each(function(){
            if(!dnVendor || String($(this).attr('data-vendorid')) === dnVendor){
                vendorBalance += parseFloat($(this).data('balance')) || 0;
            }
        });

        // Never apply more than the note has left, or more than that vendor owes
        var maxDebit = Math.min(remaining, vendorBalance);

        var requested = parseFloat($('#shared_debit_note_amount').val());
        if(dnId === '' || maxDebit <= 0){
            requested = 0;
        } else if(isNaN(requested) || requested <= 0 || requested > maxDebit){
            requested = maxDebit;
        }

        if(dnId !== '' && maxDebit > 0){
            $('#shared_debit_note_amount').attr('max', maxDebit.toFixed(2)).val(requested.toFixed(2));
        } else {
            $('#shared_debit_note_amount').removeAttr('max').val('');
        }

        // Clear every row first so hidden rows never carry a stale allocation
        $('.batch_payment_item').each(function(){
            var $row = $(this);
            $row.find('.row-debitnote-id').val('');
            $row.find('.row-debitnote-amount').val('');
            $row.find('.row-debitnote-display').text('-').removeClass('text-danger').addClass('text-muted');
        });

        var left = requested;

        batchVisibleRows().each(function(){
            var $row    = $(this);
            var rowBal  = parseFloat($row.data('balance')) || 0;
            var $payAmt = $row.find('input[name$="[amount]"]');

            var alloc      = 0;
            var rowIsVendor = !dnVendor || String($row.attr('data-vendorid')) === dnVendor;

            if(left > 0 && rowBal > 0 && dnId !== '' && rowIsVendor){
                alloc = Math.min(rowBal, left);
                left  = left - alloc;

                $row.find('.row-debitnote-id').val(dnId);
                $row.find('.row-debitnote-amount').val(alloc.toFixed(2));
                $row.find('.row-debitnote-display')
                    .text(alloc.toFixed(2))
                    .removeClass('text-muted').addClass('text-danger');
            }

            var newPay = rowBal - alloc;
            if(newPay < 0){ newPay = 0; }

            $payAmt.attr('max', newPay.toFixed(2));
            $payAmt.val(newPay > 0 ? newPay.toFixed(2) : '');
        });

        var applied = requested - left;

        $('#batch_total_balance').text(balance.toFixed(2));
        $('#batch_total_debit').text(applied.toFixed(2));
        $('#batch_total_net').text((balance - applied).toFixed(2));
    }

    $(document).off('changed.bs.select.shareddn change.shareddn', '#shared_debit_note');
    $(document).on('changed.bs.select.shareddn change.shareddn', '#shared_debit_note', function(){
        // Reset any hand-typed amount so the new note's maximum is used
        $('#shared_debit_note_amount').val('');

        // Only visible rows are submitted, so selecting a note also narrows the batch
        // to that note's vendor. This is what makes the selector usable straight away
        // instead of requiring the vendor filter to be set first.
        var dnVendor = selectedDebitNoteVendor();
        if(dnVendor){
            var $filter = $('#batch-payment-filter');
            if(String($filter.val() || '') !== dnVendor){
                $(document).data('batchDnSyncing', true);
                $filter.val(dnVendor);
                try { $filter.selectpicker('val', dnVendor); } catch(ex){}
                filterRowsByVendor(dnVendor);
                checkAnyChequeRow();
                $(document).data('batchDnSyncing', false);
            }
        }

        applySharedDebitNote();
    });

    $(document).off('change.shareddnamt', '#shared_debit_note_amount');
    $(document).on('change.shareddnamt', '#shared_debit_note_amount', function(){
        applySharedDebitNote();
    });

    // ── Check if any visible row has cheque mode selected ────────────────────
    function checkAnyChequeRow() {
        var hasCheque = false;
        $('.batch_payment_item:visible .batch-paymentmode-select').each(function(){
            var val  = $(this).val();
            var name = $(this).find('option[value="' + val + '"]').data('name') || '';
            if(name.indexOf('cheque') !== -1 || name.indexOf('check') !== -1){
                hasCheque = true;
                return false; // break
            }
        });
        if(hasCheque){
            $('#batch_shared_cheque_section').slideDown(200);
        } else {
            $('#batch_shared_cheque_section').slideUp(200);
        }
    }

    // ── Payment mode change — sync to ALL visible rows ──────────────────────
    // Use namespace to allow clean re-registration on modal reload
    $(document).off('changed.bs.select.purchasebatch change.purchasebatch', '.batch-paymentmode-select');
    $(document).on('changed.bs.select.purchasebatch change.purchasebatch', '.batch-paymentmode-select', function(){
        if($(document).data('purchaseBatchSyncing')) return;
        $(document).data('purchaseBatchSyncing', true);

        var val  = $(this).val();
        var $src = $(this);

        $('.batch_payment_item:visible .batch-paymentmode-select').each(function(){
            if($(this).is($src)) return;
            $(this).val(val);
            try { $(this).selectpicker('val', val); } catch(ex){}
        });

        $(document).data('purchaseBatchSyncing', false);
        checkAnyChequeRow();
    });

    // ── Vendor filter ────────────────────────────────────────────────────────
    $('#batch-payment-filter').on('changed.bs.select change', function(){
        if($(document).data('batchDnSyncing')) return;

        var vendorId = String($(this).val() || '');
        filterRowsByVendor(vendorId);
        checkAnyChequeRow();

        // The note list is not vendor specific any more, but a note already chosen for
        // a different vendor would allocate to rows that are no longer submitted.
        var dnVendor = selectedDebitNoteVendor();
        if(dnVendor && vendorId && dnVendor !== vendorId){
            clearSharedDebitNote();
        }

        applySharedDebitNote();
    });

    // ── Reset when modal opens ───────────────────────────────────────────────
    $('#batch-payment-modal').on('show.bs.modal', function(){
        $('#batch_shared_cheque_section').hide();
        $('#shared_cheque_bank_name, #shared_cheque_number, #shared_cheque_date').val('');
        $('#shared_cheque_attachment').val('');
        $('.row-is-cheque').val('0');
        // Start with no note selected; the list itself stays populated
        clearSharedDebitNote();
        applySharedDebitNote();
    });

    // Seed the totals on first render
    applySharedDebitNote();

    // ── Before submit: validate cheque fields, then submit via AJAX + FormData ──
    $(document).on('click', '#batch-payment-submit', function(e){
        e.preventDefault();
        e.stopImmediatePropagation();

        var $btn = $(this);

        var bank   = $.trim($('#shared_cheque_bank_name').val());
        var number = $.trim($('#shared_cheque_number').val());
        var date   = $.trim($('#shared_cheque_date').val());
        var fileInput = document.getElementById('shared_cheque_attachment');
        var hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

        // Check if any visible row is cheque mode
        var hasChequeRow = false;
        var txnError = false;
        $('.batch_payment_item:visible').each(function(){
            var $row = $(this);
            // Read paymentmode from selectpicker reliably
            var $sel  = $row.find('.batch-paymentmode-select');
            var pmVal = $sel.val();
            if(!pmVal){ try{ pmVal = $sel.selectpicker('val'); }catch(ex){} }
            var pmName = $sel.find('option[value="' + pmVal + '"]').data('name') || '';
            if(pmName.indexOf('cheque') !== -1 || pmName.indexOf('check') !== -1){
                hasChequeRow = true;
            }
            // Check transaction ID for rows that have an amount filled
            var amount = $.trim($row.find('input[name$="[amount]"]').val());
            var txnid  = $.trim($row.find('input[name$="[transactionid]"]').val());
            if(amount && !txnid){
                txnError = true;
                $row.find('input[name$="[transactionid]"]').css('border-color','#e74c3c');
            } else {
                $row.find('input[name$="[transactionid]"]').css('border-color','');
            }
        });

        if(txnError){
            alert_float('danger', '<?php echo _l('payment_transaction_id'); ?> <?php echo _l('required'); ?>');
            return false;
        }

        // Validate cheque fields if any cheque row exists
        if(hasChequeRow){
            var errors = [];
            if(!bank)    errors.push('<?php echo _l('cheque_bank_name'); ?>');
            if(!number)  errors.push('<?php echo _l('cheque_number'); ?>');
            if(!date)    errors.push('<?php echo _l('cheque_date'); ?>');
            if(!hasFile) errors.push('<?php echo _l('cheque_attachment'); ?>');
            if(errors.length > 0){
                $('#batch_shared_cheque_section').css({'border-color':'#e74c3c','background':'#fff5f5'});
                setTimeout(function(){
                    $('#batch_shared_cheque_section').css({'border-color':'#b8d4f0','background':'#f0f7ff'});
                }, 3000);
                alert_float('danger', '<?php echo _l('required'); ?>: ' + errors.join(', '));
                return false;
            }
        }

        // ── Build FormData manually — read every field explicitly ─────────────
        var fd = new FormData();

        // CSRF token from form hidden inputs.
        // Skip the per-row hidden fields: they carry their ORIGINAL row index, while
        // the loop below renumbers visible rows from 0. Appending both would leave
        // stale invoice[] groups that the server treats as extra rows - which for a
        // debit note allocation would apply the same debit twice.
        $('#batch-payment-form').find('input[type="hidden"]').each(function(){
            var n = $(this).attr('name');
            if(n && n.indexOf('invoice[') !== 0) fd.append(n, $(this).val());
        });

        // Shared cheque attachment file
        if(hasChequeRow && hasFile){
            fd.append('shared_cheque_attachment', fileInput.files[0]);
        }

        // Per-row data
        var rowIndex = 0;
        $('.batch_payment_item:visible').each(function(){
            var $row = $(this);

            var invoiceId = $row.data('invoiceid');
            var amount    = $.trim($row.find('input[name$="[amount]"]').val());
            // Allocated by the shared debit note selector into the hidden row fields
            var debitNoteId  = $.trim($row.find('.row-debitnote-id').val() || '');
            var debitNoteAmt = $.trim($row.find('.row-debitnote-amount').val() || '');

            if(!amount && !debitNoteAmt) return; // skip rows with no amount and no debit

            // Read paymentmode from selectpicker reliably
            var $sel  = $row.find('.batch-paymentmode-select');
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
            fd.append(prefix + '[amount]',        amount || '0');
            fd.append(prefix + '[date]',          dateVal);
            fd.append(prefix + '[paymentmode]',   pmVal || '');
            fd.append(prefix + '[transactionid]', txnid);
            fd.append(prefix + '[is_cheque_row]', isCheque ? '1' : '0');
            fd.append(prefix + '[debit_note_id]', debitNoteId);
            fd.append(prefix + '[debit_note_amount]', debitNoteAmt);

            if(isCheque){
                fd.append(prefix + '[cheque_bank_name]', bank);
                fd.append(prefix + '[cheque_number]',    number);
                fd.append(prefix + '[cheque_date]',      date);
            }

            rowIndex++;
        });

        if(rowIndex === 0){
            alert_float('danger', 'Please fill in at least one payment amount or debit note.');
            return false;
        }

        // ── Submit via AJAX ───────────────────────────────────────────────────
        $btn.prop('disabled', true).text('<?php echo _l('wait_text'); ?>');

        $.ajax({
            url:         $('#batch-payment-form').attr('action'),
            type:        'POST',
            data:        fd,
            processData: false,
            contentType: false,
            success: function(response){
                try {
                    var res = (typeof response === 'string') ? JSON.parse(response) : response;
                    if(res.success === true || res.success === 'true'){
                        alert_float('success', res.message || '<?php echo _l('add_batch_payments'); ?>');
                        setTimeout(function(){
                            window.location.href = admin_url + 'purchase/invoices';
                        }, 800);
                    } else {
                        $btn.prop('disabled', false).text('<?php echo _l('apply'); ?>');
                        alert_float('danger', res.message || 'Failed to add batch payments.');
                    }
                } catch(ex){
                    // Controller returned redirect HTML — treat as success
                    alert_float('success', '<?php echo _l('add_batch_payments'); ?>');
                    setTimeout(function(){
                        window.location.href = admin_url + 'purchase/invoices';
                    }, 800);
                }
            },
            error: function(xhr){
                $btn.prop('disabled', false).text('<?php echo _l('apply'); ?>');
                alert_float('danger', 'Error submitting batch payment (HTTP ' + xhr.status + '). Please try again.');
            }
        });
    });
});
</script>
