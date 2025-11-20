<?php
session_start();
require_once '../config/database.php';

// Handle remove item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $menu_id = $_POST['menu_id'];
    unset($_SESSION['cart'][$menu_id]);
    
    // If it's an AJAX request, return updated cart count
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $count = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $count += $item['quantity'];
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    }
}

// Calculate total
$total = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Food Hub</title>
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
        .cart-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .cart-item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .quantity-input {
            width: 70px;
            text-align: center;
        }
        .cart-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">Your Cart</h1>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="menu.php">Continue shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <?php foreach ($_SESSION['cart'] as $menu_id => $item): ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="cart-item-image"
                                         onerror="this.src='../assets/images/placeholder.jpg'">
                                </div>
                                <div class="col-md-4">
                                    <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="text-muted">Rs. <?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" 
                                           class="form-control quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           data-menu-id="<?php echo $menu_id; ?>"
                                           data-price="<?php echo $item['price']; ?>">
                                </div>
                                <div class="col-md-2">
                                    <p class="mb-0 item-total" data-menu-id="<?php echo $menu_id; ?>">
                                        Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    </p>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm remove-item" data-menu-id="<?php echo $menu_id; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="cart-summary">
                        <h4>Order Summary</h4>
                        <hr>
                        <!-- Stock Error Message -->
                        <div id="stockError" class="alert alert-danger d-none mb-3"></div>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Order Total:</strong>
                            <strong id="subtotal">Rs. <?php echo number_format($total, 2); ?></strong>
                        </div>
                        <button class="btn btn-primary w-100" onclick="proceedToCheckout()" id="checkoutBtn">
                            Proceed to Checkout
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to proceed to checkout
        async function proceedToCheckout() {
            <?php if (!isset($_SESSION['user_id'])): ?>
                // If not logged in, redirect to login with return URL
                window.location.href = 'login.php?redirect=checkout.php';
                return;
            <?php endif; ?>

            const stockError = document.getElementById('stockError');
            const checkoutBtn = document.getElementById('checkoutBtn');
            
            try {
                // Show loading state
                checkoutBtn.disabled = true;
                checkoutBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Checking stock...';
                stockError.classList.add('d-none');
                
                // Check stock availability
                const response = await fetch('ajax/check_stock.php');
                const data = await response.json();
                
                if (data.success) {
                    // Stock is available, proceed to checkout
                    window.location.href = 'checkout.php';
                } else {
                    // Show error message inline
                    stockError.classList.remove('d-none');
                    stockError.innerHTML = data.message.replace(/\n/g, '<br>');
                    
                    // Highlight items with insufficient stock
                    if (data.invalid_items) {
                        data.invalid_items.forEach(item => {
                            const itemRow = document.querySelector(`[data-menu-id="${item.menu_id}"]`);
                            if (itemRow) {
                                itemRow.classList.add('border-danger');
                            }
                        });
                    }
                    
                    // Restore button state
                    checkoutBtn.disabled = false;
                    checkoutBtn.innerHTML = 'Proceed to Checkout';
                }
            } catch (error) {
                console.error('Error:', error);
                stockError.classList.remove('d-none');
                stockError.textContent = 'Error checking stock availability. Please try again.';
                
                // Restore button state
                checkoutBtn.disabled = false;
                checkoutBtn.innerHTML = 'Proceed to Checkout';
            }
        }

        // Function to update cart totals
        function updateCartTotals(menuId, quantity) {
            fetch('ajax/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `menu_id=${menuId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update item total
                    const itemTotalElement = document.querySelector(`.item-total[data-menu-id="${menuId}"]`);
                    if (itemTotalElement) {
                        itemTotalElement.textContent = `Rs. ${data.item_total}`;
                    }
                    
                    // Update cart total
                    const subtotalElement = document.getElementById('subtotal');
                    if (subtotalElement) {
                        subtotalElement.textContent = `Rs. ${data.subtotal}`;
                    }
                    
                    // Update cart counter
                    getCartCount();

                    // If quantity is 0, remove the item from the cart
                    if (quantity === 0) {
                        const cartItem = document.querySelector(`.cart-item:has(input[data-menu-id="${menuId}"])`);
                        if (cartItem) {
                            cartItem.remove();
                        }
                    }
                } else {
                    // Show error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger alert-dismissible fade show mt-2';
                    errorDiv.innerHTML = `
                        ${data.message || 'Error updating cart'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    
                    // Find the cart item and append error
                    const cartItem = document.querySelector(`input[data-menu-id="${menuId}"]`).closest('.cart-item');
                    if (cartItem) {
                        // Remove any existing error messages
                        const existingError = cartItem.querySelector('.alert');
                        if (existingError) {
                            existingError.remove();
                        }
                        cartItem.appendChild(errorDiv);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger alert-dismissible fade show mt-2';
                errorDiv.innerHTML = `
                    An error occurred while updating the cart. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                // Find the cart item and append error
                const cartItem = document.querySelector(`input[data-menu-id="${menuId}"]`).closest('.cart-item');
                if (cartItem) {
                    // Remove any existing error messages
                    const existingError = cartItem.querySelector('.alert');
                    if (existingError) {
                        existingError.remove();
                    }
                    cartItem.appendChild(errorDiv);
                }
            });
        }

        // Function to update cart counter
        function updateCartCounter(count) {
            const counter = document.querySelector('.cart-counter');
            if (counter) {
                counter.textContent = count;
                counter.style.display = count > 0 ? 'inline' : 'none';
            }
        }

        // Function to get cart count
        function getCartCount() {
            fetch('ajax/get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCounter(data.count);
                }
            })
            .catch(error => console.error('Error getting cart count:', error));
        }

        // Function to handle quantity update
        function handleQuantityUpdate(input) {
            const menuId = input.dataset.menuId;
            const quantity = parseInt(input.value);
            
            if (quantity < 1) {
                input.value = 1;
                return;
            }
            
            updateCartTotals(menuId, quantity);
        }

        // Function to remove item
        function removeItem(menuId) {
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `remove_item=1&menu_id=${menuId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the cart item from DOM
                    const cartItem = document.querySelector(`.cart-item:has(button[data-menu-id="${menuId}"])`);
                    if (cartItem) {
                        cartItem.remove();
                    }
                    
                    // Update cart counter
                    updateCartCounter(data.count);
                    
                    // If cart is empty, show empty cart message
                    if (document.querySelectorAll('.cart-item').length === 0) {
                        location.reload();
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Initialize cart count and add event listeners when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize cart count
            getCartCount();
            
            // Add event listeners to remove buttons
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function() {
                    const menuId = this.dataset.menuId;
                    removeItem(menuId);
                });
            });
        });

        // Add event listeners to quantity inputs
        document.querySelectorAll('.quantity-input').forEach(input => {
            // Update on change (for mouse interaction)
            input.addEventListener('change', function() {
                handleQuantityUpdate(this);
            });

            // Update on blur (when user tabs out)
            input.addEventListener('blur', function() {
                handleQuantityUpdate(this);
            });
        });
    </script>
</body>
</html>