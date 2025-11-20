<?php
session_start();
require_once '../config/database.php';
require_once '../includes/otp_helper.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        // Generate OTP
        $otp = generateOTP();
        
        // Store OTP in database
        if (storeOTP($email, $otp)) {
            // Send OTP via email
            if (sendOTPEmail($email, $otp)) {
                $_SESSION['reset_email'] = $email;
                header('Location: verify_otp.php');
                exit;
            } else {
                $error = "Failed to send OTP. Please try again.";
            }
        } else {
            $error = "Failed to process your request. Please try again.";
        }
    } else {
        $error = "Email not found in our records.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Food Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .forgot-password-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="container">
        <div class="forgot-password-container">
            <h2 class="text-center mb-4">Password Recovery</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Enter your email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send OTP</button>
            </form>
            
            <div class="text-center mt-3">
                <a href="../customer/login.php" class="btn btn-link">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html> 