<?php
/**
 * Debug Hospital Data Issue
 * Find out exactly why the hospital dashboard is not showing data
 */

session_start();
require_once 'backend/config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Hospital Data Debug</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; font-weight: bold; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    .debug-query { background: #e8f4f8; padding: 10px; margin: 10px 0; border-left: 4px solid #3498db; }
</style></head><body>";

echo "<h1>Hospital Data Debug - Finding the Issue</h1>";

try {
    // Step 1: Check database connection
    echo "<div class='section'>";
    echo "<h2>Step 1: Database Connection</h2>";
    if ($conn) {
        echo "<div class='success'>✓ Database connection successful</div>";
    } else {
        echo "<div class='error'>✗ Database connection failed</div>";
        exit();
    }
    echo "</div>";
    
    // Step 2: Check current session
    echo "<div class='section'>";
    echo "<h2>Step 2: Session Information</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<div class='info'>Session User ID: {$_SESSION['user_id']}</div>";
        echo "<div class='info'>Session User Type: " . ($_SESSION['user_type'] ?? 'Not set') . "</div>";
    } else {
        echo "<div class='warning'>No active session found. Creating test session...</div>";
        // Set a test session for debugging
        $_SESSION['user_id'] = 1;
        $_SESSION['user_type'] = 'hospital';
        echo "<div class='info'>Test session created: User ID = 1, Type = hospital</div>";
    }
    echo "</div>";
    
    // Step 3: Check available tables
    echo "<div class='section'>";
    echo "<h2>Step 3: Available Tables</h2>";
    $tables_stmt = $conn->query("SHOW TABLES");
    $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    
    // Check for required tables
    $required_tables = ['users', 'hospitals', 'patients', 'donors', 'blood_requests', 'donation_offers'];
    $missing_tables = array_diff($required_tables, $tables);
    
    if (empty($missing_tables)) {
        echo "<div class='success'>✓ All required tables exist</div>";
    } else {
        echo "<div class='error'>✗ Missing tables: " . implode(', ', $missing_tables) . "</div>";
    }
    echo "</div>";
    
    // Step 4: Check users table
    echo "<div class='section'>";
    echo "<h2>Step 4: Users Table Analysis</h2>";
    
    $users_count = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<div class='info'>Total users: $users_count</div>";
    
    if ($users_count > 0) {
        $user_types = $conn->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type")->fetchAll();
        echo "<table>";
        echo "<tr><th>User Type</th><th>Count</th></tr>";
        foreach ($user_types as $type) {
            echo "<tr><td>{$type['user_type']}</td><td>{$type['count']}</td></tr>";
        }
        echo "</table>";
        
        // Show sample users
        echo "<h3>Sample Users:</h3>";
        $sample_users = $conn->query("SELECT id, email, user_type, first_name, last_name, is_active, is_verified FROM users LIMIT 5")->fetchAll();
        echo "<table>";
        echo "<tr><th>ID</th><th>Email</th><th>Type</th><th>Name</th><th>Active</th><th>Verified</th></tr>";
        foreach ($sample_users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['user_type']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($user['is_verified'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Step 5: Check hospitals table
    echo "<div class='section'>";
    echo "<h2>Step 5: Hospitals Table Analysis</h2>";
    
    if (in_array('hospitals', $tables)) {
        $hospitals_count = $conn->query("SELECT COUNT(*) FROM hospitals")->fetchColumn();
        echo "<div class='info'>Total hospitals: $hospitals_count</div>";
        
        if ($hospitals_count > 0) {
            $hospitals = $conn->query("SELECT h.*, u.email FROM hospitals h LEFT JOIN users u ON h.user_id = u.id LIMIT 5")->fetchAll();
            echo "<table>";
            echo "<tr><th>ID</th><th>User ID</th><th>Email</th><th>Hospital Name</th><th>Verified</th><th>Active</th></tr>";
            foreach ($hospitals as $hospital) {
                echo "<tr>";
                echo "<td>{$hospital['id']}</td>";
                echo "<td>{$hospital['user_id']}</td>";
                echo "<td>{$hospital['email']}</td>";
                echo "<td>{$hospital['hospital_name']}</td>";
                echo "<td>" . ($hospital['is_verified'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($hospital['is_active'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>No hospitals found in database</div>";
        }
    } else {
        echo "<div class='error'>Hospitals table does not exist</div>";
    }
    echo "</div>";
    
    // Step 6: Check blood_requests table
    echo "<div class='section'>";
    echo "<h2>Step 6: Blood Requests Table Analysis</h2>";
    
    if (in_array('blood_requests', $tables)) {
        $requests_count = $conn->query("SELECT COUNT(*) FROM blood_requests")->fetchColumn();
        echo "<div class='info'>Total blood requests: $requests_count</div>";
        
        if ($requests_count > 0) {
            // Show table structure
            echo "<h3>Table Structure:</h3>";
            $desc = $conn->query("DESCRIBE blood_requests")->fetchAll();
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($desc as $col) {
                echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
            }
            echo "</table>";
            
            // Show sample data
            echo "<h3>Sample Blood Requests:</h3>";
            $requests = $conn->query("SELECT * FROM blood_requests LIMIT 5")->fetchAll();
            if (!empty($requests)) {
                echo "<table>";
                $first_row = $requests[0];
                echo "<tr>";
                foreach (array_keys($first_row) as $key) {
                    if (!is_numeric($key)) {
                        echo "<th>$key</th>";
                    }
                }
                echo "</tr>";
                
                foreach ($requests as $request) {
                    echo "<tr>";
                    foreach ($request as $key => $value) {
                        if (!is_numeric($key)) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Check status distribution
            echo "<h3>Status Distribution:</h3>";
            $status_dist = $conn->query("SELECT status, COUNT(*) as count FROM blood_requests GROUP BY status")->fetchAll();
            echo "<table>";
            echo "<tr><th>Status</th><th>Count</th></tr>";
            foreach ($status_dist as $status) {
                echo "<tr><td>{$status['status']}</td><td>{$status['count']}</td></tr>";
            }
            echo "</table>";
            
        } else {
            echo "<div class='warning'>No blood requests found in database</div>";
        }
    } else {
        echo "<div class='error'>blood_requests table does not exist</div>";
    }
    echo "</div>";
    
    // Step 7: Check donation_offers table
    echo "<div class='section'>";
    echo "<h2>Step 7: Donation Offers Table Analysis</h2>";
    
    if (in_array('donation_offers', $tables)) {
        $offers_count = $conn->query("SELECT COUNT(*) FROM donation_offers")->fetchColumn();
        echo "<div class='info'>Total donation offers: $offers_count</div>";
        
        if ($offers_count > 0) {
            // Show table structure
            echo "<h3>Table Structure:</h3>";
            $desc = $conn->query("DESCRIBE donation_offers")->fetchAll();
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($desc as $col) {
                echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
            }
            echo "</table>";
            
            // Show sample data
            echo "<h3>Sample Donation Offers:</h3>";
            $offers = $conn->query("SELECT * FROM donation_offers LIMIT 5")->fetchAll();
            if (!empty($offers)) {
                echo "<table>";
                $first_row = $offers[0];
                echo "<tr>";
                foreach (array_keys($first_row) as $key) {
                    if (!is_numeric($key)) {
                        echo "<th>$key</th>";
                    }
                }
                echo "</tr>";
                
                foreach ($offers as $offer) {
                    echo "<tr>";
                    foreach ($offer as $key => $value) {
                        if (!is_numeric($key)) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } else {
            echo "<div class='warning'>No donation offers found in database</div>";
        }
    } else {
        echo "<div class='error'>donation_offers table does not exist</div>";
    }
    echo "</div>";
    
    // Step 8: Test hospital dashboard queries
    echo "<div class='section'>";
    echo "<h2>Step 8: Testing Hospital Dashboard Queries</h2>";
    
    $test_user_id = $_SESSION['user_id'];
    echo "<div class='info'>Testing with User ID: $test_user_id</div>";
    
    // Test hospital lookup query
    echo "<h3>8.1 Hospital Lookup Query</h3>";
    $hospital_query = "
        SELECT u.*, h.id as hospital_db_id, h.hospital_name, h.hospital_type, h.license_number,
               h.is_verified, h.is_active
        FROM users u 
        LEFT JOIN hospitals h ON u.id = h.user_id 
        WHERE u.id = ?
    ";
    echo "<div class='debug-query'>Query: " . htmlspecialchars($hospital_query) . "</div>";
    
    try {
        $stmt = $conn->prepare($hospital_query);
        $stmt->execute([$test_user_id]);
        $user_data = $stmt->fetch();
        
        if ($user_data) {
            echo "<div class='success'>✓ Hospital lookup successful</div>";
            echo "<table>";
            foreach ($user_data as $key => $value) {
                if (!is_numeric($key)) {
                    echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
                }
            }
            echo "</table>";
            
            $hospital_db_id = $user_data['hospital_db_id'];
            echo "<div class='info'>Hospital DB ID: " . ($hospital_db_id ?? 'NULL') . "</div>";
            
        } else {
            echo "<div class='error'>✗ No hospital data found for user ID $test_user_id</div>";
            $hospital_db_id = null;
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Query failed: " . $e->getMessage() . "</div>";
        $hospital_db_id = null;
    }
    
    // Test blood requests query
    if ($hospital_db_id) {
        echo "<h3>8.2 Blood Requests Query</h3>";
        $requests_query = "
            SELECT br.*, u.first_name, u.last_name, u.email, u.phone
            FROM blood_requests br
            LEFT JOIN users u ON br.requested_by_user_id = u.id
            WHERE br.assigned_hospital_id = ?
            ORDER BY br.created_at DESC
            LIMIT 10
        ";
        echo "<div class='debug-query'>Query: " . htmlspecialchars($requests_query) . "</div>";
        echo "<div class='debug-query'>Parameter: hospital_db_id = $hospital_db_id</div>";
        
        try {
            $stmt = $conn->prepare($requests_query);
            $stmt->execute([$hospital_db_id]);
            $blood_requests = $stmt->fetchAll();
            
            echo "<div class='info'>Found " . count($blood_requests) . " blood requests</div>";
            
            if (!empty($blood_requests)) {
                echo "<table>";
                $first_row = $blood_requests[0];
                echo "<tr>";
                foreach (array_keys($first_row) as $key) {
                    if (!is_numeric($key)) {
                        echo "<th>$key</th>";
                    }
                }
                echo "</tr>";
                
                foreach ($blood_requests as $request) {
                    echo "<tr>";
                    foreach ($request as $key => $value) {
                        if (!is_numeric($key)) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='error'>Blood requests query failed: " . $e->getMessage() . "</div>";
        }
        
        // Test donation offers query
        echo "<h3>8.3 Donation Offers Query</h3>";
        $offers_query = "
            SELECT do.*, u.first_name, u.last_name, u.email, u.phone
            FROM donation_offers do
            LEFT JOIN users u ON do.donor_id = u.id
            WHERE do.assigned_hospital_id = ?
            ORDER BY do.created_at DESC
            LIMIT 10
        ";
        echo "<div class='debug-query'>Query: " . htmlspecialchars($offers_query) . "</div>";
        echo "<div class='debug-query'>Parameter: hospital_db_id = $hospital_db_id</div>";
        
        try {
            $stmt = $conn->prepare($offers_query);
            $stmt->execute([$hospital_db_id]);
            $donation_offers = $stmt->fetchAll();
            
            echo "<div class='info'>Found " . count($donation_offers) . " donation offers</div>";
            
            if (!empty($donation_offers)) {
                echo "<table>";
                $first_row = $donation_offers[0];
                echo "<tr>";
                foreach (array_keys($first_row) as $key) {
                    if (!is_numeric($key)) {
                        echo "<th>$key</th>";
                    }
                }
                echo "</tr>";
                
                foreach ($donation_offers as $offer) {
                    echo "<tr>";
                    foreach ($offer as $key => $value) {
                        if (!is_numeric($key)) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='error'>Donation offers query failed: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='warning'>Cannot test queries - no hospital_db_id found</div>";
    }
    echo "</div>";
    
    // Step 9: Diagnosis and recommendations
    echo "<div class='section'>";
    echo "<h2>Step 9: Diagnosis and Recommendations</h2>";
    
    $issues = [];
    $recommendations = [];
    
    if (!isset($user_data) || !$user_data) {
        $issues[] = "No hospital user found for the current session";
        $recommendations[] = "Create a hospital user or login with valid hospital credentials";
    }
    
    if (!$hospital_db_id) {
        $issues[] = "Hospital record not found in hospitals table";
        $recommendations[] = "Create a hospital record linked to the user account";
    }
    
    if ($requests_count == 0) {
        $issues[] = "No blood requests in the database";
        $recommendations[] = "Create test blood requests or wait for real submissions";
    }
    
    if ($offers_count == 0) {
        $issues[] = "No donation offers in the database";
        $recommendations[] = "Create test donation offers or wait for real submissions";
    }
    
    if (isset($blood_requests) && empty($blood_requests)) {
        $issues[] = "No blood requests assigned to this hospital";
        $recommendations[] = "Check if blood requests have correct assigned_hospital_id";
    }
    
    if (isset($donation_offers) && empty($donation_offers)) {
        $issues[] = "No donation offers assigned to this hospital";
        $recommendations[] = "Check if donation offers have correct assigned_hospital_id";
    }
    
    if (!empty($issues)) {
        echo "<h3>Issues Found:</h3>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li class='error'>$issue</li>";
        }
        echo "</ul>";
        
        echo "<h3>Recommendations:</h3>";
        echo "<ul>";
        foreach ($recommendations as $rec) {
            echo "<li class='info'>$rec</li>";
        }
        echo "</ul>";
    } else {
        echo "<div class='success'>✓ No issues found - data should be displaying correctly</div>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Database Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>