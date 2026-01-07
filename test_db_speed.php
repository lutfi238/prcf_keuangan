<?php
require_once 'includes/config.php'; // To get credentials

$hosts = ['localhost', '127.0.0.1'];
$user = DB_USER;
$pass = DB_PASS;
$name = DB_NAME;

echo "<h3>Database Connection Benchmark</h3>";

foreach ($hosts as $host) {
    $start = microtime(true);
    $mysqli = new mysqli($host, $user, $pass, $name);
    $end = microtime(true);
    
    $duration = ($end - $start) * 1000;
    
    if ($mysqli->connect_error) {
        echo "Host: <b>$host</b> - Failed: " . $mysqli->connect_error . "<br>";
    } else {
        echo "Host: <b>$host</b> - Time: <b>" . number_format($duration, 2) . " ms</b><br>";
        $mysqli->close();
    }
}
?>
