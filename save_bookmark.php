<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in.',
            'redirect' => 'log.php'
        ]);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $tender_id = $_POST['id'] ?? '';
    $title = $_POST['tender_No'] ?? 'Untitled';
    $description = $_POST['description'] ?? 'No description available';
    $category = $_POST['category'] ?? 'Uncategorized';
    $department = $_POST['department'] ?? 'N/A';
    $province = $_POST['province'] ?? 'Unknown';
    $closing_date = $_POST['closing_Date'] ?? '';
    $advertised_date = $_POST['date_Published'] ?? '';
    $contact_person = $_POST['contactPerson'] ?? '';
    $contact_email = $_POST['email'] ?? '';
    $contact_phone = $_POST['telephone'] ?? '';
    $fax = $_POST['fax'] ?? '';
    $streetname = $_POST['streetname'] ?? '';
    $surburb = $_POST['surburb'] ?? '';
    $town = $_POST['town'] ?? '';
    $code = $_POST['code'] ?? '';
    $conditions = $_POST['conditions'] ?? '';
    $briefingSession = $_POST['briefingSession'] ?? '';
    $briefingVenue = $_POST['briefingVenue'] ?? '';
    $briefingCompulsory = $_POST['briefingCompulsory'] ?? '';
    $compulsory_briefing_session = $_POST['compulsory_briefing_session'] ?? '';
    $supportDocument = $_POST['supportDocument'] ?? null;
    if ($supportDocument) {
        // Optional: validate JSON
        $supportDocumentArray = json_decode($supportDocument, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // handle error
            $supportDocumentArray = null;
        }
    }
   
    $advertised_date = !empty($advertised_date) ? date('Y-m-d H:i:s', strtotime($advertised_date)) : null;
    $closing_date = !empty($closing_date) ? date('Y-m-d H:i:s', strtotime($closing_date)) : null;
    $compulsory_briefing_session = !empty($compulsory_briefing_session) ? date('Y-m-d H:i:s', strtotime($compulsory_briefing_session)) : null;

    if (empty($tender_id)) {
        echo json_encode(['success' => false, 'message' => 'Tender ID is required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO saved_tenders 
            (user_id,tender_id, tender_No, description, category, department, province, closing_Date, date_Published, contactPerson, email, telephone, fax, streetname, surburb, town, code, conditions, briefingSession, briefingVenue, briefingCompulsory,compulsory_briefing_session, supportDocument, saved_at) 
            VALUES (?,?,?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
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

        echo json_encode(['success' => true, 'message' => 'Tender bookmarked successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error saving tender: ' . $e->getMessage()]);
    }

    exit;
}

   // Format the dates properly
 // Format the dates properly with time
// $advertised_date = !empty($advertised_date) ? date('Y-m-d H:i:s', strtotime($advertised_date)) : null;
 //$closing_date = !empty($closing_date) ? date('Y-m-d H:i:s', strtotime($closing_date)) : null;
 
?>

<!-- Frontend UI for testing -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Save Tender Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="tender-card" 
     data-tender-id="12345"
     data-category="Infrastructure"
     data-location="Eastern Cape"
     data-department="Department of Transport"
     data-advertised-date="2025-04-14"
     data-closing-date="2025-04-20">
     
    <h3 class="tender-title">Road Infrastructure Upgrade</h3>
    <p class="tender-description">Upgrade of rural roads in district Z.</p>

    <div>Category: Infrastructure</div>
    <div>Location: Eastern Cape</div>
    <div>Department: Department of Transport</div>
    <div>Advertised: 14 April 2025</div>
    <div>Closing: 20 April 2025</div>

    <button class="save-tender-btn">Save Tender</button>
</div>

<script>
$(document).ready(function () {
    $('.save-tender-btn').on('click', function () {
        const card = $(this).closest('.tender-card');

        const data = {
            tender_id: card.data('tender-id'),
            title: card.find('.tender-title').text().trim(),
            description: card.find('.tender-description').text().trim(),
            category: card.data('category'),
            location: card.data('location'),
            department: card.data('department'),
            advertised_date: card.data('advertised-date'),
            closing_date: card.data('closing-date')
        };

        console.log('Sending data to save_tender.php:', data);

        $.post('save_tender.php', data, function (response) {
            if (response.success) {
                alert('Tender saved successfully!');
            } else {
                alert('Failed to save tender: ' + response.message);
                if (response.redirect) {
                    window.location.href = response.redirect;
                }
            }
        }, 'json');
    });
});
</script>

</body>
</html>
