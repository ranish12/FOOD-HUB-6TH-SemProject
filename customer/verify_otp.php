<?php
session_start();
require_once '../config/database.php';
require_once '../includes/otp_helper.php';

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit;
}

$error = '';
$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];
    
    if (verifyOTP($email, $otp)) {
        $_SESSION['verified_email'] = $email;
        header('Location: reset_password.php');
        exit;
    } else {
        $error = "Invalid or expired OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Food Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .verify-otp-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .otp-input {
            letter-spacing: 8px;
            font-size: 24px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="container">
        <div class="verify-otp-container">
            <h2 class="text-center mb-4">Verify OTP</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <p class="text-center mb-4">Please enter the OTP sent to your email address.</p>
            
            <form method="POST">
                <div class="mb-3">
                    <input type="text" class="form-control otp-input" id="otp" name="otp" 
                           maxlength="6" pattern="[0-9]{6}" required 
                           placeholder="Enter 6-digit OTP">
                </div>
                <button type="submit" class="btn btn-primary w-100">Verify OTP</button>
            </form>
            
            <div class="text-center mt-3">
                <a href="forgot_password.php" class="btn btn-link">Back to Forgot Password</a>
                <br>
                <a href="../customer/login.php" class="btn btn-link">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-format OTP input
        document.getElementById('otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html> 