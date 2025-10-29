/**
 * Currency Formatting Utility
 * Formats numbers with thousand separators for Rupiah/Dollar
 * Usage: 400000 → 400.000
 */

function formatCurrency(value) {
    // Remove all non-digit characters except decimal point
    let num = value.toString().replace(/[^\d.]/g, '');
    
    // Split into integer and decimal parts
    let parts = num.split('.');
    let integerPart = parts[0];
    let decimalPart = parts.length > 1 ? '.' + parts[1] : '';
    
    // Add thousand separators (using period for Indonesian style)
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    
    return integerPart + decimalPart;
}

function parseCurrency(formattedValue) {
    // Remove thousand separators and return numeric value
    return parseFloat(formattedValue.toString().replace(/\./g, '').replace(',', '.') || '0');
}

// Auto-apply formatting to currency input fields
document.addEventListener('DOMContentLoaded', function() {
    // Find all currency input fields
    const currencyInputs = document.querySelectorAll(
        'input[name*="unit_cost"], input[name*="requested"], input[name*="actual"], ' +
        'input[name*="nilai"], input[name*="anggaran"], input[name*="cost"]'
    );
    
    currencyInputs.forEach(input => {
        // Format on input
        input.addEventListener('input', function(e) {
            const cursorPosition = e.target.selectionStart;
            const oldLength = e.target.value.length;
            
            // Get numeric value
            const numericValue = parseCurrency(e.target.value);
            
            // Format the value
            const formatted = formatCurrency(numericValue);
            
            // Update input value
            e.target.value = formatted;
            
            // Adjust cursor position
            const newLength = formatted.length;
            const diff = newLength - oldLength;
            e.target.setSelectionRange(cursorPosition + diff, cursorPosition + diff);
        });
        
        // Format on blur
        input.addEventListener('blur', function(e) {
            const numericValue = parseCurrency(e.target.value);
            e.target.value = formatCurrency(numericValue);
        });
        
        // Initial format if value exists
        if (input.value) {
            input.value = formatCurrency(input.value);
        }
    });
});

// Function to parse all currency fields before form submission
function prepareCurrencyForSubmit(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        // Find all currency inputs in the form
        const currencyInputs = form.querySelectorAll(
            'input[name*="unit_cost"], input[name*="requested"], input[name*="actual"], ' +
            'input[name*="nilai"], input[name*="anggaran"], input[name*="cost"]'
        );
        
        // Convert formatted values back to numeric before submission
        currencyInputs.forEach(input => {
            const numericValue = parseCurrency(input.value);
            input.value = numericValue;
        });
    });
}

