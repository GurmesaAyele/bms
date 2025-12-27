<?php
/**
 * Add Test Data - Fixed for donation_offers table
 * Creates sample users, hospitals, patients, donors, blood requests, and donation offers
 */

require_once 'backend/config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Add Test Data - Fixed</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    .debug-query { background: #e8f4f8; padding: 10px; margin: 10px 0; border-left: 4px solid #3498db; }
</style></head><body>";

echo "<h1>Adding Test Data - Fixed for Your Database Structure</h1>";

try {
    // Step 1: Create test hospital user
    echo "<div class='section'>";
    echo "<h2>Step 1: Creating Test Hospital</h2>";
    
    // Check if test hospital already exists
    $check_hospital = $conn->prepare("SELECT id FROM users WHERE email = 'testhospital@bloodconnect.com'");
    $check_hospital->execute();
    
    if ($check_hospital->fetch()) {
        echo "<div class='info'>Test hospital user already exists, getting existing data...</div>";
        
        // Get existing hospital data
        $hospital_stmt = $conn->prepare("
            SELECT u.id as user_id, h.id as hospital_id 
            FROM users u 
            LEFT JOIN hospitals h ON u.id = h.user_id 
            WHERE u.email = 'testhospital@bloodconnect.com'
        ");
        $hospital_stmt->execute();
        $hospital_data = $hospital_stmt->fetch();
        
        if (!$hospital_data['hospital_id']) {
            echo "<div class='info'>Hospital record missing, creating it...</div>";
            $hospital_id = 'HOS-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $hospital_record_stmt = $conn->prepare("
                INSERT INTO hospitals (user_id, hospital_id, hospital_name, hospital_type, license_number, is_verified, is_active) 
                VALUES (?, ?, 'Test General Hospital', 'public', 'LIC-TEST-001', 1, 1)
            ");
            $hospital_record_stmt->execute([$hospital_data['user_id'], $hospital_id]);
            $hospital_data['hospital_id'] = $conn->lastInsertId();
            echo "<div class='success'>✓ Created hospital record</div>";
        }
    } else {
        // Create hospital user
        $hospital_user_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, phone, is_active, is_verified) 
            VALUES (?, ?, 'hospital', 'Test', 'General Hospital', '+1-555-0100', 1, 1)
        ");
        $hospital_user_stmt->execute(['testhospital@bloodconnect.com', password_hash('hospital123', PASSWORD_DEFAULT)]);
        $hospital_user_id = $conn->lastInsertId();
        
        // Create hospital record
        $hospital_id = 'HOS-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $hospital_stmt = $conn->prepare("
            INSERT INTO hospitals (user_id, hospital_id, hospital_name, hospital_type, license_number, is_verified, is_active) 
            VALUES (?, ?, 'Test General Hospital', 'public', 'LIC-TEST-001', 1, 1)
        ");
        $hospital_stmt->execute([$hospital_user_id, $hospital_id]);
        $hospital_id = $conn->lastInsertId();
        
        $hospital_data = ['user_id' => $hospital_user_id, 'hospital_id' => $hospital_id];
        
        echo "<div class='success'>✓ Created test hospital (Email: testhospital@bloodconnect.com, Password: hospital123)</div>";
    }
    
    echo "<div class='info'>Hospital User ID: {$hospital_data['user_id']}</div>";
    echo "<div class='info'>Hospital DB ID: {$hospital_data['hospital_id']}</div>";
    echo "</div>";
    
    // Step 2: Create test patient
    echo "<div class='section'>";
    echo "<h2>Step 2: Creating Test Patient</h2>";
    
    $check_patient = $conn->prepare("SELECT id FROM users WHERE email = 'testpatient@bloodconnect.com'");
    $check_patient->execute();
    
    if ($check_patient->fetch()) {
        echo "<div class='info'>Test patient already exists, getting existing data...</div>";
        
        $patient_stmt = $conn->prepare("
            SELECT u.id as user_id, p.id as patient_id 
            FROM users u 
            LEFT JOIN patients p ON u.id = p.user_id 
            WHERE u.email = 'testpatient@bloodconnect.com'
        ");
        $patient_stmt->execute();
        $patient_data = $patient_stmt->fetch();
        
        if (!$patient_data['patient_id']) {
            echo "<div class='info'>Patient record missing, creating it...</div>";
            $patient_id = 'PAT-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $patient_record_stmt = $conn->prepare("
                INSERT INTO patients (user_id, patient_id, blood_type, date_of_birth, gender, weight) 
                VALUES (?, ?, 'A+', '1990-01-01', 'male', 70.5)
            ");
            $patient_record_stmt->execute([$patient_data['user_id'], $patient_id]);
            $patient_data['patient_id'] = $conn->lastInsertId();
            echo "<div class='success'>✓ Created patient record</div>";
        }
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
    
    echo "<div class='info'>Patient User ID: {$patient_data['user_id']}</div>";
    echo "<div class='info'>Patient DB ID: {$patient_data['patient_id']}</div>";
    echo "</div>";
    
    // Step 3: Create test donor
    echo "<div class='section'>";
    echo "<h2>Step 3: Creating Test Donor</h2>";
    
    $check_donor = $conn->prepare("SELECT id FROM users WHERE email = 'testdonor@bloodconnect.com'");
    $check_donor->execute();
    
    if ($check_donor->fetch()) {
        echo "<div class='info'>Test donor already exists, getting existing data...</div>";
        
        $donor_stmt = $conn->prepare("
            SELECT u.id as user_id, d.id as donor_id 
            FROM users u 
            LEFT JOIN donors d ON u.id = d.user_id 
            WHERE u.email = 'testdonor@bloodconnect.com'
        ");
        $donor_stmt->execute();
        $donor_data = $donor_stmt->fetch();
        
        if (!$donor_data['donor_id']) {
            echo "<div class='info'>Donor record missing, creating it...</div>";
            $donor_id = 'DON-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $donor_record_stmt = $conn->prepare("
                INSERT INTO donors (user_id, donor_id, blood_type, date_of_birth, gender, weight, is_eligible, is_available) 
                VALUES (?, ?, 'O+', '1985-01-01', 'female', 65.0, 1, 1)
            ");
            $donor_record_stmt->execute([$donor_data['user_id'], $donor_id]);
            $donor_data['donor_id'] = $conn->lastInsertId();
            echo "<div class='success'>✓ Created donor record</div>";
        }
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
        $donor_db_id = $conn->lastInsertId();
        
        $donor_data = ['user_id' => $donor_user_id, 'donor_id' => $donor_db_id];
        
        echo "<div class='success'>✓ Created test donor (Email: testdonor@bloodconnect.com, Password: donor123)</div>";
    }
    
    echo "<div class='info'>Donor User ID: {$donor_data['user_id']}</div>";
    echo "<div class='info'>Donor DB ID: {$donor_data['donor_id']}</div>";
    echo "</div>";
    
    // Step 4: Create test blood requests
    echo "<div class='section'>";
    echo "<h2>Step 4: Creating Test Blood Requests</h2>";
    
    // Delete existing test requests first
    $conn->prepare("DELETE FROM blood_requests WHERE request_id LIKE 'REQ-TEST-%'")->execute();
    
    // Create 3 test blood requests with different statuses
    $requests_data = [
        ['status' => 'pending', 'blood_type' => 'A+', 'priority' => 'urgent', 'reason' => 'Emergency surgery preparation'],
        ['status' => 'pending', 'blood_type' => 'O+', 'priority' => 'routine', 'reason' => 'Scheduled surgery'],
        ['status' => 'approved', 'blood_type' => 'A+', 'priority' => 'emergency', 'reason' => 'Trauma patient care']
    ];
    
    foreach ($requests_data as $index => $req_data) {
        $request_id = 'REQ-TEST-' . date('Ymd') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        
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
        echo "<div class='debug-query'>assigned_hospital_id = {$hospital_data['hospital_id']}</div>";
    }
    echo "</div>";
    
    // Step 5: Create test donation offers
    echo "<div class='section'>";
    echo "<h2>Step 5: Creating Test Donation Offers</h2>";
    
    // Delete existing test offers first
    $conn->prepare("DELETE FROM donation_offers WHERE offer_id LIKE 'OFF-TEST-%'")->execute();
    
    // Create 3 test donation offers with different statuses
    $offers_data = [
        ['status' => 'pending', 'blood_type' => 'O+'],
        ['status' => 'pending', 'blood_type' => 'A+'],
        ['status' => 'accepted', 'blood_type' => 'O+']
    ];
    
    foreach ($offers_data as $index => $offer_data) {
        $offer_id = 'OFF-TEST-' . date('Ymd') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        
        $insert_offer = $conn->prepare("
            INSERT INTO donation_offers (offer_id, donor_id, hospital_id, blood_type, preferred_date, 
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
        
        echo "<div class='success'>✓ Created donation offer: {$offer_id} ({$offer_data['status']})</div>";
        echo "<div class='debug-query'>assigned_hospital_id = {$hospital_data['hospital_id']}</div>";
    }
    echo "</div>";
    
    // Step 6: Verify data was created correctly
    echo "<div class='section'>";
    echo "<h2>Step 6: Verification</h2>";
    
    // Check blood requests
    $req_check = $conn->prepare("SELECT COUNT(*) FROM blood_requests WHERE assigned_hospital_id = ?");
    $req_check->execute([$hospital_data['hospital_id']]);
    $req_count = $req_check->fetchColumn();
    echo "<div class='info'>Blood requests for hospital {$hospital_data['hospital_id']}: $req_count</div>";
    
    // Check donation offers
    $offer_check = $conn->prepare("SELECT COUNT(*) FROM donation_offers WHERE hospital_id = ?");
    $offer_check->execute([$hospital_data['hospital_id']]);
    $offer_count = $offer_check->fetchColumn();
    echo "<div class='info'>Donation offers for hospital {$hospital_data['hospital_id']}: $offer_count</div>";
    
    if ($req_count > 0 && $offer_count > 0) {
        echo "<div class='success'>✓ Test data created successfully!</div>";
    } else {
        echo "<div class='error'>✗ Something went wrong with data creation</div>";
    }
    echo "</div>";
    
    // Step 7: Test the hospital dashboard queries
    echo "<div class='section'>";
    echo "<h2>Step 7: Testing Hospital Dashboard Queries</h2>";
    
    // Test blood requests query
    echo "<h3>Blood Requests Query Test:</h3>";
    $test_req_query = "
        SELECT br.*, u.first_name, u.last_name, u.email, u.phone
        FROM blood_requests br
        LEFT JOIN users u ON br.requested_by_user_id = u.id
        WHERE br.assigned_hospital_id = ?
        ORDER BY br.created_at DESC
    ";
    echo "<div class='debug-query'>" . htmlspecialchars($test_req_query) . "</div>";
    
    $test_req_stmt = $conn->prepare($test_req_query);
    $test_req_stmt->execute([$hospital_data['hospital_id']]);
    $test_requests = $test_req_stmt->fetchAll();
    
    echo "<div class='info'>Found " . count($test_requests) . " blood requests</div>";
    if (!empty($test_requests)) {
        foreach ($test_requests as $req) {
            echo "<div class='success'>- {$req['request_id']}: {$req['blood_type']} ({$req['status']})</div>";
        }
    }
    
    // Test donation offers query
    echo "<h3>Donation Offers Query Test:</h3>";
    $test_offer_query = "
        SELECT do.*, u.first_name, u.last_name, u.email, u.phone
        FROM donation_offers do
        LEFT JOIN users u ON do.donor_id = u.id
        WHERE do.hospital_id = ?
        ORDER BY do.created_at DESC
    ";
    echo "<div class='debug-query'>" . htmlspecialchars($test_offer_query) . "</div>";
    
    $test_offer_stmt = $conn->prepare($test_offer_query);
    $test_offer_stmt->execute([$hospital_data['hospital_id']]);
    $test_offers = $test_offer_stmt->fetchAll();
    
    echo "<div class='info'>Found " . count($test_offers) . " donation offers</div>";
    if (!empty($test_offers)) {
        foreach ($test_offers as $offer) {
            echo "<div class='success'>- {$offer['offer_id']}: {$offer['blood_type']} ({$offer['status']})</div>";
        }
    }
    echo "</div>";
    
    // Step 8: Next steps
    echo "<div class='section'>";
    echo "<h2>Step 8: Next Steps</h2>";
    echo "<h3>Test Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Hospital:</strong> testhospital@bloodconnect.com / hospital123</li>";
    echo "<li><strong>Patient:</strong> testpatient@bloodconnect.com / patient123</li>";
    echo "<li><strong>Donor:</strong> testdonor@bloodconnect.com / donor123</li>";
    echo "</ul>";
    
    echo "<h3>Testing Instructions:</h3>";
    echo "<ol>";
    echo "<li>Open your hospital dashboard</li>";
    echo "<li>Login with hospital credentials</li>";
    echo "<li>You should now see the test blood requests and donation offers</li>";
    echo "<li>Try approving/rejecting requests and accepting/rejecting offers</li>";
    echo "</ol>";
    
    echo "<div class='success'><h3>✓ Test data setup completed!</h3></div>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Database Error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>SQL State: " . $e->getCode() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>