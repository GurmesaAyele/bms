<?php
session_start();

// Simulate hospital login
$_SESSION['user_id'] = 7; // Hospital user ID from debug
$_SESSION['user_type'] = 'hospital';

require_once 'backend/config/database.php';

echo "<h1>🏥 Complete Hospital Data Test</h1>";

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
        
        // Check donation offers
        echo "<h2>1. Donation Offers</h2>";
        $offers_count = $conn->prepare("SELECT COUNT(*) as count FROM donation_offers WHERE hospital_id = ?");
        $offers_count->execute([$hospital_db_id]);
        $offers_total = $offers_count->fetch()['count'];
        echo "<p>Total donation offers: $offers_total</p>";
        
        if ($offers_total > 0) {
            $offers = $conn->prepare("
                SELECT do.offer_id, do.status, do.blood_type, do.created_at, u.first_name, u.last_name
                FROM donation_offers do
                JOIN users u ON do.donor_id = u.id
                WHERE do.hospital_id = ?
                ORDER BY do.created_at DESC
            ");
            $offers->execute([$hospital_db_id]);
            $offer_list = $offers->fetchAll();
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Offer ID</th><th>Donor</th><th>Blood Type</th><th>Status</th><th>Date</th></tr>";
            foreach ($offer_list as $offer) {
                echo "<tr>";
                echo "<td>{$offer['offer_id']}</td>";
                echo "<td>{$offer['first_name']} {$offer['last_name']}</td>";
                echo "<td>{$offer['blood_type']}</td>";
                echo "<td>{$offer['status']}</td>";
                echo "<td>" . date('M j, Y', strtotime($offer['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Check blood requests
        echo "<h2>2. Blood Requests</h2>";
        $requests_count = $conn->prepare("SELECT COUNT(*) as count FROM blood_requests WHERE hospital_id = ?");
        $requests_count->execute([$hospital_db_id]);
        $requests_total = $requests_count->fetch()['count'];
        echo "<p>Total blood requests: $requests_total</p>";
        
        if ($requests_total > 0) {
            $requests = $conn->prepare("
                SELECT br.request_id, br.status, br.blood_type, br.units_requested, br.created_at, u.first_name, u.last_name
                FROM blood_requests br
                JOIN users u ON br.patient_id = u.id
                WHERE br.hospital_id = ?
                ORDER BY br.created_at DESC
            ");
            $requests->execute([$hospital_db_id]);
            $request_list = $requests->fetchAll();
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Request ID</th><th>Patient</th><th>Blood Type</th><th>Units</th><th>Status</th><th>Date</th></tr>";
            foreach ($request_list as $request) {
                echo "<tr>";
                echo "<td>{$request['request_id']}</td>";
                echo "<td>{$request['first_name']} {$request['last_name']}</td>";
                echo "<td>{$request['blood_type']}</td>";
                echo "<td>{$request['units_requested']}</td>";
                echo "<td>{$request['status']}</td>";
                echo "<td>" . date('M j, Y', strtotime($request['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Check if there are any blood requests at all in the database
        echo "<h2>3. All Blood Requests in Database</h2>";
        $all_requests = $conn->query("SELECT COUNT(*) as count FROM blood_requests")->fetch()['count'];
        echo "<p>Total blood requests in entire database: $all_requests</p>";
        
        if ($all_requests > 0) {
            $sample_requests = $conn->query("
                SELECT br.request_id, br.hospital_id, h.hospital_name, br.status, br.created_at
                FROM blood_requests br
                LEFT JOIN hospitals h ON br.hospital_id = h.id
                ORDER BY br.created_at DESC
                LIMIT 5
            ")->fetchAll();
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Request ID</th><th>Hospital ID</th><th>Hospital Name</th><th>Status</th><th>Date</th></tr>";
            foreach ($sample_requests as $request) {
                $highlight = ($request['hospital_id'] == $hospital_db_id) ? 'background: #d5f4e6;' : '';
                echo "<tr style='$highlight'>";
                echo "<td>{$request['request_id']}</td>";
                echo "<td>{$request['hospital_id']}</td>";
                echo "<td>{$request['hospital_name']}</td>";
                echo "<td>{$request['status']}</td>";
                echo "<td>" . date('M j, Y', strtotime($request['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p>Green rows = requests for your hospital</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Hospital data not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>🔗 Quick Links</h3>";
echo "<p>";
echo "<a href='frontend/dashboard/hospital.php' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Hospital Dashboard</a>";
echo "<a href='frontend/dashboard/patient.php' style='background: #16a085; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Patient Dashboard</a>";
echo "<a href='frontend/request-blood.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Request Blood</a>";
echo "</p>";
?>