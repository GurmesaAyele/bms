<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test New Donation Table Structure</title>
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
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test New Donation Table Structure</h1>
        
        <?php
        session_start();
        
        try {
            require_once 'backend/config/database.php';
            echo '<div class="success">✅ Database connected successfully</div>';
            
            // Check table structure
            echo "<h2>1. Table Structure Verification</h2>";
            $columns = $conn->query("DESCRIBE donation_offers")->fetchAll();
            
            echo "<table>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            $required_columns = ['assigned_hospital_id', 'offered_by_user_id', 'accepted_by_user_id', 'rejected_by_user_id'];
            $found_columns = [];
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "</tr>";
                
                if (in_array($column['Field'], $required_columns)) {
                    $found_columns[] = $column['Field'];
                }
            }
            echo "</table>";
            
            foreach ($required_columns as $req_col) {
                if (in_array($req_col, $found_columns)) {
                    echo "<div class='success'>✅ Required column '$req_col' exists</div>";
                } else {
                    echo "<div class='error'>❌ Missing required column '$req_col'</div>";
                }
            }
            
            // Check if user is logged in
            echo "<h2>2. Session Check</h2>";
            if (isset($_SESSION['user_id'])) {
                echo "<div class='success'>✅ User logged in: ID {$_SESSION['user_id']}, Type: {$_SESSION['user_type']}</div>";
                
                if ($_SESSION['user_type'] === 'donor') {
                    // Get donor data
                    $stmt = $conn->prepare("
                        SELECT u.*, d.donor_id, d.blood_type, d.is_eligible
                        FROM users u 
                        JOIN donors d ON u.id = d.user_id 
                        WHERE u.id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $donor_data = $stmt->fetch();
                    
                    if ($donor_data) {
                        echo "<div class='info'>👤 Donor: {$donor_data['first_name']} {$donor_data['last_name']} ({$donor_data['blood_type']}) - Eligible: " . ($donor_data['is_eligible'] ? 'Yes' : 'No') . "</div>";
                    }
                }
            } else {
                echo "<div class='warning'>⚠️ No user logged in. <a href='frontend/auth/login.php'>Login here</a></div>";
            }
            
            // Check available hospitals
            echo "<h2>3. Available Hospitals</h2>";
            $hospitals = $conn->query("
                SELECT h.id, h.hospital_name, h.is_verified, h.is_active, u.city
                FROM hospitals h
                JOIN users u ON h.user_id = u.id
                WHERE h.is_verified = 1 AND h.is_active = 1
                ORDER BY h.hospital_name
            ")->fetchAll();
            
            if (count($hospitals) > 0) {
                echo "<div class='success'>✅ Found " . count($hospitals) . " available hospital(s)</div>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Hospital Name</th><th>City</th><th>Verified</th><th>Active</th></tr>";
                foreach ($hospitals as $hospital) {
                    echo "<tr>";
                    echo "<td>{$hospital['id']}</td>";
                    echo "<td>{$hospital['hospital_name']}</td>";
                    echo "<td>{$hospital['city']}</td>";
                    echo "<td>" . ($hospital['is_verified'] ? 'Yes' : 'No') . "</td>";
                    echo "<td>" . ($hospital['is_active'] ? 'Yes' : 'No') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='error'>❌ No available hospitals found</div>";
            }
            
            // Test donation offer insertion with new structure
            echo "<h2>4. Test Donation Offer Insertion</h2>";
            
            if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'donor' && count($hospitals) > 0) {
                $test_hospital = $hospitals[0];
                $test_offer_id = 'TEST-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
                
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO donation_offers (
                            offer_id, donor_id, assigned_hospital_id, blood_type, 
                            preferred_date, preferred_time, notes, status, 
                            offered_by_user_id, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
                    ");
                    
                    $test_data = [
                        $test_offer_id,
                        $_SESSION['user_id'],
                        $test_hospital['id'],
                        $donor_data['blood_type'] ?? 'O+',
                        date('Y-m-d', strtotime('+1 day')),
                        '10:00:00',
                        'Test donation offer with new table structure',
                        $_SESSION['user_id']
                    ];
                    
                    echo "<h4>Test Data:</h4>";
                    echo "<pre>";
                    print_r($test_data);
                    echo "</pre>";
                    
                    $result = $stmt->execute($test_data);
                    
                    if ($result) {
                        echo "<div class='success'>✅ Test donation offer inserted successfully!</div>";
                        echo "<p><strong>Test Offer ID:</strong> $test_offer_id</p>";
                        
                        // Verify it exists
                        $verify = $conn->prepare("SELECT * FROM donation_offers WHERE offer_id = ?");
                        $verify->execute([$test_offer_id]);
                        $inserted = $verify->fetch();
                        
                        if ($inserted) {
                            echo "<div class='success'>✅ Verified: Offer found in database</div>";
                            echo "<h4>Inserted Record:</h4>";
                            echo "<table>";
                            foreach ($inserted as $key => $value) {
                                if (!is_numeric($key)) {
                                    echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
                                }
                            }
                            echo "</table>";
                            
                            // Clean up test data
                            $cleanup = $conn->prepare("DELETE FROM donation_offers WHERE offer_id = ?");
                            $cleanup->execute([$test_offer_id]);
                            echo "<div class='info'>ℹ️ Test data cleaned up</div>";
                        }
                    } else {
                        echo "<div class='error'>❌ Test insertion failed</div>";
                        $errorInfo = $stmt->errorInfo();
                        echo "<pre>";
                        print_r($errorInfo);
                        echo "</pre>";
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Test error: " . $e->getMessage() . "</div>";
                }
                
            } else {
                echo "<div class='warning'>⚠️ Cannot test insertion - need to be logged in as donor with available hospitals</div>";
            }
            
            // Show existing donation offers
            echo "<h2>5. Current Donation Offers</h2>";
            $offers = $conn->query("
                SELECT do.*, h.hospital_name, u.first_name, u.last_name
                FROM donation_offers do
                LEFT JOIN hospitals h ON do.assigned_hospital_id = h.id
                LEFT JOIN users u ON do.donor_id = u.id
                ORDER BY do.created_at DESC
                LIMIT 10
            ")->fetchAll();
            
            if (count($offers) > 0) {
                echo "<div class='success'>✅ Found " . count($offers) . " donation offer(s)</div>";
                echo "<table>";
                echo "<tr><th>Offer ID</th><th>Donor</th><th>Hospital</th><th>Blood Type</th><th>Date</th><th>Status</th><th>Created</th></tr>";
                foreach ($offers as $offer) {
                    echo "<tr>";
                    echo "<td>{$offer['offer_id']}</td>";
                    echo "<td>{$offer['first_name']} {$offer['last_name']}</td>";
                    echo "<td>{$offer['hospital_name']}</td>";
                    echo "<td>{$offer['blood_type']}</td>";
                    echo "<td>{$offer['preferred_date']}</td>";
                    echo "<td>{$offer['status']}</td>";
                    echo "<td>" . date('M j, Y', strtotime($offer['created_at'])) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='info'>ℹ️ No donation offers found</div>";
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
        }
        ?>
        
        <h2>✅ Code Updates Applied!</h2>
        <div class="success">
            <h3>What was updated in the PHP code:</h3>
            <ul>
                <li>✅ Updated donor dashboard INSERT query to use <code>assigned_hospital_id</code></li>
                <li>✅ Added <code>offered_by_user_id</code> to track who made the offer</li>
                <li>✅ Updated donor dashboard SELECT query to use <code>assigned_hospital_id</code></li>
                <li>✅ Updated hospital dashboard queries to use new column names</li>
                <li>✅ Updated hospital offer management (accept/reject/complete) queries</li>
                <li>✅ Added proper tracking for accepted_by_user_id, rejected_by_user_id, etc.</li>
            </ul>
        </div>
        
        <h2>🚀 Test Your System Now</h2>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
            <h4>Step-by-Step Testing:</h4>
            <ol>
                <li><strong>Login as Donor:</strong> <a href="frontend/auth/login.php" class="btn btn-success">Login</a></li>
                <li><strong>Submit Donation Offer:</strong> <a href="frontend/dashboard/donor.php" class="btn btn-info">Donor Dashboard</a></li>
                <li><strong>Check Hospital Dashboard:</strong> <a href="frontend/dashboard/hospital.php" class="btn">Hospital Dashboard</a></li>
            </ol>
        </div>
        
        <div class="info">
            <h4>📋 If you still have issues:</h4>
            <ul>
                <li>Check browser console for JavaScript errors</li>
                <li>Ensure you're logged in as an eligible donor</li>
                <li>Make sure there are active hospitals available</li>
                <li>Fill all required form fields</li>
                <li>Use the debug tool: <a href="debug_donor_submission.php">Debug Donor Submission</a></li>
            </ul>
        </div>
    </div>
</body>
</html>