<?php
/**
 * TEST SESSION WRITE/READ
 * =======================
 * File ini untuk test apakah session_write_close() berfungsi dengan baik
 */

// Step 1: Write session
if (!isset($_GET['step'])) {
    session_start();
    $_SESSION['test_data'] = 'Test successful at ' . date('Y-m-d H:i:s');
    $_SESSION['test_logged_in'] = true;
    
    echo "<h1>Step 1: Writing Session</h1>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Data written: " . $_SESSION['test_data'] . "</p>";
    echo "<p>Logged in: " . ($_SESSION['test_logged_in'] ? 'YES' : 'NO') . "</p>";
    
    // Force write
    session_write_close();
    
    echo "<h2>Session written with session_write_close()</h2>";
    echo "<p><a href='?step=2'>Continue to Step 2 (Redirect Test)</a></p>";
    exit();
}

// Step 2: Redirect
if ($_GET['step'] == 2) {
    session_start();
    $_SESSION['test_redirect'] = 'Redirect successful';
    session_write_close();
    
    header('Location: test_session_write.php?step=3');
    exit();
}

// Step 3: Read after redirect
if ($_GET['step'] == 3) {
    session_start();
    
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Session Test Result</title>";
    echo "<style>body{font-family:sans-serif;padding:20px;} .success{color:green;} .error{color:red;}</style>";
    echo "</head><body>";
    
    echo "<h1>Step 3: Reading Session After Redirect</h1>";
    echo "<p>Session ID: " . session_id() . "</p>";
    
    if (isset($_SESSION['test_logged_in']) && $_SESSION['test_logged_in']) {
        echo "<p class='success'>✅ test_logged_in: YES - Session persisted!</p>";
    } else {
        echo "<p class='error'>❌ test_logged_in: NO - Session lost!</p>";
    }
    
    if (isset($_SESSION['test_data'])) {
        echo "<p class='success'>✅ test_data: " . $_SESSION['test_data'] . "</p>";
    } else {
        echo "<p class='error'>❌ test_data: NOT FOUND</p>";
    }
    
    if (isset($_SESSION['test_redirect'])) {
        echo "<p class='success'>✅ test_redirect: " . $_SESSION['test_redirect'] . "</p>";
    } else {
        echo "<p class='error'>❌ test_redirect: NOT FOUND</p>";
    }
    
    echo "<hr>";
    echo "<h2>All Session Data:</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<hr>";
    echo "<p><a href='test_session_write.php?step=cleanup'>Clean up and restart</a></p>";
    
    echo "</body></html>";
}

// Cleanup
if (isset($_GET['step']) && $_GET['step'] == 'cleanup') {
    session_start();
    session_destroy();
    echo "<h1>Session Cleaned</h1>";
    echo "<p><a href='test_session_write.php'>Start over</a></p>";
}
?>
