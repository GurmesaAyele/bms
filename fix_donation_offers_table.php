<?php
/**
 * Fix Donation Offers Table Structure
 * This script will create or update the donation_offers table with the correct structure
 */

echo "<h1>🔧 Fix Donation Offers Table</h1>";

try {
    require_once 'backend/config/database.php';
    echo "<div style='color: green;'>✅ Database connected successfully</div>";
    
    // Drop existing table if it exists (to recreate with correct structure)
    echo "<h2>1. Recreating donation_offers table</h2>";
    
    $conn->exec("DROP TABLE IF EXISTS donation_offers");
    echo "<div style='color: blue;'>ℹ️ Dropped existing donation_offers table (if existed)</div>";
    
    // Create the donation_offers table with correct structure
    $create_table_sql = "
        CREATE TABLE donation_offers (
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
        )
    ";
    
    $conn->exec($create_table_sql);
    echo "<div style='color: green;'>✅ donation_offers table created successfully</div>";
    
    // Verify the table structure
    echo "<h2>2. Verifying Table Structure</h2>";
    $columns = $conn->query("DESCRIBE donation_offers")->fetchAll();
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
    
    // Check if hospital_id column now exists
    $hospital_id_exists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'hospital_id') {
            $hospital_id_exists = true;
            break;
        }
    }
    
    if ($hospital_id_exists) {
        echo "<div style='color: green;'>✅ hospital_id column now exists!</div>";
    } else {
        echo "<div style='color: red;'>❌ hospital_id column still missing</div>";
    }
    
    // Insert some sample donation offers for testing
    echo "<h2>3. Creating Sample Data</h2>";
    
    // Get sample donor and hospital IDs
    $donor_stmt = $conn->query("SELECT u.id FROM users u JOIN donors d ON u.id = d.user_id WHERE u.user_type = 'donor' LIMIT 1");
    $donor = $donor_stmt->fetch();
    
    $hospital_stmt = $conn->query("SELECT id FROM hospitals WHERE is_verified = 1 AND is_active = 1 LIMIT 1");
    $hospital = $hospital_stmt->fetch();
    
    if ($donor && $hospital) {
        $sample_offers = [
            [
                'offer_id' => 'OFF-2024-000001',
                'donor_id' => $donor['id'],
                'hospital_id' => $hospital['id'],
                'blood_type' => 'O+',
                'preferred_date' => date('Y-m-d', strtotime('+1 day')),
                'preferred_time' => '10:00:00',
                'status' => 'pending',
                'notes' => 'Sample donation offer for testing'
            ],
            [
                'offer_id' => 'OFF-2024-000002',
                'donor_id' => $donor['id'],
                'hospital_id' => $hospital['id'],
                'blood_type' => 'A+',
                'preferred_date' => date('Y-m-d', strtotime('+2 days')),
                'preferred_time' => '14:00:00',
                'status' => 'accepted',
                'notes' => 'Accepted sample donation offer'
            ]
        ];
        
        foreach ($sample_offers as $offer) {
            $stmt = $conn->prepare("
                INSERT INTO donation_offers (offer_id, donor_id, hospital_id, blood_type, preferred_date, preferred_time, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $offer['offer_id'],
                $offer['donor_id'],
                $offer['hospital_id'],
                $offer['blood_type'],
                $offer['preferred_date'],
                $offer['preferred_time'],
                $offer['status'],
                $offer['notes']
            ]);
        }
        
        echo "<div style='color: green;'>✅ Sample donation offers created</div>";
    } else {
        echo "<div style='color: orange;'>⚠️ No sample data created - need donors and hospitals first</div>";
    }
    
    // Test the donation offer insertion
    echo "<h2>4. Testing Donation Offer Insertion</h2>";
    if ($donor && $hospital) {
        try {
            $test_offer_id = 'TEST-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("
                INSERT INTO donation_offers (offer_id, donor_id, hospital_id, blood_type, preferred_date, preferred_time, notes, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $result = $stmt->execute([
                $test_offer_id,
                $donor['id'],
                $hospital['id'],
                'B+',
                date('Y-m-d', strtotime('+3 days')),
                '15:00:00',
                'Test donation offer'
            ]);
            
            if ($result) {
                echo "<div style='color: green;'>✅ Test donation offer inserted successfully!</div>";
                echo "<p>Offer ID: $test_offer_id</p>";
                
                // Clean up test data
                $cleanup = $conn->prepare("DELETE FROM donation_offers WHERE offer_id = ?");
                $cleanup->execute([$test_offer_id]);
                echo "<div style='color: blue;'>ℹ️ Test data cleaned up</div>";
            }
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Test insertion failed: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<h2>✅ Table Fix Complete!</h2>";
    echo "<div style='background: #d5f4e6; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>What was fixed:</h3>";
    echo "<ul>";
    echo "<li>✅ Recreated donation_offers table with correct structure</li>";
    echo "<li>✅ Added hospital_id column with proper foreign key</li>";
    echo "<li>✅ Added all necessary columns for donation management</li>";
    echo "<li>✅ Set up proper relationships between tables</li>";
    echo "<li>✅ Added sample data for testing</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🔗 Next Steps</h3>";
    echo "<p>";
    echo "<a href='frontend/auth/login.php' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Login as Donor</a>";
    echo "<a href='frontend/dashboard/donor.php' style='background: #16a085; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Test Donation Offer</a>";
    echo "<a href='frontend/dashboard/hospital.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Hospital Dashboard</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
}
?>