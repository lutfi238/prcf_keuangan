<?php
// Test maintenance mode redirect from a deep directory

// Simulate accessing pages/admin/manage_users.php
$_SERVER['SCRIPT_NAME'] = '/prcf_keuangan/pages/admin/manage_users.php';
$_SERVER['PHP_SELF'] = '/prcf_keuangan/pages/admin/manage_users.php';

require_once 'includes/maintenance_config.php';

echo "Testing maintenance mode redirect from pages/admin/manage_users.php...\n\n";

echo "Maintenance mode active: " . (is_maintenance_active() ? 'YES' : 'NO') . "\n";

if (is_maintenance_active()) {
    // Simulate the path calculation
    $script_name = $_SERVER['SCRIPT_NAME'];
    $script_dir = dirname($script_name);
    $depth = substr_count($script_dir, '/') - 1;
    $depth = max(0, $depth);
    $prefix = str_repeat('../', $depth);

    $redirect_url = $prefix . 'public/maintenance.php';
    echo "Would redirect to: $redirect_url\n";

    // Check if the file exists
    $full_path = __DIR__ . '/' . $prefix . 'public/maintenance.php';
    echo "Full path calculation: $full_path\n";
    echo "File exists at calculated path: " . (file_exists($full_path) ? 'YES' : 'NO') . "\n";

    // Also check the actual path
    $actual_path = __DIR__ . '/../public/maintenance.php';
    echo "Actual path (going up 1 level): $actual_path\n";
    echo "File exists at actual path: " . (file_exists($actual_path) ? 'YES' : 'NO') . "\n";

    // Also test the actual check_maintenance function but capture the redirect
    echo "\nTesting actual check_maintenance function...\n";
    echo "Note: This will try to redirect, but we'll catch it\n";
}
?>
