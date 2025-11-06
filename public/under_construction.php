<?php
session_start();
require_once '../includes/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_role = $_SESSION['user_role'] ?? 'Guest';

// Determine return dashboard based on role
$return_dashboard = '../auth/login.php';
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
    <title>Under Construction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff !important;
        }
        #lottie-animation {
            max-width: 480px;
            margin: 3rem auto 2rem auto;
            width: 100%;
            max-height: 400px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-2 py-4" style="background:#fff;">
    <div id="lottie-animation"></div>
    <a href="<?php echo $return_dashboard; ?>"
       class="inline-flex items-center justify-center mt-8 px-5 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-sm font-medium">
        <i class="fas fa-arrow-left mr-2"></i>
        Kembali ke Dashboard
    </a>
    <script>
        // Load Lottie animation
        try {
            lottie.loadAnimation({
                container: document.getElementById('lottie-animation'),
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: '../assets/Under Construction 1.json'
            });
        } catch (e) {
            document.getElementById('lottie-animation').style.display = 'none';
        }
    </script>
</body>
</html>
