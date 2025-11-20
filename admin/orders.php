<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    try {
        if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
            throw new Exception('Missing order ID or status');
        }
        
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];
        
        // Validate status
        $valid_statuses = ['Pending', 'Processing', 'Completed', 'Cancelled', 'Delivered'];
        if (!in_array($status, $valid_statuses)) {
            throw new Exception('Invalid status value');
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        error_log("Starting status update transaction for Order #{$order_id} to status: {$status}");
        
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET status = :status WHERE order_id = :order_id");
        $params = [':status' => $status, ':order_id' => $order_id];
        
        error_log("Executing order status update with params: " . print_r($params, true));
        
        if (!$stmt->execute($params)) {
            $error = $stmt->errorInfo();
            error_log("Failed to update order status: " . print_r($error, true));
            throw new Exception('Failed to update order status in database: ' . $error[2]);
        }
        
        // Verify the update
        $stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Status after update: " . print_r($updated, true));
        
        // If status is updated successfully, also update payment status for COD orders
        if ($status === 'Delivered') {
            error_log("Updating payment status for delivered order: {$order_id}");
            
            // First check if this is a COD order
            $stmt = $conn->prepare("SELECT payment_method FROM payments WHERE order_id = :order_id");
            $stmt->execute([':order_id' => $order_id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Payment method for order {$order_id}: " . print_r($payment, true));
            
            if ($payment && strtolower($payment['payment_method']) === 'cod') {
                error_log("Updating COD payment status to Completed");
                $stmt = $conn->prepare("UPDATE payments 
                                      SET payment_status = 'Completed' 
                                      WHERE order_id = :order_id 
                                      AND LOWER(payment_method) = 'cod'");
                if (!$stmt->execute([':order_id' => $order_id])) {
                    $error = $stmt->errorInfo();
                    error_log("Failed to update payment status: " . print_r($error, true));
                    throw new Exception('Failed to update payment status');
                }
                error_log("Payment status updated successfully");
            } else {
                error_log("Not a COD order, skipping payment status update");
            }
        }
        
        $conn->commit();
        $success = "Order #{$order_id} status updated to {$status} successfully!";
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = "Error updating order status: " . $e->getMessage();
        error_log("Admin order status update error: " . $e->getMessage());
    }
}

// Get all orders with user details
$stmt = $conn->query("
    SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
           p.payment_method, p.payment_status
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN payments p ON o.order_id = p.order_id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug current order statuses
foreach ($orders as $order) {
    error_log("Order #{$order['order_id']} - Status: {$order['status']}, Payment Method: {$order['payment_method']}, Payment Status: {$order['payment_status']}");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Food Hub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar a.active {
            background-color: #007bff;
        }
        .main-content {
            padding: 20px;
        }
        .order-card {
            margin-bottom: 20px;
        }
        .status-badge {
            font-size: 0.9em;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_navigation.php'; ?>
    <div class="container">
        <!-- Main Content -->
        <div class="col-md-10 main-content">
            <h2 class="mb-4">Order Management</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Orders List -->
            <div class="row">
                <?php foreach ($orders as $order): ?>
                <div class="col-md-6">
                    <div class="card order-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Order #<?php echo $order['order_id']; ?></h5>
                            <span class="badge bg-<?php 
                                echo match($order['status']) {
                                    'Pending' => 'warning',
                                    'Preparing' => 'info',
                                    'Ready' => 'primary',
                                    'Delivered' => 'success',
                                    'Cancelled' => 'danger',
                                    default => 'secondary'
                                };
                            ?> status-badge">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Order Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                                    <p><strong>Total Amount:</strong> Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
                                    <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <?php
                            $stmt = $conn->prepare("
                                SELECT oi.*, m.name as item_name
                                FROM orderitems oi
                                JOIN menu m ON oi.menu_id = m.menu_id
                                WHERE oi.order_id = ?
                            ");
                            $stmt->execute([$order['order_id']]);
                            $items = $stmt->fetchAll();
                            ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
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

                            <!-- Status Update Form -->
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <div class="row">
                                    <div class="col-md-8">
                                        <select name="status" class="form-select">
                                            <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="Completed" <?php echo $order['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Delivered" <?php echo $order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary w-100">Update Status</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>