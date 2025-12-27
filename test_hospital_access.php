<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard Access Test - BloodConnect</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #16a085; background: #d5f4e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; background: #d6eaf8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #b91c1c; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #dc2626; }
        .step h4 { margin-top: 0; color: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 Hospital Dashboard Access Test</h1>
        
        <?php
        // Test database connection
        try {
            require_once 'backend/config/database.php';
            echo '<div class="success">✅ Database connection successful</div>';
            
            // Check if hospital dashboard file exists
            if (file_exists('frontend/dashboard/hospital.php')) {
                echo '<div class="success">✅ Hospital dashboard file exists</div>';
            } else {
                echo '<div class="error">❌ Hospital dashboard file missing</div>';
            }
            
            // Check for hospital users
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'hospital'");
            $stmt->execute();
            $hospital_count = $stmt->fetch()['count'];
            
            if ($hospital_count > 0) {
                echo "<div class='success'>✅ Found $hospital_count hospital account(s) in database</div>";
                
                // Show hospital accounts
                $stmt = $conn->prepare("
                    SELECT u.id, u.email, u.first_name, u.last_name, h.hospital_name, h.hospital_id, h.is_verified 
                    FROM users u 
                    JOIN hospitals h ON u.id = h.user_id 
                    WHERE u.user_type = 'hospital'
                    ORDER BY u.id
                ");
                $stmt->execute();
                $hospitals = $stmt->fetchAll();
                
                echo '<h3>Available Hospital Accounts:</h3>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Email</th><th>Hospital Name</th><th>Hospital ID</th><th>Verified</th><th>Action</th></tr>';
                foreach ($hospitals as $hospital) {
                    $verified = $hospital['is_verified'] ? '✅ Yes' : '❌ No';
                    echo "<tr>";
                    echo "<td>{$hospital['id']}</td>";
                    echo "<td>{$hospital['email']}</td>";
                    echo "<td>{$hospital['hospital_name']}</td>";
                    echo "<td>{$hospital['hospital_id']}</td>";
                    echo "<td>$verified</td>";
                    echo "<td><a href='frontend/auth/login.php' class='btn btn-secondary'>Login</a></td>";
                    echo "</tr>";
                }
                echo '</table>';
                
                echo '<div class="info">💡 Use any of the above email addresses with password: <strong>hospital123</strong></div>';
                
            } else {
                echo '<div class="error">❌ No hospital accounts found in database</div>';
                echo '<div class="info">You need to run the database setup first or register a hospital account.</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Database error: ' . $e->getMessage() . '</div>';
        }
        ?>
        
        <h2>🚀 Quick Access Steps</h2>
        
        <div class="step">
            <h4>Step 1: Setup Database (if not done)</h4>
            <p>Run the database setup to create sample hospital accounts:</p>
            <a href="setup_database.php" class="btn">Setup Database</a>
        </div>
        
        <div class="step">
            <h4>Step 2: Login as Hospital</h4>
            <p>Use one of the sample hospital accounts or register a new one:</p>
            <a href="frontend/auth/login.php" class="btn">Login</a>
            <a href="frontend/auth/register-hospital.php" class="btn btn-secondary">Register New Hospital</a>
        </div>
        
        <div class="step">
            <h4>Step 3: Access Hospital Dashboard</h4>
            <p>After logging in, you can access the hospital dashboard:</p>
            <a href="frontend/dashboard/hospital.php" class="btn">Hospital Dashboard</a>
        </div>
        
        <h3>🔗 Quick Links</h3>
        <div style="margin: 20px 0;">
            <a href="frontend/index.php" class="btn">Home Page</a>
            <a href="frontend/auth/login.php" class="btn">Login</a>
            <a href="frontend/auth/register-hospital.php" class="btn btn-secondary">Register Hospital</a>
            <a href="frontend/dashboard/hospital.php" class="btn btn-secondary">Hospital Dashboard</a>
        </div>
        
        <div class="info">
            <h4>📋 Sample Login Credentials:</h4>
            <p><strong>Hospital 1:</strong> city.general@hospital.com / hospital123</p>
            <p><strong>Hospital 2:</strong> metro.medical@hospital.com / hospital123</p>
            <p><strong>Admin:</strong> admin@bloodconnect.com / admin123</p>
        </div>
        
        <h3>🔍 Troubleshooting</h3>
        <div class="info">
            <p><strong>If you get "Not Found" error:</strong></p>
            <ul>
                <li>Make sure you're accessing: <code>http://localhost/blood/frontend/dashboard/hospital.php</code></li>
                <li>Ensure you're logged in as a hospital user</li>
                <li>Check that your WAMP server is running</li>
                <li>Verify the file exists in the correct directory</li>
            </ul>
        </div>
    </div>
</body>
</html>