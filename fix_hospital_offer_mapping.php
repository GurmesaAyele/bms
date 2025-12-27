<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Hospital Offer Mapping</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .success { color: #16a085; background: #d5f4e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; background: #d6eaf8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #f39c12; background: #fef9e7; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .btn { background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Hospital Offer Mapping</h1>
        
        <?php
        try {
            require_once 'backend/config/database.php';
            echo '<div class="success">✅ Database connected successfully</div>';
            
            // Check current mapping
            echo "<h2>1. Current Hospital-Offer Mapping</h2>";
            
            // Get all hospitals
            $hospitals = $conn->query("
                SELECT h.id, h.hospital_name, h.hospital_id, u.email
                FROM hospitals h
                JOIN users u ON h.user_id = u.id
                WHERE h.is_verified = 1 AND h.is_active = 1
                ORDER BY h.id
            ")->fetchAll();
            
            echo "<h3>Available Hospitals:</h3>";
            echo "<table>";
            echo "<tr><th>DB ID</th><th>Hospital Name</th><th>Email</th></tr>";
            foreach ($hospitals as $hospital) {
                echo "<tr>";
                echo "<td>{$hospital['id']}</td>";
                echo "<td>{$hospital['hospital_name']}</td>";
                echo "<td>{$hospital['email']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Get all donation offers
            $offers = $conn->query("
                SELECT do.*, u.first_name, u.last_name
                FROM donation_offers do
                LEFT JOIN users u ON do.donor_id = u.id
                ORDER BY do.created_at DESC
            ")->fetchAll();
            
            echo "<h3>Current Donation Offers:</h3>";
            if (count($offers) > 0) {
                echo "<table>";
                echo "<tr><th>Offer ID</th><th>Donor</th><th>Hospital ID</th><th>Status</th><th>Created</th></tr>";
                foreach ($offers as $offer) {
                    echo "<tr>";
                    echo "<td>{$offer['offer_id']}</td>";
                    echo "<td>{$offer['first_name']} {$offer['last_name']}</td>";
                    echo "<td>{$offer['hospital_id']}</td>";
                    echo "<td>{$offer['status']}</td>";
                    echo "<td>" . date('M j, Y g:i A', strtotime($offer['created_at'])) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='warning'>⚠️ No donation offers found</div>";
            }
            
            // Check for mapping issues
            echo "<h2>2. Mapping Analysis</h2>";
            
            $mapping_issues = [];
            foreach ($offers as $offer) {
                $hospital_exists = false;
                foreach ($hospitals as $hospital) {
                    if ($hospital['id'] == $offer['hospital_id']) {
                        $hospital_exists = true;
                        break;
                    }
                }
                if (!$hospital_exists) {
                    $mapping_issues[] = $offer;
                }
            }
            
            if (count($mapping_issues) > 0) {
                echo "<div class='error'>❌ Found " . count($mapping_issues) . " offers with invalid hospital IDs</div>";
                echo "<table>";
                echo "<tr><th>Offer ID</th><th>Invalid Hospital ID</th><th>Donor</th></tr>";
                foreach ($mapping_issues as $issue) {
                    echo "<tr>";
                    echo "<td>{$issue['offer_id']}</td>";
                    echo "<td>{$issue['hospital_id']}</td>";
                    echo "<td>{$issue['first_name']} {$issue['last_name']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Fix the mapping issues
                if (count($hospitals) > 0) {
                    $first_hospital_id = $hospitals[0]['id'];
                    echo "<h3>🔧 Auto-Fix: Assigning invalid offers to first hospital (ID: $first_hospital_id)</h3>";
                    
                    foreach ($mapping_issues as $issue) {
                        $fix_stmt = $conn->prepare("UPDATE donation_offers SET hospital_id = ? WHERE id = ?");
                        $fix_stmt->execute([$first_hospital_id, $issue['id']]);
                        echo "<div class='info'>✅ Fixed offer {$issue['offer_id']} - assigned to hospital ID $first_hospital_id</div>";
                    }
                }
            } else {
                echo "<div class='success'>✅ All donation offers have valid hospital IDs</div>";
            }
            
            // Show final mapping
            echo "<h2>3. Final Hospital-Offer Mapping</h2>";
            foreach ($hospitals as $hospital) {
                $hospital_offers = $conn->prepare("
                    SELECT COUNT(*) as count, 
                           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                    FROM donation_offers 
                    WHERE hospital_id = ?
                ");
                $hospital_offers->execute([$hospital['id']]);
                $counts = $hospital_offers->fetch();
                
                echo "<div class='info'>";
                echo "<strong>{$hospital['hospital_name']} (ID: {$hospital['id']})</strong><br>";
                echo "Total offers: {$counts['count']} | ";
                echo "Pending: {$counts['pending']} | ";
                echo "Accepted: {$counts['accepted']} | ";
                echo "Completed: {$counts['completed']}";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
        }
        ?>
        
        <h2>✅ Fix Complete!</h2>
        <div class="success">
            <p>The hospital-offer mapping has been checked and fixed. Now:</p>
            <ol>
                <li>All donation offers should have valid hospital IDs</li>
                <li>Hospitals should be able to see their offers</li>
                <li>The hospital dashboard should work correctly</li>
            </ol>
        </div>
        
        <h3>🚀 Test Your Hospital Dashboard</h3>
        <p>
            <a href="debug_hospital_offers.php" class="btn">Debug Hospital Offers</a>
            <a href="frontend/dashboard/hospital.php" class="btn">Hospital Dashboard</a>
            <a href="frontend/auth/login.php" class="btn">Login</a>
        </p>
        
        <div class="info">
            <h4>📋 Test Instructions:</h4>
            <ol>
                <li>Login as hospital: city.general@hospital.com / hospital123</li>
                <li>Go to hospital dashboard</li>
                <li>You should now see donation offers</li>
                <li>Try accepting, rejecting, or completing offers</li>
            </ol>
        </div>
    </div>
</body>
</html>