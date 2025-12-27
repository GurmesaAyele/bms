<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Hospital Offers</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .success { color: #16a085; background: #d5f4e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; background: #d6eaf8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐛 Debug Hospital Donation Offers</h1>
        
        <?php
        session_start();
        
        try {
            require_once 'backend/config/database.php';
            echo '<div class="success">✅ Database connected successfully</div>';
            
            // Check session
            if (isset($_SESSION['user_id'])) {
                echo "<div class='success'>✅ User logged in: ID {$_SESSION['user_id']}, Type: {$_SESSION['user_type']}</div>";
                
                if ($_SESSION['user_type'] === 'hospital') {
                    // Get hospital data
                    echo "<h2>1. Hospital User Data</h2>";
                    $stmt = $conn->prepare("
                        SELECT u.*, h.hospital_id, h.hospital_name, h.id as hospital_db_id
                        FROM users u 
                        JOIN hospitals h ON u.id = h.user_id 
                        WHERE u.id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $hospital_data = $stmt->fetch();
                    
                    if ($hospital_data) {
                        echo "<table>";
                        echo "<tr><th>Field</th><th>Value</th></tr>";
                        echo "<tr><td>User ID</td><td>{$hospital_data['id']}</td></tr>";
                        echo "<tr><td>Hospital Name</td><td>{$hospital_data['hospital_name']}</td></tr>";
                        echo "<tr><td>Hospital ID (String)</td><td>{$hospital_data['hospital_id']}</td></tr>";
                        echo "<tr><td>Hospital DB ID (Integer)</td><td>{$hospital_data['hospital_db_id']}</td></tr>";
                        echo "</table>";
                        
                        $hospital_db_id = $hospital_data['hospital_db_id'];
                        
                        // Check all donation offers in database
                        echo "<h2>2. All Donation Offers in Database</h2>";
                        $all_offers = $conn->query("
                            SELECT do.*, h.hospital_name, u.first_name, u.last_name
                            FROM donation_offers do
                            LEFT JOIN hospitals h ON do.hospital_id = h.id
                            LEFT JOIN users u ON do.donor_id = u.id
                            ORDER BY do.created_at DESC
                        ")->fetchAll();
                        
                        if (count($all_offers) > 0) {
                            echo "<div class='info'>Found " . count($all_offers) . " total donation offers</div>";
                            echo "<table>";
                            echo "<tr><th>Offer ID</th><th>Donor</th><th>Hospital ID</th><th>Hospital Name</th><th>Status</th><th>Created</th></tr>";
                            foreach ($all_offers as $offer) {
                                $highlight = ($offer['hospital_id'] == $hospital_db_id) ? 'background: #d5f4e6;' : '';
                                echo "<tr style='$highlight'>";
                                echo "<td>{$offer['offer_id']}</td>";
                                echo "<td>{$offer['first_name']} {$offer['last_name']}</td>";
                                echo "<td>{$offer['hospital_id']}</td>";
                                echo "<td>{$offer['hospital_name']}</td>";
                                echo "<td>{$offer['status']}</td>";
                                echo "<td>" . date('M j, Y g:i A', strtotime($offer['created_at'])) . "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                            echo "<div class='info'>Green rows = offers for your hospital (ID: $hospital_db_id)</div>";
                        } else {
                            echo "<div class='error'>❌ No donation offers found in database</div>";
                        }
                        
                        // Check offers for this hospital specifically
                        echo "<h2>3. Offers for This Hospital (ID: $hospital_db_id)</h2>";
                        $hospital_offers = $conn->prepare("
                            SELECT do.*, u.first_name, u.last_name, u.email, u.phone, d.donor_id, d.blood_type as donor_blood_type
                            FROM donation_offers do
                            JOIN users u ON do.donor_id = u.id
                            LEFT JOIN donors d ON u.id = d.user_id
                            WHERE do.hospital_id = ?
                            ORDER BY do.created_at DESC
                        ");
                        $hospital_offers->execute([$hospital_db_id]);
                        $offers_for_hospital = $hospital_offers->fetchAll();
                        
                        if (count($offers_for_hospital) > 0) {
                            echo "<div class='success'>✅ Found " . count($offers_for_hospital) . " offers for this hospital</div>";
                            echo "<table>";
                            echo "<tr><th>Offer ID</th><th>Donor</th><th>Blood Type</th><th>Date</th><th>Status</th><th>Created</th></tr>";
                            foreach ($offers_for_hospital as $offer) {
                                echo "<tr>";
                                echo "<td>{$offer['offer_id']}</td>";
                                echo "<td>{$offer['first_name']} {$offer['last_name']}</td>";
                                echo "<td>{$offer['donor_blood_type']}</td>";
                                echo "<td>{$offer['preferred_date']}</td>";
                                echo "<td>{$offer['status']}</td>";
                                echo "<td>" . date('M j, Y g:i A', strtotime($offer['created_at'])) . "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        } else {
                            echo "<div class='error'>❌ No offers found for this hospital</div>";
                            echo "<p>This means the hospital_id in donation_offers doesn't match this hospital's ID ($hospital_db_id)</p>";
                        }
                        
                        // Check what hospital IDs are being used in donation offers
                        echo "<h2>4. Hospital IDs Used in Donation Offers</h2>";
                        $hospital_ids_used = $conn->query("
                            SELECT DISTINCT do.hospital_id, h.hospital_name, COUNT(*) as offer_count
                            FROM donation_offers do
                            LEFT JOIN hospitals h ON do.hospital_id = h.id
                            GROUP BY do.hospital_id
                            ORDER BY do.hospital_id
                        ")->fetchAll();
                        
                        if (count($hospital_ids_used) > 0) {
                            echo "<table>";
                            echo "<tr><th>Hospital ID</th><th>Hospital Name</th><th>Offer Count</th><th>Match</th></tr>";
                            foreach ($hospital_ids_used as $id_info) {
                                $match = ($id_info['hospital_id'] == $hospital_db_id) ? '✅ YES' : '❌ NO';
                                echo "<tr>";
                                echo "<td>{$id_info['hospital_id']}</td>";
                                echo "<td>{$id_info['hospital_name']}</td>";
                                echo "<td>{$id_info['offer_count']}</td>";
                                echo "<td>$match</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        }
                        
                        // Show all hospitals and their IDs
                        echo "<h2>5. All Hospitals and Their IDs</h2>";
                        $all_hospitals = $conn->query("
                            SELECT h.id, h.hospital_name, h.hospital_id, u.email, h.is_verified, h.is_active
                            FROM hospitals h
                            JOIN users u ON h.user_id = u.id
                            ORDER BY h.id
                        ")->fetchAll();
                        
                        echo "<table>";
                        echo "<tr><th>DB ID</th><th>Hospital Name</th><th>Hospital ID</th><th>Email</th><th>Verified</th><th>Active</th></tr>";
                        foreach ($all_hospitals as $hospital) {
                            $highlight = ($hospital['id'] == $hospital_db_id) ? 'background: #d5f4e6;' : '';
                            echo "<tr style='$highlight'>";
                            echo "<td>{$hospital['id']}</td>";
                            echo "<td>{$hospital['hospital_name']}</td>";
                            echo "<td>{$hospital['hospital_id']}</td>";
                            echo "<td>{$hospital['email']}</td>";
                            echo "<td>" . ($hospital['is_verified'] ? 'Yes' : 'No') . "</td>";
                            echo "<td>" . ($hospital['is_active'] ? 'Yes' : 'No') . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                        echo "<div class='info'>Green row = your hospital</div>";
                        
                    } else {
                        echo "<div class='error'>❌ No hospital data found for user ID {$_SESSION['user_id']}</div>";
                    }
                } else {
                    echo "<div class='error'>❌ You need to be logged in as a hospital</div>";
                }
            } else {
                echo "<div class='error'>❌ Not logged in. <a href='frontend/auth/login.php'>Login here</a></div>";
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
        }
        ?>
        
        <h2>🔧 Possible Solutions</h2>
        <div class="info">
            <p><strong>If no offers are showing for your hospital:</strong></p>
            <ol>
                <li>Check if the hospital_id in donation_offers matches your hospital's DB ID</li>
                <li>Verify that donors are selecting the correct hospital when submitting offers</li>
                <li>Make sure your hospital is verified and active</li>
                <li>Check if there's a mismatch in the hospital ID mapping</li>
            </ol>
        </div>
        
        <h3>🔗 Quick Links</h3>
        <p>
            <a href="frontend/dashboard/hospital.php" style="background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">Hospital Dashboard</a>
            <a href="frontend/dashboard/donor.php" style="background: #16a085; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">Donor Dashboard</a>
            <a href="test_complete_donation_system.php" style="background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">System Test</a>
        </p>
    </div>
</body>
</html>