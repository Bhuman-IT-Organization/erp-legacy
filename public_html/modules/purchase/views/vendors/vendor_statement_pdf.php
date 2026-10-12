<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Cheque details for a payment line: bank, number and date. Declared once and shared
// by the purchase invoice payment and expense payment branches below, so the two can
// never drift apart. function_exists() because this view is loaded per request and
// would otherwise redeclare on a second load in the same process.
if (!function_exists('statement_pdf_cheque_details')) {
    function statement_pdf_cheque_details($number, $bank_name, $cheque_date)
    {
        $number      = trim((string) $number);
        $bank_name   = trim((string) $bank_name);
        $cheque_date = trim((string) $cheque_date);

        // Nothing to say for cash or a transfer
        if ($number === '' && $bank_name === '') {
            return '';
        }

        $parts = [];

        if ($number !== '') {
            $parts[] = _l('cheque_number') . ': ' . $number;
        }

        if ($bank_name !== '') {
            $parts[] = _l('cheque_bank_name') . ': ' . $bank_name;
        }

        // A zero date is stored rather than null on older rows, and rendering it would
        // show 01-01-1970
        if ($cheque_date !== '' && $cheque_date !== '0000-00-00') {
            $parts[] = _l('cheque_date') . ': ' . _d($cheque_date);
        }

        return '<br />' . implode(' - ', $parts);
    }
}

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column = '<div style="color:#424242;">';
$info_right_column .= format_organization_info();
$info_right_column .= '</div>';

// Add logo
$info_left_column .= pdf_logo_url();
// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(10);

// Get Y position for the separation
$y = $pdf->getY();

// Bill to
$client_details = '<b>' . _l('statement_bill_to') . '</b>';
$client_details .= '<div style="color:#424242;">';
$client_details .= format_vendor_info($statement['client'], 'statement', 'billing');
$client_details .= '</div>';

$pdf->writeHTMLCell(($dimensions['wk'] / 2) - $dimensions['lm'] + 15, '', '', $y, $client_details, 0, 0, false, true, 'J', true);

$summary = '';
$summary .= '<h2>' . _l('account_summary') . '</h2>';
$summary .= '<div style="color:#676767;">' . _l('statement_from_to', [
    _d($statement['from']),
    _d($statement['to']),
]) . '</div>';
$summary .= '<hr />';
$summary .= '
<table cellpadding="4" border="0" style="color:#424242;" width="100%">
   <tbody>
      <tr>
          <td align="left"><br /><br />' . _l('statement_beginning_balance') . ':</td>
          <td><br /><br />' . app_format_money($statement['beginning_balance'], $statement['currency']) . '</td>
      </tr>
      <tr>
          <td align="left">' . _l('invoiced_amount') . ':</td>
          <td>' . app_format_money($statement['invoiced_amount'], $statement['currency']) . '</td>
      </tr>
      <tr>
          <td align="left">' . _l('amount_paid') . ':</td>
          <td>' . app_format_money($statement['amount_paid'], $statement['currency']) . '</td>
      </tr>
  </tbody>
  <tfoot>
      <tr>
        <td align="left"><b>' . _l('balance_due') . '</b>:</td>
        <td>' . app_format_money($statement['balance_due'], $statement['currency']) . '</td>
    </tr>
  </tfoot>
</table>';

$pdf->writeHTMLCell(($dimensions['wk'] / 2) - $dimensions['rm'] - 15, '', '', '', $summary, 0, 1, false, true, 'R', true);


$summary_info = '
<div style="text-align: center;">
    ' . _l('customer_statement_info', [
    _d($statement['from']),
    _d($statement['to']),
]) . '
</div>';

$pdf->ln(9);
$pdf->writeHTMLCell($dimensions['wk'] - ($dimensions['rm'] + $dimensions['lm']), '', '', $pdf->getY(), $summary_info, 0, 1, false, true, 'C', false);
$pdf->ln(9);

$tmpBeginningBalance = $statement['beginning_balance'];

// cellpadding reduced from 8 to 4. At 8 the padding ate enough of each column
// that a date wrapped as "13-08-202" / "6"; the date columns are also widened
// and marked nowrap so they cannot break again if the page narrows.
$tblhtml = '<table width="100%" cellspacing="0" cellpadding="4" border="0">
<thead>
 <tr height="10" bgcolor="#e8e8e8" style="color:#424242;">
     <th width="13%" nowrap="nowrap"><b>' . _l('statement_heading_date') . '</b></th>
     <th width="17%"><b>' . _l('statement_heading_details') . '</b></th>
     <th width="14%" nowrap="nowrap"><b>' . _l('statement_heading_vendor_invoice_number') . '</b></th>
     <th width="13%" align="center" nowrap="nowrap"><b>' . _l('statement_heading_due_date') . '</b></th>
     <th align="right"><b>' . _l('statement_heading_amount') . '</b></th>
     <th align="right"><b>' . _l('statement_heading_payments') . '</b></th>
     <th align="right"><b>' . _l('statement_heading_balance') . '</b></th>
 </tr>
