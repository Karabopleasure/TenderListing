<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to update preferences']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action']) || !isset($data['department'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $department = $data['department'];
    $action = $data['action'];
    
    if ($action !== 'toggle_department') {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
    // Get current preferences
    $stmt = $pdo->prepare("SELECT preferred_departments FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $currentPreferences = [];
    if ($result && $result['preferred_departments']) {
        $currentPreferences = json_decode($result['preferred_departments'], true) ?? [];
    }
    
    // Toggle department
    $key = array_search($department, $currentPreferences);
    if ($key !== false) {
        // Remove department
        array_splice($currentPreferences, $key, 1);
        $message = 'Department removed from preferences';
    } else {
        // Add department
        $currentPreferences[] = $department;
        $message = 'Department added to preferences';
    }
    
    // Update or insert preferences
    if ($result) {
        // Update existing record
        $updateStmt = $pdo->prepare("UPDATE user_preferences SET preferred_departments = ?, updated_at = NOW() WHERE user_id = ?");
        $updateResult = $updateStmt->execute([json_encode($currentPreferences), $userId]);
    } else {
        // Insert new record
        $insertStmt = $pdo->prepare("INSERT INTO user_preferences (user_id, preferred_departments, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $updateResult = $insertStmt->execute([$userId, json_encode($currentPreferences)]);
    }
    
    if ($updateResult) {
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update preferences']);
    }
    
} catch (Exception $e) {
    error_log('Preferences error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>