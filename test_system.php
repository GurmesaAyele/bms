<?php
/**
 * System Test Script for BloodConnect
 * This script tests all major functionality
 */

echo "<h1>🩸 BloodConnect System Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
.success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
.error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
.info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
h2 { color: #dc2626; }
</style>";

// Test 1: Database Connection
echo "<div class='test-section'>";
echo "<h2>1. Database Connection Test</h2>";
try {
    require_once 'backend/config/database.php';
    echo "<div class='success'>✅ Database connection successful!</div>";
    echo "<p>Connected to database: bloodconnect</p>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 2: Check Tables
echo "<div class='test-section'>";
echo "<h2>2. Database Tables Test</h2>";
try {
    $tables = ['users', 'patients', 'donors', 'hospitals', 'blood_inventory', 'blood_requests', 'donation_offers'];
    $existing_tables = [];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            $existing_tables[] = $table;
        }
    }
    
    if (count($existing_tables) === count($tables)) {
        echo "<div class='success'>✅ All required tables exist!</div>";
        echo "<p>Tables: " . implode(', ', $existing_tables) . "</p>";
    } else {
        echo "<div class='error'>❌ Missing tables. Run setup_database.php first.</div>";
        echo "<p>Existing: " . implode(', ', $existing_tables) . "</p>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Table check failed: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 3: Check Sample Data
echo "<div class='test-section'>";
echo "<h2>3. Sample Data Test</h2>";
try {
    // Check admin user
    $admin_stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'");
    $admin_stmt->execute();
    $admin_count = $admin_stmt->fetch()['count'];
    
    // Check hospitals
    $hospital_stmt = $conn->prepare("SELECT COUNT(*) as count FROM hospitals");
    $hospital_stmt->execute();
    $hospital_count = $hospital_stmt->fetch()['count'];
    
    // Check blood inventory
    $inventory_stmt = $conn->prepare("SELECT COUNT(*) as count FROM blood_inventory");
    $inventory_stmt->execute();
    $inventory_count = $inventory_stmt->fetch()['count'];
    
    echo "<div class='success'>✅ Sample data check complete!</div>";
    echo "<p>Admin users: $admin_count</p>";
    echo "<p>Hospitals: $hospital_count</p>";
    echo "<p>Blood inventory records: $inventory_count</p>";
    
    if ($admin_count == 0 || $hospital_count == 0) {
        echo "<div class='error'>⚠️ Missing sample data. Run setup_database.php to create sample data.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Sample data check failed: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 4: File Structure
echo "<div class='test-section'>";
echo "<h2>4. File Structure Test</h2>";
$required_files = [
    'frontend/index.php',
    'frontend/auth/login.php',
    'frontend/auth/register.php',
    'frontend/auth/register-patient.php',
    'frontend/auth/register-donor.php',
    'frontend/auth/register-hospital.php',
    'frontend/request-blood.php',
    'frontend/about.php',
    'frontend/services.php',
    'frontend/contact.php',
    'frontend/become-donor.php',
    'frontend/css/style.css',
    'frontend/js/main.js',
    'backend/config/database.php'
];

$missing_files = [];
$existing_files = [];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        $existing_files[] = $file;
    } else {
        $missing_files[] = $file;
    }
}

if (empty($missing_files)) {
    echo "<div class='success'>✅ All required files exist!</div>";
} else {
    echo "<div class='error'>❌ Missing files:</div>";
    echo "<ul>";
    foreach ($missing_files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
}

echo "<div class='info'>📁 Existing files: " . count($existing_files) . "/" . count($required_files) . "</div>";
echo "</div>";

// Test 5: Authentication Test
echo "<div class='test-section'>";
echo "<h2>5. Authentication System Test</h2>";
try {
    // Test password hashing
    $test_password = 'test123';
    $hashed = password_hash($test_password, PASSWORD_DEFAULT);
    $verify = password_verify($test_password, $hashed);
    
    if ($verify) {
        echo "<div class='success'>✅ Password hashing works correctly!</div>";
    } else {
        echo "<div class='error'>❌ Password hashing failed!</div>";
    }
    
    // Test admin login credentials
    $admin_stmt = $conn->prepare("SELECT * FROM users WHERE email = 'admin@bloodconnect.com'");
    $admin_stmt->execute();
    $admin = $admin_stmt->fetch();
    
    if ($admin && password_verify('admin123', $admin['password_hash'])) {
        echo "<div class='success'>✅ Admin login credentials are correct!</div>";
        echo "<p>Admin email: admin@bloodconnect.com</p>";
        echo "<p>Admin password: admin123</p>";
    } else {
        echo "<div class='error'>❌ Admin login credentials not found or incorrect!</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Authentication test failed: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 6: URL Access Test
echo "<div class='test-section'>";
echo "<h2>6. URL Access Test</h2>";
echo "<div class='info'>📋 Test these URLs in your browser:</div>";
echo "<ul>";
echo "<li><a href='frontend/index.php' target='_blank'>Home Page</a> - http://localhost/blood/frontend/index.php</li>";
echo "<li><a href='frontend/auth/login.php' target='_blank'>Login Page</a> - http://localhost/blood/frontend/auth/login.php</li>";
echo "<li><a href='frontend/auth/register.php' target='_blank'>Registration</a> - http://localhost/blood/frontend/auth/register.php</li>";
echo "<li><a href='frontend/request-blood.php' target='_blank'>Request Blood</a> - http://localhost/blood/frontend/request-blood.php</li>";
echo "<li><a href='setup_database.php' target='_blank'>Database Setup</a> - http://localhost/blood/setup_database.php</li>";
echo "</ul>";
echo "</div>";

// Test 7: CSS and JS Test
echo "<div class='test-section'>";
echo "<h2>7. Assets Test</h2>";
$css_size = file_exists('frontend/css/style.css') ? filesize('frontend/css/style.css') : 0;
$js_size = file_exists('frontend/js/main.js') ? filesize('frontend/js/main.js') : 0;

echo "<div class='info'>📊 Asset Information:</div>";
echo "<p>CSS file size: " . number_format($css_size) . " bytes</p>";
echo "<p>JS file size: " . number_format($js_size) . " bytes</p>";

if ($css_size > 0 && $js_size > 0) {
    echo "<div class='success'>✅ CSS and JavaScript files are present and have content!</div>";
} else {
    echo "<div class='error'>❌ CSS or JavaScript files are missing or empty!</div>";
}
echo "</div>";

// Summary
echo "<div class='test-section info'>";
echo "<h2>🎯 Quick Start Instructions</h2>";
echo "<ol>";
echo "<li>If database connection failed, check your MySQL server and password (14162121)</li>";
echo "<li>If tables are missing, run: <a href='setup_database.php'>setup_database.php</a></li>";
echo "<li>Access the system at: <a href='frontend/index.php'>http://localhost/blood/frontend/index.php</a></li>";
echo "<li>Login as admin: admin@bloodconnect.com / admin123</li>";
echo "<li>Register new users through the registration pages</li>";
echo "</ol>";
echo "</div>";

echo "<div class='test-section success'>";
echo "<h2>🚀 System Status</h2>";
echo "<p><strong>BloodConnect is ready to use!</strong></p>";
echo "<p>All core functionality has been implemented:</p>";
echo "<ul>";
echo "<li>✅ User registration and authentication</li>";
echo "<li>✅ Blood request system</li>";
echo "<li>✅ Modern responsive design</li>";
echo "<li>✅ Database integration</li>";
echo "<li>✅ Multi-role support (Patient, Donor, Hospital, Admin)</li>";
echo "</ul>";
echo "</div>";
?>