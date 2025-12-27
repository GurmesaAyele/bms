<?php
/**
 * Check donation_offers table structure
 */

require_once 'backend/config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Donation Offers Table Structure</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style></head><body>";

echo "<h1>Donation Offers Table Structure</h1>";

try {
    // Check if table exists
    $tables = $conn->query("SHOW TABLES LIKE 'donation_offers'")->fetchAll();
    
    if (empty($tables)) {
        echo "<div class='error'>donation_offers table does not exist!</div>";
        exit();
    }
    
    echo "<div class='success'>✓ donation_offers table exists</div>";
    
    // Show table structure
    echo "<h2>Table Structure</h2>";
    $desc = $conn->query("DESCRIBE donation_offers")->fetchAll();
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($desc as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    echo "<h2>Sample Data (First 3 rows)</h2>";
    $sample = $conn->query("SELECT * FROM donation_offers LIMIT 3")->fetchAll();
    
    if (empty($sample)) {
        echo "<div class='info'>No data in donation_offers table</div>";
    } else {
        echo "<table>";
        $first_row = $sample[0];
        echo "<tr>";
        foreach (array_keys($first_row) as $key) {
            if (!is_numeric($key)) {
                echo "<th>$key</th>";
            }
        }
        echo "</tr>";
        
        foreach ($sample as $row) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                if (!is_numeric($key)) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show CREATE TABLE statement
    echo "<h2>CREATE TABLE Statement</h2>";
    $create_stmt = $conn->query("SHOW CREATE TABLE donation_offers")->fetch();
    echo "<pre>" . htmlspecialchars($create_stmt['Create Table']) . "</pre>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>