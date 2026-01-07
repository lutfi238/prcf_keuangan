<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

$res = $conn->query("SHOW COLUMNS FROM user");
$cols = [];
while ($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'];
}
echo "Columns: " . implode(", ", $cols) . "\n";

// Try to fetch all users
$res = $conn->query("SELECT * FROM user");
while ($row = $res->fetch_assoc()) {
    echo "User: " . ($row['email'] ?? 'no-email') . " | Role: " . ($row['role'] ?? 'no-role') . "\n";
}
?>
