<?php
// Include database connection
require_once 'db.php';

// Check if the user is logged in and get seller_id from session
session_start();
if (!isset($_SESSION['seller_id'])) {
    die("Unauthorized access. Please log in.");
}
$seller_id = $_SESSION['seller_id'];

// Handle product deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $product_id = intval($_GET['delete']);

    // Fetch the image path before deleting the product
    $image_query = "SELECT image_url FROM product_table WHERE product_id = ? AND seller_id = ?";
    $stmt = $conn->prepare($image_query);
    $stmt->bind_param("ii", $product_id, $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = $row['image_url'];

        // Delete the image file if it exists
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        // Delete the product from the database
        $delete_query = "DELETE FROM product_table WHERE product_id = ? AND seller_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $product_id, $seller_id);

        if ($stmt->execute()) {
            echo "<script>alert('Product deleted successfully!');</script>";
        } else {
            echo "<script>alert('Error deleting product: " . $stmt->error . "');</script>";
        }
    }
}

// Fetch all products for the logged-in seller
$query = "SELECT p.*, c.name AS category_name, s.subcategory_name 
          FROM product_table p
          JOIN categories_table c ON p.category_id = c.category_id
          JOIN subcategories s ON p.subcategory_id = s.id
          WHERE p.seller_id = ?
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - BabyCubs Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        :root {
            --primary-color: #ff69b4;    /* Updated to match pink theme */
            --secondary-color: #f8bbd0;   /* Light pink */
            --text-color: #333;
            --sidebar-width: 260px;
            --success-color: #00b894;
            --danger-color: #d63031;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        /* Updated Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: white;      /* Changed to white */
            position: fixed;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .sidebar-header h1 {
            color: var(--primary-color);  /* Changed to pink */
            font-size: 24px;
            font-weight: bold;
        }
        
        .sidebar-header h1 i {
            margin-right: 10px;
            font-size: 28px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            color: var(--text-color);     /* Changed to dark text */
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: var(--secondary-color);
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }
        
        .sidebar-menu i {
            margin-right: 12px;
            font-size: 18px;
            width: 25px;
            text-align: center;
        }
        
        .content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .content-header h2 {
            font-size: 24px;
            color: var(--dark-color);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        /* Updated Button Styles */
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #ff4da6;    /* Darker pink */
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #00a382;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c12e2e;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .product-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #eee;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: var(--secondary-color);
        }
        
        .product-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-status {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--success-color);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .product-status.inactive {
            background-color: var(--danger-color);
        }
        
        .product-details {
            padding: 15px;
        }
        
        .product-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .product-category {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .product-price {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .product-stock {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .product-actions {
            display: flex;
            justify-content: space-between;
        }
        
        .product-actions a {
            padding: 8px 12px;
            font-size: 14px;
        }
        
        .empty-state {
            background-color: white;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }

        /* Confirmation dialog styling */
        .delete-confirm {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }
        
        .delete-confirm-box {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            max-width: 400px;
            text-align: center;
            border: 1px solid var(--secondary-color);
        }
        
        .delete-confirm-box h3 {
            margin-bottom: 15px;
        }
        
        .delete-confirm-box p {
            margin-bottom: 20px;
            color: #666;
        }
        
        .delete-confirm-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h1><i class="fas fa-baby"></i> BabyCubs</h1>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="sellerdashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="product manage.php"><i class="fas fa-tags"></i> Add Products</a></li>
            <li><a href="products.php" class="active"><i class="fas fa-box"></i> My Products</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="content">
        <div class="content-header">
            <h2>My Products</h2>
            <a href="product manage.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
        <div class="product-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                    <div class="product-status <?php echo $row['status'] ? '' : 'inactive'; ?>">
                        <?php echo $row['status'] ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>
                <div class="product-details">
                    <h3 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                    <div class="product-category">
                        <span><?php echo htmlspecialchars($row['category_name']); ?> > <?php echo htmlspecialchars($row['subcategory_name']); ?></span>
                    </div>
                    <div class="product-price">₹<?php echo number_format($row['price'], 2); ?></div>
                    <div class="product-stock">
                        <span>
                            <?php 
                            // Add visual indicator for stock levels
                            if ($row['stock_quantity'] <= 0) {
                                echo '<span style="color: #e74c3c; font-weight: bold;"><i class="fas fa-exclamation-circle"></i> Out of Stock</span>';
                            } elseif ($row['stock_quantity'] <= 5) {
                                echo '<span style="color: #e67e22; font-weight: bold;"><i class="fas fa-exclamation-triangle"></i> Low Stock: ' . $row['stock_quantity'] . ' units</span>';
                            } else {
                                echo '<span style="color: #27ae60;"><i class="fas fa-check-circle"></i> In Stock: ' . $row['stock_quantity'] . ' units</span>';
                            }
                            ?>
                        </span>
                        <span>Brand: <?php echo htmlspecialchars($row['brand']); ?></span>
                    </div>
                    <div class="product-actions">
                        <a href="edit_product.php?id=<?php echo $row['product_id']; ?>" class="btn btn-success"><i class="fas fa-edit"></i> Edit</a>
                        <a href="update_stock.php?id=<?php echo $row['product_id']; ?>" class="btn btn-primary"><i class="fas fa-boxes"></i> Update Stock</a>
                        <a href="#" class="btn btn-danger delete-btn" data-id="<?php echo $row['product_id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>"><i class="fas fa-trash"></i> Delete</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>No Products Found</h3>
            <p>You haven't added any products yet. Start selling by adding your first product.</p>
            <a href="product manage.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Your First Product</a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Delete Confirmation Dialog -->
    <div class="delete-confirm" id="deleteConfirm">
        <div class="delete-confirm-box">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete the product "<span id="productName"></span>"? This action cannot be undone.</p>
            <div class="delete-confirm-actions">
                <button class="btn btn-primary" id="cancelDelete">Cancel</button>
                <a href="#" class="btn btn-danger" id="confirmDelete">Delete</a>
            </div>
        </div>
    </div>
    
    <script>
        // Delete confirmation dialog
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const deleteConfirm = document.getElementById('deleteConfirm');
        const productNameSpan = document.getElementById('productName');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-id');
                const productName = this.getAttribute('data-name');
                
                productNameSpan.textContent = productName;
                confirmDeleteBtn.href = `products.php?delete=${productId}`;
                deleteConfirm.style.display = 'flex';
            });
        });
        
        cancelDeleteBtn.addEventListener('click', function() {
            deleteConfirm.style.display = 'none';
        });
        
        // Close dialog when clicking outside
        deleteConfirm.addEventListener('click', function(e) {
            if (e.target === deleteConfirm) {
                deleteConfirm.style.display = 'none';
            }
        });
    </script>
</body>
</html>