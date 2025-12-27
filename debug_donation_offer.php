<?php
/**
 * Debug Donation Offer Submission
 * This script helps identify why donation offers are failing
 */

echo "<h1>🩸 Donation Offer Debug</h1>";

// Test database connection
try {
    require_once 'backend/config/database.php';
    echo "<div style='color: green;'>✅ Database connected successfully</div>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}

// Test 1: Check donation_offers table structure
echo "<h2>1. Table Structure Check</h2>";
try {
    $stmt = $conn->prepare("DESCRIBE donation_offers");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking table structure: " . $e->getMessage() . "</div>";
}

// Test 2: Check if we have donor users
echo "<h2>2. Donor Users Check</h2>";
try {
    $stmt = $conn->prepare("
        SELECT u.id, u.email, u.first_name, u.last_name, d.donor_id, d.blood_type, d.is_eligible
        FROM users u 
        JOIN donors d ON u.id = d.user_id 
        WHERE u.user_type = 'donor'
        LIMIT 5
    ");
    $stmt->execute();
    $donors = $stmt->fetchAll();
    
    if (count($donors) > 0) {
        echo "<div style='color: green;'>✅ Found " . count($donors) . " donor(s)</div>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Donor ID</th><th>Blood Type</th><th>Eligible</th></tr>";
        foreach ($donors as $donor) {
            $eligible = $donor['is_eligible'] ? 'Yes' : 'No';
            echo "<tr>";
            echo "<td>{$donor['id']}</td>";
            echo "<td>{$donor['email']}</td>";
            echo "<td>{$donor['first_name']} {$donor['last_name']}</td>";
            echo "<td>{$donor['donor_id']}</td>";
            echo "<td>{$donor['blood_type']}</td>";
            echo "<td>$eligible</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='color: red;'>❌ No donor users found</div>";
        echo "<p>You need to register as a donor first: <a href='frontend/auth/register-donor.php'>Register Donor</a></p>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking donors: " . $e->getMessage() . "</div>";
}

// Test 3: Check hospitals
echo "<h2>3. Available Hospitals Check</h2>";
try {
    $stmt = $conn->prepare("
        SELECT h.id, h.hospital_name, h.is_verified, h.is_active, h.has_blood_bank
        FROM hospitals h
        WHERE h.is_verified = 1 AND h.is_active = 1 AND h.has_blood_bank = 1
        LIMIT 5
    ");
    $stmt->execute();
    $hospitals = $stmt->fetchAll();
    
    if (count($hospitals) > 0) {
        echo "<div style='color: green;'>✅ Found " . count($hospitals) . " available hospital(s)</div>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Hospital Name</th><th>Verified</th><th>Active</th><th>Blood Bank</th></tr>";
        foreach ($hospitals as $hospital) {
            echo "<tr>";
            echo "<td>{$hospital['id']}</td>";
            echo "<td>{$hospital['hospital_name']}</td>";
            echo "<td>" . ($hospital['is_verified'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($hospital['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($hospital['has_blood_bank'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='color: red;'>❌ No available hospitals found</div>";
        echo "<p>Run <a href='setup_database.php'>Database Setup</a> to create sample hospitals</p>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking hospitals: " . $e->getMessage() . "</div>";
}

// Test 4: Simulate donation offer insertion
echo "<h2>4. Donation Offer Insertion Test</h2>";
try {
    // Get first donor and hospital for testing
    $donor_stmt = $conn->prepare("SELECT u.id, d.blood_type FROM users u JOIN donors d ON u.id = d.user_id WHERE u.user_type = 'donor' LIMIT 1");
    $donor_stmt->execute();
    $donor = $donor_stmt->fetch();
    
    $hospital_stmt = $conn->prepare("SELECT id FROM hospitals WHERE is_verified = 1 AND is_active = 1 LIMIT 1");
    $hospital_stmt->execute();
    $hospital = $hospital_stmt->fetch();
    
    if ($donor && $hospital) {
        echo "<div style='color: blue;'>ℹ️ Testing with Donor ID: {$donor['id']}, Hospital ID: {$hospital['id']}</div>";
        
        $offer_id = 'TEST-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $test_date = date('Y-m-d', strtotime('+1 day'));
        $test_time = '10:00:00';
        
        // Try the exact same query as in the donor dashboard
        $stmt = $conn->prepare("
            INSERT INTO donation_offers (offer_id, donor_id, hospital_id, blood_type, preferred_date, 
                                       preferred_time, notes, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $result = $stmt->execute([
            $offer_id,
            $donor['id'],
            $hospital['id'],
            $donor['blood_type'],
            $test_date,
            $test_time,
            'Test donation offer'
        ]);
        
        if ($result) {
            echo "<div style='color: green;'>✅ Test donation offer inserted successfully!</div>";
            echo "<p>Offer ID: $offer_id</p>";
            
            // Clean up test data
            $cleanup = $conn->prepare("DELETE FROM donation_offers WHERE offer_id = ?");
            $cleanup->execute([$offer_id]);
            echo "<div style='color: blue;'>ℹ️ Test data cleaned up</div>";
        } else {
            echo "<div style='color: red;'>❌ Failed to insert test donation offer</div>";
        }
        
    } else {
        echo "<div style='color: red;'>❌ Cannot test - missing donor or hospital data</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error during insertion test: " . $e->getMessage() . "</div>";
    echo "<p><strong>Error Details:</strong></p>";
    echo "<pre>" . print_r($e, true) . "</pre>";
}

// Test 5: Check existing donation offers
echo "<h2>5. Existing Donation Offers</h2>";
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donation_offers");
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    
    echo "<div style='color: blue;'>ℹ️ Total donation offers in database: $count</div>";
    
    if ($count > 0) {
        $stmt = $conn->prepare("
            SELECT do.*, h.hospital_name 
            FROM donation_offers do 
            LEFT JOIN hospitals h ON do.hospital_id = h.id 
            ORDER BY do.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $offers = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Offer ID</th><th>Donor ID</th><th>Hospital</th><th>Blood Type</th><th>Status</th><th>Created</th></tr>";
        foreach ($offers as $offer) {
            echo "<tr>";
            echo "<td>{$offer['offer_id']}</td>";
            echo "<td>{$offer['donor_id']}</td>";
            echo "<td>{$offer['hospital_name']}</td>";
            echo "<td>{$offer['blood_type']}</td>";
            echo "<td>{$offer['status']}</td>";
            echo "<td>{$offer['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking existing offers: " . $e->getMessage() . "</div>";
}

echo "<h2>🎯 Quick Fix Instructions</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #dc2626; margin: 20px 0;'>";
echo "<h3>To fix donation offer submission:</h3>";
echo "<ol>";
echo "<li><strong>Ensure Database Setup:</strong> <a href='setup_database.php'>Run Database Setup</a></li>";
echo "<li><strong>Register as Donor:</strong> <a href='frontend/auth/register-donor.php'>Register Donor Account</a></li>";
echo "<li><strong>Login as Donor:</strong> <a href='frontend/auth/login.php'>Login</a></li>";
echo "<li><strong>Submit Offer:</strong> Go to donor dashboard and try again</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🔗 Quick Links</h3>";
echo "<p>";
echo "<a href='setup_database.php' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Setup Database</a>";
echo "<a href='frontend/auth/register-donor.php' style='background: #16a085; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Register Donor</a>";
echo "<a href='frontend/auth/login.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Login</a>";
echo "<a href='frontend/dashboard/donor.php' style='background: #e67e22; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Donor Dashboard</a>";
echo "</p>";
?>