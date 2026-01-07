<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = getenv('MYSQLHOST') ?: '127.0.0.1';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$name = getenv('MYSQLDATABASE') ?: 'prcf_keuangan';

$conn = new mysqli($host, $user, $pass, $name);
$res = $conn->query("SELECT * FROM user");

echo "Users Found: " . $res->num_rows . "\n";
echo "--------------------------------------------------\n";

while ($row = $res->fetch_assoc()) {
    foreach ($row as $key => $val) {
        if ($key == 'password_hash') $val = '[HASHED]';
        echo "$key: $val\n";
    }
    echo "--------------------------------------------------\n";
}
?>
