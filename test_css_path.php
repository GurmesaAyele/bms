<?php
echo "<h2>CSS Path Test</h2>";

// Test the CSS file path from login.php perspective
$css_path_from_login = 'frontend/css/login.css';
$css_path_relative = '../css/login.css';

echo "<p><strong>Testing CSS file paths:</strong></p>";

// Check if CSS file exists from root
if (file_exists($css_path_from_login)) {
    echo "✅ CSS file exists at: $css_path_from_login<br>";
    echo "   File size: " . filesize($css_path_from_login) . " bytes<br>";
} else {
    echo "❌ CSS file NOT found at: $css_path_from_login<br>";
}

// Check login.php file
$login_file = 'frontend/auth/login.php';
if (file_exists($login_file)) {
    echo "✅ Login file exists at: $login_file<br>";
    
    // Check if CSS link is in the file
    $login_content = file_get_contents($login_file);
    if (strpos($login_content, '../css/login.css') !== false) {
        echo "✅ CSS link found in login.php<br>";
    } else {
        echo "❌ CSS link NOT found in login.php<br>";
    }
} else {
    echo "❌ Login file NOT found<br>";
}

echo "<br><p><strong>Directory Structure:</strong></p>";
echo "<pre>";
echo "Project Root/\n";
echo "├── frontend/\n";
echo "│   ├── auth/\n";
echo "│   │   └── login.php (uses ../css/login.css)\n";
echo "│   └── css/\n";
echo "│       └── login.css\n";
echo "</pre>";

echo "<br><p><strong>Relative Path Explanation:</strong></p>";
echo "<p>From <code>frontend/auth/login.php</code>:</p>";
echo "<p><code>../css/login.css</code> means:</p>";
echo "<p>Go up one level (..) from 'auth' to 'frontend', then into 'css' folder</p>";

echo "<br><p><strong>Test URL:</strong></p>";
echo "<p>Try accessing: <a href='frontend/auth/login.php'>frontend/auth/login.php</a></p>";
?>