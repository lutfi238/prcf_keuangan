# Bank Book Excel Export Feature

## Overview
This feature allows Finance Managers to export individual bank book period data to Excel format for printing and offline analysis. Each period (month) has its own dedicated "Export to Excel" button positioned next to the delete button in the detail view.

## Implementation Date
October 22, 2025

## Feature Location
- **Module**: Bank Book (`buku_bank.php`)
- **Section**: Project Totals - Detail View (Summary Bar)
- **Button Position**: Right side of the summary bar, to the left of the "Hapus" (Delete) button

## Files Created/Modified

### New Files
1. **`pages/books/export_bank_excel.php`**
   - Standalone export script that generates Excel files
   - Uses Excel XML format (no external libraries required)
   - Accepts bank header ID via GET parameter

### Modified Files
1. **`pages/books/buku_bank.php`**
   - Added "Export to Excel" button in summary bar
   - Button positioned next to delete button
   - Opens export in new tab to preserve current view

## Technical Details

### Export Format
- **File Type**: Microsoft Excel XML (.xls)
- **Compatibility**: Excel 2003+, LibreOffice Calc, Google Sheets
- **No Dependencies**: Pure PHP implementation, no external libraries needed

### Excel Structure

#### Header Section
1. **Title Row**: "BANK BOOK - [CURRENCY]" (bold, large font)
2. **Metadata Section**:
   - Project Code
   - Project Name
   - Period (Month Year)
   - Bank Name
   - Account Name
   - Account Number
   - Exchange Rate

#### Data Table
**Columns**:
1. Date (formatted as dd/mm/yyyy)
2. Reference
3. Title Activity
4. Cost Description
5. Recipient
6. Debit (IDR) - with number formatting
7. Credit (IDR) - with number formatting
8. Balance (IDR) - with number formatting
9. Debit (USD) - with number formatting
10. Credit (USD) - with number formatting

**Special Rows**:
- **Beginning Balance Row**: Shows initial balance for the period
- **Transaction Rows**: All transactions in chronological order
- **Ending Balance Row**: Shows final balance (gray background, bold)

#### Summary Section
- Total Debit (IDR)
- Total Credit (IDR)
- Net Change (IDR)
- Total Debit (USD)
- Total Credit (USD)
- Net Change (USD)

#### Footer
- Prepared by: [User Name]
- Generated on: [Date Time]

### Styling Features

