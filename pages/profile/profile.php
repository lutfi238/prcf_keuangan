<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check username availability
if (isset($_POST['check_username'])) {
    $username = $_POST['username'];
    $stmt = $conn->prepare("SELECT id_user FROM user WHERE nama = ? AND id_user != ?");
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(['available' => $result->num_rows === 0]);
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_username = $_POST['username'];
    
    // Get current username
    $stmt = $conn->prepare("SELECT nama FROM user WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $current_user = $stmt->get_result()->fetch_assoc();
    
    // Check if username actually changed
    if ($new_username === $current_user['nama']) {
        // No changes, redirect back with info message
        header('Location: profile.php?info=nochanges');
        exit();
    }
    
    // Check if username is taken
    $stmt = $conn->prepare("SELECT id_user FROM user WHERE nama = ? AND id_user != ?");
    $stmt->bind_param("si", $new_username, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        // Username taken, redirect with error
        header('Location: profile.php?error=username_taken');
        exit();
    } else {
        $stmt = $conn->prepare("UPDATE user SET nama = ? WHERE id_user = ?");
        $stmt->bind_param("si", $new_username, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $new_username;
            // Redirect to prevent form resubmission (PRG pattern)
            header('Location: profile.php?success=profile_updated');
            exit();
        } else {
            header('Location: profile.php?error=update_failed');
            exit();
        }
    }
}

// Handle password change (SIMPLIFIED - NO OTP for prototype)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password
    $stmt = $conn->prepare("SELECT password_hash FROM user WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    
    if (!password_verify($old_password, $user_data['password_hash'])) {
        // PRG Pattern: Redirect with error
        header('Location: profile.php?error=wrong_old_password');
        exit();
    } elseif (strlen($new_password) < 8) {
        header('Location: profile.php?error=password_too_short');
        exit();
    } elseif ($new_password !== $confirm_password) {
        header('Location: profile.php?error=password_mismatch');
        exit();
    } else {
        // Direct update password (NO OTP untuk prototype)
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user SET password_hash = ? WHERE id_user = ?");
        $stmt->bind_param("si", $new_hash, $user_id);
        
        if ($stmt->execute()) {
            // Redirect to dashboard based on role after password change
            $user_role = $_SESSION['user_role'];
            switch ($user_role) {
                case 'Project Manager':
                    header('Location: ../dashboards/dashboard_pm.php?success=password_changed');
                    break;
                case 'Staff Accountant':
                    header('Location: ../dashboards/dashboard_sa.php?success=password_changed');
                    break;
                case 'Finance Manager':
                    header('Location: ../dashboards/dashboard_fm.php?success=password_changed');
                    break;
                case 'Direktur':
                    header('Location: ../dashboards/dashboard_dir.php?success=password_changed');
                    break;
                default:
                    header('Location: profile.php?success=password_changed');
            }
            exit();
        } else {
            header('Location: profile.php?error=update_failed');
            exit();
        }
    }
}

/* 
// ============================================================================
// DISABLED FOR PROTOTYPE: OTP-based password change
// ============================================================================
// Uncomment below jika ingin aktifkan OTP untuk ganti password

// Verify OTP for password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_password_otp'])) {
    $entered_otp = $_POST['otp'];
    
    if (time() - $_SESSION['password_change_time'] > 60) {
        $error = 'Kode OTP telah kadaluarsa';
    } elseif ($entered_otp == $_SESSION['password_change_otp']) {
        $new_hash = $_SESSION['new_password_hash'];
        $stmt = $conn->prepare("UPDATE user SET password_hash = ? WHERE id_user = ?");
        $stmt->bind_param("si", $new_hash, $user_id);
        
        if ($stmt->execute()) {
            unset($_SESSION['password_change_otp']);
            unset($_SESSION['password_change_time']);
            unset($_SESSION['new_password_hash']);
            $success = 'Password berhasil diubah!';
        } else {
            $error = 'Gagal mengubah password';
        }
    } else {
        $error = 'Kode OTP salah';
        $show_otp = true;
    }
}

// Resend OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_password_otp'])) {
    $stmt = $conn->prepare("SELECT email FROM user WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    $otp = rand(100000, 999999);
    $_SESSION['password_change_otp'] = $otp;
    $_SESSION['password_change_time'] = time();
    
    send_otp_email($user['email'], $otp);
    $show_otp = true;
    $success = 'Kode OTP baru telah dikirim';
}
*/ // END DISABLED BLOCK

// Get user data
$stmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found, session invalid
    session_destroy();
    header('Location: login.php?error=session_invalid');
    exit();
}

