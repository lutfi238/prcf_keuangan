<?php
require_once 'includes/config.php';

$email = 'pm@prcf.org';
$new_password = 'password123';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE user SET password_hash = ? WHERE email = ?");
$stmt->bind_param("ss", $hash, $email);

if ($stmt->execute()) {
    echo "Password for $email has been reset to: $new_password\n";
} else {
    echo "Error updating password: " . $conn->error . "\n";
}
?>
