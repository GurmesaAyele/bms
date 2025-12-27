<?php
require_once 'backend/config/database.php';

echo "Database connection test:\n";

try {
    // Test connection
    $stmt = $conn->query("SELECT 1");
    echo "✓ Database connected\n";
    
    // Check tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . "\n";
    
    // Check if blood_requests table has the correct structure
    if (in_array('blood_requests', $tables)) {
        $stmt = $conn->query("DESCRIBE blood_requests");
        $columns = $stmt->fetchAll();
        echo "\nblood_requests columns:\n";
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']})\n";
        }
    }
    
    // Check patients table
    if (in_array('patients', $tables)) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM patients");
        $result = $stmt->fetch();
        echo "\nPatients count: {$result['count']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>