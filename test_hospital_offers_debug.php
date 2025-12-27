<?php
session_start();

// Simulate hospital login for testing
$_SESSION['user_id'] = 7; // Hospital user ID from debug
$_SESSION['user_type'] = 'hospital';

require_once 'backend/config/database.php';

echo "<h1>🐛 Hospital Offers Debug Test</h1>";

try {
    // Get hospital data
    $stmt = $conn->prepare("
        SELECT u.*, h.hospital_id, h.hospital_name, h.id as hospital_db_id
        FROM users u 
        JOIN hospitals h ON u.id = h.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $hospital_data = $stmt->fetch();
    
    if ($hospital_data) {
        $hospital_db_id = $hospital_data['hospital_db_id'];
        echo "<p><strong>Hospital:</strong> {$hospital_data['hospital_name']} (DB ID: $hospital_db_id)</p>";
        
        // Test the exact query from hospital dashboard
        echo "<h2>Testing Hospital Dashboard Query</h2>";
        $offers_stmt = $conn->prepare("
            SELECT do.*, u.first_name, u.last_name, u.email, u.phone, d.donor_id, d.blood_type as donor_blood_type
            FROM donation_offers do
            JOIN users u ON do.donor_id = u.id
            LEFT JOIN donors d ON u.id = d.user_id
            WHERE do.hospital_id = ?
            ORDER BY do.created_at DESC
            LIMIT 10
        ");
        $offers_stmt->execute([$hospital_db_id]);
        $donation_offers = $offers_stmt->fetchAll();
        
        echo "<p><strong>Query Result:</strong> Found " . count($donation_offers) . " offers</p>";
        
        if (count($donation_offers) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Offer ID</th><th>Donor</th><th>Blood Type</th><th>Status</th><th>Date</th></tr>";
            foreach ($donation_offers as $offer) {
                echo "<tr>";
                echo "<td>{$offer['offer_id']}</td>";
                echo "<td>{$offer['first_name']} {$offer['last_name']}</td>";
                echo "<td>{$offer['donor_blood_type']}</td>";
                echo "<td>{$offer['status']}</td>";
                echo "<td>{$offer['preferred_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ No offers found with the dashboard query</p>";
            
            // Let's try a simpler query to see what's wrong
            echo "<h3>Testing Simpler Query</h3>";
            $simple_stmt = $conn->prepare("SELECT * FROM donation_offers WHERE hospital_id = ?");
            $simple_stmt->execute([$hospital_db_id]);
            $simple_offers = $simple_stmt->fetchAll();
            
            echo "<p>Simple query result: " . count($simple_offers) . " offers</p>";
            
            if (count($simple_offers) > 0) {
                echo "<p style='color: orange;'>⚠️ Offers exist but JOIN is failing</p>";
                echo "<h4>Raw Donation Offers:</h4>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Offer ID</th><th>Donor ID</th><th>Hospital ID</th><th>Status</th></tr>";
                foreach ($simple_offers as $offer) {
                    echo "<tr>";
                    echo "<td>{$offer['id']}</td>";
                    echo "<td>{$offer['offer_id']}</td>";
                    echo "<td>{$offer['donor_id']}</td>";
                    echo "<td>{$offer['hospital_id']}</td>";
                    echo "<td>{$offer['status']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Check if users exist for these donor_ids
                echo "<h4>Checking Users for Donor IDs:</h4>";
                foreach ($simple_offers as $offer) {
                    $user_check = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE id = ?");
                    $user_check->execute([$offer['donor_id']]);
                    $user_data = $user_check->fetch();
                    
                    if ($user_data) {
                        echo "<p>✅ Donor ID {$offer['donor_id']}: {$user_data['first_name']} {$user_data['last_name']}</p>";
                    } else {
                        echo "<p style='color: red;'>❌ Donor ID {$offer['donor_id']}: User not found</p>";
                    }
                }
            }
        }
        
    } else {
        echo "<p style='color: red;'>❌ Hospital data not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>