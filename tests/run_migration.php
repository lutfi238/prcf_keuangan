<?php
include 'includes/config.php';

echo "Running user status migration...\n\n";

$sql = file_get_contents('sql/migrations/add_user_status_field.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement) && !preg_match('/^--/', $statement)) {
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        if ($conn->query($statement) === TRUE) {
            echo "✅ Success\n";
        } else {
            echo "❌ Error: " . $conn->error . "\n";
        }
        echo "\n";
    }
}

echo "Migration completed!\n";

// Verify the changes
echo "\nVerifying changes...\n";
$result = $conn->query("DESCRIBE user");
if ($result) {
    echo "User table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']} " . ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . " " . ($row['Default'] ? "DEFAULT '{$row['Default']}'" : "") . "\n";
    }
}

$conn->close();
?>