</thead>
<tbody>
 <tr>
     <td width="13%" nowrap="nowrap">' . _d($statement['from']) . '</td>
     <td width="17%">' . _l('statement_beginning_balance') . '</td>
     <td width="14%"></td>
     <td width="13%"></td>
     <td align="right">' . app_format_money($statement['beginning_balance'], $statement['currency'], true) . '</td>
     <td></td>
     <td align="right">' . app_format_money($statement['beginning_balance'], $statement['currency'], true) . '</td>
 </tr>';
$count = 0;
foreach ($statement['result'] as $data) {
    $tblhtml .= '<tr' . (++$count % 2 ? ' bgcolor="#f6f5f5"' : '') . '>
  <td width="13%" nowrap="nowrap">' . _d($data['date']) . '</td>
  <td width="17%">';
    if (isset($data['invoice_id'])) {
        // Due date is rendered in its own column below, so the details text
        // only carries the invoice number here.
        $tblhtml .= _l('statement_invoice_details_only', get_pur_invoice_number($data['invoice_id']));
    } elseif (isset($data['payment_id'])) {
        // One cheque covering several invoices arrives here as a single row carrying
        // the whole cheque amount, so every invoice it settled is named rather than
        // just the first.
        $payment_invoice_ref = get_pur_invoice_number($data['payment_invoice_id']);

        if (isset($data['payment_grouped_count']) && $data['payment_grouped_count'] > 1) {
            $payment_invoice_refs = [];
            foreach ($data['payment_invoice_ids'] as $payment_grouped_invoice_id) {
                $payment_invoice_refs[] = get_pur_invoice_number($payment_grouped_invoice_id);
            }
            $payment_invoice_ref = implode(', ', $payment_invoice_refs);
        }

        $tblhtml .= _l('statement_payment_details', [
            '#' . $data['payment_id'],
            $payment_invoice_ref,
        ]);

        $tblhtml .= statement_pdf_cheque_details(
            isset($data['payment_cheque_number']) ? $data['payment_cheque_number'] : '',
            isset($data['payment_cheque_bank_name']) ? $data['payment_cheque_bank_name'] : '',
            isset($data['payment_cheque_date']) ? $data['payment_cheque_date'] : ''
        );
    } elseif (isset($data['debit_note_id'])) {
        $tblhtml .= _l('statement_debit_note_details', format_debit_note_number($data['debit_note_id']));
    } elseif (isset($data['debit_id'])) {
        $tblhtml .= _l('statement_debits_applied_details', [
            format_debit_note_number($data['debit_applied_debit_note_id']),
            app_format_money($data['debit_amount'], $statement['currency'], true),
            get_pur_invoice_number($data['debit_invoice_id']),
        ]);
    } elseif (isset($data['debit_note_refund_id'])) {
        $tblhtml .= _l('statement_debit_note_refund', format_debit_note_number($data['refund_debit_note_id']));
    } elseif (isset($data['expinv_id'])) {
        // Expense bought on credit - owed to this vendor, same as an invoice
        $tblhtml .= _l('statement_expense_invoice_details', $data['expinv_number']);
    } elseif (isset($data['expense_payment_id'])) {
        // Settling an expense that was bought on credit. One cheque covering several
        // expenses arrives here as a single row carrying the whole cheque amount, so
        // every expense invoice it settled is named rather than just the first.
        $expense_payment_ref = $data['expense_payment_expinv_number'];

        if (isset($data['expense_payment_grouped_count']) && $data['expense_payment_grouped_count'] > 1) {
            $expense_payment_ref = implode(', ', $data['expense_payment_expinv_numbers']);
        }

        $tblhtml .= _l('statement_expense_payment_details', [
            'EXPPAY' . str_pad($data['expense_payment_id'], 5, '0', STR_PAD_LEFT),
            $expense_payment_ref,
        ]);

        $tblhtml .= statement_pdf_cheque_details(
            isset($data['expense_payment_cheque_number']) ? $data['expense_payment_cheque_number'] : '',
            isset($data['expense_payment_cheque_bank_name']) ? $data['expense_payment_cheque_bank_name'] : '',
            isset($data['expense_payment_cheque_date']) ? $data['expense_payment_cheque_date'] : ''
        );
    } elseif (isset($data['shipping_charge_id'])) {
        // Freight bill - credits the vendor's payable account, same as an invoice
        $sc_ref = !empty($data['shipping_charge_number'])
            ? $data['shipping_charge_number']
            : 'ISC' . str_pad($data['shipping_charge_id'], 5, '0', STR_PAD_LEFT);
        $tblhtml .= _l('statement_shipping_charge_details', [
            $sc_ref,
            $data['shipping_charge_vendor_ref'] ?: '-',
        ]);
    }

    $tblhtml .= '</td>';

    // Vendor Invoice Number column. Only documents the vendor billed us for carry
    // their own reference - a payment or a debit note we raised does not.
    // No nowrap here: a cheque that settled many documents lists every vendor
    // reference, which has to be allowed to wrap or it overflows the column.
    $tblhtml .= '<td width="14%">';
    if (isset($data['invoice_id'])) {
        $tblhtml .= !empty($data['vendor_invoice_number']) ? $data['vendor_invoice_number'] : '-';
    } elseif (isset($data['shipping_charge_id'])) {
        $tblhtml .= !empty($data['shipping_charge_vendor_ref']) ? $data['shipping_charge_vendor_ref'] : '-';
    } elseif (isset($data['expinv_id'])) {
        // The vendor's reference from the expense, not our own expinv_number - that
        // already appears in the Details column.
        $tblhtml .= !empty($data['expinv_vendor_ref']) ? $data['expinv_vendor_ref'] : '-';
    } elseif (isset($data['expense_payment_id']) && !empty($data['expense_payment_vendor_refs'])) {
        // A cheque settling several expenses names every vendor invoice it cleared
        $tblhtml .= implode(', ', $data['expense_payment_vendor_refs']);
    } elseif (isset($data['payment_id']) && !empty($data['payment_vendor_refs'])) {
        $tblhtml .= implode(', ', $data['payment_vendor_refs']);
    } elseif (isset($data['payment_id']) && !empty($data['payment_vendor_ref'])) {
        $tblhtml .= $data['payment_vendor_ref'];
    } else {
        $tblhtml .= '-';
    }
    $tblhtml .= '</td>';

    // Due Date column — only invoice rows carry a due date
    $tblhtml .= '<td width="13%" align="center" nowrap="nowrap">';
    if (isset($data['invoice_id']) && !empty($data['duedate']) && $data['duedate'] != '0000-00-00') {
        $tblhtml .= _d($data['duedate']);
    } else {
        $tblhtml .= '-';
    }
    $tblhtml .= '</td>';

    $tblhtml .= '<td align="right">';
    if (isset($data['invoice_id'])) {
        $tblhtml .= app_format_money($data['invoice_amount'], $statement['currency'], true);
    } elseif (isset($data['debit_note_id'])) {
        $tblhtml .= app_format_money($data['debit_note_amount'], $statement['currency'], true);
    } elseif (isset($data['expinv_id'])) {
        $tblhtml .= app_format_money($data['expinv_amount'], $statement['currency'], true);
    } elseif (isset($data['shipping_charge_id'])) {
        $tblhtml .= app_format_money($data['shipping_charge_amount'], $statement['currency'], true);
    }
    $tblhtml .= '</td>
        <td align="right">';
    if (isset($data['payment_id'])) {
        $tblhtml .= app_format_money($data['payment_total'], $statement['currency'], true);
    } elseif (isset($data['debit_note_refund_id'])) {
        $tblhtml .= app_format_money($data['refund_amount'], $statement['currency'], true);
    } elseif (isset($data['expense_payment_id'])) {
        $tblhtml .= app_format_money($data['expense_payment_total'], $statement['currency'], true);
    }
    $tblhtml .= '</td>
            <td align="right">';
    if (isset($data['invoice_id'])) {
        $tmpBeginningBalance = ($tmpBeginningBalance + $data['invoice_amount']);
    } elseif (isset($data['payment_id'])) {
        $tmpBeginningBalance = ($tmpBeginningBalance - $data['payment_total']);
    } elseif (isset($data['debit_note_id'])) {
        $tmpBeginningBalance = ($tmpBeginningBalance - $data['debit_note_amount']);
    } elseif (isset($data['debit_note_refund_id'])) {
        $tmpBeginningBalance = ($tmpBeginningBalance + $data['refund_amount']);
    } elseif (isset($data['expinv_id'])) {
        // Increases what is owed. Note the PDF running balance uses the opposite
        // sign convention to the on screen statement, hence + here and - there.
        $tmpBeginningBalance = ($tmpBeginningBalance + $data['expinv_amount']);
    } elseif (isset($data['expense_payment_id'])) {
        // Reduces what is owed, same direction as an invoice payment
        $tmpBeginningBalance = ($tmpBeginningBalance - $data['expense_payment_total']);
    } elseif (isset($data['shipping_charge_id'])) {
        // Increases what is owed, same direction as a purchase invoice. Sign matches
        // the PDF convention above, which is the opposite of the on screen statement.
        $tmpBeginningBalance = ($tmpBeginningBalance + $data['shipping_charge_amount']);
    }
    if (!isset($data['debit_id'])) {
        $tblhtml .= app_format_money($tmpBeginningBalance, $statement['currency'], true);
    }

    $tblhtml .= '</td>
            </tr>';
}
$tblhtml .= '</tbody>
        <tfoot>
         <tr style="color:#424242;">
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td align="right"><b>' . _l('balance_due') . '</b></td>
             <td></td>
             <td align="right">
                 <b>' . app_format_money($statement['balance_due'], $statement['currency']) . '</b>
             </td>
         </tr>
     </tfoot>
 </table>';

$pdf->writeHTML($tblhtml, true, false, false, false, '');