$user = $result->fetch_assoc();

// Extra safety check
if (!$user) {
    session_destroy();
    header('Location: login.php?error=user_not_found');
    exit();
}

// Handle GET parameters for messages
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'profile_updated':
            $success = 'Profil berhasil diperbarui!';
            break;
        case 'password_changed':
            $success = 'Password berhasil diubah!';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'username_taken':
            $error = 'Username sudah digunakan';
            break;
        case 'update_failed':
            $error = 'Gagal memperbarui profil';
            break;
        case 'wrong_old_password':
            $error = 'Password lama salah';
            break;
        case 'password_too_short':
            $error = 'Password baru minimal 8 karakter';
            break;
        case 'password_mismatch':
            $error = 'Konfirmasi password tidak cocok';
            break;
        case 'session_invalid':
        case 'user_not_found':
            $error = 'Session tidak valid, silakan login kembali';
            break;
    }
}

if (isset($_GET['info'])) {
    if ($_GET['info'] === 'nochanges') {
        $error = 'Tidak ada perubahan yang disimpan';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - PRCF INDONESIA Financial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="javascript:history.back()" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">Edit Profil</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user['nama']; ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Profile Picture -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6 text-center border border-gray-200">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['nama']); ?>&background=3B82F6&color=fff&size=200" 
                        class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-blue-400">
                    <h3 class="font-bold text-gray-800 text-lg"><?php echo $user['nama']; ?></h3>
                    <p class="text-sm text-gray-600 mt-1"><?php echo $user['role']; ?></p>
                    <p class="text-sm text-gray-500 mt-2"><?php echo $user['email']; ?></p>
                </div>
            </div>

            <!-- Edit Forms -->
            <div class="md:col-span-2 space-y-6">
                <!-- Update Username -->
                <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Informasi Profil</h3>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Username</label>
                            <input type="text" name="username" id="username" value="<?php echo $user['nama']; ?>" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <p id="usernameStatus" class="text-sm mt-1"></p>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                            <input type="email" value="<?php echo $user['email']; ?>" readonly
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                            <p class="text-xs text-gray-500 mt-1">Email tidak dapat diubah</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Role</label>
                            <input type="text" value="<?php echo $user['role']; ?>" readonly
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                            <p class="text-xs text-gray-500 mt-1">Role tidak dapat diubah</p>
                        </div>

                        <button type="submit" name="update_profile" id="saveProfileBtn"
                            class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition duration-200 font-medium disabled:bg-gray-400 disabled:cursor-not-allowed"
                            disabled>
                            <i class="fas fa-save mr-2"></i> Simpan Perubahan
                        </button>
                        <p id="changeStatus" class="text-sm text-gray-500 mt-2 text-center">Tidak ada perubahan</p>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Ubah Password</h3>
                    
                    <div id="passwordForm">
                        <button onclick="togglePasswordForm()" id="showPasswordBtn"
                            class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                            <i class="fas fa-key mr-1"></i> Change Password
                        </button>

                        <form method="POST" id="changePasswordForm" class="hidden space-y-4 mt-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">Password Lama *</label>
                                <input type="password" name="old_password" required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">Password Baru *</label>
                                <input type="password" name="new_password" id="new_password" required minlength="8"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <p class="text-xs text-gray-500 mt-1">Minimal 8 karakter</p>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">Konfirmasi Password Baru *</label>
                                <input type="password" name="confirm_password" id="confirm_password" required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>

                            <div class="flex space-x-3">
                                <button type="button" onclick="togglePasswordForm()"
                                    class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition duration-200 font-medium">
                                    Batal
                                </button>
                                <button type="submit" name="change_password"
                                    class="flex-1 bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition duration-200 font-medium">
                                    <i class="fas fa-key mr-2"></i> Ubah Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (isset($show_otp) && $show_otp): ?>
                <!-- OTP Verification for Password Change -->
                <div class="bg-white rounded-lg shadow-lg p-6 border border-blue-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Verifikasi OTP</h3>
                    <p class="text-sm text-gray-600 mb-4">Kode OTP telah dikirim ke email Anda. Masukkan kode untuk mengkonfirmasi perubahan password.</p>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Kode OTP</label>
                            <input type="text" name="otp" required maxlength="6" pattern="[0-9]{6}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-center text-2xl tracking-widest"
                                placeholder="000000">
                        </div>

                        <div class="text-center text-sm text-gray-600">
                            Kode berlaku: <span id="timer" class="font-bold text-blue-600">60</span> detik
                        </div>

                        <button type="submit" name="verify_password_otp" 
                            class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition duration-200 font-medium">
                            Verifikasi
                        </button>

                        <button type="submit" name="resend_password_otp"
                            class="w-full bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition duration-200 font-medium">
                            Kirim Ulang Kode
                        </button>
                    </form>
                </div>

                <script>
                    let timeLeft = 60;
                    const timerElement = document.getElementById('timer');

                    const countdown = setInterval(() => {
                        if (timeLeft <= 0) {
                            clearInterval(countdown);
                            timerElement.textContent = '0';
                            timerElement.parentElement.innerHTML = '<span class="text-red-600 font-bold">Kode telah kadaluarsa</span>';
                        } else {
                            timeLeft--;
                            timerElement.textContent = timeLeft;
                        }
                    }, 1000);
                </script>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Check username availability and enable/disable save button
        let typingTimer;
        const originalUsername = '<?php echo $user['nama']; ?>';
        const saveBtn = document.getElementById('saveProfileBtn');
        const changeStatus = document.getElementById('changeStatus');
        
        document.getElementById('username').addEventListener('input', function() {
            clearTimeout(typingTimer);
            const username = this.value.trim();
            
            // Check if username changed
            if (username === originalUsername || username === '') {
                // No changes or empty - disable button
                saveBtn.disabled = true;
                changeStatus.textContent = 'Tidak ada perubahan';
                changeStatus.className = 'text-sm text-gray-500 mt-2 text-center';
                document.getElementById('usernameStatus').textContent = '';
            } else {
                // Username changed - enable button and check availability
                saveBtn.disabled = false;
                changeStatus.textContent = 'Tekan simpan untuk menyimpan perubahan';
                changeStatus.className = 'text-sm text-blue-600 mt-2 text-center';
                
                typingTimer = setTimeout(() => {
                    fetch('profile.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'check_username=1&username=' + encodeURIComponent(username)
                    })
                    .then(r => r.json())
                    .then(data => {
                        const status = document.getElementById('usernameStatus');
                        if (data.available) {
                            status.textContent = '✓ Username tersedia';
                            status.className = 'text-green-600 text-sm mt-1 font-medium';
                            saveBtn.disabled = false;
                        } else {
                            status.textContent = '✗ Username sudah digunakan';
                            status.className = 'text-red-600 text-sm mt-1 font-medium';
                            saveBtn.disabled = true;
                        }
                    });
                }, 500);
            }
        });

        function togglePasswordForm() {
            const form = document.getElementById('changePasswordForm');
            const btn = document.getElementById('showPasswordBtn');
            
            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                btn.classList.add('hidden');
            } else {
                form.classList.add('hidden');
                btn.classList.remove('hidden');
            }
        }

        // Password confirmation validation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPass = document.getElementById('new_password').value;
            if (this.value && this.value !== newPass) {
                this.setCustomValidity('Password tidak cocok');
            } else {
                this.setCustomValidity('');
            }
        });

        // Fix back button behavior - Remove query parameters from URL after showing message
        // This prevents double-back issue caused by PRG (Post-Redirect-Get) pattern
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success') || urlParams.has('error') || urlParams.has('info')) {
                // Wait for user to see the message, then clean URL
                setTimeout(function() {
                    // Replace current history entry without query params
                    const cleanUrl = window.location.protocol + "//" + 
                                    window.location.host + 
                                    window.location.pathname;
                    window.history.replaceState({}, document.title, cleanUrl);
                }, 100); // Small delay to ensure message is visible
            }
        })();
    </script>
</body>
</html>