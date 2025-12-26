<?php
/**
 * Database Connection Test for BloodConnect
 * Test your database connection and view data
 */

$servername = "localhost";
$username = "root";
$password = "14162121";
$database = "bloodconnect";

echo "<h1>🩸 BloodConnect Database Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #10b981; }
    .error { color: #ef4444; }
    .info { color: #3b82f6; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f8f9fa; }
    .section { margin: 20px 0; padding: 15px; border-left: 4px solid #3b82f6; background: #f8f9fa; }
</style>";

echo "<div class='container'>";

try {
    // Test database connection
    echo "<div class='section'>";
    echo "<h2>🔌 Database Connection Test</h2>";
    
    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✅ Successfully connected to database '$database'</p>";
    echo "<p class='info'>Host: $servername | User: $username</p>";
    echo "</div>";
    
    // Check tables
    echo "<div class='section'>";
    echo "<h2>📋 Database Tables</h2>";
    
    $tables_stmt = $conn->query("SHOW TABLES");
    $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p class='error'>❌ No tables found. Please run setup_database.php first.</p>";
        echo "<a href='setup_database.php' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Setup Database</a>";
    } else {
        echo "<p class='success'>✅ Found " . count($tables) . " tables:</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            // Get row count for each table
            $count_stmt = $conn->query("SELECT COUNT(*) FROM `$table`");
            $count = $count_stmt->fetchColumn();
            echo "<li><strong>$table</strong> - $count records</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    // Check users
    if (in_array('users', $tables)) {
        echo "<div class='section'>";
        echo "<h2>👥 Users in System</h2>";
        
        $users_stmt = $conn->query("
            SELECT user_type, COUNT(*) as count, 
                   SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
            FROM users 
            GROUP BY user_type
        ");
        $user_stats = $users_stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>User Type</th><th>Total</th><th>Active</th></tr>";
        foreach ($user_stats as $stat) {
            echo "<tr>";
            echo "<td>" . ucfirst($stat['user_type']) . "</td>";
            echo "<td>" . $stat['count'] . "</td>";
            echo "<td>" . $stat['active_count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        // Show recent users
        echo "<div class='section'>";
        echo "<h2>🆕 Recent Users (Last 10)</h2>";
        
        $recent_stmt = $conn->query("
            SELECT first_name, last_name, email, user_type, created_at, is_active
            FROM users 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $recent_users = $recent_stmt->fetchAll();
        
        if (empty($recent_users)) {
            echo "<p class='info'>No users found. Register some users to see them here.</p>";
        } else {
            echo "<table>";
            echo "<tr><th>Name</th><th>Email</th><th>Type</th><th>Registered</th><th>Status</th></tr>";
            foreach ($recent_users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . ucfirst($user['user_type']) . "</td>";
                echo "<td>" . date('M j, Y g:i A', strtotime($user['created_at'])) . "</td>";
                echo "<td>" . ($user['is_active'] ? '<span class="success">Active</span>' : '<span class="error">Inactive</span>') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "</div>";
    }
    
    // Check hospitals
    if (in_array('hospitals', $tables)) {
        echo "<div class='section'>";
        echo "<h2>🏥 Hospitals Status</h2>";
        
        $hospitals_stmt = $conn->query("
            SELECT h.hospital_name, h.hospital_type, h.is_verified, h.is_active, u.email, h.created_at
            FROM hospitals h
            JOIN users u ON h.user_id = u.id
            ORDER BY h.created_at DESC
            LIMIT 10
        ");
        $hospitals = $hospitals_stmt->fetchAll();
        
        if (empty($hospitals)) {
            echo "<p class='info'>No hospitals registered yet.</p>";
        } else {
            echo "<table>";
            echo "<tr><th>Hospital Name</th><th>Type</th><th>Email</th><th>Verified</th><th>Active</th><th>Registered</th></tr>";
            foreach ($hospitals as $hospital) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($hospital['hospital_name']) . "</td>";
                echo "<td>" . ucfirst($hospital['hospital_type']) . "</td>";
                echo "<td>" . htmlspecialchars($hospital['email']) . "</td>";
                echo "<td>" . ($hospital['is_verified'] ? '<span class="success">Yes</span>' : '<span class="error">No</span>') . "</td>";
                echo "<td>" . ($hospital['is_active'] ? '<span class="success">Yes</span>' : '<span class="error">No</span>') . "</td>";
                echo "<td>" . date('M j, Y', strtotime($hospital['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "</div>";
    }
    
    // Check blood requests
    if (in_array('blood_requests', $tables)) {
        echo "<div class='section'>";
        echo "<h2>🩸 Blood Requests</h2>";
        
        $requests_stmt = $conn->query("
            SELECT status, COUNT(*) as count
            FROM blood_requests
            GROUP BY status
        ");
        $request_stats = $requests_stmt->fetchAll();
        
        if (empty($request_stats)) {
            echo "<p class='info'>No blood requests yet.</p>";
        } else {
            echo "<table>";
            echo "<tr><th>Status</th><th>Count</th></tr>";
            foreach ($request_stats as $stat) {
                echo "<tr>";
                echo "<td>" . ucfirst($stat['status']) . "</td>";
                echo "<td>" . $stat['count'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "</div>";
    }
    
    // Test credentials section
    echo "<div class='section'>";
    echo "<h2>🔑 Test Credentials</h2>";
    echo "<p><strong>Admin Login:</strong></p>";
    echo "<ul>";
    echo "<li>Email: admin@bloodconnect.com</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    
    // Check if admin exists
    $admin_stmt = $conn->prepare("SELECT * FROM users WHERE email = 'admin@bloodconnect.com'");
    $admin_stmt->execute();
    $admin = $admin_stmt->fetch();
    
    if ($admin) {
        echo "<p class='success'>✅ Admin user exists and ready to use</p>";
    } else {
        echo "<p class='error'>❌ Admin user not found. Please run setup_database.php</p>";
    }
    echo "</div>";
    
    // Quick links
    echo "<div class='section'>";
    echo "<h2>🔗 Quick Links</h2>";
    echo "<p><a href='frontend/index.php' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Website</a>";
    echo "<a href='frontend/auth/login.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Login Page</a>";
    echo "<a href='setup_database.php' style='background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Setup Database</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ Database Connection Failed</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure your web server (XAMPP/WAMP/MAMP) is running</li>";
    echo "<li>Check that MySQL service is started</li>";
    echo "<li>Verify your MySQL password is: 14162121</li>";
    echo "<li>Make sure the 'bloodconnect' database exists</li>";
    echo "</ul>";
    echo "<p><a href='setup_database.php' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Database Setup</a></p>";
    echo "</div>";
}

echo "</div>";
?>