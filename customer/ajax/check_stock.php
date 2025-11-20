<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Your cart is empty.'
    ]);
    exit();
}

try {
    $invalid_items = [];
    
    // Check stock for each item
    foreach ($_SESSION['cart'] as $menu_id => $item) {
        $stmt = $conn->prepare("SELECT name, stock_quantity FROM Menu WHERE menu_id = ?");
        $stmt->execute([$menu_id]);
        $menu_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$menu_item || $menu_item['stock_quantity'] < $item['quantity']) {
            $invalid_items[] = [
                'menu_id' => $menu_id,
                'name' => $menu_item ? $menu_item['name'] : 'Unknown item',
                'requested' => $item['quantity'],
                'available' => $menu_item ? $menu_item['stock_quantity'] : 0
            ];
        }
    }
    
    if (!empty($invalid_items)) {
        $message = "The following items are not available in the requested quantity:\n";
        foreach ($invalid_items as $item) {
            $message .= "- {$item['name']}: Requested: {$item['requested']}, Available: {$item['available']}\n";
        }
        
        echo json_encode([
            'success' => false,
            'message' => $message,
            'invalid_items' => $invalid_items
        ]);
    } else {
        echo json_encode([
            'success' => true
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking stock availability.'
    ]);
}
?>
