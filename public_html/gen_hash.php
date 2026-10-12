<?php
define('BASEPATH', true);
require 'application/third_party/phpass.php';
$hasher = new PasswordHash(8, FALSE);
echo $hasher->HashPassword('Admin123$');
echo "\n";
