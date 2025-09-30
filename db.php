<?php
// Database credentials
// $host = 'localhost'; // Replace with your database host (e.g., 'localhost' or IP address)
$host = '192.168.3.5'; // Replace with your database host (e.g., 'localhost' or IP address)
$dbname = 'queue'; // Your database name
$username = 'cbadmin'; // Your database username
$password = '%rga8477#KC86&'; // Your database password (empty if no password)

// Set up the DSN (Data Source Name) for PDO
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

try {
    // Create a PDO instance
    $pdo = new PDO($dsn, $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Uncomment the next line for debugging purposes (optional)
    // echo "Connected to the database successfully!";
} catch (PDOException $e) {
    // Catch any exceptions and display the error message
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>
