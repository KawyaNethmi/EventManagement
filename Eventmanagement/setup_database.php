<?php
// setup_database.php
$host = 'localhost';
$dbname = 'event_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    // Run the SQL from above
    $sql = file_get_contents('database_setup.sql'); // Save the SQL above in a file
    $pdo->exec($sql);
    
    echo "Database setup successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>