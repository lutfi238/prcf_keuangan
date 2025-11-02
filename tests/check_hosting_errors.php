<?php
// Test untuk cek error di hosting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>System Check for InfinityFree</h2>";

// 1. PHP Version
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// 2. Required Extensions
$extensions = ['mysqli', 'session', 'json', 'curl'];
echo "<p><strong>PHP Extensions:</strong></p><ul>";
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "<li>$status $ext</li>";
}
echo "</ul>";

// 3. File Paths
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

// 4. Config File Check
$config_path = __DIR__ . '/../includes/config.php';
if (file_exists($config_path)) {
    echo "<p>✅ config.php exists</p>";
    
    // Try to include it
    try {
        require_once $config_path;
        echo "<p>✅ config.php loaded successfully</p>";
        
        // Test database connection
        if (isset($conn)) {
            echo "<p>✅ Database connected!</p>";
            echo "<p>Database: " . DB_NAME . "</p>";
        } else {
            echo "<p>❌ Database connection variable not set</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error loading config: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ config.php not found at: $config_path</p>";
}

// 5. Session Test
session_start();
$_SESSION['test'] = 'working';
echo "<p>✅ Session working</p>";

// 6. Upload Directory Permissions
$upload_dirs = ['uploads/budgets', 'uploads/receipts', 'uploads/tor'];
echo "<p><strong>Upload Directory Permissions:</strong></p><ul>";
foreach ($upload_dirs as $dir) {
    $full_path = __DIR__ . '/../' . $dir;
    if (is_dir($full_path)) {
        $writable = is_writable($full_path) ? '✅ Writable' : '❌ Not writable';
        echo "<li>$writable - $dir</li>";
    } else {
        echo "<li>❌ Not found - $dir</li>";
    }
}
echo "</ul>";

echo "<hr><p>If everything shows ✅, check database credentials in config.php</p>";
?>

