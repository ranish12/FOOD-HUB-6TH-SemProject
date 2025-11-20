<?php
session_start();
require_once '../config/database.php';
require_once '../config/esewa.php';

// Get request headers
$headers = getallheaders();
$signature = isset($headers['Signature']) ? $headers['Signature'] : null;

// Get request body
$request_body = file_get_contents('php://input');
$request_data = json_decode($request_body, true);

// Verify signature
if ($signature && $request_body) {
    $calculated_signature = generateEsewaSignature($request_body);
    
    if (hash_equals($signature, $calculated_signature)) {
        // Signature is valid, process the payment
        if (isset($request_data['request_id']) && isset($request_data['status']) && isset($_GET['pid'])) {
            $payment_id = $_GET['pid'];
            $status = $request_data['status'];
            
            if ($status === 'COMPLETE') {
                try {
                    // Start transaction
                    $conn->beginTransaction();
                    
                    // Get order_id from payment record
                    $stmt = $conn->prepare("SELECT order_id FROM Payments WHERE payment_id = ?");
                    $stmt->execute([$payment_id]);
                    $payment = $stmt->fetch();
                    
                    if ($payment) {
                        $order_id = $payment['order_id'];
                        
                        // Update payment status in database
                        $stmt = $conn->prepare("UPDATE Payments SET payment_status = 'Completed' WHERE payment_id = ?");
                        $stmt->execute([$payment_id]);
                        
                        // Update order status
                        $stmt = $conn->prepare("UPDATE Orders SET status = 'Paid' WHERE order_id = ?");
                        $stmt->execute([$order_id]);
                        
                        // Commit transaction
                        $conn->commit();
                        
                        // Redirect to success page
                        header('Location: ../customer/order_success.php?order_id=' . $order_id);
                        exit();
                    }
                } catch (Exception $e) {
                    // Rollback on error
                    $conn->rollBack();
                    error_log('eSewa payment processing error: ' . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
                    exit();
                }
            }
        }
    }
}

// If verification fails or there's an error
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Invalid signature or request']);
exit();
?>
