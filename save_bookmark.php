<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in.',
            'redirect' => 'login.php'
        ]);
        exit;
    }

    // Get JSON data from request body
    $json = file_get_contents('php://input');
    $tender_data = json_decode($json, true);

    if (!$tender_data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data received.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    
    // Extract data from the tender object - note the correct field names
    $tender_id = $tender_data['tender_No'] ?? ''; // Using tender_No as the ID
    $title = $tender_data['tender_No'] ?? 'Untitled';
    $description = $tender_data['description'] ?? 'No description available';
    $category = $tender_data['category'] ?? 'Uncategorized';
    $department = $tender_data['department'] ?? 'N/A';
    $province = $tender_data['province'] ?? 'Unknown';
    $closing_date = $tender_data['closing_Date'] ?? '';
    $advertised_date = $tender_data['date_Published'] ?? '';
    $contact_person = $tender_data['contactPerson'] ?? '';
    $contact_email = $tender_data['email'] ?? '';
    $contact_phone = $tender_data['telephone'] ?? '';
    $fax = $tender_data['fax'] ?? '';
    $streetname = $tender_data['streetname'] ?? '';
    $surburb = $tender_data['surburb'] ?? '';
    $town = $tender_data['town'] ?? '';
    $code = $tender_data['code'] ?? '';
    $conditions = $tender_data['conditions'] ?? '';
    $briefingSession = $tender_data['briefingSession'] ?? '';
    $briefingVenue = $tender_data['briefingVenue'] ?? '';
    $briefingCompulsory = $tender_data['briefingCompulsory'] ?? '';
    $compulsory_briefing_session = $tender_data['compulsory_briefing_session'] ?? '';
    $organ_of_State = $tender_data['organ_of_State'] ?? '';
    $status = $tender_data['status'] ?? '';
    $type = $tender_data['type'] ?? '';
    
    // Handle support documents
    $supportDocument = null;
    if (isset($tender_data['supportDocument']) && is_array($tender_data['supportDocument'])) {
        $supportDocument = json_encode($tender_data['supportDocument']);
    }

    // Format dates properly
    $advertised_date = !empty($advertised_date) ? date('Y-m-d H:i:s', strtotime($advertised_date)) : null;
    $closing_date = !empty($closing_date) ? date('Y-m-d H:i:s', strtotime($closing_date)) : null;
    $compulsory_briefing_session = !empty($compulsory_briefing_session) ? date('Y-m-d H:i:s', strtotime($compulsory_briefing_session)) : null;

    if (empty($tender_id)) {
        echo json_encode(['success' => false, 'message' => 'Tender ID is required. Received data: ' . json_encode($tender_data)]);
        exit;
    }

    try {
        // Check if tender is already bookmarked
        $checkStmt = $pdo->prepare("SELECT id FROM saved_tenders WHERE user_id = ? AND tender_id = ?");
        $checkStmt->execute([$user_id, $tender_id]);
        
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Tender already bookmarked.']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO saved_tenders 
            (user_id,tender_id, tender_No, description, category, department, province, closing_Date, date_Published, contactPerson, email, telephone, fax, streetname, surburb, town, code, conditions, briefingSession, briefingVenue, briefingCompulsory,compulsory_briefing_session, supportDocument, saved_at) 
            VALUES (?,?,?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $user_id,
            $tender_id,
            $title,
            $description,
            $category,
            $department,
            $province,
            $closing_date,
            $advertised_date,
            $contact_person,
            $contact_email,
            $contact_phone,
            $fax,
            $streetname,
            $surburb,
            $town,
            $code,
            $conditions,
            $briefingSession,
            $briefingVenue,
            $briefingCompulsory,
            $compulsory_briefing_session,
            $supportDocument
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Tender bookmarked successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save tender to database.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error saving tender: ' . $e->getMessage()]);
    }

    exit;
}

// If not POST request, return error
echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
?>