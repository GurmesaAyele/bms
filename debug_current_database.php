<?php
/**
 * Debug Current Database Structure
 * Check what tables and data exist in the current database
 */

require_once 'backend/config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Debug</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>Current Database Structure Debug</h1>";

try {
    // Check database connection
    echo "<div class='section'>";
    echo "<h2>Database Connection</h2>";
    if ($conn) {
        echo "<div class='success'>✓ Database connection successful</div>";
    } else {
        echo "<div class='error'>✗ Database connection failed</div>";
        exit();
    }
    echo "</div>";
    
    // Show all tables
    echo "<div class='section'>";
    echo "<h2>Available Tables</h2>";
    $tables_stmt = $conn->query("SHOW TABLES");
    $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Check blood_requests table structure
    if (in_array('blood_requests', $tables)) {
        echo "<div class='section'>";
        echo "<h2>blood_requests Table Structure</h2>";
        $desc_stmt = $conn->query("DESCRIBE blood_requests");
        $columns = $desc_stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show sample data
        echo "<h3>Sample Data (First 5 rows)</h3>";
        $sample_stmt = $conn->query("SELECT * FROM blood_requests LIMIT 5");
        $sample_data = $sample_stmt->fetchAll();
        
        if (empty($sample_data)) {
            echo "<div class='info'>No data in blood_requests table</div>";
        } else {
            echo "<table>";
            $first_row = $sample_data[0];
            echo "<tr>";
            foreach (array_keys($first_row) as $key) {
                if (!is_numeric($key)) {
                    echo "<th>$key</th>";
                }
            }
            echo "</tr>";
            
            foreach ($sample_data as $row) {
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
        echo "</div>";
    }
    
    // Check blood_offers table structure
    if (in_array('blood_offers', $tables)) {
        echo "<div class='section'>";
        echo "<h2>blood_offers Table Structure</h2>";
        $desc_stmt = $conn->query("DESCRIBE blood_offers");
        $columns = $desc_stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show sample data
        echo "<h3>Sample Data (First 5 rows)</h3>";
        $sample_stmt = $conn->query("SELECT * FROM blood_offers LIMIT 5");
        $sample_data = $sample_stmt->fetchAll();
        
        if (empty($sample_data)) {
            echo "<div class='info'>No data in blood_offers table</div>";
        } else {
            echo "<table>";
            $first_row = $sample_data[0];
            echo "<tr>";
            foreach (array_keys($first_row) as $key) {
                if (!is_numeric($key)) {
                    echo "<th>$key</th>";
                }
            }
            echo "</tr>";
            
            foreach ($sample_data as $row) {
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
        echo "</div>";
    }
    
    // Check users table
    if (in_array('users', $tables)) {
        echo "<div class='section'>";
        echo "<h2>users Table - User Types</h2>";
        $users_stmt = $conn->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
        $user_counts = $users_stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>User Type</th><th>Count</th></tr>";
        foreach ($user_counts as $count) {
            echo "<tr><td>{$count['user_type']}</td><td>{$count['count']}</td></tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    // Check hospitals table
    if (in_array('hospitals', $tables)) {
        echo "<div class='section'>";
        echo "<h2>hospitals Table Structure</h2>";
        $desc_stmt = $conn->query("DESCRIBE hospitals");
        $columns = $desc_stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show hospital data
        echo "<h3>Hospital Records</h3>";
        $hospital_stmt = $conn->query("SELECT id, hospital_name, is_verified, is_active FROM hospitals LIMIT 5");
        $hospitals = $hospital_stmt->fetchAll();
        
        if (empty($hospitals)) {
            echo "<div class='info'>No hospitals in database</div>";
        } else {
            echo "<table>";
            echo "<tr><th>ID</th><th>Hospital Name</th><th>Verified</th><th>Active</th></tr>";
            foreach ($hospitals as $hospital) {
                echo "<tr>";
                echo "<td>{$hospital['id']}</td>";
                echo "<td>{$hospital['hospital_name']}</td>";
                echo "<td>" . ($hospital['is_verified'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($hospital['is_active'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "</div>";
    }
    
    // Check patients table
    if (in_array('patients', $tables)) {
        echo "<div class='section'>";
        echo "<h2>patients Table</h2>";
        $patients_stmt = $conn->query("SELECT COUNT(*) as count FROM patients");
        $patient_count = $patients_stmt->fetch();
        echo "<div class='info'>Total patients: {$patient_count['count']}</div>";
        echo "</div>";
    }
    
    // Check donors table
    if (in_array('donors', $tables)) {
        echo "<div class='section'>";
        echo "<h2>donors Table</h2>";
        $donors_stmt = $conn->query("SELECT COUNT(*) as count FROM donors");
        $donor_count = $donors_stmt->fetch();
        echo "<div class='info'>Total donors: {$donor_count['count']}</div>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>Database Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>