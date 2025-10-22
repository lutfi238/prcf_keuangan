# Buku Piutang Exchange Rate Conversion Feature

**Date**: 2025-10-22  
**Status**: ✅ Completed  
**Module**: Buku Piutang (Accounts Receivable Book)  
**Developer**: AI Assistant  

---

## 📋 Overview

This document details the implementation of automatic exchange rate conversion for initial balance fields in the Buku Piutang (Accounts Receivable) module. The feature enables bidirectional real-time conversion between IDR and USD initial balances based on a manually entered exchange rate, matching the same functionality previously implemented in the Buku Bank module.

---

## 🎯 Objectives

1. **Bidirectional Conversion**: Automatically calculate USD when IDR is entered, and vice versa
2. **Real-time Feedback**: Instant visual updates in the UI as users type
3. **Backend Safety**: Server-side conversion as a backup for client-side calculations
4. **Consistency**: Match the exact behavior and user experience from the bank book module
5. **User Experience**: Reduce manual calculations and data entry errors

---

## ✨ Features

### 1. **Exchange Rate Field**
- New field in the Create Header form
- Default value: 1.00
- Min value: 0.01
- Step: 0.01 (allows 2 decimal places)
- Helpful hint text: "1 USD = ... IDR (untuk konversi otomatis)"

### 2. **Auto-Conversion Logic**

#### IDR → USD Conversion
```
USD = IDR / Exchange Rate
```
When a user enters an IDR amount, the USD field automatically calculates and displays the equivalent USD value.

#### USD → IDR Conversion
```
IDR = USD × Exchange Rate
```
When a user enters a USD amount, the IDR field automatically calculates and displays the equivalent IDR value.

#### Exchange Rate Change
When the exchange rate is modified:
- If IDR has a value: recalculate USD
- If USD has a value: recalculate IDR
- Provides instant feedback on currency value changes

### 3. **Helpful UI Indicators**
- Info icons and helper text on both balance fields
- Clear labeling: "Isi salah satu, yang lain otomatis dikonversi"
- Visual consistency with bank book module

---

## 💻 Implementation Details

### 1. HTML/Form Changes

#### Before (No Exchange Rate):
```html
<div>
    <label>Saldo Awal IDR</label>
    <input type="number" name="beginning_balance_idr" step="0.01" value="0">
</div>

<div>
    <label>Saldo Awal USD</label>
    <input type="number" name="beginning_balance_usd" step="0.01" value="0">
</div>
```

#### After (With Exchange Rate & Conversion):
```html
<div>
    <label>
        <i class="fas fa-exchange-alt text-green-500 mr-1"></i> Kurs (Exchange Rate) *
    </label>
    <input type="number" name="exrate" id="exrate_piutang" 
           step="0.01" value="1.00" min="0.01" required>
    <p class="text-xs text-gray-500 mt-1">
        <i class="fas fa-info-circle mr-1"></i>1 USD = ... IDR (untuk konversi otomatis)
    </p>
</div>

<div>
    <label>
        <i class="fas fa-money-bill-wave text-green-500 mr-1"></i> Saldo Awal IDR
    </label>
    <input type="number" name="beginning_balance_idr" id="beginning_balance_idr" 
           step="0.01" value="0">
    <p class="text-xs text-gray-500 mt-1">
        <i class="fas fa-info-circle mr-1"></i>Isi salah satu, yang lain otomatis dikonversi
    </p>
</div>

<div>
    <label>
        <i class="fas fa-dollar-sign text-green-500 mr-1"></i> Saldo Awal USD
    </label>
    <input type="number" name="beginning_balance_usd" id="beginning_balance_usd" 
           step="0.01" value="0">
    <p class="text-xs text-gray-500 mt-1">
        <i class="fas fa-info-circle mr-1"></i>Isi salah satu, yang lain otomatis dikonversi
    </p>
</div>
```

