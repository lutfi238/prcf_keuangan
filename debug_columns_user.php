<?php
require_once 'includes/config.php';
$res = $conn->query("SHOW COLUMNS FROM user");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}
?>
