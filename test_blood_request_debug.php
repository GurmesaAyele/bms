<?php
session_start();
require_once 'backend/config/database.php';

echo "<h2>Blood Request Debug Test</h2>";

// Simulate being logged in as a patient
$stmt = $conn->query("SELECT u.id, u.email, p.id as patient_id FROM users u JOIN patients p ON u.id = p.user_id WHERE u.user_type = 'patient' LIMIT 1");
$patient_user = $stmt->fetch();

if (!$patient_user) {
    echo "<p>❌ No patient user found. Please run the fix script first.</p>";
    exit;
}

// Set session
$_SESSION['user_id'] = $patient_user['id'];
$_SESSION['user_type'] = 'patient';

echo "<p>✅ Logged in as: {$patient_user['email']} (User ID: {$patient_user['id']}, Patient ID: {$patient_user['patient_id']})</p>";

// Simulate form submission
$_POST = [
    'submit_request' => '1',
    'bloodType' => 'O+',
    'unitsRequested' => '2',
    'priority' => 'urgent',
    'medicalReason' => 'Emergency surgery requiring blood transfusion',
    'hospitalId' => '',
    'doctorContact' => 'Dr. Smith - Emergency Department - 555-1234',
    'emergencyContact' => 'John Doe - 555-5678'
];

echo "<p>✅ Form data prepared</p>";

// Now run the exact same logic as in request-blood.php
$error_message = '';
$success_message = '';

if ($_POST && isset($_POST['submit_request'])) {
    echo "<p>🔄 Processing form submission...</p>";
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $error_message = 'Please login to submit a blood request.';
        echo "<p>❌ Not logged in</p>";
    } else {
        echo "<p>✅ User is logged in</p>";
        
        // Check if user is a patient or get patient ID
        $patient_id = null;
        if ($_SESSION['user_type'] == 'patient') {
            echo "<p>✅ User is a patient</p>";
            
            // Get patient ID from patients table
            try {
                $patient_stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
                $patient_stmt->execute([$_SESSION['user_id']]);
                $patient_record = $patient_stmt->fetch();
                if ($patient_record) {
                    $patient_id = $patient_record['id'];
                    echo "<p>✅ Patient record found: ID {$patient_id}</p>";
                } else {
                    $error_message = 'Patient record not found. Please complete your patient registration.';
                    echo "<p>❌ Patient record not found</p>";
                }
            } catch (PDOException $e) {
                $error_message = 'Error verifying patient status. Please try again.';
                echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
            }
        } else {
            $error_message = 'Only registered patients can submit blood requests. Please register as a patient first.';
            echo "<p>❌ User is not a patient</p>";
        }
        
        if (!$error_message && $patient_id) {
            echo "<p>✅ Validation passed, processing request...</p>";
            
            $blood_type = $_POST['bloodType'];
            $units_requested = (int)$_POST['unitsRequested'];
            $priority = $_POST['priority'];
            $medical_reason = trim($_POST['medicalReason']);
            $hospital_id = !empty($_POST['hospitalId']) ? $_POST['hospitalId'] : null;
            $doctor_contact = trim($_POST['doctorContact']);
            $emergency_contact = trim($_POST['emergencyContact']);
            
            echo "<p>📋 Form data extracted:</p>";
            echo "<ul>";
            echo "<li>Blood Type: $blood_type</li>";
            echo "<li>Units: $units_requested</li>";
            echo "<li>Priority: $priority</li>";
            echo "<li>Medical Reason: $medical_reason</li>";
            echo "<li>Hospital ID: " . ($hospital_id ?: 'None') . "</li>";
            echo "<li>Doctor Contact: $doctor_contact</li>";
            echo "<li>Emergency Contact: $emergency_contact</li>";
            echo "</ul>";
            
            // Validation
            if (empty($blood_type) || empty($units_requested) || empty($priority) || empty($medical_reason)) {
                $error_message = 'Please fill in all required fields.';
                echo "<p>❌ Required fields missing</p>";
            } elseif ($units_requested < 1 || $units_requested > 10) {
                $error_message = 'Units requested must be between 1 and 10.';
                echo "<p>❌ Invalid units requested</p>";
            } else {
                echo "<p>✅ Form validation passed</p>";
                
                try {
                    // Generate request ID
                    $request_id = 'REQ-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
                    echo "<p>🆔 Generated request ID: $request_id</p>";
                    
                    // Insert blood request
                    $stmt = $conn->prepare("
                        INSERT INTO blood_requests (request_id, patient_id, assigned_hospital_id, blood_type, units_requested, 
                                                  priority, medical_reason, doctor_contact, emergency_contact_name, 
                                                  requested_by_user_id, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                    ");
                    
                    echo "<p>🔄 Executing database insert...</p>";
                    
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
                        echo "<p>🎉 SUCCESS: $success_message</p>";
                        
                        // Verify the record was inserted
                        $verify_stmt = $conn->prepare("SELECT * FROM blood_requests WHERE request_id = ?");
                        $verify_stmt->execute([$request_id]);
                        $inserted_record = $verify_stmt->fetch();
                        
                        if ($inserted_record) {
                            echo "<p>✅ Record verified in database:</p>";
                            echo "<pre>" . print_r($inserted_record, true) . "</pre>";
                        }
                        
                    } else {
                        $error_message = 'Failed to execute insert statement.';
                        echo "<p>❌ Insert failed</p>";
                        echo "<p>Error info: " . print_r($stmt->errorInfo(), true) . "</p>";
                    }
                    
                } catch (PDOException $e) {
                    $error_message = 'Failed to submit request. Please try again. Error: ' . $e->getMessage();
                    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
}

if ($error_message) {
    echo "<div style='color: red; padding: 10px; border: 2px solid red; margin: 10px 0; background: #ffe6e6;'>";
    echo "<strong>ERROR:</strong> $error_message";
    echo "</div>";
}

if ($success_message) {
    echo "<div style='color: green; padding: 10px; border: 2px solid green; margin: 10px 0; background: #e6ffe6;'>";
    echo "<strong>SUCCESS:</strong> $success_message";
    echo "</div>";
}

echo "<h3>Next Steps:</h3>";
echo "<p>1. <a href='frontend/auth/login.php'>Login as patient</a> ({$patient_user['email']})</p>";
echo "<p>2. <a href='frontend/request-blood.php'>Try the actual blood request form</a></p>";
?>