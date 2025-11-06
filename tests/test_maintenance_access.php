<?php
// Simulate accessing index.php with maintenance mode enabled
// This will test if the redirect works properly

// Set up server variables like a real web request
$_SERVER['SCRIPT_NAME'] = '/prcf_keuangan/index.php';
$_SERVER['PHP_SELF'] = '/prcf_keuangan/index.php';

// Include maintenance config
require_once 'includes/maintenance_config.php';

echo "Testing maintenance mode redirect from index.php...\n\n";

// Check if maintenance is active
echo "Maintenance mode active: " . (is_maintenance_active() ? 'YES' : 'NO') . "\n";

// Test the check_maintenance function (but don't actually redirect)
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
    echo "Full path: $full_path\n";
    echo "File exists: " . (file_exists($full_path) ? 'YES' : 'NO') . "\n";
} else {
    echo "No redirect needed\n";
}
?>
