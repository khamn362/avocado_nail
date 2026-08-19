<?php
$host = 'localhost';
$dbname = 'nail_salon';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "<br>Make sure MySQL is running and the 'nail_salon' database exists. Import database.sql first.");
}

function getConnection() {
    global $pdo;
    return $pdo;
}
