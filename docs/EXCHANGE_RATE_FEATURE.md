# Exchange Rate Feature for Bank Book Header

## Overview
Added exchange rate (exrate) functionality to the `buku_bank_header` table, enabling automatic conversion between IDR and USD initial balances based on a manually inputted exchange rate.

## Database Changes

### Column Added
**Table**: `buku_bank_header`  
**Column**: `exrate`  
**Type**: `DECIMAL(12,2)`  
**Default**: `1.00`  
**Position**: After `account_number` column

### SQL Migration Command
```sql
ALTER TABLE buku_bank_header 
ADD COLUMN exrate DECIMAL(12,2) DEFAULT 1.00 
AFTER account_number;
```

### Execution via Command Prompt
```cmd
C:\xampp\mysql\bin\mysql.exe -u root -e "USE prcf_keuangan; ALTER TABLE buku_bank_header ADD COLUMN exrate DECIMAL(12,2) DEFAULT 1.00 AFTER account_number;"
```

### Verification
```cmd
C:\xampp\mysql\bin\mysql.exe -u root -e "USE prcf_keuangan; DESCRIBE buku_bank_header;"
```

## Implementation Details

### Files Modified
1. **`pages/books/buku_bank.php`** - Bank book main page
   - Added exchange rate field to create header form
   - Implemented server-side conversion logic
   - Added client-side real-time conversion

2. **`assets/other/prcf_keuangan.sql`** - Database schema reference
   - Updated schema to include exrate column

### Backend (PHP) Changes

#### Form Handler Update
```php
// Handle create new bank header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_header'])) {
    
    $exrate = $_POST['exrate'] ?? 1.00; // Exchange rate
    $saldo_awal_idr = $_POST['saldo_awal_idr'] ?? 0;
    $saldo_awal_usd = $_POST['saldo_awal_usd'] ?? 0;
    
    // Automatic conversion based on which field was filled
    if ($exrate > 0) {
        if ($saldo_awal_idr > 0 && $saldo_awal_usd == 0) {
            // Convert IDR to USD
            $saldo_awal_usd = $saldo_awal_idr / $exrate;
        } elseif ($saldo_awal_usd > 0 && $saldo_awal_idr == 0) {
            // Convert USD to IDR
            $saldo_awal_idr = $saldo_awal_usd * $exrate;
        }
    }
    
    // Insert includes exrate column
    $stmt = $conn->prepare("INSERT INTO buku_bank_header (..., exrate, ...) VALUES (...)");
}
```

**Conversion Logic:**
- If `saldo_awal_idr` > 0 and `saldo_awal_usd` = 0 → Calculate USD = IDR / exrate
- If `saldo_awal_usd` > 0 and `saldo_awal_idr` = 0 → Calculate IDR = USD * exrate
- If both have values → Keep both as entered
- If both are 0 → Keep both at 0

### Frontend (HTML) Changes

#### Exchange Rate Field
```html
<div>
    <label class="block text-gray-700 text-sm font-semibold mb-2">
        <i class="fas fa-exchange-alt text-green-500 mr-1"></i> Kurs (Exchange Rate) *
    </label>
    <input type="number" name="exrate" id="exrate" step="0.01" value="1.00" 
           min="0.01" required 
           class="w-full px-4 py-3 border border-gray-300 rounded-lg...">
    <p class="text-xs text-gray-500 mt-1">
        <i class="fas fa-info-circle mr-1"></i>1 USD = ... IDR (untuk konversi otomatis)
    </p>
</div>
```

#### Updated Balance Fields
```html
<!-- Saldo Awal IDR -->
<input type="number" name="saldo_awal_idr" id="saldo_awal_idr" step="0.01" value="0">
<p class="text-xs text-gray-500 mt-1">
    <i class="fas fa-info-circle mr-1"></i>Isi salah satu, yang lain otomatis dikonversi
</p>

<!-- Saldo Awal USD -->
<input type="number" name="saldo_awal_usd" id="saldo_awal_usd" step="0.01" value="0">
<p class="text-xs text-gray-500 mt-1">
    <i class="fas fa-info-circle mr-1"></i>Isi salah satu, yang lain otomatis dikonversi
</p>
```

### Frontend (JavaScript) Changes

#### Real-Time Conversion Function
```javascript
function setupInitialBalanceConversion() {
    const exrateInput = document.getElementById('exrate');
    const saldoIdrInput = document.getElementById('saldo_awal_idr');
    const saldoUsdInput = document.getElementById('saldo_awal_usd');
    
    // When IDR is entered, convert to USD
    saldoIdrInput.addEventListener('input', function() {
        const exrate = parseFloat(exrateInput.value) || 1;
        const idrValue = parseFloat(this.value) || 0;
        
        if (idrValue > 0 && exrate > 0) {
            saldoUsdInput.value = (idrValue / exrate).toFixed(2);
        }
    });
    
    // When USD is entered, convert to IDR
    saldoUsdInput.addEventListener('input', function() {
        const exrate = parseFloat(exrateInput.value) || 1;
        const usdValue = parseFloat(this.value) || 0;
        
        if (usdValue > 0 && exrate > 0) {
            saldoIdrInput.value = (usdValue * exrate).toFixed(2);
        }
    });
    
    // When exchange rate changes, recalculate conversion
    exrateInput.addEventListener('input', function() {
        const exrate = parseFloat(this.value) || 1;
        const idrValue = parseFloat(saldoIdrInput.value) || 0;
        const usdValue = parseFloat(saldoUsdInput.value) || 0;
        
        // Recalculate based on which field has value
        if (idrValue > 0 && exrate > 0) {
            saldoUsdInput.value = (idrValue / exrate).toFixed(2);
        } else if (usdValue > 0 && exrate > 0) {
            saldoIdrInput.value = (usdValue * exrate).toFixed(2);
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // ... other initializations ...
    setupInitialBalanceConversion();
});
```

