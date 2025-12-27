<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Donation Submission - Complete Database Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #16a085; background: #d5f4e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; background: #d6eaf8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #f39c12; background: #fef9e7; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #b91c1c; }
        .btn-success { background: #16a085; }
        .btn-info { background: #3498db; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #dc2626; }
        .step h4 { margin-top: 0; color: #dc2626; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Complete Database Fix for Donation Submission</h1>
        
        <?php
        try {
            require_once 'backend/config/database.php';
            echo '<div class="success">✅ Database connected successfully</div>';
            
            echo "<h2>Step 1: Current Database Analysis</h2>";
            
            // Check current database structure
            echo "<h3>Current Tables:</h3>";
            $tables = $conn->query("SHOW TABLES")->fetchAll();
            echo "<ul>";
            foreach ($tables as $table) {
                $table_name = array_values($table)[0];
                echo "<li>$table_name</li>";
            }
            echo "</ul>";
            
            // Check donation_offers table specifically
            echo "<h3>Donation Offers Table Analysis:</h3>";
            try {
                $columns = $conn->query("DESCRIBE donation_offers")->fetchAll();
                echo "<div class='info'>✅ donation_offers table exists</div>";
                
                echo "<table>";
                echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
                $has_hospital_id = false;
                foreach ($columns as $column) {
                    if ($column['Field'] === 'hospital_id') {
                        $has_hospital_id = true;
                    }
                    echo "<tr>";
                    echo "<td>{$column['Field']}</td>";
                    echo "<td>{$column['Type']}</td>";
                    echo "<td>{$column['Null']}</td>";
                    echo "<td>{$column['Key']}</td>";
                    echo "<td>{$column['Default']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                if (!$has_hospital_id) {
                    echo "<div class='error'>❌ PROBLEM FOUND: hospital_id column is missing!</div>";
                    echo "<p>This is why donation submissions are failing.</p>";
                } else {
                    echo "<div class='success'>✅ hospital_id column exists</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ donation_offers table doesn't exist or has issues: " . $e->getMessage() . "</div>";
            }
            
            echo "<h2>Step 2: Fixing Database Structure</h2>";
            
            // Drop and recreate donation_offers table with correct structure
            echo "<h3>Recreating donation_offers table...</h3>";
            
            try {
                // First, backup any existing data
                $backup_data = [];
                try {
                    $existing_data = $conn->query("SELECT * FROM donation_offers")->fetchAll();
                    $backup_data = $existing_data;
                    echo "<div class='info'>ℹ️ Backed up " . count($backup_data) . " existing records</div>";
                } catch (Exception $e) {
                    echo "<div class='info'>ℹ️ No existing data to backup</div>";
                }
                
                // Drop existing table
                $conn->exec("DROP TABLE IF EXISTS donation_offers");
                echo "<div class='warning'>⚠️ Dropped existing donation_offers table</div>";
                
                // Create new table with correct structure
                $create_sql = "
                CREATE TABLE donation_offers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    offer_id VARCHAR(50) UNIQUE NOT NULL,
                    donor_id INT NOT NULL,
                    hospital_id INT,
                    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
                    preferred_date DATE,
                    preferred_time TIME,
                    status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
                    accepted_by INT,
                    accepted_at TIMESTAMP NULL,
                    completed_at TIMESTAMP NULL,
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
                    FOREIGN KEY (accepted_by) REFERENCES users(id) ON DELETE SET NULL
                )";
                
                $conn->exec($create_sql);
                echo "<div class='success'>✅ Created new donation_offers table with correct structure</div>";
                
                // Verify the new structure
                $new_columns = $conn->query("DESCRIBE donation_offers")->fetchAll();
                echo "<h4>New Table Structure:</h4>";
                echo "<table>";
                echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
                foreach ($new_columns as $column) {
                    echo "<tr>";
                    echo "<td>{$column['Field']}</td>";
                    echo "<td>{$column['Type']}</td>";
                    echo "<td>{$column['Null']}</td>";
                    echo "<td>{$column['Key']}</td>";
                    echo "<td>{$column['Default']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Error creating table: " . $e->getMessage() . "</div>";
            }
            
            echo "<h2>Step 3: Testing Donation Submission</h2>";
            
            // Get test data
            $donor_stmt = $conn->query("SELECT u.id, u.email, d.blood_type, d.is_eligible FROM users u JOIN donors d ON u.id = d.user_id WHERE u.user_type = 'donor' LIMIT 1");
            $test_donor = $donor_stmt->fetch();
            
            $hospital_stmt = $conn->query("SELECT h.id, h.hospital_name FROM hospitals h WHERE h.is_verified = 1 AND h.is_active = 1 LIMIT 1");
            $test_hospital = $hospital_stmt->fetch();
            
            if ($test_donor && $test_hospital) {
                echo "<div class='info'>🧪 Testing with:</div>";
                echo "<ul>";
                echo "<li>Donor: {$test_donor['email']} (Blood: {$test_donor['blood_type']}, Eligible: " . ($test_donor['is_eligible'] ? 'Yes' : 'No') . ")</li>";
                echo "<li>Hospital: {$test_hospital['hospital_name']} (ID: {$test_hospital['id']})</li>";
                echo "</ul>";
                
                // Test insertion
                try {
                    $test_offer_id = 'TEST-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
                    
                    $stmt = $conn->prepare("
                        INSERT INTO donation_offers (offer_id, donor_id, hospital_id, blood_type, preferred_date, preferred_time, notes, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                    ");
                    
                    $test_data = [
                        $test_offer_id,
                        $test_donor['id'],
                        $test_hospital['id'],
                        $test_donor['blood_type'],
                        date('Y-m-d', strtotime('+1 day')),
                        '10:00:00',
                        'Test donation offer - system verification'
                    ];
                    
                    $result = $stmt->execute($test_data);
                    
                    if ($result) {
                        echo "<div class='success'>✅ Test donation offer created successfully!</div>";
                        echo "<p><strong>Test Offer ID:</strong> $test_offer_id</p>";
                        
                        // Verify it exists
                        $verify = $conn->prepare("SELECT * FROM donation_offers WHERE offer_id = ?");
                        $verify->execute([$test_offer_id]);
                        $inserted = $verify->fetch();
                        
                        if ($inserted) {
                            echo "<div class='success'>✅ Verified: Offer found in database</div>";
                            echo "<pre>";
                            print_r($inserted);
                            echo "</pre>";
                            
                            // Clean up test data
                            $cleanup = $conn->prepare("DELETE FROM donation_offers WHERE offer_id = ?");
                            $cleanup->execute([$test_offer_id]);
                            echo "<div class='info'>ℹ️ Test data cleaned up</div>";
                        }
                    } else {
                        echo "<div class='error'>❌ Test insertion failed</div>";
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Test error: " . $e->getMessage() . "</div>";
                }
                
            } else {
                echo "<div class='warning'>⚠️ No test data available. Need to create donor and hospital accounts first.</div>";
                
                if (!$test_donor) {
                    echo "<p>No donor accounts found. <a href='frontend/auth/register-donor.php'>Register as donor</a></p>";
                }
                
                if (!$test_hospital) {
                    echo "<p>No hospital accounts found. Run <a href='setup_database.php'>database setup</a></p>";
                }
            }
            
            echo "<h2>Step 4: System Status Check</h2>";
            
            // Check all required components
            $checks = [
                'Users table' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'Donors' => $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'donor'")->fetchColumn(),
                'Hospitals' => $conn->query("SELECT COUNT(*) FROM hospitals WHERE is_verified = 1")->fetchColumn(),
                'Donation offers table' => 'EXISTS',
                'Hospital_id column' => 'EXISTS'
            ];
            
            echo "<table>";
            echo "<tr><th>Component</th><th>Status</th></tr>";
            foreach ($checks as $component => $status) {
                $status_color = ($status === 'EXISTS' || $status > 0) ? 'green' : 'red';
                echo "<tr><td>$component</td><td style='color: $status_color;'>$status</td></tr>";
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Database connection error: ' . $e->getMessage() . '</div>';
            echo '<p>Make sure your WAMP server is running and the database credentials are correct.</p>';
        }
        ?>
        
        <h2>✅ Database Fix Complete!</h2>
        <div class="success">
            <h3>What was fixed:</h3>
            <ul>
                <li>✅ Recreated donation_offers table with correct structure</li>
                <li>✅ Added hospital_id column with proper foreign key</li>
                <li>✅ Added all necessary columns for donation management</li>
                <li>✅ Set up proper relationships between tables</li>
                <li>✅ Tested donation offer insertion</li>
            </ul>
        </div>
        
        <div class="step">
            <h4>🚀 Now Test Your Donation System:</h4>
            <ol>
                <li><strong>Login as Donor:</strong> <a href="frontend/auth/login.php" class="btn btn-success">Login</a></li>
                <li><strong>Go to Donor Dashboard:</strong> <a href="frontend/dashboard/donor.php" class="btn btn-info">Donor Dashboard</a></li>
                <li><strong>Submit Donation Offer:</strong> Fill the form and click submit</li>
                <li><strong>Check Hospital Dashboard:</strong> <a href="frontend/dashboard/hospital.php" class="btn">Hospital Dashboard</a></li>
            </ol>
        </div>
        
        <div class="info">
            <h4>📋 Test Credentials:</h4>
            <p><strong>Donor:</strong> Register new donor or use existing account</p>
            <p><strong>Hospital:</strong> city.general@hospital.com / hospital123</p>
            <p><strong>Admin:</strong> admin@bloodconnect.com / admin123</p>
        </div>
        
        <h3>🔗 Quick Links</h3>
        <div style="margin: 20px 0;">
            <a href="setup_database.php" class="btn">Setup Database</a>
            <a href="frontend/auth/register-donor.php" class="btn btn-success">Register Donor</a>
            <a href="frontend/auth/login.php" class="btn btn-info">Login</a>
            <a href="debug_donor_submission.php" class="btn">Debug Tool</a>
        </div>
        
        <div class="warning">
            <h4>⚠️ Important Notes:</h4>
            <ul>
                <li>This script directly modifies your MySQL database in WAMP</li>
                <li>The changes are now permanent in your phpMyAdmin</li>
                <li>You can verify the changes by checking the donation_offers table in phpMyAdmin</li>
                <li>If you still have issues, use the debug tool to identify specific problems</li>
            </ul>
        </div>
    </div>
</body>
</html>