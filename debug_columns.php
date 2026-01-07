<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = getenv('MYSQLHOST') ?: '127.0.0.1';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$name = getenv('MYSQLDATABASE') ?: 'prcf_keuangan';

$conn = new mysqli($host, $user, $pass, $name);
$res = $conn->query("SHOW COLUMNS FROM user");

echo "Field | Type | Null | Key | Default | Extra\n";
while ($row = $res->fetch_assoc()) {
    echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
}
?>
