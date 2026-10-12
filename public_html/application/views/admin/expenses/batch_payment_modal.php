<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/**
 * Batch payments for credit expenses.
 *
 * The expense counterpart of modules/purchase/views/invoices/batch_payment_modal.php
 * and it works the same way: every open credit expense is rendered once, and the
 * vendor selector filters the rows client side. Only visible rows with an amount
 * are submitted.
 *
 * Each row is saved through Expenses_model::add_expense_payment(), so the
 * overpayment guard, the double entry and the payment status behave exactly as they
 * do for a single payment recorded from the expense preview.
 */
?>
<div class="modal fade" id="expense-batch-payment-modal">
    <div class="modal-dialog modal-lg" role="document" style="width:95%; max-width:1400px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?= _l('expense_batch_payments'); ?></h4>
            </div>
            <?= form_open(admin_url('expenses/add_batch_payment'), ['id' => 'expense-batch-payment-form']); ?>
            <div class="modal-body">

                <?php if (count($expenses) === 0) { ?>
                    <div class="alert alert-info no-mbot">
                        <?= _l('expense_batch_payment_nothing_open'); ?>
                    </div>
                <?php } else { ?>

                <p class="text-muted"><?= _l('expense_batch_payment_intro'); ?></p>

                <div class="row mbot15">
                    <div class="col-sm-4">
                        <div class="form-group select-placeholder">
                            <label class="control-label"><?= _l('expense_batch_vendor'); ?></label>
                            <select id="expense-batch-filter" class="selectpicker" data-width="100%"
                                data-live-search="true"
                                data-none-selected-text="<?= _l('expense_batch_payment_all_vendors'); ?>">
                                <option value=""><?= _l('expense_batch_payment_all_vendors'); ?></option>
                                <?php foreach ($vendors as $vendor_id => $vendor_name) { ?>
                                    <option value="<?= (int) $vendor_id; ?>"><?= e($vendor_name); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <label class="control-label">&nbsp;</label>
                        <p class="no-margin" style="padding-top:7px;">
                            <?= _l('expense_batch_payment_selected_total'); ?>:
                            <strong id="expense_batch_total">0.00</strong>
                        </p>
                    </div>
                </div>

                <!-- Shared controls. Filled into every row being paid, so the common
                     case of one payment run does not have to be typed out per row. -->
                <div style="background:#f0f7ff; border:1px solid #b8d4f0; border-radius:4px; padding:14px; margin-bottom:14px;">
                    <h5 class="no-margin mbot10 bold">
                        <i class="fa fa-money"></i> <?= _l('expense_batch_payment_apply_to_all'); ?>
                    </h5>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group select-placeholder">
                                <label class="control-label"><?= _l('payment_mode'); ?></label>
                                <select id="shared_paymentmode" class="selectpicker" data-width="100%"
                                    data-none-selected-text="-">
                                    <option value=""></option>
                                    <?php foreach ($payment_modes as $mode) { ?>
                                        <option value="<?= (int) $mode['id']; ?>"
                                            data-name="<?= strtolower(e($mode['name'])); ?>">
                                            <?= e($mode['name']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group select-placeholder">
                                <label class="control-label"><?= _l('expense_payment_paid_from'); ?></label>
                                <select id="shared_gl_account" class="selectpicker" data-width="100%"
                                    data-live-search="true" data-none-selected-text="-">
                                    <option value=""><?= _l('expense_batch_payment_from_mode'); ?></option>
                                    <?php foreach ($pay_accounts as $acc) { ?>
                                        <option value="<?= (int) $acc['id']; ?>"><?= e($acc['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label"><?= _l('expense_payment_date'); ?></label>
                                <div class="input-group date">
                                    <input type="text" id="shared_date" class="form-control datepicker"
                                        value="<?= _d(date('Y-m-d')); ?>" autocomplete="off">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label class="control-label"><?= _l('payment_transaction_id'); ?></label>
                                <input type="text" id="shared_transactionid" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Cheque details, revealed only when a cheque mode is chosen -->
                    <div id="shared_cheque_section" class="hide">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label class="control-label"><?= _l('cheque_number'); ?></label>
                                    <input type="text" id="shared_cheque_number" class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label class="control-label"><?= _l('cheque_bank_name'); ?></label>
                                    <input type="text" id="shared_cheque_bank_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label class="control-label"><?= _l('cheque_date'); ?></label>
                                    <div class="input-group date">
                                        <input type="text" id="shared_cheque_date" class="form-control datepicker"
                                            autocomplete="off">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="no-margin">
                        <button type="button" class="btn btn-default btn-sm" id="expense_batch_pay_full">
                            <?= _l('expense_batch_payment_fill_full'); ?>
                        </button>
                        <button type="button" class="btn btn-default btn-sm" id="expense_batch_clear">
                            <?= _l('expense_batch_payment_clear'); ?>
                        </button>
                    </p>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered" id="expense-batch-table">
                        <thead>
                            <tr>
                                <th><strong><?= _l('expense_invoice_number'); ?></strong></th>
                                <th><strong><?= _l('expense_batch_vendor'); ?></strong></th>
                                <th style="white-space:nowrap"><strong><?= _l('expense_batch_date'); ?></strong></th>
                                <th><strong><?= _l('expense_category'); ?></strong></th>
                                <th class="text-right"><strong><?= _l('expense_total'); ?></strong></th>
                                <th class="text-right"><strong><?= _l('expense_amount_due'); ?></strong></th>
                                <th style="width:150px"><strong><?= _l('expense_payment_amount'); ?></strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $index => $expense) { ?>
                            <tr class="expense_batch_item"
                                data-vendorid="<?= (int) $expense->vendor; ?>"
                                data-expenseid="<?= (int) $expense->id; ?>"
                                data-left="<?= $expense->amount_left; ?>">
                                <td>
                                    <a href="<?= admin_url('expenses/list_expenses/' . $expense->id); ?>" target="_blank">
                                        <?= !empty($expense->expinv_number) ? e($expense->expinv_number) : '#' . (int) $expense->id; ?>
                                    </a>
                                    <?php if (!empty($expense->expense_name)) { ?>
                                        <br /><span class="text-muted text-sm"><?= e($expense->expense_name); ?></span>
                                    <?php } ?>
                                    <input type="hidden" name="expense[<?= $index; ?>][expense_id]" value="<?= (int) $expense->id; ?>">
                                    <input type="hidden" name="expense[<?= $index; ?>][date]"          class="row-date">
                                    <input type="hidden" name="expense[<?= $index; ?>][paymentmode]"   class="row-paymentmode">
                                    <input type="hidden" name="expense[<?= $index; ?>][gl_account]"    class="row-glaccount">
                                    <input type="hidden" name="expense[<?= $index; ?>][transactionid]" class="row-transactionid">
                                    <input type="hidden" name="expense[<?= $index; ?>][is_cheque_row]" class="row-is-cheque" value="0">
                                    <input type="hidden" name="expense[<?= $index; ?>][cheque_number]"    class="row-cheque-number">
                                    <input type="hidden" name="expense[<?= $index; ?>][cheque_bank_name]" class="row-cheque-bank">
                                    <input type="hidden" name="expense[<?= $index; ?>][cheque_date]"      class="row-cheque-date">
                                </td>
                                <td><?= e($expense->vendor_company); ?></td>
                                <td style="white-space:nowrap"><?= _d($expense->date); ?></td>
                                <td><?= e($expense->category_name); ?></td>
                                <td class="text-right"><?= app_format_money($expense->total_with_tax, $expense->currency_name); ?></td>
                                <td class="text-right bold row-left-display"><?= app_format_money($expense->amount_left, $expense->currency_name); ?></td>
                                <td>
                                    <input type="number" class="form-control row-amount"
                                        name="expense[<?= $index; ?>][amount]"
                                        step="0.01" min="0" max="<?= $expense->amount_left; ?>"
                                        placeholder="0.00">
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <?php } ?>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <?php if (count($expenses) > 0) { ?>
                    <button type="submit" class="btn btn-primary" id="expense-batch-submit"><?= _l('submit'); ?></button>
                <?php } ?>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script>
(function ($) {
    "use strict";

    var $modal = $('#expense-batch-payment-modal');

    function chequeModeSelected() {
        var $opt = $('#shared_paymentmode').find('option:selected');
        var name = ($opt.data('name') || '').toString();

        return name.indexOf('cheque') !== -1 || name.indexOf('check') !== -1;
    }

    // Cheque fields are meaningless for cash or transfer, so they only appear for a
    // cheque mode.
    $modal.on('change', '#shared_paymentmode', function () {
        $('#shared_cheque_section').toggleClass('hide', !chequeModeSelected());
    });

    function visibleRows() {
        return $modal.find('.expense_batch_item:not(.hide)');
    }

    function recalcTotal() {
        var total = 0;
        visibleRows().each(function () {
            var v = parseFloat($(this).find('.row-amount').val());
            if (!isNaN(v)) { total += v; }
        });
        $('#expense_batch_total').text(total.toFixed(2));
    }

    $modal.on('input change', '.row-amount', recalcTotal);

    // Vendor filter. Hidden rows are also disabled so the browser does not post
    // them, matching how the purchase batch screen behaves.
    $modal.on('changed.bs.select', '#expense-batch-filter', function () {
        var vendorId = $(this).val();

        $modal.find('.expense_batch_item').each(function () {
            var $row  = $(this);
            var match = (vendorId === '' || String($row.data('vendorid')) === String(vendorId));

            $row.toggleClass('hide', !match);
            $row.find('input').prop('disabled', !match);

            if (!match) { $row.find('.row-amount').val(''); }
        });

        recalcTotal();
    });

    // Fill every visible row with its full outstanding balance
    $modal.on('click', '#expense_batch_pay_full', function () {
        visibleRows().each(function () {
            $(this).find('.row-amount').val(parseFloat($(this).data('left')).toFixed(2));
        });
        recalcTotal();
    });

    $modal.on('click', '#expense_batch_clear', function () {
        $modal.find('.row-amount').val('');
        recalcTotal();
    });

    $('#expense-batch-payment-form').on('submit', function (e) {
        e.preventDefault();

        var $btn        = $('#expense-batch-submit');
        var mode        = $('#shared_paymentmode').val();
        var glAccount   = $('#shared_gl_account').val();
        var date        = $.trim($('#shared_date').val());
        var txnid       = $.trim($('#shared_transactionid').val());
        var isCheque    = chequeModeSelected();
        var chequeNo    = $.trim($('#shared_cheque_number').val());
        var chequeBank  = $.trim($('#shared_cheque_bank_name').val());
        var chequeDate  = $.trim($('#shared_cheque_date').val());

        var rowsToPay = 0;
        visibleRows().each(function () {
            var v = parseFloat($(this).find('.row-amount').val());
            if (!isNaN(v) && v > 0) { rowsToPay++; }
        });

        if (rowsToPay === 0) {
            alert_float('warning', '<?= _l('expense_batch_payment_no_amounts'); ?>');
            return false;
        }
        if (!mode) {
            alert_float('warning', '<?= _l('expense_batch_payment_mode_required'); ?>');
            return false;
        }
        if (!date) {
            alert_float('warning', '<?= _l('expense_batch_payment_date_required'); ?>');
            return false;
        }
        if (isCheque && (!chequeNo || !chequeBank || !chequeDate)) {
            alert_float('warning', '<?= _l('expense_batch_payment_cheque_required'); ?>');
            return false;
        }

        // Copy the shared values into each row being paid. Rows with no amount keep
        // empty fields and are skipped server side.
        visibleRows().each(function () {
            var $row = $(this);
            var v    = parseFloat($row.find('.row-amount').val());

            if (isNaN(v) || v <= 0) { return; }

            $row.find('.row-date').val(date);
            $row.find('.row-paymentmode').val(mode);
            $row.find('.row-glaccount').val(glAccount || '');
            $row.find('.row-transactionid').val(txnid);
            $row.find('.row-is-cheque').val(isCheque ? '1' : '0');

            if (isCheque) {
                $row.find('.row-cheque-number').val(chequeNo);
                $row.find('.row-cheque-bank').val(chequeBank);
                $row.find('.row-cheque-date').val(chequeDate);
            }
        });

        $btn.prop('disabled', true).text('<?= _l('wait_text'); ?>');

        $.post($('#expense-batch-payment-form').attr('action'), $('#expense-batch-payment-form').serialize())
            .done(function (response) {
                var res = (typeof response === 'string') ? JSON.parse(response) : response;

                if (res.success) {
                    alert_float('success', res.message);
                    setTimeout(function () { window.location.reload(); }, 900);
                } else {
                    $btn.prop('disabled', false).text('<?= _l('submit'); ?>');
                    alert_float('danger', res.message);
                }
            })
            .fail(function (xhr) {
                $btn.prop('disabled', false).text('<?= _l('submit'); ?>');
                alert_float('danger', 'HTTP ' + xhr.status);
            });

        return false;
    });
})(jQuery);
</script>
