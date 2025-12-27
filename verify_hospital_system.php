<?php
/**
 * Complete Hospital System Verification
 * This script verifies that the hospital dashboard system is working correctly
 */

echo "<h1>🏥 Hospital System Verification</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    require_once 'backend/config/database.php';
    echo "✅ Database connected successfully<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: File Existence
echo "<h2>2. File Existence Test</h2>";
$required_files = [
    'frontend/dashboard/hospital.php' => 'Hospital Dashboard',
    'frontend/auth/login.php' => 'Login Page',
    'frontend/auth/register-hospital.php' => 'Hospital Registration',
    'backend/config/database.php' => 'Database Config'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description: $file<br>";
    } else {
        echo "❌ $description: $file (MISSING)<br>";
    }
}

// Test 3: Database Tables
echo "<h2>3. Database Tables Test</h2>";
$required_tables = ['users', 'hospitals', 'blood_requests', 'donation_offers', 'blood_inventory'];
foreach ($required_tables as $table) {
    try {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error checking table '$table': " . $e->getMessage() . "<br>";
    }
}

// Test 4: Hospital Accounts
echo "<h2>4. Hospital Accounts Test</h2>";
try {
    $stmt = $conn->prepare("
        SELECT u.id, u.email, h.hospital_name, h.hospital_id, h.is_verified, h.is_active
        FROM users u 
        JOIN hospitals h ON u.id = h.user_id 
        WHERE u.user_type = 'hospital'
    ");
    $stmt->execute();
    $hospitals = $stmt->fetchAll();
    
    if (count($hospitals) > 0) {
        echo "✅ Found " . count($hospitals) . " hospital account(s)<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Email</th><th>Hospital Name</th><th>Hospital ID</th><th>Verified</th><th>Active</th></tr>";
        foreach ($hospitals as $hospital) {
            $verified = $hospital['is_verified'] ? 'Yes' : 'No';
            $active = $hospital['is_active'] ? 'Yes' : 'No';
            echo "<tr>";
            echo "<td>{$hospital['email']}</td>";
            echo "<td>{$hospital['hospital_name']}</td>";
            echo "<td>{$hospital['hospital_id']}</td>";
            echo "<td>$verified</td>";
            echo "<td>$active</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No hospital accounts found<br>";
        echo "<p>Run <a href='setup_database.php'>setup_database.php</a> to create sample accounts</p>";
    }
} catch (Exception $e) {
    echo "❌ Error checking hospital accounts: " . $e->getMessage() . "<br>";
}

// Test 5: Session Test
echo "<h2>5. Session Test</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "✅ User is logged in (ID: {$_SESSION['user_id']}, Type: {$_SESSION['user_type']})<br>";
    if ($_SESSION['user_type'] === 'hospital') {
        echo "✅ User is a hospital - can access hospital dashboard<br>";
        echo "<p><a href='frontend/dashboard/hospital.php' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Hospital Dashboard</a></p>";
    } else {
        echo "ℹ️ User is not a hospital (Type: {$_SESSION['user_type']})<br>";
    }
} else {
    echo "ℹ️ No user logged in<br>";
    echo "<p>You need to login first: <a href='frontend/auth/login.php'>Login Here</a></p>";
}

// Test 6: URL Test
echo "<h2>6. URL Access Test</h2>";
$base_url = "http://localhost/blood/";
echo "<p>Your BloodConnect system should be accessible at:</p>";
echo "<ul>";
echo "<li><a href='{$base_url}frontend/index.php' target='_blank'>Home Page</a></li>";
echo "<li><a href='{$base_url}frontend/auth/login.php' target='_blank'>Login Page</a></li>";
echo "<li><a href='{$base_url}frontend/auth/register-hospital.php' target='_blank'>Hospital Registration</a></li>";
echo "<li><a href='{$base_url}frontend/dashboard/hospital.php' target='_blank'>Hospital Dashboard</a> (requires login)</li>";
echo "</ul>";

echo "<h2>🎯 Quick Fix Instructions</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #dc2626; margin: 20px 0;'>";
echo "<h3>To access the Hospital Dashboard:</h3>";
echo "<ol>";
echo "<li><strong>Setup Database:</strong> <a href='setup_database.php'>Run Database Setup</a></li>";
echo "<li><strong>Login:</strong> Go to <a href='frontend/auth/login.php'>Login Page</a></li>";
echo "<li><strong>Use Credentials:</strong> city.general@hospital.com / hospital123</li>";
echo "<li><strong>Access Dashboard:</strong> You'll be redirected automatically</li>";
echo "</ol>";
echo "</div>";

echo "<h2>📋 Test Summary</h2>";
echo "<p>If all tests above show ✅, your hospital dashboard should work correctly.</p>";
echo "<p>The 'Not Found' error occurs when:</p>";
echo "<ul>";
echo "<li>You're not logged in as a hospital user</li>";
echo "<li>The URL path is incorrect</li>";
echo "<li>WAMP server is not running</li>";
echo "</ul>";
?>