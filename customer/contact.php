<?php
session_start();
require_once '../config/database.php';

// Get contact information
$stmt = $conn->query("SELECT * FROM ContactInfo WHERE id = 1");
$contact = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Food Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .contact-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .contact-info {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            height: 100%;
        }
        .contact-item {
            margin-bottom: 20px;
        }
        .contact-item i {
            color: #FF6B00;
            margin-right: 10px;
            font-size: 1.2rem;
        }
        .map-container {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .contact-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <!-- Contact Section -->
    <div class="contact-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="contact-info">
                        <h3 class="mb-4">Contact Information</h3>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo nl2br(htmlspecialchars($contact['address'])); ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($contact['phone']); ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($contact['email']); ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo htmlspecialchars($contact['working_hours']); ?></span>
                        </div>
                    </div>
                </div>
                <!-- <div class="col-md-8">
                    <div class="contact-form">
                        <!-- <h3 class="mb-4">Send us a Message</h3>
                        <form id="contactForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form> -->
                    <!-- </div>
                </div> -->
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <!-- <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.4643151999997!2d85.3172783!3d27.7090333!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb1900c2f3f0c1%3A0x7c0c1d3c3c3c3c3c!2sFood%20Hub!5e0!3m2!1sen!2snp!4v1620000000000!5m2!1sen!2snp" 
                            width="100%" 
                            height="100%" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div> -->
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