<?php
/**
 * ACCOUNTING ENTRY GENERATOR - Direct DB (no CI framework)
 * Run via browser: http://localhost/nextgenlive/generate_accounting_entries_live.php
 * DELETE THIS FILE AFTER USE
 */

// DB Connection - change these for live server
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'nextgen_live';  // <-- CHANGE THIS$prefix  = 'tbl';
$prefix  = 'tbl';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if($conn->connect_error) die("DB Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "<pre>\n=== ACCOUNTING ENTRY GENERATOR ===\n\n";

// Helper: Get option value
function get_opt($conn, $prefix, $name){
    $r = $conn->query("SELECT value FROM {$prefix}options WHERE name = '{$name}' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    return $row ? $row['value'] : '';
}

// Helper: Get tax mapping
function get_tax_map($conn, $prefix, $tax_id){
    $r = $conn->query("SELECT * FROM {$prefix}acc_tax_mappings WHERE tax_id = '{$tax_id}' LIMIT 1");
    return $r ? $r->fetch_assoc() : null;
}

// Helper: Insert accounting entry
function insert_entry($conn, $prefix, $data){
    $cols = implode(',', array_map(function($k){ return "`$k`"; }, array_keys($data)));
    $vals = implode(',', array_map(function($v) use ($conn){ 
        if(is_null($v)) return 'NULL';
        return "'" . $conn->real_escape_string($v) . "'"; 
    }, array_values($data)));
    $conn->query("INSERT INTO {$prefix}acc_account_history ({$cols}) VALUES ({$vals})");
    return $conn->insert_id;
}

$total_entries = 0;

// ═══════════════════════════════════════════════════════
// 1. APPROVED GRNs → Dr Inventory/Expense, Cr GR/IR
// ═══════════════════════════════════════════════════════
echo "--- GRN (Stock Import) Entries ---\n";

$grns = $conn->query("SELECT * FROM {$prefix}goods_receipt WHERE approval = 1");
while($grn = $grns->fetch_assoc()){
    // Skip if already has entries
    $chk = $conn->query("SELECT COUNT(*) as cnt FROM {$prefix}acc_account_history WHERE rel_type = 'stock_import' AND rel_id = {$grn['id']}");
    if($chk->fetch_assoc()['cnt'] > 0){
        echo "  GRN #{$grn['id']} ({$grn['goods_receipt_code']}): SKIPPED\n";
        continue;
    }

    // Get PO and type info
    $debit_account = get_opt($conn, $prefix, 'acc_wh_stock_import_deposit_to') ?: 112;
    $credit_account = get_opt($conn, $prefix, 'acc_wh_stock_import_payment_account') ?: 167;
    $vendor_id = $grn['supplier_code'] ?: 0;

    if($grn['pr_order_id'] > 0){
        $po = $conn->query("SELECT po.ledger_account, pr.type FROM {$prefix}pur_orders po LEFT JOIN {$prefix}pur_request pr ON pr.id = po.pur_request WHERE po.id = {$grn['pr_order_id']}")->fetch_assoc();
        if($po && !empty($po['type'])){
            $rt = $conn->query("SELECT ledger_account, credit_ledger FROM {$prefix}pur_request_types WHERE type_code = '{$po['type']}'")->fetch_assoc();
            if($rt){
                if(!empty($rt['ledger_account']) && $rt['ledger_account'] > 0){
                    $debit_account = $rt['ledger_account'];
                } elseif(!empty($po['ledger_account']) && $po['ledger_account'] > 0){
                    $debit_account = $po['ledger_account'];
                }
                if(!empty($rt['credit_ledger']) && $rt['credit_ledger'] > 0){
                    $credit_account = $rt['credit_ledger'];
                }
            }
        }
        // Fallback to PO ledger if type has no ledger
        if($debit_account == (get_opt($conn, $prefix, 'acc_wh_stock_import_deposit_to') ?: 112)){
            if(!empty($po['ledger_account']) && $po['ledger_account'] > 0){
                $debit_account = $po['ledger_account'];
            }
        }
    }

    // Get GRN total (sum of sub_total from detail)
    $det = $conn->query("SELECT SUM(sub_total) as total FROM {$prefix}goods_receipt_detail WHERE goods_receipt_id = {$grn['id']}");
    $grn_total = $det->fetch_assoc()['total'] ?: 0;
    if($grn_total <= 0) { echo "  GRN #{$grn['id']}: SKIPPED (zero amount)\n"; continue; }

    // Currency conversion (if GRN is in foreign currency stored as AED already, rate=1)
    $amount = round((float)$grn_total, 2);

    $desc = "GRN: {$grn['goods_receipt_code']}";
    $date = $grn['date_c'] ?: date('Y-m-d');

    // Dr Inventory/Expense
    insert_entry($conn, $prefix, ['account'=>$debit_account, 'debit'=>$amount, 'credit'=>0, 'description'=>$desc, 'number'=>$grn['goods_receipt_code'], 'vendor'=>$vendor_id, 'split'=>$credit_account, 'rel_id'=>$grn['id'], 'rel_type'=>'stock_import', 'date'=>$date, 'datecreated'=>date('Y-m-d H:i:s'), 'addedfrom'=>1]);
    // Cr GR/IR Clearing
    insert_entry($conn, $prefix, ['account'=>$credit_account, 'debit'=>0, 'credit'=>$amount, 'description'=>$desc, 'number'=>$grn['goods_receipt_code'], 'vendor'=>$vendor_id, 'split'=>$debit_account, 'rel_id'=>$grn['id'], 'rel_type'=>'stock_import', 'date'=>$date, 'datecreated'=>date('Y-m-d H:i:s'), 'addedfrom'=>1]);

    echo "  GRN #{$grn['id']} ({$grn['goods_receipt_code']}): Dr {$debit_account} / Cr {$credit_account} = {$amount}\n";
    $total_entries += 2;
}

// ═══════════════════════════════════════════════════════
// 2. APPROVED INVOICES → Dr GR/IR, Cr Vendor AP + Dr VAT, Cr AP
// ═══════════════════════════════════════════════════════
echo "\n--- Purchase Invoice Entries ---\n";

$ap_account = get_opt($conn, $prefix, 'acc_pur_invoice_payment_account') ?: 159;
$vat_account = 124; // VAT Paid

$invs = $conn->query("SELECT * FROM {$prefix}pur_invoices WHERE approval_status = 2 AND (is_deleted = 0 OR is_deleted IS NULL)");
if($invs) while($inv = $invs->fetch_assoc()){
    $chk = $conn->query("SELECT COUNT(*) as cnt FROM {$prefix}acc_account_history WHERE rel_type = 'purchase_invoice' AND rel_id = {$inv['id']}");
    if($chk->fetch_assoc()['cnt'] > 0){
        echo "  Invoice #{$inv['id']} ({$inv['invoice_number']}): SKIPPED\n";
        continue;
    }

    $grir_account = 167; // Default GR/IR clearing
    $po_rate = 1;

    if(!empty($inv['pur_order']) && $inv['pur_order'] > 0){
        $po = $conn->query("SELECT po.pur_request, po.ledger_account, po.currency_rate, pr.type FROM {$prefix}pur_orders po LEFT JOIN {$prefix}pur_request pr ON pr.id = po.pur_request WHERE po.id = {$inv['pur_order']}")->fetch_assoc();
        if($po){
            $po_rate = (float)$po['currency_rate'] ?: 1;
            if(!empty($po['type'])){
                $rt = $conn->query("SELECT credit_ledger, ledger_account FROM {$prefix}pur_request_types WHERE type_code = '{$po['type']}'")->fetch_assoc();
                if($rt && !empty($rt['credit_ledger'])) $grir_account = $rt['credit_ledger'];
                if($rt && (empty($rt['ledger_account']) || $rt['ledger_account'] == 0)){
                    // Service type - use PO ledger as GR/IR debit
                    if(!empty($po['ledger_account']) && $po['ledger_account'] > 0){
                        $grir_account = $po['ledger_account'];
                    }
                }
            }
        }

        // Get exact GRN amount for GR/IR (avoids forex rounding)
        $grn_amt = $conn->query("SELECT SUM(h.credit) as total FROM {$prefix}acc_account_history h JOIN {$prefix}goods_receipt g ON g.id = h.rel_id WHERE h.rel_type = 'stock_import' AND h.account = {$grir_account} AND g.pr_order_id = {$inv['pur_order']}");
        $grn_row = $grn_amt->fetch_assoc();
        $subtotal_aed = ($grn_row && $grn_row['total'] > 0) ? (float)$grn_row['total'] : round((float)$inv['subtotal'] / $po_rate, 2);
    } else {
        $subtotal_aed = (float)$inv['subtotal'];
    }

    // VAT conversion
    $vat_aed = (float)$inv['tax'];
    if($po_rate > 0 && $po_rate != 1){
        $vat_aed = round($vat_aed / $po_rate, 2);
    }

    $desc = "INV: {$inv['invoice_number']}";
    $date = $inv['invoice_date'] ?: date('Y-m-d');
    $vendor_id = $inv['vendor'] ?: 0;

    // Dr GR/IR Clearing, Cr Vendor AP (subtotal)
    if($subtotal_aed > 0){
        insert_entry($conn, $prefix, ['account'=>$grir_account, 'debit'=>$subtotal_aed, 'credit'=>0, 'description'=>$desc, 'number'=>$inv['invoice_number'], 'vendor'=>$vendor_id, 'split'=>$ap_account, 'rel_id'=>$inv['id'], 'rel_type'=>'purchase_invoice', 'date'=>$date, 'datecreated'=>date('Y-m-d H:i:s'), 'addedfrom'=>1]);
        insert_entry($conn, $prefix, ['account'=>$ap_account, 'debit'=>0, 'credit'=>$subtotal_aed, 'description'=>$desc, 'number'=>$inv['invoice_number'], 'vendor'=>$vendor_id, 'split'=>$grir_account, 'rel_id'=>$inv['id'], 'rel_type'=>'purchase_invoice', 'date'=>$date, 'datecreated'=>date('Y-m-d H:i:s'), 'addedfrom'=>1]);
        $total_entries += 2;
    }

    // Dr VAT Paid, Cr Vendor AP (tax)
    if($vat_aed > 0){
        insert_entry($conn, $prefix, ['account'=>$vat_account, 'debit'=>$vat_aed, 'credit'=>0, 'description'=>$desc, 'number'=>$inv['invoice_number'], 'vendor'=>$vendor_id, 'split'=>$ap_account, 'rel_id'=>$inv['id'], 'rel_type'=>'purchase_invoice', 'date'=>$date, 'datecreated'=>date('Y-m-d H:i:s'), 'addedfrom'=>1, 'tax'=>1]);
        insert_entry($conn, $prefix, ['account'=>$ap_account, 'debit'=>0, 'credit'=>$vat_aed, 'description'=>$desc, 'number'=>$inv['invoice_number'], 'vendor'=>$vendor_id, 'split'=>$vat_account, 'rel_id'=>$inv['id'], 'rel_type'=>'purchase_invoice', 'date'=>$date, 'datecreated'=>date('Y-m-d H:i:s'), 'addedfrom'=>1, 'tax'=>1]);
        $total_entries += 2;
    }

    echo "  Invoice #{$inv['id']} ({$inv['invoice_number']}): Dr GR/IR {$subtotal_aed} + VAT {$vat_aed}, Cr AP\n";
}

// ═══════════════════════════════════════════════════════
// 3. APPROVED PAYMENTS → Dr Vendor AP, Cr Cash/Bank
// ═══════════════════════════════════════════════════════
echo "\n--- Purchase Payment Entries ---\n";

$pays = $conn->query("SELECT * FROM {$prefix}pur_invoice_payment WHERE approval_status = 2 AND (is_deleted = 0 OR is_deleted IS NULL)");
if($pays) while($pay = $pays->fetch_assoc()){
    $chk = $conn->query("SELECT COUNT(*) as cnt FROM {$prefix}acc_account_history WHERE rel_type = 'purchase_payment' AND rel_id = {$pay['id']}");
    if($chk->fetch_assoc()['cnt'] > 0){
        echo "  Payment #{$pay['id']}: SKIPPED\n";
        continue;
    }

    $amount = (float)$pay['amount'];
    if($amount <= 0) continue;

    // Get GL account from payment or default
    $credit_acc = (!empty($pay['gl_account']) && $pay['gl_account'] > 0) ? $pay['gl_account'] : 133; // Cash In Hand default

    // Get vendor from invoice
    $vendor_id = 0;
    $inv_r = $conn->query("SELECT vendor, invoice_number FROM {$prefix}pur_invoices WHERE id = {$pay['pur_invoice']}");
    $inv_data = $inv_r ? $inv_r->fetch_assoc() : null;
    if($inv_data) $vendor_id = $inv_data['vendor'];

    // Currency conversion if needed
    $inv_full = $conn->query("SELECT currency, currency_rate FROM {$prefix}pur_invoices WHERE id = {$pay['pur_invoice']}")->fetch_assoc();
    if($inv_full && $inv_full['currency'] > 0 && (float)$inv_full['currency_rate'] > 0 && (float)$inv_full['currency_rate'] != 1){
        $amount = round($amount / (float)$inv_full['currency_rate'], 2);
    }

    $desc = $inv_data ? "Payment: {$inv_data['invoice_number']}" : "Payment #{$pay['id']}";
    $date = $pay['date'] ?: date('Y-m-d');

    // Dr Vendor AP, Cr Cash/Bank
    insert_entry($conn, $prefix, ['account'=>$ap_account, 'debit'=>$amount, 'credit'=>0, 'description'=>$desc, 'number'=>$pay['transactionid'], 'vendor'=>$vendor_id, 'split'=>$credit_acc, 'rel_id'=>$pay['id'], 'rel_type'=>'purchase_payment', 'date'=>$date, 'datecreated'=>date('Y-m-d H:i:s'), 'addedfrom'=>1]);
    insert_entry($conn, $prefix, ['account'=>$credit_acc, 'debit'=>0, 'credit'=>$amount, 'description'=>$desc, 'number'=>$pay['transactionid'], 'vendor'=>$vendor_id, 'split'=>$ap_account, 'rel_id'=>$pay['id'], 'rel_type'=>'purchase_payment', 'date'=>$date, 'datecreated'=>date('Y-m-d H:i:s'), 'addedfrom'=>1]);

    echo "  Payment #{$pay['id']} (Txn: {$pay['transactionid']}): Dr AP / Cr {$credit_acc} = {$amount}\n";
    $total_entries += 2;
}

// ═══════════════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════════════
echo "\n=== SUMMARY ===\n";
echo "Total entries created: {$total_entries}\n\n";

$bal = $conn->query("SELECT SUM(debit) as dr, SUM(credit) as cr FROM {$prefix}acc_account_history");
$b = $bal->fetch_assoc();
echo "Trial Balance:\n";
echo "  Total Debit:  " . number_format($b['dr'], 2) . "\n";
echo "  Total Credit: " . number_format($b['cr'], 2) . "\n";
echo "  Difference:   " . number_format($b['dr'] - $b['cr'], 2) . "\n";
echo (abs($b['dr'] - $b['cr']) < 0.01) ? "  STATUS: BALANCED ✓\n" : "  STATUS: NOT BALANCED ✗\n";

echo "\n=== DONE - DELETE THIS FILE NOW ===\n</pre>";
$conn->close();
