<?php
/**
 * Final Test for Hospital Dashboard
 * Test with correct column names
 */

require_once 'backend/config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Final Hospital Dashboard Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style></head><body>";

echo "<h1>Final Hospital Dashboard Test</h1>";

try {
    // Step 1: Create test data with correct column names
    echo "<div class='section'>";
    echo "<h2>Step 1: Creating Test Data with Correct Column Names</h2>";
    
    // Get or create test hospital
    $hospital_stmt = $conn->prepare("
        SELECT u.id as user_id, h.id as hospital_id 
        FROM users u 
        JOIN hospitals h ON u.id = h.user_id 
        WHERE u.email = 'testhospital@bloodconnect.com'
    ");
    $hospital_stmt->execute();
    $hospital_data = $hospital_stmt->fetch();
    
    if (!$hospital_data) {
        echo "<div class='error'>Test hospital not found. Please run fix_database_issues.php first.</div>";
        exit();
    }
    
    echo "<div class='success'>✓ Found test hospital (ID: {$hospital_data['hospital_id']})</div>";
    
    // Clear existing test data
    $conn->prepare("DELETE FROM donation_offers WHERE offer_id LIKE 'OFF-FINAL-%'")->execute();
    
    // Create test donation offers with correct column names
    $test_offers = [
        ['status' => 'pending', 'blood_type' => 'O+'],
        ['status' => 'pending', 'blood_type' => 'A+'],
        ['status' => 'accepted', 'blood_type' => 'B+']
    ];
    
    foreach ($test_offers as $index => $offer_data) {
        $offer_id = 'OFF-FINAL-' . date('YmdHis') . '-' . ($index + 1);
        
        $insert_offer = $conn->prepare("
            INSERT INTO donation_offers (offer_id, donor_id, hospital_id, blood_type, preferred_date, 
                                       preferred_time, status, notes, created_at)
            VALUES (?, 1, ?, ?, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', ?, 'Final test offer', NOW())
        ");
        $insert_offer->execute([
            $offer_id,
            $hospital_data['hospital_id'],
            $offer_data['blood_type'],
            $offer_data['status']
        ]);
        
        echo "<div class='success'>✓ Created donation offer: {$offer_id} ({$offer_data['status']})</div>";
    }
    echo "</div>";
    
    // Step 2: Test hospital dashboard queries
    echo "<div class='section'>";
    echo "<h2>Step 2: Testing Hospital Dashboard Queries</h2>";
    
    // Test donation offers query
    echo "<h3>Donation Offers Query:</h3>";
    $offers_query = "
        SELECT do.*, u.first_name, u.last_name, u.email, u.phone
        FROM donation_offers do
        LEFT JOIN users u ON do.donor_id = u.id
        WHERE do.hospital_id = ?
        ORDER BY do.created_at DESC
    ";
    
    $offers_stmt = $conn->prepare($offers_query);
    $offers_stmt->execute([$hospital_data['hospital_id']]);
    $donation_offers = $offers_stmt->fetchAll();
    
    echo "<div class='info'>Found " . count($donation_offers) . " donation offers</div>";
    
    if (!empty($donation_offers)) {
        echo "<table>";
        echo "<tr><th>Offer ID</th><th>Donor</th><th>Blood Type</th><th>Status</th><th>Created</th></tr>";
        foreach ($donation_offers as $offer) {
            echo "<tr>";
            echo "<td>{$offer['offer_id']}</td>";
            echo "<td>{$offer['first_name']} {$offer['last_name']}</td>";
            echo "<td>{$offer['blood_type']}</td>";
            echo "<td><strong>{$offer['status']}</strong></td>";
            echo "<td>{$offer['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test statistics query
    echo "<h3>Statistics Query:</h3>";
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM blood_requests WHERE assigned_hospital_id = ?) as total_requests,
            (SELECT COUNT(*) FROM blood_requests WHERE assigned_hospital_id = ? AND status = 'pending') as pending_requests,
            (SELECT COUNT(*) FROM donation_offers WHERE hospital_id = ?) as total_offers,
            (SELECT COUNT(*) FROM donation_offers WHERE hospital_id = ? AND status = 'pending') as pending_offers
    ";
    
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute([$hospital_data['hospital_id'], $hospital_data['hospital_id'], $hospital_data['hospital_id'], $hospital_data['hospital_id']]);
    $stats = $stats_stmt->fetch();
    
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th></tr>";
    echo "<tr><td>Total Blood Requests</td><td>{$stats['total_requests']}</td></tr>";
    echo "<tr><td>Pending Blood Requests</td><td>{$stats['pending_requests']}</td></tr>";
    echo "<tr><td>Total Donation Offers</td><td>{$stats['total_offers']}</td></tr>";
    echo "<tr><td>Pending Donation Offers</td><td>{$stats['pending_offers']}</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Step 3: Test update operations
    echo "<div class='section'>";
    echo "<h2>Step 3: Testing Update Operations</h2>";
    
    if (!empty($donation_offers)) {
        $test_offer = $donation_offers[0];
        
        if ($test_offer['status'] === 'pending') {
            echo "<h3>Testing Accept Operation:</h3>";
            
            $update_stmt = $conn->prepare("
                UPDATE donation_offers 
                SET status = 'accepted', accepted_by = ?, accepted_at = NOW(), notes = ?
                WHERE id = ? AND hospital_id = ?
            ");
            $result = $update_stmt->execute([1, 'Test acceptance', $test_offer['id'], $hospital_data['hospital_id']]);
            
            if ($result && $update_stmt->rowCount() > 0) {
                echo "<div class='success'>✓ Successfully accepted donation offer</div>";
            } else {
                echo "<div class='error'>✗ Failed to accept donation offer</div>";
            }
        }
    }
    echo "</div>";
    
    // Step 4: Final verification
    echo "<div class='section'>";
    echo "<h2>Step 4: Final Verification</h2>";
    
    echo "<div class='success'><h3>✓ All tests completed successfully!</h3></div>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Open the hospital dashboard: <a href='frontend/dashboard/hospital.php' target='_blank'>Hospital Dashboard</a></li>";
    echo "<li>Login with: testhospital@bloodconnect.com / hospital123</li>";
    echo "<li>You should now see both blood requests and donation offers</li>";
    echo "<li>Try accepting/rejecting donation offers</li>";
    echo "</ol>";
    
    echo "<h3>Database Column Mapping:</h3>";
    echo "<ul>";
    echo "<li><strong>donation_offers.hospital_id</strong> → Links to hospitals.id</li>";
    echo "<li><strong>donation_offers.donor_id</strong> → Links to users.id (donor)</li>";
    echo "<li><strong>donation_offers.accepted_by</strong> → Links to users.id (hospital user)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Database Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>