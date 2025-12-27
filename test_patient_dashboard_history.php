<?php
session_start();
require_once 'backend/config/database.php';

echo "<h2>Testing Patient Dashboard Blood Request History</h2>";

try {
    // Check if we have any patient users
    $stmt = $conn->query("
        SELECT u.id, u.email, u.user_type, p.id as patient_id 
        FROM users u 
        JOIN patients p ON u.id = p.user_id 
        WHERE u.user_type = 'patient' 
        LIMIT 1
    ");
    $patient_user = $stmt->fetch();
    
    if (!$patient_user) {
        echo "<p>No patient users found. Creating test patient...</p>";
        
        // Create test patient
        $user_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified)
            VALUES (?, ?, 'patient', 'Test', 'Patient', 1, 1)
        ");
        $test_email = 'patient.test@example.com';
        $test_password = password_hash('test123', PASSWORD_DEFAULT);
        $user_stmt->execute([$test_email, $test_password]);
        $user_id = $conn->lastInsertId();
        
        $patient_stmt = $conn->prepare("
            INSERT INTO patients (user_id, patient_id, blood_type, date_of_birth, gender, emergency_contact_name)
            VALUES (?, ?, 'O+', '1990-01-01', 'male', 'Test Emergency Contact')
        ");
        $patient_id_str = 'PAT-' . date('Y') . '-' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
        $patient_stmt->execute([$user_id, $patient_id_str]);
        $patient_id = $conn->lastInsertId();
        
        $patient_user = ['id' => $user_id, 'email' => $test_email, 'patient_id' => $patient_id];
        echo "<p>✓ Test patient created: {$test_email} / test123</p>";
    }
    
    // Check existing blood requests for this patient
    $requests_stmt = $conn->prepare("
        SELECT br.*, h.hospital_name, u.phone as hospital_phone
        FROM blood_requests br
        LEFT JOIN hospitals h ON br.assigned_hospital_id = h.id
        LEFT JOIN users u ON h.user_id = u.id
        WHERE br.patient_id = ?
        ORDER BY br.created_at DESC
    ");
    $requests_stmt->execute([$patient_user['patient_id']]);
    $existing_requests = $requests_stmt->fetchAll();
    
    echo "<p>Found " . count($existing_requests) . " existing blood requests for patient ID: {$patient_user['patient_id']}</p>";
    
    // Create some test blood requests if none exist
    if (count($existing_requests) < 3) {
        echo "<p>Creating test blood requests...</p>";
        
        $test_requests = [
            [
                'blood_type' => 'O+',
                'units' => 2,
                'priority' => 'urgent',
                'status' => 'pending',
                'medical_reason' => 'Emergency surgery requiring blood transfusion'
            ],
            [
                'blood_type' => 'O+',
                'units' => 1,
                'priority' => 'routine',
                'status' => 'approved',
                'medical_reason' => 'Routine medical procedure'
            ],
            [
                'blood_type' => 'O+',
                'units' => 3,
                'priority' => 'emergency',
                'status' => 'completed',
                'medical_reason' => 'Critical blood loss due to accident'
            ]
        ];
        
        foreach ($test_requests as $request_data) {
            $request_id = 'REQ-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("
                INSERT INTO blood_requests (request_id, patient_id, assigned_hospital_id, blood_type, units_requested, 
                                          priority, medical_reason, doctor_contact, emergency_contact_name, 
                                          requested_by_user_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $request_id,
                $patient_user['patient_id'],
                1, // Test hospital ID
                $request_data['blood_type'],
                $request_data['units'],
                $request_data['priority'],
                $request_data['medical_reason'],
                'Dr. Test Doctor - 555-1234',
                'Emergency Contact - 555-5678',
                $patient_user['id'],
                $request_data['status']
            ]);
            
            echo "<p>✓ Created test request: {$request_id} ({$request_data['status']})</p>";
        }
    }
    
    // Test the dashboard query
    echo "<h3>Testing Dashboard Query</h3>";
    
    $dashboard_stmt = $conn->prepare("
        SELECT br.*, h.hospital_name, u.phone as hospital_phone
        FROM blood_requests br
        LEFT JOIN hospitals h ON br.assigned_hospital_id = h.id
        LEFT JOIN users u ON h.user_id = u.id
        WHERE br.patient_id = ?
        ORDER BY br.created_at DESC
        LIMIT 10
    ");
    $dashboard_stmt->execute([$patient_user['patient_id']]);
    $dashboard_requests = $dashboard_stmt->fetchAll();
    
    echo "<p>Dashboard query returned " . count($dashboard_requests) . " requests</p>";
    
    if (!empty($dashboard_requests)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 20px;'>";
        echo "<tr><th>Request ID</th><th>Blood Type</th><th>Units</th><th>Priority</th><th>Status</th><th>Created</th></tr>";
        
        foreach ($dashboard_requests as $request) {
            echo "<tr>";
            echo "<td>{$request['request_id']}</td>";
            echo "<td>{$request['blood_type']}</td>";
            echo "<td>{$request['units_requested']}</td>";
            echo "<td>{$request['priority']}</td>";
            echo "<td><span style='padding: 2px 8px; border-radius: 4px; background: " . 
                 ($request['status'] == 'pending' ? '#fef3c7' : 
                  ($request['status'] == 'approved' ? '#d1fae5' : 
                   ($request['status'] == 'completed' ? '#a7f3d0' : '#fecaca'))) . 
                 ";'>{$request['status']}</span></td>";
            echo "<td>" . date('M j, Y g:i A', strtotime($request['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<br><h3>Test Complete!</h3>";
    echo "<p><strong>Patient Login:</strong> {$patient_user['email']} / test123</p>";
    echo "<p><a href='frontend/auth/login.php'>Login as Patient</a></p>";
    echo "<p><a href='frontend/dashboard/patient.php'>View Patient Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>