<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu_id']) && isset($_POST['quantity'])) {
    $menu_id = $_POST['menu_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if ($quantity > 0) {
        try {
            // Check if menu item exists in database
            $stmt = $conn->prepare("SELECT name, price, image_url FROM menu WHERE menu_id = ? AND is_available = 1 AND is_deleted = 0");
            $stmt->execute([$menu_id]);
            $menu_item = $stmt->fetch();
            
            if ($menu_item) {
                // Update or add item to cart
                $_SESSION['cart'][$menu_id] = [
                    'name' => $menu_item['name'],
                    'price' => $menu_item['price'],
                    'quantity' => $quantity,
                    'image_url' => $menu_item['image_url']
                ];
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Menu item not found']);
                exit();
            }
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit();
        }
    } else {
        // Remove item if quantity is 0
        unset($_SESSION['cart'][$menu_id]);
    }
    
    // Calculate new totals
    $subtotal = 0;
    $item_total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
    }
    $delivery_fee = 50;
    $grand_total = $subtotal + $delivery_fee;
    
    // Get item total if it exists
    if (isset($_SESSION['cart'][$menu_id])) {
        $item_total = $_SESSION['cart'][$menu_id]['price'] * $_SESSION['cart'][$menu_id]['quantity'];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'subtotal' => number_format($subtotal, 2),
        'item_total' => number_format($item_total, 2)
    ]);
    exit();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}
