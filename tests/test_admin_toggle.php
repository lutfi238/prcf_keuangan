<?php
// Test the admin maintenance toggle functionality

echo "<h1>Testing Admin Maintenance Toggle</h1>\n";

// Test the regex pattern used in system_control.php
$config_content = file_get_contents('includes/maintenance_config.php');

echo "<h2>Current config content (relevant part):</h2>\n";
preg_match('/define\(\'MAINTENANCE_MODE\', (true|false)\);/', $config_content, $matches);
echo "<pre>Found: " . ($matches[0] ?? 'NOT FOUND') . "</pre>\n\n";

echo "<h2>Testing regex replacement:</h2>\n";

// Test replacing false with true
$new_content_true = preg_replace(
    '/define\(\'MAINTENANCE_MODE\', (true|false)\);/',
    "define('MAINTENANCE_MODE', true);",
    $config_content
);

echo "<strong>After setting to TRUE:</strong><br>\n";
preg_match('/define\(\'MAINTENANCE_MODE\', (true|false)\);/', $new_content_true, $matches);
echo "<pre>Found: " . ($matches[0] ?? 'NOT FOUND') . "</pre>\n\n";

// Test replacing true with false
$new_content_false = preg_replace(
    '/define\(\'MAINTENANCE_MODE\', (true|false)\);/',
    "define('MAINTENANCE_MODE', false);",
    $new_content_true
);

echo "<strong>After setting back to FALSE:</strong><br>\n";
preg_match('/define\(\'MAINTENANCE_MODE\', (true|false)\);/', $new_content_false, $matches);
echo "<pre>Found: " . ($matches[0] ?? 'NOT FOUND') . "</pre>\n\n";

echo "<h2>File permissions test:</h2>\n";
$writable = is_writable('includes/maintenance_config.php');
echo "Config file writable: " . ($writable ? 'YES' : 'NO') . "\n\n";

if ($writable) {
    echo "<strong>✅ Admin toggle should work correctly</strong>\n";
} else {
    echo "<strong>❌ File permissions issue - admin toggle will fail</strong>\n";
}
?>
