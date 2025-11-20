<?php
session_start();
require_once '../config/database.php';
require_once '../config/esewa.php';

// Get the base64 encoded response if available
$response_data = isset($_GET['data']) ? json_decode(base64_decode($_GET['data']), true) : null;

// Define error messages
$error_messages = [
    'verification_failed' => 'Payment verification failed. Please contact support.',
    'db_error' => 'Database error occurred. Please contact support.',
    'missing_params' => 'Missing required parameters.',
    'invalid_signature' => 'Invalid payment response signature.',
    'invalid_response' => 'Invalid payment response received.',
    'payment_incomplete' => 'Payment is not complete. Please try again.',
    'service_unavailable' => 'eSewa service is currently unavailable. Please try again later.',
    'default' => 'An error occurred during payment processing.'
];

// Get error type from URL
$error_type = isset($_GET['error']) ? $_GET['error'] : 'default';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get error message
$error_message = isset($error_messages[$error_type]) ? $error_messages[$error_type] : $error_messages['default'];

// If we have a status, append it to the message
if ($status) {
    $error_message .= " (Status: " . htmlspecialchars($status) . ")";
}

// Check if we have a pending order
$has_pending_order = isset($_SESSION['pending_order_id']) && 
                    isset($_SESSION['pending_transaction_uuid']) && 
                    isset($_SESSION['pending_amount']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Error - Food Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .error-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            text-align: center;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .error-icon {
            font-size: 64px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 18px;
            color: #333;
            margin-bottom: 30px;
        }
        .btn-retry {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px;
        }
        .btn-retry:hover {
            background-color: #218838;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navigation.php'; ?>

    <div class="container">
        <div class="error-container">
            <i class="fas fa-exclamation-circle error-icon"></i>
            <h2 class="mb-4">Payment Error</h2>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            
            <?php if ($has_pending_order): ?>
            <form action="retry_payment.php" method="post" class="mb-4">
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($_SESSION['pending_order_id']); ?>">
                <input type="hidden" name="transaction_uuid" value="<?php echo htmlspecialchars($_SESSION['pending_transaction_uuid']); ?>">
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($_SESSION['pending_amount']); ?>">
                <button type="submit" class="btn btn-retry">
                    <i class="fas fa-sync-alt"></i> Retry Payment Status Check
                </button>
            </form>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="cart.php" class="btn btn-secondary me-2">
                    <i class="fas fa-shopping-cart"></i> Return to Cart
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 