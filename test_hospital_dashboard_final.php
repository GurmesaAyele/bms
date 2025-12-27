<?php
session_start();

// Set hospital session
$_SESSION['user_id'] = 7; // Hospital user ID
$_SESSION['user_type'] = 'hospital';

echo "<h1>🏥 Hospital Dashboard Final Test</h1>";
echo "<p>✅ Session set for hospital user ID 7</p>";
echo "<p><strong>Test the hospital dashboard now:</strong></p>";
echo "<p><a href='frontend/dashboard/hospital.php' style='background: #dc2626; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px;'>🏥 Open Hospital Dashboard</a></p>";

echo "<h2>Expected Results:</h2>";
echo "<ul>";
echo "<li>✅ Should show 3 donation offers from donors</li>";
echo "<li>✅ Should show hospital statistics</li>";
echo "<li>✅ Should allow accepting/rejecting donation offers</li>";
echo "<li>✅ Should show blood requests from patients (if any)</li>";
echo "<li>✅ Should display hospital profile information</li>";
echo "</ul>";

echo "<h2>Other Test Links:</h2>";
echo "<p>";
echo "<a href='test_hospital_data_complete.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>📊 View Hospital Data</a>";
echo "<a href='debug_hospital_offers.php' style='background: #16a085; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🐛 Debug Offers</a>";
echo "<a href='frontend/dashboard/donor.php' style='background: #e67e22; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🩸 Donor Dashboard</a>";
echo "</p>";
?>