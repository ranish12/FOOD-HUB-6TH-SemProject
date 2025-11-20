<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header('Location: ../login.php');
    exit();
}

// Handle menu item operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_item':
                $name = $_POST['name'];
                $category_id = $_POST['category_id'];
                $price = $_POST['price'];
                $description = $_POST['description'];
                $image_url = $_POST['image_url'];
                $is_available = isset($_POST['is_available']) ? 1 : 0;
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO Menu (name, category_id, price, description, image_url, is_available, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $category_id, $price, $description, $image_url, $is_available, $is_featured])) {
                    $success = "Menu item added successfully!";
                } else {
                    $error = "Failed to add menu item.";
                }
                break;
                
            case 'edit_item':
                $menu_id = $_POST['menu_id'];
                $name = $_POST['name'];
                $category_id = $_POST['category_id'];
                $price = $_POST['price'];
                $description = $_POST['description'];
                $image_url = $_POST['image_url'];
                $is_available = isset($_POST['is_available']) ? 1 : 0;
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                
                $stmt = $conn->prepare("UPDATE Menu SET name = ?, category_id = ?, price = ?, description = ?, image_url = ?, is_available = ?, is_featured = ? WHERE menu_id = ?");
                if ($stmt->execute([$name, $category_id, $price, $description, $image_url, $is_available, $is_featured, $menu_id])) {
                    $success = "Menu item updated successfully!";
                } else {
                    $error = "Failed to update menu item.";
                }
                break;
                
            case 'delete_item':
                $menu_id = $_POST['menu_id'];
                $stmt = $conn->prepare("UPDATE Menu SET is_deleted = TRUE WHERE menu_id = ?");
                if ($stmt->execute([$menu_id])) {
                    header('Location: dashboard.php?success=menu_deleted');
                } else {
                    header('Location: dashboard.php?error=delete_failed');
                }
                exit();
                break;
        }
    }
}

