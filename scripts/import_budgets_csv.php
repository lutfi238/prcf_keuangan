<?php
// scripts/import_budgets_csv.php
// Usage: php scripts/import_budgets_csv.php data/budget_template.csv

require_once __DIR__ . '/../includes/config.php';

if ($argc < 2) {
    die("Usage: php scripts/import_budgets_csv.php <csv_file>\n");
}

$csv_file = $argv[1];

if (!file_exists($csv_file)) {
    die("Error: File not found: $csv_file\n");
}

echo "Starting Import from $csv_file...\n";

$handle = fopen($csv_file, "r");
if ($handle === FALSE) {
    die("Error: Cannot open file.\n");
}

// Read Header
$header = fgetcsv($handle);
// Expected Header: kode_proyek, village_name, exp_code, currency, amount, exrate

$row_count = 0;
$success_count = 0;
$fail_count = 0;

$conn->begin_transaction();

try {
    while (($data = fgetcsv($handle)) !== FALSE) {
        $row_count++;
        
        // Map columns (Adjust indices based on your CSV template)
        $kode_proyek = trim($data[0]);
        $village_name = trim($data[1]);
        $exp_code = trim($data[2]);
        $currency = strtoupper(trim($data[3]));
        $amount = floatval($data[4]);
        $exrate = floatval($data[5]);
        
        if (empty($kode_proyek) || empty($village_name) || empty($exp_code)) {
            echo "Row $row_count: Skipped (Missing required fields)\n";
            $fail_count++;
            continue;
        }
        
        // 1. Find Village ID and Abbr
        // Try exact match first
        $stmt = $conn->prepare("SELECT id_village, village_abbr FROM villages WHERE village_name = ? OR village_code = ?");
        $stmt->bind_param("ss", $village_name, $village_name);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows === 0) {
            echo "Row $row_count: Failed - Village '$village_name' not found.\n";
            $fail_count++;
            continue;
        }
        
        $v_row = $res->fetch_assoc();
        $id_village = $v_row['id_village'];
        $village_abbr = $v_row['village_abbr'];
        
        // 2. Generate Place Code
        $place_code = $exp_code . '-' . $village_abbr . '-01';
        
        // 3. Calculate Amounts
        if ($currency === 'USD') {
            $budget_usd = $amount;
            $budget_idr = $amount * $exrate;
        } else {
            $budget_idr = $amount;
            $budget_usd = ($exrate > 0) ? ($amount / $exrate) : 0;
        }
        
        // 4. Insert/Update
        $stmt = $conn->prepare("INSERT INTO project_code_budgets 
            (kode_proyek, id_village, exp_code, place_code, budget_usd, budget_idr, exrate, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            budget_usd = VALUES(budget_usd), 
            budget_idr = VALUES(budget_idr), 
            exrate = VALUES(exrate)");
            
        $stmt->bind_param("sisssdd", $kode_proyek, $id_village, $exp_code, $place_code, $budget_usd, $budget_idr, $exrate);
        
        if ($stmt->execute()) {
            echo "Row $row_count: Success - $place_code\n";
            $success_count++;
        } else {
            echo "Row $row_count: DB Error - " . $stmt->error . "\n";
            $fail_count++;
        }
    }
    
    $conn->commit();
    echo "\nImport Complete.\n";
    echo "Total Processed: $row_count\n";
    echo "Success: $success_count\n";
    echo "Failed: $fail_count\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}

fclose($handle);
?>
