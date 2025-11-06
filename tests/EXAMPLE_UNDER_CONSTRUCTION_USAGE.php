<?php
/**
 * CONTOH PENGGUNAAN UNDER_CONSTRUCTION.PHP
 * =========================================
 * 
 * File ini menunjukkan berbagai cara memanggil under_construction.php
 * dari halaman lain di aplikasi.
 */

// ============================================================================
// CONTOH 1: SIMPLE LINK
// ============================================================================
?>

<!-- Menu item yang belum ready -->
<a href="under_construction.php?feature=Laporan Donor">
    <i class="fas fa-file-invoice"></i>
    Laporan Donor
</a>

<?php
// ============================================================================
// CONTOH 2: BUTTON STYLED (Tailwind CSS)
// ============================================================================
?>

<!-- Card menu dengan under construction -->
<div class="grid grid-cols-3 gap-4">
    <!-- Fitur yang sudah ready -->
    <a href="create_proposal.php" class="card hover:bg-blue-50">
        <i class="fas fa-file-alt text-blue-500 text-3xl mb-2"></i>
        <h3>Buat Proposal</h3>
        <span class="badge bg-green-100 text-green-700">Ready</span>
    </a>
    
    <!-- Fitur belum ready - redirect ke under construction -->
    <a href="under_construction.php?feature=Export Excel" class="card hover:bg-gray-50">
        <i class="fas fa-file-excel text-gray-400 text-3xl mb-2"></i>
        <h3>Export Excel</h3>
        <span class="badge bg-yellow-100 text-yellow-700">Coming Soon</span>
    </a>
    
    <a href="under_construction.php?feature=Analisis Grafik" class="card hover:bg-gray-50">
        <i class="fas fa-chart-line text-gray-400 text-3xl mb-2"></i>
        <h3>Analisis Grafik</h3>
        <span class="badge bg-yellow-100 text-yellow-700">Coming Soon</span>
    </a>
</div>

<?php
// ============================================================================
// CONTOH 3: CONDITIONAL REDIRECT (di file PHP)
// ============================================================================

// File: export_excel.php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

// Feature flag - ganti jadi true kalau sudah ready
define('FEATURE_EXPORT_EXCEL_ENABLED', false);

if (!FEATURE_EXPORT_EXCEL_ENABLED) {
    // Fitur belum ready, redirect ke under construction
    header('Location: under_construction.php?feature=Export Excel');
    exit();
}

// Kalau sudah enable, lanjut ke kode export excel
echo "Export Excel feature...";

// ============================================================================
// CONTOH 4: FUNCTION HELPER
// ============================================================================

/**
 * Helper function untuk check feature availability
 * 
 * @param string $feature_name Nama fitur
 * @param string $target_url URL tujuan kalau fitur ready
 */
function check_feature_available($feature_name, $target_url = null) {
    // List fitur yang sudah ready
    $ready_features = [
        'Dashboard',
        'Create Proposal',
        'Review Proposal',
        'Approve Proposal',
        'Financial Report',
        'Profile',
    ];
    
    // Check if feature is ready
    if (!in_array($feature_name, $ready_features)) {
        // Redirect ke under construction
        $encoded_name = urlencode($feature_name);
        header("Location: under_construction.php?feature={$encoded_name}");
        exit();
    }
    
    // Kalau ready dan ada target URL, redirect ke sana
    if ($target_url) {
        header("Location: {$target_url}");
        exit();
    }
    
    return true; // Feature available
}

// Usage:
// check_feature_available('Export PDF', 'export_pdf.php');

// ============================================================================
// CONTOH 5: DALAM SWITCH CASE (Route Handler)
// ============================================================================

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'create_proposal':
            // Feature ready
            header('Location: create_proposal.php');
            break;
            
        case 'export_excel':
            // Feature not ready
            header('Location: under_construction.php?feature=Export Excel');
            break;
            
        case 'analytics':
            // Feature not ready
            header('Location: under_construction.php?feature=Dashboard Analytics');
            break;
            
        default:
            header('Location: dashboard_pm.php');
    }
    exit();
}

