<?php
require_once __DIR__ . '/../includes/config.php';

$sqlFile = __DIR__ . '/../sql/migrations/add_budget_management_system.sql';
$sql = file_get_contents($sqlFile);

if ($sql === false) {
    die("Error reading SQL file: $sqlFile");
}

// Split SQL by semicolon to execute multiple queries
$queries = explode(';', $sql);

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) {
        continue;
    }

    try {
        if ($conn->query($query) === TRUE) {
            echo "Query executed successfully: " . substr($query, 0, 50) . "...\n";
        } else {
            if ($conn->errno == 1060) {
                echo "Column already exists (Skipped): " . substr($query, 0, 50) . "...\n";
            } else {
                echo "Error executing query: " . $conn->error . "\nQuery: " . $query . "\n";
            }
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}

echo "Migration completed.\n";
?>
