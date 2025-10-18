<?php
header('Content-Type: text/plain');

try {
    // Check MongoDB connection
    $db = getMySQLDB();
    if (!$db) throw new Exception('MYSQLDB connection failed');
    
    // Check Redis connection  
    $redis = getRedis();
    if (!$redis) throw new Exception('Redis connection failed');
    $redis->ping();
    
    
    http_response_code(200);
    echo "OK - All services connected";
} catch (Exception $e) {
    http_response_code(503);
    echo "ERROR: " . $e->getMessage();
}
?>