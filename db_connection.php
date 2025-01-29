<?php
// Load the configuration file
$config = require 'config.php';

// Database connection details
$host = $config['db']['host'];
$port = $config['db']['port'];
$username = $config['db']['username'];
$password = $config['db']['password'];
$dbname = $config['db']['dbname'];

// Create a connection
$dsn = "mysql:host=$host;port=$port;dbname=$dbname";
try {
    // Create a PDO object for connection
    $pdo = new PDO($dsn, $username, $password);
    // Set error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Failed to connect to the database: " . $e->getMessage();
    exit();
}