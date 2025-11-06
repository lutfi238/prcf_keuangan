<?php
/**
 * TEST REDIRECT METHODS
 * =====================
 * File ini untuk test berbagai metode redirect
 */

session_start();

// Set test session data
$_SESSION['test_logged_in'] = true;
$_SESSION['test_user'] = 'Test User';
$_SESSION['test_time'] = date('Y-m-d H:i:s');

$method = $_GET['method'] ?? 'menu';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Redirect Methods</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">🔄 Test Redirect Methods</h1>

            <?php if ($method === 'menu'): ?>
                <div class="space-y-4">
                    <p class="text-gray-600 mb-4">Test berbagai metode redirect dari auth/verify_otp.php ke dashboard:</p>
                    
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold mb-2">Method 1: PHP header() redirect</h3>
                        <p class="text-sm text-gray-600 mb-3">Standard PHP redirect dengan header()</p>
                        <a href="?method=header" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Test Header Redirect
                        </a>
                    </div>

                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold mb-2">Method 2: Meta refresh</h3>
                        <p class="text-sm text-gray-600 mb-3">HTML meta tag refresh</p>
                        <a href="?method=meta" class="inline-block bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Test Meta Refresh
                        </a>
                    </div>

                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold mb-2">Method 3: JavaScript redirect</h3>
                        <p class="text-sm text-gray-600 mb-3">JavaScript window.location.href</p>
                        <a href="?method=javascript" class="inline-block bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                            Test JavaScript Redirect
                        </a>
                    </div>

                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold mb-2">Method 4: Combined (Current Fix)</h3>
                        <p class="text-sm text-gray-600 mb-3">Meta + JavaScript (used in verify_otp.php)</p>
                        <a href="?method=combined" class="inline-block bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                            Test Combined Redirect
                        </a>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded">
                    <h3 class="font-semibold text-blue-800 mb-2">Session Info:</h3>
                    <pre class="text-xs text-blue-700"><?php print_r($_SESSION); ?></pre>
                </div>

            <?php elseif ($method === 'header'): ?>
                <?php
                // Test PHP header redirect
                session_write_close();
                header('Location: test_redirect_target.php?method=header');
                exit();
                ?>

            <?php elseif ($method === 'meta'): ?>
                <?php session_write_close(); ?>
                <meta http-equiv="refresh" content="0;url=test_redirect_target.php?method=meta">
                <p>Redirecting via meta refresh...</p>

            <?php elseif ($method === 'javascript'): ?>
                <?php session_write_close(); ?>
                <script>
                    window.location.href = "test_redirect_target.php?method=javascript";
                </script>
                <p>Redirecting via JavaScript...</p>

            <?php elseif ($method === 'combined'): ?>
                <?php session_write_close(); ?>
                <meta http-equiv="refresh" content="0;url=test_redirect_target.php?method=combined">
                <script>
                    window.location.href = "test_redirect_target.php?method=combined";
                </script>
                <p>Redirecting via combined method...</p>
                <p>If not redirected, <a href="test_redirect_target.php?method=combined">click here</a>.</p>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>
