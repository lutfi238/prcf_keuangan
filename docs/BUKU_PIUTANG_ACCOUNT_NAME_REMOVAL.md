# Buku Piutang Header - Account Name Field Removal

## Overview
Removed the redundant `account_name` (also stored as `nama_proyek`) field from the `buku_piutang_header` table. The system now exclusively uses the project name (`nama_proyek`) from the `proyek` table via the existing foreign key relationship (`kode_proyek`).

## Rationale

### Before (Redundant Data)
- `buku_piutang_header` table stored **both** `kode_proyek` (foreign key) **and** `account_name`/`nama_proyek` (redundant project name)
- This created data duplication and potential inconsistency
- If project name changed in `proyek` table, the `buku_piutang_header` would show outdated names

### After (Normalized Data)
- `buku_piutang_header` only stores `kode_proyek` (foreign key reference)
- Project name is fetched from `proyek` table via JOIN
- Single source of truth for project names
- Automatic updates when project names change

## Database Changes

### Column Removed
**Table**: `buku_piutang_header`  
**Column**: `nama_proyek` (previously called `account_name` in schema file)  
**Type**: `varchar(255)`

### SQL Migration Command
```sql
ALTER TABLE buku_piutang_header DROP COLUMN nama_proyek;
```

### Execution via Command Prompt
```cmd
C:\xampp\mysql\bin\mysql.exe -u root -e "USE prcf_keuangan; ALTER TABLE buku_piutang_header DROP COLUMN nama_proyek;"
```

### Verification
```cmd
C:\xampp\mysql\bin\mysql.exe -u root -e "USE prcf_keuangan; DESCRIBE buku_piutang_header;"
```

## Implementation Details

### Files Modified

1. **`pages/books/buku_piutang.php`** - Main buku piutang page
   - Removed `account_name` input from create header form
   - Updated INSERT query to exclude `account_name`
   - Updated SELECT queries to use JOIN for fetching project name
   - Updated display to show `nama_proyek` from JOIN

2. **`assets/other/prcf_keuangan.sql`** - Database schema reference
   - Removed `account_name` column from table definition

### Backend (PHP) Changes

#### ✅ Create Header Form Handler - BEFORE
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_header'])) {
    $kode_proyek = $_POST['kode_proyek'];
    $account_name = $_POST['account_name'];  // ❌ Removed
    $periode_mulai = $_POST['periode_mulai'];
    $periode_selesai = $_POST['periode_selesai'];
    $beginning_balance_idr = $_POST['beginning_balance_idr'] ?? 0;
    $beginning_balance_usd = $_POST['beginning_balance_usd'] ?? 0;
    
    $stmt = $conn->prepare("INSERT INTO buku_piutang_header (kode_proyek, account_name, ...) VALUES (?, ?, ...)");
    $stmt->bind_param("ssssddddi", $kode_proyek, $account_name, ...);
}
```

#### ✅ Create Header Form Handler - AFTER
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_header'])) {
    $kode_proyek = $_POST['kode_proyek'];
    // account_name removed - will use nama_proyek from proyek table
    $periode_mulai = $_POST['periode_mulai'];
    $periode_selesai = $_POST['periode_selesai'];
    $beginning_balance_idr = $_POST['beginning_balance_idr'] ?? 0;
    $beginning_balance_usd = $_POST['beginning_balance_usd'] ?? 0;
    
    $stmt = $conn->prepare("INSERT INTO buku_piutang_header (kode_proyek, periode_mulai, periode_selesai, ...) VALUES (?, ?, ?, ...)");
    $stmt->bind_param("sssdddddi", $kode_proyek, $periode_mulai, $periode_selesai, ...);
}
```

#### ✅ Data Retrieval Query - BEFORE
```php
$piutang_headers = $conn->query("SELECT ph.id_piutang, ph.kode_proyek, ph.account_name, ph.periode_mulai, ph.periode_selesai, p.nama_proyek 
    FROM buku_piutang_header ph 
    LEFT JOIN proyek p ON ph.kode_proyek = p.kode_proyek 
    ORDER BY ph.created_at DESC");
```

