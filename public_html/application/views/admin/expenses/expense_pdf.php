<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$html = '<h1>' . $expense->category_name . '</h1>';

if (! empty($expense->expense_name)) {
    $html .= '<h3>' . $expense->expense_name . '</h3>';
}

$html .= '<p>' . _l('expense_amount') . ' <strong>' . app_format_money($expense->amount, $expense->currency_data) . '</strong>';

if ($expense->paymentmode != '0' && ! empty($expense->paymentmode)) {
    $html .= '<br />' . _l('expense_paid_via', $expense->payment_mode_name);
}

$html .= '</p>';

if ($expense->tax != 0 || $expense->tax2 != 0) {
    $html .= '<p>';
    if ($expense->tax != 0) {
        $html .= '<strong>' . _l('tax_1') . ':</strong> ' . $expense->taxrate . '% (' . $expense->tax_name . ')';
        $total = $expense->amount;
        $total += ($total / 100 * $expense->taxrate);
    }
    if ($expense->tax2 != 0) {
        $html .= '<br /><strong>' . _l('tax_2') . ':</strong> ' . $expense->taxrate2 . '% (' . $expense->tax_name2 . ')';
        $total += ($expense->amount / 100 * $expense->taxrate2);
    }

    $html .= '</p>';
    $html .= '<p><strong>' . _l('total_with_tax') . ': ' . app_format_money($total, $expense->currency_data) . '</strong></p>';
}

// The breakdown behind the header amount. Only rendered when the expense has lines,
// so anything recorded before line items existed prints exactly as it did before.
//
// Resolved here rather than passed in, because this template is loaded from more than
// one place and not every caller knows about line items.
$expense_pdf_items = [];
if (isset($expense_items)) {
    $expense_pdf_items = $expense_items;
} else {
    $CI = &get_instance();
    if (!class_exists('Expenses_model', false)) {
        $CI->load->model('expenses_model');
    }
    $expense_pdf_items = $CI->expenses_model->get_expense_items($expense->id);
}

if (!empty($expense_pdf_items)) {
    $ei_subtotal = 0;
    $ei_tax      = 0;

    $html .= '<p><strong>' . _l('expense_line_items') . '</strong></p>';
    $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%">';
    $html .= '<tr style="background-color:#f0f0f0;">'
        . '<td width="6%" align="center"><strong>#</strong></td>'
        . '<td width="38%"><strong>' . _l('expense_line_item_description') . '</strong></td>'
        . '<td width="10%" align="right"><strong>' . _l('quantity') . '</strong></td>'
        . '<td width="14%" align="right"><strong>' . _l('expense_line_item_rate') . '</strong></td>'
        . '<td width="14%" align="right"><strong>' . _l('amount') . '</strong></td>'
        . '<td width="8%" align="right"><strong>' . _l('expense_line_item_tax_percent') . '</strong></td>'
        . '<td width="10%" align="right"><strong>' . _l('expense_line_item_tax_amount') . '</strong></td>'
        . '</tr>';

    foreach ($expense_pdf_items as $ei_i => $ei_row) {
        $ei_subtotal += (float) $ei_row['amount'];
        $ei_tax      += (float) $ei_row['tax_amount'];

        $html .= '<tr>'
            . '<td width="6%" align="center">' . ((int) $ei_i + 1) . '</td>'
            . '<td width="38%">' . $ei_row['description'] . '</td>'
            . '<td width="10%" align="right">' . app_format_number($ei_row['qty']) . '</td>'
            . '<td width="14%" align="right">' . app_format_money($ei_row['rate'], $expense->currency_data) . '</td>'
            . '<td width="14%" align="right">' . app_format_money($ei_row['amount'], $expense->currency_data) . '</td>'
            . '<td width="8%" align="right">' . app_format_number($ei_row['tax_percent']) . '%</td>'
            . '<td width="10%" align="right">' . app_format_money($ei_row['tax_amount'], $expense->currency_data) . '</td>'
            . '</tr>';
    }

    $html .= '<tr style="background-color:#f0f0f0;">'
        . '<td width="6%"></td><td width="38%"></td><td width="10%"></td>'
        . '<td width="14%" align="right"><strong>' . _l('subtotal') . '</strong></td>'
        . '<td width="14%" align="right"><strong>' . app_format_money($ei_subtotal, $expense->currency_data) . '</strong></td>'
        . '<td width="8%"></td>'
        . '<td width="10%" align="right"><strong>' . app_format_money($ei_tax, $expense->currency_data) . '</strong></td>'
        . '</tr>';

    // Tax included, so this is the expense Amount
    $html .= '<tr style="background-color:#f0f0f0;">'
        . '<td width="6%"></td><td width="38%"></td><td width="10%"></td>'
        . '<td width="14%" align="right"><strong>' . _l('total') . '</strong></td>'
        . '<td width="32%" colspan="3" align="right"><strong>'
        . app_format_money($ei_subtotal + $ei_tax, $expense->currency_data) . '</strong></td>'
        . '</tr>';

    $html .= '</table>';
}

$html .= '<p><strong>' . _l('expense_date') . '</strong> ' . _d($expense->date) . '</p>';

if ($expense->billable == 1) {
    if ($expense->invoiceid == null) {
        $html .= '<p><strong>' . _l('invoice') . ':</strong> ' . _l('expense_invoice_not_created');
    } else {
        $html .= '<p><strong>' . _l('invoice') . ':</strong> ' . format_invoice_number($invoice->id);
        if ($invoice->status == 2) {
            $html .= '<br />' . _l('expense_billed');
        } else {
            $html .= '<br />' . _l('expense_not_billed');
        }
        $html .= '</p>';
    }
}

if (! empty($expense->reference_no)) {
    $html .= '<p><strong>' . _l('expense_ref_noe') . '</strong> ' . $expense->reference_no . '</p>';
}

if ($expense->clientid) {
    $html .= '<p><strong>' . _l('expense_customer') . '</strong> ' . $expense->company . '</p>';
}

if ($expense->project_id) {
    $html .= '<p><strong>' . _l('project') . ':</strong> ' . $expense->project_data->name . '</p>';
}

if (! empty($expense->note)) {
    $html .= '<p><strong>' . _l('expense_note') . '</strong> ' . $expense->note . '</p>';
}

$custom_fields = get_custom_fields('expenses');

foreach ($custom_fields as $field) {
    $value = get_custom_field_value($expense->expenseid, $field['id'], 'expenses');

    if ($value == '') {
        continue;
    }

    $html .= '<p><strong>' . $field['name'] . ':</strong> ' . $value . '</p>';
}

$pdf->writeHTML($html, true, false, false, false, '');
