<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

if (!isset($_SESSION['signupid']) || !isset($_GET['order_id'])) {
    header("Location: error.php");
    exit();
}

$order_id = $_GET['order_id'];
$signupid = $_SESSION['signupid'];

// Fetch order details
$query = "SELECT * FROM orders_table WHERE order_id = ? AND signupid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $order_id, $signupid);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

// Fetch payment details
$payment_query = "SELECT * FROM payment_table WHERE order_id = ?";
$stmt = $conn->prepare($payment_query);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment = $payment_result->fetch_assoc();

// Fetch cart items
$cart_query = "SELECT * FROM cart_table WHERE order_id = ? AND signupid = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("si", $order_id, $signupid);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_items = $cart_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Order #<?php echo $order_id; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }

        .receipt {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            background: white;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #000;
        }

        .receipt-details {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
        }

        .total-row {
            font-weight: bold;
        }

        .print-button {
            text-align: center;
            margin: 20px 0;
        }

        .print-button button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        @media print {
            .print-button {
                display: none;
            }
            body {
                padding: 0;
            }
            .receipt {
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <h1>BabyCubs</h1>
            <p>Order Receipt</p>
        </div>

        <div class="receipt-details">
            <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?></p>
            <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($payment['created_at'])); ?></p>
            <p><strong>Payment ID:</strong> <?php echo htmlspecialchars($payment['payment_id']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment['payment_method']); ?></p>
        </div>

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
                $total = 0;
                foreach ($cart_items as $item): 
                    $itemTotal = $item['price'] * $item['quantity'];
                    $total += $itemTotal;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td>₹<?php echo number_format($itemTotal, 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;"><strong>Total Amount:</strong></td>
                    <td>₹<?php echo number_format($total, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="receipt-details">
            <p><strong>Shipping Address:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p>Thank you for shopping with BabyCubs!</p>
            <p>For any queries, please contact our customer support.</p>
        </div>
    </div>

    <div class="print-button">
        <button onclick="window.print()">Print Receipt</button>
    </div>
</body>
</html> 