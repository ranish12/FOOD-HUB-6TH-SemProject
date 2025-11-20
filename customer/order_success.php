<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submission for COD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method']) && $_POST['payment_method'] === 'cod') {
    // Verify cart exists
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        header('Location: cart.php');
        exit();
    }

    $delivery_address = $_POST['delivery_address'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Calculate total
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        $delivery_fee = 50;
        $grand_total = $total + $delivery_fee;
        
        // Create order
        $stmt = $conn->prepare(
            "INSERT INTO Orders (user_id, total_amount, delivery_address) 
            VALUES (?, ?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $grand_total, $delivery_address]);
        $order_id = $conn->lastInsertId();
        
        // Add order items
        foreach ($_SESSION['cart'] as $menu_id => $item) {
            $stmt = $conn->prepare(
                "INSERT INTO OrderItems (order_id, menu_id, quantity, price) 
                VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$order_id, $menu_id, $item['quantity'], $item['price']]);
        }
        
        // Create payment record
        $stmt = $conn->prepare(
            "INSERT INTO Payments (order_id, payment_method, payment_status, amount) 
            VALUES (?, 'COD', 'Pending', ?)"
        );
        $stmt->execute([$order_id, $grand_total]);
        
        // Commit transaction
        $conn->commit();
        
        // Clear cart
        unset($_SESSION['cart']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error creating order: " . $e->getMessage());
        header('Location: checkout.php?error=1');
        exit();
    }
}

// Get order details
$order_id = $_GET['order_id'] ?? $order_id ?? null;
if ($order_id) {
    $stmt = $conn->prepare(
        "SELECT o.*, p.payment_method 
        FROM Orders o 
        LEFT JOIN Payments p ON o.order_id = p.order_id 
        WHERE o.order_id = ? AND o.user_id = ?"
    );
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$order) {
    header('Location: index.php');
    exit();
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, m.name as item_name
    FROM OrderItems oi
    JOIN Menu m ON oi.menu_id = m.menu_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Food Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar {
            background: #2c3e50 !important;
        }
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: #FF6B00 !important;
            text-decoration: none;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .navbar-brand:hover {
            color: #FF8533 !important;
            transform: translateY(-1px);
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: #FF6B00 !important;
        }
        .success-icon {
            color: #28a745;
            font-size: 5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle success-icon"></i>
                        <h2 class="mb-4">Order Placed Successfully!</h2>
                        <?php if (isset($order)): ?>
                            <p>Order #<?php echo $order['order_id']; ?></p>
                            <p>Total Amount: Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
                            <p>Delivery Address: <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                        <?php endif; ?>
                        <div class="mt-4">
                            <h4>Order Items</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>Rs. <?php echo number_format($item['price'], 2); ?></td>
                                            <td>Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Return to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 