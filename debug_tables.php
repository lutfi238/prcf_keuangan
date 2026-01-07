<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = getenv('MYSQLHOST') ?: '127.0.0.1';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$name = getenv('MYSQLDATABASE') ?: 'prcf_keuangan';

$conn = new mysqli($host, $user, $pass, $name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$res = $conn->query("SHOW TABLES");

if (!$res) {
    die("Query failed: " . $conn->error);
}

echo "Tables in $name:\n";
while ($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
?>
