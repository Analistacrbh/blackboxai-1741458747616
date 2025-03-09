<?php
// Test script to generate and verify password hash

$password = '328050';

// Generate hash
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: $password\n";
echo "Generated Hash: $hash\n";

// Verify hash
$verify = password_verify($password, $hash);
echo "Verification result: " . ($verify ? "Success" : "Failed") . "\n";

// Store this hash in users.sql
echo "\nSQL Insert Statement:\n";
echo "INSERT INTO users (username, password_hash, full_name, email, user_level, status, created_at)\n";
echo "VALUES ('adm', '$hash', 'Administrador do Sistema', 'admin@sistema.com', 'admin', 'active', NOW());\n";
?>
