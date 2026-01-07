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
    
    // Disable foreign key checks during import
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
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

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// 4. All data is already in prcf_keuangan.sql
// Villages, Projects, and Users are included in the SQL dump
echo "📦 Data sudah di-import dari SQL file.\n";
echo "   - Villages: dari SQL\n";
echo "   - Projects: dari SQL\n";
echo "   - Users: dari SQL (Password: password123)\n";

echo "\n✨ Database berhasil di-reset!\n";
echo (php_sapi_name() === 'cli' ? "" : "</pre>");
?>
