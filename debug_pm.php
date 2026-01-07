<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = getenv('MYSQLHOST') ?: '127.0.0.1';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$name = getenv('MYSQLDATABASE') ?: 'prcf_keuangan';

$conn = new mysqli($host, $user, $pass, $name);
$res = $conn->query("SELECT id_user, nama, email, role, status FROM user WHERE role = 'Project Manager'");

if ($res->num_rows > 0) {
    echo "PM Found:\n";
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No Project Manager found.\n";
    // List all roles
    $res = $conn->query("SELECT DISTINCT role FROM user");
    echo "Available roles:\n";
    while ($row = $res->fetch_assoc()) {
        echo $row['role'] . "\n";
    }
}
?>
