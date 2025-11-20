<?php
require_once '../config/database.php';

try {
    // First, add a test category if not exists
    $sql = "INSERT INTO categories (name, description) VALUES ('Test Category', 'Test Description')";
    $conn->exec($sql);
    $category_id = $conn->lastInsertId();
    
    // Add a test menu item if not exists
    $sql = "INSERT INTO menu (name, category_id, price, description) 
            VALUES ('Test Pizza', ?, 500.00, 'Test Pizza Description')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$category_id]);
    $menu_id = $conn->lastInsertId();
    
    // Now create the test order
    $sql = "INSERT INTO orders (user_id, total_amount, status, delivery_address) 
            VALUES (1, 1000.00, 'Pending', 'Test Address')";
    $conn->exec($sql);
    $order_id = $conn->lastInsertId();
    
    // Add order items
    $sql = "INSERT INTO orderitems (order_id, menu_id, quantity, price) 
            VALUES (?, ?, 2, 500.00)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$order_id, $menu_id]);
    
    echo "Test setup completed successfully!<br>";
    echo "Order ID: " . $order_id . "<br>";
    
    // Create a form to test eSewa payment
    echo "<form action='checkout.php' method='POST'>";
    echo "<input type='hidden' name='order_id' value='" . $order_id . "'>";
    echo "<button type='submit'>Proceed to Payment</button>";
    echo "</form>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
