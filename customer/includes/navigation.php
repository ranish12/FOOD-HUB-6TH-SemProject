<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="menu.php">
            <i class="fas fa-utensils"></i> Food Hub
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'menu.php' ? 'active' : ''; ?>" href="menu.php">
                        <i class="fas fa-utensils"></i> Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'cart.php' ? 'active' : ''; ?>" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <span class="cart-counter badge rounded-pill bg-danger">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>" href="about.php">
                        <i class="fas fa-info-circle"></i> About Us
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>" href="contact.php">
                        <i class="fas fa-envelope"></i> Contact
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1): ?>
                                <li>
                                    <a class="dropdown-item" href="../admin/dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li>
                                <a class="dropdown-item" href="orders.php">
                                    <i class="fas fa-list"></i> My Orders
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>" href="register.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar {
    background: #2c3e50 !important;
    padding: 0.75rem 0;
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
    padding: 0.5rem 1rem !important;
}
.nav-link:hover {
    color: #FF6B00 !important;
}
.nav-link.active {
    color: #FF6B00 !important;
    font-weight: 600;
}
.nav-link i {
    margin-right: 0.5rem;
}
.cart-counter {
    position: relative;
    top: -8px;
    left: -5px;
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}
.dropdown-menu {
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    padding: 0.5rem;
}
.dropdown-item {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}
.dropdown-item:hover {
    background: rgba(255, 107, 0, 0.1);
    color: #FF6B00;
}
.dropdown-item i {
    width: 20px;
    text-align: center;
    margin-right: 0.5rem;
}
@media (max-width: 991.98px) {
    .navbar-collapse {
        background: #2c3e50;
        padding: 1rem;
        border-radius: 10px;
        margin-top: 1rem;
    }
    .navbar-nav {
        margin-bottom: 1rem;
    }
}
</style> 