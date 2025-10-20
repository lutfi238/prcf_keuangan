<?php
/**
 * REDIRECT TARGET PAGE
 * ====================
 * Halaman tujuan redirect untuk testing
 */

session_start();

$method = $_GET['method'] ?? 'unknown';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirect Test Result</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-6">
                <div class="inline-block bg-green-100 text-green-600 rounded-full p-4 mb-4">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-green-600 mb-2">✅ Redirect Successful!</h1>
                <p class="text-gray-600">Method: <strong><?php echo htmlspecialchars($method); ?></strong></p>
            </div>

            <div class="space-y-4">
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-lg mb-3 text-blue-600">Session Status</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Test Logged In:</span>
                            <span class="font-semibold <?php echo isset($_SESSION['test_logged_in']) && $_SESSION['test_logged_in'] ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo isset($_SESSION['test_logged_in']) && $_SESSION['test_logged_in'] ? '✅ YES' : '❌ NO'; ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Test User:</span>
                            <span class="font-semibold"><?php echo $_SESSION['test_user'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Test Time:</span>
                            <span class="font-semibold"><?php echo $_SESSION['test_time'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Session ID:</span>
                            <span class="font-mono text-xs"><?php echo session_id(); ?></span>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-lg mb-3 text-blue-600">All Session Data</h3>
                    <pre class="bg-gray-50 p-3 rounded text-xs overflow-x-auto"><?php print_r($_SESSION); ?></pre>
                </div>

                <div class="mt-6 p-4 <?php echo (isset($_SESSION['test_logged_in']) && $_SESSION['test_logged_in']) ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?> border rounded">
                    <h3 class="font-semibold mb-2">
                        <?php if (isset($_SESSION['test_logged_in']) && $_SESSION['test_logged_in']): ?>
                            <span class="text-green-800">✅ Test PASSED</span>
                        <?php else: ?>
                            <span class="text-red-800">❌ Test FAILED</span>
                        <?php endif; ?>
                    </h3>
                    <ul class="text-sm space-y-1">
                        <?php if (isset($_SESSION['test_logged_in']) && $_SESSION['test_logged_in']): ?>
                            <li class="text-green-700">✅ Session data preserved after redirect</li>
                            <li class="text-green-700">✅ Method "<?php echo htmlspecialchars($method); ?>" works correctly</li>
                            <li class="text-green-700">✅ This method dapat digunakan untuk verify_otp.php</li>
                        <?php else: ?>
                            <li class="text-red-700">❌ Session data lost after redirect</li>
                            <li class="text-red-700">❌ Method "<?php echo htmlspecialchars($method); ?>" has issues</li>
                            <li class="text-red-700">❌ DO NOT use this method for verify_otp.php</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="text-center mt-6">
                    <a href="test_redirect_methods.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                        ← Back to Test Menu
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
