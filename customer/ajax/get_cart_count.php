<?php
session_start();
require_once '../../config/database.php';

// Initialize count
$count = 0;

// Calculate total items in cart for both logged-in and non-logged-in users
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'count' => $count]);
?>