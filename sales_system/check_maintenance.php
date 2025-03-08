<?php
/**
 * Check Maintenance Status Script
 * Returns JSON response indicating whether system is in maintenance mode
 */

// Set content type to JSON
header('Content-Type: application/json');

// Check if maintenance flag file exists
$maintenanceFile = __DIR__ . '/maintenance.flag';
$isInMaintenance = file_exists($maintenanceFile);

// If maintenance file exists, check if maintenance period has expired
if ($isInMaintenance) {
    $maintenanceDetails = file_get_contents($maintenanceFile);
    
    // Try to find estimated completion time in maintenance details
    if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $maintenanceDetails, $matches)) {
        $estimatedTime = strtotime($matches[0]);
        $currentTime = time();
        
        // If estimated time has passed, remove maintenance flag
        if ($currentTime > $estimatedTime) {
            unlink($maintenanceFile);
            $isInMaintenance = false;
        }
    }
}

// Return maintenance status
echo json_encode([
    'maintenance' => $isInMaintenance,
    'timestamp' => date('Y-m-d H:i:s'),
    'message' => $isInMaintenance ? 'Sistema em manutenção' : 'Sistema operacional'
]);

// Log maintenance check if system is in maintenance mode
if ($isInMaintenance && file_exists(__DIR__ . '/logs')) {
    $logMessage = date('Y-m-d H:i:s') . " - Maintenance check from IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    file_put_contents(__DIR__ . '/logs/maintenance.log', $logMessage, FILE_APPEND);
}
?>
