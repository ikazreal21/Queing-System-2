<?php
date_default_timezone_set('Asia/Manila');

// Database credentials
$host = '192.168.3.5';
$dbname = 'queue';
$username = 'cbadmin';
$password = '%rga8477#KC86&';

// DSN
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

try {
    $pdo = new PDO($dsn, $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 🔥 THIS IS THE MISSING FIX
    $pdo->exec("SET time_zone = '+08:00'");

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>
