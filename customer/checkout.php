<?php
session_start();
require_once '../config/database.php';
require_once '../config/esewa.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if cart exists and has items
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Calculate subtotal from cart items
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    error_log("Item: Price={$item['price']}, Qty={$item['quantity']}, Total={$item_total}");
}
error_log("Subtotal from cart: {$subtotal}");

// Initialize delivery fee
$delivery_fee = 0;

// Calculate tax and grand total
$tax = round($subtotal * 0.13, 2); // 13% of subtotal (items only)
$grand_total = $subtotal + $delivery_fee + $tax;

error_log("Final calculation: Subtotal={$subtotal}, Delivery={$delivery_fee}, Tax={$tax}, Grand Total={$grand_total}");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $delivery_address = $_POST['delivery_address'];
    $payment_method = $_POST['payment_method'];
    
    if (empty($delivery_address) || empty($payment_method) || !isset($_POST['delivery_location'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide delivery address, payment method, and delivery location'
        ]);
        exit();
    }

    // Set delivery fee based on location
    switch ($_POST['delivery_location']) {
        case 'banepa':
            $delivery_fee = 100;
            break;
        case 'dhulikhel':
            $delivery_fee = 50;
            break;
        case 'selfpickup':
            $delivery_fee = 0;
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid delivery location'
            ]);
            exit();
    }

    // Recalculate grand total with proper delivery fee
    $grand_total = $subtotal + $delivery_fee + $tax;
    
    // Verify POST data
    error_log("Checkout POST data - Address: {$delivery_address}, Payment: {$payment_method}");
    
    try {
        error_log("Starting checkout process for user: {$_SESSION['user_id']}");
        error_log("POST data: " . print_r($_POST, true));
        error_log("Cart data: " . print_r($_SESSION['cart'], true));
        
        // Validate cart
        if (empty($_SESSION['cart'])) {
            throw new Exception('Cart is empty');
        }

        // Verify user exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            error_log("User not found: {$_SESSION['user_id']}");
            throw new Exception('Invalid user session. Please login again.');
        }
        error_log("User verified: {$_SESSION['user_id']}");

        // Start transaction
        $conn->beginTransaction();
        error_log("Transaction started");

        // Create order first
        error_log("Creating order with: Subtotal={$subtotal}, Delivery={$delivery_fee}, Tax={$tax}, Total={$grand_total}");
        
        $sql = "INSERT INTO orders (user_id, subtotal, delivery_fee, tax_amount, total_amount, delivery_address, status) 
               VALUES (:user_id, :subtotal, :delivery_fee, :tax, :total, :address, :status)";
        
        $stmt = $conn->prepare($sql);
        $orderParams = [
            ':user_id' => $_SESSION['user_id'],
            ':subtotal' => $subtotal,
            ':delivery_fee' => $delivery_fee,
            ':tax' => $tax,
            ':total' => $grand_total,
            ':address' => $delivery_address,
            ':status' => 'Pending'
        ];
        
        error_log("Order parameters: " . print_r($orderParams, true));
        
        if (!$stmt->execute($orderParams)) {
            $error = $stmt->errorInfo();
            error_log("SQL Error: " . print_r($error, true));
            throw new Exception('Failed to create order: ' . $error[2]);
        }
        
        $order_id = $conn->lastInsertId();
        error_log("Order created with ID: {$order_id}");
        
        // Check stock availability for all items with proper locking
        $error_items = [];
        foreach ($_SESSION['cart'] as $menu_id => $item) {
            // Use SELECT FOR UPDATE to lock the row
            $stmt = $conn->prepare("SELECT name, stock_quantity FROM Menu WHERE menu_id = ? FOR UPDATE");
            $stmt->execute([$menu_id]);
            $menu_item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$menu_item) {
                $error_items[] = [
                    'menu_id' => $menu_id,
                    'name' => $item['name'],
                    'requested' => $item['quantity'],
                    'available' => 0
                ];
                continue;
            }

            // Verify stock availability
            if ($menu_item['stock_quantity'] < $item['quantity']) {
                $error_items[] = [
                    'menu_id' => $menu_id,
                    'name' => $menu_item['name'],
                    'requested' => $item['quantity'],
                    'available' => $menu_item['stock_quantity']
                ];
            }
        }

        // If any items are invalid, rollback and return error
        if (!empty($error_items)) {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'The following items are not available in the requested quantity:\n' .
                          implode('\n', array_map(function($item) {
                              return "- {$item['name']}: Requested: {$item['requested']}, Available: {$item['available']}";
                          }, $error_items)),
                'invalid_items' => $error_items
            ]);
            exit();
        }
        
        // Log order details before creation
        error_log("Creating order with: User ID={$_SESSION['user_id']}, Subtotal={$subtotal}, Delivery={$delivery_fee}, Tax={$tax}, Total={$grand_total}");
        
        // Add order items and update stock
        foreach ($_SESSION['cart'] as $menu_id => $item) {
            // Add order items
            foreach ($_SESSION['cart'] as $menu_id => $item) {
                $sql = "INSERT INTO orderitems (order_id, menu_id, quantity, price) 
                       VALUES (:order_id, :menu_id, :quantity, :price)";
                
                $stmt = $conn->prepare($sql);
                $itemParams = [
                    ':order_id' => $order_id,
                    ':menu_id' => $menu_id,
                    ':quantity' => $item['quantity'],
                    ':price' => $item['price']
                ];
                
                error_log("Adding order item: " . print_r($itemParams, true));
                
                if (!$stmt->execute($itemParams)) {
                    $error = $stmt->errorInfo();
                    error_log("SQL Error adding order item: " . print_r($error, true));
                    throw new Exception('Failed to add order item: ' . $error[2]);
                }
                
                // Update stock
                $sql = "UPDATE menu 
                       SET stock_quantity = stock_quantity - :qty 
                       WHERE menu_id = :id AND stock_quantity >= :qty";
                
                $stmt = $conn->prepare($sql);
                $stockParams = [
                    ':qty' => $item['quantity'],
                    ':id' => $menu_id
                ];
                
                error_log("Updating stock: " . print_r($stockParams, true));
                
                if (!$stmt->execute($stockParams)) {
                    $error = $stmt->errorInfo();
                    error_log("SQL Error updating stock: " . print_r($error, true));
                    throw new Exception('Failed to update stock: ' . $error[2]);
                }
                
                // Get updated stock quantity
                $stmt = $conn->prepare("SELECT stock_quantity FROM menu WHERE menu_id = ?");
                $stmt->execute([$menu_id]);
                $current_stock = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("Stock updated for menu_id: {$menu_id}. New stock: {$current_stock['stock_quantity']}");
            }
        }
        
        // Create payment record
        error_log("Creating payment record: OrderID={$order_id}, Method={$payment_method}, Amount={$grand_total}");
        $stmt = $conn->prepare("
            INSERT INTO payments (order_id, payment_method, payment_status, amount) 
            VALUES (:order_id, :method, :status, :amount)
        ");
        
        $paymentParams = [
            ':order_id' => $order_id,
            ':method' => $payment_method,
            ':status' => 'Pending',
            ':amount' => $grand_total
        ];
        
        error_log("Payment parameters: " . print_r($paymentParams, true));
        
        if (!$stmt->execute($paymentParams)) {
            $error = $stmt->errorInfo();
            error_log("SQL Error creating payment: " . print_r($error, true));
            throw new Exception('Failed to create payment record: ' . $error[2]);
        }
        $payment_id = $conn->lastInsertId();
        error_log("Payment record created with ID: {$payment_id}");
        
        // Commit transaction
        $conn->commit();
        error_log("Transaction committed successfully");
        
        // Handle payment based on method
        error_log("Handling payment method: {$payment_method}");
        
        if ($payment_method === 'cod') {
            try {
                // Clear cart
                unset($_SESSION['cart']);
                error_log("Cart cleared for COD order");
                
                // Return success response
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Order placed successfully! Your order ID is: ' . $order_id,
                    'order_id' => $order_id
                ]);
                error_log("COD order completed successfully. OrderID: {$order_id}");
                exit();
            } catch (Exception $e) {
                error_log("Error processing COD order: " . $e->getMessage());
                throw new Exception('Error processing COD order: ' . $e->getMessage());
            }
        } else if ($payment_method === 'esewa') {
            // Save cart before unsetting
            $cart = $_SESSION['cart'];
            unset($_SESSION['cart']);
            
            // Keep amounts in rupees for eSewa
            $amount = number_format($subtotal, 2, '.', ''); // Base amount in rupees
            $delivery_charge = number_format($delivery_fee, 2, '.', ''); // Delivery in rupees
            $tax_amount = number_format($tax, 2, '.', ''); // Tax in rupees
            $total_amount = number_format($grand_total, 2, '.', ''); // Total in rupees
            
            error_log("eSewa Payment (rupees):");
            error_log("Base amount: Rs. {$amount}");
            error_log("Delivery: Rs. {$delivery_charge}");
            error_log("Tax: Rs. {$tax_amount}");
            error_log("Total: Rs. {$total_amount}");
            $transaction_uuid = "FH_{$payment_id}_" . time();
            
            // Create signature string (amounts must be without decimals for signature)
            $signature_string = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code=EPAYTEST";
            $signature = generateEsewaSignature($signature_string);
            
            // Return payment form
            header('Content-Type: text/html');
            echo "<html><body>
                <form id='esewaForm' action='" . ESEWA_API_URL . "' method='POST'>
                    <input type='hidden' name='amount' value='{$amount}'>
                    <input type='hidden' name='tax_amount' value='{$tax_amount}'>
                    <input type='hidden' name='total_amount' value='{$total_amount}'>
                    <input type='hidden' name='transaction_uuid' value='{$transaction_uuid}'>
                    <input type='hidden' name='product_code' value='EPAYTEST'>
                    <input type='hidden' name='product_service_charge' value='0'>
                    <input type='hidden' name='product_delivery_charge' value='{$delivery_charge}'>
                    <input type='hidden' name='success_url' value='" . ESEWA_SUCCESS_URL . "?transaction_uuid={$transaction_uuid}' required>
                    <input type='hidden' name='failure_url' value='" . ESEWA_FAILURE_URL . "?transaction_uuid={$transaction_uuid}' required>
                    <input type='hidden' name='signed_field_names' value='total_amount,transaction_uuid,product_code' required>
                    <div style='text-align: center; padding: 20px;'>
                        <h2>Order Summary</h2>
                        <p>Amount: Rs. {$amount}</p>
                        <p>Delivery Charge: Rs. {$delivery_charge}</p>
                        <p>Tax: Rs. {$tax_amount}</p>
                        <p><strong>Total: Rs. {$total_amount}</strong></p>
                        <p>Redirecting to eSewa...</p>
                    </div>
                    <input type='hidden' name='signature' value='{$signature}' required>
                </form>
                <script>document.getElementById('esewaForm').submit();</script>
                </body></html>";
            exit();
        } else if ($payment_method === 'cod') {
            // Clear cart before redirecting
            unset($_SESSION['cart']);
            
            // For cash on delivery, return success response
            echo json_encode([
                'success' => true,
                'order_id' => $order_id,
                'message' => 'Order placed successfully'
            ]);
            exit();
        } else {
            throw new Exception('Invalid payment method: ' . $payment_method);
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
            error_log("Transaction rolled back due to error");
        }
        
        $errorMessage = $e->getMessage();
        error_log("Error creating order: {$errorMessage}");
        error_log("Stack trace: " . $e->getTraceAsString());
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error creating order: ' . $errorMessage
        ]);
        exit();
    }
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM Users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Food Hub</title>
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
        .checkout-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .checkout-item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .checkout-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        #esewaModal .modal-content {
            border-radius: 15px;
        }
        #esewaModal .modal-header {
            background-color: #5E2590;
            color: white;
            border-radius: 15px 15px 0 0;
        }
        #esewaModal .modal-body {
            padding: 2rem;
        }
        #esewaModal .form-control {
            border-radius: 8px;
            padding: 12px;
        }
        #esewaModal .btn-primary {
            background-color: #5E2590;
            border-color: #5E2590;
            border-radius: 8px;
            padding: 12px;
        }
        #esewaModal .btn-primary:hover {
            background-color: #4a1d6f;
            border-color: #4a1d6f;
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">Checkout</h1>
        
        <div class="row">
            <div class="col-md-8">
                <form id="checkoutForm" method="POST" action="order_success.php">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Delivery Information</h5>
                            <div class="mb-3">
                                <label for="delivery_location" class="form-label">Delivery Location</label>
                                <select class="form-control" id="delivery_location" name="delivery_location" required>
                                    <option value="">Select delivery location</option>
                                    <option value="banepa">Banepa (Rs. 100)</option>
                                    <option value="dhulikhel">Dhulikhel (Rs. 50)</option>
                                    <option value="selfpickup">Self Pickup (Rs. 0)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="delivery_address" class="form-label">Delivery Address</label>
                                <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Items</h5>
                            <?php foreach ($_SESSION['cart'] as $menu_id => $item): ?>
                                <div class="checkout-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="checkout-item-image"
                                                 onerror="this.src='../assets/images/placeholder.jpg'">
                                        </div>
                                        <div class="col-md-4">
                                            <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <p class="text-muted">Rs. <?php echo number_format($item['price'], 2); ?></p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-0">Quantity: <?php echo $item['quantity']; ?></p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-0">Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="col-md-4">
                <div class="checkout-summary">
                    <h4>Order Summary</h4>
                    <hr>
                    <!-- Stock Error Message -->
                    <div id="stockError" class="alert alert-danger mb-3 d-none"></div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal:</span>
                        <span>Rs. <span id="subtotal"><?php echo number_format($subtotal, 2); ?></span></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Delivery Fee:</span>
                        <span>Rs. <span id="delivery-fee"><?php echo number_format($delivery_fee, 2); ?></span></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tax (13%):</span>
                        <span>Rs. <span id="tax"><?php echo number_format($tax, 2); ?></span></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong>Rs. <span id="grand-total"><?php echo number_format($grand_total, 2); ?></span></strong>
                    </div>
                    <div class="d-grid gap-2">
                        <div class="form-group mb-3">
                            <label>Payment Method</label>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary" id="esewaPayBtn">
                                    <i class="fas fa-wallet"></i> Pay with eSewa
                                </button>
                                <button type="submit" class="btn btn-success" id="codPayBtn" form="checkoutForm">
                                    <i class="fas fa-money-bill-wave"></i> Cash on Delivery
                                </button>
                            </div>
                        </div>
                        <!-- Add error display area -->
                        <div id="checkoutError" class="alert alert-danger d-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- eSewa Payment Form -->
    <form id="esewaPaymentForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" style="display: none;">
        <input type="hidden" name="payment_method" value="esewa">
        <input type="hidden" name="delivery_location" id="esewaDeliveryLocation">
        <input type="hidden" name="delivery_address" id="esewaDeliveryAddress">
                    </form>
                </div>
            </div>
        </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to handle form submission
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const stockError = document.getElementById('stockError');
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                try {
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
                    stockError.classList.add('d-none');
                    
                    // Submit the form
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Redirect to success page or handle success
                        window.location.href = data.redirect || 'order_success.php';
                    } else {
                        // Show error message
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
                    }
                } catch (error) {
                    console.error('Error:', error);
                    stockError.classList.remove('d-none');
                    stockError.textContent = 'An error occurred while processing your order. Please try again.';
                } finally {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            const checkoutForm = document.getElementById('checkoutForm');
            const esewaPayBtn = document.getElementById('esewaPayBtn');
            const deliveryLocation = document.getElementById('delivery_location');
            const deliveryAddress = document.getElementById('delivery_address');
            const errorDiv = document.getElementById('checkoutError');
            
            // Elements for price updates
            const subtotalEl = document.getElementById('subtotal');
            const deliveryFeeEl = document.getElementById('delivery-fee');
            const taxEl = document.getElementById('tax');
            const grandTotalEl = document.getElementById('grand-total');
            
            // Get initial subtotal value
            const subtotal = parseFloat(subtotalEl.textContent.replace(/,/g, ''));
            
            // Handle delivery location change
            deliveryLocation.addEventListener('change', function() {
                let deliveryFee = 0;
                
                switch(this.value) {
                    case 'banepa':
                        deliveryFee = 100;
                        break;
                    case 'dhulikhel':
                        deliveryFee = 50;
                        break;
                    case 'selfpickup':
                        deliveryFee = 0;
                        break;
                }
                
                // Update delivery fee display
                deliveryFeeEl.textContent = deliveryFee.toFixed(2);
                
                // Calculate tax (13% of subtotal only)
                const tax = (subtotal * 0.13).toFixed(2);
                taxEl.textContent = tax;
                
                // Update grand total
                const grandTotal = (subtotal + deliveryFee + parseFloat(tax)).toFixed(2);
                grandTotalEl.textContent = grandTotal;
            });
            
            // Function to show validation error
            function showValidationError(element, message) {
                // Remove any existing error message
                const existingError = element.parentElement.querySelector('.invalid-feedback');
                if (existingError) existingError.remove();
                
                // Add error class and message
                element.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = message;
                element.parentElement.appendChild(errorDiv);
            }

            // Function to clear validation error
            function clearValidationError(element) {
                element.classList.remove('is-invalid');
                const errorDiv = element.parentElement.querySelector('.invalid-feedback');
                if (errorDiv) errorDiv.remove();
            }

            // Handle eSewa payment
            esewaPayBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Clear previous errors
                clearValidationError(deliveryLocation);
                clearValidationError(deliveryAddress);
                
                let isValid = true;
                
                if (!deliveryLocation.value) {
                    showValidationError(deliveryLocation, 'Please select delivery location');
                    isValid = false;
                }
                
                if (!deliveryAddress.value) {
                    showValidationError(deliveryAddress, 'Please enter delivery address');
                    isValid = false;
                }
                
                if (!isValid) {
                    return;
                }
                
                // Set values in eSewa form
                document.getElementById('esewaDeliveryLocation').value = deliveryLocation.value;
                document.getElementById('esewaDeliveryAddress').value = deliveryAddress.value;
                
                // Submit the eSewa form
                document.getElementById('esewaPaymentForm').submit();
            });
            
            // Handle form submission
            checkoutForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Clear previous errors
                clearValidationError(deliveryLocation);
                clearValidationError(deliveryAddress);
                
                let isValid = true;
                
                if (!deliveryLocation.value) {
                    showValidationError(deliveryLocation, 'Please select delivery location');
                    isValid = false;
                }
                
                if (!deliveryAddress.value) {
                    showValidationError(deliveryAddress, 'Please enter delivery address');
                    isValid = false;
                }
                
                if (!isValid) {
                    return;
                }
                
                const button = document.getElementById('codPayBtn');
                const originalBtnText = button.innerHTML;
                
                try {
                    // Disable button and show loading state
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
                    errorDiv.classList.add('d-none');
                    
                    // Submit the order
                    const formData = new FormData(checkoutForm);
                    formData.append('payment_method', 'cod');
                    
                    const response = await fetch('checkout.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Redirect to success page
                        window.location.href = 'order_success.php?order_id=' + data.order_id;
                    } else {
                        // Show error message
                        errorDiv.classList.remove('d-none');
                        errorDiv.textContent = data.message || 'Error processing your order. Please try again.';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    errorDiv.classList.remove('d-none');
                    errorDiv.textContent = 'Error processing your order. Please try again.';
                } finally {
                    // Restore button state
                    button.disabled = false;
                    button.innerHTML = originalBtnText;
                }
            });
        });
    </script>
</body>
</html> 