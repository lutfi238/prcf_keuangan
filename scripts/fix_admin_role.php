<?php
require_once __DIR__ . '/../includes/config.php';

echo "Fixing Admin Role...\n";

// 1. Modify Column to include 'Admin'
$sql = "ALTER TABLE user MODIFY COLUMN role ENUM('Admin', 'Finance Manager', 'Project Manager', 'Staff Accountant', 'Direktur') NOT NULL";
if ($conn->query($sql)) {
    echo "Success: Added 'Admin' to role ENUM.\n";
} else {
    echo "Error modifying column: " . $conn->error . "\n";
}

// 2. Update User Role
$email = 'lutfifirdaus238@gmail.com';
$stmt = $conn->prepare("UPDATE user SET role = 'Admin' WHERE email = ?");
$stmt->bind_param("s", $email);
if ($stmt->execute()) {
    echo "Success: Updated user $email to 'Admin'.\n";
} else {
    echo "Error updating user: " . $stmt->error . "\n";
}

// 3. Verify
$stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
print_r($user);
?>
