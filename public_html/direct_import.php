<?php
// Direct SAP Import - Test Script
// Place in: d:\xampp\htdocs\nextgen\direct_import.php
// Access: http://localhost/nextgen/direct_import.php

define('BASEPATH', TRUE);
require_once 'application/config/database.php';

$conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Direct SAP Customer Import</h2>";

// Your SAP data
$customers = [
    ['1001045', 'RCT INTERNATIONAL LIMITED', 'CYPRUS', '', 'CY', '', ''],
    ['1002018', 'JK INTERNATIONAL FZE', 'DUBAI', '', 'AE', '', ''],
    ['1002040', 'JALAT KHAN CIGARETTE MANU', 'KABUL AFGHANISTAN', 'Industrial Park,Pol-eCharkhi', 'AF', '+93799333999', '']
];

$imported = 0;

foreach ($customers as $cust) {
    list($code, $name, $city, $address, $country_code, $phone, $email) = $cust;
    
    // Get country ID
    $country_id = 0;
    if (!empty($country_code)) {
        $result = $conn->query("SELECT country_id FROM tblcountries WHERE iso2 = '$country_code' LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $country_id = $row['country_id'];
        }
    }
    
    // Insert client
    $sql = "INSERT INTO tblclients (company, vat, city, address, country, phonenumber, datecreated, addedfrom) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssis", $name, $code, $city, $address, $country_id, $phone);
    
    if ($stmt->execute()) {
        $client_id = $stmt->insert_id;
        
        // Create primary contact
        if (empty($email)) {
            $email = 'customer' . $client_id . '@temp.local';
        }
        
        $sql2 = "INSERT INTO tblcontacts (userid, firstname, lastname, email, is_primary, datecreated, active) 
                 VALUES (?, 'Primary', 'Contact', ?, 1, NOW(), 1)";
        
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("is", $client_id, $email);
        
        if ($stmt2->execute()) {
            $imported++;
            echo "<p style='color:green'>✓ Imported: $name (ID: $client_id)</p>";
        } else {
            echo "<p style='color:red'>✗ Contact failed for: $name</p>";
        }
        $stmt2->close();
    } else {
        echo "<p style='color:red'>✗ Failed: $name - " . $stmt->error . "</p>";
    }
    $stmt->close();
}

echo "<hr><h3>Total Imported: $imported</h3>";
echo "<p><a href='admin/clients'>View Customers</a></p>";

$conn->close();
?>
