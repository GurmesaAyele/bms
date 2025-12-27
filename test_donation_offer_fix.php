<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Offer Fix Test - BloodConnect</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #16a085; background: #d5f4e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; background: #d6eaf8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #b91c1c; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #dc2626; }
        .step h4 { margin-top: 0; color: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🩸 Donation Offer Fix Test</h1>
        
        <?php
        try {
            require_once 'backend/config/database.php';
            echo '<div class="success">✅ Database connected successfully</div>';
            
            // Check if we have the necessary data
            $donor_count = $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'donor'")->fetchColumn();
            $hospital_count = $conn->query("SELECT COUNT(*) FROM hospitals WHERE is_verified = 1 AND is_active = 1")->fetchColumn();
            
            echo "<div class='info'>📊 System Status:</div>";
            echo "<ul>";
            echo "<li>Donor accounts: $donor_count</li>";
            echo "<li>Active hospitals: $hospital_count</li>";
            echo "</ul>";
            
            if ($donor_count == 0) {
                echo '<div class="error">❌ No donor accounts found. You need to register as a donor first.</div>';
            }
            
            if ($hospital_count == 0) {
                echo '<div class="error">❌ No active hospitals found. Run database setup first.</div>';
            }
            
            // Test the donation offer table structure
            echo "<h2>Database Table Check</h2>";
            $columns = $conn->query("SHOW COLUMNS FROM donation_offers")->fetchAll();
            echo "<div class='success'>✅ donation_offers table exists with " . count($columns) . " columns</div>";
            
            // Show recent donation offers
            $recent_offers = $conn->query("SELECT COUNT(*) FROM donation_offers")->fetchColumn();
            echo "<div class='info'>📋 Total donation offers in database: $recent_offers</div>";
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Database error: ' . $e->getMessage() . '</div>';
        }
        ?>
        
        <h2>🔧 Fix Applied</h2>
        <div class="success">
            ✅ Enhanced donation offer submission with:
            <ul>
                <li>Better form validation</li>
                <li>Improved error handling</li>
                <li>Hospital availability check</li>
                <li>JavaScript form validation</li>
                <li>Loading state indicators</li>
            </ul>
        </div>
        
        <h2>🚀 Testing Steps</h2>
        
        <div class="step">
            <h4>Step 1: Setup Database (if needed)</h4>
            <p>Ensure you have sample data:</p>
            <a href="setup_database.php" class="btn">Setup Database</a>
        </div>
        
        <div class="step">
            <h4>Step 2: Register as Donor</h4>
            <p>Create a donor account if you don't have one:</p>
            <a href="frontend/auth/register-donor.php" class="btn">Register Donor</a>
        </div>
        
        <div class="step">
            <h4>Step 3: Login as Donor</h4>
            <p>Login with your donor credentials:</p>
            <a href="frontend/auth/login.php" class="btn">Login</a>
        </div>
        
        <div class="step">
            <h4>Step 4: Test Donation Offer</h4>
            <p>Go to donor dashboard and submit a donation offer:</p>
            <a href="frontend/dashboard/donor.php" class="btn">Donor Dashboard</a>
        </div>
        
        <h2>🐛 Debug Tools</h2>
        <div class="info">
            <p>If you still get errors, use these debug tools:</p>
            <a href="debug_donation_offer.php" class="btn">Debug Donation Offers</a>
        </div>
        
        <h2>📋 Common Issues & Solutions</h2>
        <div class="step">
            <h4>Issue: "Failed to submit offer"</h4>
            <p><strong>Possible causes:</strong></p>
            <ul>
                <li>No hospitals available (run database setup)</li>
                <li>Donor not eligible (check donor profile)</li>
                <li>Invalid form data (check all required fields)</li>
                <li>Database connection issues</li>
            </ul>
        </div>
        
        <div class="step">
            <h4>Issue: Form validation errors</h4>
            <p><strong>Make sure to:</strong></p>
            <ul>
                <li>Select a hospital from the dropdown</li>
                <li>Choose a future date (not today or past)</li>
                <li>Select a preferred time</li>
                <li>Ensure you're logged in as an eligible donor</li>
            </ul>
        </div>
        
        <h3>🔗 Quick Links</h3>
        <div style="margin: 20px 0;">
            <a href="setup_database.php" class="btn">Setup Database</a>
            <a href="frontend/auth/register-donor.php" class="btn">Register Donor</a>
            <a href="frontend/auth/login.php" class="btn">Login</a>
            <a href="frontend/dashboard/donor.php" class="btn">Donor Dashboard</a>
            <a href="debug_donation_offer.php" class="btn">Debug Tool</a>
        </div>
    </div>
</body>
</html>