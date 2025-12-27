<?php
session_start();
require_once 'backend/config/database.php';

echo "<h2>Hospital Dashboard Complete Test</h2>";

try {
    // Check if we have hospital users
    $hospital_stmt = $conn->query("
        SELECT u.id, u.email, h.id as hospital_id, h.hospital_name 
        FROM users u 
        JOIN hospitals h ON u.id = h.user_id 
        WHERE u.user_type = 'hospital' 
        LIMIT 1
    ");
    $hospital_user = $hospital_stmt->fetch();
    
    if (!$hospital_user) {
        echo "<p>No hospital users found. Creating test hospital...</p>";
        
        // Create test hospital user
        $user_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified)
            VALUES (?, ?, 'hospital', 'Test', 'Hospital', 1, 1)
        ");
        $test_email = 'hospital@test.com';
        $test_password = password_hash('test123', PASSWORD_DEFAULT);
        $user_stmt->execute([$test_email, $test_password]);
        $user_id = $conn->lastInsertId();
        
        // Create hospital record
        $hospital_stmt = $conn->prepare("
            INSERT INTO hospitals (user_id, hospital_id, hospital_name, hospital_type, license_number, is_verified, is_active)
            VALUES (?, ?, 'Test Hospital', 'private', 'LIC-TEST-001', 1, 1)
        ");
        $hospital_id_str = 'HP-' . date('Y') . '-' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
        $hospital_stmt->execute([$user_id, $hospital_id_str]);
        $hospital_db_id = $conn->lastInsertId();
        
        echo "<p>✓ Test hospital created: $test_email / test123</p>";
        $hospital_user = ['id' => $user_id, 'email' => $test_email, 'hospital_id' => $hospital_db_id, 'hospital_name' => 'Test Hospital'];
    }
    
    // Simulate hospital login
    $_SESSION['user_id'] = $hospital_user['id'];
    $_SESSION['user_type'] = 'hospital';
    
    echo "<p>✓ Simulated login as: {$hospital_user['email']} (Hospital ID: {$hospital_user['hospital_id']})</p>";
    
    // Create test blood requests for this hospital
    echo "<h3>Creating Test Blood Requests</h3>";
    
    // Get or create a test patient
    $patient_stmt = $conn->query("SELECT p.id, p.user_id FROM patients p LIMIT 1");
    $patient = $patient_stmt->fetch();
    
    if (!$patient) {
        // Create test patient
        $user_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified)
            VALUES (?, ?, 'patient', 'Test', 'Patient', 1, 1)
        ");
        $patient_email = 'patient@test.com';
        $patient_password = password_hash('test123', PASSWORD_DEFAULT);
        $user_stmt->execute([$patient_email, $patient_password]);
        $patient_user_id = $conn->lastInsertId();
        
        $patient_stmt = $conn->prepare("
            INSERT INTO patients (user_id, patient_id, blood_type, date_of_birth, gender, emergency_contact_name)
            VALUES (?, ?, 'O+', '1990-01-01', 'male', 'Test Emergency Contact')
        ");
        $patient_id_str = 'PAT-' . date('Y') . '-' . str_pad($patient_user_id, 6, '0', STR_PAD_LEFT);
        $patient_stmt->execute([$patient_user_id, $patient_id_str]);
        $patient_id = $conn->lastInsertId();
        
        echo "<p>✓ Test patient created</p>";
    } else {
        $patient_id = $patient['id'];
        $patient_user_id = $patient['user_id'];
        echo "<p>✓ Using existing patient (ID: $patient_id)</p>";
    }
    
    // Create test blood requests
    $test_requests = [
        ['blood_type' => 'O+', 'units' => 2, 'priority' => 'urgent', 'reason' => 'Emergency surgery requiring blood transfusion'],
        ['blood_type' => 'A+', 'units' => 1, 'priority' => 'routine', 'reason' => 'Scheduled surgery preparation'],
        ['blood_type' => 'B-', 'units' => 3, 'priority' => 'emergency', 'reason' => 'Critical blood loss from accident']
    ];
    
    foreach ($test_requests as $req) {
        $request_id = 'REQ-TEST-' . date('YmdHis') . '-' . rand(100, 999);
        $stmt = $conn->prepare("
            INSERT INTO blood_requests (request_id, patient_id, assigned_hospital_id, blood_type, units_requested, 
                                      priority, medical_reason, doctor_contact, emergency_contact_name, 
                                      requested_by_user_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $result = $stmt->execute([
            $request_id,
            $patient_id,
            $hospital_user['hospital_id'],
            $req['blood_type'],
            $req['units'],
            $req['priority'],
            $req['reason'],
            'Dr. Test Doctor',
            'Emergency Contact',
            $patient_user_id
        ]);
        
        if ($result) {
            echo "<p>✓ Created blood request: {$req['blood_type']} - {$req['priority']}</p>";
        }
    }
    
    // Create test donation offers
    echo "<h3>Creating Test Donation Offers</h3>";
    
    // Get or create a test donor
    $donor_stmt = $conn->query("SELECT d.id, d.user_id FROM donors d LIMIT 1");
    $donor = $donor_stmt->fetch();
    
    if (!$donor) {
        // Create test donor
        $user_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified)
            VALUES (?, ?, 'donor', 'Test', 'Donor', 1, 1)
        ");
        $donor_email = 'donor@test.com';
        $donor_password = password_hash('test123', PASSWORD_DEFAULT);
        $user_stmt->execute([$donor_email, $donor_password]);
        $donor_user_id = $conn->lastInsertId();
        
        $donor_stmt = $conn->prepare("
            INSERT INTO donors (user_id, donor_id, blood_type, date_of_birth, gender, weight, emergency_contact_name)
            VALUES (?, ?, 'O+', '1985-01-01', 'male', 70.5, 'Test Emergency Contact')
        ");
        $donor_id_str = 'DON-' . date('Y') . '-' . str_pad($donor_user_id, 6, '0', STR_PAD_LEFT);
        $donor_stmt->execute([$donor_user_id, $donor_id_str]);
        $donor_id = $conn->lastInsertId();
        
        echo "<p>✓ Test donor created</p>";
    } else {
        $donor_id = $donor['id'];
        $donor_user_id = $donor['user_id'];
        echo "<p>✓ Using existing donor (ID: $donor_id)</p>";
    }
    
    // Create test donation offers
    $test_offers = [
        ['blood_type' => 'O+', 'date' => date('Y-m-d', strtotime('+1 day')), 'time' => '10:00:00'],
        ['blood_type' => 'O+', 'date' => date('Y-m-d', strtotime('+2 days')), 'time' => '14:00:00']
    ];
    
    foreach ($test_offers as $offer) {
        $offer_id = 'OFF-TEST-' . date('YmdHis') . '-' . rand(100, 999);
        $stmt = $conn->prepare("
            INSERT INTO donation_offers (offer_id, donor_id, assigned_hospital_id, blood_type, preferred_date, 
                                       preferred_time, offered_by_user_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $result = $stmt->execute([
            $offer_id,
            $donor_id,
            $hospital_user['hospital_id'],
            $offer['blood_type'],
            $offer['date'],
            $offer['time'],
            $donor_user_id
        ]);
        
        if ($result) {
            echo "<p>✓ Created donation offer: {$offer['blood_type']} on {$offer['date']}</p>";
        }
    }
    
    // Test hospital dashboard queries
    echo "<h3>Testing Hospital Dashboard Queries</h3>";
    
    // Test blood requests query
    $requests_stmt = $conn->prepare("
        SELECT br.*, u.first_name, u.last_name, u.email, u.phone, p.patient_id, p.blood_type as patient_blood_type
        FROM blood_requests br
        JOIN patients p ON br.patient_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE br.assigned_hospital_id = ?
        ORDER BY br.created_at DESC
        LIMIT 10
    ");
    $requests_stmt->execute([$hospital_user['hospital_id']]);
    $blood_requests = $requests_stmt->fetchAll();
    
    echo "<p>✓ Found " . count($blood_requests) . " blood requests for hospital</p>";
    
    // Test donation offers query
    $offers_stmt = $conn->prepare("
        SELECT do.*, u.first_name, u.last_name, u.email, u.phone, 
               d.donor_id, d.blood_type as donor_blood_type
        FROM donation_offers do
        JOIN donors d ON do.donor_id = d.id
        JOIN users u ON d.user_id = u.id
        WHERE do.assigned_hospital_id = ?
        ORDER BY do.created_at DESC
        LIMIT 10
    ");
    $offers_stmt->execute([$hospital_user['hospital_id']]);
    $donation_offers = $offers_stmt->fetchAll();
    
    echo "<p>✓ Found " . count($donation_offers) . " donation offers for hospital</p>";
    
    // Test statistics query
    $stats_stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM blood_requests WHERE assigned_hospital_id = ?) as total_requests,
            (SELECT COUNT(*) FROM blood_requests WHERE assigned_hospital_id = ? AND status = 'pending') as pending_requests,
            (SELECT COUNT(*) FROM blood_requests WHERE assigned_hospital_id = ? AND status = 'approved') as approved_requests,
            (SELECT COUNT(*) FROM blood_requests WHERE assigned_hospital_id = ? AND status = 'completed') as completed_requests,
            (SELECT COUNT(*) FROM donation_offers WHERE assigned_hospital_id = ?) as total_offers,
            (SELECT COUNT(*) FROM donation_offers WHERE assigned_hospital_id = ? AND status = 'pending') as pending_offers
    ");
    $stats_stmt->execute([$hospital_user['hospital_id'], $hospital_user['hospital_id'], $hospital_user['hospital_id'], $hospital_user['hospital_id'], $hospital_user['hospital_id'], $hospital_user['hospital_id']]);
    $stats = $stats_stmt->fetch();
    
    echo "<p>✓ Statistics: {$stats['pending_requests']} pending requests, {$stats['pending_offers']} pending offers</p>";
    
    echo "<h3>✅ Hospital Dashboard Test Complete!</h3>";
    echo "<p><strong>Test Credentials:</strong></p>";
    echo "<p>Hospital: {$hospital_user['email']} / test123</p>";
    echo "<p><a href='frontend/dashboard/hospital.php' target='_blank'>Open Hospital Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
}
?>