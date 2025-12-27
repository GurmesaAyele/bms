<?php
require_once 'backend/config/database.php';

echo "<h2>Fixing Blood Requests Table</h2>";

try {
    // Check if blood_requests table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'blood_requests'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✓ blood_requests table exists</p>";
        
        // Check current structure
        $stmt = $conn->query("DESCRIBE blood_requests");
        $columns = $stmt->fetchAll();
        $column_names = array_column($columns, 'Field');
        
        echo "<p>Current columns: " . implode(", ", $column_names) . "</p>";
        
        // Check if we need to add missing columns
        $needed_columns = [
            'assigned_hospital_id' => 'hospital_id',
            'requested_by_user_id' => null
        ];
        
        foreach ($needed_columns as $new_col => $old_col) {
            if (!in_array($new_col, $column_names)) {
                if ($old_col && in_array($old_col, $column_names)) {
                    // Rename existing column
                    $conn->exec("ALTER TABLE blood_requests CHANGE $old_col $new_col INT");
                    echo "<p>✓ Renamed column $old_col to $new_col</p>";
                } else {
                    // Add new column
                    $conn->exec("ALTER TABLE blood_requests ADD COLUMN $new_col INT");
                    echo "<p>✓ Added column $new_col</p>";
                }
            } else {
                echo "<p>✓ Column $new_col already exists</p>";
            }
        }
        
        // Add foreign key constraints if they don't exist
        try {
            $conn->exec("ALTER TABLE blood_requests ADD CONSTRAINT fk_blood_requests_requested_by FOREIGN KEY (requested_by_user_id) REFERENCES users(id) ON DELETE CASCADE");
            echo "<p>✓ Added foreign key constraint for requested_by_user_id</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p>✓ Foreign key constraint already exists for requested_by_user_id</p>";
            } else {
                echo "<p>⚠ Could not add foreign key constraint: " . $e->getMessage() . "</p>";
            }
        }
        
    } else {
        echo "<p>✗ blood_requests table does not exist. Please run setup_database.php first.</p>";
    }
    
    // Test if we can insert a blood request now
    echo "<h3>Testing Blood Request Insertion</h3>";
    
    // Check if we have any patients
    $stmt = $conn->query("SELECT COUNT(*) as count FROM patients");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "<p>No patients found. Creating a test patient...</p>";
        
        // Create a test user first
        $user_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified)
            VALUES (?, ?, 'patient', 'Test', 'Patient', 1, 1)
        ");
        $test_email = 'test.patient@example.com';
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
        
        echo "<p>✓ Test patient created (ID: $patient_id, User ID: $user_id)</p>";
    } else {
        // Get first patient
        $stmt = $conn->query("SELECT p.id, p.user_id FROM patients p LIMIT 1");
        $patient = $stmt->fetch();
        $patient_id = $patient['id'];
        $user_id = $patient['user_id'];
        echo "<p>✓ Using existing patient (ID: $patient_id, User ID: $user_id)</p>";
    }
    
    // Try to insert a test blood request
    $request_id = 'REQ-TEST-' . date('YmdHis');
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
        'Test medical reason for blood request',
        'Dr. Test Doctor',
        'Test Emergency Contact',
        $user_id
    ]);
    
    if ($result) {
        echo "<p>✅ Test blood request inserted successfully! Request ID: $request_id</p>";
        
        // Clean up test request
        $conn->prepare("DELETE FROM blood_requests WHERE request_id = ?")->execute([$request_id]);
        echo "<p>✓ Test request cleaned up</p>";
    } else {
        echo "<p>✗ Failed to insert test blood request</p>";
        print_r($stmt->errorInfo());
    }
    
} catch (PDOException $e) {
    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<br><a href='frontend/request-blood.php'>Test Blood Request Form</a>";
?>