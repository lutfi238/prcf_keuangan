<?php
/**
 * Get Next Village Code API
 * 
 * Returns the next available village code with gap detection.
 * If codes 0001, 0003 exist (0002 deleted), returns 0002.
 * If no gaps, returns MAX + 1.
 * 
 * Format: 4 digit numeric with zero-padding (0001, 0002, etc.)
 */

session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Get all active numeric village codes
    $result = $conn->query("
        SELECT CAST(village_code AS UNSIGNED) as code_num 
        FROM villages 
        WHERE is_deleted = 0 
        AND village_code REGEXP '^[0-9]+$'
        ORDER BY code_num ASC
    ");
    
    $existing_codes = [];
    while ($row = $result->fetch_assoc()) {
        $existing_codes[] = (int)$row['code_num'];
    }
    
    $next_code = 1; // Start from 0001
    
    if (!empty($existing_codes)) {
        // Find first gap in sequence
        $expected = 1;
        $found_gap = false;
        
        foreach ($existing_codes as $code) {
            if ($code > $expected) {
                // Found a gap
                $next_code = $expected;
                $found_gap = true;
                break;
            }
            $expected = $code + 1;
        }
        
        if (!$found_gap) {
            // No gaps, use next after max
            $next_code = max($existing_codes) + 1;
        }
    }
    
    // Format with zero-padding (4 digits)
    $formatted_code = str_pad($next_code, 4, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'success' => true,
        'next_code' => $formatted_code
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
