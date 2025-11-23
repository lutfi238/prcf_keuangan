<?php
require_once __DIR__ . '/../includes/config.php';

$email = 'lutfifirdaus238@gmail.com';
echo "Checking user: $email\n";

// 1. Check User Row
$stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    print_r($user);
} else {
    echo "User not found!\n";
}

// 2. Check Enum Definition
echo "\nChecking 'role' column definition:\n";
$res = $conn->query("SHOW COLUMNS FROM user LIKE 'role'");
$row = $res->fetch_assoc();
print_r($row);
?>
