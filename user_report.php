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

// Fetch shipping addresses
$addresses_query = "SELECT * FROM shipping_addresses WHERE signupid = ? ORDER BY is_default DESC";
$stmt = $conn->prepare($addresses_query);
$stmt->bind_param("i", $user['signupid']);
$stmt->execute();
$addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get orders for this user
$orders_query = "SELECT 
                    o.order_id,
                    o.shipping_address,
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
    <title>Detailed User Report - <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: rgb(248, 191, 125);
            color: #333;
        }

        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            color: rgb(179, 69, 10);
            margin: 0;
        }

        .print-button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .section h2 {
            color: rgb(179, 69, 10);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            display: flex;
            align-items: center;
        }

        .section h2 i {
            margin-right: 10px;
        }

        .user-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }

        .info-value {
            background: #fff;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            display: block;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactive {
            background: #ffebee;
            color: #c62828;
        }

        .status-pending {
            background: #fff8e1;
            color: #ff8f00;
        }

        .purchase-summary {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: rgb(179, 69, 10);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .summary-card h3 {
            margin: 0;
            font-size: 16px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .summary-card p {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .accordion {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .accordion-header {
            background: rgb(179, 69, 10);
            color: white;
            padding: 15px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .accordion-content {
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .accordion-content.show {
            padding: 20px;
            max-height: 2000px;
            transition: max-height 0.5s ease-in;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .product-image-container {
            height: 150px;
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details {
            padding: 15px;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .product-category {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .product-price {
            color: rgb(179, 69, 10);
            font-weight: bold;
            font-size: 18px;
        }

        .address-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .default-address {
            border: 2px solid rgb(179, 69, 10);
        }

        .default-badge {
            background: rgb(179, 69, 10);
            color: white;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 10px;
            margin-left: 10px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .orders-table th {
            background: #f5f5f5;
        }

        .order-item-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .order-item-table th,
        .order-item-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .shipping-details {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }

        .tab-container {
            margin-bottom: 20px;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background: #f1f1f1;
            border: 1px solid #ddd;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }

        .tab.active {
            background: rgb(179, 69, 10);
            color: white;
        }

        .tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            background: #fff;
        }

        .tab-content.active {
            display: block;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background: rgb(179, 69, 10);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }

        .back-button:hover {
            background: #c85000;
        }

        @media print {
            .back-button, .print-button, .tab {
                display: none;
            }
            .tab-content {
                display: block;
                border: none;
            }
            body {
                background: white;
                padding: 0;
            }
            .report-container {
                box-shadow: none;
                padding: 0;
            }
        }

        @media (max-width: 768px) {
            .user-info, .purchase-summary {
                grid-template-columns: 1fr;
            }
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .order-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .order-info h4 {
            margin: 0 0 5px 0;
            color: rgb(179, 69, 10);
        }

        .order-date {
            color: #666;
            font-size: 14px;
        }

        .order-date i {
            margin-right: 5px;
        }

        .order-status {
            text-align: right;
        }

        .order-amount {
            margin-top: 5px;
            font-size: 16px;
        }

        .order-address {
            padding-top: 10px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .order-address i {
            color: rgb(179, 69, 10);
            margin-top: 3px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-completed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-pending {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-failed {
            background: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <header>
            <h1 class="page-title">User Report: <?php echo htmlspecialchars($user['username']); ?></h1>
            <button class="print-button" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
        </header>

        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" onclick="openTab(event, 'overview')"><i class="fas fa-user"></i> Overview</div>
                <div class="tab" onclick="openTab(event, 'orders')"><i class="fas fa-shopping-cart"></i> Orders</div>
                <!-- <div class="tab" onclick="openTab(event, 'addresses')"><i class="fas fa-map-marker-alt"></i> Addresses</div> -->
                <div class="tab" onclick="openTab(event, 'activity')"><i class="fas fa-chart-line"></i> Activity</div>
            </div>

            <div id="overview" class="tab-content active">
                <div class="section">
                    <h2><i class="fas fa-user-circle"></i> User Information</h2>
                    <div class="user-info">
                        <div class="info-item">
                            <span class="info-label">Username</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Account Status</span>
                            <span class="status-badge status-<?php echo strtolower($user['account_status']); ?>">
                                <?php echo ucfirst($user['account_status']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">User Status</span>
                            <span class="status-badge status-<?php echo strtolower($user['status']); ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Registration Type</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['reg_type']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2><i class="fas fa-chart-pie"></i> Purchase Summary</h2>
                    <div class="purchase-summary">
                        <div class="summary-card">
                            <h3>Total Orders</h3>
                            <p><?php echo $totals['total_orders'] ?? 0; ?></p>
                        </div>
                        <div class="summary-card">
                            <h3>Total Spent</h3>
                            <p>₹<?php echo number_format($totals['total_spent'] ?? 0, 2); ?></p>
                        </div>
                        <div class="summary-card">
                            <h3>Last Purchase</h3>
                            <p><?php echo !empty($orders) ? date('d M Y', strtotime($orders[0]['created_at'])) : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="orders" class="tab-content">
                <div class="section">
                    <h2><i class="fas fa-shopping-cart"></i> Order History</h2>
                    
                    <?php if (empty($orders)): ?>
                        <p>No orders found for this user.</p>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-info">
                                        <h4>Order #<?php echo htmlspecialchars($order['order_id']); ?></h4>
                                        <span class="order-date">
                                            <i class="far fa-calendar-alt"></i>
                                            <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="order-status">
                                        <span class="status-badge status-<?php echo strtolower($order['payment_status']); ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                        <div class="order-amount">
                                            <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="order-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="addresses" class="tab-content">
                <div class="section">
                    <h2><i class="fas fa-map-marker-alt"></i> Shipping Addresses</h2>
                    
                    <?php if (empty($addresses)): ?>
                        <p>No saved addresses found for this user.</p>
                    <?php else: ?>
                        <?php foreach ($addresses as $address): ?>
                        <div class="address-card <?php echo $address['is_default'] ? 'default-address' : ''; ?>">
                            <h4>
                                Address
                                <?php if ($address['is_default']): ?>
                                <span class="default-badge">Default</span>
                                <?php endif; ?>
                            </h4>
                            <p><?php echo nl2br(htmlspecialchars($address['address_line1'])); ?></p>
                            <p>
                                <?php echo htmlspecialchars($address['city']); ?>, 
                                <?php echo htmlspecialchars($address['state']); ?> - 
                                <?php echo htmlspecialchars($address['postal_code']); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div id="activity" class="tab-content">
                <div class="section">
                    <h2><i class="fas fa-shopping-basket"></i> Cart Activity</h2>
                    
                    <?php if (empty($cart_items)): ?>
                        <p>No active cart items found for this user.</p>
                    <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Image</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Added On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             width="50" height="50" style="object-fit: cover; border-radius: 4px;">
                                    </td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($item['status']); ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($item['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <a href="admindashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script>
        function openTab(evt, tabName) {
            // Declare all variables
            var i, tabcontent, tablinks;

            // Get all elements with class="tab-content" and hide them
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].className = tabcontent[i].className.replace(" active", "");
            }

            // Get all elements with class="tab" and remove the class "active"
            tablinks = document.getElementsByClassName("tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }

            // Show the current tab, and add an "active" class to the button that opened the tab
            document.getElementById(tabName).className += " active";
            evt.currentTarget.className += " active";
        }

        function toggleAccordion(element) {
            // Toggle the active class on the accordion header
            element.classList.toggle("active");
            
            // Toggle the show class on the next sibling (accordion content)
            var content = element.nextElementSibling;
            if (content.classList.contains("show")) {
                content.classList.remove("show");
            } else {
                content.classList.add("show");
            }
        }
    </script>
</body>
</html>