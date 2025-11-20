<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user's orders with payment information
$stmt = $conn->prepare("
    SELECT o.*, p.payment_method, p.payment_status,
           GROUP_CONCAT(CONCAT(m.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
    FROM Orders o
    LEFT JOIN Payments p ON o.order_id = p.order_id
    LEFT JOIN OrderItems oi ON o.order_id = oi.order_id
    LEFT JOIN Menu m ON oi.menu_id = m.menu_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Food Hub</title>
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
        .nav-link.active {
            color: #FF6B00 !important;
            font-weight: 600;
        }
        .order-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .order-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .order-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            border-bottom: 1px solid #ddd;
        }
        .order-body {
            padding: 15px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .status-paid {
            background-color: #28a745;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: black;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">My Orders</h1>
        
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                You haven't placed any orders yet.
                <a href="menu.php" class="alert-link ms-2">Browse our menu</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">Order #<?php echo $order['order_id']; ?></h5>
                                <small class="text-muted">
                                    <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?>
                                </small>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="status-badge <?php echo 'status-' . strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>Order Items:</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($order['items']); ?></p>
                                
                                <h6>Delivery Address:</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($order['delivery_address']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        
                                
                                        <p class="mb-0">
                                            <strong>Total Amount:</strong> 
                                            Rs. <?php echo number_format($order['total_amount'], 2); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 