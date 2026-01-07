<?php
require_once 'includes/config.php';

$email = 'sa@prcf.org';
$new_pass = 'password123';
$hash = password_hash($new_pass, PASSWORD_DEFAULT);

// Check if user exists
$stmt = $conn->prepare("SELECT id_user, nama, role, status FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $user = $res->fetch_assoc();
    echo "Found User: " . $user['nama'] . " | Role: " . $user['role'] . " | Status: " . $user['status'] . "<br>";
    
    // Update password
    $upd = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
    $upd->bind_param("ss", $hash, $email);
    if ($upd->execute()) {
        echo "✅ Password for $email has been reset to '$new_pass'<br>";
    } else {
        echo "❌ Failed to update password: " . $conn->error . "<br>";
    }
} else {
    echo "❌ User $email NOT FOUND in database.<br>";
    // Optional: List all users to see what's there
    echo "<hr>Available Users:<br>";
    $all = $conn->query("SELECT email, role FROM user");
    while ($row = $all->fetch_assoc()) {
        echo $row['email'] . " (" . $row['role'] . ")<br>";
    }
}
?>
