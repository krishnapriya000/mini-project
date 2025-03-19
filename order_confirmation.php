<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

if (!isset($_SESSION['signupid'])) {
    header("Location: error.php");
    exit();
}

$signupid = $_SESSION['signupid'];

// Fetch latest payment details
$payment_query = "SELECT * FROM payment_table 
                 WHERE signupid = ? AND payment_status = 'paid'
                 ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($payment_query);
$stmt->bind_param("i", $signupid);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment = $payment_result->fetch_assoc();

// Get user details
$user_query = "SELECT * FROM signup WHERE signupid = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $signupid);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - BabyCubs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .success-icon {
            color: #28a745;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .order-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .order-section h2 {
            color: #2c3e50;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }

        .status-paid {
            background-color: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 14px;
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-check-circle success-icon"></i>
            <h1>Order Confirmed!</h1>
            <p>Thank you for shopping with BabyCubs</p>
        </div>

        <div class="order-section">
            <h2>Order Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Order Date</div>
                    <div class="info-value">
                        <?php echo date('d M Y, h:i A', strtotime($payment['created_at'])); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($payment['payment_id']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value"><?php echo htmlspecialchars($payment['payment_method']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-paid">Paid</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="order-section">
            <h2>Order Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch ordered items
                    $items_query = "SELECT * FROM cart_table WHERE signupid = ? AND status = 'ordered'";
                    $stmt = $conn->prepare($items_query);
                    $stmt->bind_param("i", $signupid);
                    $stmt->execute();
                    $items_result = $stmt->get_result();
                    $total = 0;

                    while ($item = $items_result->fetch_assoc()):
                        $itemTotal = $item['price'] * $item['quantity'];
                        $total += $itemTotal;
                    ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                            </div>
                        </td>
                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td>₹<?php echo number_format($itemTotal, 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;"><strong>Total Amount:</strong></td>
                        <td><strong>₹<?php echo number_format($total, 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if (!empty($user['address'])): ?>
        <div class="order-section">
            <h2>Shipping Address</h2>
            <p><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="print_receipt.php" class="btn btn-primary" target="_blank">
                <i class="fas fa-print"></i> Print Receipt
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Continue Shopping
            </a>
        </div>
    </div>
</body>
</html>
</html>