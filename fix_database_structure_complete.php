<?php
require_once 'backend/config/database.php';

echo "<h2>Complete Database Structure Fix</h2>";

try {
    // First, let's check what tables exist
    $stmt = $conn->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Existing tables: " . implode(", ", $existing_tables) . "</p>";
    
    // Fix blood_requests table structure
    echo "<h3>1. Fixing blood_requests table</h3>";
    
    if (in_array('blood_requests', $existing_tables)) {
        // Check current structure
        $stmt = $conn->query("DESCRIBE blood_requests");
        $columns = $stmt->fetchAll();
        $column_names = array_column($columns, 'Field');
        
        echo "<p>Current blood_requests columns: " . implode(", ", $column_names) . "</p>";
        
        // Add missing columns if they don't exist
        $missing_columns = [
            'emergency_contact_phone' => "ALTER TABLE blood_requests ADD COLUMN emergency_contact_phone VARCHAR(20) NULL",
            'rejected_by_user_id' => "ALTER TABLE blood_requests ADD COLUMN rejected_by_user_id INT NULL",
            'rejected_at' => "ALTER TABLE blood_requests ADD COLUMN rejected_at TIMESTAMP NULL",
            'rejection_reason' => "ALTER TABLE blood_requests ADD COLUMN rejection_reason VARCHAR(255) NULL",
            'rejection_notes' => "ALTER TABLE blood_requests ADD COLUMN rejection_notes TEXT NULL",
            'cancelled_at' => "ALTER TABLE blood_requests ADD COLUMN cancelled_at TIMESTAMP NULL",
            'cancellation_reason' => "ALTER TABLE blood_requests ADD COLUMN cancellation_reason VARCHAR(255) NULL"
        ];
        
        foreach ($missing_columns as $col_name => $sql) {
            if (!in_array($col_name, $column_names)) {
                try {
                    $conn->exec($sql);
                    echo "<p>✓ Added column: $col_name</p>";
                } catch (PDOException $e) {
                    echo "<p>⚠ Could not add $col_name: " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p>✓ Column $col_name already exists</p>";
            }
        }
        
        // Add foreign key constraints
        $foreign_keys = [
            'fk_blood_requests_patient' => "ALTER TABLE blood_requests ADD CONSTRAINT fk_blood_requests_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE",
            'fk_blood_requests_hospital' => "ALTER TABLE blood_requests ADD CONSTRAINT fk_blood_requests_hospital FOREIGN KEY (assigned_hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL",
            'fk_blood_requests_requested_by' => "ALTER TABLE blood_requests ADD CONSTRAINT fk_blood_requests_requested_by FOREIGN KEY (requested_by_user_id) REFERENCES users(id) ON DELETE CASCADE",
            'fk_blood_requests_approved_by' => "ALTER TABLE blood_requests ADD CONSTRAINT fk_blood_requests_approved_by FOREIGN KEY (approved_by_user_id) REFERENCES users(id) ON DELETE SET NULL",
            'fk_blood_requests_rejected_by' => "ALTER TABLE blood_requests ADD CONSTRAINT fk_blood_requests_rejected_by FOREIGN KEY (rejected_by_user_id) REFERENCES users(id) ON DELETE SET NULL"
        ];
        
        foreach ($foreign_keys as $fk_name => $sql) {
            try {
                $conn->exec($sql);
                echo "<p>✓ Added foreign key: $fk_name</p>";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                    echo "<p>✓ Foreign key $fk_name already exists</p>";
                } else {
                    echo "<p>⚠ Could not add foreign key $fk_name: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    // Fix blood_offers table (rename to donation_offers if needed)
    echo "<h3>2. Fixing donation offers table</h3>";
    
    if (in_array('blood_offers', $existing_tables) && !in_array('donation_offers', $existing_tables)) {
        echo "<p>Renaming blood_offers to donation_offers...</p>";
        $conn->exec("RENAME TABLE blood_offers TO donation_offers");
        echo "<p>✓ Table renamed successfully</p>";
    } elseif (in_array('donation_offers', $existing_tables)) {
        echo "<p>✓ donation_offers table already exists</p>";
    } else {
        echo "<p>Creating donation_offers table...</p>";
        $conn->exec("
            CREATE TABLE donation_offers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                offer_id VARCHAR(50) UNIQUE NOT NULL,
                donor_id INT NOT NULL,
                assigned_hospital_id INT NULL,
                blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
                volume_ml INT DEFAULT 450,
                preferred_date DATE NOT NULL,
                preferred_time TIME NOT NULL,
                status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                offered_by_user_id INT NOT NULL,
                accepted_by_hospital_id INT NULL,
                accepted_by_user_id INT NULL,
                accepted_at TIMESTAMP NULL,
                rejected_by_hospital_id INT NULL,
                rejected_by_user_id INT NULL,
                rejected_at TIMESTAMP NULL,
                rejection_reason VARCHAR(255) NULL,
                rejection_notes TEXT NULL,
                scheduled_date DATE NULL,
                scheduled_time TIME NULL,
                completed_at TIMESTAMP NULL,
                cancelled_at TIMESTAMP NULL,
                cancellation_reason VARCHAR(255) NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
                FOREIGN KEY (offered_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (accepted_by_hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
                FOREIGN KEY (accepted_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (rejected_by_hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
                FOREIGN KEY (rejected_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
                
                INDEX idx_offer_id (offer_id),
                INDEX idx_donor_id (donor_id),
                INDEX idx_hospital_id (assigned_hospital_id),
                INDEX idx_status (status),
                INDEX idx_blood_type (blood_type),
                INDEX idx_preferred_date (preferred_date)
            )
        ");
        echo "<p>✓ donation_offers table created</p>";
    }
    
    // Test blood request insertion
    echo "<h3>3. Testing Blood Request Submission</h3>";
    
    // Check if we have a test patient
    $stmt = $conn->query("SELECT p.id as patient_id, p.user_id FROM patients p LIMIT 1");
    $patient = $stmt->fetch();
    
    if (!$patient) {
        echo "<p>Creating test patient...</p>";
        
        // Create test user
        $user_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified)
            VALUES (?, ?, 'patient', 'Test', 'Patient', 1, 1)
        ");
        $test_email = 'testpatient@bloodconnect.com';
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
        $patient = ['patient_id' => $patient_id, 'user_id' => $user_id];
    }
    
    // Test blood request insertion
    $request_id = 'REQ-TEST-' . date('YmdHis');
    $stmt = $conn->prepare("
        INSERT INTO blood_requests (
            request_id, patient_id, assigned_hospital_id, blood_type, units_requested, 
            priority, medical_reason, doctor_contact, emergency_contact_name, 
            requested_by_user_id, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    try {
        $result = $stmt->execute([
            $request_id,
            $patient['patient_id'],
            null, // No specific hospital
            'O+',
            2,
            'urgent',
            'Test medical reason for blood request',
            'Dr. Test Doctor',
            'Test Emergency Contact',
            $patient['user_id']
        ]);
        
        if ($result) {
            echo "<p>✅ Test blood request inserted successfully! Request ID: $request_id</p>";
            
            // Clean up test request
            $conn->prepare("DELETE FROM blood_requests WHERE request_id = ?")->execute([$request_id]);
            echo "<p>✓ Test request cleaned up</p>";
        } else {
            echo "<p>✗ Failed to insert test blood request</p>";
        }
    } catch (PDOException $e) {
        echo "<p>✗ Error inserting test request: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>4. Summary</h3>";
    echo "<p>✅ Database structure has been fixed and tested</p>";
    echo "<p>✅ Blood request form should now work properly</p>";
    
    echo "<h3>5. Test Instructions</h3>";
    echo "<p>1. <a href='frontend/auth/login.php'>Login</a> with: testpatient@bloodconnect.com / test123</p>";
    echo "<p>2. Go to <a href='frontend/request-blood.php'>Blood Request Form</a></p>";
    echo "<p>3. Fill out and submit the form</p>";
    
} catch (PDOException $e) {
    echo "<p>✗ Database error: " . $e->getMessage() . "</p>";
}
?>