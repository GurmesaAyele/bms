<?php
/**
 * Database Setup Script for BloodConnect
 * Run this file once to set up the database with sample data
 */

$servername = "localhost";
$username = "root";
$password = "14162121";
$database = "bloodconnect";

try {
    // Connect to MySQL server (without database)
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS $database");
    echo "✅ Database '$database' created successfully<br>";
    
    // Connect to the database
    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables
    $tables = [
        "users" => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                user_type ENUM('patient', 'donor', 'hospital', 'admin') NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                phone VARCHAR(20),
                address TEXT,
                city VARCHAR(100),
                state VARCHAR(100),
                zip_code VARCHAR(20),
                country VARCHAR(100) DEFAULT 'USA',
                is_active BOOLEAN DEFAULT TRUE,
                is_verified BOOLEAN DEFAULT FALSE,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
        
        "patients" => "
            CREATE TABLE IF NOT EXISTS patients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                patient_id VARCHAR(50) UNIQUE NOT NULL,
                blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
                date_of_birth DATE NOT NULL,
                gender ENUM('male', 'female', 'other') NOT NULL,
                weight DECIMAL(5,2),
                height DECIMAL(5,2),
                medical_id VARCHAR(100),
                insurance_provider VARCHAR(255),
                emergency_contact_name VARCHAR(255),
                emergency_contact_phone VARCHAR(20),
                known_allergies TEXT,
                medical_conditions TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
        
        "donors" => "
            CREATE TABLE IF NOT EXISTS donors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                donor_id VARCHAR(50) UNIQUE NOT NULL,
                blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
                date_of_birth DATE NOT NULL,
                gender ENUM('male', 'female', 'other') NOT NULL,
                weight DECIMAL(5,2) NOT NULL,
                height DECIMAL(5,2),
                is_eligible BOOLEAN DEFAULT TRUE,
                last_donation_date DATE,
                next_eligible_date DATE,
                total_donations INT DEFAULT 0,
                health_status ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
                is_available BOOLEAN DEFAULT TRUE,
                preferred_donation_time ENUM('morning', 'afternoon', 'evening', 'any') DEFAULT 'any',
                emergency_contact_name VARCHAR(255),
                emergency_contact_phone VARCHAR(20),
                known_allergies TEXT,
                medical_conditions TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
        
        "hospitals" => "
            CREATE TABLE IF NOT EXISTS hospitals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                hospital_id VARCHAR(50) UNIQUE NOT NULL,
                hospital_name VARCHAR(255) NOT NULL,
                hospital_type ENUM('government', 'private', 'charitable', 'specialty') NOT NULL,
                license_number VARCHAR(100) NOT NULL,
                accreditation_level ENUM('level_1', 'level_2', 'level_3', 'level_4') DEFAULT 'level_1',
                bed_capacity INT,
                has_blood_bank BOOLEAN DEFAULT TRUE,
                blood_bank_license VARCHAR(100),
                emergency_services BOOLEAN DEFAULT TRUE,
                trauma_center_level ENUM('level_1', 'level_2', 'level_3', 'level_4', 'none') DEFAULT 'none',
                operating_hours_start TIME DEFAULT '00:00:00',
                operating_hours_end TIME DEFAULT '23:59:59',
                is_24_7 BOOLEAN DEFAULT TRUE,
                latitude DECIMAL(10, 8),
                longitude DECIMAL(11, 8),
                website VARCHAR(255),
                emergency_phone VARCHAR(20),
                blood_bank_phone VARCHAR(20),
                is_verified BOOLEAN DEFAULT FALSE,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
        
        "blood_inventory" => "
            CREATE TABLE IF NOT EXISTS blood_inventory (
                id INT AUTO_INCREMENT PRIMARY KEY,
                hospital_id INT NOT NULL,
                blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
                units_available INT DEFAULT 0,
                low_stock_threshold INT DEFAULT 10,
                critical_stock_threshold INT DEFAULT 5,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
                UNIQUE KEY unique_hospital_blood_type (hospital_id, blood_type)
            )",
        
        "blood_requests" => "
            CREATE TABLE IF NOT EXISTS blood_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id VARCHAR(50) UNIQUE NOT NULL,
                patient_id INT NOT NULL,
                assigned_hospital_id INT,
                blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
                units_requested INT NOT NULL,
                priority ENUM('routine', 'urgent', 'emergency') DEFAULT 'routine',
                medical_reason TEXT NOT NULL,
                doctor_contact VARCHAR(255),
                emergency_contact_name VARCHAR(255),
                requested_by_user_id INT NOT NULL,
                status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
                approved_by INT,
                approved_at TIMESTAMP NULL,
                completed_at TIMESTAMP NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
                FOREIGN KEY (requested_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
            )",
        
        "donation_offers" => "
            CREATE TABLE IF NOT EXISTS donation_offers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                offer_id VARCHAR(50) UNIQUE NOT NULL,
                donor_id INT NOT NULL,
                hospital_id INT,
                blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
                preferred_date DATE,
                preferred_time TIME,
                status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
                accepted_by INT,
                accepted_at TIMESTAMP NULL,
                completed_at TIMESTAMP NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
                FOREIGN KEY (accepted_by) REFERENCES users(id) ON DELETE SET NULL
            )"
    ];
    
    foreach ($tables as $table_name => $sql) {
        $conn->exec($sql);
        echo "✅ Table '$table_name' created successfully<br>";
    }
    
    // Insert default admin user
    $admin_email = 'admin@bloodconnect.com';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $check_admin = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_admin->execute([$admin_email]);
    
    if (!$check_admin->fetch()) {
        $admin_stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, user_type, first_name, last_name, is_active, is_verified)
            VALUES (?, ?, 'admin', 'System', 'Administrator', 1, 1)
        ");
        $admin_stmt->execute([$admin_email, $admin_password]);
        echo "✅ Default admin user created (admin@bloodconnect.com / admin123)<br>";
    } else {
        echo "ℹ️ Admin user already exists<br>";
    }
    
    // Insert sample hospitals
    $sample_hospitals = [
        [
            'email' => 'city.general@hospital.com',
            'password' => password_hash('hospital123', PASSWORD_DEFAULT),
            'first_name' => 'City General',
            'last_name' => 'Hospital',
            'phone' => '+1-555-0101',
            'address' => '123 Medical Center Dr',
            'city' => 'Downtown',
            'state' => 'CA',
            'hospital_name' => 'City General Hospital',
            'hospital_type' => 'government',
            'license_number' => 'LIC-001-2024',
            'is_verified' => 1
        ],
        [
            'email' => 'metro.medical@hospital.com',
            'password' => password_hash('hospital123', PASSWORD_DEFAULT),
            'first_name' => 'Metro Medical',
            'last_name' => 'Center',
            'phone' => '+1-555-0102',
            'address' => '456 Health Ave',
            'city' => 'Midtown',
            'state' => 'CA',
            'hospital_name' => 'Metro Medical Center',
            'hospital_type' => 'private',
            'license_number' => 'LIC-002-2024',
            'is_verified' => 1
        ]
    ];
    
    foreach ($sample_hospitals as $hospital) {
        $check_hospital = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_hospital->execute([$hospital['email']]);
        
        if (!$check_hospital->fetch()) {
            // Insert user
            $user_stmt = $conn->prepare("
                INSERT INTO users (email, password_hash, user_type, first_name, last_name, phone, address, city, state, is_active, is_verified)
                VALUES (?, ?, 'hospital', ?, ?, ?, ?, ?, ?, 1, 1)
            ");
            $user_stmt->execute([
                $hospital['email'],
                $hospital['password'],
                $hospital['first_name'],
                $hospital['last_name'],
                $hospital['phone'],
                $hospital['address'],
                $hospital['city'],
                $hospital['state']
            ]);
            
            $user_id = $conn->lastInsertId();
            $hospital_id = 'HP-' . date('Y') . '-' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
            
            // Insert hospital profile
            $hospital_stmt = $conn->prepare("
                INSERT INTO hospitals (user_id, hospital_id, hospital_name, hospital_type, license_number, is_verified, is_active)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $hospital_stmt->execute([
                $user_id,
                $hospital_id,
                $hospital['hospital_name'],
                $hospital['hospital_type'],
                $hospital['license_number'],
                $hospital['is_verified']
            ]);
            
            $hospital_profile_id = $conn->lastInsertId();
            
            // Initialize blood inventory
            $blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
            foreach ($blood_types as $blood_type) {
                $units = rand(5, 50); // Random units between 5-50
                $inventory_stmt = $conn->prepare("
                    INSERT INTO blood_inventory (hospital_id, blood_type, units_available, low_stock_threshold, critical_stock_threshold)
                    VALUES (?, ?, ?, 10, 5)
                ");
                $inventory_stmt->execute([$hospital_profile_id, $blood_type, $units]);
            }
            
            echo "✅ Sample hospital '{$hospital['hospital_name']}' created<br>";
        }
    }
    
    echo "<br><h2>🎉 Database Setup Complete!</h2>";
    echo "<h3>Test Credentials:</h3>";
    echo "<strong>Admin:</strong> admin@bloodconnect.com / admin123<br>";
    echo "<strong>Hospital 1:</strong> city.general@hospital.com / hospital123<br>";
    echo "<strong>Hospital 2:</strong> metro.medical@hospital.com / hospital123<br>";
    echo "<br><a href='frontend/index.php' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Website</a>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>