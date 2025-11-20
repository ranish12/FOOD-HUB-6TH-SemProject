<?php
session_start();
require_once '../config/database.php';

// Get about us content
$stmt = $conn->query("SELECT * FROM AboutUs WHERE id = 1");
$about = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Food Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .about-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .about-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .about-content {
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <!-- About Section -->
    <div class="about-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 mb-4 mb-md-0">
                    <?php if ($about['image_url']): ?>
                    <img src="../<?php echo htmlspecialchars($about['image_url']); ?>" alt="About Us" class="about-image">
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <div class="about-content">
                        <h2 class="mb-4"><?php echo htmlspecialchars($about['title']); ?></h2>
                        <p class="lead"><?php echo nl2br(htmlspecialchars($about['content'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        // Initialize cart count on page load
        document.addEventListener('DOMContentLoaded', () => {
            getCartCount();
        });
    </script>
</body>
</html>