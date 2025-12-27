<?php
session_start();
require_once 'backend/config/database.php';

echo "<h2>Blood Request Form Test</h2>";

// Check if we have test users
$stmt = $conn->query("SELECT u.id, u.email, u.user_type, p.id as patient_id FROM users u LEFT JOIN patients p ON u.id = p.user_id WHERE u.user_type = 'patient' LIMIT 1");
$test_user = $stmt->fetch();

if (!$test_user) {
    echo "<p>No patient users found. Creating test patient...</p>";
    
    // Create test patient user
    $user_stmt = $conn->prepare("
        INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified)
        VALUES (?, ?, 'patient', 'Test', 'Patient', 1, 1)
    ");
    $test_email = 'patient@test.com';
    $test_password = password_hash('test123', PASSWORD_DEFAULT);
    $user_stmt->execute([$test_email, $test_password]);
    $user_id = $conn->lastInsertId();
    
    // Create patient record
    $patient_stmt = $conn->prepare("
        INSERT INTO patients (user_id, patient_id, blood_type, date_of_birth, gender, emergency_contact_name)
        VALUES (?, ?, 'O+', '1990-01-01', 'male', 'Test Emergency Contact')
    ");
    $patient_id_str = 'PAT-' . date('Y') . '-' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
    $patient_stmt->execute([$user_id, $patient_id_str]);
    $patient_id = $conn->lastInsertId();
    
    echo "<p>✓ Test patient created: $test_email / test123</p>";
    $test_user = ['id' => $user_id, 'email' => $test_email, 'user_type' => 'patient', 'patient_id' => $patient_id];
}

// Simulate login
$_SESSION['user_id'] = $test_user['id'];
$_SESSION['user_type'] = $test_user['user_type'];

echo "<p>✓ Simulated login as: {$test_user['email']} (User ID: {$test_user['id']})</p>";

// Simulate form submission
$_POST = [
    'submit_request' => '1',
    'bloodType' => 'O+',
    'unitsRequested' => '2',
    'priority' => 'urgent',
    'medicalReason' => 'Test medical emergency requiring blood transfusion',
    'hospitalId' => '',
    'doctorContact' => 'Dr. Test Doctor - 555-1234',
    'emergencyContact' => 'Emergency Contact - 555-5678'
];

echo "<p>✓ Simulating form submission with test data</p>";

// Include the form processing logic
$error_message = '';
$success_message = '';

// Handle blood request form submission
if ($_POST && isset($_POST['submit_request'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $error_message = 'Please login to submit a blood request.';
    } else {
        // Check if user is a patient or get patient ID
        $patient_id = null;
        if ($_SESSION['user_type'] == 'patient') {
            // Get patient ID from patients table
            try {
                $patient_stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
                $patient_stmt->execute([$_SESSION['user_id']]);
                $patient_record = $patient_stmt->fetch();
                if ($patient_record) {
                    $patient_id = $patient_record['id'];
                    echo "<p>✓ Found patient record: ID {$patient_id}</p>";
                } else {
                    $error_message = 'Patient record not found. Please complete your patient registration.';
                }
            } catch (PDOException $e) {
                $error_message = 'Error verifying patient status. Please try again.';
            }
        } else {
            $error_message = 'Only registered patients can submit blood requests. Please register as a patient first.';
        }
        
        if (!$error_message && $patient_id) {
            $blood_type = $_POST['bloodType'];
            $units_requested = (int)$_POST['unitsRequested'];
            $priority = $_POST['priority'];
            $medical_reason = trim($_POST['medicalReason']);
            $hospital_id = !empty($_POST['hospitalId']) ? $_POST['hospitalId'] : null;
            $doctor_contact = trim($_POST['doctorContact']);
            $emergency_contact = trim($_POST['emergencyContact']);
            
            echo "<p>✓ Form data validated</p>";
            
            // Validation
            if (empty($blood_type) || empty($units_requested) || empty($priority) || empty($medical_reason)) {
                $error_message = 'Please fill in all required fields.';
            } elseif ($units_requested < 1 || $units_requested > 10) {
                $error_message = 'Units requested must be between 1 and 10.';
            } else {
                try {
                    // Generate request ID
                    $request_id = 'REQ-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
                    
                    echo "<p>✓ Generated request ID: $request_id</p>";
                    
                    // Insert blood request
                    $stmt = $conn->prepare("
                        INSERT INTO blood_requests (request_id, patient_id, assigned_hospital_id, blood_type, units_requested, 
                                                  priority, medical_reason, doctor_contact, emergency_contact_name, 
                                                  requested_by_user_id, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                    ");
                    $result = $stmt->execute([
                        $request_id,
                        $patient_id,
                        $hospital_id,
                        $blood_type,
                        $units_requested,
                        $priority,
                        $medical_reason,
                        $doctor_contact,
                        $emergency_contact,
                        $_SESSION['user_id']
                    ]);
                    
                    if ($result) {
                        $success_message = "Blood request submitted successfully! Request ID: $request_id. You will be notified when a match is found.";
                        echo "<p>✅ SUCCESS: $success_message</p>";
                    } else {
                        $error_message = 'Failed to execute insert statement.';
                        echo "<p>✗ FAILED: $error_message</p>";
                    }
                    
                } catch (PDOException $e) {
                    $error_message = 'Failed to submit request. Please try again. Error: ' . $e->getMessage();
                    echo "<p>✗ DATABASE ERROR: $error_message</p>";
                }
            }
        }
    }
}

if ($error_message) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>ERROR: $error_message</div>";
}

if ($success_message) {
    echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>SUCCESS: $success_message</div>";
}

echo "<br><h3>Next Steps:</h3>";
echo "<p>1. <a href='frontend/auth/login.php'>Login as patient</a> (patient@test.com / test123)</p>";
echo "<p>2. <a href='frontend/request-blood.php'>Try the blood request form</a></p>";
?>