<?php
/**
 * Date Format Helper for Indonesian Format
 * 
 * Provides utility functions to convert between database format (YYYY-MM-DD)
 * and Indonesian display format (DD/MM/YYYY)
 */

/**
 * Convert database date (YYYY-MM-DD) to Indonesian format (DD/MM/YYYY)
 * @param string $date Date in YYYY-MM-DD format
 * @return string Date in DD/MM/YYYY format
 */
function formatDateID($date) {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date; // Return original if parse fails
    }
    return date('d/m/Y', $timestamp);
}

/**
 * Convert Indonesian format (DD/MM/YYYY) to database format (YYYY-MM-DD)
 * @param string $date Date in DD/MM/YYYY format
 * @return string Date in YYYY-MM-DD format
 */
function parseDateID($date) {
    if (empty($date)) {
        return null;
    }
    
    // If already in YYYY-MM-DD format, return as is
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }
    
    // Parse DD/MM/YYYY format
    $parts = explode('/', $date);
    if (count($parts) === 3) {
        $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
        $year = $parts[2];
        
        // Handle 2-digit year
        if (strlen($year) === 2) {
            $year = '20' . $year;
        }
        
        return "$year-$month-$day";
    }
    
    return $date; // Return original if parse fails
}

/**
 * Get today's date in Indonesian format
 * @return string Today in DD/MM/YYYY format
 */
function todayID() {
    return date('d/m/Y');
}

/**
 * Format date with full month name in Indonesian
 * @param string $date Date in any format
 * @return string Date like "08 Januari 2026"
 */
function formatDateLongID($date) {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    $day = date('d', $timestamp);
    $month = $months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day $month $year";
}
