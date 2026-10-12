<?php
/**
 * LIVE SERVER ACCOUNTING ENTRY GENERATOR
 * 
 * Run this script from the live server root directory:
 *   cd /var/www/html/nextgen
 *   php sql/2026-07-15/generate_accounting_entries_live.php
 * 
 * Or access via browser (remove after use):
 *   https://yourdomain.com/nextgen/sql/2026-07-15/generate_accounting_entries_live.php
 * 
 * This generates accounting entries for:
 * 1. All approved GRNs (stock_import)
 * 2. All approved Purchase Invoices (purchase_invoice)
 * 3. All approved Purchase Payments (purchase_payment)
 */

// Bootstrap CodeIgniter
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PATH_INFO'] = '/';
$_SERVER['HTTP_HOST'] = 'localhost';

$system_path = 'system';
$application_folder = 'application';

// Set the current directory correctly
chdir(dirname(dirname(dirname(__FILE__))));

require_once 'index.php';

$CI = &get_instance();
$CI->load->model('accounting/accounting_model');
$CI->load->model('purchase/purchase_model');
$CI->load->model('warehouse/warehouse_model');
$CI->load->model('currencies_model');

echo "<pre>\n";
echo "=== ACCOUNTING ENTRY GENERATOR FOR LIVE ===\n\n";

// 1. Process approved GRNs
echo "--- Processing GRNs (stock_import) ---\n";
$CI->db->where('approval', 1);
$grns = $CI->db->get(db_prefix().'goods_receipt')->result();

$grn_count = 0;
foreach($grns as $grn){
    // Check if already has accounting entries
    $CI->db->where('rel_id', $grn->id);
    $CI->db->where('rel_type', 'stock_import');
    $existing = $CI->db->count_all_results(db_prefix().'acc_account_history');
    if($existing > 0){
        echo "  GRN #{$grn->id} ({$grn->goods_receipt_code}): SKIPPED (already has entries)\n";
        continue;
    }
    
    $result = $CI->accounting_model->automatic_stock_import_conversion($grn->id);
    $status = $result ? 'OK' : 'SKIPPED/FAILED';
    echo "  GRN #{$grn->id} ({$grn->goods_receipt_code}): {$status}\n";
    if($result) $grn_count++;
}
echo "  Total GRN entries created: {$grn_count}\n\n";

// 2. Process approved Purchase Invoices
echo "--- Processing Purchase Invoices ---\n";
$CI->db->where('approval_status', 2);
$CI->db->where('(is_deleted = 0 OR is_deleted IS NULL)');
$invoices = $CI->db->get(db_prefix().'pur_invoices')->result();

$inv_count = 0;
foreach($invoices as $inv){
    // Check if already has accounting entries
    $CI->db->where('rel_id', $inv->id);
    $CI->db->where('rel_type', 'purchase_invoice');
    $existing = $CI->db->count_all_results(db_prefix().'acc_account_history');
    if($existing > 0){
        echo "  Invoice #{$inv->id} ({$inv->invoice_number}): SKIPPED (already has entries)\n";
        continue;
    }
    
    $result = $CI->accounting_model->automatic_purchase_invoice_conversion($inv->id);
    $status = $result ? 'OK' : 'SKIPPED/FAILED';
    echo "  Invoice #{$inv->id} ({$inv->invoice_number}): {$status}\n";
    if($result) $inv_count++;
}
echo "  Total Invoice entries created: {$inv_count}\n\n";

// 3. Process approved Purchase Payments
echo "--- Processing Purchase Payments ---\n";
$CI->db->where('approval_status', 2);
$CI->db->where('(is_deleted = 0 OR is_deleted IS NULL)');
$payments = $CI->db->get(db_prefix().'pur_invoice_payment')->result();

$pay_count = 0;
foreach($payments as $pay){
    // Check if already has accounting entries
    $CI->db->where('rel_id', $pay->id);
    $CI->db->where('rel_type', 'purchase_payment');
    $existing = $CI->db->count_all_results(db_prefix().'acc_account_history');
    if($existing > 0){
        echo "  Payment #{$pay->id}: SKIPPED (already has entries)\n";
        continue;
    }
    
    $result = $CI->accounting_model->automatic_purchase_payment_conversion($pay->id);
    $status = $result ? 'OK' : 'SKIPPED/FAILED';
    echo "  Payment #{$pay->id} (Txn: {$pay->transactionid}): {$status}\n";
    if($result) $pay_count++;
}
echo "  Total Payment entries created: {$pay_count}\n\n";

// Summary
echo "=== SUMMARY ===\n";
echo "GRN entries: {$grn_count}\n";
echo "Invoice entries: {$inv_count}\n";
echo "Payment entries: {$pay_count}\n";

// Verify trial balance
$CI->db->select('SUM(debit) as total_dr, SUM(credit) as total_cr');
$totals = $CI->db->get(db_prefix().'acc_account_history')->row();
echo "\nTrial Balance Check:\n";
echo "  Total Debit:  " . number_format($totals->total_dr, 2) . "\n";
echo "  Total Credit: " . number_format($totals->total_cr, 2) . "\n";
echo "  Difference:   " . number_format($totals->total_dr - $totals->total_cr, 2) . "\n";
if(abs($totals->total_dr - $totals->total_cr) < 0.01){
    echo "  STATUS: BALANCED ✓\n";
} else {
    echo "  STATUS: NOT BALANCED ✗ - Please investigate!\n";
}

echo "\n=== DONE ===\n";
echo "</pre>\n";
