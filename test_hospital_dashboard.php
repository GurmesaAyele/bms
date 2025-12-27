<?php
/**
 * Test Hospital Dashboard Access
 * This file tests if the hospital dashboard is accessible and working
 */

echo "<h2>Testing Hospital Dashboard Access</h2>";

// Test 1: Check if hospital dashboard file exists
$hospital_dashboard_path = 'frontend/dashboard/hospital.php';
if (file_exists($hospital_dashboard_path)) {
    echo "<p>✅ Hospital dashboard file exists at: $hospital_dashboard_path</p>";
} else {
    echo "<p>❌ Hospital dashboard file NOT found at: $hospital_dashboard_path</p>";
}

// Test 2: Check database connection
try {
    require_once 'backend/config/database.php';
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 3: Check if we can access the hospital dashboard URL
$dashboard_url = "http://localhost/blood/frontend/dashboard/hospital.php";
echo "<p>🔗 Hospital Dashboard URL: <a href='$dashboard_url' target='_blank'>$dashboard_url</a></p>";

// Test 4: Check session and authentication requirements
echo "<h3>Authentication Test</h3>";
echo "<p>The hospital dashboard requires:</p>";
echo "<ul>";
echo "<li>Active session with user_id</li>";
echo "<li>User type must be 'hospital'</li>";
echo "<li>Valid hospital record in database</li>";
echo "</ul>";

// Test 5: Check if we have any hospital users in the database
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as hospital_count FROM users WHERE user_type = 'hospital'");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>📊 Hospital users in database: " . $result['hospital_count'] . "</p>";
    
    if ($result['hospital_count'] > 0) {
        $stmt = $conn->prepare("SELECT u.id, u.email, h.hospital_name, h.is_verified FROM users u JOIN hospitals h ON u.id = h.user_id WHERE u.user_type = 'hospital' LIMIT 5");
        $stmt->execute();
        $hospitals = $stmt->fetchAll();
        
        echo "<h4>Sample Hospital Accounts:</h4>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Hospital Name</th><th>Verified</th></tr>";
        foreach ($hospitals as $hospital) {
            $verified = $hospital['is_verified'] ? 'Yes' : 'No';
            echo "<tr><td>{$hospital['id']}</td><td>{$hospital['email']}</td><td>{$hospital['hospital_name']}</td><td>$verified</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking hospital users: " . $e->getMessage() . "</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Register a hospital account at: <a href='http://localhost/blood/frontend/auth/register-hospital.php'>Register Hospital</a></li>";
echo "<li>Login with hospital credentials at: <a href='http://localhost/blood/frontend/auth/login.php'>Login</a></li>";
echo "<li>Access hospital dashboard (will redirect to login if not authenticated)</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>File Structure Check:</strong></p>";
echo "<ul>";
$files_to_check = [
    'frontend/dashboard/hospital.php',
    'frontend/auth/login.php',
    'frontend/auth/register-hospital.php',
    'backend/config/database.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<li>✅ $file</li>";
    } else {
        echo "<li>❌ $file (MISSING)</li>";
    }
}
echo "</ul>";
?>