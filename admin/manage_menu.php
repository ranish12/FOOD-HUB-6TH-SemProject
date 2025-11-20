<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header('Location: ../customer/login.php');
    exit();
}

// Handle form submission for updating stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $menu_id = $_POST['menu_id'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get current stock with lock
        $stmt = $conn->prepare("SELECT stock_quantity FROM Menu WHERE menu_id = ? FOR UPDATE");
        $stmt->execute([$menu_id]);
        $current_stock = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current_stock) {
            throw new Exception("Menu item not found");
        }
        
        // Update stock
        $stmt = $conn->prepare("UPDATE Menu SET stock_quantity = ? WHERE menu_id = ?");
        $result = $stmt->execute([$stock_quantity, $menu_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Failed to update stock");
        }
        
        // Log the stock change
        error_log("Admin stock update - Menu ID: {$menu_id}, Old stock: {$current_stock['stock_quantity']}, New stock: {$stock_quantity}");
        
        // Commit transaction
        $conn->commit();
        $success_message = "Stock updated successfully from {$current_stock['stock_quantity']} to {$stock_quantity}!";
        
    } catch(Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error_message = "Error updating stock: " . $e->getMessage();
        error_log("Admin stock update failed - " . $e->getMessage());
    }
}

// Get all menu items
try {
    $stmt = $conn->query("
        SELECT m.menu_id, m.name, m.price, m.stock_quantity, m.is_deleted, 
               c.name as category_name, m.description, m.image_url
        FROM Menu m 
        LEFT JOIN Categories c ON m.category_id = c.category_id 
        ORDER BY m.name
    ");
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching menu items: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu - Food Hub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stock-warning { color: #dc3545; }
        .stock-low { color: #ffc107; }
        .stock-ok { color: #198754; }
    </style>
</head>
<body>
    <?php include 'includes/admin_navigation.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Manage Menu Items</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menu_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td>Rs. <?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <span class="<?php 
                                    if ($item['stock_quantity'] <= 0) echo 'stock-warning';
                                    elseif ($item['stock_quantity'] <= 5) echo 'stock-low';
                                    else echo 'stock-ok';
                                ?>">
                                    <?php echo $item['stock_quantity']; ?> items
                                </span>
                            </td>
                            <td>
                                <?php if ($item['is_deleted']): ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-primary btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#stockModal<?php echo $item['menu_id']; ?>">
                                    Update Stock
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Stock Update Modal -->
                        <div class="modal fade" id="stockModal<?php echo $item['menu_id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Stock - <?php echo htmlspecialchars($item['name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="menu_id" value="<?php echo $item['menu_id']; ?>">
                                            <div class="mb-3">
                                                <label for="stock<?php echo $item['menu_id']; ?>" class="form-label">Stock Quantity</label>
                                                <input type="number" 
                                                       class="form-control" 
                                                       id="stock<?php echo $item['menu_id']; ?>" 
                                                       name="stock_quantity"
                                                       value="<?php echo $item['stock_quantity']; ?>"
                                                       min="0"
                                                       required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" name="update_stock" class="btn btn-primary">Update Stock</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
