<?php
/**
 * Truncate All Tables Except 'user'
 * Script ini akan menghapus seluruh data transaksi, proposal, laporan, dan budget,
 * namun tetap mempertahankan data akun pengguna (tabel user).
 */

require_once __DIR__ . '/../includes/config.php';

// Pastikan dijalankan via CLI atau dengan parameter khusus untuk keamanan
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die("Akses ditolak. Jalankan via CLI atau tambahkan ?run=1 di URL.");
}

$isCli = (php_sapi_name() === 'cli');
echo $isCli ? "" : "<pre>";
echo "🧹 Memulai proses pembersihan data (Truncate)...\n";
echo "=============================================\n";

// 1. Matikan pengecekan Foreign Key
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// 2. Ambil daftar seluruh tabel
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_array()) {
    $tableName = $row[0];
    // Kecualikan tabel 'user'
    if ($tableName !== 'user') {
        $tables[] = $tableName;
    }
}

// 3. Eksekusi Truncate dengan Laporan
$totalDeleted = 0;
foreach ($tables as $table) {
    // Hitung data sebelum
    $countRes = $conn->query("SELECT COUNT(*) FROM `$table`");
    $countBefore = $countRes ? $countRes->fetch_row()[0] : '?';

    if ($conn->query("TRUNCATE TABLE `$table`")) {
        echo sprintf("   %-30s : %d baris -> 0 baris [OK]\n", $table, $countBefore);
    } else {
        echo sprintf("   %-30s : [ERROR] %s\n", $table, $conn->error);
    }
}

// 4. Hidupkan kembali pengecekan Foreign Key
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "\n=============================================\n";
echo "✅ Proses selesai. Seluruh data transaksi clean.\n";
echo "   KECUALI tabel 'user' yang tetap aman.\n";
echo $isCli ? "" : "</pre>";
?>