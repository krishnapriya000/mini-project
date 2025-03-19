
<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the order_id is provided in the URL
if (!isset($_GET['order_id'])) {
    header("Location: cart.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Fetch the order details from the database
$order_query = "SELECT * FROM orders_table WHERE order_id = ? AND signupid = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("si", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();

// Check if the order exists
if ($order_result->num_rows === 0) {
    header("Location: cart.php?error=order_not_found");
    exit();
}

$order = $order_result->fetch_assoc();

// Fetch the payment details
$payment_query = "SELECT * FROM payment_table WHERE order_id = ? AND signupid = ?";
$stmt = $conn->prepare($payment_query);
$stmt->bind_param("si", $order_id, $user_id);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment = $payment_result->fetch_assoc();

// Fetch the order items from the database
$items_query = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
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
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            padding: 20px;
        }

        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        h1 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .order-details, .order-items {
            margin-bottom: 30px;
        }

        .order-details h2, .order-items h2 {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .order-details p {
            font-size: 16px;
            color: #555;
            margin-bottom: 10px;
        }

        .order-items table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-items th, .order-items td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .order-items th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .order-items td {
            color: #555;
        }

        .order-items td.item-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .order-total {
            font-size: 18px;
            font-weight: 600;
            color: #e74c3c;
            text-align: right;
            margin-top: 20px;
        }

        .btn-home {
            display: inline-block;
            background-color: #0077cc;
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .btn-home:hover {
            background-color: #005fa3;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <h1>Order Confirmation</h1>

        <!-- Order Details -->
        <div class="order-details">
            <h2>Order Details</h2>
            <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
            <p><strong>Order Date:</strong> <?php echo date("F j, Y, g:i a", strtotime($order['created_at'])); ?></p>
            <p><strong>Total Amount:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
            <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
            <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
            <p><strong>Order Status:</strong> <?php echo htmlspecialchars($order['order_status']); ?></p>
            <p><strong>Payment ID:</strong> <?php echo htmlspecialchars($payment['payment_id']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment['payment_method']); ?></p>
        </div>

        <!-- Order Items -->
        <div class="order-items">
            <h2>Order Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                        <tr>
                            <td class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₹<?php echo number_format($item['price'], 2); ?></td>
                            <td>₹<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="order-total">
                <strong>Total:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?>
            </div>
        </div>

        <!-- Back to Home Button -->
        <a href="index.php" class="btn-home">Back to Home</a>
    </div>
</body>
</html>