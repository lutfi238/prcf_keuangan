<?php
require_once __DIR__ . '/../includes/config.php';

function describeTable($conn, $table) {
    echo "Table: $table\n";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    echo "\n";
}

echo "Tables in database:\n";
$tables = $conn->query("SHOW TABLES");
while ($row = $tables->fetch_array()) {
    echo $row[0] . "\n";
}
echo "\n";

$res = $conn->query("SHOW CREATE TABLE proposal_budget_details");
if ($row = $res->fetch_assoc()) {
    echo $row['Create Table'] . "\n";
}
// describeTable($conn, 'proyek');
?>
