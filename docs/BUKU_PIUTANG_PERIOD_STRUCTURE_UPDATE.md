# Buku Piutang Period Structure Update

**Date**: 2025-10-22  
**Status**: ✅ Completed  
**Module**: Buku Piutang (Accounts Receivable Book)  
**Developer**: AI Assistant  

---

## 📋 Overview

This document details the modification of the `buku_piutang_header` table structure to standardize the period representation across financial modules. The period fields were changed from date ranges (`periode_mulai` and `periode_selesai`) to separate month and year fields (`periode_bulan` and `periode_tahun`) to match the structure used in the `buku_bank_header` table.

---

## 🎯 Objectives

1. **Standardize Period Representation**: Align period structure between `buku_piutang_header` and `buku_bank_header` tables
2. **Simplify Period Selection**: Enable month/year dropdowns instead of date range pickers
3. **Improve Data Consistency**: Use uniform period representation across all financial modules
4. **Enhance Reporting**: Facilitate easier period-based grouping and reporting

---

## 🗄️ Database Changes

### Table Modified: `buku_piutang_header`

#### Fields Removed:
- `periode_mulai` (DATE) - Period start date
- `periode_selesai` (DATE) - Period end date

#### Fields Added:
- `periode_bulan` (CHAR(2) NOT NULL) - Period month (01-12)
- `periode_tahun` (CHAR(4) NOT NULL) - Period year (4-digit year)

### SQL Migration Command

```sql
ALTER TABLE buku_piutang_header 
DROP COLUMN periode_mulai, 
DROP COLUMN periode_selesai, 
ADD COLUMN periode_bulan CHAR(2) NOT NULL AFTER kode_proyek, 
ADD COLUMN periode_tahun CHAR(4) NOT NULL AFTER periode_bulan;
```

#### Execution Method:
Executed via Windows Command Prompt:
```cmd
cd C:\xampp\mysql\bin
mysql -u root -p prcf_keuangan -e "ALTER TABLE buku_piutang_header DROP COLUMN periode_mulai, DROP COLUMN periode_selesai, ADD COLUMN periode_bulan CHAR(2) NOT NULL AFTER kode_proyek, ADD COLUMN periode_tahun CHAR(4) NOT NULL AFTER periode_bulan;"
```

### Updated Table Structure

```sql
CREATE TABLE `buku_piutang_header` (
  `id_piutang` int(11) NOT NULL,
  `kode_proyek` varchar(50) DEFAULT NULL,
  `periode_bulan` char(2) NOT NULL,
  `periode_tahun` char(4) NOT NULL,
  `beginning_balance_idr` decimal(15,2) DEFAULT 0.00,
  `ending_balance_idr` decimal(15,2) DEFAULT 0.00,
  `beginning_balance_usd` decimal(15,2) DEFAULT 0.00,
  `ending_balance_usd` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `catatan_fm` text DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `tgl_pembuatan` date DEFAULT NULL,
  `tgl_persetujuan` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## 💻 Code Changes

### 1. File: `pages/books/buku_piutang.php`

#### A. Create Header Form - Updated Period Fields

**Before (Date Range):**
```php
<div>
    <label>Periode Mulai *</label>
    <input type="date" name="periode_mulai" required>
</div>

<div>
    <label>Periode Selesai *</label>
    <input type="date" name="periode_selesai" required>
</div>
```

**After (Month/Year):**
```php
<div>
    <label>Bulan Periode *</label>
    <select name="periode_bulan" required>
        <option value="">Pilih Bulan</option>
        <option value="01">Januari</option>
        <option value="02">Februari</option>
        <option value="03">Maret</option>
        <option value="04">April</option>
        <option value="05">Mei</option>
        <option value="06">Juni</option>
        <option value="07">Juli</option>
        <option value="08">Agustus</option>
        <option value="09">September</option>
        <option value="10">Oktober</option>
        <option value="11">November</option>
        <option value="12">Desember</option>
    </select>
</div>

<div>
    <label>Tahun Periode *</label>
    <input type="text" name="periode_tahun" required maxlength="4" pattern="[0-9]{4}" 
        placeholder="<?php echo date('Y'); ?>">
</div>
```

#### B. Backend Handler - Updated INSERT Statement

**Before:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_header'])) {
    $kode_proyek = $_POST['kode_proyek'];
    $periode_mulai = $_POST['periode_mulai'];
    $periode_selesai = $_POST['periode_selesai'];
    $beginning_balance_idr = $_POST['beginning_balance_idr'] ?? 0;
    $beginning_balance_usd = $_POST['beginning_balance_usd'] ?? 0;
    
    $stmt = $conn->prepare("INSERT INTO buku_piutang_header (kode_proyek, periode_mulai, periode_selesai, beginning_balance_idr, beginning_balance_usd, ending_balance_idr, ending_balance_usd, created_by, status, tgl_pembuatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', CURDATE())");
    $stmt->bind_param("sssdddddi", $kode_proyek, $periode_mulai, $periode_selesai, $beginning_balance_idr, $beginning_balance_usd, $beginning_balance_idr, $beginning_balance_usd, $user_id);
    
    // ...
}
```

