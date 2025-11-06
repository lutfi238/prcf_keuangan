<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Ditutup - PRCF INDONESIA Financial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md text-center">
        <div class="mb-6">
            <div class="mx-auto w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-user-slash text-orange-600 text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Registrasi Ditutup</h1>
            <p class="text-gray-600">Pendaftaran akun baru saat ini tidak tersedia</p>
        </div>

        <div class="bg-orange-50 border-l-4 border-orange-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-orange-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-orange-700">
                        Sistem registrasi publik sedang dinonaktifkan. Hanya administrator yang dapat membuat akun baru.
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <a href="login.php" class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition duration-200 font-medium inline-block">
                <i class="fas fa-sign-in-alt mr-2"></i>Kembali ke Login
            </a>

            <div class="text-sm text-gray-500">
                <p>Untuk informasi lebih lanjut, hubungi administrator sistem.</p>
            </div>
        </div>
    </div>
</body>
</html>
