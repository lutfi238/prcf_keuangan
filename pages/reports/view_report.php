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

// Redirect to role-specific view report pages
$user_role = $_SESSION['user_role'];
$report_id = $_GET['id'] ?? 0;

switch ($user_role) {
    case 'Project Manager':
        header('Location: view_report_pm.php?id=' . $report_id);
        exit();
    case 'Finance Manager':
        header('Location: view_report_fm.php?id=' . $report_id);
        exit();
    case 'Staff Accountant':
        header('Location: view_report_sa.php?id=' . $report_id);
        exit();
    case 'Direktur':
        header('Location: view_report_dir.php?id=' . $report_id);
        exit();
    default:
        header('Location: ../../auth/unauthorized.php');
        exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Laporan Keuangan - PRCF INDONESIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8 border border-gray-200 text-center">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Redirecting...</h1>
            <p class="text-gray-600 mb-4">Redirecting to your role-specific report view...</p>
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
        </div>
    </div>
</body>
</html>
