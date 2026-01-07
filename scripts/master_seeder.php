<?php
/**
 * Master Seeder for PRCF Keuangan
 * Mengisi data awal untuk testing: User, Proyek, dan Desa.
 */

require_once __DIR__ . '/../includes/config.php';

// Pastikan dijalankan via CLI atau dengan parameter khusus untuk keamanan
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die("Akses ditolak. Jalankan via CLI atau tambahkan ?run=1 di URL.");
}

echo "<pre>";
echo "🚀 Memulai Master Seeding...\n\n";

// 1. Seed Desa (Villages)
$villages = [
    ['V001', 'Nanga Jemah', 'NJ', 'Desa Nanga Jemah'],
    ['V002', 'Sri Wangi', 'SW', 'Desa Sri Wangi'],
    ['V003', 'Penepian Raya', 'PR', 'Desa Penepian Raya'],
    ['V004', 'Tanjung Jaya', 'TJ', 'Desa Tanjung Jaya'],
    ['V005', 'Riam Jaya', 'RJ', 'Desa Riam Jaya']
];

echo "📦 Seeding Villages...\n";
$stmt = $conn->prepare("INSERT IGNORE INTO villages (village_code, village_name, village_abbr, description) VALUES (?, ?, ?, ?)");
foreach ($villages as $v) {
    $stmt->bind_param("ssss", $v[0], $v[1], $v[2], $v[3]);
    $stmt->execute();
    echo "   - " . ($stmt->affected_rows > 0 ? "Inserted" : "Skipped") . ": {$v[1]}\n";
}

// 2. Seed Proyek (Projects)
$projects = [
    ['PRJ-2026-001', 'Konservasi Hutan PRCF 2026', 'ongoing', 'Donor Utama', 500000000.00, '2026-01-01', '2026-12-31'],
    ['PRJ-2026-002', 'Pemberdayaan Ekonomi Desa', 'planning', 'Donor B', 250000000.00, '2026-02-01', '2026-11-30']
];

echo "\n🏗️ Seeding Projects...\n";
$stmt = $conn->prepare("INSERT IGNORE INTO proyek (kode_proyek, nama_proyek, status_proyek, donor, nilai_anggaran, periode_mulai, periode_selesai) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($projects as $p) {
    $stmt->bind_param("ssssdss", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6]);
    $stmt->execute();
    echo "   - " . ($stmt->affected_rows > 0 ? "Inserted" : "Skipped") . ": {$p[1]}\n";
}

// 3. Seed Users (Semua Role)
// Password default: password123
$password_hash = password_hash('password123', PASSWORD_DEFAULT);
$users = [
    ['Administrator', 'Admin', 'admin@prcf.org', '081234567890'],
    ['Project Manager One', 'Project Manager', 'pm@prcf.org', '081234567891'],
    ['Finance Manager One', 'Finance Manager', 'fm@prcf.org', '081234567892'],
    ['Staff Accountant One', 'Staff Accountant', 'sa@prcf.org', '081234567893'],
    ['Direktur Utama', 'Direktur', 'direktur@prcf.org', '081234567894']
];

echo "\n👥 Seeding Users (Password: password123)...\n";
$stmt = $conn->prepare("INSERT IGNORE INTO user (nama, role, email, no_HP, password_hash) VALUES (?, ?, ?, ?, ?)");
foreach ($users as $u) {
    $stmt->bind_param("sssss", $u[0], $u[1], $u[2], $u[3], $password_hash);
    $stmt->execute();
    echo "   - " . ($stmt->affected_rows > 0 ? "Inserted" : "Skipped") . ": {$u[0]} ({$u[1]})\n";
}

echo "\n✅ Master Seeding Selesai.\n";
echo "</pre>";
?>