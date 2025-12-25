<?php
/**
 * Database Configuration for BloodConnect
 * WAMP Server Configuration
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'bloodconnect');
define('DB_USER', 'root');
define('DB_PASS', '14162121'); // Your WAMP password
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Database connection options
$db_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATE
];

/**
 * Database Connection Class
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $GLOBALS['db_options']);
            
            // Set timezone
            $this->connection->exec("SET time_zone = '+00:00'");
            
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            error_log("Database Test Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get database info
     */
    public function getDatabaseInfo() {
        try {
            $stmt = $this->connection->query("SELECT VERSION() as version, DATABASE() as database_name");
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database Info Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if tables exist
     */
    public function checkTables() {
        try {
            $stmt = $this->connection->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredTables = [
                'users', 'patients', 'donors', 'hospitals',
                'blood_inventory', 'blood_units', 'blood_requests',
                'donation_offers', 'donation_history', 'notifications',
                'activity_logs', 'system_settings'
            ];
            
            $missingTables = array_diff($requiredTables, $tables);
            
            return [
                'exists' => count($tables) > 0,
                'total_tables' => count($tables),
                'required_tables' => count($requiredTables),
                'missing_tables' => $missingTables,
                'all_tables_exist' => empty($missingTables)
            ];
            
        } catch (PDOException $e) {
            error_log("Check Tables Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute SQL file
     */
    public function executeSQLFile($filePath) {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("SQL file not found: " . $filePath);
            }
            
            $sql = file_get_contents($filePath);
            
            // Split SQL into individual statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($statement) {
                    return !empty($statement) && !preg_match('/^\s*--/', $statement);
                }
            );
            
            $this->connection->beginTransaction();
            
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $this->connection->exec($statement);
                }
            }
            
            $this->connection->commit();
            return true;
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            error_log("Execute SQL File Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Get database connection (helper function)
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Test database connection (helper function)
 */
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        return $db->testConnection();
    } catch (Exception $e) {
        error_log("Database Connection Test Failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Initialize database (helper function)
 */
function initializeDatabase() {
    try {
        $db = Database::getInstance();
        
        // Check if tables exist
        $tableCheck = $db->checkTables();
        
        if (!$tableCheck['all_tables_exist']) {
            // Execute database creation script
            $sqlFile = __DIR__ . '/../database/bloodconnect_database.sql';
            $db->executeSQLFile($sqlFile);
            
            return [
                'success' => true,
                'message' => 'Database initialized successfully',
                'tables_created' => $tableCheck['required_tables']
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Database already initialized',
            'tables_exist' => $tableCheck['total_tables']
        ];
        
    } catch (Exception $e) {
        error_log("Database Initialization Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Database initialization failed: ' . $e->getMessage()
        ];
    }
}