<?php
/**
 * Regenerate ALL GRN accounting entries for LIVE servers
 * Logic: Dr = PR type ledger_account | Cr = 240000 GR/IR Clearing (ID 167)
 * 
 * Place this file in the web root (same level as index.php) and run via browser:
 * 
 * Local:  http://localhost/nextgenlive/regenerate_grn_accounting_live.php
 * Live1:  https://fd098.fortiddns.com:5225/regenerate_grn_accounting_live.php
 * Live2:  https://fd098.fortiddns.com:5252/regenerate_grn_accounting_live.php
 * LAN:   https://172.168.20.61/nextgen/regenerate_grn_accounting_live.php
 * 
 * DELETE AFTER RUNNING!
 */

// Auto-detect DB credentials from app-config
$config_path = __DIR__ . '/application/config/app-config.php';
if(!file_exists($config_path)){
    // Try one level up in case placed in subfolder
    $config_path = dirname(__DIR__) . '/application/config/app-config.php';
}

if(file_exists($config_path)){
    define('BASEPATH', true); // Required by app-config.php guard
    include($config_path);
    $db_host = APP_DB_HOSTNAME;
    $db_user = APP_DB_USERNAME;
    $db_pass = APP_DB_PASSWORD;
    $db_name = APP_DB_NAME;
} else {
    // Fallback - update these manually if config not found
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'nextgen_live';
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if($conn->connect_error) die("DB Error: ".$conn->connect_error);
$prefix = 'tbl';

echo "<pre>\n=== REGENERATE ALL GRN ACCOUNTING ENTRIES ===\n";
echo "Server: " . gethostname() . " | DB: $db_name\n\n";

$r = $conn->query("SELECT * FROM {$prefix}goods_receipt WHERE approval = 1 AND is_deleted = 0 ORDER BY id ASC");
$grns = [];
while($row = $r->fetch_assoc()) $grns[] = $row;
echo "Total approved GRNs: " . count($grns) . "\n\n";

// Delete existing stock_import entries
$conn->query("DELETE FROM {$prefix}acc_account_history WHERE rel_type = 'stock_import'");
echo "Deleted old stock_import entries: {$conn->affected_rows}\n\n";

$total_entries = 0;
$default_credit = 167; // 240000 GR/IR Clearing
$default_debit = 112;  // 150000 Inventory Raw Materials (fallback)

foreach($grns as $grn){
    $grn_id = $grn['id'];
    $po_id = $grn['pr_order_id'];
    $debit_account = $default_debit;
    
    if($po_id > 0){
        $r2 = $conn->query("SELECT po.ledger_account as po_ledger, po.pur_request, pr.type 
            FROM {$prefix}pur_orders po 
            LEFT JOIN {$prefix}pur_request pr ON pr.id = po.pur_request 
            WHERE po.id = $po_id");
        $po_pr = $r2 ? $r2->fetch_assoc() : null;
        
        if($po_pr){
            // Priority 1: PR type's ledger_account
            if(!empty($po_pr['type'])){
                $type_escaped = $conn->real_escape_string($po_pr['type']);
                $r3 = $conn->query("SELECT ledger_account, credit_ledger FROM {$prefix}pur_request_types WHERE type_code = '$type_escaped'");
                $rt = $r3 ? $r3->fetch_assoc() : null;
                if($rt){
                    if(!empty($rt['ledger_account']) && $rt['ledger_account'] > 0){
                        $debit_account = (int)$rt['ledger_account'];
                    }
                    if(!empty($rt['credit_ledger']) && $rt['credit_ledger'] > 0){
                        $default_credit = (int)$rt['credit_ledger'];
                    }
                }
            }
            // Priority 2: PO's own ledger_account
            if($debit_account == $default_debit && !empty($po_pr['po_ledger']) && $po_pr['po_ledger'] > 0){
                $debit_account = (int)$po_pr['po_ledger'];
            }
            // Priority 3: PR's own ledger_account
            if($debit_account == $default_debit && !empty($po_pr['pur_request']) && $po_pr['pur_request'] > 0){
                $r4 = $conn->query("SELECT ledger_account FROM {$prefix}pur_request WHERE id = " . (int)$po_pr['pur_request']);
                $pr_row = $r4 ? $r4->fetch_assoc() : null;
                if($pr_row && !empty($pr_row['ledger_account']) && $pr_row['ledger_account'] > 0){
                    $debit_account = (int)$pr_row['ledger_account'];
                }
            }
        }
    }
    
    $credit_account = $default_credit;
    
    $r5 = $conn->query("SELECT * FROM {$prefix}goods_receipt_detail WHERE goods_receipt_id = $grn_id");
    $items = [];
    if($r5){ while($row = $r5->fetch_assoc()) $items[] = $row; }
    if(empty($items)){ $default_credit = 167; continue; }
    
    $currency_rate = 1;
    if($grn['currency'] != 0 && (float)$grn['currency_exchange_rate'] != 0){
        $currency_rate = (float)$grn['currency_exchange_rate'];
    }
    
    $vendor = $grn['supplier_code'] ?: 0;
    $grn_code = $conn->real_escape_string($grn['goods_receipt_code']);
    $date = $grn['date_c'] ?: date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    
    $entry_count = 0;
    foreach($items as $item){
        $amount = (float)$item['sub_total'];
        if($amount == 0) $amount = (float)$item['quantities'] * (float)$item['unit_price'];
        if($currency_rate != 1 && $currency_rate > 0) $amount = round($amount / $currency_rate, 2);
        if($amount <= 0) continue;
        
        $desc = $conn->real_escape_string("GRN: {$grn['goods_receipt_code']}");
        
        // Dr: Material/Expense account
        $conn->query("INSERT INTO {$prefix}acc_account_history 
            (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom) 
            VALUES ($debit_account, $credit_account, $amount, 0, '$desc', '$grn_code', $vendor, $grn_id, 'stock_import', '$date', '$now', 1)");
        
        // Cr: GR/IR Clearing
        $conn->query("INSERT INTO {$prefix}acc_account_history 
            (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom) 
            VALUES ($credit_account, $debit_account, 0, $amount, '$desc', '$grn_code', $vendor, $grn_id, 'stock_import', '$date', '$now', 1)");
        
        $entry_count += 2;
    }
    
    $conn->query("UPDATE {$prefix}goods_receipt SET acc_mapping = 1 WHERE id = $grn_id");
    
    $r6 = $conn->query("SELECT number, name FROM {$prefix}acc_accounts WHERE id = $debit_account");
    $dr_acct = $r6 ? $r6->fetch_assoc() : ['number'=>'?','name'=>'?'];
    echo "  GRN #{$grn_id} ({$grn['goods_receipt_code']}): Dr {$dr_acct['number']} {$dr_acct['name']} / Cr 240000 GR/IR | {$entry_count} entries\n";
    $total_entries += $entry_count;
    
    // Reset credit to default for next GRN
    $default_credit = 167;
}

echo "\n\nTotal entries created: $total_entries\n";

// Trial balance check
$r = $conn->query("SELECT SUM(debit) as dr, SUM(credit) as cr, SUM(debit)-SUM(credit) as diff FROM {$prefix}acc_account_history");
$t = $r->fetch_assoc();
echo "\nTrial Balance: Dr={$t['dr']} | Cr={$t['cr']} | Diff={$t['diff']}\n";

// Per-account summary
echo "\nPer-account summary (stock_import entries):\n";
$r = $conn->query("SELECT h.account, a.number, a.name, SUM(h.debit) as dr, SUM(h.credit) as cr 
    FROM {$prefix}acc_account_history h 
    JOIN {$prefix}acc_accounts a ON a.id = h.account 
    WHERE h.rel_type = 'stock_import' 
    GROUP BY h.account ORDER BY a.number");
if($r){ while($row = $r->fetch_assoc()){
    echo "  {$row['number']} {$row['name']}: Dr={$row['dr']}, Cr={$row['cr']}\n";
}}

echo "\n=== DONE ===\nDELETE THIS FILE!\n</pre>";
$conn->close();
