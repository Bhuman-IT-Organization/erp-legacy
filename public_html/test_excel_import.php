<!DOCTYPE html>
<html>
<head>
    <title>SAP Excel Import Test</title>
</head>
<body>
    <h2>SAP Customer Excel Import</h2>
    
    <h3>Instructions:</h3>
    <ol>
        <li>Create Excel file with these columns in Row 1:<br>
            <code>Customer | Sales Org | Dist Channel | Division | Sales Office | Name 1 | City | Search Term | Name 2 | Street | House Number | Postal Code | Country | Region | Telephone | Email | Account Group</code>
        </li>
        <li>Add your data starting from Row 2</li>
        <li>Column F (Name 1) is required</li>
        <li>Upload below</li>
    </ol>
    
    <form action="test_excel_import.php" method="post" enctype="multipart/form-data">
        <input type="file" name="excel_file" accept=".xlsx,.xls" required>
        <button type="submit">Import</button>
    </form>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
        define('BASEPATH', TRUE);
        require_once 'application/config/database.php';
        require_once 'application/third_party/PHPExcel/PHPExcel.php';
        
        $conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        try {
            $file = $_FILES['excel_file']['tmp_name'];
            $objPHPExcel = PHPExcel_IOFactory::load($file);
            $sheet = $objPHPExcel->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            
            echo "<hr><h3>Import Results:</h3>";
            echo "<p>Total rows in Excel: " . ($highestRow - 1) . "</p>";
            
            $imported = 0;
            
            for ($row = 2; $row <= $highestRow; $row++) {
                $name = trim($sheet->getCell('F'.$row)->getValue());
                
                if (empty($name)) {
                    echo "<p style='color:orange'>Row $row: Skipped (empty name)</p>";
                    continue;
                }
                
                $code = trim($sheet->getCell('A'.$row)->getValue());
                $city = trim($sheet->getCell('G'.$row)->getValue());
                $address = trim($sheet->getCell('J'.$row)->getValue());
                $country_code = trim($sheet->getCell('M'.$row)->getValue());
                $phone = trim($sheet->getCell('O'.$row)->getValue());
                $email = trim($sheet->getCell('P'.$row)->getValue());
                
                // Get country ID
                $country_id = 0;
                if (!empty($country_code)) {
                    $result = $conn->query("SELECT country_id FROM tblcountries WHERE iso2 = '$country_code' LIMIT 1");
                    if ($result && $r = $result->fetch_assoc()) {
                        $country_id = $r['country_id'];
                    }
                }
                
                // Insert client
                $sql = "INSERT INTO tblclients (company, vat, city, address, country, phonenumber, datecreated, addedfrom) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssis", $name, $code, $city, $address, $country_id, $phone);
                
                if ($stmt->execute()) {
                    $client_id = $stmt->insert_id;
                    
                    // Create contact
                    if (empty($email)) {
                        $email = 'customer' . $client_id . '@temp.local';
                    }
                    
                    $sql2 = "INSERT INTO tblcontacts (userid, firstname, lastname, email, is_primary, datecreated, active) 
                             VALUES (?, 'Primary', 'Contact', ?, 1, NOW(), 1)";
                    
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param("is", $client_id, $email);
                    
                    if ($stmt2->execute()) {
                        $imported++;
                        echo "<p style='color:green'>✓ Row $row: $name (ID: $client_id)</p>";
                    }
                    $stmt2->close();
                }
                $stmt->close();
            }
            
            echo "<hr><h3 style='color:green'>Successfully Imported: $imported customers</h3>";
            echo "<p><a href='admin/clients'>View Customers</a></p>";
            
            $conn->close();
            
        } catch (Exception $e) {
            echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
        }
    }
    ?>
</body>
</html>
