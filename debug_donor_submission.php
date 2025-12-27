<?php
/**
 * Debug Donor Submission
 * This script helps debug why donation offers aren't being submitted
 */

session_start();
require_once 'backend/config/database.php';

echo "<h1>🐛 Debug Donor Submission</h1>";

// Check if user is logged in
echo "<h2>1. Session Check</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<div style='color: green;'>✅ User logged in: ID {$_SESSION['user_id']}, Type: {$_SESSION['user_type']}</div>";
    
    if ($_SESSION['user_type'] !== 'donor') {
        echo "<div style='color: red;'>❌ User is not a donor! Type: {$_SESSION['user_type']}</div>";
        echo "<p>You need to login as a donor to test this.</p>";
        exit;
    }
} else {
    echo "<div style='color: red;'>❌ No user logged in</div>";
    echo "<p>Please <a href='frontend/auth/login.php'>login as a donor</a> first.</p>";
    exit;
}

// Get user data
echo "<h2>2. User Data Check</h2>";
try {
    $stmt = $conn->prepare("
        SELECT u.*, d.donor_id, d.blood_type, d.is_eligible
        FROM users u 
        JOIN donors d ON u.id = d.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
    
    if ($user_data) {
        echo "<div style='color: green;'>✅ User data found</div>";
        echo "<ul>";
        echo "<li>Name: {$user_data['first_name']} {$user_data['last_name']}</li>";
        echo "<li>Email: {$user_data['email']}</li>";
        echo "<li>Donor ID: {$user_data['donor_id']}</li>";
        echo "<li>Blood Type: {$user_data['blood_type']}</li>";
        echo "<li>Eligible: " . ($user_data['is_eligible'] ? 'Yes' : 'No') . "</li>";
        echo "</ul>";
    } else {
        echo "<div style='color: red;'>❌ No donor data found for user ID {$_SESSION['user_id']}</div>";
        exit;
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error getting user data: " . $e->getMessage() . "</div>";
    exit;
}

// Check hospitals
echo "<h2>3. Available Hospitals Check</h2>";
try {
    $hospitals_stmt = $conn->prepare("
        SELECT h.id, h.hospital_name, h.hospital_type, h.is_verified, h.is_active, h.has_blood_bank,
               u.city
        FROM hospitals h
        JOIN users u ON h.user_id = u.id
        WHERE h.is_verified = 1 AND h.is_active = 1 AND h.has_blood_bank = 1
        ORDER BY h.hospital_name
    ");
    $hospitals_stmt->execute();
    $hospitals = $hospitals_stmt->fetchAll();
    
    if (count($hospitals) > 0) {
        echo "<div style='color: green;'>✅ Found " . count($hospitals) . " available hospital(s)</div>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
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
        echo "<div style='color: red;'>❌ No available hospitals found</div>";
        echo "<p>This could be why the form isn't working. Run <a href='setup_database.php'>database setup</a> first.</p>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking hospitals: " . $e->getMessage() . "</div>";
}

// Check donation_offers table
echo "<h2>4. Donation Offers Table Check</h2>";
try {
    $columns = $conn->query("DESCRIBE donation_offers")->fetchAll();
    echo "<div style='color: green;'>✅ donation_offers table exists</div>";
    
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
        echo "<div style='color: red;'>❌ hospital_id column missing!</div>";
        echo "<p>Run <a href='fix_donation_offers_table.php'>table fix script</a> first.</p>";
    }
    
    $offer_count = $conn->query("SELECT COUNT(*) FROM donation_offers")->fetchColumn();
    echo "<div style='color: blue;'>ℹ️ Current donation offers in database: $offer_count</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking donation_offers table: " . $e->getMessage() . "</div>";
}

// Process form submission if posted
if ($_POST && isset($_POST['submit_offer'])) {
    echo "<h2>5. Form Submission Debug</h2>";
    
    echo "<h3>Posted Data:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    $hospital_id = (int)$_POST['hospital'];
    $preferred_date = $_POST['preferredDate'];
    $preferred_time = $_POST['preferredTime'];
    $notes = trim($_POST['notes']);
    
    echo "<h3>Processed Data:</h3>";
    echo "<ul>";
    echo "<li>Hospital ID: $hospital_id</li>";
    echo "<li>Preferred Date: $preferred_date</li>";
    echo "<li>Preferred Time: $preferred_time</li>";
    echo "<li>Notes: $notes</li>";
    echo "<li>User ID: {$_SESSION['user_id']}</li>";
    echo "<li>Blood Type: {$user_data['blood_type']}</li>";
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
    
    if (!$user_data['is_eligible']) {
        $validation_errors[] = "You are not currently eligible to donate blood.";
    }
    
    if (!empty($validation_errors)) {
        echo "<h3>Validation Errors:</h3>";
        echo "<div style='color: red;'>";
        foreach ($validation_errors as $error) {
            echo "❌ $error<br>";
        }
        echo "</div>";
    } else {
        echo "<div style='color: green;'>✅ Validation passed</div>";
        
        // Try to insert
        try {
            // Check if hospital exists
            $hospital_check = $conn->prepare("SELECT id FROM hospitals WHERE id = ? AND is_verified = 1 AND is_active = 1");
            $hospital_check->execute([$hospital_id]);
            $hospital_exists = $hospital_check->fetch();
            
            if (!$hospital_exists) {
                echo "<div style='color: red;'>❌ Hospital ID $hospital_id not found or not active</div>";
            } else {
                echo "<div style='color: green;'>✅ Hospital verified</div>";
                
                $offer_id = 'OFF-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
                
                echo "<h3>Attempting Database Insert:</h3>";
                echo "<p>Offer ID: $offer_id</p>";
                
                $stmt = $conn->prepare("
                    INSERT INTO donation_offers (offer_id, donor_id, hospital_id, blood_type, preferred_date, 
                                               preferred_time, notes, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                
                $insert_data = [
                    $offer_id,
                    $_SESSION['user_id'],
                    $hospital_id,
                    $user_data['blood_type'],
                    $preferred_date,
                    $preferred_time,
                    $notes
                ];
                
                echo "<h4>Insert Data:</h4>";
                echo "<pre>";
                print_r($insert_data);
                echo "</pre>";
                
                $result = $stmt->execute($insert_data);
                
                if ($result) {
                    echo "<div style='color: green;'>✅ Donation offer inserted successfully!</div>";
                    echo "<p>Offer ID: $offer_id</p>";
                    
                    // Verify it was inserted
                    $verify_stmt = $conn->prepare("SELECT * FROM donation_offers WHERE offer_id = ?");
                    $verify_stmt->execute([$offer_id]);
                    $inserted_offer = $verify_stmt->fetch();
                    
                    if ($inserted_offer) {
                        echo "<div style='color: green;'>✅ Verified: Offer found in database</div>";
                        echo "<pre>";
                        print_r($inserted_offer);
                        echo "</pre>";
                    } else {
                        echo "<div style='color: red;'>❌ Offer not found in database after insert</div>";
                    }
                } else {
                    echo "<div style='color: red;'>❌ Insert failed</div>";
                    $errorInfo = $stmt->errorInfo();
                    echo "<p>Error: " . print_r($errorInfo, true) . "</p>";
                }
            }
            
        } catch (PDOException $e) {
            echo "<div style='color: red;'>❌ Database error: " . $e->getMessage() . "</div>";
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Show current offers for this donor
echo "<h2>6. Current Donation Offers for This Donor</h2>";
try {
    $offers_stmt = $conn->prepare("
        SELECT do.*, h.hospital_name
        FROM donation_offers do
        LEFT JOIN hospitals h ON do.hospital_id = h.id
        WHERE do.donor_id = ?
        ORDER BY do.created_at DESC
    ");
    $offers_stmt->execute([$_SESSION['user_id']]);
    $current_offers = $offers_stmt->fetchAll();
    
    if (count($current_offers) > 0) {
        echo "<div style='color: green;'>✅ Found " . count($current_offers) . " offer(s)</div>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Offer ID</th><th>Hospital</th><th>Blood Type</th><th>Date</th><th>Status</th><th>Created</th></tr>";
        foreach ($current_offers as $offer) {
            echo "<tr>";
            echo "<td>{$offer['offer_id']}</td>";
            echo "<td>{$offer['hospital_name']}</td>";
            echo "<td>{$offer['blood_type']}</td>";
            echo "<td>{$offer['preferred_date']}</td>";
            echo "<td>{$offer['status']}</td>";
            echo "<td>{$offer['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='color: blue;'>ℹ️ No donation offers found for this donor</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking current offers: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Donation Form</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .form-group { margin: 10px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        button { background: #dc2626; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #b91c1c; }
    </style>
</head>
<body>

<h2>7. Test Donation Form</h2>
<div class="form-container">
    <form method="POST">
        <div class="form-group">
            <label>Hospital:</label>
            <select name="hospital" required>
                <option value="">Select Hospital</option>
                <?php if (isset($hospitals)): ?>
                    <?php foreach ($hospitals as $hospital): ?>
                        <option value="<?php echo $hospital['id']; ?>">
                            <?php echo htmlspecialchars($hospital['hospital_name'] . ' - ' . $hospital['city']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
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
        
        <button type="submit" name="submit_offer">Submit Test Donation Offer</button>
    </form>
</div>

<h3>🔗 Quick Links</h3>
<p>
    <a href="setup_database.php" style="background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">Setup Database</a>
    <a href="fix_donation_offers_table.php" style="background: #16a085; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">Fix Table</a>
    <a href="frontend/dashboard/donor.php" style="background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">Donor Dashboard</a>
</p>

</body>
</html>