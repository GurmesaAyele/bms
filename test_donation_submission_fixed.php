<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Donation Submission - Fixed</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #16a085; background: #d5f4e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; background: #d6eaf8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #b91c1c; }
        .btn-success { background: #16a085; }
        .btn-info { background: #3498db; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .form-container { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .form-group { margin: 10px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        button { background: #dc2626; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #b91c1c; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🩸 Test Donation Submission - Fixed for Current Table</h1>
        
        <?php
        session_start();
        
        try {
            require_once 'backend/config/database.php';
            echo '<div class="success">✅ Database connected successfully</div>';
            
            // Check current table structure
            echo "<h2>1. Current Table Structure</h2>";
            $columns = $conn->query("DESCRIBE donation_offers")->fetchAll();
            
            echo "<table>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Check session
            echo "<h2>2. Session Status</h2>";
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
                } else {
                    echo "<div class='error'>❌ You need to be logged in as a donor to test this</div>";
                }
            } else {
                echo "<div class='error'>❌ No user logged in. <a href='frontend/auth/login.php'>Login here</a></div>";
            }
            
            // Get hospitals
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
            } else {
                echo "<div class='error'>❌ No available hospitals found</div>";
            }
            
            // Handle form submission
            if ($_POST && isset($_POST['test_submit']) && isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'donor') {
                echo "<h2>4. Form Submission Test</h2>";
                
                $hospital_id = (int)$_POST['hospital'];
                $preferred_date = $_POST['preferredDate'];
                $preferred_time = $_POST['preferredTime'];
                $notes = trim($_POST['notes']);
                
                echo "<h3>Submitted Data:</h3>";
                echo "<ul>";
                echo "<li>Hospital ID: $hospital_id</li>";
                echo "<li>Date: $preferred_date</li>";
                echo "<li>Time: $preferred_time</li>";
                echo "<li>Notes: $notes</li>";
                echo "</ul>";
                
                // Validation
                $validation_errors = [];
                
                if (empty($hospital_id) || $hospital_id <= 0) {
                    $validation_errors[] = "Please select a hospital.";
                }
                
                if (empty($preferred_date)) {
                    $validation_errors[] = "Please select a preferred date.";
                } elseif (strtotime($preferred_date) < strtotime(date('Y-m-d'))) {
                    $validation_errors[] = "Preferred date cannot be in the past.";
                }
                
                if (empty($preferred_time)) {
                    $validation_errors[] = "Please select a preferred time.";
                }
                
                if (!$donor_data['is_eligible']) {
                    $validation_errors[] = "You are not currently eligible to donate blood.";
                }
                
                if (!empty($validation_errors)) {
                    echo "<div class='error'>";
                    echo "<h4>❌ Validation Errors:</h4>";
                    foreach ($validation_errors as $error) {
                        echo "• $error<br>";
                    }
                    echo "</div>";
                } else {
                    echo "<div class='success'>✅ Validation passed</div>";
                    
                    try {
                        // Check if hospital exists
                        $hospital_check = $conn->prepare("SELECT id FROM hospitals WHERE id = ? AND is_verified = 1 AND is_active = 1");
                        $hospital_check->execute([$hospital_id]);
                        if (!$hospital_check->fetch()) {
                            throw new Exception("Selected hospital is not available.");
                        }
                        
                        $offer_id = 'TEST-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
                        
                        echo "<h4>Attempting Database Insert:</h4>";
                        echo "<p>Offer ID: $offer_id</p>";
                        
                        $stmt = $conn->prepare("
                            INSERT INTO donation_offers (offer_id, donor_id, hospital_id, blood_type, preferred_date, 
                                                       preferred_time, notes, status, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                        ");
                        
                        $result = $stmt->execute([
                            $offer_id,
                            $_SESSION['user_id'],
                            $hospital_id,
                            $donor_data['blood_type'],
                            $preferred_date,
                            $preferred_time,
                            $notes
                        ]);
                        
                        if ($result) {
                            echo "<div class='success'>✅ SUCCESS! Donation offer submitted successfully!</div>";
                            echo "<p><strong>Offer ID:</strong> $offer_id</p>";
                            
                            // Verify it exists
                            $verify = $conn->prepare("SELECT * FROM donation_offers WHERE offer_id = ?");
                            $verify->execute([$offer_id]);
                            $inserted = $verify->fetch();
                            
                            if ($inserted) {
                                echo "<div class='success'>✅ Verified: Offer found in database</div>";
                                echo "<table>";
                                echo "<tr><th>Field</th><th>Value</th></tr>";
                                foreach ($inserted as $key => $value) {
                                    if (!is_numeric($key)) {
                                        echo "<tr><td>$key</td><td>$value</td></tr>";
                                    }
                                }
                                echo "</table>";
                            }
                        } else {
                            echo "<div class='error'>❌ FAILED! Database insertion failed</div>";
                            $errorInfo = $stmt->errorInfo();
                            echo "<pre>";
                            print_r($errorInfo);
                            echo "</pre>";
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='error'>❌ ERROR: " . $e->getMessage() . "</div>";
                    }
                }
            }
            
            // Show existing offers
            echo "<h2>5. Current Donation Offers</h2>";
            $offers = $conn->query("
                SELECT do.*, h.hospital_name, u.first_name, u.last_name
                FROM donation_offers do
                LEFT JOIN hospitals h ON do.hospital_id = h.id
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
        
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'donor' && count($hospitals) > 0): ?>
        <h2>6. Test Donation Form</h2>
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label>Hospital:</label>
                    <select name="hospital" required>
                        <option value="">Select Hospital</option>
                        <?php foreach ($hospitals as $hospital): ?>
                            <option value="<?php echo $hospital['id']; ?>">
                                <?php echo htmlspecialchars($hospital['hospital_name'] . ' - ' . $hospital['city']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Preferred Date:</label>
                    <input type="date" name="preferredDate" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Preferred Time:</label>
                    <select name="preferredTime" required>
                        <option value="">Select Time</option>
                        <option value="09:00:00">9:00 AM</option>
                        <option value="10:00:00">10:00 AM</option>
                        <option value="11:00:00">11:00 AM</option>
                        <option value="14:00:00">2:00 PM</option>
                        <option value="15:00:00">3:00 PM</option>
                        <option value="16:00:00">4:00 PM</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Notes:</label>
                    <textarea name="notes" rows="3" placeholder="Any special requirements..."></textarea>
                </div>
                
                <button type="submit" name="test_submit">Test Submit Donation Offer</button>
            </form>
        </div>
        <?php endif; ?>
        
        <h2>✅ Code Fixed for Current Table Structure!</h2>
        <div class="success">
            <h3>What was updated:</h3>
            <ul>
                <li>✅ Fixed INSERT query to use existing <code>hospital_id</code> column</li>
                <li>✅ Fixed SELECT queries to use existing table structure</li>
                <li>✅ Updated hospital dashboard queries</li>
                <li>✅ Enhanced success/error message display</li>
                <li>✅ Added proper validation and error handling</li>
            </ul>
        </div>
        
        <h3>🚀 Now Test Your Real System</h3>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
            <p><strong>The donation submission should now work! Try it:</strong></p>
            <a href="frontend/dashboard/donor.php" class="btn btn-success">Go to Donor Dashboard</a>
            <a href="frontend/dashboard/hospital.php" class="btn btn-info">Hospital Dashboard</a>
        </div>
    </div>
</body>
</html>