<?php
session_start();
require_once '../config/database.php';
require_once '../config/esewa.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: ../customer/cart.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get delivery address from session or form
        $delivery_address = $_SESSION['delivery_address'] ?? '';
        
        // Calculate total
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        $delivery_fee = 50;
        $grand_total = $total + $delivery_fee;
        
        // Create order
        $stmt = $conn->prepare(
            "INSERT INTO Orders (user_id, total_amount, delivery_address, status) 
             VALUES (?, ?, ?, 'Pending')"
        );
        $stmt->execute([$_SESSION['user_id'], $grand_total, $delivery_address]);
        $order_id = $conn->lastInsertId();
        
        // Add order items
        foreach ($_SESSION['cart'] as $menu_id => $item) {
            $stmt = $conn->prepare(
                "INSERT INTO OrderItems (order_id, menu_id, quantity, price) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$order_id, $menu_id, $item['quantity'], $item['price']]);
        }
        
        // Create payment record
        $stmt = $conn->prepare(
            "INSERT INTO Payments (order_id, payment_method, payment_status, amount) 
             VALUES (?, 'esewa', 'Pending', ?)"
        );
        $stmt->execute([$order_id, $grand_total]);
        $payment_id = $conn->lastInsertId();
        
        // Generate transaction UUID
        $transaction_uuid = 'FH_' . $order_id . '_' . time();
        
        // Update payment with transaction UUID
        $stmt = $conn->prepare("UPDATE Payments SET transaction_id = ? WHERE payment_id = ?");
        $stmt->execute([$transaction_uuid, $payment_id]);
        
        // Commit transaction
        $conn->commit();
        
        // Clear cart after successful order creation
        unset($_SESSION['cart']);
        
        // Prepare eSewa payment data
        $amount = $grand_total;
        $tax_amount = 0; // Tax included in total
        $service_charge = 0;
        $delivery_charge = 0;
        
        // Prepare signature
        $signed_field_names = 'total_amount,transaction_uuid,product_code';
        
        // Format values for signature
        $total_amount = number_format($amount, 2, '.', '');
        $product_code = ESEWA_MERCHANT_ID;
        
        // Generate signature string
        $string_to_sign = $total_amount . ',' . $transaction_uuid . ',' . $product_code;
        
        // Generate signature
        $signature = hash_hmac('sha256', $string_to_sign, $product_code);
        
        // Show eSewa payment form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Redirecting to eSewa - Food Hub</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-body text-center">
                                <h3 class="mb-4">Redirecting to eSewa</h3>
                                <p>Please wait while we redirect you to eSewa payment gateway...</p>
                                <form action="<?php echo ESEWA_API_URL; ?>" method="POST" id="esewaForm">
                                    <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                                    <input type="hidden" name="tax_amount" value="<?php echo $tax_amount; ?>">
                                    <input type="hidden" name="total_amount" value="<?php echo $amount; ?>">
                                    <input type="hidden" name="transaction_uuid" value="<?php echo $transaction_uuid; ?>">
                                    <input type="hidden" name="product_code" value="<?php echo ESEWA_MERCHANT_ID; ?>">
                                    <input type="hidden" name="product_service_charge" value="<?php echo $service_charge; ?>">
                                    <input type="hidden" name="product_delivery_charge" value="<?php echo $delivery_charge; ?>">
                                    <input type="hidden" name="success_url" value="<?php echo ESEWA_SUCCESS_URL; ?>">
                                    <input type="hidden" name="failure_url" value="<?php echo ESEWA_FAILURE_URL; ?>">
                                    <input type="hidden" name="signed_field_names" value="<?php echo $signed_field_names; ?>">
                                    <input type="hidden" name="signature" value="<?php echo $signature; ?>">
                                    <button type="submit" class="btn btn-primary" style="display: none;">Proceed to eSewa</button>
                                </form>
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                // Automatically submit the form
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('esewaForm').submit();
                });
            </script>
        </body>
        </html>
        <?php
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log('Error processing order: ' . $e->getMessage());
        header('Location: ../customer/checkout.php?error=1');
        exit();
    }
}

// If we get here, something went wrong
header('Location: ../customer/checkout.php');
exit();
