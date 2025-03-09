<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test credentials
$password = '328050';
$username = 'adm';

// Generate a new hash
echo "Testing password verification\n";
echo "Password: $password\n";

// Generate hash using PASSWORD_DEFAULT
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Generated hash: $hash\n";

// Verify the password against the hash
$verify = password_verify($password, $hash);
echo "Verification result: " . ($verify ? "Success" : "Failed") . "\n";

// Test with known hash format
$testHash = '$2y$10$vSf1Js/Wbg23.tTDpdGtleD3CujyEQa0aGccIUSBIKbZ1YvOXu2Ta';
echo "\nTesting with known hash:\n";
echo "Test hash: $testHash\n";
$verify2 = password_verify($password, $testHash);
echo "Verification result: " . ($verify2 ? "Success" : "Failed") . "\n";

// Output hash info
echo "\nHash information:\n";
$info = password_get_info($hash);
print_r($info);
?>
