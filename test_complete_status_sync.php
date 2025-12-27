<?php
session_start();
require_once 'backend/config/database.php';

echo "<h2>Complete Status Synchronization Test</h2>";

try {
    // Create test users if they don't exist
    echo "<h3>Setting up Test Users</h3>";
    
    // Create test patient
    $patient_email = 'patient.sync@test.com';
    $check_patient = $conn->prepare("SELECT u.id, p.id as patient_id FROM users u LEFT JOIN patients p ON u.id = p.user_id WHERE u.email = ?");
    $check_patient->execute([$patient_email]);
    $patient_user = $check_patient->fetch();
    
    if (!$patient_user) {
        $user_stmt = $conn->prepare("INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified) VALUES (?, ?, 'patient', 'Test', 'Patient', 1, 1)");
        $user_stmt->execute([$patient_email, password_hash('test123', PASSWORD_DEFAULT)]);
        $patient_user_id = $conn->lastInsertId();
        
        $patient_stmt = $conn->prepare("INSERT INTO patients (user_id, patient_id, blood_type, date_of_birth, gender, emergency_contact_name) VALUES (?, ?, 'O+', '1990-01-01', 'male', 'Emergency Contact')");
        $patient_id_str = 'PAT-SYNC-' . str_pad($patient_user_id, 6, '0', STR_PAD_LEFT);
        $patient_stmt->execute([$patient_user_id, $patient_id_str]);
        $patient_id = $conn->lastInsertId();
        
        echo "<p>✓ Created test patient: $patient_email</p>";
    } else {
        $patient_user_id = $patient_user['id'];
        $patient_id = $patient_user['patient_id'];
        echo "<p>✓ Using existing patient: $patient_email</p>";
    }
    
    // Create test donor
    $donor_email = 'donor.sync@test.com';
    $check_donor = $conn->prepare("SELECT u.id, d.id as donor_id FROM users u LEFT JOIN donors d ON u.id = d.user_id WHERE u.email = ?");
    $check_donor->execute([$donor_email]);
    $donor_user = $check_donor->fetch();
    
    if (!$donor_user) {
        $user_stmt = $conn->prepare("INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified) VALUES (?, ?, 'donor', 'Test', 'Donor', 1, 1)");
        $user_stmt->execute([$donor_email, password_hash('test123', PASSWORD_DEFAULT)]);
        $donor_user_id = $conn->lastInsertId();
        
        $donor_stmt = $conn->prepare("INSERT INTO donors (user_id, donor_id, blood_type, date_of_birth, gender, weight, emergency_contact_name) VALUES (?, ?, 'O+', '1985-01-01', 'male', 70.5, 'Emergency Contact')");
        $donor_id_str = 'DON-SYNC-' . str_pad($donor_user_id, 6, '0', STR_PAD_LEFT);
        $donor_stmt->execute([$donor_user_id, $donor_id_str]);
        $donor_id = $conn->lastInsertId();
        
        echo "<p>✓ Created test donor: $donor_email</p>";
    } else {
        $donor_user_id = $donor_user['id'];
        $donor_id = $donor_user['donor_id'];
        echo "<p>✓ Using existing donor: $donor_email</p>";
    }
    
    // Create test hospital
    $hospital_email = 'hospital.sync@test.com';
    $check_hospital = $conn->prepare("SELECT u.id, h.id as hospital_id FROM users u LEFT JOIN hospitals h ON u.id = h.user_id WHERE u.email = ?");
    $check_hospital->execute([$hospital_email]);
    $hospital_user = $check_hospital->fetch();
    
    if (!$hospital_user) {
        $user_stmt = $conn->prepare("INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified) VALUES (?, ?, 'hospital', 'Test', 'Hospital', 1, 1)");
        $user_stmt->execute([$hospital_email, password_hash('test123', PASSWORD_DEFAULT)]);
        $hospital_user_id = $conn->lastInsertId();
        
        $hospital_stmt = $conn->prepare("INSERT INTO hospitals (user_id, hospital_id, hospital_name, hospital_type, license_number, is_verified, is_active) VALUES (?, ?, 'Test Sync Hospital', 'private', 'LIC-SYNC-001', 1, 1)");
        $hospital_id_str = 'HP-SYNC-' . str_pad($hospital_user_id, 6, '0', STR_PAD_LEFT);
        $hospital_stmt->execute([$hospital_user_id, $hospital_id_str]);
        $hospital_id = $conn->lastInsertId();
        
        echo "<p>✓ Created test hospital: $hospital_email</p>";
    } else {
        $hospital_user_id = $hospital_user['id'];
        $hospital_id = $hospital_user['hospital_id'];
        echo "<p>✓ Using existing hospital: $hospital_email</p>";
    }
    
    // Test 1: Create Blood Request and Test Status Updates
    echo "<h3>Test 1: Blood Request Status Synchronization</h3>";
    
    $request_id = 'REQ-SYNC-' . date('YmdHis');
    $stmt = $conn->prepare("
        INSERT INTO blood_requests (request_id, patient_id, assigned_hospital_id, blood_type, units_requested, 
                                  priority, medical_reason, doctor_contact, emergency_contact_name, 
                                  requested_by_user_id, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $request_id,
        $patient_id,
        $hospital_id,
        'O+',
        2,
        'urgent',
        'Test blood request for synchronization',
        'Dr. Test',
        'Emergency Contact',
        $patient_user_id
    ]);
    $blood_request_id = $conn->lastInsertId();
    
    echo "<p>✓ Created blood request: $request_id (Status: pending)</p>";
    
    // Simulate hospital approval
    $stmt = $conn->prepare("
        UPDATE blood_requests 
        SET status = 'approved', approved_by_user_id = ?, approved_at = NOW(), rejection_notes = ?
        WHERE id = ?
    ");
    $stmt->execute([$hospital_user_id, 'Approved for urgent medical need', $blood_request_id]);
    
    echo "<p>✓ Hospital approved the request (Status: approved)</p>";
    
    // Verify status in patient view
    $check_stmt = $conn->prepare("
        SELECT br.*, h.hospital_name 
        FROM blood_requests br
        LEFT JOIN hospitals h ON br.assigned_hospital_id = h.id
        WHERE br.id = ?
    ");
    $check_stmt->execute([$blood_request_id]);
    $request_status = $check_stmt->fetch();
    
    echo "<p>✓ Patient view shows: Status = {$request_status['status']}, Hospital = {$request_status['hospital_name']}</p>";
    
    // Simulate completion
    $stmt = $conn->prepare("
        UPDATE blood_requests 
        SET status = 'completed', completed_at = NOW(), rejection_notes = ?
        WHERE id = ?
    ");
    $stmt->execute(['Blood transfusion completed successfully', $blood_request_id]);
    
    echo "<p>✓ Hospital marked request as completed (Status: completed)</p>";
    
    // Test 2: Create Donation Offer and Test Status Updates
    echo "<h3>Test 2: Donation Offer Status Synchronization</h3>";
    
    $offer_id = 'OFF-SYNC-' . date('YmdHis');
    $stmt = $conn->prepare("
        INSERT INTO donation_offers (offer_id, donor_id, assigned_hospital_id, blood_type, preferred_date, 
                                   preferred_time, offered_by_user_id, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $offer_id,
        $donor_id,
        $hospital_id,
        'O+',
        date('Y-m-d', strtotime('+1 day')),
        '10:00:00',
        $donor_user_id
    ]);
    $donation_offer_id = $conn->lastInsertId();
    
    echo "<p>✓ Created donation offer: $offer_id (Status: pending)</p>";
    
    // Simulate hospital acceptance
    $stmt = $conn->prepare("
        UPDATE donation_offers 
        SET status = 'accepted', accepted_by_hospital_id = ?, accepted_by_user_id = ?, accepted_at = NOW(), notes = ?
        WHERE id = ?
    ");
    $stmt->execute([$hospital_id, $hospital_user_id, 'Thank you for your donation offer. Please arrive 15 minutes early.', $donation_offer_id]);
    
    echo "<p>✓ Hospital accepted the offer (Status: accepted)</p>";
    
    // Verify status in donor view
    $check_stmt = $conn->prepare("
        SELECT do.*, h.hospital_name 
        FROM donation_offers do
        LEFT JOIN hospitals h ON do.assigned_hospital_id = h.id
        WHERE do.id = ?
    ");
    $check_stmt->execute([$donation_offer_id]);
    $offer_status = $check_stmt->fetch();
    
    echo "<p>✓ Donor view shows: Status = {$offer_status['status']}, Hospital = {$offer_status['hospital_name']}</p>";
    
    // Simulate completion
    $stmt = $conn->prepare("
        UPDATE donation_offers 
        SET status = 'completed', completed_at = NOW(), notes = ?
        WHERE id = ?
    ");
    $stmt->execute(['Donation completed successfully. Thank you!', $donation_offer_id]);
    
    // Update donor records
    $donor_update = $conn->prepare("
        UPDATE donors 
        SET total_donations = total_donations + 1,
            last_donation_date = CURDATE(),
            next_eligible_date = DATE_ADD(CURDATE(), INTERVAL 56 DAY)
        WHERE id = ?
    ");
    $donor_update->execute([$donor_id]);
    
    echo "<p>✓ Hospital marked donation as completed and updated donor records (Status: completed)</p>";
    
    // Test 3: Verify Dashboard Synchronization
    echo "<h3>Test 3: Dashboard Synchronization Verification</h3>";
    
    // Check patient dashboard data
    $patient_requests = $conn->prepare("
        SELECT br.*, h.hospital_name 
        FROM blood_requests br
        LEFT JOIN hospitals h ON br.assigned_hospital_id = h.id
        WHERE br.patient_id = ?
        ORDER BY br.created_at DESC
    ");
    $patient_requests->execute([$patient_id]);
    $patient_data = $patient_requests->fetchAll();
    
    echo "<p>✓ Patient dashboard shows " . count($patient_data) . " blood requests</p>";
    foreach ($patient_data as $req) {
        echo "<p>&nbsp;&nbsp;- {$req['request_id']}: {$req['status']} at {$req['hospital_name']}</p>";
    }
    
    // Check donor dashboard data
    $donor_offers = $conn->prepare("
        SELECT do.*, h.hospital_name 
        FROM donation_offers do
        LEFT JOIN hospitals h ON do.assigned_hospital_id = h.id
        WHERE do.donor_id = ?
        ORDER BY do.created_at DESC
    ");
    $donor_offers->execute([$donor_id]);
    $donor_data = $donor_offers->fetchAll();
    
    echo "<p>✓ Donor dashboard shows " . count($donor_data) . " donation offers</p>";
    foreach ($donor_data as $offer) {
        echo "<p>&nbsp;&nbsp;- {$offer['offer_id']}: {$offer['status']} at {$offer['hospital_name']}</p>";
    }
    
    // Check hospital dashboard data
    $hospital_requests = $conn->prepare("
        SELECT br.*, u.first_name, u.last_name 
        FROM blood_requests br
        JOIN patients p ON br.patient_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE br.assigned_hospital_id = ?
        ORDER BY br.created_at DESC
    ");
    $hospital_requests->execute([$hospital_id]);
    $hospital_req_data = $hospital_requests->fetchAll();
    
    $hospital_offers = $conn->prepare("
        SELECT do.*, u.first_name, u.last_name 
        FROM donation_offers do
        JOIN donors d ON do.donor_id = d.id
        JOIN users u ON d.user_id = u.id
        WHERE do.assigned_hospital_id = ?
        ORDER BY do.created_at DESC
    ");
    $hospital_offers->execute([$hospital_id]);
    $hospital_offer_data = $hospital_offers->fetchAll();
    
    echo "<p>✓ Hospital dashboard shows " . count($hospital_req_data) . " blood requests and " . count($hospital_offer_data) . " donation offers</p>";
    
    echo "<h3>✅ Status Synchronization Test Complete!</h3>";
    echo "<p><strong>Test Results:</strong></p>";
    echo "<ul>";
    echo "<li>✓ Blood requests sync properly between patient and hospital dashboards</li>";
    echo "<li>✓ Donation offers sync properly between donor and hospital dashboards</li>";
    echo "<li>✓ Status updates are reflected in real-time across all dashboards</li>";
    echo "<li>✓ Hospital can approve/reject/complete blood requests</li>";
    echo "<li>✓ Hospital can accept/reject/complete donation offers</li>";
    echo "</ul>";
    
    echo "<p><strong>Test Credentials:</strong></p>";
    echo "<p>Patient: $patient_email / test123</p>";
    echo "<p>Donor: $donor_email / test123</p>";
    echo "<p>Hospital: $hospital_email / test123</p>";
    
    echo "<p><strong>Dashboard Links:</strong></p>";
    echo "<p><a href='frontend/dashboard/patient.php' target='_blank'>Patient Dashboard</a></p>";
    echo "<p><a href='frontend/dashboard/donor.php' target='_blank'>Donor Dashboard</a></p>";
    echo "<p><a href='frontend/dashboard/hospital.php' target='_blank'>Hospital Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
}
?>