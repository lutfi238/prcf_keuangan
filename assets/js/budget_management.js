/**
 * Budget Management Shared Logic
 * Used in: manage_budgets.php (FM) and create_proposal.php (PM)
 */

// Format currency
function formatCurrency(amount, currency = 'IDR') {
    if (currency === 'IDR') {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
    } else {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(amount);
    }
}

// Generate Place Code: [exp_code]-[village_abbr]-01
function generatePlaceCode(expCode, villageAbbr) {
    if (!expCode || !villageAbbr) return '';
    return `${expCode}-${villageAbbr}-01`;
}

// Calculate IDR from USD
function calculateIDR(usd, exrate) {
    return (parseFloat(usd) || 0) * (parseFloat(exrate) || 1);
}

// Calculate USD from IDR
function calculateUSD(idr, exrate) {
    return (parseFloat(idr) || 0) / (parseFloat(exrate) || 1);
}

// Fetch latest exchange rate
async function fetchLatestExrate() {
    try {
        const response = await fetch('../../api/get_latest_exrate.php');
        const data = await response.json();
        return data.rate || 15500;
    } catch (error) {
        console.error('Error fetching exrate:', error);
        return 15500;
    }
}

// Fetch villages
async function fetchVillages() {
    try {
        const response = await fetch('../../api/get_villages.php');
        const data = await response.json();
        // Ensure we always return an array
        if (Array.isArray(data)) {
            return data;
        }
        console.error('Villages API returned non-array:', data);
        return [];
    } catch (error) {
        console.error('Error fetching villages:', error);
        return [];
    }
}

// Fetch Exp Codes by Project and optional Village
async function fetchExpCodes(kodeProyek, idVillage = null) {
    try {
        let url = `../../api/get_exp_codes_by_project.php?kode_proyek=${kodeProyek}`;
        if (idVillage) {
            url += `&id_village=${idVillage}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        // Ensure we always return an array
        if (Array.isArray(data)) {
            return data;
        }
        console.error('ExpCodes API returned non-array:', data);
        return [];
    } catch (error) {
        console.error('Error fetching exp codes:', error);
        return [];
    }
}