// Get menu items with categories
$stmt = $conn->query("
    SELECT m.*, c.name as category_name 
    FROM Menu m 
    LEFT JOIN Categories c ON m.category_id = c.category_id 
    WHERE m.is_deleted = FALSE
    ORDER BY c.name, m.name
");
$menu_items = $stmt->fetchAll();

// Get categories for the dropdown
$stmt = $conn->query("SELECT * FROM Categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get daily sales summary
$stmt = $conn->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales
    FROM Orders 
    WHERE status != 'Cancelled'
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$daily_sales = $stmt->fetchAll();

// Get monthly sales summary
$stmt = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales
    FROM Orders 
    WHERE status != 'Cancelled'
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
");
$monthly_sales = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Food Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar .active {
            background-color: #0d6efd;
        }
        .main-content {
            padding: 20px;
        }
        .menu-item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        .nav-link {
            display: flex;
            align-items: center;
        }
        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .sales-card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .sales-card .card-body {
            padding: 20px;
        }
        .sales-card .card-title {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .sales-card .card-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #343a40;
        }
        .menu-section {
            margin-top: 30px;
        }
        .menu-item-card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_navigation.php'; ?>
    <div class="container">
        <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php
                        switch ($_GET['success']) {
                            case 'menu_updated':
                                echo "Menu item updated successfully!";
                                break;
                            case 'menu_added':
                                echo "Menu item added successfully!";
                                break;
                            case 'menu_deleted':
                                echo "Menu item deleted successfully!";
                                break;
                            default:
                                echo "Operation completed successfully!";
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php
                        switch ($_GET['error']) {
                            case 'invalid_image':
                                echo "Invalid image format. Please upload JPG, JPEG, PNG, or GIF.";
                                break;
                            case 'upload_failed':
                                echo "Failed to upload image. Please try again.";
                                break;
                            case 'update_failed':
                                echo "Failed to update menu item. Please try again.";
                                break;
                            case 'database_error':
                                echo "Database error occurred. Please try again.";
                                break;
                            default:
                                echo "An error occurred. Please try again.";
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Sales Summary -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card sales-card">
                            <div class="card-body">
                                <h5 class="card-title">Today's Sales</h5>
                                <div class="card-value">
                                    <?php
                                    $today_sales = 0;
                                    foreach ($daily_sales as $sale) {
                                        if ($sale['date'] == date('Y-m-d')) {
                                            $today_sales = $sale['total_sales'];
                                            break;
                                        }
                                    }
                                    echo 'Rs. ' . number_format($today_sales, 2);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card sales-card">
                            <div class="card-body">
                                <h5 class="card-title">This Month's Sales</h5>
                                <div class="card-value">
                                    <?php
                                    $month_sales = 0;
                                    foreach ($monthly_sales as $sale) {
                                        if ($sale['month'] == date('Y-m')) {
                                            $month_sales = $sale['total_sales'];
                                            break;
                                        }
                                    }
                                    echo 'Rs. ' . number_format($month_sales, 2);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card sales-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Orders Today</h5>
                                <div class="card-value">
                                    <?php
                                    $today_orders = 0;
                                    foreach ($daily_sales as $sale) {
                                        if ($sale['date'] == date('Y-m-d')) {
                                            $today_orders = $sale['total_orders'];
                                            break;
                                        }
                                    }
                                    echo $today_orders;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Charts -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Daily Sales (Last 7 Days)</h5>
                                <canvas id="dailySalesChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Monthly Sales (Last 12 Months)</h5>
                                <canvas id="monthlySalesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Menu Management Section -->
                <div class="menu-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Menu Management</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="fas fa-plus me-2"></i>Add New Item
                        </button>
                    </div>

                    <!-- Menu Items Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Featured</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menu_items as $item): ?>
                                <tr>
                                    <td>
                                        <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="menu-item-image"
                                             onerror="this.src='../assets/images/placeholder.jpg'">
                                    </td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                    <td>Rs. <?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $item['is_available'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $item['is_available'] ? 'Available' : 'Not Available'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $item['is_featured'] ? 'bg-primary' : 'bg-secondary'; ?>">
                                            <?php echo $item['is_featured'] ? 'Featured' : 'Not Featured'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-item" 
                                                data-id="<?php echo $item['menu_id']; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editMenuItemModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-item"
                                                data-id="<?php echo $item['menu_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" action="add_menu_item.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (Rs.)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                            <small class="text-muted">Select an image from your computer</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_available" name="is_available" checked>
                                <label class="form-check-label" for="is_available">Available</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured">
                                <label class="form-check-label" for="is_featured">Featured</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Menu Item Modal -->
    <div class="modal fade" id="editMenuItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="edit_menu_item.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="menu_id" id="edit_menu_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_category" class="form-label">Category</label>
                                    <select class="form-select" id="edit_category" name="category_id" required>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_price" class="form-label">Price</label>
                                    <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_image" class="form-label">Image</label>
                                    <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                                    <small class="text-muted">Leave empty to keep current image</small>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_is_available" name="is_available">
                                        <label class="form-check-label" for="edit_is_available">Available</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_is_featured" name="is_featured">
                                        <label class="form-check-label" for="edit_is_featured">Featured</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare data for charts
        const dailySalesData = <?php echo json_encode(array_reverse($daily_sales)); ?>;
        const monthlySalesData = <?php echo json_encode(array_reverse($monthly_sales)); ?>;

        // Daily Sales Chart
        new Chart(document.getElementById('dailySalesChart'), {
            type: 'line',
            data: {
                labels: dailySalesData.map(item => item.date),
                datasets: [{
                    label: 'Daily Sales (Rs.)',
                    data: dailySalesData.map(item => item.total_sales),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rs. ' + value;
                            }
                        }
                    }
                }
            }
        });

        // Monthly Sales Chart
        new Chart(document.getElementById('monthlySalesChart'), {
            type: 'bar',
            data: {
                labels: monthlySalesData.map(item => item.month),
                datasets: [{
                    label: 'Monthly Sales (Rs.)',
                    data: monthlySalesData.map(item => item.total_sales),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rs. ' + value;
                            }
                        }
                    }
                }
            }
        });

        // Function to load menu item data for editing
        function loadMenuItemData(menuId) {
            fetch(`get_menu_item.php?id=${menuId}`)
                .then(response => response.json())
                .then(item => {
                    document.getElementById('edit_menu_id').value = item.menu_id;
                    document.getElementById('edit_name').value = item.name;
                    document.getElementById('edit_category').value = item.category_id;
                    document.getElementById('edit_price').value = item.price;
                    document.getElementById('edit_description').value = item.description;
                    document.getElementById('edit_is_available').checked = item.is_available == 1;
                    document.getElementById('edit_is_featured').checked = item.is_featured == 1;
                })
                .catch(error => {
                    console.error('Error loading menu item:', error);
                    alert('Error loading menu item data');
                });
        }

        // Add event listeners to edit buttons
        document.querySelectorAll('.edit-item').forEach(button => {
            button.addEventListener('click', function() {
                const menuId = this.dataset.id;
                loadMenuItemData(menuId);
            });
        });

        // Add event listeners to delete buttons
        document.querySelectorAll('.delete-item').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm(`Are you sure you want to delete "${this.dataset.name}"?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'dashboard.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_item';
                    
                    const menuIdInput = document.createElement('input');
                    menuIdInput.type = 'hidden';
                    menuIdInput.name = 'menu_id';
                    menuIdInput.value = this.dataset.id;
                    
                    form.appendChild(actionInput);
                    form.appendChild(menuIdInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html> 