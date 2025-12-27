<?php
require_once 'backend/config/database.php';

echo "<h2>Available User Accounts</h2>";

try {
    // Get all users
    $stmt = $conn->query("
        SELECT u.id, u.email, u.user_type, u.first_name, u.last_name, 
               p.id as patient_id, d.id as donor_id, h.id as hospital_id
        FROM users u 
        LEFT JOIN patients p ON u.id = p.user_id 
        LEFT JOIN donors d ON u.id = d.user_id 
        LEFT JOIN hospitals h ON u.id = h.user_id 
        ORDER BY u.user_type, u.id
    ");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p>No users found. Please run setup_database.php first.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Type</th><th>Name</th><th>Profile ID</th><th>Login Link</th></tr>";
        
        foreach ($users as $user) {
            $profile_id = '';
            if ($user['patient_id']) $profile_id = "Patient: {$user['patient_id']}";
            if ($user['donor_id']) $profile_id = "Donor: {$user['donor_id']}";
            if ($user['hospital_id']) $profile_id = "Hospital: {$user['hospital_id']}";
            
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['user_type']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>{$profile_id}</td>";
            echo "<td><a href='frontend/auth/login.php'>Login</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Test Credentials:</h3>";
        echo "<p><strong>Patient:</strong> sam@gmail.com (if exists) or patient@test.com / test123</p>";
        echo "<p><strong>Admin:</strong> admin@bloodconnect.com / admin123</p>";
        echo "<p><strong>Hospital:</strong> city.general@hospital.com / hospital123</p>";
    }
    
    // Check current session
    session_start();
    if (isset($_SESSION['user_id'])) {
        echo "<h3>Current Session:</h3>";
        echo "<p>User ID: {$_SESSION['user_id']}</p>";
        echo "<p>User Type: {$_SESSION['user_type']}</p>";
        echo "<p><a href='frontend/request-blood.php'>Go to Blood Request Form</a></p>";
    } else {
        echo "<h3>Not logged in</h3>";
        echo "<p><a href='frontend/auth/login.php'>Login Here</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>