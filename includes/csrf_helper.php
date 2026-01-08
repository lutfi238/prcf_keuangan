<?php
/**
 * CSRF Protection Helper
 * 
 * Provides CSRF token generation and validation for form security.
 */

/**
 * Generate CSRF token and store in session
 * 
 * @return string Generated CSRF token
 */
function csrf_generate() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate new token if not exists or expired (30 min)
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > 1800) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from request
 * 
 * @param string|null $token Token to validate (defaults to $_POST['csrf_token'])
 * @return bool True if valid
 */
function csrf_validate($token = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    
    // Check if token exists and matches
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Use timing-safe comparison
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate hidden input field with CSRF token
 * 
 * @return string HTML hidden input element
 */
function csrf_field() {
    $token = csrf_generate();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF and die with error if invalid
 * 
 * @param string $redirect_url Optional URL to redirect on failure
 */
function csrf_check($redirect_url = null) {
    if (!csrf_validate()) {
        if ($redirect_url) {
            header('Location: ' . $redirect_url . '?error=csrf_failed');
            exit();
        }
        http_response_code(403);
        die('Invalid security token. Please refresh and try again.');
    }
}

/**
 * Get CSRF token value for AJAX requests
 * 
 * @return string Current CSRF token
 */
function csrf_token() {
    return csrf_generate();
}
?>
