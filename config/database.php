<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // First connect without database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS food_hub");
    
    // Now connect to the database
    $conn = new PDO("mysql:host=$host;dbname=food_hub", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // If connection fails, show error message
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// Function to get all food items
function getAllFoodItems($conn) {
    $stmt = $conn->prepare("SELECT * FROM items WHERE is_available = TRUE ORDER BY category, name");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Function to get food items by category
function getFoodItemsByCategory($conn, $category) {
    $stmt = $conn->prepare("SELECT * FROM items WHERE category = ? AND is_available = TRUE ORDER BY name");
    $stmt->execute([$category]);
    return $stmt->fetchAll();
}

// Function to get food item by ID
function getFoodItemById($conn, $item_id) {
    $stmt = $conn->prepare("SELECT * FROM items WHERE item_id = ?");
    $stmt->execute([$item_id]);
    return $stmt->fetch();
}

// Function to create a new order
function createOrder($conn, $user_id, $total_amount) {
    $stmt = $conn->prepare("INSERT INTO Orders (user_id, total_amount) VALUES (?, ?)");
    $stmt->execute([$user_id, $total_amount]);
    return $conn->lastInsertId();
}

// Function to add items to an order
function addOrderItems($conn, $order_id, $item_id, $quantity, $item_price) {
    $stmt = $conn->prepare("INSERT INTO OrderItems (order_id, item_id, quantity, item_price) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$order_id, $item_id, $quantity, $item_price]);
}

// Function to create a payment record
function createPayment($conn, $order_id, $payment_method, $amount) {
    $stmt = $conn->prepare("INSERT INTO Payments (order_id, payment_method, payment_status, payment_time, amount) 
                           VALUES (?, ?, 'Pending', NOW(), ?)");
    return $stmt->execute([$order_id, $payment_method, $amount]);
}

// Function to update order status
function updateOrderStatus($conn, $order_id, $status) {
    $stmt = $conn->prepare("UPDATE Orders SET order_status = ? WHERE order_id = ?");
    return $stmt->execute([$status, $order_id]);
}

// Function to update payment status
function updatePaymentStatus($conn, $payment_id, $status) {
    $stmt = $conn->prepare("UPDATE Payments SET payment_status = ? WHERE payment_id = ?");
    return $stmt->execute([$status, $payment_id]);
}
?> 