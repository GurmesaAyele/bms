<?php
/**
 * Fix Database Issues
 * Check and fix common database structure issues
 */

require_once 'backend/config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix Database Issues</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; font-weight: bold; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>Fix Database Issues</h1>";

try {
    // Check hospitals table structure
    echo "<div class='section'>";
    echo "<h2>Step 1: Check Hospitals Table Structure</h2>";
    
    $tables = $conn->query("SHOW TABLES LIKE 'hospitals'")->fetchAll();
    
    if (empty($tables)) {
        echo "<div class='error'>hospitals table does not exist! Creating it...</div>";
        
        $create_hospitals = "
        CREATE TABLE hospitals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            hospital_id VARCHAR(50) UNIQUE NOT NULL,
            hospital_name VARCHAR(255) NOT NULL,
            hospital_type ENUM('public', 'private', 'government', 'specialty') NOT NULL DEFAULT 'public',
            license_number VARCHAR(100) UNIQUE NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            is_verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $conn->exec($create_hospitals);
        echo "<div class='success'>✓ Created hospitals table</div>";
    } else {
        echo "<div class='success'>✓ hospitals table exists</div>";
        
        // Check if hospital_id column exists
        $desc = $conn->query("DESCRIBE hospitals")->fetchAll();
        $columns = array_column($desc, 'Field');
        
        if (!in_array('hospital_id', $columns)) {
            echo "<div class='warning'>hospital_id column missing, adding it...</div>";
            $conn->exec("ALTER TABLE hospitals ADD COLUMN hospital_id VARCHAR(50) UNIQUE");
            echo "<div class='success'>✓ Added hospital_id column</div>";
        } else {
            echo "<div class='success'>✓ hospital_id column exists</div>";
        }
        
        // Show current structure
        echo "<h3>Current Structure:</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($desc as $col) {
            echo "<tr><td><strong>{$col['Field']}</strong></td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Check donation_offers table structure
    echo "<div class='section'>";
    echo "<h2>Step 2: Check Donation Offers Table Structure</h2>";
    
    $tables = $conn->query("SHOW TABLES LIKE 'donation_offers'")->fetchAll();
    
    if (empty($tables)) {
        echo "<div class='error'>donation_offers table does not exist! Creating it...</div>";
        
        $create_donation_offers = "
        CREATE TABLE donation_offers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            offer_id VARCHAR(50) UNIQUE NOT NULL,
            donor_id INT NOT NULL,
            assigned_hospital_id INT NULL,
            blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
            preferred_date DATE NULL,
            preferred_time TIME NULL,
            status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
            accepted_by INT NULL,
            accepted_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_offer_id (offer_id),
            INDEX idx_donor_id (donor_id),
            INDEX idx_assigned_hospital_id (assigned_hospital_id),
            INDEX idx_blood_type (blood_type),
            INDEX idx_status (status),
            INDEX idx_accepted_by (accepted_by)
        )";
        
        $conn->exec($create_donation_offers);
        echo "<div class='success'>✓ Created donation_offers table</div>";
    } else {
        echo "<div class='success'>✓ donation_offers table exists</div>";
        
        // Show current structure
        $desc = $conn->query("DESCRIBE donation_offers")->fetchAll();
        echo "<h3>Current Structure:</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($desc as $col) {
            echo "<tr><td><strong>{$col['Field']}</strong></td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Fix existing hospital records without hospital_id
    echo "<div class='section'>";
    echo "<h2>Step 3: Fix Existing Hospital Records</h2>";
    
    $hospitals_without_id = $conn->query("SELECT id, user_id, hospital_name FROM hospitals WHERE hospital_id IS NULL OR hospital_id = ''")->fetchAll();
    
    if (!empty($hospitals_without_id)) {
        echo "<div class='warning'>Found " . count($hospitals_without_id) . " hospitals without hospital_id</div>";
        
        foreach ($hospitals_without_id as $hospital) {
            $new_hospital_id = 'HOS-' . date('Y') . '-' . str_pad($hospital['id'], 6, '0', STR_PAD_LEFT);
            $update_stmt = $conn->prepare("UPDATE hospitals SET hospital_id = ? WHERE id = ?");
            $update_stmt->execute([$new_hospital_id, $hospital['id']]);
            
            echo "<div class='success'>✓ Updated hospital '{$hospital['hospital_name']}' with ID: $new_hospital_id</div>";
        }
    } else {
        echo "<div class='success'>✓ All hospitals have hospital_id values</div>";
    }
    echo "</div>";
    
    // Create test hospital if none exists
    echo "<div class='section'>";
    echo "<h2>Step 4: Ensure Test Hospital Exists</h2>";
    
    $test_hospital = $conn->prepare("SELECT id FROM users WHERE email = 'testhospital@bloodconnect.com'");
    $test_hospital->execute();
    
    if (!$test_hospital->fetch()) {
        echo "<div class='info'>Creating test hospital user...</div>";
        
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
        
        echo "<div class='success'>✓ Created test hospital (Email: testhospital@bloodconnect.com, Password: hospital123)</div>";
        echo "<div class='info'>Hospital ID: $hospital_id</div>";
    } else {
        echo "<div class='success'>✓ Test hospital user already exists</div>";
    }
    echo "</div>";
    
    // Summary
    echo "<div class='section'>";
    echo "<h2>Step 5: Summary</h2>";
    
    // Count records
    $hospital_count = $conn->query("SELECT COUNT(*) FROM hospitals")->fetchColumn();
    $user_count = $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'hospital'")->fetchColumn();
    $request_count = $conn->query("SELECT COUNT(*) FROM blood_requests")->fetchColumn();
    $offer_count = $conn->query("SELECT COUNT(*) FROM donation_offers")->fetchColumn();
    
    echo "<ul>";
    echo "<li><strong>Hospital Users:</strong> $user_count</li>";
    echo "<li><strong>Hospital Records:</strong> $hospital_count</li>";
    echo "<li><strong>Blood Requests:</strong> $request_count</li>";
    echo "<li><strong>Donation Offers:</strong> $offer_count</li>";
    echo "</ul>";
    
    echo "<div class='success'><h3>✓ Database structure fixed!</h3></div>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Try the hospital dashboard again: <a href='frontend/dashboard/hospital.php'>Hospital Dashboard</a></li>";
    echo "<li>Login with: testhospital@bloodconnect.com / hospital123</li>";
    echo "<li>If no data shows, run: <a href='add_test_data_fixed.php'>Add Test Data</a></li>";
    echo "</ol>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Database Error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>SQL State: " . $e->getCode() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>