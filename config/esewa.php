<?php
// eSewa API Configuration
define('ESEWA_API_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
define('ESEWA_MERCHANT_ID', 'EPAYTEST');
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q'); // Replace with your actual secret key
define('ESEWA_SUCCESS_URL', 'http://localhost/food-hub/customer/payment_success.php');
define('ESEWA_FAILURE_URL', 'http://localhost/food-hub/payment/esewa_failed.php');

// Helper function to generate eSewa signature
function generateEsewaSignature($payload) {
    $secret_key = ESEWA_SECRET_KEY;
    return base64_encode(hash_hmac('sha256', $payload, $secret_key, true));
}
?>
