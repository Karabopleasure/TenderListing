<?php
header('Content-Type: application/json');

try {
    $outputFile = __DIR__ . '/tender.json';
    $hasUpdates = false;
    
    // Check if tender file exists and was updated recently
    if (file_exists($outputFile)) {
        $lastModified = filemtime($outputFile);
        $lastCheck = $_SESSION['last_tender_check'] ?? time();
        
        if ($lastModified > $lastCheck) {
            $hasUpdates = true;
        }
        
        $_SESSION['last_tender_check'] = time();
    }
    
    echo json_encode(['hasUpdates' => $hasUpdates]);
    
} catch (Exception $e) {
    echo json_encode(['hasUpdates' => false, 'error' => 'Check failed']);
}
?>