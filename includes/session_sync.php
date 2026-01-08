<?php
/**
 * Session Sync Helper
 * Ensures session data is synchronized with database
 */

/**
 * Sync session user data with database
 * Updates session if user data has changed in database
 */
function sync_session_with_database($conn) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Get current user data from database
    $stmt = $conn->prepare("SELECT nama, email, role FROM user WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User no longer exists - invalidate session
        session_destroy();
        return false;
    }
    
    $user = $result->fetch_assoc();
    
    // Status check removed as column does not exist
    // Defaulting to active behavior for now
    
    // Update session with latest data
    $_SESSION['user_name'] = $user['nama'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    return true;
}
?>
