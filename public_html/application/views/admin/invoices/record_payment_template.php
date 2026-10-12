<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?= form_open_multipart(admin_url('invoices/record_payment'), ['id' => 'record_payment_form']); ?>
<div class="col-md-12 no-padding animated fadeIn">
    <div class="panel_s">
        <?= form_hidden('invoiceid', $invoice->id); ?>
        <div class="panel-heading">
            <h4 class="panel-title">
                <?= _l('record_payment_for_invoice'); ?>
                <?= e(format_invoice_number($invoice->id)); ?>
            </h4>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <?php
                    $amount       = $invoice->total_left_to_pay;
                    $totalAllowed = 0;
                    echo render_input('amount', 'record_payment_amount_received', $amount, 'number', ['max' => $amount]);
                    ?>
                    <?= render_date_input('date', 'record_payment_date', _d(date('Y-m-d'))); ?>
                    <div class="form-group">
                        <label for="paymentmode" class="control-label"><span class="text-danger">* </span><?= _l('payment_mode'); ?></label>
                        <select class="selectpicker" name="paymentmode" id="sales_paymentmode"
                            data-width="100%" required
                            data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                            <option value=""></option>
                            <?php foreach ($payment_modes as $mode) { ?>
                            <?php if (is_payment_mode_allowed_for_invoice($mode['id'], $invoice->id)) {
                                $totalAllowed++; ?>
                            <option value="<?= e($mode['id']); ?>"
                                data-name="<?= strtolower(e($mode['name'])); ?>">
                                <?= e($mode['name']); ?>
                            </option>
                            <?php } ?>
                            <?php } ?>
                            <?php
                            // Credit notes with remaining balance for this client
                            if(isset($invoice) && isset($invoice->clientid) && $invoice->clientid > 0){
                                $CI_cn =& get_instance();
                                $CI_cn->db->where('clientid', $invoice->clientid);
                                $CI_cn->db->where('status', 1);
                                $_cn_list = $CI_cn->db->get(db_prefix().'creditnotes')->result_array();
                                foreach($_cn_list as $_cn){
                                    $CI_cn->db->select('SUM(amount) as applied');
                                    $CI_cn->db->where('credit_id', $_cn['id']);
                                    $_cn_app = $CI_cn->db->get(db_prefix().'credits')->row();
                                    $_cn_applied = $_cn_app && $_cn_app->applied ? (float)$_cn_app->applied : 0;
                                    $_cn_remaining = (float)$_cn['total'] - $_cn_applied;
                                    if($_cn_remaining > 0){
                                        $_cn_num = (isset($_cn['prefix']) ? $_cn['prefix'] : '') . str_pad($_cn['number'], 5, '0', STR_PAD_LEFT);
                                        $totalAllowed++;
                            ?>
                            <option value="credit_note_<?php echo $_cn['id']; ?>" data-name="credit_note">
                                Credit Note: <?php echo $_cn_num; ?> (Balance: <?php echo app_format_money($_cn_remaining, ''); ?>)
                            </option>
                            <?php }
                                }
                            } ?>
                        </select>
                    </div>

                    <?php if ($totalAllowed === 0) { ?>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        No payment modes are enabled for this invoice.
                        <a href="<?= admin_url('invoices/invoice/' . $invoice->id . '?allowed_payment_modes=1'); ?>" class="alert-link">Click here</a>
                        to edit the invoice and enable payment modes (including Cheque).
                    </div>
                    <?php } ?>

                    <!-- ── Cheque Details (shown when payment mode is Cheque) ── -->
                    <div id="sales_cheque_section" style="display:none; background:#f9f9f9; border:1px solid #ddd; border-radius:4px; padding:12px; margin-bottom:10px;">
                        <h5 class="no-margin mbot10"><i class="fa fa-money"></i> Cheque Details <small class="text-danger">* Required</small></h5>
                        <div class="form-group">
                            <label class="control-label"><span class="text-danger">* </span>Bank Name</label>
                            <input type="text" name="cheque_bank_name" id="sales_cheque_bank_name" class="form-control" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="control-label"><span class="text-danger">* </span>Cheque Number</label>
                            <input type="text" name="cheque_number" id="sales_cheque_number" class="form-control" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="control-label"><span class="text-danger">* </span>Cheque Date</label>
                            <div class="input-group date">
                                <input type="text" name="cheque_date" id="sales_cheque_date" class="form-control datepicker" autocomplete="off">
                                <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Cheque Attachment</label>
                            <input type="file" name="cheque_attachment" id="sales_cheque_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.gif">
                            <small class="text-muted">Upload soft copy of cheque (JPG, PNG, PDF)</small>
                        </div>
                    </div>
                    <?php 
                    $CI_gl =& get_instance();
                    $CI_gl->db->select('id, CONCAT(number, " - ", name) as name');
                    $CI_gl->db->where('active', 1);
                    $CI_gl->db->order_by('number', 'ASC');
                    $sales_gl_accounts = $CI_gl->db->get(db_prefix().'acc_accounts')->result_array();
                    echo render_select('gl_account', $sales_gl_accounts, array('id','name'), 'GL Account', '', array('required'=>'required', 'data-live-search'=>'true')); 
                    ?>
                </div>
                <div class="col-md-6">
                    <?= render_input('transactionid', 'payment_transaction_id'); ?>
                    <div class="form-group">
                        <label for="note" class="control-label"><?= _l('record_payment_leave_note'); ?></label>
                        <textarea name="note" class="form-control" rows="8"
                            placeholder="<?= _l('invoice_record_payment_note_placeholder'); ?>"
                            id="note"></textarea>
                    </div>
                </div>
                <div class="col-md-12 tw-mt-3">
                    <?php
                    $pr_template = is_email_template_active('invoice-payment-recorded');
                    $sms_trigger = is_sms_trigger_active(SMS_TRIGGER_PAYMENT_RECORDED);
                    if ($pr_template || $sms_trigger) { ?>
                    <div class="checkbox checkbox-primary mtop15">
                        <input type="checkbox" name="do_not_send_email_template" id="do_not_send_email_template">
                        <label for="do_not_send_email_template">
                            <?php
                            if ($pr_template) {
                                echo _l('do_not_send_invoice_payment_email_template_contact');
                                if ($sms_trigger) echo '/';
                            }
                            if ($sms_trigger) echo 'SMS ' . _l('invoice_payment_recorded');
                            ?>
                        </label>
                    </div>
                    <?php } ?>
                    <div class="checkbox checkbox-primary mtop15 do_not_redirect hide">
                        <input type="checkbox" name="do_not_redirect" id="do_not_redirect" checked>
                        <label for="do_not_redirect"><?= _l('do_not_redirect_payment'); ?></label>
                    </div>
                </div>
            </div>
            <?php hooks()->do_action('after_admin_last_record_payment_form_field', $invoice); ?>
            <?php if ($payments) { ?>
            <div class="mtop25 inline-block full-width">
                <h5 class="bold"><?= _l('invoice_payments_received'); ?></h5>
                <?php include_once APPPATH . 'views/admin/invoices/invoice_payments_table.php'; ?>
            </div>
            <?php } ?>
            <?php hooks()->do_action('before_admin_add_payment_form_submit', $invoice); ?>
        </div>

        <div class="panel-footer text-right">
            <a href="#" class="btn btn-danger"
                onclick="init_invoice(<?= e($invoice->id); ?>); return false;"><?= _l('cancel'); ?></a>
            <button type="button" id="sales_record_payment_btn" autocomplete="off"
                class="btn btn-success"><?= _l('submit'); ?></button>
        </div>
    </div>
