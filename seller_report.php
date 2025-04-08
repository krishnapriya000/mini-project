<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if seller_id is provided
if (!isset($_GET['seller_id'])) {
    header("Location: admindashboard.php");
    exit();
}

$seller_id = $_GET['seller_id'];

// Fetch seller details
$seller_query = "SELECT s.*, sg.email, sg.phone 
                 FROM seller s
                 JOIN signup sg ON s.signupid = sg.signupid
                 WHERE s.seller_id = ?";

$stmt = $conn->prepare($seller_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();

// Fetch seller's products
$products_query = "SELECT p.*, c.name as category_name, s.subcategory_name,
                         (SELECT COUNT(*) FROM cart_items WHERE product_id = p.product_id) as times_sold
                  FROM product_table p
                  LEFT JOIN categories_table c ON p.category_id = c.category_id
                  LEFT JOIN subcategories s ON p.subcategory_id = s.id
                  WHERE p.seller_id = ?
                  ORDER BY p.created_at DESC";

$stmt = $conn->prepare($products_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$products = $stmt->get_result();

// Get total products and sales
$stats_query = "SELECT 
                COUNT(DISTINCT p.product_id) as total_products,
                SUM(ci.quantity) as total_items_sold,
                SUM(ci.quantity * ci.price) as total_revenue
                FROM product_table p
                LEFT JOIN cart_items ci ON p.product_id = ci.product_id
                WHERE p.seller_id = ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: rgb(248, 191, 125);
        }

        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .section h2 {
            color: rgb(179, 69, 10);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .seller-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgb(179, 69, 10);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }

        .stat-card p {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: bold;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .products-table th,
        .products-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .products-table th {
            background: rgb(179, 69, 10);
            color: white;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
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

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-inactive { background: #ffebee; color: #c62828; }
        .status-pending { background: #fff3e0; color: #ef6c00; }

        .download-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
        }
        
        .download-button {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        
        .download-button:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .download-button i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="section">
            <h2>Seller Information</h2>
            <div class="seller-info">
                <div>
                    <p><strong>Seller Name:</strong> <?php echo htmlspecialchars($seller['seller_name']); ?></p>
                    <p><strong>Business Name:</strong> <?php echo htmlspecialchars($seller['business_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($seller['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($seller['phone']); ?></p>
                </div>
                <div>
                    <p><strong>Status:</strong> 
                        <span class="status-badge status-<?php echo strtolower($seller['status']); ?>">
                            <?php echo ucfirst($seller['status']); ?>
                        </span>
                    </p>
                    <p><strong>Registration Date:</strong> <?php echo date('F j, Y', strtotime($seller['registration_date'])); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($seller['description']); ?></p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Performance Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <p><?php echo $stats['total_products']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Items Sold</h3>
                    <p><?php echo $stats['total_items_sold'] ?? 0; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <p>₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Products</h2>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Times Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="product-image">
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($product['category_name']); ?> / 
                                <?php echo htmlspecialchars($product['subcategory_name']); ?>
                            </td>
                            <td>₹<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock_quantity']; ?></td>
                            <td><?php echo $product['times_sold']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="download-section">
            <a href="generate_seller_receipt.php?seller_id=<?php echo $seller_id; ?>" class="download-button">
                <i class="fas fa-download"></i> Download Seller Report
            </a>
        </div>

        <a href="admindashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>