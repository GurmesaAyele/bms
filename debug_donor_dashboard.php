<?php
/**
 * Debug version of donor dashboard to identify form submission issues
 */

session_start();
require_once 'backend/config/database.php';

// Debug: Show all POST data
if ($_POST) {
    echo "<div style='position: fixed; top: 0; left: 0; width: 100%; background: #000; color: #fff; padding: 10px; z-index: 9999;'>";
    echo "<h3>DEBUG: Form Submitted!</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    echo "<p>Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
    echo "<p>Session User Type: " . ($_SESSION['user_type'] ?? 'NOT SET') . "</p>";
    echo "</div>";
    echo "<br><br><br><br><br><br><br><br>"; // Space for debug info
}

// Check if user is logged in and is a donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    header('Location: ../auth/login.php');
    exit();
}

// Get user and donor data
try {
    $stmt = $conn->prepare("
        SELECT u.*, d.donor_id, d.blood_type, d.date_of_birth, d.gender, d.weight, d.height,
               d.is_eligible, d.last_donation_date, d.next_eligible_date, d.total_donations,
               d.health_status, d.is_available, d.preferred_donation_time,
               d.emergency_contact_name, d.emergency_contact_phone, d.known_allergies, d.medical_conditions
        FROM users u 
        JOIN donors d ON u.id = d.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        header('Location: ../auth/login.php');
        exit();
    }
} catch (PDOException $e) {
    die("Error loading user data: " . $e->getMessage());
}

// Handle donation offer submission
if ($_POST && isset($_POST['submit_offer'])) {
    echo "<div style='background: #ffffcc; padding: 10px; margin: 10px 0; border: 1px solid #ccc;'>";
    echo "<h3>🐛 DEBUG: Processing Form Submission</h3>";
    
    $hospital_id = (int)$_POST['hospital'];
    $preferred_date = $_POST['preferredDate'];
    $preferred_time = $_POST['preferredTime'];
    $notes = trim($_POST['notes']);
    
    echo "<p><strong>Extracted Data:</strong></p>";
    echo "<ul>";
    echo "<li>Hospital ID: $hospital_id</li>";
    echo "<li>Preferred Date: $preferred_date</li>";
    echo "<li>Preferred Time: $preferred_time</li>";
    echo "<li>Notes: $notes</li>";
    echo "<li>User ID: {$_SESSION['user_id']}</li>";
    echo "<li>Blood Type: {$user_data['blood_type']}</li>";
    echo "<li>Is Eligible: " . ($user_data['is_eligible'] ? 'Yes' : 'No') . "</li>";
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
        echo "<p><strong>❌ Validation Errors:</strong></p>";
        echo "<ul>";
        foreach ($validation_errors as $error) {
            echo "<li style='color: red;'>$error</li>";
        }
        echo "</ul>";
        $error_message = implode("<br>", $validation_errors);
    } else {
        echo "<p><strong>✅ Validation Passed</strong></p>";
        
        try {
            // Check if hospital exists and is active
            $hospital_check = $conn->prepare("SELECT id FROM hospitals WHERE id = ? AND is_verified = 1 AND is_active = 1");
            $hospital_check->execute([$hospital_id]);
            $hospital_exists = $hospital_check->fetch();
            
            if (!$hospital_exists) {
                throw new Exception("Selected hospital is not available.");
            }
            
            echo "<p><strong>✅ Hospital Verified (ID: $hospital_id)</strong></p>";
            
            $offer_id = 'OFF-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            
            echo "<p><strong>Generated Offer ID:</strong> $offer_id</p>";
            
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
            
            echo "<p><strong>Insert Data:</strong></p>";
            echo "<pre>";
            print_r($insert_data);
            echo "</pre>";
            
            $result = $stmt->execute($insert_data);
            
            if ($result) {
                echo "<p><strong>✅ SUCCESS! Database insertion successful</strong></p>";
                $success_message = "Donation offer submitted successfully! Offer ID: " . $offer_id;
                
                // Verify it was inserted
                $verify = $conn->prepare("SELECT * FROM donation_offers WHERE offer_id = ?");
                $verify->execute([$offer_id]);
                $inserted = $verify->fetch();
                
                if ($inserted) {
                    echo "<p><strong>✅ Verified in database</strong></p>";
                    echo "<pre>";
                    print_r($inserted);
                    echo "</pre>";
                } else {
                    echo "<p><strong>❌ Not found in database after insert</strong></p>";
                }
                
                // Don't redirect in debug mode so we can see the results
                // header("Location: " . $_SERVER['PHP_SELF'] . "?success=1&offer_id=" . $offer_id);
                // exit();
            } else {
                echo "<p><strong>❌ Database insertion failed</strong></p>";
                $errorInfo = $stmt->errorInfo();
                echo "<pre>";
                print_r($errorInfo);
                echo "</pre>";
                $error_message = "Failed to submit offer. Database error occurred.";
            }
            
        } catch (PDOException $e) {
            echo "<p><strong>❌ PDO Exception:</strong> " . $e->getMessage() . "</p>";
            $error_message = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            echo "<p><strong>❌ Exception:</strong> " . $e->getMessage() . "</p>";
            $error_message = $e->getMessage();
        }
    }
    
    echo "</div>";
}

