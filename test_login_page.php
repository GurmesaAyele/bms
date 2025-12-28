<?php
// Simple test to check if login page loads correctly
echo "Testing Login Page...\n\n";

// Check if login.css exists
$loginCssPath = 'frontend/css/login.css';
if (file_exists($loginCssPath)) {
    echo "✅ Login CSS file exists: $loginCssPath\n";
    echo "   File size: " . filesize($loginCssPath) . " bytes\n";
} else {
    echo "❌ Login CSS file missing: $loginCssPath\n";
}

// Check if login.php exists
$loginPhpPath = 'frontend/auth/login.php';
if (file_exists($loginPhpPath)) {
    echo "✅ Login PHP file exists: $loginPhpPath\n";
    echo "   File size: " . filesize($loginPhpPath) . " bytes\n";
} else {
    echo "❌ Login PHP file missing: $loginPhpPath\n";
}

// Check if the CSS is properly linked in login.php
$loginContent = file_get_contents($loginPhpPath);
if (strpos($loginContent, 'href="../css/login.css"') !== false) {
    echo "✅ Login CSS is properly linked in login.php\n";
} else {
    echo "❌ Login CSS link not found in login.php\n";
}

// Check for required CSS classes
$cssContent = file_get_contents($loginCssPath);
$requiredClasses = [
    '.login-container',
    '.login-header',
    '.login-logo',
    '.form-control',
    '.btn-login',
    '.register-btn'
];

echo "\nChecking CSS classes:\n";
foreach ($requiredClasses as $class) {
    if (strpos($cssContent, $class) !== false) {
        echo "✅ Found: $class\n";
    } else {
        echo "❌ Missing: $class\n";
    }
}

echo "\n🎯 To test the login page:\n";
echo "1. Make sure your web server is running\n";
echo "2. Navigate to: http://localhost/your-project/frontend/auth/login.php\n";
echo "3. The page should show a modern login form with:\n";
echo "   - Animated red logo\n";
echo "   - Glass-morphism design\n";
echo "   - Smooth animations\n";
echo "   - Responsive layout\n";

echo "\n📝 Login page features:\n";
echo "- Modern gradient background\n";
echo "- Glassmorphism container design\n";
echo "- Animated logo with pulse effect\n";
echo "- Smooth form interactions\n";
echo "- Password toggle functionality\n";
echo "- Registration options for different user types\n";
echo "- Fully responsive design\n";
echo "- Loading states and animations\n";
?>