</div>
<?= form_close(); ?>

<script>
$(function() {
    "use strict";
    init_selectpicker();
    init_datepicker();

    // ── Show/hide cheque section on payment mode change ───────────────────────
    $(document).on('changed.bs.select change', '#sales_paymentmode', function(){
        var $sel = $(this);
        var val  = $sel.val();
        var txt  = '';
        if(val){
            var $opt = $sel.find('option[value="' + val + '"]');
            txt = ($opt.data('name') || $opt.text()).toLowerCase();
        }
        if(txt.indexOf('cheque') !== -1 || txt.indexOf('check') !== -1){
            $('#sales_cheque_section').slideDown(200, function(){
                if(typeof init_datepicker === 'function') init_datepicker();
            });
        } else {
            $('#sales_cheque_section').slideUp(200);
            $('#sales_cheque_bank_name, #sales_cheque_number, #sales_cheque_date').val('');
        }
        // Original do_not_redirect logic
        var $notRedirect = $(".do_not_redirect");
        !$.isNumeric(val) ? $notRedirect.removeClass("hide") : $notRedirect.addClass("hide");
    });

    // ── Submit via AJAX + FormData ────────────────────────────────────────────
    $(document).on('click', '#sales_record_payment_btn', function(e){
        e.preventDefault();
        var $btn  = $(this);
        var $form = $('#record_payment_form');

        // Read paymentmode reliably
        var $pmSel  = $form.find('select[name="paymentmode"]');
        var paymode = $pmSel.val();
        if(!paymode){ try{ paymode = $pmSel.selectpicker('val'); }catch(ex){} }
        if(!paymode){
            var btnTxt = $form.find('.bootstrap-select button .filter-option-inner-inner').text().trim();
            $pmSel.find('option').each(function(){
                if($(this).text().trim() === btnTxt){ paymode = $(this).val(); return false; }
            });
        }

        // Basic validation
        var amount = $.trim($form.find('input[name="amount"]').val());
        var date   = $.trim($form.find('input[name="date"]').val());
        var gl_acc = $form.find('select[name="gl_account"]').val();
        if(!gl_acc){ try{ gl_acc = $form.find('select[name="gl_account"]').selectpicker('val'); }catch(ex){} }
        if(!amount || !date || !paymode || !gl_acc){
            var missing = [];
            if(!amount)  missing.push('Amount');
            if(!date)    missing.push('Date');
            if(!paymode) missing.push('Payment Mode');
            if(!gl_acc)  missing.push('GL Account');
            alert_float('danger', missing.join(', ') + ' is required');
            return false;
        }

        // Determine if cheque mode
        var pmTxt = ($pmSel.find('option[value="' + paymode + '"]').data('name') || '').toLowerCase();
        var isCheque = (pmTxt.indexOf('cheque') !== -1 || pmTxt.indexOf('check') !== -1);

        // Validate cheque fields
        if(isCheque){
            var errors = [];
            var bank   = $.trim($('#sales_cheque_bank_name').val());
            var number = $.trim($('#sales_cheque_number').val());
            var cdate  = $.trim($('#sales_cheque_date').val());
            if(!bank)   errors.push('Bank Name');
            if(!number) errors.push('Cheque Number');
            if(!cdate)  errors.push('Cheque Date');
            if(errors.length > 0){
                $('#sales_cheque_section').css({'border-color':'#e74c3c','background':'#fff5f5'});
                setTimeout(function(){ $('#sales_cheque_section').css({'border-color':'#ddd','background':'#f9f9f9'}); }, 3000);
                alert_float('danger', 'Required: ' + errors.join(', '));
                return false;
            }
        }

        // Build FormData
        var fd = new FormData();
        // Hidden inputs (CSRF etc.)
        $form.find('input[type="hidden"]').each(function(){
            var n = $(this).attr('name');
            if(n) fd.append(n, $(this).val());
        });
        fd.append('invoiceid',   $form.find('input[name="invoiceid"]').val());
        fd.append('amount',      amount);
        fd.append('date',        date);
        fd.append('paymentmode', paymode);
        fd.append('gl_account',  gl_acc);
        fd.append('transactionid', $.trim($form.find('input[name="transactionid"]').val()));
        fd.append('note',        $form.find('textarea[name="note"]').val() || '');

        // Optional checkboxes
        if($form.find('input[name="do_not_send_email_template"]').is(':checked')){
            fd.append('do_not_send_email_template', '1');
        }
        if($form.find('input[name="do_not_redirect"]').is(':checked')){
            fd.append('do_not_redirect', '1');
        }

        // Cheque fields
        if(isCheque){
            fd.append('cheque_bank_name', $.trim($('#sales_cheque_bank_name').val()));
            fd.append('cheque_number',    $.trim($('#sales_cheque_number').val()));
            fd.append('cheque_date',      $.trim($('#sales_cheque_date').val()));
            var fileEl = document.getElementById('sales_cheque_attachment');
            if(fileEl && fileEl.files.length > 0){
                fd.append('cheque_attachment', fileEl.files[0]);
            }
        }

        $btn.prop('disabled', true).text('<?= _l('wait_text'); ?>');

        $.ajax({
            url:         $form.attr('action'),
            type:        'POST',
            data:        fd,
            processData: false,
            contentType: false,
            success: function(response){
                try {
                    var res = (typeof response === 'string') ? JSON.parse(response) : response;
                    if(res.success){
                        alert_float('success', res.message || '<?= _l('invoice_payment_recorded'); ?>');
                        setTimeout(function(){
                            if(res.redirect){
                                window.location.href = res.redirect;
                            } else {
                                window.location.reload();
                            }
                        }, 700);
                    } else {
                        $btn.prop('disabled', false).text('<?= _l('submit'); ?>');
                        alert_float('danger', res.message || '<?= _l('invoice_payment_record_failed'); ?>');
                    }
                } catch(ex){
                    // Non-JSON response (redirect HTML) — treat as success
                    alert_float('success', '<?= _l('invoice_payment_recorded'); ?>');
                    setTimeout(function(){ window.location.reload(); }, 700);
                }
            },
            error: function(xhr){
                $btn.prop('disabled', false).text('<?= _l('submit'); ?>');
                alert_float('danger', 'Error recording payment (HTTP ' + xhr.status + '). Please try again.');
            }
        });
    });
});
</script>