// ============================================================================
// CONTOH 6: DROPDOWN MENU
// ============================================================================
?>

<!-- Dropdown dengan fitur ready & under construction -->
<div class="dropdown">
    <button class="dropdown-toggle">Laporan <i class="fas fa-chevron-down"></i></button>
    <div class="dropdown-menu">
        <!-- Ready -->
        <a href="create_financial_report.php" class="dropdown-item">
            <i class="fas fa-file-alt text-green-500"></i>
            Buat Laporan Keuangan
        </a>
        
        <!-- Under Construction -->
        <a href="under_construction.php?feature=Laporan Donor" class="dropdown-item text-gray-500">
            <i class="fas fa-file-invoice text-gray-400"></i>
            Laporan Donor
            <span class="badge">Soon</span>
        </a>
        
        <a href="under_construction.php?feature=Export PDF" class="dropdown-item text-gray-500">
            <i class="fas fa-file-pdf text-gray-400"></i>
            Export PDF
            <span class="badge">Soon</span>
        </a>
    </div>
</div>

<?php
// ============================================================================
// CONTOH 7: TABLE ACTION BUTTONS
// ============================================================================
?>

<!-- Table dengan action buttons -->
<table>
    <tr>
        <td>Proposal #123</td>
        <td>
            <!-- View (ready) -->
            <a href="review_proposal.php?id=123" class="btn btn-sm btn-primary">
                <i class="fas fa-eye"></i> View
            </a>
            
            <!-- Export PDF (not ready) -->
            <a href="under_construction.php?feature=Export PDF" class="btn btn-sm btn-secondary">
                <i class="fas fa-download"></i> PDF
            </a>
            
            <!-- Send Email (not ready) -->
            <a href="under_construction.php?feature=Email Notification" class="btn btn-sm btn-secondary">
                <i class="fas fa-envelope"></i> Email
            </a>
        </td>
    </tr>
</table>

<?php
// ============================================================================
// CONTOH 8: ARRAY CONFIG (Best Practice)
// ============================================================================

// File: config/features.php
$features = [
    'dashboard' => [
        'name' => 'Dashboard',
        'url' => 'dashboard_pm.php',
        'enabled' => true,
        'icon' => 'fas fa-home'
    ],
    'create_proposal' => [
        'name' => 'Buat Proposal',
        'url' => 'create_proposal.php',
        'enabled' => true,
        'icon' => 'fas fa-file-alt'
    ],
    'export_excel' => [
        'name' => 'Export Excel',
        'url' => 'export_excel.php',
        'enabled' => false, // ← Not ready
        'icon' => 'fas fa-file-excel'
    ],
    'analytics' => [
        'name' => 'Analytics Dashboard',
        'url' => 'analytics.php',
        'enabled' => false, // ← Not ready
        'icon' => 'fas fa-chart-bar'
    ],
];

// Helper function
function get_feature_url($feature_key) {
    global $features;
    
    if (!isset($features[$feature_key])) {
        return 'dashboard.php'; // Default
    }
    
    $feature = $features[$feature_key];
    
    if ($feature['enabled']) {
        return $feature['url'];
    } else {
        return 'under_construction.php?feature=' . urlencode($feature['name']);
    }
}

// Usage in menu:
?>
<nav>
    <a href="<?php echo get_feature_url('dashboard'); ?>">
        <i class="<?php echo $features['dashboard']['icon']; ?>"></i>
        <?php echo $features['dashboard']['name']; ?>
    </a>
    
    <a href="<?php echo get_feature_url('export_excel'); ?>">
        <i class="<?php echo $features['export_excel']['icon']; ?>"></i>
        <?php echo $features['export_excel']['name']; ?>
        <?php if (!$features['export_excel']['enabled']): ?>
            <span class="badge">Soon</span>
        <?php endif; ?>
    </a>
