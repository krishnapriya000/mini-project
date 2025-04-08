<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user_id is provided
if (!isset($_GET['user_id'])) {
    header("Location: admindashboard.php");
    exit();
}

$user_id = $_GET['user_id'];

// Fetch user details
$user_query = "SELECT u.*, s.email, s.phone, s.status as account_status
               FROM user_table u
               JOIN signup s ON u.signupid = s.signupid
               WHERE u.user_id = ?";

$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    // Handle user not found
    $_SESSION['error'] = "User not found";
    header("Location: admindashboard.php");
    exit();
}

// Get orders for this user
$orders_query = "SELECT 
                    o.order_id,
                    o.created_at,
                    o.total_amount,
                    o.payment_status,
                    o.order_status
                FROM orders_table o
                WHERE o.signupid = ?
                ORDER BY o.created_at DESC";

$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user['signupid']);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);

// Get order items for each order
foreach ($orders as $key => $order) {
    $order_items_query = "SELECT 
                            ci.product_id,
                            ci.quantity,
                            ci.price,
                            p.name as product_name,
                            p.image_url,
                            p.description,
                            c.name as category_name,
                            s.subcategory_name
                        FROM cart_items ci
                        JOIN product_table p ON ci.product_id = p.product_id
                        JOIN categories_table c ON ci.category_id = c.category_id
                        JOIN subcategories s ON ci.subcategory_id = s.id
                        WHERE ci.order_id = ?";
    
    $stmt = $conn->prepare($order_items_query);
    $stmt->bind_param("s", $order['order_id']);
    $stmt->execute();
    $items_result = $stmt->get_result();
    $orders[$key]['items'] = $items_result->fetch_all(MYSQLI_ASSOC);
}

// Get total amount spent and order count
$total_spent_query = "SELECT SUM(total_amount) as total_spent, 
                            COUNT(DISTINCT order_id) as total_orders 
                     FROM orders_table 
                     WHERE signupid = ?";
$stmt = $conn->prepare($total_spent_query);
$stmt->bind_param("i", $user['signupid']);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

// Get recent cart activity
$cart_query = "SELECT ci.product_id, p.name, p.price, p.image_url, ci.quantity, ci.status, ci.created_at
              FROM cart_items ci
              JOIN product_table p ON ci.product_id = p.product_id
              WHERE ci.signupid = ? AND ci.order_id IS NULL
              ORDER BY ci.created_at DESC
              LIMIT 5";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user['signupid']);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Report - <?php echo htmlspecialchars($user['username']); ?></title>
    <style>
        /* Basic PDF-friendly styles */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        h1 {
            font-size: 20px;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        
        h2, h3 {
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 7px;
            background: #f2f2f2;
            border-radius: 3px;
            font-size: 12px;
        }

        /* Button styles */
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .action-buttons button, 
        .action-buttons a {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .print-btn {
            background-color: #4CAF50;
            color: white;
        }
        
        .receipt-btn {
            background-color: #2196F3;
            color: white;
        }
        
        .dashboard-btn {
            background-color: #555;
            color: white;
        }
        
        /* PDF specific rules */
        @media print {
            body {
                padding: 0;
                font-size: 12pt;
            }
            
            .no-print {
                display: none;
            }
            
            .section {
                page-break-inside: avoid;
            }
            
            table {
                page-break-inside: avoid;
            }
            
            h1 {
                font-size: 18pt;
            }
            
            h2 {
                font-size: 14pt;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>User: <?php echo htmlspecialchars($user['username']); ?></h1>
            <div class="no-print action-buttons">
                <button onclick="window.print()" class="print-btn">Print Report</button>
                <button onclick="printReceipt()" class="receipt-btn">Print Receipt</button>
                <a href="admindashboard.php" class="dashboard-btn">Back to Dashboard</a>
            </div>
        </div>

        <!-- User Information Section -->
        <div class="section">
            <h2>User Information</h2>
            <table>
                <tr>
                    <th>Username</th>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <th>Email</th>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <th>Account Status</th>
                    <td><?php echo ucfirst($user['account_status']); ?></td>
                </tr>
                <tr>
                    <th>User Status</th>
                    <td><?php echo ucfirst($user['status']); ?></td>
                    <th>Registration Type</th>
                    <td><?php echo htmlspecialchars($user['reg_type']); ?></td>
                </tr>
            </table>
        </div>

        <!-- Summary Section -->
        <div class="section">
            <h2>Purchase Summary</h2>
            <table>
                <tr>
                    <th>Total Orders</th>
                    <td><?php echo $totals['total_orders'] ?? 0; ?></td>
                </tr>
                <tr>
                    <th>Total Spent</th>
                    <td>₹<?php echo number_format($totals['total_spent'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <th>Last Purchase</th>
                    <td><?php echo !empty($orders) ? date('d M Y', strtotime($orders[0]['created_at'])) : 'N/A'; ?></td>
                </tr>
            </table>
        </div>

        <!-- Orders Section -->
        <div class="section">
            <h2>Order History</h2>
            <?php if (empty($orders)): ?>
                <p>No orders found for this user.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo ucfirst($order['payment_status']); ?></td>
                            <td><?php echo ucfirst($order['order_status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Cart Activity Section -->
        <div class="section">
            <h2>Cart Activity</h2>
            <?php if (empty($cart_items)): ?>
                <p>No active cart items found for this user.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>₹<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo ucfirst($item['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>


    <script>
        function printReceipt() {
            // You can customize this for receipt-specific printing
            let originalTitle = document.title;
            document.title = "Receipt for <?php echo htmlspecialchars($user['username']); ?>";
            
            // Optionally hide some sections for receipt printing
            let sections = document.querySelectorAll('.section');
            sections.forEach((section, index) => {
                if (index > 1) { // Hide all sections except first two (user info and purchase summary)
                    section.style.display = 'none';
                }
            });
            
            window.print();
            
            // Restore after printing
            setTimeout(() => {
                document.title = originalTitle;
                sections.forEach(section => {
                    section.style.display = 'block';
                });
            }, 500);
        }
    </script>
</body>


</html>