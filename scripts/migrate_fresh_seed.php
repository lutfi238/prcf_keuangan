<?php
/**
 * Migrate Fresh & Seed for PRCF Keuangan
 * Menghapus semua tabel, membuat ulang dari skema, dan mengisi data awal.
 * Mirip dengan 'php artisan migrate:fresh --seed' di Laravel.
 */

require_once __DIR__ . '/../includes/config.php';

// Pastikan dijalankan via CLI atau dengan parameter khusus untuk keamanan
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die("Akses ditolak. Jalankan via CLI atau tambahkan ?run=1 di URL.");
}

echo (php_sapi_name() === 'cli' ? "" : "<pre>");
echo "🔥 Memulai Migrate Fresh & Seed...\n\n";

// 1. Drop semua tabel yang ada
echo "🗑️  Menghapus semua tabel...\n";
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    if ($conn->query("DROP TABLE IF EXISTS `$table`")) {
        echo "   - Dropped: $table\n";
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");
echo "✅ Semua tabel berhasil dihapus.\n\n";

// 2. Import skema database
$sql_file = __DIR__ . '/../database/prcf_keuangan.sql';
if (file_exists($sql_file)) {
    echo "🏗️  Mengimport skema database dari prcf_keuangan.sql...\n";
    
    $commands = file_get_contents($sql_file);
    
    // PHP mysqli multi_query can handle multiple statements
    if ($conn->multi_query($commands)) {
        do {
            // Flush all results
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "✅ Skema database berhasil diimport.\n\n";
    } else {
        die("❌ Gagal mengimport skema: " . $conn->error);
    }
} else {
    die("❌ File skema tidak ditemukan di: $sql_file");
}

// 3. Re-establish connection because multi_query might have closed it or left it in a weird state
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("❌ Reconnection failed: " . $conn->connect_error);
}

// 4. Jalankan Seeder
echo "📦 Menjalankan Seeder...\n";

// Kita gunakan logika yang sama dengan master_seeder.php
// Untuk efisiensi, kita definisikan ulang di sini atau include (tapi master_seeder punya die() cek)
// Agar bersih, kita jalankan master_seeder.php via include setelah bypass cek CLI (karena kita di CLI) atau panggil fungsinya.

// Cara paling aman: Definisikan ulang data seed agar tidak terjadi duplikasi output/masalah include
$password_hash = password_hash('password123', PASSWORD_DEFAULT);

// Villages
$villages = [
    ['V001', 'Nanga Jemah', 'NJ', 'Desa Nanga Jemah'],
    ['V002', 'Sri Wangi', 'SW', 'Desa Sri Wangi'],
    ['V003', 'Penepian Raya', 'PR', 'Desa Penepian Raya'],
    ['V004', 'Tanjung Jaya', 'TJ', 'Desa Tanjung Jaya'],
    ['V005', 'Riam Jaya', 'RJ', 'Desa Riam Jaya']
];
$stmt = $conn->prepare("INSERT INTO villages (village_code, village_name, village_abbr, description) VALUES (?, ?, ?, ?)");
foreach ($villages as $v) {
    $stmt->bind_param("ssss", $v[0], $v[1], $v[2], $v[3]);
    $stmt->execute();
}
echo "   - Villages seeded.\n";

// Projects
$projects = [
    ['PRJ-2026-001', 'Konservasi Hutan PRCF 2026', 'ongoing', 'Donor Utama', 500000000.00, '2026-01-01', '2026-12-31'],
    ['PRJ-2026-002', 'Pemberdayaan Ekonomi Desa', 'planning', 'Donor B', 250000000.00, '2026-02-01', '2026-11-30']
];
$stmt = $conn->prepare("INSERT INTO proyek (kode_proyek, nama_proyek, status_proyek, donor, nilai_anggaran, periode_mulai, periode_selesai) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($projects as $p) {
    $stmt->bind_param("ssssdss", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6]);
    $stmt->execute();
}
echo "   - Projects seeded.\n";

// Users
$users = [
    ['Administrator', 'Admin', 'admin@prcf.org', '081234567890'],
    ['Project Manager One', 'Project Manager', 'pm@prcf.org', '081234567891'],
    ['Finance Manager One', 'Finance Manager', 'fm@prcf.org', '081234567892'],
    ['Staff Accountant One', 'Staff Accountant', 'sa@prcf.org', '081234567893'],
    ['Direktur Utama', 'Direktur', 'direktur@prcf.org', '081234567894']
];
$stmt = $conn->prepare("INSERT INTO user (nama, role, email, no_HP, password_hash) VALUES (?, ?, ?, ?, ?)");
foreach ($users as $u) {
    $stmt->bind_param("sssss", $u[0], $u[1], $u[2], $u[3], $password_hash);
    $stmt->execute();
}
echo "   - Users seeded (Password: password123).\n";

echo "\n✨ Database berhasil di-reset dan di-seed!\n";
echo (php_sapi_name() === 'cli' ? "" : "</pre>");
?>