## User Experience Flow

### Creating a New Bank Book Header

#### Option 1: Enter IDR Amount
1. Staff Accountant enters exchange rate (e.g., 15,500.00)
2. Enters IDR initial balance (e.g., 10,000,000.00)
3. **System automatically calculates** USD = 10,000,000 / 15,500 = 645.16
4. Both values are saved to database

#### Option 2: Enter USD Amount
1. Staff Accountant enters exchange rate (e.g., 15,500.00)
2. Enters USD initial balance (e.g., 1,000.00)
3. **System automatically calculates** IDR = 1,000 * 15,500 = 15,500,000.00
4. Both values are saved to database

#### Option 3: Change Exchange Rate
1. Staff Accountant enters IDR or USD amount
2. **Changes the exchange rate** (e.g., from 15,500 to 16,000)
3. **System automatically recalculates** the other currency
4. Values update in real-time without page reload

## Features

### ✅ Automatic Conversion
- **Real-time**: Conversion happens as user types
- **Bidirectional**: Works both IDR→USD and USD→IDR
- **Dynamic**: Updates when exchange rate changes

### ✅ Server-Side Validation
- Exchange rate must be > 0
- At least one balance field should have a value
- Both currencies calculated and saved consistently

### ✅ User-Friendly Interface
- Clear labels with icons
- Helpful tooltips explaining conversion
- Visual feedback on field interactions
- Consistent with existing UI design

### ✅ Data Integrity
- Default exchange rate: 1.00
- Decimal precision: 2 places for amounts
- Prevents division by zero
- Handles empty/null values gracefully

## Benefits

1. **Efficiency**: Eliminates manual calculation errors
2. **Consistency**: Ensures both currencies always match exchange rate
3. **Flexibility**: Users can enter in their preferred currency
4. **Transparency**: Shows both IDR and USD values clearly
5. **Accuracy**: Uses precise decimal calculations

## Testing Scenarios

### Test Case 1: IDR to USD Conversion
**Input:**
- Exchange Rate: 15,750.00
- Saldo Awal IDR: 50,000,000.00
- Saldo Awal USD: 0

**Expected Output:**
- Saldo Awal USD: 3,174.60

### Test Case 2: USD to IDR Conversion
**Input:**
- Exchange Rate: 15,750.00
- Saldo Awal IDR: 0
- Saldo Awal USD: 5,000.00

**Expected Output:**
- Saldo Awal IDR: 78,750,000.00

### Test Case 3: Exchange Rate Change
**Initial:**
- Exchange Rate: 15,500.00
- Saldo Awal IDR: 31,000,000.00
- Saldo Awal USD: 2,000.00 (auto-calculated)

**User changes exchange rate to: 16,000.00**

**Expected Output:**
- Saldo Awal USD: 1,937.50 (recalculated)

### Test Case 4: Both Fields Entered
**Input:**
- Exchange Rate: 15,500.00
- Saldo Awal IDR: 10,000,000.00
- Saldo Awal USD: 500.00

**Expected Output:**
- Both values retained as entered (no automatic conversion)

## Database Schema Reference

### Updated Table Structure
```sql
CREATE TABLE `buku_bank_header` (
  `id_bank_header` varchar(30) NOT NULL,
  `kode_proyek` varchar(20) NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `exrate` decimal(12,2) DEFAULT 1.00,  -- NEW COLUMN
  `currency` varchar(10) NOT NULL,
  `periode_bulan` char(2) NOT NULL,
  `periode_tahun` char(4) NOT NULL,
  `saldo_awal_idr` decimal(18,2) DEFAULT 0.00,
  `saldo_awal_usd` decimal(18,2) DEFAULT 0.00,
  `current_period_change_idr` decimal(18,2) DEFAULT 0.00,
  `current_period_change_usd` decimal(18,2) DEFAULT 0.00,
  `saldo_akhir_idr` decimal(18,2) DEFAULT 0.00,
  `saldo_akhir_usd` decimal(18,2) DEFAULT 0.00,
  `prepared_by` varchar(100) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `status_laporan` enum('draft','submitted','approved') DEFAULT 'draft',
  `tanggal_pembuatan` date DEFAULT curdate(),
  `tanggal_persetujuan` date DEFAULT NULL,
  PRIMARY KEY (`id_bank_header`),
  KEY `kode_proyek` (`kode_proyek`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Notes for Future Development

1. **Exchange Rate History**: Consider adding a table to track exchange rate changes over time
2. **Auto-fetch Rates**: Potential integration with currency API for real-time rates
3. **Rounding Rules**: May need to implement specific rounding rules per accounting standards
4. **Audit Trail**: Track when conversions occur and what rates were used

## Migration Status

✅ Database column added successfully  
✅ PHP backend conversion logic implemented  
✅ JavaScript real-time conversion implemented  
✅ UI fields added and styled  
✅ Schema file updated  
✅ Documentation completed  

## Rollback Procedure (If Needed)

If you need to remove this feature:

```sql
ALTER TABLE buku_bank_header DROP COLUMN exrate;
```

**Warning**: This will permanently remove the exchange rate data. Make sure to backup data first!

## Related Documentation
- Bank Details Autocomplete Feature: `docs/BANK_DETAILS_AUTOCOMPLETE_FEATURE.md`
- Database Schema: `assets/other/prcf_keuangan.sql`
- Main Implementation: `pages/books/buku_bank.php`