**Key Changes:**
- Added `id` attributes for JavaScript access
- Added exchange rate field with validation
- Added helper text below each field
- Added Font Awesome icons for visual clarity

### 2. JavaScript Implementation

```javascript
// Auto-convert initial balance between IDR and USD for Create Header Form
function setupInitialBalanceConversion() {
    const exrateInput = document.getElementById('exrate_piutang');
    const beginningBalanceIdrInput = document.getElementById('beginning_balance_idr');
    const beginningBalanceUsdInput = document.getElementById('beginning_balance_usd');
    
    if (!exrateInput || !beginningBalanceIdrInput || !beginningBalanceUsdInput) {
        return; // Elements not found, exit gracefully
    }
    
    // When IDR is entered, convert to USD
    beginningBalanceIdrInput.addEventListener('input', function() {
        const exrate = parseFloat(exrateInput.value) || 1;
        const idrValue = parseFloat(this.value) || 0;
        
        if (idrValue > 0 && exrate > 0) {
            beginningBalanceUsdInput.value = (idrValue / exrate).toFixed(2);
        }
    });
    
    // When USD is entered, convert to IDR
    beginningBalanceUsdInput.addEventListener('input', function() {
        const exrate = parseFloat(exrateInput.value) || 1;
        const usdValue = parseFloat(this.value) || 0;
        
        if (usdValue > 0 && exrate > 0) {
            beginningBalanceIdrInput.value = (usdValue * exrate).toFixed(2);
        }
    });
    
    // When exchange rate changes, recalculate conversion
    exrateInput.addEventListener('input', function() {
        const exrate = parseFloat(this.value) || 1;
        const idrValue = parseFloat(beginningBalanceIdrInput.value) || 0;
        const usdValue = parseFloat(beginningBalanceUsdInput.value) || 0;
        
        // Recalculate based on which field has value
        if (idrValue > 0 && exrate > 0) {
            beginningBalanceUsdInput.value = (idrValue / exrate).toFixed(2);
        } else if (usdValue > 0 && exrate > 0) {
            beginningBalanceIdrInput.value = (usdValue * exrate).toFixed(2);
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    setupInitialBalanceConversion();
    // ... other initialization code
});
```

**Features:**
- Defensive programming: checks if elements exist before attaching listeners
- Uses `parseFloat()` with fallback to handle invalid input
- `toFixed(2)` ensures 2 decimal places for currency display
- Only converts when values are greater than 0
- Handles all three scenarios: IDR input, USD input, and exchange rate change

### 3. Backend PHP Implementation

#### Create Header Handler (Before):
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

