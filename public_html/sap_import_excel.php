<!DOCTYPE html>
<html>
<head><title>SAP Excel Import</title></head>
<body>
<h2>SAP Customer Excel Import</h2>
<p><strong>Excel Format:</strong> Row 1 = Headers, Row 2+ = Data<br>
Columns: Customer | Sales Org | ... | <strong>Name 1 (F - Required)</strong> | City | ... | Email (P)</p>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required>
    <button type="submit">Import</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    
    $conn = new mysqli('localhost', 'root', '', 'nextgen');
    
    if ($conn->connect_error) {
        die("Connection failed");
    }
    
    $file = $_FILES['excel_file']['tmp_name'];
    $ext = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
    
    // Convert Excel to CSV using COM
    if ($ext !== 'csv') {
        try {
            $excel = new COM("Excel.Application");
            $excel->Visible = 0;
            $wb = $excel->Workbooks->Open(realpath($file));
            $csvFile = sys_get_temp_dir() . '/temp_' . time() . '.csv';
            $wb->SaveAs($csvFile, 6);
            $wb->Close(false);
            $excel->Quit();
            unset($excel);
            $file = $csvFile;
        } catch (Exception $e) {
            die("<p style='color:red'>Error: Install Excel or upload CSV file</p>");
        }
    }
    
    echo "<hr><h3>Results:</h3>";
    
    $imported = 0;
    $handle = fopen($file, 'r');
    fgetcsv($handle); // Skip header
    
    while (($data = fgetcsv($handle)) !== false) {
        $name = isset($data[5]) ? trim($data[5]) : '';
        
        if (empty($name)) continue;
        
        $code = isset($data[0]) ? trim($data[0]) : '';
        $city = isset($data[6]) ? trim($data[6]) : '';
        $address = isset($data[9]) ? trim($data[9]) : '';
        $country_code = isset($data[12]) ? trim($data[12]) : '';
        $phone = isset($data[14]) ? trim($data[14]) : '';
        $email = isset($data[15]) ? trim($data[15]) : '';
        
        $country_id = 0;
        if (!empty($country_code)) {
            $result = $conn->query("SELECT country_id FROM tblcountries WHERE iso2 = '$country_code' LIMIT 1");
            if ($result && $r = $result->fetch_assoc()) {
                $country_id = $r['country_id'];
            }
        }
        
        $sql = "INSERT INTO tblclients (company, vat, city, address, country, phonenumber, datecreated, addedfrom) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssis", $name, $code, $city, $address, $country_id, $phone);
        
        if ($stmt->execute()) {
            $client_id = $stmt->insert_id;
            
            if (empty($email)) {
                $email = 'customer' . $client_id . '@temp.local';
            }
            
            $sql2 = "INSERT INTO tblcontacts (userid, firstname, lastname, email, is_primary, datecreated, active) 
                     VALUES (?, 'Primary', 'Contact', ?, 1, NOW(), 1)";
            
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("is", $client_id, $email);
            
            if ($stmt2->execute()) {
                $imported++;
                echo "<p style='color:green'>✓ $name</p>";
            }
            $stmt2->close();
        }
        $stmt->close();
    }
    
    fclose($handle);
    if (isset($csvFile)) unlink($csvFile);
    
    echo "<hr><h3 style='color:green'>Imported: $imported</h3>";
    echo "<p><a href='admin/clients'>View Customers</a></p>";
    
    $conn->close();
}
?>
</body>
</html>
