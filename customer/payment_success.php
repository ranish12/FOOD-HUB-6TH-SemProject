<?php
session_start();
require_once '../config/database.php';
require_once '../config/esewa.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get transaction details from eSewa response
if (isset($_GET['transaction_uuid'])) {
    $transaction_uuid = $_GET['transaction_uuid'];
    
    // Extract payment_id from transaction_uuid (format: FH_paymentid_timestamp)
    $parts = explode('_', $transaction_uuid);
    if (count($parts) >= 2) {
        $payment_id = $parts[1];
        
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Get order_id from payment record
            $stmt = $conn->prepare("SELECT order_id FROM Payments WHERE payment_id = ?");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch();
            
            if ($payment) {
                $order_id = $payment['order_id'];
                
                // Update payment status
                $stmt = $conn->prepare("UPDATE Payments SET payment_status = 'Completed' WHERE payment_id = ?");
                $stmt->execute([$payment_id]);
                
                // Update order status
                $stmt = $conn->prepare("UPDATE Orders SET status = 'Paid' WHERE order_id = ?");
                $stmt->execute([$order_id]);
                
                // Commit transaction
                $conn->commit();
                
                // Redirect to success page
                header('Location: order_success.php?order_id=' . $order_id);
                exit();
            } else {
                // Payment not found
                error_log("Payment record not found for ID: " . $payment_id);
                header('Location: payment_error.php?error=payment_not_found');
                exit();
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Error processing payment: " . $e->getMessage());
            header('Location: payment_error.php?error=db_error');
            exit();
        }
    } else {
        // Invalid transaction UUID format
        error_log("Invalid transaction UUID format: " . $transaction_uuid);
        header('Location: payment_error.php?error=invalid_transaction');
        exit();
    }
} else {
    // Missing transaction UUID
    error_log("Missing transaction UUID in request");
    header('Location: payment_error.php?error=missing_transaction');
    exit();
}
?> 