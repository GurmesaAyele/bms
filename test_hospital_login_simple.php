<?php
session_start();

// Simulate hospital login
$_SESSION['user_id'] = 7; // Hospital user ID from debug
$_SESSION['user_type'] = 'hospital';

echo "<h1>Hospital Login Test</h1>";
echo "<p>Session set: User ID 7, Type: hospital</p>";
echo "<p><a href='frontend/dashboard/hospital.php'>Go to Hospital Dashboard</a></p>";
echo "<p><a href='debug_hospital_offers.php'>Debug Hospital Offers</a></p>";
?>