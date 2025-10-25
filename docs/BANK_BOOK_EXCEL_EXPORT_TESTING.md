# Testing Guide - Bank Book Excel Export

## Prerequisites

Before testing, ensure:
- ✅ You are logged in as Finance Manager
- ✅ There is at least one bank book header with transactions
- ✅ Browser allows popup/new tab from the domain

## Test Cases

### Test 1: Basic Export Functionality

**Objective**: Verify that the export button appears and downloads an Excel file.

**Steps**:
1. Navigate to: `http://localhost/prcf_keuangan/pages/books/buku_bank.php`
2. Expand a project that has bank book data
3. Expand a year
4. Locate a month/period with transactions
5. Find the green "Export to Excel" button in the summary bar
6. Click the button

**Expected Results**:
- ✅ New tab opens
- ✅ Excel file downloads automatically
- ✅ Filename follows pattern: `Bank_Book_[CURRENCY]_[PROJECT]_[YEAR]_[MONTH]_[TIMESTAMP].xls`
- ✅ Original page remains intact (no navigation)

**Pass Criteria**: File downloads successfully with correct naming convention.

---

### Test 2: Excel File Content Validation

**Objective**: Verify that the Excel file contains all required data.

**Steps**:
1. Complete Test 1 to download an Excel file
2. Open the downloaded file in Microsoft Excel or LibreOffice Calc
3. Review the content

**Expected Results**:

#### Header Section (Rows 1-9)
- ✅ Row 1: Title "BANK BOOK - [CURRENCY]" (bold, large)
- ✅ Row 2: Project Code displayed correctly
- ✅ Row 3: Project Name displayed correctly
- ✅ Row 4: Period (e.g., "October 2025")
- ✅ Row 5: Bank Name
- ✅ Row 6: Account Name
- ✅ Row 7: Account Number
- ✅ Row 8: Exchange Rate value

#### Data Table (Row 11+)
- ✅ Row 11: Column headers with blue background
- ✅ Row 12: "Beginning Balance" row with correct amount
- ✅ Row 13+: All transactions in chronological order
- ✅ Each row contains: Date, Ref, Activity, Description, Recipient, Debits, Credits, Balances
- ✅ Last transaction row: "Ending Balance" with gray background

#### Summary Section
- ✅ Total Debit (IDR) matches sum of all debits
- ✅ Total Credit (IDR) matches sum of all credits
- ✅ Net Change (IDR) = Total Debit - Total Credit
- ✅ Total Debit (USD) present
- ✅ Total Credit (USD) present
- ✅ Net Change (USD) present

#### Footer
- ✅ "Prepared by: [Name]" present
- ✅ "Generated on: [Date Time]" present with current timestamp

**Pass Criteria**: All sections present with accurate data.

---

### Test 3: Number Formatting

**Objective**: Verify that numbers are properly formatted in Excel.

**Steps**:
1. Open downloaded Excel file
2. Select any cell with a currency value
3. Check the cell formatting

**Expected Results**:
- ✅ Numbers display with thousands separator (e.g., 1,000,000.00)
- ✅ Two decimal places for all currency values
- ✅ Negative numbers display correctly (if any)
- ✅ Zero values display as "0.00"
- ✅ Right-aligned in cells

**Pass Criteria**: All currency values formatted consistently and correctly.

---

### Test 4: Visual Styling

**Objective**: Verify that the Excel file has proper styling.

**Steps**:
1. Open downloaded Excel file
2. Review visual appearance

**Expected Results**:

