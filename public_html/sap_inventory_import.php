<!DOCTYPE html>
<html>
<head><title>SAP Inventory Import</title></head>
<body>
<h2>SAP Inventory CSV Import</h2>
<p><strong>Instructions:</strong> Save your Excel file as CSV first, then upload<br>
Format: Row 1 = Headers | Columns: A=Material (Required), B=Material Description (Required), F=Base Unit of Measure</p>

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
        $material = isset($data[0]) ? trim($data[0]) : '';
        $description = isset($data[1]) ? trim($data[1]) : '';
        $unit_code = isset($data[5]) ? trim($data[5]) : '';
        
        if (empty($material) || empty($description)) continue;
        
        // Get unit_id from tblware_unit_type
        $unit_id = NULL;
        if (!empty($unit_code)) {
            $result = $conn->query("SELECT unit_type_id FROM tblware_unit_type WHERE unit_name = '" . $conn->real_escape_string($unit_code) . "' LIMIT 1");
            if ($result && $r = $result->fetch_assoc()) {
                $unit_id = $r['unit_type_id'];
            } else {
                // Insert new unit if not exists
                $insert_unit = $conn->prepare("INSERT INTO tblware_unit_type (unit_code, unit_name, unit_symbol, display) VALUES (?, ?, ?, 1)");
                $insert_unit->bind_param("sss", $unit_code, $unit_code, $unit_code);
                if ($insert_unit->execute()) {
                    $unit_id = $insert_unit->insert_id;
                }
                $insert_unit->close();
            }
        }
        
        // Delete existing item with same material code
        $check = $conn->query("SELECT id FROM tblitems WHERE description = '" . $conn->real_escape_string($description) . "' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $old_id = $check->fetch_assoc()['id'];
            $conn->query("DELETE FROM tblitems WHERE id = $old_id");
        }
        
        $sql = "INSERT INTO tblitems (description, rate, tax, unit, group_id) 
                VALUES (?, 0, NULL, ?, 0)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $description, $unit_id);
        
        if ($stmt->execute()) {
            $imported++;
            echo "<p style='color:green'>✓ $material - $description</p>";
        }
        $stmt->close();
    }
    
    fclose($handle);
    
    echo "<hr><h3 style='color:green'>Imported: $imported items</h3>";
    echo "<p><a href='admin/items'>View Items</a></p>";
    
    $conn->close();
}
?>
</body>
</html>
