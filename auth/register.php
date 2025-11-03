<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();

// Check if registration is enabled
if (!defined('REGISTRATION_ENABLED') || !REGISTRATION_ENABLED) {
    // Registration disabled - show message
    $registration_disabled = true;
} else {
    $registration_disabled = false;
}

$error = '';
$success = '';

// Check username availability
if (isset($_POST['check_username'])) {
    $username = $_POST['username'];
    $stmt = $conn->prepare("SELECT id_user FROM user WHERE nama = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(['available' => $result->num_rows === 0]);
    exit();
}

// Check phone availability
if (isset($_POST['check_phone'])) {
    $phone = $_POST['phone'];
    $stmt = $conn->prepare("SELECT id_user FROM user WHERE no_HP = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(['available' => $result->num_rows === 0]);
    exit();
}

// Check email availability
if (isset($_POST['check_email'])) {
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT id_user FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(['available' => $result->num_rows === 0]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Check if registration is disabled
    if ($registration_disabled) {
        $error = 'Pendaftaran akun baru saat ini dinonaktifkan oleh administrator. Silakan hubungi admin untuk membuat akun.';
    } else {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $phone = $_POST['phone'];
        $role = $_POST['role'];
        
        // Validation
        if (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter';
        } elseif ($password !== $confirm_password) {
            $error = 'Password tidak cocok';
        } else {
            // Validate phone number jika diisi
            $phone_validation = validate_phone_number_format($phone);
            if (!$phone_validation['valid']) {
                $error = $phone_validation['error'];
            } else {
                // Check if username exists
                $stmt = $conn->prepare("SELECT id_user FROM user WHERE nama = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'Username sudah terdaftar';
                } else {
                    // Check if email exists
                    $stmt = $conn->prepare("SELECT id_user FROM user WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $error = 'Email sudah terdaftar';
                    } else {
                        // Format phone number for contact purposes
                        $phone_formatted = format_phone_number($phone);
                        
                        // Check if phone number already exists
                        $stmt = $conn->prepare("SELECT id_user FROM user WHERE no_HP = ?");
                        $stmt->bind_param("s", $phone_formatted);
                        $stmt->execute();
                        if ($stmt->get_result()->num_rows > 0) {
                            $error = 'Nomor telepon sudah terdaftar';
                        } else {
                            // Insert user
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare("INSERT INTO user (nama, email, password_hash, no_HP, role) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("sssss", $username, $email, $password_hash, $phone_formatted, $role);
                            
                            if ($stmt->execute()) {
                                $success = 'Akun berhasil dibuat! Silakan login.';
                            } else {
                                $error = 'Gagal membuat akun: ' . $conn->error;
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun - PRCFI Financial Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-8">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Buat Akun Baru</h1>
            <p class="text-gray-600">PRCF INDONESIA Financial - Sistem Manajemen Keuangan</p>
        </div>

        <?php if ($registration_disabled): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-4">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-2xl mr-3"></i>
                    <div>
                        <h3 class="font-bold mb-2">Pendaftaran Ditutup</h3>
                        <p class="text-sm">Pendaftaran akun baru saat ini dinonaktifkan oleh administrator.</p>
                        <p class="text-sm mt-2">Silakan hubungi admin untuk membuat akun baru.</p>
                    </div>
                </div>
            </div>
            <div class="mt-6 text-center">
                <a href="login.php" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Login
                </a>
            </div>
        <?php else: ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
                <div class="mt-4">
                    <a href="login.php" class="text-blue-500 hover:text-blue-700 font-medium">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" id="registerForm" class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Username</label>
                    <input type="text" name="username" id="username" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                        placeholder="Masukkan username">
                    <p id="usernameStatus" class="text-sm mt-1"></p>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                    <input type="email" name="email" id="email" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                        placeholder="Masukkan email">
                    <p id="emailStatus" class="text-sm mt-1"></p>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                    <input type="password" name="password" id="password" required minlength="8"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                        placeholder="Minimal 8 karakter">
                    <p id="passwordError" class="text-red-500 text-sm mt-1 hidden">Password minimal 8 karakter</p>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                        placeholder="Masukkan ulang password">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        Nomor Telepon (opsional)
                        <span class="text-gray-400 text-xs font-semibold">(untuk kontak admin bila diperlukan)</span>
                    </label>
                    <input type="tel" name="phone" id="phone" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                        placeholder="Contoh: 081234567890"
                        maxlength="13"
                        title="Masukkan nomor telepon aktif bila ingin dihubungi admin">
                    <div class="mt-1 text-xs">
                        <p class="text-gray-500">
                            📞 Opsional: isi jika ingin administrator menghubungi Anda via telepon.
                        </p>
                        <p class="text-gray-500 mt-1">
                            📱 Format: <code class="bg-gray-100 px-1 rounded">081234567890</code> atau <code class="bg-gray-100 px-1 rounded">6281234567890</code>
                        </p>
                    </div>
                    <p id="phoneStatus" class="text-sm mt-2 font-medium"></p>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Role</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="role" value="Finance Manager" required class="mr-2">
                            <span class="text-gray-700">Finance Manager (FM)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="role" value="Staff Accountant" required class="mr-2">
                            <span class="text-gray-700">Staff Accounting (SA)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="role" value="Project Manager" required class="mr-2">
                            <span class="text-gray-700">Project Manager (PM)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="role" value="Direktur" required class="mr-2">
                            <span class="text-gray-700">Direktur (Dir)</span>
                        </label>
                    </div>
                </div>

                <button type="submit" name="register" 
                    class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition duration-200 font-medium">
                    Buat Akun
                </button>
            </form>
        <?php endif; ?>  <!-- End success message check -->
        
        <?php endif; ?>  <!-- End registration enabled check -->
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        // Check username availability
        document.getElementById('username').addEventListener('blur', function() {
            const username = this.value;
            if (username) {
                fetch('register.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'check_username=1&username=' + encodeURIComponent(username)
                })
                .then(r => r.json())
                .then(data => {
                    const status = document.getElementById('usernameStatus');
                    if (data.available) {
                        status.textContent = '✅ Username available';
                        status.className = 'text-green-600 text-sm mt-1 font-medium';
                    } else {
                        status.textContent = '❌ Username already taken';
                        status.className = 'text-red-600 text-sm mt-1 font-medium';
                    }
                });
            }
        });

        // Check email availability
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            if (email) {
                fetch('register.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'check_email=1&email=' + encodeURIComponent(email)
                })
                .then(r => r.json())
                .then(data => {
                    const status = document.getElementById('emailStatus');
                    if (data.available) {
                        status.textContent = '✅ Email available';
                        status.className = 'text-green-600 text-sm mt-1 font-medium';
                    } else {
                        status.textContent = '❌ Email already registered';
                        status.className = 'text-red-600 text-sm mt-1 font-medium';
                    }
                });
            }
        });

        // Real-time phone number validation
        document.getElementById('phone').addEventListener('input', function() {
            const phone = this.value;
            const status = document.getElementById('phoneStatus');
            
            if (!phone) {
                status.textContent = '';
                return;
            }
            
            // Remove non-numeric characters for validation
            const phoneClean = phone.replace(/[^0-9]/g, '');
            
            // Check length
            if (phoneClean.length < 10) {
                status.innerHTML = '<span class="text-orange-600">⚠️ Minimal 10 digit</span>';
                return;
            }
            
            if (phoneClean.length > 13) {
                status.innerHTML = '<span class="text-red-600">❌ Maksimal 13 digit</span>';
                return;
            }
            
            // Check if starts with valid prefix
            if (!phoneClean.match(/^(0|62)/)) {
                status.innerHTML = '<span class="text-red-600">❌ Harus dimulai dengan 0 atau 62</span>';
                return;
            }
            
            // Check if mobile number (08xx or 628xx)
            if (phoneClean.startsWith('0')) {
                if (!phoneClean.startsWith('08')) {
                    status.innerHTML = '<span class="text-red-600">❌ Nomor HP harus 08xx</span>';
                    return;
                }
                if (phoneClean.length < 11 || phoneClean.length > 13) {
                    status.innerHTML = '<span class="text-orange-600">⚠️ Nomor 08xx harus 11-13 digit</span>';
                    return;
                }
            } else if (phoneClean.startsWith('62')) {
                if (!phoneClean.startsWith('628')) {
                    status.innerHTML = '<span class="text-red-600">❌ Nomor internasional harus 628xx</span>';
                    return;
                }
                if (phoneClean.length < 12 || phoneClean.length > 14) {
                    status.innerHTML = '<span class="text-orange-600">⚠️ Nomor 628xx harus 12-14 digit</span>';
                    return;
                }
            }
            
            // Validate operator prefix
            const operatorPrefix = phoneClean.startsWith('0') 
                ? phoneClean.substring(2, 4) 
                : phoneClean.substring(3, 5);
            
            const validOperators = ['11','12','13','14','15','16','17','18','19', // Telkomsel
                                   '21','22','23', // Indosat
                                   '31','32','33','38', // XL
                                   '55','56','57','58','59', // IM3
                                   '77','78', // XL
                                   '81','82','83','84','85','88', // Smartfren, Axis
                                   '95','96','97','98','99']; // Three
            
            if (!validOperators.includes(operatorPrefix)) {
                status.innerHTML = '<span class="text-red-600">❌ Operator tidak valid (gunakan Telkomsel, Indosat, XL, Three, Axis, Smartfren)</span>';
                return;
            }
            
            // If all validations pass, check availability
            fetch('register.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'check_phone=1&phone=' + encodeURIComponent(phone)
            })
            .then(r => r.json())
            .then(data => {
                if (data.available) {
                    status.innerHTML = '<span class="text-green-600">✅ Nomor valid & tersedia</span>';
                } else {
                    status.innerHTML = '<span class="text-red-600">❌ Nomor sudah terdaftar</span>';
                }
            });
        });

        // Password validation
        document.getElementById('password').addEventListener('input', function() {
            const error = document.getElementById('passwordError');
            if (this.value.length < 8 && this.value.length > 0) {
                error.classList.remove('hidden');
            } else {
                error.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
