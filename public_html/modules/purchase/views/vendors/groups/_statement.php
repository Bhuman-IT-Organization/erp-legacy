<?php defined('BASEPATH') or exit('No direct script access allowed');

// Cheque details for a payment line: bank, number and date. Declared once and shared
// by the purchase invoice payment and expense payment branches below, so the two can
// never drift apart. function_exists() because this view is loaded per request and
// would otherwise redeclare on a second load in the same process.
if (!function_exists('statement_cheque_details_html')) {
    function statement_cheque_details_html($number, $bank_name, $cheque_date)
    {
        $number      = trim((string) $number);
        $bank_name   = trim((string) $bank_name);
        $cheque_date = trim((string) $cheque_date);

        // Nothing to say for cash or a transfer
        if ($number === '' && $bank_name === '') {
            return '';
        }

        $parts = array();

        if ($number !== '') {
            $parts[] = _l('cheque_number') . ': ' . e($number);
        }

        if ($bank_name !== '') {
            $parts[] = _l('cheque_bank_name') . ': ' . e($bank_name);
        }

        // A zero date is stored rather than null on older rows, and rendering it would
        // show 01-01-1970
        if ($cheque_date !== '' && $cheque_date !== '0000-00-00') {
            $parts[] = _l('cheque_date') . ': ' . _d($cheque_date);
        }

        return '<br /><span class="text-muted">' . implode(' &middot; ', $parts) . '</span>';
    }
}
?>
<div class="col-md-5">
  <div class="text-right">
    <h4 class="no-margin bold"><?php echo _l('account_summary'); ?></h4>
    <p class="text-muted"><?php echo _l('statement_from_to',array($from,$to)); ?></p>
    <hr />
    <table class="table statement-account-summary">
      <tbody>
        <tr>
          <td class="text-left"><?php echo _l('statement_beginning_balance'); ?>:</td>
          <td><?php echo app_format_money($statement['beginning_balance'], $statement['currency']); ?></td>
        </tr>
        <tr>
          <td class="text-left"><?php echo _l('invoiced_amount'); ?>:</td>
          <td><?php echo app_format_money($statement['invoiced_amount'], $statement['currency']); ?></td>
        </tr>
        <tr>
          <td class="text-left"><?php echo _l('amount_paid'); ?>:</td>
          <td><?php echo app_format_money($statement['amount_paid'], $statement['currency']); ?></td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td class="text-left"><b><?php echo _l('balance_due'); ?></b>:</td>
          <td><?php echo app_format_money($statement['balance_due'], $statement['currency']); ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<div class="col-md-12">
  <div class="text-center bold padding-10">
    <?php echo _l('customer_statement_info',array($from,$to)); ?>
  </div>
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
     <thead>
       <tr>
         <?php
         // Dates must not wrap. Without this the column shrinks to fit the wider
         // Details text and a date breaks across two lines as "13-08-202" / "6".
         $nowrap = ' style="white-space:nowrap"';
         ?>
         <th<?php echo $nowrap; ?>><b><?php echo _l('statement_heading_date'); ?></b></th>
         <th><b><?php echo _l('statement_heading_details'); ?></b></th>
         <th<?php echo $nowrap; ?>><b><?php echo _l('statement_heading_vendor_invoice_number'); ?></b></th>
         <th<?php echo $nowrap; ?>><b><?php echo _l('statement_heading_due_date'); ?></b></th>
         <th class="text-right"><b><?php echo _l('statement_heading_amount'); ?></b></th>
         <th class="text-right"><b><?php echo _l('statement_heading_payments'); ?></b></b></th>
         <th class="text-right"><b><?php echo _l('statement_heading_balance'); ?></b></b></th>
       </tr>
     </thead>
     <tbody>
       <tr>
         <td<?php echo $nowrap; ?>><?php echo $from; ?></td>
         <td><?php echo _l('statement_beginning_balance'); ?></td>
         <td></td>
         <td></td>
         <td class="text-right"><?php echo app_format_money($statement['beginning_balance'], $statement['currency'], true); ?></td>
         <td></td>
         <td class="text-right"><?php echo app_format_money($statement['beginning_balance'], $statement['currency'], true); ?></td>
       </tr>
       <?php
       $tmpBeginningBalance = $statement['beginning_balance'];
       foreach($statement['result'] as $data){ ?>
         <tr>
           <td<?php echo $nowrap; ?>><?php echo _d($data['date']); ?></td>
           <td>
            <?php
            if(isset($data['invoice_id'])) {
              // Due date has its own column now, so the details text carries only the
              // invoice number rather than repeating the date - same as the PDF.
              echo _l('statement_invoice_details_only','<a href="'.admin_url('purchase/purchase_invoice/'.$data['invoice_id']).'" target="_blank">'.get_pur_invoice_number($data['invoice_id']).'</a>');
            } else if(isset($data['payment_id'])){
             // One cheque covering several invoices arrives here as a single row
             // carrying the whole cheque amount, so every invoice it settled is named
             // rather than just the first.
             $payment_invoice_ref = get_pur_invoice_number($data['payment_invoice_id']);

             if(isset($data['payment_grouped_count']) && $data['payment_grouped_count'] > 1){
               $payment_invoice_refs = array();
               foreach($data['payment_invoice_ids'] as $payment_grouped_invoice_id){
                 $payment_invoice_refs[] = get_pur_invoice_number($payment_grouped_invoice_id);
               }
               $payment_invoice_ref = implode(', ', $payment_invoice_refs);
             }

             echo _l('statement_payment_details',array('<a href="'.admin_url('purchase/payment_invoice/'.$data['payment_id']).'" target="_blank">'.'#'.$data['payment_id'].'</a>',$payment_invoice_ref));

             echo statement_cheque_details_html(
               isset($data['payment_cheque_number']) ? $data['payment_cheque_number'] : '',
               isset($data['payment_cheque_bank_name']) ? $data['payment_cheque_bank_name'] : '',
               isset($data['payment_cheque_date']) ? $data['payment_cheque_date'] : ''
             );
           } else if(isset($data['debit_note_id'])) {
            echo _l('statement_debit_note_details',array('<a href="'.admin_url('purchase/debit_notes/'.$data['debit_note_id']).'" target="_blank">'.format_debit_note_number($data['debit_note_id']).'</a>'));
          } else if(isset($data['debit_id'])) {
            echo _l('statement_debits_applied_details',array(
              '<a href="'.admin_url('purchase/debit_notes/'.$data['debit_applied_debit_note_id']).'" target="_blank">'.format_debit_note_number($data['debit_applied_debit_note_id']).'</a>',
              app_format_money($data['debit_amount'], $statement['currency'], true),
              get_pur_invoice_number($data['debit_invoice_id'])
            )
          );
          } else if(isset($data['debit_note_refund_id'])) {
            echo _l('statement_debit_note_refund', format_debit_note_number($data['refund_debit_note_id']));
          } else if(isset($data['expinv_id'])) {
            // Expense bought on credit - owed to this vendor
            echo _l('statement_expense_invoice_details', array(
              '<a href="'.admin_url('expenses/list_expenses/'.$data['expinv_id']).'" target="_blank">'.e($data['expinv_number']).'</a>'
            ));
          } else if(isset($data['expense_payment_id'])) {
            // Settling an expense that was bought on credit. One cheque covering
            // several expenses arrives here as a single row carrying the whole cheque
            // amount, so every expense invoice it settled is named rather than just
            // the first.
            if(isset($data['expense_payment_grouped_count']) && $data['expense_payment_grouped_count'] > 1){
              echo _l('statement_expense_payment_details', array(
                'EXPPAY'.str_pad($data['expense_payment_id'], 5, '0', STR_PAD_LEFT),
                e(implode(', ', $data['expense_payment_expinv_numbers']))
              ));
            } else {
              echo _l('statement_expense_payment_details', array(
                'EXPPAY'.str_pad($data['expense_payment_id'], 5, '0', STR_PAD_LEFT),
                '<a href="'.admin_url('expenses/list_expenses/'.$data['expense_payment_expense_id']).'" target="_blank">'.e($data['expense_payment_expinv_number']).'</a>'
              ));
            }

            echo statement_cheque_details_html(
              isset($data['expense_payment_cheque_number']) ? $data['expense_payment_cheque_number'] : '',
              isset($data['expense_payment_cheque_bank_name']) ? $data['expense_payment_cheque_bank_name'] : '',
              isset($data['expense_payment_cheque_date']) ? $data['expense_payment_cheque_date'] : ''
            );
          } else if(isset($data['shipping_charge_id'])) {
            // Freight bill - credits the vendor's payable account, so it is owed
            $sc_ref = !empty($data['shipping_charge_number'])
                ? $data['shipping_charge_number']
                : 'ISC'.str_pad($data['shipping_charge_id'], 5, '0', STR_PAD_LEFT);
            echo _l('statement_shipping_charge_details', array(
              '<a href="'.admin_url('purchase/view_shipping_charge/'.$data['shipping_charge_id']).'" target="_blank">'.e($sc_ref).'</a>',
              e($data['shipping_charge_vendor_ref'] ?: '-')
            ));
          }
          ?>
        </td>
        <td>
          <?php
          // The vendor's own reference for the document. A payment carries the
          // references of everything it settled; a debit note we raised has none.
          //
          // Deliberately not nowrap: a cheque that settled many documents lists every
          // vendor reference, which has to be allowed to wrap or it stretches the table.
          if(isset($data['invoice_id'])) {
            echo !empty($data['vendor_invoice_number']) ? e($data['vendor_invoice_number']) : '-';
          } else if(isset($data['shipping_charge_id'])) {
            echo !empty($data['shipping_charge_vendor_ref']) ? e($data['shipping_charge_vendor_ref']) : '-';
          } else if(isset($data['expinv_id'])) {
            // The vendor's reference from the expense, not our own expinv_number -
            // that already appears in the Details column.
            echo !empty($data['expinv_vendor_ref']) ? e($data['expinv_vendor_ref']) : '-';
          } else if(isset($data['expense_payment_id']) && !empty($data['expense_payment_vendor_refs'])) {
            // A cheque settling several expenses names every vendor invoice it cleared
            echo e(implode(', ', $data['expense_payment_vendor_refs']));
          } else if(isset($data['payment_id']) && !empty($data['payment_vendor_refs'])) {
            echo e(implode(', ', $data['payment_vendor_refs']));
          } else if(isset($data['payment_id']) && !empty($data['payment_vendor_ref'])) {
            echo e($data['payment_vendor_ref']);
          } else {
            echo '-';
          }
          ?>
        </td>
        <td<?php echo $nowrap; ?>>
          <?php
          // Only purchase invoices carry a due date. Shipping charges and credit
          // expenses have no due date column, so a dash is honest rather than
          // inventing one from the document date.
          if(isset($data['invoice_id']) && !empty($data['duedate']) && $data['duedate'] != '0000-00-00') {
            echo _d($data['duedate']);
          } else {
            echo '-';
          }
          ?>
        </td>
        <td class="text-right">
          <?php
          if(isset($data['invoice_id'])) {
            echo app_format_money($data['invoice_amount'], $statement['currency'], true);
          } else if(isset($data['debit_note_id'])) {
            echo app_format_money($data['debit_note_amount'], $statement['currency'], true);
          } else if(isset($data['expinv_id'])) {
            echo app_format_money($data['expinv_amount'], $statement['currency'], true);
          } else if(isset($data['shipping_charge_id'])) {
            echo app_format_money($data['shipping_charge_amount'], $statement['currency'], true);
          }
          ?>
        </td>
        <td class="text-right">
          <?php
          if(isset($data['payment_id'])) {
            echo app_format_money($data['payment_total'], $statement['currency'], true);
          } else if(isset($data['debit_note_refund_id'])) {
            echo app_format_money($data['refund_amount'], $statement['currency'], true);
          } else if(isset($data['expense_payment_id'])) {
            echo app_format_money($data['expense_payment_total'], $statement['currency'], true);
          }
          ?>
        </td>
        
        <td class="text-right">
          <?php
          if(isset($data['payment_id'])) {
            $tmpBeginningBalance = ($tmpBeginningBalance + $data['payment_total']);
          } else if(isset($data['invoice_id'])){
            $tmpBeginningBalance = ($tmpBeginningBalance - $data['invoice_amount']);
          } else if(isset($data['debit_note_refund_id'])) {
            $tmpBeginningBalance = ($tmpBeginningBalance - $data['refund_amount']);
          } else if(isset($data['debit_note_id'])) {
            $tmpBeginningBalance = ($tmpBeginningBalance + $data['debit_note_amount']);
          } else if(isset($data['expinv_id'])) {
            // Increases what is owed, same direction as a purchase invoice
            $tmpBeginningBalance = ($tmpBeginningBalance - $data['expinv_amount']);
          } else if(isset($data['expense_payment_id'])) {
            // Reduces what is owed, same direction as an invoice payment
            $tmpBeginningBalance = ($tmpBeginningBalance + $data['expense_payment_total']);
          } else if(isset($data['shipping_charge_id'])) {
            // Increases what is owed, same direction as a purchase invoice
            $tmpBeginningBalance = ($tmpBeginningBalance - $data['shipping_charge_amount']);
          }
          if(!isset($data['debit_id'])){
              echo app_format_money($tmpBeginningBalance, $statement['currency'], true);
          }
          ?>
        </td>
      </tr>
    <?php } ?>
  </tbody>
  <tfoot class="statement_tfoot">
   <tr>
     <td colspan="5" class="text-right">
       <b><?php echo _l('balance_due'); ?></b>
     </td>
     <td class="text-right" colspan="2">
       <b><?php echo app_format_money($statement['balance_due'], $statement['currency']); ?></b>
     </td>
   </tr>
 </tfoot>
</table>
</div>
</div>
