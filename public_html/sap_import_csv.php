<!DOCTYPE html>
<html>
<head><title>SAP CSV Import</title></head>
<body>
<h2>SAP Customer CSV Import</h2>
<p><strong>Instructions:</strong> Save your Excel file as CSV first, then upload<br>
Format: Row 1 = Headers | Columns: A=Customer, F=Name1 (Required), G=City, J=Street, L=Zip, M=Country, O=Phone, P=Email</p>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit">Import CSV</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    
    $conn = new mysqli('localhost', 'root', '', 'nextgen');
    if ($conn->connect_error) die("Connection failed");
    
    $file = $_FILES['csv_file']['tmp_name'];
    
    echo "<hr><h3>Results:</h3>";
    
    $imported = 0;
    $handle = fopen($file, 'r');
    fgetcsv($handle); // Skip header
    
    while (($data = fgetcsv($handle)) !== false) {
        $name = isset($data[5]) ? trim($data[5]) : '';
        if (empty($name)) continue;
        
        $sap_vendor_id = isset($data[0]) ? trim($data[0]) : '';
        $city = isset($data[6]) ? trim($data[6]) : '';
        $address = isset($data[9]) ? trim($data[9]) : '';
        $country_code = isset($data[12]) ? trim($data[12]) : '';
        $phone = isset($data[14]) ? trim($data[14]) : '';
        $email = isset($data[15]) ? trim($data[15]) : '';
        
        // Delete existing client with same name
        $check = $conn->query("SELECT userid FROM tblclients WHERE company = '" . $conn->real_escape_string($name) . "' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $old_id = $check->fetch_assoc()['userid'];
            $conn->query("DELETE FROM tblcontacts WHERE userid = $old_id");
            $conn->query("DELETE FROM tblclients WHERE userid = $old_id");
        }
        
        $country_id = 0;
        if (!empty($country_code)) {
            $result = $conn->query("SELECT country_id FROM tblcountries WHERE iso2 = '$country_code' LIMIT 1");
            if ($result && $r = $result->fetch_assoc()) {
                $country_id = $r['country_id'];
            }
        }
        
        $sql = "INSERT INTO tblclients (company, sap_vendor_id, city, address, country, phonenumber, datecreated, addedfrom) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssis", $name, $sap_vendor_id, $city, $address, $country_id, $phone);
        
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
                $contact_id = $stmt2->insert_id;
                
                // Add all permissions
                $permissions = ['invoices', 'estimates', 'contracts', 'proposals', 'support', 'projects'];
                foreach ($permissions as $perm) {
                    $conn->query("INSERT INTO tblcontactpermissions (permission_id, userid) VALUES ((SELECT id FROM tblcontactpermissions WHERE name = '$perm' LIMIT 1), $contact_id)");
                }
                
                $imported++;
                echo "<p style='color:green'>✓ $name (SAP: $sap_vendor_id)</p>";
            }
            $stmt2->close();
        }
        $stmt->close();
    }
    
    fclose($handle);
    
    echo "<hr><h3 style='color:green'>Imported: $imported customers</h3>";
    echo "<p><a href='admin/clients'>View Customers</a></p>";
    
    $conn->close();
}
?>
</body>
</html>
