<?php
require_once __DIR__ . '/../includes/config.php';

$villages = [
    ['1001', 'Nanga Jemah', 'NJ'],
    ['1002', 'Sri Wangi', 'SW'],
    ['1003', 'Nanga Lauk', 'NL'],
    ['1004', 'Tanjung Kapuas', 'TK'],
    ['1005', 'Nanga Betung', 'NB']
];

echo "Seeding Villages...\n";
$stmt = $conn->prepare("INSERT IGNORE INTO villages (village_code, village_name, village_abbr) VALUES (?, ?, ?)");

foreach ($villages as $v) {
    $stmt->bind_param("sss", $v[0], $v[1], $v[2]);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "Inserted: {$v[1]} ({$v[2]})\n";
        } else {
            echo "Skipped (Exists): {$v[1]}\n";
        }
    } else {
        echo "Error: " . $stmt->error . "\n";
    }
}
echo "Done.\n";
?>
