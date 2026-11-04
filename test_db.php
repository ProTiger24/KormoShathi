<?php
// test_db.php
header('Content-Type: text/html; charset=utf-8');

$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

echo "<h3>Render Environment Variables:</h3>";
echo "Host: " . var_export($host, true) . "<br>";
echo "Port: " . var_export($port, true) . "<br>";
echo "DB Name: " . var_export($dbname, true) . "<br>";
echo "User: " . var_export($user, true) . "<br>";
echo "Pass Length: " . strlen($pass) . " characters<br><br>";

if (!$host || !$port || !$user) {
    die("<h3 style='color:red;'>Error: Environment variables are not set correctly in Render.</h3>");
}

echo "<h3>Attempting Database Connection...</h3>";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // Aiven এর জন্য SSL সেটিংস
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "<h3 style='color:green;'>✅ Connection Successful! Your Render app can talk to Aiven.</h3>";
    
} catch (PDOException $e) {
    echo "<h3 style='color:red;'>❌ Connection Failed:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
