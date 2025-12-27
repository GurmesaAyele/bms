<?php
/**
 * Check Database Configuration
 * Verify database connection and configuration
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Configuration Check</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>Database Configuration Check</h1>";

// Check if config file exists
echo "<div class='section'>";
echo "<h2>Configuration File Check</h2>";

$config_file = 'backend/config/database.php';
if (file_exists($config_file)) {
    echo "<div class='success'>✓ Configuration file exists: {$config_file}</div>";
    
    // Show config file content (without sensitive data)
    $config_content = file_get_contents($config_file);
    $safe_content = preg_replace('/password["\']?\s*=>\s*["\'][^"\']*["\']/', 'password => "***HIDDEN***"', $config_content);
    echo "<h3>Configuration Content:</h3>";
    echo "<pre>" . htmlspecialchars($safe_content) . "</pre>";
} else {
    echo "<div class='error'>✗ Configuration file not found: {$config_file}</div>";
    echo "<div class='info'>Creating default configuration file...</div>";
    
    // Create default config
    $default_config = '<?php
/**
 * Database Configuration
 * WAMP Server MySQL Configuration
 */

$host = "localhost";
$dbname = "bloodconnect";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>';
    
    // Create directory if it doesn't exist
    if (!is_dir('backend/config')) {
        mkdir('backend/config', 0755, true);
    }
    
    file_put_contents($config_file, $default_config);
    echo "<div class='success'>✓ Created default configuration file</div>";
}
echo "</div>";

// Test database connection
echo "<div class='section'>";
echo "<h2>Database Connection Test</h2>";

try {
    require_once $config_file;
    
    if (isset($conn) && $conn instanceof PDO) {
        echo "<div class='success'>✓ Database connection successful</div>";
        
        // Test query
        $test_query = $conn->query("SELECT VERSION() as version");
        $version = $test_query->fetch();
        echo "<div class='info'>MySQL Version: {$version['version']}</div>";
        
        // Check current database
        $db_query = $conn->query("SELECT DATABASE() as current_db");
        $current_db = $db_query->fetch();
        echo "<div class='info'>Current Database: {$current_db['current_db']}</div>";
        
    } else {
        echo "<div class='error'>✗ Database connection object not found</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
    
    // Common WAMP issues and solutions
    echo "<h3>Common WAMP Issues and Solutions:</h3>";
    echo "<ul>";
    echo "<li><strong>Database doesn't exist:</strong> Create 'bloodconnect' database in phpMyAdmin</li>";
    echo "<li><strong>Access denied:</strong> Check MySQL username/password (default: root with no password)</li>";
    echo "<li><strong>Connection refused:</strong> Make sure WAMP MySQL service is running</li>";
    echo "<li><strong>Port issues:</strong> Check if MySQL is running on port 3306</li>";
    echo "</ul>";
    
    echo "<h3>WAMP Setup Steps:</h3>";
    echo "<ol>";
    echo "<li>Start WAMP server (all services should be green)</li>";
    echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
    echo "<li>Create database named 'bloodconnect'</li>";
    echo "<li>Import your database tables</li>";
    echo "<li>Refresh this page</li>";
    echo "</ol>";
}
echo "</div>";

// Check required tables
if (isset($conn)) {
    echo "<div class='section'>";
    echo "<h2>Required Tables Check</h2>";
    
    $required_tables = ['users', 'hospitals', 'patients', 'donors', 'blood_requests', 'blood_offers'];
    
    try {
        $tables_query = $conn->query("SHOW TABLES");
        $existing_tables = $tables_query->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Existing Tables:</h3>";
        echo "<ul>";
        foreach ($existing_tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        
        echo "<h3>Required Tables Status:</h3>";
        foreach ($required_tables as $table) {
            if (in_array($table, $existing_tables)) {
                echo "<div class='success'>✓ $table - EXISTS</div>";
            } else {
                echo "<div class='error'>✗ $table - MISSING</div>";
            }
        }
        
        // Check if all required tables exist
        $missing_tables = array_diff($required_tables, $existing_tables);
        if (empty($missing_tables)) {
            echo "<div class='success'><h3>✓ All required tables exist!</h3></div>";
        } else {
            echo "<div class='error'><h3>Missing tables: " . implode(', ', $missing_tables) . "</h3></div>";
            echo "<div class='info'>You need to create these tables. You can use the SQL commands you provided.</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>Error checking tables: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
}

// System information
echo "<div class='section'>";
echo "<h2>System Information</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>PDO Available:</strong> " . (extension_loaded('pdo') ? 'Yes' : 'No') . "</li>";
echo "<li><strong>PDO MySQL Available:</strong> " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "</li>";
echo "<li><strong>Current Directory:</strong> " . getcwd() . "</li>";
echo "<li><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>