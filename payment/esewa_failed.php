<?php
session_start();
require_once '../config/database.php';
require_once '../config/esewa.php';

// Get transaction details from eSewa response
if (isset($_GET['transaction_uuid'])) {
    $transaction_uuid = $_GET['transaction_uuid'];
    
    // Extract order_id from transaction_uuid (format: FH_orderid_timestamp)
    $parts = explode('_', $transaction_uuid);
    if (count($parts) >= 2) {
        $order_id = $parts[1];
        
        try {
            // Update payment status to failed
            $stmt = $conn->prepare("UPDATE Payments SET payment_status = 'Failed' WHERE order_id = ?");
            $stmt->execute([$order_id]);
            
            // Update order status
            $stmt = $conn->prepare("UPDATE Orders SET status = 'Payment Failed' WHERE order_id = ?");
            $stmt->execute([$order_id]);
            
            // Log the failure
            error_log('eSewa payment failed for order: ' . $order_id);
        } catch (Exception $e) {
            error_log('Error updating failed payment status: ' . $e->getMessage());
        }
    }
}

// Redirect to failure page
header('Location: ../customer/order_failed.php');
exit();
?>