1. **Header Styling**:
   - Blue background (#4472C4) for column headers
   - Bold text for headers
   - Centered alignment

2. **Data Cells**:
   - Borders on all cells
   - Number format: `#,##0.00` for currency values
   - Right-aligned for numeric values
   - Center-aligned for dates

3. **Special Formatting**:
   - Ending balance row: Gray background (#E7E6E6)
   - Summary section: Bold labels
   - Title: 16pt font size
   - Metadata: Bold labels with regular values

### Filename Convention
```
Bank_Book_[CURRENCY]_[PROJECT_CODE]_[YEAR]_[MONTH]_[TIMESTAMP].xls
```

**Example**:
```
Bank_Book_IDR_RC01_2025_October_20251022114530.xls
```

### Security Features

1. **Session Validation**: Requires active login session
2. **Role-Based Access**: Only Finance Managers can export
3. **Maintenance Mode Check**: Respects system maintenance mode
4. **SQL Injection Protection**: Uses prepared statements
5. **XSS Prevention**: All output is HTML-escaped

## Usage Instructions

### For Finance Managers

1. **Navigate to Bank Book**:
   - Go to Dashboard → Buku Bank

2. **Expand Project Data**:
   - Click on a project to expand years
   - Click on a year to expand months
   - View the period you want to export

3. **Export to Excel**:
   - Locate the summary bar for the desired month
   - Click the green "Export to Excel" button (next to "Hapus")
   - Excel file will download automatically
   - File opens in new tab (preserves your current view)

4. **Print/Use Offline**:
   - Open the downloaded Excel file
   - Review data, make offline notes if needed
   - Print directly from Excel with proper formatting

### Button Appearance
```
┌─────────────────────────────────────────────────────────┐
│ [Export to Excel] [Hapus]                               │
│  (Green Button)    (Red Button)                         │
└─────────────────────────────────────────────────────────┘
```

## Developer Notes

### Why Excel XML Format?

1. **No Dependencies**: Doesn't require PHPSpreadsheet or other libraries
2. **Lightweight**: Small file sizes, fast generation
3. **Compatible**: Works with all modern Excel versions
4. **Styling Support**: Full support for colors, borders, fonts, number formats
5. **Reliable**: Well-documented Microsoft standard

### Customization Options

#### To Modify Excel Layout
Edit `export_bank_excel.php`:
- **Column Widths**: Modify `<Column ss:Width="XXX"/>` values
- **Colors**: Change `ss:Color="#XXXXXX"` in style definitions
- **Fonts**: Adjust `ss:FontName` and `ss:Size` attributes
- **Number Formats**: Update `ss:Format` in style definitions

#### To Add More Data Fields
1. Add new column in table header section
2. Add corresponding data cell in transaction loop
3. Update column count in merged cells if needed
4. Adjust column width definitions

#### To Change Filename Format
Modify the `sprintf()` call around line 57 in `export_bank_excel.php`

### Code Quality

**Strengths**:
- ✅ Proper session management
- ✅ SQL injection protection
- ✅ XSS prevention
- ✅ Clean separation of concerns
- ✅ Professional Excel formatting
- ✅ Comprehensive data export

**Maintainability**:
- Clear code comments
- Logical structure
- Easy to extend
- Follows project conventions

## Testing Checklist

- [x] Export button appears in correct position
- [x] Button styling matches design system
- [x] Export generates valid Excel file
- [x] File downloads with correct name
- [x] All data fields export correctly
- [x] Number formatting works properly
- [x] Date formatting is correct
- [x] Beginning/ending balances match
- [x] Summary calculations are accurate
- [x] Security validations work
- [x] Works in all major browsers
- [x] Opens in new tab without disrupting current view

## Future Enhancements

### Potential Improvements

1. **Batch Export**: 
   - Export multiple periods at once
   - Generate workbook with multiple sheets (one per period)

2. **Template Customization**:
   - Allow users to choose export templates
   - Add company logo to exports
   - Custom header/footer text

3. **Advanced Filters**:
   - Export only specific date ranges within a period
   - Filter by transaction type (debit/credit)
   - Include/exclude specific categories

4. **PDF Export**:
   - Alternative export format for official documents
   - Digital signatures integration

5. **Email Integration**:
   - Send exported file directly via email
   - Schedule automatic periodic reports

6. **Audit Trail**:
   - Log all export actions
   - Track who exported what and when

## Sample Excel Output Structure

```
┌─────────────────────────────────────────────────────────────┐
│                    BANK BOOK - IDR                          │
├─────────────────────────────────────────────────────────────┤
│ Project Code:    RC01                                       │
│ Project Name:    Sample Project                             │
│ Period:          October 2025                               │
│ Bank Name:       BCA                                        │
│ Account Name:    Main Account                               │
│ Account Number:  1234567890                                 │
│ Exchange Rate:   15000.00                                   │
├──────┬─────────┬──────────┬──────────┬─────────┬───────────┤
│ Date │ Ref     │ Activity │ Desc     │ Recip   │ Debit IDR │...
├──────┼─────────┼──────────┼──────────┼─────────┼───────────┤
│      │         │ Beginning Balance    │         │ 1,000,000 │
├──────┼─────────┼──────────┼──────────┼─────────┼───────────┤
│01/10 │ REF001  │ Payment  │ Salary   │ John    │   500,000 │
│05/10 │ REF002  │ Receipt  │ Income   │ Client  │ 2,000,000 │
├──────┼─────────┼──────────┼──────────┼─────────┼───────────┤
│      │         │ Ending Balance       │         │ 2,500,000 │
└──────┴─────────┴──────────┴──────────┴─────────┴───────────┘

Summary:
Total Debit (IDR):      2,500,000.00
Total Credit (IDR):       500,000.00
Net Change (IDR):       2,000,000.00
...

Prepared by: Finance Manager
Generated on: 22/10/2025 11:45:30
```

## Reference Files

The Excel export format was designed based on the sample file:
- **Location**: `assets/other/02 - Bank Book IDR - RC01 - Year 3 - buku_bank.xls`
- **Purpose**: Reference template for layout, styling, and data structure

## Support & Troubleshooting

### Common Issues

1. **Export Button Not Visible**:
   - Clear browser cache
   - Ensure you're logged in as Finance Manager
   - Check browser console for JavaScript errors

2. **Download Doesn't Start**:
   - Check browser popup blocker settings
   - Ensure session hasn't expired
   - Try opening link in new tab manually

3. **Excel Shows Warning on Open**:
   - This is normal for XML-based Excel files
   - Click "Yes" to open anyway - file is safe
   - Data will display correctly

4. **Formatting Issues in Excel**:
   - Ensure you're using Excel 2003 or later
   - Try opening in LibreOffice Calc or Google Sheets
   - Check if macros are disabled (not needed for this file)

### Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| "No bank header ID specified" | Missing or invalid ID parameter | Check URL has valid `?id=` parameter |
| "Bank header not found" | Invalid header ID | Ensure the bank header exists in database |
| "Unauthorized" | Wrong user role | Login as Finance Manager |
| "Session expired" | Timeout | Re-login and try again |

## Related Documentation

- [Bank Book Module Documentation](./BANK_BOOK_MODULE.md) - If exists
- [Exchange Rate Feature](./EXCHANGE_RATE_FEATURE.md)
- [Bank Details Autocomplete](./BANK_DETAILS_AUTOCOMPLETE_FEATURE.md)

## Change Log

### Version 1.0 (October 22, 2025)
- ✅ Initial implementation
- ✅ Excel XML export format
- ✅ Professional styling and formatting
- ✅ Security validations
- ✅ Comprehensive data export
- ✅ Summary calculations
- ✅ User-friendly button placement

---

**Last Updated**: October 22, 2025  
**Author**: PRCF Development Team  
**Status**: Production Ready ✅
