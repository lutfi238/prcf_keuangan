<?php
// Test maintenance mode functionality
require_once 'includes/maintenance_config.php';

echo "<h1>Maintenance Mode Test</h1>\n";
echo "<pre>\n";

// Test current status
echo "MAINTENANCE_MODE constant: " . (MAINTENANCE_MODE ? 'true' : 'false') . "\n";
echo "is_maintenance_active(): " . (is_maintenance_active() ? 'true' : 'false') . "\n\n";

// Test IP whitelist
echo "Your IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
echo "is_ip_whitelisted(): " . (is_ip_whitelisted() ? 'true' : 'false') . "\n\n";

// Test whitelist array
echo "Whitelist IPs: " . print_r($MAINTENANCE_WHITELIST_IPS, true) . "\n\n";

// Test scheduled maintenance (if set)
echo "MAINTENANCE_START: '" . MAINTENANCE_START . "'\n";
echo "MAINTENANCE_END: '" . MAINTENANCE_END . "'\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";

// Test the path calculation logic (using the same logic as check_maintenance function)
echo "\nPath calculation test:\n";
$script_name = $_SERVER['SCRIPT_NAME'];
$script_dir = dirname($script_name);
$depth = substr_count($script_dir, '/') - 1; // Subtract 1 for the leading slash
$depth = max(0, $depth);
$prefix = str_repeat('../', $depth);
echo "Script name: $script_name\n";
echo "Script dir: $script_dir\n";
echo "Depth: $depth\n";
echo "Prefix: '$prefix'\n";
echo "Expected maintenance page path: {$prefix}public/maintenance.php\n";

// Test if maintenance page exists
$maintenance_path = __DIR__ . '/' . $prefix . 'public/maintenance.php';
echo "Absolute path to maintenance page: $maintenance_path\n";
echo "Maintenance page exists: " . (file_exists($maintenance_path) ? 'YES' : 'NO') . "\n";

echo "</pre>\n";
?>
