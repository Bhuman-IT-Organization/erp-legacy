<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12 no-padding">
    <div class="panel_s">
        <div class="panel-body">
            <div class="horizontal-scrollable-tabs preview-tabs-top panel-full-width-tabs">
                <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
                <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
                <div class="horizontal-tabs">
                    <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#tab_expense" aria-controls="tab_expense" role="tab" data-toggle="tab">
                                <?= _l('expense'); ?>
                            </a>
                        </li>
                        <?php if (count($child_expenses) > 0 || $expense->recurring != 0) { ?>
                        <li role="presentation">
                            <a href="#tab_child_expenses" aria-controls="tab_child_expenses" role="tab"
                                data-toggle="tab">
                                <?= _l('child_expenses'); ?>
                            </a>
                        </li>
                        <?php } ?>
                        <?php if (! empty($expense_is_credit)) { ?>
                        <li role="presentation">
                            <a href="#tab_expense_payments" aria-controls="tab_expense_payments" role="tab"
                                data-toggle="tab">
                                <?= _l('expense_payments'); ?>
                                <?php $live_expense_payments = array_filter($expense_payments, function ($p) {
                                    return $p['is_deleted'] == 0;
                                });
                            if (count($live_expense_payments) > 0) {
                                echo '<span class="badge">' . count($live_expense_payments) . '</span>';
                            } ?>
                            </a>
                        </li>
                        <?php } ?>
                        <li role="presentation">
                            <a href="#tab_tasks"
                                onclick="init_rel_tasks_table(<?= e($expense->expenseid); ?>,'expense'); return false;"
                                aria-controls="tab_tasks" role="tab" data-toggle="tab">
                                <?= _l('tasks'); ?>
                            </a>
                        </li>
                        <li role="presentation" class="tab-separator">
                            <a href="#tab_reminders"
                                onclick="initDataTable('.table-reminders', admin_url + 'misc/get_reminders/' + <?= $expense->id; ?> + '/' + 'expense', undefined, undefined,undefined,[1,'ASC']); return false;"
                                aria-controls="tab_reminders" role="tab" data-toggle="tab">
                                <?= _l('expenses_reminders'); ?>
                                <?php
                        $total_reminders = total_rows(
                            db_prefix() . 'reminders',
                            [
                                'isnotified' => 0,
                                'staff'      => get_staff_user_id(),
                                'rel_type'   => 'expense',
                                'rel_id'     => $expense->expenseid,
                            ]
                        );
