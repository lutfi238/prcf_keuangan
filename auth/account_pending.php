<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();

// If user is not logged in, redirect to login
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

// If user account is not pending, redirect to appropriate dashboard
if ($_SESSION['user_status'] !== 'pending') {
    switch ($_SESSION['user_role']) {
        case 'Admin':
            header('Location: ../pages/dashboards/dashboard_admin.php');
            break;
        case 'Project Manager':
            header('Location: ../pages/dashboards/dashboard_pm.php');
            break;
        case 'Staff Accountant':
            header('Location: ../pages/dashboards/dashboard_sa.php');
            break;
        case 'Finance Manager':
            header('Location: ../pages/dashboards/dashboard_fm.php');
            break;
        case 'Direktur':
            header('Location: ../pages/dashboards/dashboard_dir.php');
            break;
        default:
            header('Location: login.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Menunggu Verifikasi - PRCF INDONESIA Financial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md text-center">
        <div class="mb-6">
            <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-clock text-yellow-600 text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Akun Menunggu Verifikasi</h1>
            <p class="text-gray-600">Halo, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></p>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-yellow-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Akun Anda telah berhasil dibuat dan sedang menunggu verifikasi dari administrator.
                        Anda akan menerima notifikasi email ketika akun Anda telah diaktifkan.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-envelope text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>Apa yang terjadi selanjutnya?</strong><br>
                        • Administrator akan meninjau data akun Anda<br>
                        • Anda akan menerima email konfirmasi<br>
                        • Akses penuh akan diberikan setelah verifikasi
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <a href="login.php?logout=1" class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition duration-200 font-medium inline-block text-center">
                <i class="fas fa-sign-out-alt mr-2"></i>Keluar & Login Lagi
            </a>

            <div class="text-sm text-gray-500">
                <p>Untuk informasi lebih lanjut, hubungi administrator sistem.</p>
                <p class="mt-2">Email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
