<?php
// This is the shared navigation for admin pages
require_once '../config/database.php';

// Get count of new/unread orders
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$result = $stmt->fetch();
$newOrders = $result['total'];
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Food Hub Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="categories.php">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="orders.php">
                        <i class="fas fa-shopping-cart"></i> Orders
                        <?php if ($newOrders > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?php echo $newOrders; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_menu.php">
                        <i class="fas fa-box"></i> Stock Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sales_report.php">
                        <i class="fas fa-chart-bar"></i> Sales Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about_contact.php">
                        <i class="fas fa-info-circle"></i> About & Contact
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