**After:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_header'])) {
    $kode_proyek = $_POST['kode_proyek'];
    $periode_bulan = $_POST['periode_bulan'];
    $periode_tahun = $_POST['periode_tahun'];
    $beginning_balance_idr = $_POST['beginning_balance_idr'] ?? 0;
    $beginning_balance_usd = $_POST['beginning_balance_usd'] ?? 0;
    
    $stmt = $conn->prepare("INSERT INTO buku_piutang_header (kode_proyek, periode_bulan, periode_tahun, beginning_balance_idr, beginning_balance_usd, ending_balance_idr, ending_balance_usd, created_by, status, tgl_pembuatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', CURDATE())");
    $stmt->bind_param("sssddddi", $kode_proyek, $periode_bulan, $periode_tahun, $beginning_balance_idr, $beginning_balance_usd, $beginning_balance_idr, $beginning_balance_usd, $user_id);
    
    // ...
}
```

#### C. Hierarchical Query - Updated Year Extraction

**Before:**
```php
$query = "SELECT 
    ph.*,
    p.nama_proyek,
    u.nama as creator_name,
    YEAR(ph.periode_mulai) as tahun,
    (SELECT COUNT(*) FROM buku_piutang_detail pd WHERE pd.id_piutang = ph.id_piutang) as total_transactions
FROM buku_piutang_header ph
LEFT JOIN proyek p ON ph.kode_proyek = p.kode_proyek
LEFT JOIN user u ON ph.created_by = u.id_user
ORDER BY ph.kode_proyek, ph.periode_mulai DESC";
```

**After:**
```php
$query = "SELECT 
    ph.*,
    p.nama_proyek,
    u.nama as creator_name,
    ph.periode_tahun as tahun,
    (SELECT COUNT(*) FROM buku_piutang_detail pd WHERE pd.id_piutang = ph.id_piutang) as total_transactions
FROM buku_piutang_header ph
LEFT JOIN proyek p ON ph.kode_proyek = p.kode_proyek
LEFT JOIN user u ON ph.created_by = u.id_user
ORDER BY ph.kode_proyek, ph.periode_tahun DESC, ph.periode_bulan DESC";
```

#### D. Display Logic - Period Formatting

**Before (Date Range Display):**
```php
<span class="bg-cyan-700 text-white text-xs px-3 py-1 rounded-full">
    <?php echo date('d/m/Y', strtotime($header['periode_mulai'])); ?> - <?php echo date('d/m/Y', strtotime($header['periode_selesai'])); ?>
</span>
```

**After (Month Year Display):**
```php
<span class="bg-cyan-700 text-white text-xs px-3 py-1 rounded-full">
    <?php 
    $month_names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $bulan_display = $month_names[(int)$header['periode_bulan']];
    echo $bulan_display . ' ' . $header['periode_tahun']; 
    ?>
