<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    
    $update_query = "UPDATE orders_table SET order_status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Order status updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update order status";
    }
    
    header("Location: admin_orders.php");
    exit();
}

// Fetch all orders with user details
$orders_query = "SELECT o.*, s.username 
                FROM orders_table o
                JOIN signup s ON o.signupid = s.signupid
                ORDER BY o.created_at DESC";
$orders_result = $conn->query($orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Order Management</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background:rgb(248, 191, 125)
            color: #5d4037;
            margin: 0;
            padding: 0;
        }

        h2 {
            color:rgb(245, 229, 224);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 200px;
            background-color: rgb(179, 69, 10);
            color: #fff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #fff;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 15px 0;
        }

        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .sidebar ul li a:hover {
            color: #d7ccc8;
        }

        .sidebar ul li.active a {
            color: #d7ccc8;
            font-weight: bold;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background-color: rgb(179, 69, 10);
            color: white;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            font-size: 28px;
        }

        /* Table Styles */
        .table-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgb(179, 69, 10);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background-color: #8d6e63;
            color: #fff;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
        }

        table th {
            font-weight: bold;
        }

        table tbody tr {
            border-bottom: 1px solid #ddd;
        }

        table tbody tr:hover {
            background-color: #f5f5f5;
        }

        /* Order Details Styles */
        .order-details {
            display: none;
            background: #f9f9f9;
            padding: 15px;
            margin: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgb(179, 69, 10);
        }

        .order-details h4 {
            margin-top: 0;
            color: rgb(179, 69, 10);
        }

        .order-details p {
            margin: 5px 0;
        }

        /* Button Styles */
        .view-details-btn {
            background: rgb(179, 69, 10);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .view-details-btn:hover {
            background: #6d4c41;
        }

        /* Status Select Styles */
        .status-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: #fff;
            color: #5d4037;
            cursor: pointer;
        }

        .status-select:focus {
            outline: none;
            border-color: #8d6e63;
        }

        /* Status Color Styles */
        .status-processing { color: #f39c12; }
        .status-shipped { color: #3498db; }
        .status-delivered { color: #2ecc71; }
        .status-cancelled { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>BabyCubs</h2>
        <ul>
            <li><a href="admindashboard.php">Dashboard</a></li>
            <li><a href="index.php">Home</a></li>
            <li><a href="admindashboard.php#Manage Users">Manage Users</a></li>
            <li><a href="admindashboard.php#Manage Seller">Manage Sellers</a></li>
            <li><a href="manage categories.php">Manage Categories</a></li>
            <li class="active"><a href="admin_orders.php">Orders</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Order Management</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Order Status</th>
                        <th>Payment Status</th>
                        <th>Created Date</th>
                        <!-- <th>Actions</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            <!-- <td class="status-<?php echo strtolower($order['order_status']); ?>">
                                <?php echo ucfirst($order['order_status'] ?? 'Processing'); ?>
                            </td> -->
                            <td><?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?></td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></td>
                            <td>
                                <button class="view-details-btn" onclick="toggleDetails('<?php echo $order['order_id']; ?>')">
                                    View Details
                                </button>
                                <!-- <form method="post" style="display: inline-block; margin-left: 10px;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <select name="new_status" class="status-select" onchange="this.form.submit()">
                                        <option value="">Update Status</option>
                                        <option value="processing" <?php echo ($order['order_status'] ?? '') == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo ($order['order_status'] ?? '') == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo ($order['order_status'] ?? '') == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo ($order['order_status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </form> -->
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7">
                                <div id="details-<?php echo $order['order_id']; ?>" class="order-details">
                                    <h4>Order Details</h4>
                                    <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
                                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                                    <p><strong>Total Amount:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                                    <p><strong>Payment ID:</strong> <?php echo htmlspecialchars($order['payment_id']); ?></p>
                                    <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address'] ?? 'N/A'); ?></p>
                                    <p><strong>Order Status:</strong> <?php echo ucfirst($order['order_status'] ?? 'Processing'); ?></p>
                                    <p><strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?></p>
                                    <p><strong>Created Date:</strong> <?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleDetails(orderId) {
            const detailsDiv = document.getElementById(`details-${orderId}`);
            if (detailsDiv.style.display === 'none' || detailsDiv.style.display === '') {
                detailsDiv.style.display = 'block';
            } else {
                detailsDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>