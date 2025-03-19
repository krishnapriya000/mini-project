<?php
session_start();
$error_message = isset($_GET['message']) ? $_GET['message'] : "An unknown error occurred.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs - Error</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: linear-gradient(to right, #ffffff, #f8f9fa);
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: color 0.3s ease;
        }
        
        .logo:hover {
            color: #0077cc;
        }
        
        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }
        
        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 22px;
            color: #2c3e50;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .icon-btn:hover {
            color: #0077cc;
            background-color: rgba(0,119,204,0.1);
            transform: translateY(-2px);
        }
        
        .error-container {
            max-width: 800px;
            margin: 80px auto;
            padding: 40px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .error-icon {
            font-size: 70px;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .error-message {
            font-size: 16px;
            color: #555;
            margin-bottom: 30px;
            padding: 0 20px;
            line-height: 1.6;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .primary-btn {
            background-color: #0077cc;
            color: white;
            border: none;
        }
        
        .primary-btn:hover {
            background-color: #005fa3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,119,204,0.3);
        }
        
        .secondary-btn {
            background-color: #f8f9fa;
            color: #2c3e50;
            border: 1px solid #ddd;
        }
        
        .secondary-btn:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .support-info {
            margin-top: 40px;
            padding: 20px;
            background-color: #f0f7ff;
            border-radius: 8px;
        }
        
        .support-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .support-text {
            color: #555;
            line-height: 1.6;
        }
        
        .support-email {
            display: block;
            margin-top: 10px;
            color: #0077cc;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: space-around;
            }
            
            .error-container {
                margin: 40px 20px;
                padding: 30px 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" style="text-decoration: none;">
            <div class="logo">BabyCubs</div>
        </a>
        
        <div class="nav-links">
            <a href="index.php" style="text-decoration: none;">
                <button class="icon-btn" title="Home">
                    <i class="fas fa-home"></i>
                </button>
            </a>
            <a href="product_view.php" style="text-decoration: none;">
                <button class="icon-btn" title="Products">
                    <i class="fas fa-shopping-bag"></i>
                </button>
            </a>
            <a href="cart.php" style="text-decoration: none;">
                <button class="icon-btn" title="Cart">
                    <i class="fas fa-shopping-cart"></i>
                </button>
            </a>
            <a href="profile.php" style="text-decoration: none;">
                <div class="icon-btn" title="Profile">
                    <i class="fas fa-user"></i>
                </div>
            </a>
        </div>
    </div>

    <div class="error-container">
        <i class="fas fa-exclamation-circle error-icon"></i>
        <h1 class="error-title">Payment Error</h1>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        
        <div class="action-buttons">
            <a href="checkout.php" class="btn primary-btn">Try Again</a>
            <a href="cart.php" class="btn secondary-btn">Return to Cart</a>
        </div>
        
        <div class="support-info">
            <h3 class="support-title">Need Help?</h3>
            <p class="support-text">
                If you continue to experience issues with your payment, please contact our customer support team.
                Our team is available 24/7 to assist you with any questions or concerns.
            </p>
            <a href="mailto:support@babycubs.com" class="support-email">support@babycubs.com</a>
        </div>
    </div>
</body>
</html>