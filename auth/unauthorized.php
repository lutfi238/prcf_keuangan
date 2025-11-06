<?php
session_start();

// Get user info if logged in
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$user_role = $is_logged_in ? $_SESSION['user_role'] : 'Guest';
$user_name = $is_logged_in ? $_SESSION['user_name'] : 'Pengunjung';

// Determine return dashboard based on role
$return_dashboard = 'login.php';
switch ($user_role) {
    case 'Project Manager':
        $return_dashboard = '../pages/dashboards/dashboard_pm.php';
        break;
    case 'Staff Accountant':
        $return_dashboard = '../pages/dashboards/dashboard_sa.php';
        break;
    case 'Finance Manager':
        $return_dashboard = '../pages/dashboards/dashboard_fm.php';
        break;
    case 'Direktur':
        $return_dashboard = '../pages/dashboards/dashboard_dir.php';
        break;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - PRCF INDONESIA Financial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .fade-in {
            animation: fadeIn 1s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-2 py-4">
    <div class="max-w-3xl w-full">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden fade-in">
            <!-- Header (Compact) -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 py-4 px-6 text-center">
                <div class="flex items-center justify-center mb-2">
                    <i class="fas fa-shield-alt text-white text-4xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-1">🚫 Akses Ditolak</h1>
                <p class="text-red-100 text-sm">Unauthorized Access</p>
            </div>

            <!-- Content (Compact) -->
            <div class="px-6 pb-6 pt-8 text-center">
                <!-- User Role Badge -->
                                
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    Anda Tidak Memiliki Akses Untuk halaman ini
                </h2>


                <!-- Status Badge -->
                <div class="flex flex-wrap justify-center gap-2 mb-6">
                    <span class="px-3 py-1.5 bg-red-100 text-red-800 rounded-full text-xs font-semibold flex items-center">
                        <span class="w-2 h-2 bg-red-500 rounded-full mr-2 pulse"></span>
                        Access Denied
                    </span>
                </div>

                <!-- Action Buttons (Compact) -->
                <div class="flex flex-col sm:flex-row gap-2 justify-center mt-4">
                    <a href="<?php echo $return_dashboard; ?>" 
                        class="inline-flex items-center justify-center px-5 py-2.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-sm font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali ke Dashboard
                    </a>
                </div>

                <!-- Footer (Compact) -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <?php if ($is_logged_in): ?>
                    <p class="text-gray-500 text-xs">
                        Login sebagai: <span class="font-medium text-gray-700"><?php echo $user_name; ?></span>
                    </p>
                    <?php endif; ?>
                    <p class="text-gray-400 text-xs mt-1">
                        © <?php echo date('Y'); ?> PRCF INDONESIA Financial
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
