<?php
require_once 'includes/config.php';

$email = 'sa@prcf.org';
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'Secretariat Admin'; // or whatever SA stands for? Let's check existing roles.
// Actually, let's list roles first.

echo "Checking for $email...\n";

$check = $conn->prepare("SELECT id_user, role FROM user WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $u = $res->fetch_assoc();
    echo "User found. Role: " . $u['role'] . "\n";
    // Correct column is password_hash
    $upd = $conn->prepare("UPDATE user SET password_hash = ? WHERE email = ?");
    $upd->bind_param("ss", $hash, $email);
    $upd->execute();
    echo "Password RESET to '$password'.\n";
} else {
    echo "User NOT found. Creating...\n";
    $role = 'Administrator'; // Guessing SA = Administrator
    $nama = 'Super Admin';
    $status = 'active';
    
    // Correct column is password_hash
    $ins = $conn->prepare("INSERT INTO user (email, password_hash, nama, role, status) VALUES (?, ?, ?, ?, ?)");
    $ins->bind_param("sssss", $email, $hash, $nama, $role, $status);
    try {
        $ins->execute();
        echo "User CREATED with role '$role' and password '$password'.\n";
    } catch (Exception $e) {
        echo "Failed to create: " . $e->getMessage() . "\n";
    }
}
?>
