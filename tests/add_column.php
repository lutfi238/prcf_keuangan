<?php
include 'includes/config.php';

$sql = "ALTER TABLE user ADD COLUMN status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'inactive' AFTER role";

echo "Adding status column to user table...\n";
if ($conn->query($sql) === TRUE) {
    echo "✅ Status column added successfully\n";

    // Update existing users to active status
    $update_sql = "UPDATE user SET status = 'active'";
    if ($conn->query($update_sql) === TRUE) {
        echo "✅ Existing users set to active status\n";
    } else {
        echo "❌ Error updating existing users: " . $conn->error . "\n";
    }
} else {
    echo "❌ Error adding column: " . $conn->error . "\n";
}

$conn->close();
?>