#### ✅ Data Retrieval Query - AFTER
```php
$piutang_headers = $conn->query("SELECT ph.id_piutang, ph.kode_proyek, p.nama_proyek, ph.periode_mulai, ph.periode_selesai 
    FROM buku_piutang_header ph 
    LEFT JOIN proyek p ON ph.kode_proyek = p.kode_proyek 
    ORDER BY ph.created_at DESC");
```

**Note**: Now fetching `nama_proyek` **only from JOIN**, not from `buku_piutang_header` table.

### Frontend (HTML) Changes

#### ✅ Create Header Form - UPDATED WITH UI DISPLAY
```html
<div>
    <label class="block text-gray-700 text-sm font-semibold mb-2">
        <i class="fas fa-project-diagram text-green-500 mr-1"></i> Kode Proyek *
    </label>
    <select name="kode_proyek" id="kode_proyek" required onchange="updateProjectName()">
        <option value="">Pilih Proyek</option>
        <option value="PRJ-001" data-nama-proyek="Project Alpha">PRJ-001 - Project Alpha</option>
        <!-- More project options -->
    </select>
</div>

<!-- ✅ NEW: Auto-populated project name display -->
<div>
    <label class="block text-gray-700 text-sm font-semibold mb-2">
        <i class="fas fa-folder text-green-500 mr-1"></i> Nama Proyek
    </label>
    <input type="text" id="nama_proyek_display" readonly 
           placeholder="Otomatis terisi saat memilih proyek"
           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-700 cursor-not-allowed">
    <p class="text-xs text-gray-500 mt-1">
        <i class="fas fa-info-circle mr-1"></i>Otomatis terisi berdasarkan proyek yang dipilih
    </p>
</div>

<div>
    <label class="block text-gray-700 text-sm font-semibold mb-2">
        <i class="fas fa-calendar-plus text-green-500 mr-1"></i> Periode Mulai *
    </label>
    <input type="date" name="periode_mulai" required>
</div>
```

**Key Features:**
- Project name field is **read-only** (cannot be edited)
- Automatically updates when project is selected
- Visual styling shows it's auto-populated (gray background)
- Helpful tooltip explains the behavior

#### ✅ Display Header Title - BEFORE
```php
<h4 class="font-bold text-white text-lg">
    <i class="fas fa-file-invoice-dollar mr-2"></i>
    <?php echo $header['account_name']; ?>  <!-- ❌ From buku_piutang_header table -->
</h4>
```

#### ✅ Display Header Title - AFTER
```php
<h4 class="font-bold text-white text-lg">
    <i class="fas fa-file-invoice-dollar mr-2"></i>
    <?php echo $header['nama_proyek']; ?>  <!-- ✅ From proyek table via JOIN -->
</h4>
```

#### ✅ Dropdown Options - BEFORE
```php
<option value="<?php echo $ph['id_piutang']; ?>">
    <?php echo $ph['kode_proyek'] . ' - ' . $ph['account_name'] . ' (...)'; ?>
</option>
```

#### ✅ Dropdown Options - AFTER
```php
<option value="<?php echo $ph['id_piutang']; ?>">
    <?php echo $ph['kode_proyek'] . ' - ' . $ph['nama_proyek'] . ' (...)'; ?>
</option>
```

## Updated Database Schema

