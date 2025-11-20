<?php
session_start();
require_once '../config/database.php';
require_once '../config/esewa.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: payment_error.php?error=invalid_request');
    exit();
}

// Get the payment details from POST data
$order_id = $_POST['order_id'] ?? null;
$transaction_uuid = $_POST['transaction_uuid'] ?? null;
$amount = $_POST['amount'] ?? null;

// Validate required parameters
if (!$order_id || !$transaction_uuid || !$amount) {
    header('Location: payment_error.php?error=missing_params');
    exit();
}

// Log the retry attempt
error_log("Retrying payment status check for order {$order_id}");

// Check payment status with retries
$max_retries = 3;
$retry_delay = 2; // seconds
$status_response = null;
$attempts = 0;

while ($attempts < $max_retries) {
    $status_response = checkEsewaPaymentStatus($transaction_uuid, $amount);
    
    if ($status_response) {
        break;
    }
    
    $attempts++;
    if ($attempts < $max_retries) {
        error_log("Payment status check failed (attempt {$attempts}). Retrying in {$retry_delay} seconds...");
        sleep($retry_delay);
    }
}

// Log the status response for debugging
error_log("Payment status response on retry: " . print_r($status_response, true));

if ($status_response && $status_response['status'] === 'COMPLETE') {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Update payment status
        $stmt = $conn->prepare("
            UPDATE payments 
            SET payment_status = 'Completed', 
                transaction_id = ? 
            WHERE order_id = ?
        ");
        $stmt->execute([$status_response['ref_id'], $order_id]);
        
        // Update order status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = 'Paid' 
            WHERE id = ?
        ");
        $stmt->execute([$order_id]);
        
        // Commit transaction
        $conn->commit();
        
        // Clear pending order session data
        unset($_SESSION['pending_order_id']);
        unset($_SESSION['pending_transaction_uuid']);
        unset($_SESSION['pending_amount']);
        
        // Clear cart
        unset($_SESSION['cart']);
        
        // Redirect to success page
        header('Location: order_success.php?order_id=' . $order_id);
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error updating payment status on retry: " . $e->getMessage());
        header('Location: payment_error.php?error=db_error');
        exit();
    }
} else {
    // Payment still not completed or service unavailable
    $status = $status_response ? $status_response['status'] : 'SERVICE_UNAVAILABLE';
    error_log("Payment still not completed on retry. Status: " . $status);
    
    // Keep the pending order data in session
    $_SESSION['pending_order_id'] = $order_id;
    $_SESSION['pending_transaction_uuid'] = $transaction_uuid;
    $_SESSION['pending_amount'] = $amount;
    
    header('Location: payment_error.php?error=payment_incomplete&status=' . urlencode($status));
    exit();
} 