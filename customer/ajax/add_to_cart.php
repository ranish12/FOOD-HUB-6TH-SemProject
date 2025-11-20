<?php
session_start();
require_once '../../config/database.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$menu_id = $input['menu_id'] ?? null;
$quantity = $input['quantity'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $menu_id) {
    try {
        // Get menu item details with stock check
        $stmt = $conn->prepare("SELECT * FROM Menu WHERE menu_id = ? AND is_deleted = FALSE");
        $stmt->execute([$menu_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            exit();
        }

        // Check if item is in stock
        if ($item['stock_quantity'] <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Item is out of stock']);
            exit();
        }

        // Calculate total quantity including cart
        $cart_quantity = isset($_SESSION['cart'][$menu_id]) ? $_SESSION['cart'][$menu_id]['quantity'] : 0;
        $total_quantity = $cart_quantity + $quantity;

        // Check if adding would exceed stock
        if ($total_quantity > $item['stock_quantity']) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Not enough stock. Only ' . ($item['stock_quantity'] - $cart_quantity) . ' items available.'
            ]);
            exit();
        }
        
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Add item to cart or update quantity
        if (isset($_SESSION['cart'][$menu_id])) {
            $_SESSION['cart'][$menu_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$menu_id] = [
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $quantity,
                'image_url' => $item['image_url']
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}
?> 