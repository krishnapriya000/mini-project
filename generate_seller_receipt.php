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

// Generate a printable HTML report
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Report: <?php echo htmlspecialchars($seller['business_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f9f9f9;
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .report-container {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        h2 {
            color: #444;
            margin-top: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            margin-right: 8px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 10px 0 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f5f5f5;
            font-weight: bold;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .print-controls {
            display: flex;
            justify-content: center;
            margin: 30px 0 10px;
        }
        
        .print-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        @media print {
            .print-controls {
                display: none;
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
    </style>
</head>
<body>
    <div class="report-container">
        <h1>Seller Report: <?php echo htmlspecialchars($seller['business_name']); ?></h1>
        
        <h2>Seller Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Seller Name:</span>
                <span><?php echo htmlspecialchars($seller['seller_name']); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Business Name:</span>
                <span><?php echo htmlspecialchars($seller['business_name']); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span><?php echo htmlspecialchars($seller['email']); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Phone:</span>
                <span><?php echo htmlspecialchars($seller['phone']); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span><?php echo ucfirst(htmlspecialchars($seller['status'])); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Registration Date:</span>
                <span><?php echo date('F j, Y', strtotime($seller['registration_date'])); ?></span>
            </div>
        </div>
        
        <h2>Performance Summary</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div>Total Products</div>
                <div class="stat-value"><?php echo $stats['total_products']; ?></div>
            </div>
            
            <div class="stat-card">
                <div>Items Sold</div>
                <div class="stat-value"><?php echo $stats['total_items_sold'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <div>Total Revenue</div>
                <div class="stat-value">₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
            </div>
        </div>
        
        <h2>Product Inventory</h2>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>In Stock</th>
                    <th>Times Sold</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Reset pointer to the beginning of products
                $products->data_seek(0);
                
                while ($product = $products->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category_name'] . '/' . $product['subcategory_name']); ?></td>
                    <td>₹<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo $product['stock_quantity']; ?></td>
                    <td><?php echo $product['times_sold']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div class="print-controls">
            <button class="print-btn" onclick="window.print()">Print Report</button>
        </div>
    </div>
    
    <script>
        // Auto-print when the page loads
        window.onload = function() {
            // Add a small delay to make sure everything is rendered
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>
<?php
// No need to close the PHP tag 