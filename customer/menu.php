<?php
session_start();
require_once '../config/database.php';

// Get categories
$stmt = $conn->query("SELECT * FROM Categories ORDER BY name");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Food Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .content-wrapper {
            margin-top: 20px;
        }
        .search-filter-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .search-box {
            width: 100%;
            transition: all 0.3s ease;
            margin-bottom: 0;
        }
        .search-box .input-group {
            width: 100%;
        }
        .search-box .form-control {
            border-radius: 8px 0 0 8px;
            border: 1px solid #e0e0e0;
            padding: 10px 15px;
            height: 42px;
            font-size: 0.95rem;
            box-shadow: none;
        }
        .search-box .btn {
            border-radius: 0 8px 8px 0;
            padding: 10px 20px;
        }
        .category-filter {
            width: 100%;
        }
        .category-filter .form-select {
            height: 42px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 10px 15px;
            font-size: 0.95rem;
            background-color: white;
        }
        .menu-item {
            transition: transform 0.2s;
        }
        .menu-item:hover {
            transform: translateY(-5px);
        }
        .menu-item-image {
            height: 200px;
            object-fit: cover;
        }
        @media (max-width: 768px) {
            .search-box, .category-filter {
                max-width: 100%;
                margin: 0.5rem 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <!-- Main Content -->
    <!-- Toast message for cart notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="cartToast" class="toast" role="alert">
            <div class="toast-body d-flex align-items-center">
                <i class="fas fa-check-circle text-success me-2"></i>
                <span id="cartToastMessage"></span>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="container">
            <!-- Search and Filter Section -->
            <div class="search-filter-container">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="search-box">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search menu...">
                                <button class="btn btn-primary" type="button" id="searchButton">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="category-filter">
                            <select class="form-select" id="categoryFilter">
                                <option value="all">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Products Section -->
            <?php include 'includes/featured_products.php'; ?>

            <!-- Menu Items Grid -->
            <div class="row" id="menuItems">
                <!-- Menu items will be loaded here via AJAX -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to load menu items
        function loadMenuItems() {
            const category = document.getElementById('categoryFilter').value;
            const search = document.getElementById('searchInput').value;
            
            fetch(`ajax/get_menu_items.php?category=${category}&search=${search}`)
                .then(response => response.json())
                .then(items => {
                    const menuItemsContainer = document.getElementById('menuItems');
                    menuItemsContainer.innerHTML = '';
                    
                    if (!items || items.length === 0) {
                        menuItemsContainer.innerHTML = `
                            <div class="col-12 text-center">
                                <p class="text-muted">No menu items found.</p>
                            </div>
                        `;
                        return;
                    }
                    
                    items.forEach(item => {
                        menuItemsContainer.innerHTML += `
                            <div class="col-md-4 mb-4">
                                <div class="card menu-item h-100">
                                    <img src="../${item.image_url}" class="card-img-top menu-item-image" alt="${item.name}" onerror="this.src='../assets/images/placeholder.jpg'">
                                    <div class="card-body">
                                        <h5 class="card-title">${item.name}</h5>
                                        <p class="card-text">${item.description}</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="h5 mb-0">Rs. ${item.price}</span>
                                            ${item.stock_quantity > 0 ? `
                                                <button class="btn btn-primary add-to-cart" data-id="${item.menu_id}">
                                                    <i class="fas fa-plus"></i> Add to Cart
                                                </button>
                                            ` : `
                                                <button class="btn btn-secondary" disabled>
                                                    <i class="fas fa-times"></i> Out of Stock
                                                </button>
                                            `}
                                        </div>
                                        ${item.stock_quantity > 0 && item.stock_quantity <= 5 ? `
                                            <small class="text-warning mt-2 d-block">
                                                <i class="fas fa-exclamation-triangle"></i> Only ${item.stock_quantity} left
                                            </small>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    // Add event listeners to cart buttons
                    document.querySelectorAll('.add-to-cart').forEach(button => {
                        button.addEventListener('click', function() {
                            const menuId = this.dataset.id;
                            addToCart(menuId);
                        });
                    });
                })
                .catch(error => {
                    console.error('Error loading menu items:', error);
                });
        }

        // Function to show toast message
        function showToast(message) {
            const toast = document.getElementById('cartToast');
            const toastMessage = document.getElementById('cartToastMessage');
            toastMessage.textContent = message;
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
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

        // Function to add item to cart
        function addToCart(menuId) {
            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    menu_id: menuId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message using toast
                    showToast('Item added to cart!');
                    // Update cart counter
                    getCartCount();
                } else if (data.requires_login) {
                    // Redirect to login if required
                    window.location.href = 'login.php?redirect=cart.php';
                } else {
                    showToast(data.message || 'Error adding item to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error adding item to cart');
            });
        }

        // Load menu items and initialize cart count on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadMenuItems();
            getCartCount();
        });

        // Handle search and filter changes
        document.getElementById('searchInput').addEventListener('input', loadMenuItems);
        document.getElementById('categoryFilter').addEventListener('change', loadMenuItems);

        // Focus search input when clicking the search button
        const searchInput = document.getElementById('searchInput');
        const searchButton = document.getElementById('searchButton');

        searchButton.addEventListener('click', () => {
            searchInput.focus();
        });
    </script>
</body>
</html> 