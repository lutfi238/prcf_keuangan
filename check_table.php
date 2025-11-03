<?php
include 'includes/config.php';

$result = $conn->query('SHOW COLUMNS FROM user');
echo "User table columns:\n";
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']} " . ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . " " . ($row['Default'] ? "DEFAULT '{$row['Default']}'" : '') . "\n";
}

$conn->close();
?>
