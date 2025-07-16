<?php
// db_connect.php

$host = 'localhost'; // Database host
$dbname = 'tender'; // Database name
$username = 'root'; // Database username (update if necessary)
$password = ''; // Database password (update if necessary)

try {
    // Create a PDO instance and establish the connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optional: Set the charset for the connection
    $pdo->exec("SET NAMES 'utf8'");
    
} catch (PDOException $e) {
    // In case of an error, display the message
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>
