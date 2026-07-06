<?php
// test_db.php - Test database connection
require_once __DIR__ . '/config/database.php';

echo "<h1>Database Connection Test</h1>";

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected successfully!<br>";
    
    // Test query
    $result = $db->query("SELECT COUNT(*) as count FROM Members");
    $count = $result->fetch()['count'] ?? 0;
    echo "✅ Found " . $count . " members in the database.<br>";
    
    echo "<br>✅ All systems working!";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>