</span>
```

#### E. Add Detail Form - Period Header Selection

**Before:**
```php
$piutang_headers = $conn->query("SELECT ph.id_piutang, ph.kode_proyek, p.nama_proyek, ph.periode_mulai, ph.periode_selesai 
    FROM buku_piutang_header ph 
    LEFT JOIN proyek p ON ph.kode_proyek = p.kode_proyek 
    ORDER BY ph.created_at DESC");

// Display
while ($ph = $piutang_headers->fetch_assoc()): 
?>  
    <option value="<?php echo $ph['id_piutang']; ?>">
        <?php echo $ph['kode_proyek'] . ' - ' . $ph['nama_proyek'] . ' (' . date('d/m/Y', strtotime($ph['periode_mulai'])) . ' - ' . date('d/m/Y', strtotime($ph['periode_selesai'])) . ')'; ?>
    </option>
<?php endwhile; ?>
```

**After:**
```php
$piutang_headers = $conn->query("SELECT ph.id_piutang, ph.kode_proyek, p.nama_proyek, ph.periode_bulan, ph.periode_tahun 
    FROM buku_piutang_header ph 
    LEFT JOIN proyek p ON ph.kode_proyek = p.kode_proyek 
    ORDER BY ph.created_at DESC");

// Display
while ($ph = $piutang_headers->fetch_assoc()): 
?>  
    <option value="<?php echo $ph['id_piutang']; ?>">
        <?php 
        $month_names = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $bulan_display = $month_names[(int)$ph['periode_bulan']];
        echo $ph['kode_proyek'] . ' - ' . $ph['nama_proyek'] . ' (' . $bulan_display . ' ' . $ph['periode_tahun'] . ')'; 
        ?>
    </option>
<?php endwhile; ?>
```

### 2. File: `assets/other/prcf_keuangan.sql`

Updated table structure to reflect new schema:

```sql
CREATE TABLE `buku_piutang_header` (
  `id_piutang` int(11) NOT NULL,
  `kode_proyek` varchar(50) DEFAULT NULL,
  `periode_bulan` char(2) NOT NULL,
  `periode_tahun` char(4) NOT NULL,
  -- ... rest of fields
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## 🎨 UI Changes

### Before: Date Range Picker
- Two date input fields (periode_mulai and periode_selesai)
- Calendar picker interface
- Required date range validation
- Display format: DD/MM/YYYY - DD/MM/YYYY

### After: Month/Year Selector
- Month dropdown (01-12) with Indonesian month names
- Year text input with 4-digit validation
- Cleaner, more focused period selection
- Display format: Bulan YYYY (e.g., "Januari 2025")

---

## 📊 Benefits

### 1. **Consistency Across Modules**
- Uniform period representation in both buku_bank and buku_piutang
- Reduces confusion for users working across different financial modules
- Standardized reporting and filtering logic

### 2. **Simplified User Input**
- Dropdown selection instead of calendar navigation
- Faster period selection (2 clicks vs multiple date selections)
- Reduced risk of selecting incorrect date ranges

### 3. **Better Data Organization**
- Natural hierarchical grouping by year → month
- Easier to query and filter by specific months or years
- Simplified period comparison across projects

### 4. **Improved Reporting**
- Month-based financial reports align with accounting standards
- Easier aggregation by month or year
- Consistent period labeling across all reports

### 5. **Technical Advantages**
- Simpler validation (2-digit month, 4-digit year)
- Reduced ambiguity in period definitions
- Better database indexing potential

---

## 🔄 Data Migration

### Important Note:
⚠️ **Existing Data**: If there were existing records with `periode_mulai` and `periode_selesai`, they would need manual migration. Since this is a new system with no production data, no migration script was required.

### Future Migration Strategy (if needed):
```sql
-- Example migration if old data exists
UPDATE buku_piutang_header 
SET 
    periode_bulan = LPAD(MONTH(periode_mulai), 2, '0'),
    periode_tahun = YEAR(periode_mulai)
WHERE periode_bulan IS NULL OR periode_tahun IS NULL;
```

---

## ✅ Testing Checklist

- [x] Database structure modified successfully
- [x] Create Header form updated with month/year selectors
- [x] Backend INSERT statement updated
- [x] Hierarchical display query updated
- [x] Period display formatting updated
- [x] Add Detail form dropdown updated
- [x] SQL schema file updated
- [x] No PHP syntax errors
- [x] Documentation created

---

## 🔗 Related Files

1. **Primary Implementation**:
   - `c:\xampp\htdocs\prcf_keuangan\pages\books\buku_piutang.php`

2. **Database Schema**:
   - `c:\xampp\htdocs\prcf_keuangan\assets\other\prcf_keuangan.sql`

3. **Documentation**:
   - This file: `docs\BUKU_PIUTANG_PERIOD_STRUCTURE_UPDATE.md`
   - Related: `docs\BUKU_PIUTANG_ACCOUNT_NAME_REMOVAL.md`
   - Related: `docs\EXCHANGE_RATE_FEATURE.md`

---

## 📝 Usage Example

### Creating a New Piutang Header

1. Click "Buat Header Periode Baru" button
2. Select project from "Kode Proyek" dropdown
3. Select month from "Bulan Periode" dropdown (e.g., "Januari")
4. Enter year in "Tahun Periode" field (e.g., "2025")
5. Enter beginning balances (optional)
6. Click "Buat Header"

**Result**: New header created for "Januari 2025" period

### Adding Transactions

1. Click "Tambah Transaksi" button
2. Select period header showing as: "PRJ-2025-001 - Project Name (Jan 2025)"
3. Fill transaction details
4. Submit transaction

---

## 🔮 Future Enhancements

1. **Period Validation**: Prevent duplicate periods for the same project
2. **Period Navigation**: Quick month navigation arrows (← → )
3. **Period Filtering**: Filter transactions by year or month range
4. **Period Closing**: Lock periods to prevent backdated entries
5. **Fiscal Year Support**: Configure fiscal year periods

---

## 📚 References

- **Buku Bank Structure**: Similar implementation in `buku_bank_header` table
- **Accounting Standards**: Monthly period representation aligns with financial reporting norms
- **Database Normalization**: Separate month/year fields enable better querying and indexing

---

## 👨‍💻 Development Notes

### Why CHAR(2) and CHAR(4)?
- **CHAR(2)** for month: Fixed-length with leading zero (01-12)
- **CHAR(4)** for year: Fixed 4-digit year format (e.g., 2025)
- Ensures consistent sorting and display formatting
- Simpler than parsing DATE fields

### Month Name Arrays
Two arrays used for different contexts:
```php
// Full month names (for period display in headers)
$month_names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Abbreviated month names (for compact dropdown display)
$month_names_short = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 
                      'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
```

---

**Last Updated**: 2025-10-22  
**Maintained By**: PRCF Indonesia Development Team