#### Colors
- ✅ Header row: Blue background (#4472C4)
- ✅ Ending balance row: Gray background (#E7E6E6)
- ✅ Data cells: White background

#### Borders
- ✅ All data cells have borders
- ✅ Header row has thicker borders
- ✅ Ending balance row has thick bottom border

#### Fonts
- ✅ Title: 16pt, bold
- ✅ Section headers: 12pt, bold
- ✅ Column headers: 11pt, bold
- ✅ Data: 11pt, regular
- ✅ All text: Calibri font family

#### Alignment
- ✅ Numbers: Right-aligned
- ✅ Dates: Center-aligned
- ✅ Text: Left-aligned
- ✅ Column headers: Center-aligned

**Pass Criteria**: Styling matches professional financial report standards.

---

### Test 5: Data Accuracy

**Objective**: Verify that exported data matches web display.

**Steps**:
1. Keep the bank book page open
2. Export to Excel
3. Compare values side-by-side

**Expected Results**:
- ✅ Beginning balance matches
- ✅ Each transaction amount matches
- ✅ Running balance after each transaction matches
- ✅ Ending balance matches
- ✅ Transaction count matches
- ✅ All dates match (check format dd/mm/yyyy)
- ✅ All text fields match (activity, description, recipient)

**Pass Criteria**: 100% data accuracy between web and Excel.

---

### Test 6: Multiple Periods Export

**Objective**: Test exporting different periods from the same project.

**Steps**:
1. Navigate to bank book page
2. Export Period 1 (e.g., January 2025)
3. Export Period 2 (e.g., February 2025)
4. Export Period 3 (e.g., March 2025)

**Expected Results**:
- ✅ Each export creates a unique file
- ✅ Filenames are different (timestamp differs)
- ✅ Each file contains correct period data
- ✅ No data mixing between periods
- ✅ All exports complete successfully

**Pass Criteria**: Each period exports independently with correct data.

---

### Test 7: Different Projects Export

**Objective**: Test exporting data from different projects.

**Steps**:
1. Navigate to bank book page
2. Expand Project A, export a period
3. Expand Project B, export a period
4. Compare files

**Expected Results**:
- ✅ Filenames include correct project codes
- ✅ Each file shows correct project name
- ✅ Data is project-specific (no mixing)
- ✅ Bank details are project-specific

**Pass Criteria**: Project data remains isolated and accurate.

---

### Test 8: Empty Period Export

**Objective**: Test exporting a period with no transactions.

**Steps**:
1. Create a new bank book header without adding transactions
2. Export that period

**Expected Results**:
- ✅ Excel file generates successfully
- ✅ Header section displays correctly
- ✅ "Beginning Balance" row present
- ✅ No transaction rows (only beginning/ending balance)
- ✅ "Ending Balance" row shows same as beginning balance
- ✅ Summary section shows all zeros except balances
- ✅ No errors or warnings

**Pass Criteria**: Export handles empty periods gracefully.

---

### Test 9: Large Dataset Export

**Objective**: Test performance with many transactions.

**Steps**:
1. Find or create a period with 100+ transactions
2. Export to Excel
3. Measure time and review result

**Expected Results**:
- ✅ Export completes in under 5 seconds
- ✅ File size is reasonable (<1MB for 100 transactions)
- ✅ All transactions present in Excel
- ✅ No timeout errors
- ✅ Excel opens quickly

**Pass Criteria**: Handles large datasets efficiently.

---

### Test 10: Browser Compatibility

**Objective**: Test export across different browsers.

**Steps**:
1. Test export in Chrome
2. Test export in Firefox
3. Test export in Edge
4. Test export in Safari (if available)

**Expected Results**:
- ✅ Download works in all browsers
- ✅ New tab behavior consistent
- ✅ File opens correctly from all browsers
- ✅ No browser-specific issues

**Pass Criteria**: Works in all major browsers.

---

### Test 11: Security & Access Control

**Objective**: Verify security measures are in place.

**Steps**:
1. Logout from system
2. Try to access: `http://localhost/prcf_keuangan/pages/books/export_bank_excel.php?id=BH-20251020-073558-25fb`
3. Login as Project Manager (not Finance Manager)
4. Try to access the export URL again
5. Login as Finance Manager
6. Try to access export with invalid ID

**Expected Results**:
- ✅ Logged out: Redirects to login page
- ✅ Wrong role: Redirects to unauthorized page
- ✅ Invalid ID: Shows "Bank header not found" error
- ✅ Valid access: Export works

**Pass Criteria**: Security validations prevent unauthorized access.

---

### Test 12: Button Placement & Visibility

**Objective**: Verify the export button is correctly positioned and visible.

**Steps**:
1. Navigate to bank book page
2. Expand a project/year/period
3. Locate the summary bar

**Expected Results**:
- ✅ Export button visible on right side of summary bar
- ✅ Button positioned left of "Hapus" button
- ✅ Green background color
- ✅ Excel icon visible (`fa-file-excel`)
- ✅ Text reads "Export to Excel"
- ✅ Proper spacing between buttons (8px)
- ✅ Hover effect changes to darker green
- ✅ Cursor changes to pointer on hover

**Pass Criteria**: Button is professionally styled and positioned.

---

### Test 13: Concurrent Exports

**Objective**: Test multiple simultaneous exports.

**Steps**:
1. Open bank book page
2. Quickly click export on 3 different periods
3. Check results

**Expected Results**:
- ✅ All 3 files download
- ✅ Each file is unique
- ✅ No errors or conflicts
- ✅ All files contain correct data

**Pass Criteria**: Handles concurrent exports without issues.

---

### Test 14: IDR vs USD Currency

**Objective**: Test export for both currency types.

**Steps**:
1. Export a period with currency = IDR
2. Export a period with currency = USD
3. Compare files

**Expected Results**:
- ✅ Title shows correct currency (IDR or USD)
- ✅ Filename includes correct currency
- ✅ Both currencies display in data table
- ✅ Primary currency highlighted/emphasized appropriately

**Pass Criteria**: Both currencies export correctly.

---

### Test 15: Special Characters & Encoding

**Objective**: Test handling of special characters in data.

**Steps**:
1. Create/find transactions with:
   - Indonesian characters (é, á, etc.)
   - Symbols (&, @, #, etc.)
   - Long text descriptions
2. Export to Excel
3. Open and review

**Expected Results**:
- ✅ All characters display correctly
- ✅ No encoding issues (Ã, â, etc.)
- ✅ Long text doesn't break layout
- ✅ Special symbols preserved

**Pass Criteria**: Text encoding is correct (UTF-8).

---

## Quick Smoke Test (5 minutes)

For rapid validation, perform these essential checks:

1. ✅ **Login** as Finance Manager
2. ✅ **Navigate** to Buku Bank page
3. ✅ **Expand** one project/year/period
4. ✅ **Locate** green "Export to Excel" button
5. ✅ **Click** export button
6. ✅ **Verify** file downloads
7. ✅ **Open** file in Excel
8. ✅ **Check** data is present and formatted
9. ✅ **Compare** one transaction with web display
10. ✅ **Verify** beginning/ending balances match

If all 10 steps pass → Feature is working correctly ✅

---

## Regression Testing

After any code changes, re-run:
- Test 1 (Basic functionality)
- Test 2 (Content validation)
- Test 5 (Data accuracy)
- Test 11 (Security)

---

## Performance Benchmarks

| Dataset Size | Expected Export Time | Max File Size |
|--------------|----------------------|---------------|
| 10 transactions | <1 second | ~30KB |
| 50 transactions | <2 seconds | ~80KB |
| 100 transactions | <3 seconds | ~150KB |
| 500 transactions | <8 seconds | ~600KB |

If performance exceeds these benchmarks, investigate optimization.

---

## Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| Download doesn't start | Popup blocker | Allow popups for localhost |
| File shows XML warning | Normal for XML-based Excel | Click "Yes" to open |
| Wrong data in export | Wrong header ID | Check URL parameter |
| Formatting broken in Excel | Old Excel version | Use Excel 2007+ |
| Missing transactions | Database sync issue | Refresh page and retry |

---

## Test Report Template

```
Test Date: __________
Tester: __________
Browser: __________
Excel Version: __________

┌──────────┬────────┬─────────────────────┐
│ Test #   │ Result │ Notes               │
├──────────┼────────┼─────────────────────┤
│ Test 1   │ ☐ PASS │                     │
│          │ ☐ FAIL │                     │
├──────────┼────────┼─────────────────────┤
│ Test 2   │ ☐ PASS │                     │
│          │ ☐ FAIL │                     │
├──────────┼────────┼─────────────────────┤
│ ...      │        │                     │
└──────────┴────────┴─────────────────────┘

Overall Status: ☐ All Tests Pass  ☐ Issues Found

Critical Issues:
-

Minor Issues:
-

Recommendations:
-
```

---

**Last Updated**: October 22, 2025  
**Version**: 1.0  
**Status**: Ready for Testing ✅
