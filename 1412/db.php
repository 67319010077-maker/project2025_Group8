<?php
// Database Configuration
$host = 'localhost';
$dbname = 'food_shop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if admin is logged in
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}

// Helper function to check if customer is logged in
function isCustomerLoggedIn()
{
    return isset($_SESSION['customer_id']);
}

// Helper function to get current customer
function getCurrentCustomer()
{
    return $_SESSION['customer_name'] ?? null;
}

// Helper function to redirect
function redirect($url)
{
    header("Location: $url");
    exit;
}
?>