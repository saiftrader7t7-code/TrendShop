<?php
// /common/config.php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- DATABASE CREDENTIALS ---
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'quick_kart_db');

// --- DATABASE CONNECTION ---
// Suppress the initial connection warning to handle it manually.
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check for connection errors, especially if the database doesn't exist yet.
if ($conn->connect_error) {
    // Check if the specific error is "Unknown database" and not on install page
    if ($conn->connect_errno === 1049 && basename($_SERVER['PHP_SELF']) != 'install.php') {
        die("
            <div style='font-family: Arial, sans-serif; text-align: center; padding: 50px;'>
                <h1>Database Not Found</h1>
                <p>The application is not installed correctly. Please run the installer.</p>
                <a href='install.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>
                    Run Installer
                </a>
            </div>
        ");
    } elseif (basename($_SERVER['PHP_SELF']) != 'install.php') {
        // For any other connection error
        die("Connection failed: " . $conn->connect_error);
    }
}

// Set charset if connection is successful
if ($conn && !$conn->connect_error) {
    $conn->set_charset("utf8mb4");
}

// --- BASE URL ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
// Define the base URL for the application
define('BASE_URL', $protocol . '://' . $host . $base_path);
?>