<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// Get the tender ID from the request
$tenderId = $_GET['id'] ?? null;
if (!$tenderId || !is_numeric($tenderId)) {
    echo json_encode(['success' => false, 'message' => 'Valid tender ID is required.']);
    exit;
}

try {
    // First check if the tender exists and belongs to the user
    $checkStmt = $pdo->prepare("SELECT id FROM saved_tenders WHERE id = ? AND user_id = ?");
    $checkStmt->execute([$tenderId, $_SESSION['user_id']]);
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Tender not found or does not belong to you.']);
        exit;
    }

    // Prepare the SQL statement to remove the tender
    $stmt = $pdo->prepare("DELETE FROM saved_tenders WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$tenderId, $_SESSION['user_id']]);

    // Check if the deletion was successful
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Tender removed successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove tender. Please try again.']);
    }
} catch (PDOException $e) {
    // Log the error message for debugging
    error_log("Database error in remove_saved_tender.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again.']);
} catch (Exception $e) {
    // Log any other errors
    error_log("General error in remove_saved_tender.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>