### buku_piutang_header Table Structure
```sql
CREATE TABLE `buku_piutang_header` (
  `id_piutang` int(11) NOT NULL AUTO_INCREMENT,
  `kode_proyek` varchar(50) DEFAULT NULL,
  -- `account_name` removed - use JOIN to get nama_proyek from proyek table
  `periode_mulai` date DEFAULT NULL,
  `periode_selesai` date DEFAULT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_piutang`),
  KEY `kode_proyek` (`kode_proyek`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `buku_piutang_header_ibfk_1` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE SET NULL,
  CONSTRAINT `buku_piutang_header_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `user` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `buku_piutang_header_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `user` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Foreign Key Relationship
```sql
-- buku_piutang_header.kode_proyek → proyek.kode_proyek
-- Fetches nama_proyek via JOIN instead of storing redundantly
```

## Benefits

### 1. **Data Normalization**
- ✅ Eliminates redundant data storage
- ✅ Follows database normalization principles (3NF)
- ✅ Reduces database size

### 2. **Data Integrity**
- ✅ Single source of truth for project names
- ✅ Automatic updates when project names change
- ✅ No data inconsistency between tables

### 3. **Simplified User Experience**
- ✅ Users only select project once
- ✅ Project name auto-displays in read-only field for clarity
- ✅ No manual entry of project name (reduces errors)
- ✅ Visual feedback when project is selected

### 4. **Easier Maintenance**
- ✅ Update project name in one place (`proyek` table)
- ✅ All piutang records automatically reflect changes
- ✅ Less data to manage

## User Experience Changes

### Creating a New Piutang Header

#### Before (2 manual inputs)
1. Select **Kode Proyek** from dropdown
2. Manually type **Nama Akun** (could be inconsistent) ❌
3. Fill in period dates and balances
4. Submit

#### After (1 manual input + auto-display)
1. Select **Kode Proyek** from dropdown ✅
2. **Nama Proyek** automatically displays in read-only field 🎉
3. Fill in period dates and balances
4. Submit

**Result**: Project name visible for user reference, consistent data, better UX!

### Viewing Piutang Records

#### Before
- Displayed `account_name` stored in `buku_piutang_header`
- Could be outdated if project name changed

#### After
- Displays `nama_proyek` from `proyek` table via JOIN
- Always shows current project name
- Automatic updates

## Migration Guide

### For Existing Data

If you have existing data in the `nama_proyek` column before removal:

```sql
-- No data migration needed!
-- The foreign key relationship (kode_proyek) is already present
-- JOIN queries will fetch the correct project name from proyek table
```

### Rollback Procedure (If Needed)

If you need to restore the field:

```sql
-- Add column back
ALTER TABLE buku_piutang_header 
ADD COLUMN account_name VARCHAR(255) DEFAULT NULL 
AFTER kode_proyek;

-- Populate with project names
UPDATE buku_piutang_header bph
JOIN proyek p ON bph.kode_proyek = p.kode_proyek
SET bph.account_name = p.nama_proyek;
```

**Warning**: This goes against normalization principles. Only rollback if absolutely necessary!

## Testing Checklist

- [x] Database column successfully removed
- [x] Create header form displays project name field (read-only)
- [x] Project name auto-updates when project is selected
- [x] Piutang headers display correct project names from JOIN
- [x] Dropdown options show project names correctly
- [x] JavaScript updateProjectName() function works
- [x] No PHP errors or warnings
- [x] Data integrity maintained with foreign key
- [x] SQL schema file updated
- [x] UI provides visual feedback to users

## Related Changes

### Similar Pattern in Other Tables

This change follows the normalization principle. Consider applying to other tables:

- ✅ **buku_bank_header**: Already uses JOIN for `nama_proyek`
- ✅ **laporan_keuangan_header**: Uses `kode_projek` with JOIN
- ⚠️ **Other tables**: Review for similar redundancies

## Notes for Developers

### When Querying buku_piutang_header

**Always use JOIN** to get project name:

```php
// ✅ CORRECT
$query = "SELECT ph.*, p.nama_proyek 
          FROM buku_piutang_header ph
          LEFT JOIN proyek p ON ph.kode_proyek = p.kode_proyek";

// ❌ WRONG - account_name no longer exists
$query = "SELECT ph.*, ph.account_name 
          FROM buku_piutang_header ph";
```

### When Creating New Records

**Only provide kode_proyek**:

```php
// ✅ CORRECT
$stmt = $conn->prepare("INSERT INTO buku_piutang_header (kode_proyek, ...) VALUES (?, ...)");

// ❌ WRONG - account_name column doesn't exist
$stmt = $conn->prepare("INSERT INTO buku_piutang_header (kode_proyek, account_name, ...) VALUES (?, ?, ...)");
```

## Migration Status

✅ Database column removed successfully  
✅ PHP code updated (create, read, display)  
✅ Form fields updated (removed account_name input)  
✅ SQL schema file updated  
✅ Documentation completed  
✅ Foreign key relationship verified  

## Related Documentation
- Exchange Rate Feature: `docs/EXCHANGE_RATE_FEATURE.md`
- Bank Details Autocomplete: `docs/BANK_DETAILS_AUTOCOMPLETE_FEATURE.md`
- Database Schema: `assets/other/prcf_keuangan.sql`
- Main Implementation: `pages/books/buku_piutang.php`
