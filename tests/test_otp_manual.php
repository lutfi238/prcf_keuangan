<?php
/**
 * MANUAL OTP TESTER
 * =================
 * Tool untuk test OTP verification tanpa harus login
 */

session_start();

// Simulate OTP setup
if (!isset($_SESSION['test_otp'])) {
    $_SESSION['test_otp'] = rand(100000, 999999);
    $_SESSION['test_otp_time'] = time();
    $_SESSION['test_otp_attempts'] = 0;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify'])) {
        $entered = preg_replace('/\D/', '', $_POST['otp'] ?? '');
        $current = time();
        
        echo "<pre style='background:#f0f0f0;padding:10px;border:1px solid #ccc;margin:10px 0;'>";
        echo "DEBUG INFO:\n";
        echo "============\n";
        echo "Entered OTP: '$entered'\n";
        echo "Session OTP: '" . $_SESSION['test_otp'] . "'\n";
        echo "String comparison: " . ($entered === (string)$_SESSION['test_otp'] ? 'MATCH ✅' : 'NO MATCH ❌') . "\n";
        echo "Type of entered: " . gettype($entered) . "\n";
        echo "Type of session: " . gettype($_SESSION['test_otp']) . "\n";
        echo "Length entered: " . strlen($entered) . "\n";
        echo "Length session: " . strlen((string)$_SESSION['test_otp']) . "\n";
        echo "\nTime info:\n";
        echo "Current time: $current\n";
        echo "OTP time: " . $_SESSION['test_otp_time'] . "\n";
        echo "Difference: " . ($current - $_SESSION['test_otp_time']) . " seconds\n";
        echo "Expired (>60s): " . (($current - $_SESSION['test_otp_time']) > 60 ? 'YES ❌' : 'NO ✅') . "\n";
        echo "</pre>";
        
        if ($current - $_SESSION['test_otp_time'] > 60) {
            $error = 'OTP Expired';
        } elseif ($entered === (string)$_SESSION['test_otp']) {
            $success = '✅ OTP CORRECT! Verification would succeed.';
        } else {
            $error = '❌ OTP WRONG!';
            $_SESSION['test_otp_attempts']++;
        }
    } elseif (isset($_POST['reset'])) {
        $_SESSION['test_otp'] = rand(100000, 999999);
        $_SESSION['test_otp_time'] = time();
        $_SESSION['test_otp_attempts'] = 0;
        $success = 'New OTP generated!';
    }
}

$time_left = max(0, 60 - (time() - $_SESSION['test_otp_time']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual OTP Tester</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">🧪 Manual OTP Tester</h1>

            <!-- Current OTP Display -->
            <div class="mb-6 bg-blue-50 border-2 border-blue-300 rounded-lg p-4">
                <h2 class="font-semibold text-blue-800 mb-2">Current OTP (Copy this!):</h2>
                <div class="text-4xl font-mono font-bold text-blue-600 text-center py-4">
                    <?php echo $_SESSION['test_otp']; ?>
                </div>
                <p class="text-sm text-blue-700 text-center mt-2">
                    ⏱️ Time remaining: <strong><?php echo $time_left; ?></strong> seconds
                    <?php if ($time_left <= 0): ?>
                        <span class="text-red-600 font-bold">⚠️ EXPIRED!</span>
                    <?php endif; ?>
                </p>
                <p class="text-xs text-gray-600 text-center mt-2">
                    Session ID: <?php echo session_id(); ?>
                </p>
            </div>

            <?php if ($error): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <!-- Test Form -->
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Enter OTP:</label>
                    <input type="text" name="otp" maxlength="6" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg text-center text-2xl font-mono"
                        placeholder="0 0 0 0 0 0"
                        autofocus>
                </div>

                <div class="flex gap-4">
                    <button type="submit" name="verify" value="1"
                        class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                        Verify OTP
                    </button>
                    <button type="submit" name="reset" value="1"
                        class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 font-semibold">
                        Generate New OTP
                    </button>
                </div>
            </form>

            <!-- Session Info -->
            <div class="mt-6 border-t pt-6">
                <h3 class="font-semibold text-gray-700 mb-3">Session Data:</h3>
                <pre class="bg-gray-50 p-4 rounded text-xs overflow-x-auto"><?php print_r($_SESSION); ?></pre>
            </div>

            <!-- Instructions -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="font-semibold text-yellow-800 mb-2">📝 How to Use:</h3>
                <ol class="text-sm text-yellow-700 space-y-1 list-decimal list-inside">
                    <li>Copy the OTP displayed in blue box above</li>
                    <li>Paste it into the input field</li>
                    <li>Click "Verify OTP"</li>
                    <li>Check if it matches (should show DEBUG INFO)</li>
                    <li>If expired, click "Generate New OTP"</li>
                </ol>
            </div>

            <!-- Test Cases -->
            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-700 mb-3">🧪 Test Cases to Try:</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-start gap-2">
                        <span class="text-green-600">✅</span>
                        <span><strong>Correct OTP:</strong> Copy exact OTP from blue box</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-red-600">❌</span>
                        <span><strong>Wrong OTP:</strong> Type 123456 (should fail)</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-red-600">❌</span>
                        <span><strong>Expired OTP:</strong> Wait 60 seconds, then verify</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-blue-600">🔄</span>
                        <span><strong>With spaces:</strong> Type OTP with spaces (e.g., "123 456")</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh time remaining every second
        setInterval(function() {
            location.reload();
        }, 60000); // Reload every 60 seconds to show updated time

        // Log form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            console.log('Form submitting...');
            console.log('OTP value:', document.querySelector('input[name="otp"]').value);
        });
    </script>
</body>
</html>
