<?php
/**
 * Add Test Data to Database
 * Creates sample users, hospitals, patients, donors, blood requests, and blood offers
 */

require_once 'backend/config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Add Test Data</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
</style></head><body>";

echo "<h1>Adding Test Data to Database</h1>";

try {
    // Step 1: Create test hospital user
    echo "<div class='section'>";
    echo "<h2>Step 1: Creating Test Hospital</h2>";
    
    // Check if test hospital already exists
    $check_hospital = $conn->prepare("SELECT id FROM users WHERE email = 'testhospital@bloodconnect.com'");
    $check_hospital->execute();
    
    if ($check_hospital->fetch()) {
        echo "<div class='info'>Test hospital already exists, skipping creation.</div>";
        
        // Get existing hospital data
        $hospital_stmt = $conn->prepare("
            SELECT u.id as user_id, h.id as hospital_id 
            FROM users u 
            JOIN hospitals h ON u.id = h.user_id 
            WHERE u.email = 'testhospital@bloodconnect.com'
        ");
        $hospital_stmt->execute();
        $hospital_data = $hospital_stmt->fetch();
    } else {
        // Create hospital user
        $hospital_user_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, phone, is_active, is_verified) 
            VALUES (?, ?, 'hospital', 'Test', 'General Hospital', '+1-555-0100', 1, 1)
        ");
        $hospital_user_stmt->execute(['testhospital@bloodconnect.com', password_hash('hospital123', PASSWORD_DEFAULT)]);
        $hospital_user_id = $conn->lastInsertId();
        
        // Create hospital record
        $hospital_stmt = $conn->prepare("
            INSERT INTO hospitals (user_id, hospital_name, hospital_type, license_number, is_verified, is_active) 
            VALUES (?, 'Test General Hospital', 'public', 'LIC-TEST-001', 1, 1)
        ");
        $hospital_stmt->execute([$hospital_user_id]);
        $hospital_id = $conn->lastInsertId();
        
        $hospital_data = ['user_id' => $hospital_user_id, 'hospital_id' => $hospital_id];
        
        echo "<div class='success'>✓ Created test hospital (Email: testhospital@bloodconnect.com, Password: hospital123)</div>";
    }
    echo "</div>";
    
    // Step 2: Create test patient
    echo "<div class='section'>";
    echo "<h2>Step 2: Creating Test Patient</h2>";
    
    $check_patient = $conn->prepare("SELECT id FROM users WHERE email = 'testpatient@bloodconnect.com'");
    $check_patient->execute();
    
    if ($check_patient->fetch()) {
        echo "<div class='info'>Test patient already exists, skipping creation.</div>";
        
        $patient_stmt = $conn->prepare("
            SELECT u.id as user_id, p.id as patient_id 
            FROM users u 
            JOIN patients p ON u.id = p.user_id 
            WHERE u.email = 'testpatient@bloodconnect.com'
        ");
        $patient_stmt->execute();
        $patient_data = $patient_stmt->fetch();
    } else {
        // Create patient user
        $patient_user_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, phone, is_active, is_verified) 
            VALUES (?, ?, 'patient', 'John', 'Doe', '+1-555-0200', 1, 1)
        ");
        $patient_user_stmt->execute(['testpatient@bloodconnect.com', password_hash('patient123', PASSWORD_DEFAULT)]);
        $patient_user_id = $conn->lastInsertId();
        
        // Create patient record
        $patient_id = 'PAT-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $patient_stmt = $conn->prepare("
            INSERT INTO patients (user_id, patient_id, blood_type, date_of_birth, gender, weight) 
            VALUES (?, ?, 'A+', '1990-01-01', 'male', 70.5)
        ");
        $patient_stmt->execute([$patient_user_id, $patient_id]);
        $patient_db_id = $conn->lastInsertId();
        
        $patient_data = ['user_id' => $patient_user_id, 'patient_id' => $patient_db_id];
        
        echo "<div class='success'>✓ Created test patient (Email: testpatient@bloodconnect.com, Password: patient123)</div>";
    }
    echo "</div>";
    
    // Step 3: Create test donor
    echo "<div class='section'>";
    echo "<h2>Step 3: Creating Test Donor</h2>";
    
    $check_donor = $conn->prepare("SELECT id FROM users WHERE email = 'testdonor@bloodconnect.com'");
    $check_donor->execute();
    
    if ($check_donor->fetch()) {
        echo "<div class='info'>Test donor already exists, skipping creation.</div>";
        
        $donor_stmt = $conn->prepare("SELECT id as user_id FROM users WHERE email = 'testdonor@bloodconnect.com'");
        $donor_stmt->execute();
        $donor_data = $donor_stmt->fetch();
    } else {
        // Create donor user
        $donor_user_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, phone, is_active, is_verified) 
            VALUES (?, ?, 'donor', 'Jane', 'Smith', '+1-555-0300', 1, 1)
        ");
        $donor_user_stmt->execute(['testdonor@bloodconnect.com', password_hash('donor123', PASSWORD_DEFAULT)]);
        $donor_user_id = $conn->lastInsertId();
        
        // Create donor record
        $donor_id = 'DON-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $donor_stmt = $conn->prepare("
            INSERT INTO donors (user_id, donor_id, blood_type, date_of_birth, gender, weight, is_eligible, is_available) 
            VALUES (?, ?, 'O+', '1985-01-01', 'female', 65.0, 1, 1)
        ");
        $donor_stmt->execute([$donor_user_id, $donor_id]);
        
        $donor_data = ['user_id' => $donor_user_id];
        
        echo "<div class='success'>✓ Created test donor (Email: testdonor@bloodconnect.com, Password: donor123)</div>";
    }
    echo "</div>";
    
    // Step 4: Create test blood requests
    echo "<div class='section'>";
    echo "<h2>Step 4: Creating Test Blood Requests</h2>";
    
    // Create 3 test blood requests with different statuses
    $requests_data = [
        ['status' => 'pending', 'blood_type' => 'A+', 'priority' => 'urgent', 'reason' => 'Emergency surgery preparation'],
        ['status' => 'pending', 'blood_type' => 'O+', 'priority' => 'routine', 'reason' => 'Scheduled surgery'],
        ['status' => 'approved', 'blood_type' => 'A+', 'priority' => 'emergency', 'reason' => 'Trauma patient care']
    ];
    
    foreach ($requests_data as $index => $req_data) {
        $request_id = 'REQ-TEST-' . date('Ymd') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        
        // Check if request already exists
        $check_req = $conn->prepare("SELECT id FROM blood_requests WHERE request_id = ?");
        $check_req->execute([$request_id]);
        
        if (!$check_req->fetch()) {
            $insert_req = $conn->prepare("
                INSERT INTO blood_requests (request_id, patient_id, assigned_hospital_id, blood_type, units_requested, 
                                          priority, status, medical_reason, requested_by_user_id, created_at)
                VALUES (?, ?, ?, ?, 2, ?, ?, ?, ?, NOW())
            ");
            $insert_req->execute([
                $request_id,
                $patient_data['patient_id'],
                $hospital_data['hospital_id'],
                $req_data['blood_type'],
                $req_data['priority'],
                $req_data['status'],
                $req_data['reason'],
                $patient_data['user_id']
            ]);
            
            echo "<div class='success'>✓ Created blood request: {$request_id} ({$req_data['status']})</div>";
        } else {
            echo "<div class='info'>Blood request {$request_id} already exists</div>";
        }
    }
    echo "</div>";
    
    // Step 5: Create test blood offers
    echo "<div class='section'>";
    echo "<h2>Step 5: Creating Test Blood Offers</h2>";
    
    // Create 3 test blood offers with different statuses
    $offers_data = [
        ['status' => 'pending', 'blood_type' => 'O+'],
        ['status' => 'pending', 'blood_type' => 'A+'],
        ['status' => 'accepted', 'blood_type' => 'O+']
    ];
    
    foreach ($offers_data as $index => $offer_data) {
        $offer_id = 'OFF-TEST-' . date('Ymd') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        
        // Check if offer already exists
        $check_offer = $conn->prepare("SELECT id FROM blood_offers WHERE offer_id = ?");
        $check_offer->execute([$offer_id]);
        
        if (!$check_offer->fetch()) {
            $insert_offer = $conn->prepare("
                INSERT INTO blood_offers (offer_id, donor_id, hospital_id, blood_type, preferred_date, 
                                        preferred_time, status, notes, created_at)
                VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', ?, 'Test donation offer', NOW())
            ");
            $insert_offer->execute([
                $offer_id,
                $donor_data['user_id'],
                $hospital_data['hospital_id'],
                $offer_data['blood_type'],
                $offer_data['status']
            ]);
            
            echo "<div class='success'>✓ Created blood offer: {$offer_id} ({$offer_data['status']})</div>";
        } else {
            echo "<div class='info'>Blood offer {$offer_id} already exists</div>";
        }
    }
    echo "</div>";
    
    // Step 6: Summary
    echo "<div class='section'>";
    echo "<h2>Test Data Summary</h2>";
    
    // Count records
    $request_count = $conn->query("SELECT COUNT(*) FROM blood_requests")->fetchColumn();
    $offer_count = $conn->query("SELECT COUNT(*) FROM blood_offers")->fetchColumn();
    $hospital_count = $conn->query("SELECT COUNT(*) FROM hospitals")->fetchColumn();
    $patient_count = $conn->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $donor_count = $conn->query("SELECT COUNT(*) FROM donors")->fetchColumn();
    
    echo "<ul>";
    echo "<li><strong>Hospitals:</strong> {$hospital_count}</li>";
    echo "<li><strong>Patients:</strong> {$patient_count}</li>";
    echo "<li><strong>Donors:</strong> {$donor_count}</li>";
    echo "<li><strong>Blood Requests:</strong> {$request_count}</li>";
    echo "<li><strong>Blood Offers:</strong> {$offer_count}</li>";
    echo "</ul>";
    
    echo "<h3>Test Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Hospital:</strong> testhospital@bloodconnect.com / hospital123</li>";
    echo "<li><strong>Patient:</strong> testpatient@bloodconnect.com / patient123</li>";
    echo "<li><strong>Donor:</strong> testdonor@bloodconnect.com / donor123</li>";
    echo "</ul>";
    
    echo "<div class='success'><h3>✓ Test data creation completed!</h3></div>";
    echo "</div>";
    
    // Step 7: Next steps
    echo "<div class='section'>";
    echo "<h2>Next Steps</h2>";
    echo "<ol>";
    echo "<li>Open <a href='hospital_dashboard_fixed.php' target='_blank'>hospital_dashboard_fixed.php</a> to test the hospital dashboard</li>";
    echo "<li>Login with hospital credentials: testhospital@bloodconnect.com / hospital123</li>";
    echo "<li>You should see the test blood requests and offers</li>";
    echo "<li>Try approving/rejecting requests and accepting/rejecting offers</li>";
    echo "<li>Check patient and donor dashboards to see status updates</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Database Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>