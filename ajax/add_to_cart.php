<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $menu_id = $_POST['id'];
    
    try {
        // Get menu item details
        $stmt = $conn->prepare("SELECT * FROM Menu WHERE menu_id = ? AND is_available = 1");
        $stmt->execute([$menu_id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Item not found or not available']);
            exit();
        }
        
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Add item to cart or update quantity
        if (isset($_SESSION['cart'][$menu_id])) {
            $_SESSION['cart'][$menu_id]['quantity']++;
        } else {
            $_SESSION['cart'][$menu_id] = [
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => 1,
                'image_url' => $item['image_url']
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
} 