<?php
// forgot-password.php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// SMTP Configuration - directly defined here instead of using constants
$smtp_user = 'krishnapriyarajesh0@gmail.com';
$smtp_pass = 'xwry zrcw biqh sgwd';

$error = $success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        // Check if email exists in database
        $sql = "SELECT * FROM signup WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Generate OTP
            $otp = sprintf("%06d", random_int(0, 999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Update database with OTP and expiry
            $update_sql = "UPDATE signup SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sss", $otp, $expiry, $email);
            
            if ($update_stmt->execute()) {
                // Send email with OTP
                $mail = new PHPMailer(true);
                
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp_user;
                    $mail->Password = $smtp_pass;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    
                    $mail->setFrom($smtp_user, 'Password Reset');
                    $mail->addAddress($email);
                    $mail->Subject = 'Password Reset OTP';
                    $mail->Body = "Your OTP for password reset is: $otp\n\nThis code will expire in 10 minutes.";
                    
                    $mail->send();
                    $_SESSION['reset_email'] = $email;
                    header("Location: verify-otp.php");
                    exit();
                } catch (Exception $e) {
                    $error = "Failed to send OTP. Please try again later. Error: " . $mail->ErrorInfo;
                }
            } else {
                $error = "Something went wrong. Please try again.";
            }
        } else {
            $error = "Email address not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn">Send Reset OTP</button>
        </form>
    </div>
</body>
</html>