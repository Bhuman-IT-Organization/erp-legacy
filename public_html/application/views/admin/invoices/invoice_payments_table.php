<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th><span class="bold"><?= _l('payments_table_number_heading'); ?></span></th>
                <th><span class="bold"><?= _l('payments_table_mode_heading'); ?></span></th>
                <th><span class="bold">Cheque Details</span></th>
                <th><span class="bold"><?= _l('payments_table_date_heading'); ?></span></th>
                <th><span class="bold"><?= _l('payments_table_amount_heading'); ?></span></th>
                <th><span class="bold"><?= _l('options'); ?></span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoice->payments as $payment) {
                $is_deleted = !empty($payment['is_deleted']);
            ?>
            <tr class="payment<?= $is_deleted ? ' tw-opacity-60' : ''; ?>">
                <td>
                    <?= e($payment['paymentid']); ?>
                    <?php if ($is_deleted): ?>
                        <span class="label label-danger mleft5">Deleted</span>
                    <?php else: ?>
                        <?= icon_btn('payments/pdf/' . $payment['paymentid'], 'fa-regular fa-file-pdf', 'btn-default pull-right'); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?= e($payment['name']); ?>
                    <?php if (!empty($payment['paymentmethod'])) {
                        echo ' - ' . $payment['paymentmethod'];
                    }
                    if ($payment['transactionid']) {
                        echo '<br />' . _l('payments_table_transaction_id', $payment['transactionid']);
                    }
                    ?>
                </td>
                <td>
                    <?php
                    $pm_name_lower = strtolower($payment['name'] ?? '');
                    $is_cheque_pay = (strpos($pm_name_lower, 'cheque') !== false || strpos($pm_name_lower, 'check') !== false);
                    if ($is_cheque_pay): ?>
                    <small>
                        <?php if (!empty($payment['cheque_bank_name'])): ?>
                        <strong>Bank Name:</strong> <?= e($payment['cheque_bank_name']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($payment['cheque_number'])): ?>
                        <strong>Cheque #:</strong> <?= e($payment['cheque_number']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($payment['cheque_date'])): ?>
                        <strong>Cheque Date:</strong> <?= _d($payment['cheque_date']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($payment['cheque_attachment'])): ?>
                        <a href="<?= site_url('uploads/invoices/payment_cheque/' . $payment['paymentid'] . '/' . $payment['cheque_attachment']); ?>" target="_blank" class="text-info">
                            <i class="fa fa-paperclip"></i> View Attachment
                        </a>
                        <?php endif; ?>
                    </small>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><?= e(_d($payment['date'])); ?></td>
                <td>
                    <?= e(app_format_money($payment['amount'], $invoice->currency_name)); ?>
                    <?php if ($is_deleted): ?>
                    <br><small class="text-danger"><i class="fa fa-ban"></i> Not counted</small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($is_deleted): ?>
                    <!-- Deleted payment: view only, no edit/delete -->
                    <a href="<?= admin_url('payments/payment/' . $payment['paymentid']); ?>"
                        class="tw-text-neutral-400"
                        title="View (deleted — read only)">
                        <i class="fa-regular fa-eye fa-lg"></i>
                    </a>
                    <?php else: ?>
                    <div class="tw-flex tw-items-center tw-space-x-2">
                        <a href="<?= admin_url('payments/payment/' . $payment['paymentid']); ?>"
                            class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                            <i class="fa-regular fa-pen-to-square fa-lg"></i>
                        </a>
                        <?php if (staff_can('delete', 'payments')) { ?>
                        <a href="<?= admin_url('invoices/delete_payment/' . $payment['paymentid'] . '/' . $payment['invoiceid']); ?>"
                            class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                            <i class="fa-regular fa-trash-can"></i>
                        </a>
                        <?php } ?>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
