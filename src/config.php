<?php
// MySQL Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'cloudcanvas');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Session configuration (using database sessions instead of Redis)
define('SESSION_TABLE', getenv('SESSION_TABLE') ?: 'sessions');
define('SESSION_LIFETIME', getenv('SESSION_LIFETIME') ?: 3600);

// Application settings
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Initialize MySQL database connection
function getMySQLDB() {
    static $db = null;
    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $db = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("MySQL connection failed: " . $e->getMessage());
            return null;
        }
    }
    return $db;
}

// Alternative MySQLi version (if you prefer MySQLi over PDO)
function getMySQLiDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            if ($db->connect_error) {
                throw new Exception("Connection failed: " . $db->connect_error);
            }
            $db->set_charset(DB_CHARSET);
        } catch (Exception $e) {
            error_log("MySQLi connection failed: " . $e->getMessage());
            return null;
        }
    }
    return $db;
}

// Session management with MySQL
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        // Set custom session handler if using database sessions
        if (getenv('USE_DB_SESSIONS')) {
            $db = getMySQLDB();
            if ($db) {
                $handler = new MySQLSessionHandler($db);
                session_set_save_handler($handler, true);
            }
        }
        
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        session_name('cloudcanvas_session');
        session_start();
    }
}

// MySQL Session Handler
class MySQLSessionHandler implements SessionHandlerInterface {
    private $db;
    private $table;

    public function __construct(PDO $db, $table = 'sessions') {
        $this->db = $db;
        $this->table = $table;
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT NOT NULL,
            timestamp INT NOT NULL,
            lifetime INT NOT NULL
        )";
        $this->db->exec($sql);
        
        // Create index on timestamp for garbage collection
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_timestamp ON {$this->table} (timestamp)");
    }

    public function open($savePath, $sessionName) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($sessionId) {
        $sql = "SELECT data FROM {$this->table} WHERE id = ? AND timestamp + lifetime > ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId, time()]);
        
        if ($row = $stmt->fetch()) {
            return $row['data'];
        }
        return '';
    }

    public function write($sessionId, $data) {
        $timestamp = time();
        $lifetime = SESSION_LIFETIME;
        
        $sql = "REPLACE INTO {$this->table} (id, data, timestamp, lifetime) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$sessionId, $data, $timestamp, $lifetime]);
    }

    public function destroy($sessionId) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$sessionId]);
    }

    public function gc($maxlifetime) {
        $sql = "DELETE FROM {$this->table} WHERE timestamp + lifetime < ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([time()]);
    }
}

// Database initialization function (run once during setup)
function initializeDatabase() {
    $db = getMySQLDB();
    if (!$db) return false;

    try {
        // Create users table
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email)
        )");

        // Create images table
        $db->exec("CREATE TABLE IF NOT EXISTS images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(50) NOT NULL,
            file_size INT NOT NULL,
            image_data LONGBLOB NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_public BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_uploaded_at (uploaded_at),
            INDEX idx_is_public (is_public)
        )");

        // Create sessions table (if using database sessions)
        if (getenv('USE_DB_SESSIONS')) {
            $db->exec("CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(128) PRIMARY KEY,
                data TEXT NOT NULL,
                timestamp INT NOT NULL,
                lifetime INT NOT NULL,
                INDEX idx_timestamp (timestamp)
            )");
        }

        return true;
    } catch (PDOException $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

// Utility function for database maintenance
function cleanupExpiredSessions() {
    $db = getMySQLDB();
    if (!$db) return false;

    try {
        $sql = "DELETE FROM sessions WHERE timestamp + lifetime < ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([time()]);
    } catch (PDOException $e) {
        error_log("Session cleanup failed: " . $e->getMessage());
        return false;
    }
}

// Close database connection (optional, for cleanup)
function closeDatabaseConnection() {
    // PDO connections are automatically closed when the script ends
    // This is just for completeness
    $db = null;
}
?>