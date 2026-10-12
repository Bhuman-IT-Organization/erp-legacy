<?php
// Test SAP Import - Place this file in nextgen root and access via browser
// URL: http://localhost/nextgen/test_sap_import.php

define('BASEPATH', TRUE);
require_once 'application/config/database.php';

$conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>SAP Import Test</h2>";

// Test data
$test_data = [
    'company' => 'TEST COMPANY LTD',
    'vat' => 'TEST001',
    'phonenumber' => '+123456789',
    'city' => 'Test City',
    'address' => 'Test Street',
    'zip' => '12345',
    'country' => 0,
    'state' => 'Test State',
    'datecreated' => date('Y-m-d H:i:s'),
    'addedfrom' => 0
];

// Insert into tblclients
$fields = implode(',', array_keys($test_data));
$values = "'" . implode("','", array_values($test_data)) . "'";
$sql = "INSERT INTO tblclients ($fields) VALUES ($values)";

if ($conn->query($sql)) {
    $client_id = $conn->insert_id;
    echo "<p style='color:green'>✓ Client created successfully! ID: $client_id</p>";
    
    // Create primary contact
    $contact_data = [
        'userid' => $client_id,
        'firstname' => 'Contact',
        'lastname' => 'TEST COMPANY LTD',
        'email' => 'test@example.com',
        'is_primary' => 1,
        'datecreated' => date('Y-m-d H:i:s'),
        'active' => 1
    ];
    
    $fields2 = implode(',', array_keys($contact_data));
    $values2 = "'" . implode("','", array_values($contact_data)) . "'";
    $sql2 = "INSERT INTO tblcontacts ($fields2) VALUES ($values2)";
    
    if ($conn->query($sql2)) {
        echo "<p style='color:green'>✓ Contact created successfully!</p>";
    } else {
        echo "<p style='color:red'>✗ Contact Error: " . $conn->error . "</p>";
    }
    
    echo "<p><a href='admin/clients/client/$client_id'>View Customer</a></p>";
} else {
    echo "<p style='color:red'>✗ Error: " . $conn->error . "</p>";
}

$conn->close();
?>
