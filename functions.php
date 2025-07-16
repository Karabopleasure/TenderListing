<?php
error_reporting(E_ALL);

// Display errors to the browser
ini_set('display_errors', 1);

// functions.php

// Fetch all tenders based on search and filter
function fetchTenders($pdo, $keyword = '', $category_id = '', $location = '') {
    // Base query
    $query = "SELECT tenders.*, categories.name AS category_name 
              FROM tenders
              JOIN categories ON tenders.category_id = categories.id
              WHERE 1=1";  // This ensures that we can easily append conditions

    // Apply filters if provided
    if ($keyword) {
        $query .= " AND (tenders.title LIKE :keyword OR tenders.description LIKE :keyword)";
    }

    if ($category_id) {
        $query .= " AND tenders.category_id = :category_id";
    }

    if ($location) {
        $query .= " AND tenders.location LIKE :location";
    }

    // Order tenders by deadline (ascending)
    $query .= " ORDER BY tenders.deadline ASC";

    // Prepare the statement
    $stmt = $pdo->prepare($query);

    // Bind values for filters
    if ($keyword) {
        $stmt->bindValue(':keyword', "%$keyword%");
    }
    if ($category_id) {
        $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
    }
    if ($location) {
        $stmt->bindValue(':location', "%$location%");
    }

    // Execute the query
    $stmt->execute();

    // Fetch and return the results
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch a single tender by its ID
function fetchTenderById($pdo, $id) {
    $query = "SELECT tenders.*, categories.name AS category_name 
              FROM tenders 
              JOIN categories ON tenders.category_id = categories.id
              WHERE tenders.id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