if ($total_reminders > 0) {
    echo '<span class="badge">' . $total_reminders . '</span>';
}
?>
                            </a>
                        </li>
                        <li role="presentation" class="tab-separator toggle_view">
                            <a href="#" onclick="small_table_full_view(); return false;" data-placement="left"
                                data-toggle="tooltip"
                                data-title="<?= _l('toggle_full_view'); ?>">
                                <i class="fa fa-expand"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row mtop20">
                <div class="col-md-6" id="expenseHeadings">
                    <h3 class="tw-font-semibold tw-text-lg tw-text-neutral-700 tw-mt-0 tw-mb-1 tw-flex tw-flex-wrap tw-items-center tw-gap-x-2"
                        id="expenseCategory">
                        <?= e($expense->category_name); ?>
                        <?php if ($expense->billable == 1) {
                            if ($expense->invoiceid == null) {
                                echo '<span class="label label-danger tw-py-0.5">' . _l('expense_invoice_not_created') . '</span>';
                            } else {
                                echo '<span class="bold">' . e(format_invoice_number($invoice->id)) . ' - </span>';
                                if ($invoice->status == 2) {
                                    echo '<span class="label label-success tw-py-0.5">' . _l('expense_billed') . '</span>';
                                } else {
                                    echo '<span class="label label-danger tw-py-0.5">' . _l('expense_not_billed') . '</span>';
                                }
                            }
                        }

                        // Payment status is only meaningful for an expense bought on
                        // credit. Anything else was paid the moment it was recorded.
                        if (! empty($expense_is_credit)) {
                            // Own lang keys rather than the bare unpaid/paid/partially_paid
                            // ones, which live in the purchase module and are not
                            // guaranteed to be loaded on this screen.
                            $expense_status_labels = [
                                'unpaid'         => ['label-danger', 'expense_status_unpaid'],
                                'partially_paid' => ['label-warning', 'expense_status_partially_paid'],
                                'paid'           => ['label-success', 'expense_status_paid'],
                            ];
                            $expense_status = ! empty($expense->payment_status) ? $expense->payment_status : 'unpaid';
                            [$expense_status_class, $expense_status_key] = $expense_status_labels[$expense_status]
                                ?? ['label-default', 'expense_status_unpaid'];

                            echo '<span class="label ' . $expense_status_class . ' tw-py-0.5">' . _l($expense_status_key) . '</span>';

                            if (! empty($expense->expinv_number)) {
                                echo '<span class="label label-default tw-py-0.5">' . e($expense->expinv_number) . '</span>';
                            }
                        } ?>
                    </h3>
                    <?php if (! empty($expense->expense_name)) { ?>
                    <h4 class="tw-text-base tw-font-medium tw-m-0 tw-text-neutral-500" id="expenseName">
                        <?= e($expense->expense_name); ?>
                    </h4>
                    <?php } ?>
                    <h4 class="tw-text-base tw-mt-1 tw-mb-0 tw-text-neutral-500" id="expenserCreator">
                        <?= _l('created_by'); ?>:
                        <a
                            href="<?= admin_url('staff/profile/' . $expense->addedfrom) ?>">
                            <?= e(get_staff_full_name($expense->addedfrom)); ?>
                        </a>
                    </h4>
                </div>
                <div class="col-md-6 _buttons text-right">
                    <div class="visible-xs">
                        <div class="mtop10"></div>
                    </div>
                    <div class="pull-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default pull-left dropdown-toggle"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?= _l('more'); ?>
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dLabel">

                                <?php if (staff_can('create', 'expenses')) { ?>
                                <li>
                                    <a
                                        href="<?= admin_url('expenses/copy/' . $expense->expenseid); ?>">
                                        <?= _l('expense_copy'); ?>
                                    </a>
                                </li>
                                <?php } ?>
                                <li>
                                    <a href="#" onclick="print_expense_information(); return false;">
                                        <?= _l('print'); ?>
                                    </a>
                                </li>
                                <?php if (staff_can('delete', 'expenses')) { ?>
                                <li>
                                    <a class="text-danger delete-text _delete"
                                        href="<?= admin_url('expenses/delete/' . $expense->expenseid); ?>"
                                        data-toggle="tooltip" data-placement="bottom" title="">
                                        <?= _l('expense_delete'); ?>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                    <?php if (staff_can('edit', 'expenses')) { ?>
                    <a class="btn btn-default pull-right ltr:tw-mr-1 rtl:tw-ml-1 !tw-px-3" data-toggle="tooltip"
                        title="<?= _l('expense_edit'); ?>"
                        href="<?= admin_url('expenses/expense/' . $expense->expenseid); ?>">
                        <i class="fa-regular fa-pen-to-square"></i>
                    </a>
                    <?php } ?>
                    <?php
                    // Settling a credit expense. Offered only while a balance remains,
                    // and only for credit expenses - the rest already credited bank or
                    // cash when they were recorded, so paying again would double count.
                    if (! empty($expense_is_credit) && $expense_amount_left > 0 && staff_can('edit', 'expenses')) { ?>
                    <button type="button" class="btn btn-success pull-right ltr:tw-mr-1 rtl:tw-ml-1"
                        data-toggle="modal" data-target="#expense_payment_modal">
                        <i class="fa fa-money ltr:tw-mr-1 rtl:tw-ml-1"></i>
                        <?= _l('expense_record_payment'); ?>
                    </button>
                    <?php } ?>
                    <?php if ($expense->billable == 1 && $expense->invoiceid == null) { ?>
                    <?php if (staff_can('create', 'invoices')) { ?>
                    <button type="button" class="btn btn-primary pull-right ltr:tw-mr-1 rtl:tw-ml-1 expense_convert_btn"
                        data-id="<?= e($expense->expenseid); ?>"
                        data-toggle="modal" data-target="#expense_convert_helper_modal">
                        <?= _l('expense_convert_to_invoice'); ?>
                    </button>
                    <?php } ?>
                    <?php } elseif ($expense->invoiceid != null) { ?>
                    <a href="<?= admin_url('invoices/list_invoices/' . $expense->invoiceid); ?>"
                        class="btn btn-primary ltr:tw-mr-2 rtl:tw-ml-2 pull-right">
                        <?= e(format_invoice_number($invoice->id)); ?>
                    </a>
                    <?php } ?>
                </div>
            </div>
            <div class="clearfix"></div>
            <hr class="hr-panel-separator" />
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane ptop10 active" id="tab_expense"
                    data-empty-note="<?= empty($expense->note); ?>"
                    data-empty-name="<?= empty($expense->expense_name); ?>">
                    <div class="row">
                        <?php
                if ($expense->recurring > 0 || $expense->recurring_from != null) {
                    echo '<div class="col-md-12">';

                    $recurring_expense           = $expense;
                    $show_recurring_expense_info = true;

                    if ($expense->recurring_from != null) {
                        $recurring_expense = $this->expenses_model->get($expense->recurring_from);
                        // Maybe recurring expense not longer recurring?
                        if ($recurring_expense->recurring == 0) {
                            $show_recurring_expense_info = false;
                        } else {
                            $next_recurring_date_compare = $recurring_expense->last_recurring_date;
                        }
                    } else {
                        $next_recurring_date_compare = $recurring_expense->date;
                        if ($recurring_expense->last_recurring_date) {
                            $next_recurring_date_compare = $recurring_expense->last_recurring_date;
                        }
                    }
                    if ($show_recurring_expense_info) {
                        $next_date = date('Y-m-d', strtotime('+' . $recurring_expense->repeat_every . ' ' . strtoupper($recurring_expense->recurring_type), strtotime($next_recurring_date_compare)));
                    } ?>
                        <?php if ($expense->recurring_from == null && $recurring_expense->cycles > 0 && $recurring_expense->cycles == $recurring_expense->total_cycles) { ?>
                        <div class="alert alert-info mbot15">
                            <?= e(_l('recurring_has_ended', _l('expense_lowercase'))); ?>
                        </div>
                        <?php } elseif ($show_recurring_expense_info) { ?>
                        <span class="label label-info">
                            <?= _l('cycles_remaining'); ?>:
                            <b class="tw-ml-2">
                                <?= e($recurring_expense->cycles == 0 ? _l('cycles_infinity') : $recurring_expense->cycles - $recurring_expense->total_cycles);
                            ?>
                            </b>
                        </span>
                        <?php if ($recurring_expense->cycles == 0 || $recurring_expense->cycles != $recurring_expense->total_cycles) {
                            echo '<span class="label label-info tw-ml-1"><i class="fa-regular fa-circle-question fa-fw" data-toggle="tooltip" data-title="' . _l('recurring_recreate_hour_notice', _l('expense')) . '"></i> ' . _l('next_expense_date', '<b class="tw-ml-1">' . e(_d($next_date)) . '</b>') . '</span>';
                        }
                        }
                    if ($expense->recurring_from != null) { ?>
                        <?= '<p class="text-muted no-mbot' . ($show_recurring_expense_info ? ' mtop15' : '') . '">' . _l('expense_recurring_from', '<a href="' . admin_url('expenses/list_expenses/' . $expense->recurring_from) . '" onclick="init_expense(' . $expense->recurring_from . ');return false;">' . e($recurring_expense->category_name) . (! empty($recurring_expense->expense_name) ? ' (' . e($recurring_expense->expense_name) . ')' : '') . '</a></p>'); ?>
                        <?php } ?>
                    </div>
                    <div class="clearfix"></div>
                    <hr class="hr-panel-separator !tw-my-6" />
                    <?php
                } ?>
                    <div class="col-md-6">
                        <p>
                        <div id="amountWrapper">
                            <span
                                class="bold font-medium"><?= _l('expense_amount'); ?></span>
                            <span class="bold font-medium">
                                <?= e(app_format_money($expense->amount, $expense->currency_data)); ?>
                            </span>
                        </div>
                        <?php if ($expense->paymentmode != '0' && ! empty($expense->paymentmode)) {
                            ?>
                        <span class="text-muted text-sm">
                            <?= e(_l('expense_paid_via', $expense->payment_mode_name)); ?>
                        </span><br />
                        <?php
                        } ?>
                        <?php
                        // Cheque details - only captured when the payment mode is a cheque
                        $has_cheque_details = ! empty($expense->cheque_number)
                            || ! empty($expense->cheque_bank_name)
                            || ! empty($expense->cheque_date);
                        if ($has_cheque_details) { ?>
                        <span class="text-muted text-sm">
                            <?php $cheque_parts = [];
                            if (! empty($expense->cheque_number)) {
                                $cheque_parts[] = _l('cheque_number') . ': ' . e($expense->cheque_number);
                            }
                            if (! empty($expense->cheque_bank_name)) {
                                $cheque_parts[] = _l('cheque_bank_name') . ': ' . e($expense->cheque_bank_name);
                            }
                            if (! empty($expense->cheque_date)) {
                                $cheque_parts[] = _l('cheque_date') . ': ' . e(_d($expense->cheque_date));
                            }
                            echo implode(' &nbsp;|&nbsp; ', $cheque_parts); ?>
                        </span><br />
                        <?php } ?>
                        <?php
                                if ($expense->tax != 0) {
                                    echo '<br /><span class="bold">' . _l('tax_1') . ':</span> ' . e($expense->taxrate) . '% (' . e($expense->tax_name) . ')';
                                    $total = $expense->amount;
                                    $total += ($total / 100 * $expense->taxrate);
                                }
if ($expense->tax2 != 0) {
    echo '<br /><span class="bold">' . _l('tax_2') . ':</span> ' . e($expense->taxrate2) . '% (' . e($expense->tax_name2) . ')';
    $total += ($expense->amount / 100 * $expense->taxrate2);
}
if ($expense->tax != 0 || $expense->tax2 != 0) {
    echo '<p class="font-medium bold">' . _l('total_with_tax') . ': ' . e(app_format_money($total, $expense->currency_data)) . '</p>';
}
?>
                        <?php if (! empty($expense_is_credit)) { ?>
                        <table class="table table-borderless no-margin tw-max-w-xs" id="expensePaymentSummary">
                            <tbody>
                                <tr>
                                    <td class="no-padding-left bold"><?= _l('expense_payment_total'); ?></td>
                                    <td class="text-right">
                                        <?= e(app_format_money($expense_total, $expense->currency_data)); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="no-padding-left bold"><?= _l('expense_payment_paid'); ?></td>
                                    <td class="text-right">
                                        <?= e(app_format_money($expense_total_paid, $expense->currency_data)); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="no-padding-left bold"><?= _l('expense_payment_balance_due'); ?></td>
                                    <td class="text-right bold <?= $expense_amount_left > 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?= e(app_format_money($expense_amount_left, $expense->currency_data)); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <?php } ?>
                        <?php
                        // The breakdown behind the header amount. Only rendered when the
                        // expense actually has lines, so anything recorded before line
                        // items existed looks exactly as it did.
                        if (!empty($expense_items)) {
                            $ei_subtotal = 0;
                            $ei_tax      = 0;
                            foreach ($expense_items as $ei_row) {
                                $ei_subtotal += (float) $ei_row['amount'];
                                $ei_tax      += (float) $ei_row['tax_amount'];
                            }
                            ?>
                        <p class="bold mbot5"><?= _l('expense_line_items'); ?></p>
                        <div class="table-responsive mbot15">
                            <table class="table table-bordered no-margin">
                                <thead>
                                    <tr>
                                        <th style="width:34px;">#</th>
                                        <th><?= _l('expense_line_item_description'); ?></th>
                                        <th class="text-right" style="width:70px;"><?= _l('quantity'); ?></th>
                                        <th class="text-right" style="width:110px;"><?= _l('expense_line_item_rate'); ?></th>
                                        <th class="text-right" style="width:120px;"><?= _l('amount'); ?></th>
                                        <th class="text-right" style="width:80px;"><?= _l('expense_line_item_tax_percent'); ?></th>
                                        <th class="text-right" style="width:120px;"><?= _l('expense_line_item_tax_amount'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expense_items as $ei_i => $ei_row) { ?>
                                    <tr>
                                        <td class="text-center"><?= (int) $ei_i + 1; ?></td>
                                        <td><?= e($ei_row['description']); ?></td>
                                        <td class="text-right"><?= e(app_format_number($ei_row['qty'])); ?></td>
                                        <td class="text-right"><?= e(app_format_money($ei_row['rate'], $expense->currency_data)); ?></td>
                                        <td class="text-right"><?= e(app_format_money($ei_row['amount'], $expense->currency_data)); ?></td>
                                        <td class="text-right"><?= e(app_format_number($ei_row['tax_percent'])); ?>%</td>
                                        <td class="text-right"><?= e(app_format_money($ei_row['tax_amount'], $expense->currency_data)); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right bold"><?= _l('subtotal'); ?></td>
                                        <td class="text-right bold"><?= e(app_format_money($ei_subtotal, $expense->currency_data)); ?></td>
                                        <td></td>
                                        <td class="text-right bold"><?= e(app_format_money($ei_tax, $expense->currency_data)); ?></td>
                                    </tr>
                                    <?php // Tax included, so this is the expense Amount ?>
                                    <tr>
                                        <td colspan="4" class="text-right bold"><?= _l('total'); ?></td>
                                        <td colspan="3" class="text-right bold">
                                            <?= e(app_format_money($ei_subtotal + $ei_tax, $expense->currency_data)); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php } ?>
                        <p><span
                                class="bold"><?= _l('expense_date'); ?></span>
                            <span
                                class="text-muted"><?= e(_d($expense->date)); ?></span>
                        </p>
                        </p>
                        <?php if (! empty($expense->reference_no)) { ?>
                        <p class="bold mbot15">
                            <?= _l('expense_ref_noe'); ?>
                            <span class="text-muted">
                                <?= e($expense->reference_no); ?>
                            </span>
                        </p>
                        <?php } ?>
                        <?php if ($expense->clientid) { ?>
                        <p class="bold mbot5">
                            <?= _l('expense_customer'); ?>
                        </p>
                        <p class="mbot15">
                            <a
                                href="<?= admin_url('clients/client/' . $expense->clientid); ?>">
                                <?= e($expense->company); ?>
                            </a>
                        </p>
                        <?php } ?>
                        <?php if (! empty($expense_vendor_name)) { ?>
                        <p class="bold mbot5">
                            <?= _l('pur_vendor'); ?>
                        </p>
                        <p class="mbot15">
                            <a
                                href="<?= admin_url('purchase/vendor/' . $expense->vendor); ?>">
                                <?= e($expense_vendor_name); ?>
                            </a>
                        </p>
                        <?php } ?>
                        <?php if (! empty($expense_credit_gl_name)) { ?>
                        <p class="bold mbot5">
                            <?= _l('credit_gl_account'); ?>
                        </p>
                        <p class="text-muted mbot15">
                            <?= e($expense_credit_gl_name); ?>
                        </p>
                        <?php } ?>
                        <?php if (! empty($expense_ledger_name)) { ?>
                        <p class="bold mbot5">
                            <?= _l('ledger_account'); ?>
                        </p>
                        <p class="text-muted mbot15">
                            <?= e($expense_ledger_name); ?>
                        </p>
                        <?php } ?>
                        <?php if (! empty($expense_cost_center_name)) { ?>
                        <p class="bold mbot5">
                            <?= _l('cost_center'); ?>
                        </p>
                        <p class="text-muted mbot15">
                            <?= e($expense_cost_center_name); ?>
                        </p>
                        <?php } ?>
                        <?php if ($expense->project_id) { ?>
                        <p class="bold mbot5">
                            <?= _l('project'); ?>
                        </p>
                        <p class="mbot15">
                            <a
                                href="<?= admin_url('projects/view/' . $expense->project_id); ?>">
                                <?= e($expense->project_data->name); ?>
                            </a>
                        </p>
                        <?php } ?>
                        <?php
                     $custom_fields = get_custom_fields('expenses');

foreach ($custom_fields as $field) { ?>
                        <?php $value = get_custom_field_value($expense->expenseid, $field['id'], 'expenses');
    if ($value == '') {
        continue;
    } ?>
                        <div class="row mbot10">
                            <div class="col-md-12 mtop5">
                                <p class="mbot5">
                                    <span
                                        class="bold"><?= e(ucfirst($field['name'])); ?></span>
                                </p>
                                <div class="text-left">
                                    <?= $value; ?>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        <?php if ($expense->note != '') { ?>
                        <p class="bold mbot5">
                            <?= _l('expense_note'); ?>
                        </p>
                        <div class="text-muted mbot15">
                            <?= process_text_content_for_display($expense->note); ?>
                        </div>
                        <?php } ?>
                        <?php hooks()->do_action('after_left_panel_expense_preview_template', $expense); ?>
                    </div>
                    <div class="col-md-6" id="expenseReceipt">
                        <h4 class="tw-mt-0 tw-font-bold tw-text-lg">
                            <?= _l('expense_receipt'); ?>
                        </h4>

                        <?php if (empty($expense->attachment)) { ?>
                        <?= form_open('admin/expenses/add_expense_attachment/' . $expense->expenseid, ['class' => 'mtop10 dropzone dropzone-expense-preview dropzone-manual', 'id' => 'expense-receipt-upload']); ?>
                        <div id="dropzoneDragArea" class="dz-default dz-message">
                            <span><?= _l('expense_add_edit_attach_receipt'); ?></span>
                        </div>
                        <?= form_close(); ?>
                        <?php } else { ?>
                        <div class="row">
                            <div class="col-md-10">
                                <i
                                    class="<?= get_mime_class($expense->filetype); ?>"></i>
                                <a
                                    href="<?= site_url('download/file/expense/' . $expense->expenseid); ?>">
                                    <?= e($expense->attachment); ?>
                                </a>
                            </div>
                            <?php if ($expense->attachment_added_from == get_staff_user_id() || is_admin()) { ?>
                            <div class="col-md-2 text-right">
                                <a class="_delete text-danger"
                                    href="<?= admin_url('expenses/delete_expense_attachment/' . $expense->expenseid . '/preview'); ?>"
                                    class="text-danger">
                                    <i class="fa fa fa-times"></i>
                                </a>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } ?>
                        <?php hooks()->do_action('after_right_panel_expense_preview_template', $expense); ?>
                    </div>
                </div>
            </div>
            <?php if (count($child_expenses) > 0 || $expense->recurring != 0) { ?>
            <div role="tabpanel" class="tab-pane" id="tab_child_expenses">
                <?php if (count($child_expenses) > 0) { ?>
                <h4 class="mbot25 mtop25">
                    <?= _l('expenses_created_from_this_recurring_expense'); ?>
                </h4>
                <ul class="list-group">
                    <?php foreach ($child_expenses as $recurring) { ?>
                    <li class="list-group-item">
                        <a href="<?= admin_url('expenses/list_expenses/' . $recurring->expenseid); ?>"
                            onclick="init_expense(<?= e($recurring->expenseid); ?>); return false;"
                            target="_blank"><?= e($recurring->category_name . (! empty($recurring->expense_name) ? ' (' . e($recurring->expense_name) . ')' : '')); ?>
                        </a>
                        <br />
                        <span class="inline-block mtop10">
                            <?= '<span class="bold">' . e(_d($recurring->date)) . '</span>'; ?><br />
                            <p><span
                                    class="bold font-medium"><?= _l('expense_amount'); ?></span>
                                <span class="text-danger bold font-medium">
                                    <?= e(app_format_money($recurring->amount, $recurring->currency_data)); ?>
                                </span>
                                <?php
          if ($recurring->tax != 0) {
              echo '<br /><span class="bold">' . _l('tax_1') . ':</span> ' . e($recurring->taxrate) . '% (' . e($recurring->tax_name) . ')';
              $total = $recurring->amount;
              $total += ($total / 100 * $recurring->taxrate);
          }
                        if ($recurring->tax2 != 0) {
                            echo '<br /><span class="bold">' . _l('tax_2') . ':</span> ' . e($recurring->taxrate2) . '% (' . e($recurring->tax_name2) . ')';
                            $total += ($recurring->amount / 100 * $recurring->taxrate2);
                        }
                        if ($recurring->tax != 0 || $recurring->tax2 != 0) {
                            echo '<p class="font-medium bold text-danger">' . _l('total_with_tax') . ': ' . e(app_format_money($total, $recurring->currency_data)) . '</p>';
                        }
                        ?>
                        </span>
                    </li>
                    <?php } ?>
                </ul>
                <?php } else { ?>
                <p class="bold">
                    <?= e(_l('no_child_found', _l('expenses'))); ?>
                </p>
                <?php } ?>
            </div>
            <?php } ?>
            <?php if (! empty($expense_is_credit)) { ?>
            <div role="tabpanel" class="tab-pane ptop10" id="tab_expense_payments">
                <?php if ($expense_amount_left > 0 && staff_can('edit', 'expenses')) { ?>
                <button type="button" class="btn btn-success mbot15" data-toggle="modal"
                    data-target="#expense_payment_modal">
                    <i class="fa fa-money ltr:tw-mr-1 rtl:tw-ml-1"></i>
                    <?= _l('expense_record_payment'); ?>
                </button>
                <?php } ?>
                <?php if (count($expense_payments) == 0) { ?>
                <p class="text-muted"><?= _l('expense_payments_no_payments'); ?></p>
                <?php } else { ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th><?= _l('expense_payment_number'); ?></th>
                                <th><?= _l('expense_payment_date'); ?></th>
                                <th><?= _l('payment_mode'); ?></th>
                                <th><?= _l('expense_payment_paid_from'); ?></th>
                                <th><?= _l('payment_transaction_id'); ?></th>
                                <th class="text-right"><?= _l('expense_payment_amount'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expense_payments as $expense_payment) { ?>
                            <tr<?= $expense_payment['is_deleted'] == 1 ? ' class="text-muted"' : ''; ?>>
                                <td>
                                    <?= e('EXPPAY' . str_pad($expense_payment['id'], 5, '0', STR_PAD_LEFT)); ?>
                                    <?php if ($expense_payment['is_deleted'] == 1) { ?>
                                    <span class="label label-default"><?= _l('expense_payment_reversed_label'); ?></span>
                                    <?php } ?>
                                </td>
                                <td><?= e(_d($expense_payment['date'])); ?></td>
                                <td>
                                    <?php // Null coalesced throughout - these columns are nullable and passing
                                          // null to htmlspecialchars is deprecated on PHP 8.1+ ?>
                                    <?= e($expense_payment['payment_mode_name'] ?? ''); ?>
                                    <?php if (! empty($expense_payment['cheque_number'])) { ?>
                                    <br /><span class="text-muted text-sm">
                                        <?= _l('cheque_number'); ?>:
                                        <?= e($expense_payment['cheque_number']); ?>
                                    </span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?= e(trim(($expense_payment['gl_account_number'] ?? '') . ' - ' . ($expense_payment['gl_account_name'] ?? ''), ' -')); ?>
                                </td>
                                <td><?= e($expense_payment['transactionid'] ?? ''); ?></td>
                                <td class="text-right">
                                    <?= e(app_format_money($expense_payment['amount'], $expense->currency_data)); ?>
                                </td>
                                <td class="text-right">
                                    <?php if ($expense_payment['is_deleted'] == 0 && staff_can('delete', 'expenses')) { ?>
                                    <a href="<?= admin_url('expenses/delete_expense_payment/' . $expense_payment['id']); ?>"
                                        class="text-danger _delete" data-toggle="tooltip"
                                        title="<?= _l('expense_payment_reverse'); ?>">
                                        <i class="fa fa-rotate-left"></i>
                                    </a>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
            <div role="tabpanel" class="tab-pane" id="tab_tasks">
                <?php init_relation_tasks_table(['data-new-rel-id' => $expense->expenseid, 'data-new-rel-type' => 'expense'], 'tasksFilters'); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_reminders">
                <a href="#" data-toggle="modal" class="btn btn-primary"
                    data-target=".reminder-modal-expense-<?= e($expense->id); ?>"><i
                        class="fa-regular fa-bell"></i>
                    <?= _l('expense_set_reminder_title'); ?></a>
                <hr />
                <?php render_datatable([_l('reminder_description'), _l('reminder_date'), _l('reminder_staff'), _l('reminder_is_notified')], 'reminders'); ?>
                <?php $this->load->view('admin/includes/modals/reminder', ['id' => $expense->id, 'name' => 'expense', 'members' => $members, 'reminder_title' => _l('expense_set_reminder_title')]); ?>
            </div>
        </div>
    </div>
</div>
</div>
<?php if (! empty($expense_is_credit) && $expense_amount_left > 0 && staff_can('edit', 'expenses')) { ?>
<div class="modal fade" id="expense_payment_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <?php
        // form_open() rather than a raw <form> - APP_CSRF_PROTECTION is on and a
        // form without the token is rejected with 419 Page Expired.
        echo form_open(admin_url('expenses/expense_payment/' . $expense->expenseid), ['id' => 'expense-payment-form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?= _l('expense_record_payment'); ?></h4>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    <?= _l(
                        'expense_payment_modal_intro',
                        [
                            ! empty($expense->expinv_number) ? e($expense->expinv_number) : '#' . e($expense->id),
                            e(app_format_money($expense_amount_left, $expense->currency_data)),
                        ]
                    ); ?>
                </p>
                <hr class="hr-panel-heading" />
                <div class="row">
                    <div class="col-md-6">
                        <?= render_input('amount', 'expense_payment_amount', $expense_amount_left, 'number', [
                            'step'     => '0.01',
                            'min'      => '0.01',
                            'max'      => $expense_amount_left,
                            'required' => true,
                        ]); ?>
                    </div>
                    <div class="col-md-6">
                        <?= render_date_input('date', 'expense_payment_date', _d(date('Y-m-d')), ['required' => true]); ?>
                    </div>
                </div>
                <?= render_select('paymentmode', $expense_payment_modes, ['id', 'name'], 'payment_mode', '', ['required' => true]); ?>
                <?= render_select('gl_account', $expense_pay_accounts, ['id', 'name'], 'expense_payment_paid_from', '', ['required' => true]); ?>
                <?php if (! empty($expense_credit_gl_name)) { ?>
                <p class="text-muted tw-text-sm -tw-mt-2">
                    <?= _l('expense_payment_paid_from_help', e($expense_credit_gl_name)); ?>
                </p>
                <?php } ?>
                <div id="expense_payment_cheque_fields" class="hide">
                    <div class="row">
                        <div class="col-md-4">
                            <?= render_input('cheque_number', 'cheque_number'); ?>
                        </div>
                        <div class="col-md-4">
                            <?= render_input('cheque_bank_name', 'cheque_bank_name'); ?>
                        </div>
                        <div class="col-md-4">
                            <?= render_date_input('cheque_date', 'cheque_date'); ?>
                        </div>
                    </div>
                </div>
                <?= render_input('transactionid', 'payment_transaction_id'); ?>
                <?= render_textarea('note', 'note', '', ['rows' => 4]); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="submit" class="btn btn-success"
                    data-loading-text="<?= _l('wait_text'); ?>"><?= _l('submit'); ?></button>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>
<?php } ?>
<script>
    init_btn_with_tooltips();
    init_selectpicker();
    init_datepicker();
    init_form_reminder();
    init_tabs_scrollable();

    // ── Expense payment modal ────────────────────────────────────────────────
    // This template is injected by AJAX, so handlers are bound with the form's own
    // id and unbound first to survive the panel being reloaded.
    (function() {
        var $expensePaymentForm = $('#expense-payment-form');
        if ($expensePaymentForm.length === 0) return;

        var expensePaymentId = <?= (int) $expense->expenseid; ?>;

        // Cheque details only apply to a cheque payment mode. Matched on the mode
        // name because ids differ per install.
        $expensePaymentForm.off('change.expensePayment').on('change.expensePayment', 'select[name="paymentmode"]', function() {
            var modeText = $(this).find('option:selected').text().toLowerCase();
            var isCheque = modeText.indexOf('cheque') !== -1 || modeText.indexOf('check') !== -1;

            $('#expense_payment_cheque_fields').toggleClass('hide', !isCheque);
            if (!isCheque) {
                $('#expense_payment_cheque_fields').find('input').val('');
            }
        });

        $expensePaymentForm.off('submit.expensePayment').on('submit.expensePayment', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');

            $submit.button('loading');

            $.post($form.attr('action'), $form.serialize()).done(function(response) {
                try {
                    response = typeof(response) === 'string' ? JSON.parse(response) : response;
                } catch (err) {
                    alert_float('danger', '<?= _l('expense_payment_failed'); ?>');
                    $submit.button('reset');
                    return;
                }

                if (response.success) {
                    alert_float('success', response.message);

                    // Wait for the modal to finish hiding before replacing the
                    // panel. Reloading mid-animation leaves the backdrop and the
                    // modal-open class on body behind, which locks the page.
                    $('#expense_payment_modal')
                        .one('hidden.bs.modal', function() {
                            // Reload the preview so the balance, status and payments
                            // list all come from the server rather than being
                            // patched in here
                            init_expense(expensePaymentId);
                        })
                        .modal('hide');
                } else {
                    alert_float('danger', response.message);
                    $submit.button('reset');
                }
            }).fail(function() {
                alert_float('danger', '<?= _l('expense_payment_failed'); ?>');
                $submit.button('reset');
            });
        });
    })();

    if ($('#dropzoneDragArea').length > 0) {
        if (typeof(expensePreviewDropzone) != 'undefined') {
            expensePreviewDropzone.destroy();
        }
        expensePreviewDropzone = new Dropzone("#expense-receipt-upload", appCreateDropzoneOptions({
            clickable: '#dropzoneDragArea',
            maxFiles: 1,
            success: function(file, response) {
                init_expense( <?= e($expense->expenseid); ?> );
            }
        }));
    }
</script>