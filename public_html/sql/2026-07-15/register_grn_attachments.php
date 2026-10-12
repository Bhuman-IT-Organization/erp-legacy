<?php
/**
 * Register existing GRN physical files into tblfiles database
 * Run via browser on live server: https://domain/nextgen/register_grn_attachments.php
 * DELETE AFTER USE
 */

// DB Connection - CHANGE for live server
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'nextgen';  // CHANGE to live DB name
$prefix  = 'tbl';

// Path to GRN uploads folder - CHANGE for live server
$upload_base = __DIR__ . '/modules/warehouse/uploads/goods_receipt/';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if($conn->connect_error) die("DB Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "<pre>\n=== Register GRN Attachments ===\n\n";

$total_registered = 0;

// Scan the goods_receipt upload directory
if(!is_dir($upload_base)){
    echo "Upload folder not found: {$upload_base}\n";
    echo "Trying alternative path...\n";
    $upload_base = __DIR__ . '/modules/warehouse/uploads/stock_import/';
}

if(!is_dir($upload_base)){
    die("No upload folder found. Check path.\n");
}

$dirs = scandir($upload_base);
foreach($dirs as $dir){
    if($dir == '.' || $dir == '..' || !is_numeric($dir)) continue;
    
    $grn_id = (int)$dir;
    $grn_path = $upload_base . $dir . '/';
    
    if(!is_dir($grn_path)) continue;
    
    $files = scandir($grn_path);
    foreach($files as $file){
        if($file == '.' || $file == '..' || $file == 'index.html') continue;
        if(strpos($file, 'signature_') === 0) continue; // Skip signature files
        
        $filepath = $grn_path . $file;
        if(!is_file($filepath)) continue;
        
        // Check if already registered
        $check = $conn->query("SELECT id FROM {$prefix}files WHERE rel_id = {$grn_id} AND rel_type = 'goods_receipt' AND file_name = '" . $conn->real_escape_string($file) . "'");
        if($check && $check->num_rows > 0){
            echo "  GRN #{$grn_id}/{$file}: ALREADY EXISTS\n";
            continue;
        }
        
        // Get file info
        $filetype = mime_content_type($filepath) ?: 'application/octet-stream';
        $dateadded = date('Y-m-d H:i:s', filemtime($filepath));
        
        // Get GRN creator for staffid
        $grn_row = $conn->query("SELECT addedfrom FROM {$prefix}goods_receipt WHERE id = {$grn_id}");
        $staffid = 1;
        if($grn_row && $row = $grn_row->fetch_assoc()){
            $staffid = $row['addedfrom'] ?: 1;
        }
        
        // Insert into tblfiles
        $sql = "INSERT INTO {$prefix}files (rel_id, rel_type, file_name, filetype, staffid, dateadded) VALUES ({$grn_id}, 'goods_receipt', '" . $conn->real_escape_string($file) . "', '" . $conn->real_escape_string($filetype) . "', {$staffid}, '{$dateadded}')";
        $conn->query($sql);
        
        if($conn->insert_id){
            echo "  GRN #{$grn_id}/{$file}: REGISTERED\n";
            $total_registered++;
        } else {
            echo "  GRN #{$grn_id}/{$file}: FAILED - " . $conn->error . "\n";
        }
    }
}

echo "\n=== DONE ===\n";
echo "Total files registered: {$total_registered}\n";
echo "\n DELETE THIS FILE NOW!\n</pre>";
$conn->close();
