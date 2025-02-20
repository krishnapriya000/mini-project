<?php
session_start(); // Only call session_start() once at the very beginning
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if user came from forgot-password.php
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit();
}

$error = $success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify_otp'])) {
        $otp = trim($_POST['otp']);
        $email = $_SESSION['reset_email'];
        
        if (empty($otp)) {
            $error = "Please enter the OTP.";
        } else {
            // Get current server time for comparison
            $current_time = date('Y-m-d H:i:s');
            
            $sql = "SELECT * FROM signup WHERE email = ? AND reset_token = ? AND reset_token_expiry > ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $email, $otp, $current_time);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $_SESSION['otp_verified'] = true;
                header("Location: change-password.php");
                exit();
            } else {
                // Check specifically if token has expired
                $check_expired = "SELECT * FROM signup WHERE email = ? AND reset_token = ? AND reset_token_expiry <= ?";
                $stmt_expired = $conn->prepare($check_expired);
                $stmt_expired->bind_param("sss", $email, $otp, $current_time);
                $stmt_expired->execute();
                $result_expired = $stmt_expired->get_result();
                
                if ($result_expired->num_rows === 1) {
                    $error = "OTP has expired. Please request a new one.";
                } else {
                    $error = "Invalid OTP. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-image: url("./images/pic28.jpg");
            background-size: cover;
            padding: 20px;
        }
        .container {
            background: rgb(244, 243, 243);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(255, 254, 254, 0.97);
            width: 100%;
            max-width: 400px;
            text-align: center;
            opacity: 0.8;
        }
        .form-group {
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #007BFF;
            border: none;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font-size: 16px;
        }
        .error { color: #dc3545; }
        .success { color: #28a745; }
        .resend-link {
            margin-top: 15px;
            display: block;
            color: #007BFF;
            text-decoration: none;
            cursor: pointer;
        }
        .resend-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify OTP</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="text" name="otp" placeholder="Enter OTP" required>
            </div>
            <button type="submit" name="verify_otp" class="btn">Verify OTP</button>
        </form>
        
        <a href="forgot-password.php" class="resend-link">Resend OTP</a>
    </div>
</body>
</html>