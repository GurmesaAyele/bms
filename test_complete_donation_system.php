<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Donation System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #16a085; background: #d5f4e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; background: #d6eaf8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #b91c1c; }
        .btn-success { background: #16a085; }
        .btn-info { background: #3498db; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .workflow { background: #e8f5e5; padding: 15px; margin: 10px 0; border-left: 4px solid #16a085; }
        .workflow h4 { margin-top: 0; color: #16a085; }
        .feature { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #3498db; }
        .feature h4 { margin-top: 0; color: #3498db; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🩸 Complete Donation Management System Test</h1>
        
        <?php
        try {
            require_once 'backend/config/database.php';
            echo '<div class="success">✅ Database connected successfully</div>';
            
            // Check system status
            echo "<h2>System Status</h2>";
            
            $stats = [
                'users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'donors' => $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'donor'")->fetchColumn(),
                'hospitals' => $conn->query("SELECT COUNT(*) FROM hospitals WHERE is_verified = 1 AND is_active = 1")->fetchColumn(),
                'total_offers' => $conn->query("SELECT COUNT(*) FROM donation_offers")->fetchColumn(),
                'pending_offers' => $conn->query("SELECT COUNT(*) FROM donation_offers WHERE status = 'pending'")->fetchColumn(),
                'accepted_offers' => $conn->query("SELECT COUNT(*) FROM donation_offers WHERE status = 'accepted'")->fetchColumn(),
                'completed_offers' => $conn->query("SELECT COUNT(*) FROM donation_offers WHERE status = 'completed'")->fetchColumn(),
                'rejected_offers' => $conn->query("SELECT COUNT(*) FROM donation_offers WHERE status = 'rejected'")->fetchColumn(),
                'cancelled_offers' => $conn->query("SELECT COUNT(*) FROM donation_offers WHERE status = 'cancelled'")->fetchColumn()
            ];
            
            echo "<table>";
            echo "<tr><th>Metric</th><th>Count</th></tr>";
            foreach ($stats as $metric => $count) {
                echo "<tr><td>" . ucfirst(str_replace('_', ' ', $metric)) . "</td><td>$count</td></tr>";
            }
            echo "</table>";
            
            // Show recent donation offers with all statuses
            echo "<h2>Recent Donation Offers (All Statuses)</h2>";
            $offers = $conn->query("
                SELECT do.*, h.hospital_name, u.first_name, u.last_name, u.email
                FROM donation_offers do
                LEFT JOIN hospitals h ON do.hospital_id = h.id
                LEFT JOIN users u ON do.donor_id = u.id
                ORDER BY do.created_at DESC
                LIMIT 15
            ")->fetchAll();
            
            if (count($offers) > 0) {
                echo "<table>";
                echo "<tr><th>Offer ID</th><th>Donor</th><th>Hospital</th><th>Blood Type</th><th>Date</th><th>Status</th><th>Created</th><th>Updated</th></tr>";
                foreach ($offers as $offer) {
                    $status_color = '';
                    switch ($offer['status']) {
                        case 'pending': $status_color = 'background: #fef9e7; color: #f39c12;'; break;
                        case 'accepted': $status_color = 'background: #d6eaf8; color: #3498db;'; break;
                        case 'completed': $status_color = 'background: #d5f4e6; color: #16a085;'; break;
                        case 'rejected': $status_color = 'background: #fadbd8; color: #e74c3c;'; break;
                        case 'cancelled': $status_color = 'background: #f8f9fa; color: #6c757d;'; break;
                    }
                    
                    echo "<tr>";
                    echo "<td>{$offer['offer_id']}</td>";
                    echo "<td>{$offer['first_name']} {$offer['last_name']}</td>";
                    echo "<td>{$offer['hospital_name']}</td>";
                    echo "<td>{$offer['blood_type']}</td>";
                    echo "<td>{$offer['preferred_date']}</td>";
                    echo "<td><span style='padding: 3px 8px; border-radius: 3px; $status_color'>{$offer['status']}</span></td>";
                    echo "<td>" . date('M j, g:i A', strtotime($offer['created_at'])) . "</td>";
                    echo "<td>" . ($offer['updated_at'] ? date('M j, g:i A', strtotime($offer['updated_at'])) : '-') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo '<div class="info">ℹ️ No donation offers found</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
        }
        ?>
        
        <div class="workflow">
            <h3>🔄 Complete Donation Workflow</h3>
            <p><strong>The system now supports the complete donation lifecycle:</strong></p>
            <ol>
                <li><strong>Donor submits offer</strong> → Creates pending donation offer</li>
                <li><strong>Donor can edit/cancel</strong> → While status is pending or accepted</li>
                <li><strong>Hospital reviews offer</strong> → Can accept or reject with feedback</li>
                <li><strong>Hospital completes donation</strong> → Updates donor records automatically</li>
                <li><strong>History management</strong> → Both parties can delete completed/rejected offers</li>
            </ol>
        </div>
        
        <div class="feature">
            <h3>🎯 New Features Added</h3>
            <h4>For Donors:</h4>
            <ul>
                <li>✅ <strong>Edit Offers</strong> - Change date, time, and notes for pending offers</li>
                <li>✅ <strong>Cancel Offers</strong> - Cancel pending or accepted offers</li>
                <li>✅ <strong>Delete History</strong> - Remove completed, rejected, or cancelled offers</li>
                <li>✅ <strong>Real-time Status</strong> - See hospital feedback and status updates</li>
            </ul>
            
            <h4>For Hospitals:</h4>
            <ul>
                <li>✅ <strong>Accept/Reject Offers</strong> - With feedback messages to donors</li>
                <li>✅ <strong>Complete Donations</strong> - Mark as completed and update donor records</li>
                <li>✅ <strong>Delete History</strong> - Remove completed donations from records</li>
                <li>✅ <strong>Contact Donors</strong> - Direct phone contact links</li>
            </ul>
        </div>
        
        <h2>🚀 Test the Complete System</h2>
        
        <div class="feature">
            <h4>For Donors:</h4>
            <p>1. Login as donor and submit a donation offer</p>
            <p>2. Try editing the offer (change date/time)</p>
            <p>3. Try canceling the offer</p>
            <p>4. After hospital processes it, delete from history</p>
            <a href="frontend/auth/login.php" class="btn btn-success">Login as Donor</a>
            <a href="frontend/dashboard/donor.php" class="btn btn-info">Donor Dashboard</a>
        </div>
        
        <div class="feature">
            <h4>For Hospitals:</h4>
            <p>1. Login as hospital and view donation offers</p>
            <p>2. Accept or reject offers with feedback</p>
            <p>3. Mark accepted offers as completed</p>
            <p>4. Delete completed donations from history</p>
            <a href="frontend/auth/login.php" class="btn btn-success">Login as Hospital</a>
            <a href="frontend/dashboard/hospital.php" class="btn btn-info">Hospital Dashboard</a>
        </div>
        
        <h3>📋 Test Credentials</h3>
        <div class="info">
            <p><strong>Donor:</strong> Register new donor or use existing account</p>
            <p><strong>Hospital:</strong> city.general@hospital.com / hospital123</p>
            <p><strong>Admin:</strong> admin@bloodconnect.com / admin123</p>
        </div>
        
        <h3>🔗 Quick Links</h3>
        <div style="margin: 20px 0;">
            <a href="setup_database.php" class="btn">Setup Database</a>
            <a href="frontend/auth/register-donor.php" class="btn btn-success">Register Donor</a>
            <a href="frontend/auth/register-hospital.php" class="btn btn-info">Register Hospital</a>
            <a href="frontend/index.php" class="btn">Home Page</a>
        </div>
        
        <div class="success">
            <h3>✅ System Complete!</h3>
            <p>The BloodConnect donation management system now includes:</p>
            <ul>
                <li>Complete donation workflow from submission to completion</li>
                <li>Donor offer management (edit, cancel, delete)</li>
                <li>Hospital offer processing (accept, reject, complete, delete)</li>
                <li>Real-time status updates and feedback</li>
                <li>Automatic donor record updates</li>
                <li>History management for both parties</li>
            </ul>
        </div>
    </div>
</body>
</html>