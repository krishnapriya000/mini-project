<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if seller is logged in
if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit();
}

$seller_id = $_SESSION['seller_id'];

// Get detailed order statistics
$stats_query = "SELECT 
    COUNT(DISTINCT o.order_id) as total_orders,
    SUM(ci.quantity) as total_items_sold,
    SUM(ci.quantity * ci.price) as total_revenue,
    COUNT(DISTINCT CASE WHEN o.order_status = 'processing' THEN o.order_id END) as pending_orders,
    COUNT(DISTINCT CASE WHEN o.order_status = 'completed' THEN o.order_id END) as completed_orders,
    COUNT(DISTINCT CASE WHEN o.order_status = 'cancelled' THEN o.order_id END) as cancelled_orders,
    COUNT(DISTINCT s.signupid) as unique_customers
FROM product_table p
LEFT JOIN cart_items ci ON p.product_id = ci.product_id
LEFT JOIN orders_table o ON ci.order_id = o.order_id
LEFT JOIN signup s ON o.signupid = s.signupid
WHERE p.seller_id = ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get seller's orders with detailed information
$orders_query = "SELECT 
    o.order_id,
    o.created_at,
    o.order_status,
    o.shipping_address,
    o.payment_status,
    p.name as product_name,
    p.image_url,
    p.price,
    ci.quantity,
    (ci.quantity * ci.price) as subtotal,
    s.username as customer_name,
    s.email as customer_email,
    s.phone as customer_phone,
    c.name as category_name,
    sub.subcategory_name
FROM product_table p
JOIN cart_items ci ON p.product_id = ci.product_id
JOIN orders_table o ON ci.order_id = o.order_id
JOIN signup s ON o.signupid = s.signupid
LEFT JOIN categories_table c ON p.category_id = c.category_id
LEFT JOIN subcategories sub ON p.subcategory_id = sub.id
WHERE p.seller_id = ?
ORDER BY o.created_at DESC";

$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Previous styles remain the same */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(145deg, rgb(179, 69, 10), rgb(199, 89, 30));
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .order-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }

        .payment-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .payment-pending { background: #fff3cd; color: #856404; }
        .payment-completed { background: #d4edda; color: #155724; }
        .payment-failed { background: #f8d7da; color: #721c24; }

        .category-tag {
            display: inline-block;
            padding: 3px 8px;
            background: #e9ecef;
            border-radius: 12px;
            font-size: 11px;
            color: #495057;
            margin-top: 5px;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-orders i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="section">
            <h2>Orders Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>Total Orders</h3>
                    <p><?php echo $stats['total_orders'] ?? 0; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-box"></i>
                    <h3>Items Sold</h3>
                    <p><?php echo $stats['total_items_sold'] ?? 0; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-rupee-sign"></i>
                    <h3>Total Revenue</h3>
                    <p>₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3>Unique Customers</h3>
                    <p><?php echo $stats['unique_customers'] ?? 0; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <h3>Pending Orders</h3>
                    <p><?php echo $stats['pending_orders'] ?? 0; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <h3>Completed Orders</h3>
                    <p><?php echo $stats['completed_orders'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Order History</h2>
            
            <div class="order-filters">
                <select class="filter-select" id="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                
                <select class="filter-select" id="dateFilter">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>

            <?php if ($orders->num_rows > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Order Details</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                                     class="product-image">
                                <div>
                                    <div><?php echo htmlspecialchars($order['product_name']); ?></div>
                                    <div class="category-tag">
                                        <?php echo htmlspecialchars($order['category_name'] . ' / ' . $order['subcategory_name']); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></div>
                                <div><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></div>
                                <div><strong>Quantity:</strong> <?php echo $order['quantity']; ?></div>
                                <div>
                                    <span class="payment-badge payment-<?php echo strtolower($order['payment_status']); ?>">
                                        Payment: <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></div>
                                <div class="customer-info">
                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['customer_email']); ?></div>
                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                                    <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['shipping_address']); ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td>
                                <div><strong>₹<?php echo number_format($order['subtotal'], 2); ?></strong></div>
                                <div class="customer-info">
                                    <div>Unit: ₹<?php echo number_format($order['price'], 2); ?></div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-box-open"></i>
                    <h3>No Orders Yet</h3>
                    <p>You haven't received any orders for your products yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <a href="sellerdashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script>
        // Filter functionality
        document.getElementById('statusFilter').addEventListener('change', filterOrders);
        document.getElementById('dateFilter').addEventListener('change', filterOrders);

        function filterOrders() {
            const statusFilter = document.getElementById('statusFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const rows = document.querySelectorAll('.orders-table tbody tr');
            
            rows.forEach(row => {
                const status = row.querySelector('.status-badge').textContent.trim().toLowerCase();
                const date = new Date(row.querySelector('td:nth-child(2)').textContent.split('Date:')[1]);
                let showRow = true;
                
                if (statusFilter && !status.includes(statusFilter)) {
                    showRow = false;
                }
                
                if (dateFilter) {
                    const now = new Date();
                    if (dateFilter === 'today' && date.toDateString() !== now.toDateString()) {
                        showRow = false;
                    } else if (dateFilter === 'week' && (now - date) > 7 * 24 * 60 * 60 * 1000) {
                        showRow = false;
                    } else if (dateFilter === 'month' && 
                             (date.getMonth() !== now.getMonth() || 
                              date.getFullYear() !== now.getFullYear())) {
                        showRow = false;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }
    </script>
</body>
</html>