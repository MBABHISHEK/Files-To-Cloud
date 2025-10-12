<?php
// GCP Configuration for CloudCanvas

// MongoDB configuration (Cloud MongoDB or Atlas)
define('DB_HOST', getenv('DB_HOST') ?: 'mongodb://localhost:27017');
define('DB_NAME', getenv('DB_NAME') ?: 'cloudcanvas');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Redis configuration for sessions
define('REDIS_HOST', getenv('REDIS_HOST') ?: 'redis-service');
define('REDIS_PORT', getenv('REDIS_PORT') ?: 6379);

// GCS configuration for file storage
define('GCS_BUCKET', getenv('GCS_BUCKET') ?: 'cloudcanvas-bucket');
define('GCP_PROJECT_ID', getenv('GCP_PROJECT_ID') ?: 'your-project-id');

// Application settings
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Initialize MongoDB client with auth
function getMongoDB() {
    static $client = null;
    if ($client === null) {
        try {
            $connectionString = DB_HOST;
            $options = [];
            
            if (DB_USER && DB_PASS) {
                $options = [
                    'username' => DB_USER,
                    'password' => DB_PASS,
                    'authSource' => 'admin'
                ];
            }
            
            $client = new MongoDB\Client($connectionString, $options);
        } catch (Exception $e) {
            error_log("MongoDB connection failed: " . $e->getMessage());
            return null;
        }
    }
    return $client->selectDatabase(DB_NAME);
}

// Initialize Redis client
function getRedis() {
    static $redis = null;
    if ($redis === null) {
        try {
            $redis = new Predis\Client([
                'host' => REDIS_HOST,
                'port' => REDIS_PORT,
                'timeout' => 2.5,
            ]);
        } catch (Exception $e) {
            error_log("Redis connection failed: " . $e->getMessage());
            return null;
        }
    }
    return $redis;
}

// Initialize Google Cloud Storage client
function getGCSClient() {
    static $storage = null;
    if ($storage === null) {
        try {
            $storage = new Google\Cloud\Storage\StorageClient([
                'projectId' => GCP_PROJECT_ID,
            ]);
        } catch (Exception $e) {
            error_log("GCS client initialization failed: " . $e->getMessage());
            return null;
        }
    }
    return $storage;
}

// Upload file to GCS
function uploadToGCS($fileTmpPath, $filename, $contentType) {
    $storage = getGCSClient();
    if (!$storage) return false;
    
    try {
        $bucket = $storage->bucket(GCS_BUCKET);
        $object = $bucket->upload(
            fopen($fileTmpPath, 'r'),
            [
                'name' => $filename,
                'predefinedAcl' => 'publicRead',
                'metadata' => [
                    'contentType' => $contentType
                ]
            ]
        );
        
        return $object->info()['mediaLink'];
    } catch (Exception $e) {
        error_log("GCS upload failed: " . $e->getMessage());
        return false;
    }
}

// Session management with Redis
function startSession() {
    $redis = getRedis();
    if ($redis) {
        session_set_save_handler(
            new RedisSessionHandler($redis)
        );
    }
    session_start();
}

class RedisSessionHandler implements SessionHandlerInterface {
    private $redis;
    private $ttl;

    public function __construct($redis, $ttl = 3600) {
        $this->redis = $redis;
        $this->ttl = $ttl;
    }

    public function open($savePath, $sessionName) { return true; }
    public function close() { return true; }
    public function gc($maxlifetime) { return true; }

    public function read($sessionId) {
        return $this->redis->get("sessions:{$sessionId}") ?: '';
    }

    public function write($sessionId, $data) {
        return $this->redis->setex("sessions:{$sessionId}", $this->ttl, $data);
    }

    public function destroy($sessionId) {
        return $this->redis->del("sessions:{$sessionId}") > 0;
    }
}
?>