</nav>

<?php
// ============================================================================
// CONTOH 9: JAVASCRIPT DYNAMIC
// ============================================================================
?>
<script>
// Feature availability check
const features = {
    'dashboard': true,
    'create_proposal': true,
    'export_excel': false,
    'export_pdf': false,
    'analytics': false
};

function openFeature(featureName) {
    const featureKey = featureName.toLowerCase().replace(/\s+/g, '_');
    
    if (features[featureKey]) {
        // Feature ready - go to actual page
        window.location.href = featureKey + '.php';
    } else {
        // Feature not ready - go to under construction
        window.location.href = 'under_construction.php?feature=' + encodeURIComponent(featureName);
    }
}

// Usage:
// <button onclick="openFeature('Export Excel')">Export Excel</button>
</script>

<?php
// ============================================================================
// CONTOH 10: REAL WORLD - DASHBOARD MENU
// ============================================================================
?>

<!-- Dashboard PM - Menu Section -->
<div class="dashboard-menu">
    <div class="menu-section">
        <h3>Proposal Management</h3>
        
        <!-- Ready Feature -->
        <a href="create_proposal.php" class="menu-item">
            <div class="icon-box bg-blue-100">
                <i class="fas fa-plus text-blue-600"></i>
            </div>
            <div class="menu-content">
                <h4>Buat Proposal Baru</h4>
                <p>Ajukan proposal proyek baru</p>
            </div>
            <span class="badge badge-success">Active</span>
        </a>
        
        <!-- Under Construction Feature -->
        <a href="under_construction.php?feature=Template Proposal" class="menu-item menu-item-disabled">
            <div class="icon-box bg-gray-100">
                <i class="fas fa-file-alt text-gray-400"></i>
            </div>
            <div class="menu-content">
                <h4>Template Proposal</h4>
                <p>Gunakan template siap pakai</p>
            </div>
            <span class="badge badge-warning">Coming Soon</span>
        </a>
    </div>
    
    <div class="menu-section">
        <h3>Laporan & Export</h3>
        
        <!-- Under Construction Features -->
        <a href="under_construction.php?feature=Export Excel" class="menu-item menu-item-disabled">
            <div class="icon-box bg-gray-100">
                <i class="fas fa-file-excel text-gray-400"></i>
            </div>
            <div class="menu-content">
                <h4>Export ke Excel</h4>
                <p>Download laporan format Excel</p>
            </div>
            <span class="badge badge-warning">Soon</span>
        </a>
        
        <a href="under_construction.php?feature=Export PDF" class="menu-item menu-item-disabled">
            <div class="icon-box bg-gray-100">
                <i class="fas fa-file-pdf text-gray-400"></i>
            </div>
            <div class="menu-content">
                <h4>Export ke PDF</h4>
                <p>Download laporan format PDF</p>
            </div>
            <span class="badge badge-warning">Soon</span>
        </a>
    </div>
</div>

<?php
// ============================================================================
// SUMMARY
// ============================================================================
/**
 * CARA MUDAH PAKAI under_construction.php:
 * 
 * 1. SIMPLE LINK:
 *    <a href="under_construction.php?feature=Nama Fitur">Link</a>
 * 
 * 2. PHP REDIRECT:
 *    header('Location: under_construction.php?feature=Nama Fitur');
 * 
 * 3. CONDITIONAL:
 *    if (!$feature_ready) {
 *        header('Location: under_construction.php?feature=...');
 *    }
 * 
 * 4. JAVASCRIPT:
 *    window.location.href = 'under_construction.php?feature=...';
 * 
 * PARAMETER:
 * - ?feature=Nama Fitur  ← Nama yang akan ditampilkan
 * - URL encode otomatis handle spasi & special chars
 * 
 * RETURN DASHBOARD:
 * - under_construction.php auto detect user role
 * - Button "Kembali" otomatis ke dashboard yang sesuai
 */
?>

