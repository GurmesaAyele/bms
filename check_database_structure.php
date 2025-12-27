<?php
/**
 * Check Database Structure for Donation Offers
 */

echo "<h1>🔍 Database Structure Check</h1>";

try {
    require_once 'backend/config/database.php';
    echo "<div style='color: green;'>✅ Database connected successfully</div>";
    
    // Check if donation_offers table exists
    echo "<h2>1. Table Existence Check</h2>";
    $tables = $conn->query("SHOW TABLES LIKE 'donation_offers'")->fetchAll();
    if (count($tables) > 0) {
        echo "<div style='color: green;'>✅ donation_offers table exists</div>";
    } else {
        echo "<div style='color: red;'>❌ donation_offers table does not exist</div>";
        echo "<p>Need to create the table first!</p>";
    }
    
    // Check table structure
    echo "<h2>2. Table Structure</h2>";
    try {
        $columns = $conn->query("DESCRIBE donation_offers")->fetchAll();
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if hospital_id column exists
        $hospital_id_exists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'hospital_id') {
                $hospital_id_exists = true;
                break;
            }
        }
        
        if ($hospital_id_exists) {
            echo "<div style='color: green;'>✅ hospital_id column exists</div>";
        } else {
            echo "<div style='color: red;'>❌ hospital_id column is missing!</div>";
            echo "<p>This is the cause of the error. We need to add this column.</p>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Error checking table structure: " . $e->getMessage() . "</div>";
    }
    
    // Check other related tables
    echo "<h2>3. Related Tables Check</h2>";
    $related_tables = ['users', 'donors', 'hospitals'];
    foreach ($related_tables as $table) {
        try {
            $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<div style='color: green;'>✅ $table table exists with $count records</div>";
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ $table table issue: " . $e->getMessage() . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Database connection error: " . $e->getMessage() . "</div>";
}

echo "<h2>🔧 Next Steps</h2>";
echo "<p>If hospital_id column is missing, we need to:</p>";
echo "<ol>";
echo "<li>Add the hospital_id column to donation_offers table</li>";
echo "<li>Set up proper foreign key relationships</li>";
echo "<li>Update the table structure to match the expected schema</li>";
echo "</ol>";
?>