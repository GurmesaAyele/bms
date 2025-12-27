<?php
session_start();
require_once 'backend/config/database.php';

echo "<h1>Complete Blood Request System Test</h1>";

// Step 1: Check available patient accounts
echo "<h2>Step 1: Available Patient Accounts</h2>";

try {
    $stmt = $conn->query("
        SELECT u.id, u.email, u.first_name, u.last_name, p.id as patient_id, p.patient_id as patient_code
        FROM users u 
        JOIN patients p ON u.id = p.user_id 
        WHERE u.user_type = 'patient' AND u.is_active = 1
        ORDER BY u.id
    ");
    $patients = $stmt->fetchAll();
    
    if (empty($patients)) {
        echo "<p>❌ No patient accounts found. Creating test patient...</p>";
        
        // Create test patient
        $user_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified)
            VALUES (?, ?, 'patient', 'Test', 'Patient', 1, 1)
        ");
        $test_email = 'patient@bloodconnect.com';
        $test_password = password_hash('patient123', PASSWORD_DEFAULT);
        $user_stmt->execute([$test_email, $test_password]);
        $user_id = $conn->lastInsertId();
        
        // Create patient record
        $patient_stmt = $conn->prepare("
            INSERT INTO patients (user_id, patient_id, blood_type, date_of_birth, gender, emergency_contact_name)
            VALUES (?, ?, 'O+', '1990-01-01', 'male', 'Emergency Contact')
        ");
        $patient_id_str = 'PAT-' . date('Y') . '-' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
        $patient_stmt->execute([$user_id, $patient_id_str]);
        
        echo "<p>✅ Test patient created: $test_email / patient123</p>";
        
        // Refresh the list
        $stmt->execute();
        $patients = $stmt->fetchAll();
    }
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr><th>User ID</th><th>Email</th><th>Name</th><th>Patient ID</th><th>Test Login</th></tr>";
    
    foreach ($patients as $patient) {
        echo "<tr>";
        echo "<td>{$patient['id']}</td>";
        echo "<td>{$patient['email']}</td>";
        echo "<td>{$patient['first_name']} {$patient['last_name']}</td>";
        echo "<td>{$patient['patient_code']}</td>";
        echo "<td><a href='?test_login={$patient['id']}' style='color: blue;'>Test Login</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Step 2: Handle test login
if (isset($_GET['test_login'])) {
    $user_id = (int)$_GET['test_login'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'patient'");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            echo "<div style='background: #e6ffe6; border: 2px solid green; padding: 10px; margin: 10px 0;'>";
            echo "<h3>✅ Test Login Successful!</h3>";
            echo "<p>Logged in as: {$user['email']} ({$user['first_name']} {$user['last_name']})</p>";
            echo "<p>User ID: {$user['id']}</p>";
            echo "<p>User Type: {$user['user_type']}</p>";
            echo "</div>";
        }
    } catch (PDOException $e) {
        echo "<p>❌ Login error: " . $e->getMessage() . "</p>";
    }
}

// Step 3: Show current session status
echo "<h2>Step 2: Current Session Status</h2>";

if (isset($_SESSION['user_id'])) {
    echo "<div style='background: #e6ffe6; border: 1px solid green; padding: 10px; margin: 10px 0;'>";
    echo "<p>✅ <strong>Logged In</strong></p>";
    echo "<p>User ID: {$_SESSION['user_id']}</p>";
    echo "<p>Email: {$_SESSION['user_email']}</p>";
    echo "<p>Type: {$_SESSION['user_type']}</p>";
    echo "<p>Name: {$_SESSION['user_name']}</p>";
    echo "</div>";
} else {
    echo "<div style='background: #ffe6e6; border: 1px solid red; padding: 10px; margin: 10px 0;'>";
    echo "<p>❌ <strong>Not Logged In</strong></p>";
    echo "<p>Please click 'Test Login' above for one of the patient accounts</p>";
    echo "</div>";
}

// Step 4: Test blood request submission (only if logged in)
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'patient') {
    echo "<h2>Step 3: Test Blood Request Submission</h2>";
    
    // Simulate form submission
    if (isset($_GET['test_submit'])) {
        echo "<p>🔄 Testing blood request submission...</p>";
        
        // Get patient ID
        try {
            $patient_stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
            $patient_stmt->execute([$_SESSION['user_id']]);
            $patient_record = $patient_stmt->fetch();
            
            if ($patient_record) {
                $patient_id = $patient_record['id'];
                echo "<p>✅ Patient record found: ID {$patient_id}</p>";
                
                // Generate request ID
                $request_id = 'REQ-TEST-' . date('YmdHis') . '-' . rand(100, 999);
                
                // Insert blood request
                $stmt = $conn->prepare("
                    INSERT INTO blood_requests (
                        request_id, patient_id, assigned_hospital_id, blood_type, units_requested, 
                        priority, medical_reason, doctor_contact, emergency_contact_name, 
                        requested_by_user_id, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                
                $result = $stmt->execute([
                    $request_id,
                    $patient_id,
                    null, // No specific hospital
                    'O+',
                    2,
                    'urgent',
                    'Test blood request for emergency surgery',
                    'Dr. Test Doctor - Emergency Dept',
                    'Emergency Contact - 555-1234',
                    $_SESSION['user_id']
                ]);
                
                if ($result) {
                    echo "<div style='background: #e6ffe6; border: 2px solid green; padding: 15px; margin: 10px 0;'>";
                    echo "<h3>🎉 SUCCESS! Blood Request Submitted</h3>";
                    echo "<p><strong>Request ID:</strong> $request_id</p>";
                    echo "<p><strong>Status:</strong> Pending</p>";
                    echo "<p><strong>Blood Type:</strong> O+</p>";
                    echo "<p><strong>Units:</strong> 2</p>";
                    echo "<p><strong>Priority:</strong> Urgent</p>";
                    echo "</div>";
                    
                    // Verify in database
                    $verify_stmt = $conn->prepare("SELECT * FROM blood_requests WHERE request_id = ?");
                    $verify_stmt->execute([$request_id]);
                    $record = $verify_stmt->fetch();
                    
                    if ($record) {
                        echo "<p>✅ Record verified in database</p>";
                        echo "<details><summary>View Database Record</summary>";
                        echo "<pre>" . print_r($record, true) . "</pre>";
                        echo "</details>";
                    }
                    
                } else {
                    echo "<p>❌ Failed to insert blood request</p>";
                    echo "<p>Error: " . print_r($stmt->errorInfo(), true) . "</p>";
                }
                
            } else {
                echo "<p>❌ Patient record not found</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p><a href='?test_submit=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Test Blood Request Submission</a></p>";
    }
}

// Step 5: Instructions
echo "<h2>Step 4: Next Steps</h2>";
echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 10px 0;'>";
echo "<h3>To test the actual blood request form:</h3>";
echo "<ol>";
echo "<li><strong>Login:</strong> Go to <a href='frontend/auth/login.php' target='_blank'>Login Page</a></li>";
echo "<li><strong>Use credentials:</strong> patient@bloodconnect.com / patient123 (or any patient from the table above)</li>";
echo "<li><strong>Submit request:</strong> Go to <a href='frontend/request-blood.php' target='_blank'>Blood Request Form</a></li>";
echo "<li><strong>Fill form:</strong> Complete all required fields and submit</li>";
echo "</ol>";

echo "<h3>Available Test Accounts:</h3>";
echo "<ul>";
foreach ($patients as $patient) {
    echo "<li><strong>{$patient['email']}</strong> - Password: patient123 (or the password you set)</li>";
}
echo "</ul>";
echo "</div>";

// Step 6: Recent blood requests
echo "<h2>Step 5: Recent Blood Requests</h2>";

try {
    $stmt = $conn->query("
        SELECT br.*, u.email, u.first_name, u.last_name, p.patient_id as patient_code
        FROM blood_requests br
        JOIN users u ON br.requested_by_user_id = u.id
        JOIN patients p ON br.patient_id = p.id
        ORDER BY br.created_at DESC
        LIMIT 10
    ");
    $requests = $stmt->fetchAll();
    
    if (empty($requests)) {
        echo "<p>No blood requests found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr><th>Request ID</th><th>Patient</th><th>Blood Type</th><th>Units</th><th>Priority</th><th>Status</th><th>Created</th></tr>";
        
        foreach ($requests as $request) {
            echo "<tr>";
            echo "<td>{$request['request_id']}</td>";
            echo "<td>{$request['first_name']} {$request['last_name']}<br><small>{$request['email']}</small></td>";
            echo "<td>{$request['blood_type']}</td>";
            echo "<td>{$request['units_requested']}</td>";
            echo "<td>{$request['priority']}</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Error fetching requests: " . $e->getMessage() . "</p>";
}

echo "<p><a href='?' style='background: #6c757d; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔄 Refresh Page</a></p>";
?>