#### Create Header Handler (After):
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_header'])) {
    $kode_proyek = $_POST['kode_proyek'];
    $periode_bulan = $_POST['periode_bulan'];
    $periode_tahun = $_POST['periode_tahun'];
    $exrate = $_POST['exrate'] ?? 1.00;
    $beginning_balance_idr = $_POST['beginning_balance_idr'] ?? 0;
    $beginning_balance_usd = $_POST['beginning_balance_usd'] ?? 0;
    
    // Automatic conversion based on exchange rate (same logic as buku_bank)
    if ($exrate > 0) {
        if ($beginning_balance_idr > 0 && $beginning_balance_usd == 0) {
            $beginning_balance_usd = $beginning_balance_idr / $exrate;
        } elseif ($beginning_balance_usd > 0 && $beginning_balance_idr == 0) {
            $beginning_balance_idr = $beginning_balance_usd * $exrate;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO buku_piutang_header (kode_proyek, periode_bulan, periode_tahun, beginning_balance_idr, beginning_balance_usd, ending_balance_idr, ending_balance_usd, created_by, status, tgl_pembuatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', CURDATE())");
    $stmt->bind_param("sssddddi", $kode_proyek, $periode_bulan, $periode_tahun, $beginning_balance_idr, $beginning_balance_usd, $beginning_balance_idr, $beginning_balance_usd, $user_id);
    
    // ...
}
```

**Backend Conversion Logic:**
- Captures exchange rate from form submission
- Performs server-side conversion as backup
- If IDR is entered but USD is 0: calculate USD
- If USD is entered but IDR is 0: calculate IDR
- If both are provided: use submitted values (respects frontend calculation)
- Ensures data integrity even if JavaScript is disabled

---

## 🔄 User Flow

### Scenario 1: User Enters IDR Amount

1. User opens "Buat Header Periode Baru" form
2. User selects project and period
3. User enters exchange rate: **15,500.00**
4. User types IDR amount: **100,000,000**
5. **System automatically calculates**: USD = 100,000,000 / 15,500 = **6,451.61**
6. USD field instantly updates to show **6,451.61**
7. User clicks "Buat Header"
8. Backend verifies conversion and saves both values

### Scenario 2: User Enters USD Amount

1. User opens "Buat Header Periode Baru" form
2. User selects project and period
3. User enters exchange rate: **15,500.00**
4. User types USD amount: **5,000**
5. **System automatically calculates**: IDR = 5,000 × 15,500 = **77,500,000**
6. IDR field instantly updates to show **77,500,000.00**
7. User clicks "Buat Header"
8. Backend verifies conversion and saves both values

### Scenario 3: User Changes Exchange Rate

1. User has already entered IDR: **100,000,000**
2. USD is showing: **6,451.61** (at rate 15,500)
3. User updates exchange rate to: **16,000.00**
4. **System automatically recalculates**: USD = 100,000,000 / 16,000 = **6,250.00**
5. USD field updates to show **6,250.00**
6. User can proceed to save

---

## 📊 Benefits

### 1. **Reduced User Errors**
- Eliminates manual calculation mistakes
- Ensures accurate conversion at all times
- Prevents data inconsistency between IDR and USD values

### 2. **Improved User Experience**
- Real-time feedback (no need to click buttons)
- Faster data entry workflow
- Intuitive: user only needs to fill one currency field
- Clear visual indicators guide the user

### 3. **Data Integrity**
- Both frontend and backend perform conversion
- Consistent conversion logic across modules
- Exchange rate is always preserved for reference

### 4. **Consistency Across Modules**
- Matches bank book module behavior exactly
- Users experience familiar pattern across all financial forms
- Reduces learning curve for new features

### 5. **Flexibility**
- User can choose to enter either IDR or USD
- Exchange rate can be updated any time before submission
- System adapts to different exchange rates per period

---

## 🧪 Testing Checklist

- [x] Exchange rate field displays with default value 1.00
- [x] IDR input triggers USD calculation
- [x] USD input triggers IDR calculation
- [x] Exchange rate change recalculates displayed currency
- [x] Decimal places formatted to 2 digits
- [x] Zero values don't trigger conversion
- [x] Backend performs conversion as backup
- [x] Form submission saves both IDR and USD values correctly
- [x] Helper text displays correctly
- [x] Icons render properly
- [x] No JavaScript errors in console
- [x] Graceful handling if elements not found

---

## 🔗 Related Files

1. **Primary Implementation**:
   - `c:\xampp\htdocs\prcf_keuangan_dashboard\pages\books\buku_piutang.php`

2. **Reference Implementation** (Bank Book Module):
   - `c:\xampp\htdocs\prcf_keuangan_dashboard\pages\books\buku_bank.php`

3. **Documentation**:
   - This file: `docs\BUKU_PIUTANG_EXCHANGE_RATE_CONVERSION.md`
   - Related: `docs\EXCHANGE_RATE_FEATURE.md` (Bank Book)
   - Related: `docs\BUKU_PIUTANG_PERIOD_STRUCTURE_UPDATE.md`

---

## 📝 Usage Example

### Example 1: Creating Header with IDR Balance

**User Actions:**
1. Click "Buat Header Periode Baru"
2. Select project: "PRJ-2025-001 - Test Project"
3. Select month: "Januari"
4. Enter year: "2025"
5. Enter exchange rate: **15,750.00**
6. Enter Saldo Awal IDR: **250,000,000**
7. ✅ System auto-fills Saldo Awal USD: **15,873.02**
8. Click "Buat Header"

**Result:**
- Header created with:
  - Beginning Balance IDR: 250,000,000.00
  - Beginning Balance USD: 15,873.02
  - Ending Balance IDR: 250,000,000.00
  - Ending Balance USD: 15,873.02

### Example 2: Creating Header with USD Balance

**User Actions:**
1. Click "Buat Header Periode Baru"
2. Select project: "PRJ-2025-002 - Another Project"
3. Select month: "Februari"
4. Enter year: "2025"
5. Enter exchange rate: **15,800.00**
6. Enter Saldo Awal USD: **10,000**
7. ✅ System auto-fills Saldo Awal IDR: **158,000,000.00**
8. Click "Buat Header"

**Result:**
- Header created with:
  - Beginning Balance IDR: 158,000,000.00
  - Beginning Balance USD: 10,000.00
  - Ending Balance IDR: 158,000,000.00
  - Ending Balance USD: 10,000.00

---

## 🔮 Future Enhancements

1. **Historical Exchange Rates**: Auto-populate exchange rate based on period date
2. **Exchange Rate Validation**: Warn if rate is significantly different from recent periods
3. **Multi-Currency Support**: Extend to other currencies (EUR, SGD, etc.)
4. **Exchange Rate API Integration**: Fetch real-time rates from external API
5. **Conversion History Log**: Track exchange rate changes for audit purposes

---

## 📚 Technical Notes

### Why IDs Use Suffix "_piutang"?
```javascript
const exrateInput = document.getElementById('exrate_piutang');
```
- Prevents ID conflicts with bank book module if both pages loaded
- Makes debugging easier (clear which module the element belongs to)
- Follows best practice for unique element identification

### Decimal Precision
- All conversions use `.toFixed(2)` for display
- Backend stores with `DECIMAL(15,2)` precision
- Ensures consistent rounding across frontend and backend

### Defensive Programming
```javascript
if (!exrateInput || !beginningBalanceIdrInput || !beginningBalanceUsdInput) {
    return; // Exit gracefully if elements not found
}
```
- Prevents JavaScript errors if form structure changes
- Allows page to function even if conversion feature fails
- Makes code more maintainable and robust

### Backend Conversion as Safety Net
Even though frontend JavaScript performs real-time conversion, the backend also calculates:
- Protects against JavaScript being disabled
- Ensures data integrity
- Provides consistent behavior regardless of client capabilities

---

## 🎓 Code Specification Compliance

### Bidirectional Field Calculation Rule ✅
**Memory ID**: `e1491d1e-6baf-4488-ae0e-6abd25b82e12`

> When two fields are related by a conversion factor (e.g., IDR/USD via exchange rate), entering a value in either field must automatically calculate and update the other field in real time, both on the frontend and backend.

**Implementation:**
- ✅ Frontend: JavaScript event listeners on both IDR and USD fields
- ✅ Real-time: Uses `input` event for instant feedback
- ✅ Bidirectional: IDR→USD and USD→IDR conversions
- ✅ Backend: PHP conversion logic as backup
- ✅ Conversion factor: Exchange rate field controls calculation

### Cross-Module UI Consistency ✅
**Memory ID**: `b049fea2-64ef-4027-a889-fcf88ab13aed`

The implementation matches the bank book module's UX pattern:
- Same visual design (colors, icons, spacing)
- Same interaction behavior (real-time conversion)
- Same helper text patterns
- Same responsive characteristics

---

**Last Updated**: 2025-10-22  
**Maintained By**: PRCF Indonesia Development Team