// Check for success message from redirect
if (isset($_GET['success']) && isset($_GET['offer_id'])) {
    $success_message = "Donation offer submitted successfully! Offer ID: " . htmlspecialchars($_GET['offer_id']);
}

// Get nearby hospitals
try {
    $hospitals_stmt = $conn->prepare("
        SELECT h.id, h.hospital_name, h.hospital_type, h.emergency_services, h.is_24_7,
               u.phone, u.address, u.city
        FROM hospitals h
        JOIN users u ON h.user_id = u.id
        WHERE h.is_verified = 1 AND h.is_active = 1 AND h.has_blood_bank = 1
        ORDER BY h.hospital_name
        LIMIT 10
    ");
    $hospitals_stmt->execute();
    $hospitals = $hospitals_stmt->fetchAll();
} catch (PDOException $e) {
    $hospitals = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEBUG: Donor Dashboard - BloodConnect</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .alert { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .alert-success { background: #d5f4e6; color: #16a085; }
        .alert-danger { background: #fadbd8; color: #e74c3c; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        button { background: #dc2626; color: white; padding: 12px 24px; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #b91c1c; }
        button:disabled { background: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐛 DEBUG: Donor Dashboard</h1>
        
        <div style="background: #e8f5e5; padding: 10px; border-radius: 5px; margin: 10px 0;">
            <h3>Debug Info:</h3>
            <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
            <p><strong>User Type:</strong> <?php echo $_SESSION['user_type']; ?></p>
            <p><strong>Donor Name:</strong> <?php echo $user_data['first_name'] . ' ' . $user_data['last_name']; ?></p>
            <p><strong>Blood Type:</strong> <?php echo $user_data['blood_type']; ?></p>
            <p><strong>Eligible:</strong> <?php echo $user_data['is_eligible'] ? 'Yes' : 'No'; ?></p>
            <p><strong>Available Hospitals:</strong> <?php echo count($hospitals); ?></p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <h2>Submit Donation Offer</h2>
        <form method="POST" id="donationOfferForm">
            <div class="form-group">
                <label>Blood Type</label>
                <input type="text" value="<?php echo htmlspecialchars($user_data['blood_type']); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Preferred Hospital <span style="color: red;">*</span></label>
                <select name="hospital" required>
                    <option value="">Select Hospital</option>
                    <?php foreach ($hospitals as $hospital): ?>
                        <option value="<?php echo $hospital['id']; ?>">
                            <?php echo htmlspecialchars($hospital['hospital_name'] . ' - ' . $hospital['city']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($hospitals)): ?>
                    <small style="color: red;">No hospitals available. Please contact admin.</small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Preferred Date <span style="color: red;">*</span></label>
                <input type="date" name="preferredDate" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Preferred Time <span style="color: red;">*</span></label>
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
                <label>Additional Notes</label>
                <textarea name="notes" rows="3" placeholder="Any special requirements or notes..."></textarea>
            </div>
            
            <button type="submit" name="submit_offer" <?php echo !$user_data['is_eligible'] ? 'disabled' : ''; ?>>
                Submit Donation Offer (DEBUG)
            </button>
            <?php if (!$user_data['is_eligible']): ?>
                <small style="color: red;">You are currently not eligible to donate.</small>
            <?php endif; ?>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <h3>🔗 Links:</h3>
            <p><a href="donor.php">Go to Real Donor Dashboard</a></p>
            <p><a href="../auth/logout.php">Logout</a></p>
        </div>
    </div>

    <script>
        console.log('DEBUG: JavaScript loaded');
        
        // Remove the JavaScript validation that might be preventing submission
        document.getElementById('donationOfferForm').addEventListener('submit', function(e) {
            console.log('DEBUG: Form submit event triggered');
            
            const hospital = document.querySelector('select[name="hospital"]').value;
            const date = document.querySelector('input[name="preferredDate"]').value;
            const time = document.querySelector('select[name="preferredTime"]').value;
            
            console.log('DEBUG: Form values:', {
                hospital: hospital,
                date: date,
                time: time
            });
            
            // Don't prevent submission in debug mode - let it go through
            // Show loading state
            const submitBtn = document.querySelector('button[name="submit_offer"]');
            submitBtn.innerHTML = 'Submitting... (DEBUG)';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>