<?php
/**
 * Village Management API
 * Handles CRUD operations for villages with audit trail
 * 
 * Actions: add, update, delete (soft)
 * Access: Admin, Finance Manager only
 */

header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/maintenance_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

// Role check: Admin or Finance Manager only
$allowed_roles = ['Admin', 'Finance Manager'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Finance Manager access required.']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

// Handle form-based POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_village'])) {
        addVillage($conn, $user_id);
        exit;
    }
    if (isset($_POST['edit_village'])) {
        updateVillage($conn, $user_id);
        exit;
    }
    if (isset($_POST['delete_village'])) {
        $_POST['id_village'] = $_POST['village_id'] ?? 0;
        deleteVillage($conn, $user_id);
        exit;
    }
}

try {
    switch ($action) {
        case 'add':
            addVillage($conn, $user_id);
            break;
        
        case 'update':
            updateVillage($conn, $user_id);
            break;
        
        case 'delete':
            deleteVillage($conn, $user_id);
            break;
        
        case 'list':
            listVillages($conn);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Add new village
 */
function addVillage($conn, $user_id) {
    // Support both JSON and Form Data
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($content_type, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
    } else {
        $data = $_POST;
    }
    
    // Validate input
    $village_code = trim($data['village_code'] ?? ($data['villageCode'] ?? ''));
    $village_name = trim($data['village_name'] ?? ($data['villageName'] ?? ''));
    $village_abbr = strtoupper(trim($data['village_abbr'] ?? ($data['villageAbbr'] ?? '')));
    $description = trim($data['description'] ?? ($data['villageDescription'] ?? ''));
    
    // Validation
    if (empty($village_code) || empty($village_name) || empty($village_abbr)) {
        echo json_encode(['success' => false, 'message' => 'Village code, name, and abbreviation are required']);
        return;
    }
    
    // Validate village_code format (V###)
    if (!preg_match('/^[A-Z0-9]{1,4}$/', $village_code)) {
        echo json_encode(['success' => false, 'message' => 'Village code must be 1-4 characters (alphanumeric)']);
        return;
    }
    
    // Validate village_abbr format (2-5 uppercase letters)
    if (!preg_match('/^[A-Z]{2,5}$/', $village_abbr)) {
        echo json_encode(['success' => false, 'message' => 'Village abbreviation must be 2-5 uppercase letters (e.g., NJ, BWI)']);
        return;
    }
    
    // Check uniqueness for village_code (exclude deleted)
    $check_code = $conn->prepare("SELECT id_village FROM villages WHERE village_code = ? AND is_deleted = 0");
    $check_code->bind_param("s", $village_code);
    $check_code->execute();
    if ($check_code->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Village code already exists']);
        return;
    }
    
    // Check uniqueness for village_abbr (exclude deleted)
    $check_abbr = $conn->prepare("SELECT id_village FROM villages WHERE village_abbr = ? AND is_deleted = 0");
    $check_abbr->bind_param("s", $village_abbr);
    $check_abbr->execute();
    if ($check_abbr->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Village abbreviation already exists']);
        return;
    }
    
    // Insert new village
    $stmt = $conn->prepare("INSERT INTO villages (village_code, village_name, village_abbr, description, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $village_code, $village_name, $village_abbr, $description, $user_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Village added successfully',
            'id_village' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add village: ' . $conn->error]);
    }
}

/**
 * Update existing village
 */
function updateVillage($conn, $user_id) {
    // Support both JSON and Form Data
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($content_type, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
    } else {
        $data = $_POST;
    }
    
    $id_village = intval($data['id_village'] ?? ($data['village_id'] ?? 0));
    $village_code = trim($data['village_code'] ?? ($data['villageCode'] ?? ''));
    $village_name = trim($data['village_name'] ?? ($data['villageName'] ?? ''));
    $village_abbr = strtoupper(trim($data['village_abbr'] ?? ($data['villageAbbr'] ?? '')));
    $description = trim($data['description'] ?? ($data['villageDescription'] ?? ''));
    
    // Validation
    if ($id_village <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid village ID']);
        return;
    }
    
    if (empty($village_code) || empty($village_name) || empty($village_abbr)) {
        echo json_encode(['success' => false, 'message' => 'Village code, name, and abbreviation are required']);
        return;
    }
    
    // Validate formats
    if (!preg_match('/^[A-Z0-9]{1,4}$/', $village_code)) {
        echo json_encode(['success' => false, 'message' => 'Village code must be 1-4 characters (alphanumeric)']);
        return;
    }
    
    if (!preg_match('/^[A-Z]{2,5}$/', $village_abbr)) {
        echo json_encode(['success' => false, 'message' => 'Village abbreviation must be 2-5 uppercase letters']);
        return;
    }
    
    // Check uniqueness (exclude self and deleted)
    $check_code = $conn->prepare("SELECT id_village FROM villages WHERE village_code = ? AND id_village != ? AND is_deleted = 0");
    $check_code->bind_param("si", $village_code, $id_village);
    $check_code->execute();
    if ($check_code->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Village code already exists']);
        return;
    }
    
    $check_abbr = $conn->prepare("SELECT id_village FROM villages WHERE village_abbr = ? AND id_village != ? AND is_deleted = 0");
    $check_abbr->bind_param("si", $village_abbr, $id_village);
    $check_abbr->execute();
    if ($check_abbr->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Village abbreviation already exists']);
        return;
    }
    
    // Update village
    $stmt = $conn->prepare("UPDATE villages SET village_code = ?, village_name = ?, village_abbr = ?, description = ?, updated_by = ? WHERE id_village = ? AND is_deleted = 0");
    $stmt->bind_param("ssssii", $village_code, $village_name, $village_abbr, $description, $user_id, $id_village);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Village updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Village not found or already deleted']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update village: ' . $conn->error]);
    }
}

/**
 * Soft delete village
 */
function deleteVillage($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id_village = intval($data['id_village'] ?? 0);
    
    if ($id_village <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid village ID']);
        return;
    }
    
    // Check if village is used in budgets
    $check_usage = $conn->prepare("SELECT COUNT(*) as count FROM project_code_budgets WHERE id_village = ?");
    $check_usage->bind_param("i", $id_village);
    $check_usage->execute();
    $usage_result = $check_usage->get_result()->fetch_assoc();
    
    if ($usage_result['count'] > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete village: It is used in ' . $usage_result['count'] . ' budget allocation(s). The village will be hidden from dropdowns but preserved for historical data.'
        ]);
        // Still proceed with soft delete to hide it
    }
    
    // Soft delete
    $stmt = $conn->prepare("UPDATE villages SET is_deleted = 1, updated_by = ? WHERE id_village = ?");
    $stmt->bind_param("ii", $user_id, $id_village);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Village deleted successfully (soft delete)']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Village not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete village: ' . $conn->error]);
    }
}

/**
 * List all active villages with audit info
 */
function listVillages($conn) {
    $query = "SELECT v.*, 
              uc.nama as created_by_name, 
              uu.nama as updated_by_name
              FROM villages v
              LEFT JOIN user uc ON v.created_by = uc.id_user
              LEFT JOIN user uu ON v.updated_by = uu.id_user
              WHERE v.is_deleted = 0
              ORDER BY v.village_name ASC";
    
    $result = $conn->query($query);
    $villages = [];
    
    while ($row = $result->fetch_assoc()) {
        $villages[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $villages]);
}
