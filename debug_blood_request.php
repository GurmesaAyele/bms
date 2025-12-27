<?php
session_start();
require_once 'backend/config/database.php';

echo "<h2>Blood Request Debug Test</h2>";

// Test database connection
try {
    echo "<p>✓ Database connection successful</p>";
    
    // Check if blood_requests table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'blood_requests'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✓ blood_requests table exists</p>";
        
        // Check table structure
        $stmt = $conn->query("DESCRIBE blood_requests");
        $columns = $stmt->fetchAll();
        echo "<p>✓ Table structure:</p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>✗ blood_requests table does not exist</p>";
    }
    
    // Check if patients table exists and has data
    $stmt = $conn->query("SHOW TABLES LIKE 'patients'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✓ patients table exists</p>";
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM patients");
        $result = $stmt->fetch();
        echo "<p>Patients count: {$result['count']}</p>";
    } else {
        echo "<p>✗ patients table does not exist</p>";
    }
    
    // Check if users table exists and has data
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'patient'");
    $result = $stmt->fetch();
    echo "<p>Patient users count: {$result['count']}</p>";
    
    // Test session simulation
    echo "<h3>Testing Blood Request Submission</h3>";
    
    // Simulate a logged-in patient user
    $_SESSION['user_id'] = 1; // Assuming admin user exists
    $_SESSION['user_type'] = 'patient';
    
    // Check if we can find/create a patient record
    try {
        $patient_stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
        $patient_stmt->execute([$_SESSION['user_id']]);
        $patient_record = $patient_stmt->fetch();
        
        if ($patient_record) {
            echo "<p>✓ Patient record found: ID {$patient_record['id']}</p>";
            $patient_id = $patient_record['id'];
        } else {
            echo "<p>✗ No patient record found for user_id {$_SESSION['user_id']}</p>";
            echo "<p>Creating test patient record...</p>";
            
            // Create a test patient record
            $insert_stmt = $conn->prepare("
                INSERT INTO patients (user_id, patient_id, blood_type, date_of_birth, gender, emergency_contact_name) 
                VALUES (?, ?, 'O+', '1990-01-01', 'male', 'Test Contact')
            ");
            $patient_id_str = 'PAT-' . date('Y') . '-' . str_pad(1, 6, '0', STR_PAD_LEFT);
            $insert_stmt->execute([$_SESSION['user_id'], $patient_id_str]);
            $patient_id = $conn->lastInsertId();
            echo "<p>✓ Test patient record created: ID {$patient_id}</p>";
        }
        
        // Test blood request insertion
        $request_id = 'REQ-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("
            INSERT INTO blood_requests (request_id, patient_id, assigned_hospital_id, blood_type, units_requested, 
                                      priority, medical_reason, doctor_contact, emergency_contact_name, 
                                      requested_by_user_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $result = $stmt->execute([
            $request_id,
            $patient_id,
            1, // Test hospital ID
            'O+',
            2,
            'urgent',
            'Test medical reason',
            'Dr. Test',
            'Emergency Contact',
            $_SESSION['user_id']
        ]);
        
        if ($result) {
            echo "<p>✓ Blood request inserted successfully! Request ID: {$request_id}</p>";
        } else {
            echo "<p>✗ Failed to insert blood request</p>";
            print_r($stmt->errorInfo());
        }
        
    } catch (PDOException $e) {
        echo "<p>✗ Error during patient/request processing: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>✗ Database connection failed: " . $e->getMessage() . "</p>";
}
?>