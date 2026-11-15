<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>1. PHP Status: OK (Version " . phpversion() . ")</h1>";

require_once __DIR__ . '/config/database.php';

echo "<h2>2. Testing MySQL Database Connection...</h2>";
echo "Host: " . DB_HOST . "<br>";
echo "User: " . DB_USER . "<br>";
echo "DB: " . DB_NAME . "<br>";

try {
    $pdo = getDbConnection();
    echo "<h3 style='color: green;'>3. MySQL Connection: SUCCESSFUL!</h3>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found in database: " . implode(", ", $tables) . "<br>";
} catch (Exception $e) {
    echo "<h3 style='color: red;'>3. MySQL Connection FAILED:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
