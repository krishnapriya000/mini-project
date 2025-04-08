<?php
session_start();
require_once 'db.php';

// Check if seller is logged in
if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit();
}

// Get seller information
$seller_id = $_SESSION['seller_id'];
$conn = new mysqli($servername, $username, $password, $database);

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get seller data
$seller_query = "SELECT s.*, sg.email, sg.phone 
                 FROM seller s 
                 JOIN signup sg ON s.signupid = sg.signupid 
                 WHERE s.seller_id = ?";

$stmt = $conn->prepare($seller_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $seller_id);
if (!$stmt->execute()) {
    die("Query execution failed: " . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die("Get result failed: " . $conn->error);
}

$seller_data = $result->fetch_assoc();
if (!$seller_data) {
    die("No seller data found for this seller ID.");
}

// Get earnings data
$earnings_query = "SELECT 
                    SUM(oi.subtotal) AS total_earnings,
                    COUNT(DISTINCT o.order_id) AS total_orders,
                    SUM(oi.quantity) AS total_items_sold
                   FROM order_items oi
                   JOIN orders_table o ON oi.order_id = o.order_id
                   JOIN product_table p ON oi.product_id = p.product_id
                   WHERE p.seller_id = ? AND o.payment_status = 'completed'";

$stmt = $conn->prepare($earnings_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$earnings_result = $stmt->get_result();
$earnings_data = $earnings_result->fetch_assoc();

// Check for column names in orders_table
$columns_query = "SHOW COLUMNS FROM orders_table";
$columns_result = $conn->query($columns_query);
$status_column = '';

if ($columns_result) {
    while ($column = $columns_result->fetch_assoc()) {
        if ($column['Field'] == 'status') {
            $status_column = 'status';
            break;
        } else if ($column['Field'] == 'status_name') {
            $status_column = 'status_name';
            break;
        } else if ($column['Field'] == 'order_status') {
            $status_column = 'order_status';
            break;
        }
    }
}

// If no status column found, use a placeholder
if (empty($status_column)) {
    $transactions_query = "SELECT 
                        o.order_id,
                        o.order_date,
                        oi.product_name,
                        oi.quantity,
                        oi.subtotal,
                        'pending' AS status
                       FROM order_items oi
                       JOIN orders_table o ON oi.order_id = o.order_id
                       JOIN product_table p ON oi.product_id = p.product_id
                       WHERE p.seller_id = ? AND o.payment_status = 'completed'
                       ORDER BY o.order_date DESC
                       LIMIT 5";
} else {
    $transactions_query = "SELECT 
                        o.order_id,
                        o.order_date,
                        oi.product_name,
                        oi.quantity,
                        oi.subtotal,
                        o.$status_column AS status
                       FROM order_items oi
                       JOIN orders_table o ON oi.order_id = o.order_id
                       JOIN product_table p ON oi.product_id = p.product_id
                       WHERE p.seller_id = ? AND o.payment_status = 'completed'
                       ORDER BY o.order_date DESC
                       LIMIT 5";
}

$stmt = $conn->prepare($transactions_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$transactions_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - BabyCubs</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        :root {
            --primary-color: #ff69b4;
            --secondary-color: #f8bbd0;
            --text-color: #333;
            --sidebar-width: 250px;
        }

        body {
            background-color: #f5f5f5;
        }

        /* Header Styles */
        .header {
            background-color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
        }

        .logo {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: bold;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 70px;
            width: var(--sidebar-width);
            height: calc(100vh - 70px);
            background-color: white;
            padding: 2rem 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar-item {
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-item:hover {
            background-color: var(--secondary-color);
            color: var(--primary-color);
        }

        .sidebar-item.active {
            background-color: var(--secondary-color);
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: 70px;
            padding: 2rem;
        }

        .dashboard-card {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .earnings-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .earnings-card {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .earnings-label {
            color: #666;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .earnings-value {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: bold;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .transactions-table th, 
        .transactions-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .transactions-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .status-pending {
            color: #ff9800;
            font-weight: bold;
        }

        .status-completed {
            color: #4caf50;
            font-weight: bold;
        }

        .btn-view-all {
            display: inline-block;
            margin-top: 1rem;
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-view-all:hover {
            background-color: #ff4081;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">BabyCubs</div>
        <div class="header-right">
            <a href="index.php" style="color: var(--text-color);"><i class="fas fa-home"></i></a>
            <div class="profile-icon">
                <a href="profile.php"> <?php echo strtoupper(substr($seller_data['seller_name'], 0, 1)); ?></a>
            </div>
            <a href="logout.php" style="color: var(--text-color);"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="sidebar">
        <a href="sellerdashboard.php" class="sidebar-item">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        <a href="product manage.php" class="sidebar-item">
            <i class="fas fa-plus"></i>
            Add Product
        </a>

        <a href="earnings.php" class="sidebar-item active">
            <i class="fas fa-dollar-sign"></i>
            Earnings
        </a>
        <a href="logout.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-card">
            <h2 style="margin-bottom: 2rem;">Earnings Overview</h2>
            
            <div class="earnings-grid">
                <div class="earnings-card">
                    <div class="earnings-label">Total Earnings</div>
                    <div class="earnings-value">₹<?php echo number_format($earnings_data['total_earnings'] ?? 0, 2); ?></div>
                </div>
                <div class="earnings-card">
                    <div class="earnings-label">Total Orders</div>
                    <div class="earnings-value"><?php echo $earnings_data['total_orders'] ?? 0; ?></div>
                </div>
                <div class="earnings-card">
                    <div class="earnings-label">Items Sold</div>
                    <div class="earnings-value"><?php echo $earnings_data['total_items_sold'] ?? 0; ?></div>
                </div>
            </div>

            <h3 style="margin: 2rem 0 1rem;">Recent Transactions</h3>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions_result->num_rows > 0): ?>
                        <?php while ($transaction = $transactions_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo substr($transaction['order_id'], 0, 8); ?>...</td>
                                <td><?php echo date('M d, Y', strtotime($transaction['order_date'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                <td><?php echo $transaction['quantity']; ?></td>
                                <td>₹<?php echo number_format($transaction['subtotal'], 2); ?></td>
                                <td class="status-<?php echo strtolower($transaction['status']); ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No transactions found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <a href="order.php" class="btn-view-all">View All Orders</a>
        </div>
    </main>

    <script>
        // Add active class to current sidebar item
        const currentPath = window.location.pathname;
        const sidebarItems = document.querySelectorAll('.sidebar-item');
        sidebarItems.forEach(item => {
            if (item.getAttribute('href') === currentPath.split('/').pop()) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    </script>
</body>
</html>