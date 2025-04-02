<?php
session_start();
require 'config.php'; // Your database connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user type from database
$user_type = 'user'; // default
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT reg_type FROM signup WHERE signupid = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $user_type = $user_data['reg_type'];
    }
    $stmt->close();
}

// Get favorited products for the current user
$favorited_products = [];
if (isset($_SESSION['user_id'])) {
    // First get user_id from user_table
    $user_query = "SELECT user_id FROM user_table WHERE signupid = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user_result = $stmt->get_result();
    
    if ($user_result->num_rows > 0) {
        $user_id = $user_result->fetch_assoc()['user_id'];
        
        // Then get favorited products
        $favorites_query = "SELECT product_id FROM user_favorites WHERE user_id = ?";
        $stmt = $conn->prepare($favorites_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $favorites_result = $stmt->get_result();
        
        while ($row = $favorites_result->fetch_assoc()) {
            $favorited_products[] = $row['product_id'];
        }
    }
    $stmt->close();
}

// Fetch all products from the database
$product_query = "
    SELECT p.*, c.name AS category_name 
    FROM product_table p
    LEFT JOIN categories_table c ON p.category_id = c.category_id
    WHERE p.status = 1
    ORDER BY p.created_at DESC
";

try {
    $result = $conn->query($product_query);
} catch (Exception $e) {
    die("Query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs - Baby Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #e6ebfa;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: linear-gradient(to right, #ffffff, #f8f9fa);
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
            text-align: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: color 0.3s ease;
            margin: 0 auto;
        }
        
        .logo:hover {
            color: #0077cc;
        }
        
        .search-container {
            display: flex;
            flex-grow: 1;
            justify-content: center;
            margin: 0 auto;
            max-width: 600px;
            position: relative;
        }
        
        .search-container input {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid #e0e0e0;
            border-radius: 30px;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .search-container form {
            width: 100%;
            display: flex;
            position: relative;
        }
        
        .search-btn {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            padding: 0 25px;
            background-color: #0077cc;
            color: white;
            border: none;
            border-radius: 0 30px 30px 0;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background-color: #005fa3;
        }
        
        .search-container input[type="text"] {
            width: 100%;
            padding-right: 100px;
        }
        
        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 22px;
            color: #2c3e50;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .icon-btn:hover {
            color: #0077cc;
            background-color: rgba(0,119,204,0.1);
            transform: translateY(-2px);
        }
        
        .user-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6c5ce7, #8e44ad);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(108,92,231,0.3);
        }
        
        .user-icon:hover {
            transform: scale(1.1);
        }
        
        .category-nav {
            display: flex;
            justify-content: center;
            background: white;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            text-align: center;
        }
        
        .category-nav::-webkit-scrollbar {
            height: 4px;
        }
        
        .category-nav::-webkit-scrollbar-thumb {
            background: #0077cc;
            border-radius: 4px;
        }
        
        .category-nav button {
            padding: 8px 20px;
            margin: 0 8px;
            background-color: white;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            color: #2c3e50;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .category-nav button:hover {
            background-color: #0077cc;
            border-color: #0077cc;
            color: white;
            transform: translateY(-2px);
        }
        
        .product-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .product-card {
            position: relative;
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }
        
        .product-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .like-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10;
            transition: all 0.2s ease;
        }
        
        .like-btn i {
            font-size: 16px;
            color: #ccc;
            transition: all 0.2s ease;
        }
        
        .like-btn.active i {
            color: #ff5252;
        }
        
        .like-btn:hover {
            transform: scale(1.1);
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #0077cc;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .discount {
            font-size: 13px;
            font-weight: normal;
            color: #ff5252;
        }
        
        .product-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .cart-buy-buttons {
            display: flex;
            gap: 10px;
        }
        
        .action-button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        
        .add-to-cart {
            background-color: #0077cc;
            color: white;
        }
        
        .add-to-cart:hover {
            background-color: #005fa3;
        }
        
        .out-of-stock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 5;
        }
        
        .out-of-stock-text {
            background-color: #ff5252;
            color: white;
            padding: 5px 15px;
            border-radius: 4px;
            font-weight: bold;
            transform: rotate(-15deg);
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        
        .stock-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            z-index: 5;
        }
        
        .in-stock {
            background-color: #4CAF50;
            color: white;
        }
        
        .low-stock {
            background-color: #FFC107;
            color: #333;
        }
        
        .out-of-stock {
            background-color: #F44336;
            color: white;
        }
        
        .unavailable-button {
            background-color: #e0e0e0;
            color: #9e9e9e;
            cursor: not-allowed;
        }
        
        /* Grayscale filter for out of stock products */
        .product-card.out-of-stock img {
            filter: grayscale(100%);
        }
        
        /* Existing modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        
        .close-btn {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .product-container {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                padding: 15px;
            }
            
            .header {
                padding: 15px;
                flex-direction: column;
                gap: 15px;
            }
            
            .search-container {
                width: 100%;
            }
            
            .nav-links {
                width: 100%;
                justify-content: space-around;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" style="text-decoration: none; margin: 0 auto;">
            <div class="logo">BabyCubs</div>
        </a>
        
        <div class="search-container">
            <form action="search.php" method="GET">
                <input type="text" name="query" placeholder="Search products">
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>
        
        <div class="nav-links">
            <a href="index.php" style="text-decoration: none;">
                <button class="icon-btn" title="Home">
                    <i class="fas fa-home"></i>
                </button>
            </a>
            <a href="favorites.php" style="text-decoration: none;">
                <button class="icon-btn" title="Favorites">
                    <i class="fas fa-heart"></i>
                </button>
            </a>
            <a href="profile.php" style="text-decoration: none;">
                <div class="user-icon" title="Profile">
                    <?php 
                    if(isset($_SESSION['username'])) {
                        echo substr($_SESSION['username'], 0, 1);
                    } else {
                        echo "P"; 
                    }
                    ?>
                </div>
            </a>
            <a href="cart.php" style="text-decoration: none;">
                <button class="icon-btn" title="Cart">
                    <i class="fas fa-shopping-cart"></i>
                </button>
            </a>
            <a href="logout.php" style="text-decoration: none;">
                <button class="icon-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </a>
        </div>
    </div>
    
    <div class="category-nav">
        <?php
        $cat_query = "SELECT * FROM categories_table ORDER BY name";
        $cat_result = $conn->query($cat_query);
        
        if ($cat_result && $cat_result->num_rows > 0) {
            while ($cat_row = $cat_result->fetch_assoc()) {
                echo '<a href="category.php?id='.$cat_row['category_id'].'" style="text-decoration: none;">';
                echo '<button>'.htmlspecialchars($cat_row['name']).'</button>';
                echo '</a>';
            }
        }
        ?>
    </div>
    
    <div class="product-container">
        <?php 
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
                $discount_percentage = rand(10, 60);
                $original_price = $row['price'];
                $stock_status = '';
                $is_out_of_stock = false;
                
                // Check stock status
                if (isset($row['stock_quantity'])) {
                    if ($row['stock_quantity'] <= 0) {
                        $stock_status = 'out-of-stock';
                        $is_out_of_stock = true;
                    } elseif ($row['stock_quantity'] <= 5) {
                        $stock_status = 'low-stock';
                    } else {
                        $stock_status = 'in-stock';
                    }
                }
                ?>
                
                <div class="product-card <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                    <div class="product-image">
                        <button class="like-btn <?php echo in_array($row['product_id'], $favorited_products) ? 'active' : ''; ?>" 
                                onclick="toggleLike(this, <?php echo $row['product_id']; ?>)">
                            <i class="fas fa-heart"></i>
                        </button>
                        
                        <?php if ($stock_status): ?>
                        <div class="stock-badge <?php echo $stock_status; ?>">
                            <?php 
                                if ($stock_status === 'out-of-stock') echo 'Out of Stock';
                                elseif ($stock_status === 'low-stock') echo 'Low Stock';
                                else echo 'In Stock';
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($is_out_of_stock): ?>
                        <div class="out-of-stock-overlay">
                            <div class="out-of-stock-text">OUT OF STOCK</div>
                        </div>
                        <?php endif; ?>
                        
                        <a href="product_details.php?id=<?php echo $row['product_id']; ?>">
                            <?php if (!empty($row['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <?php else: ?>
                                <img src="images/placeholder.jpg" alt="No Image Available">
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="product-info">
                        <div class="product-title">
                            <a href="product_details.php?id=<?php echo $row['product_id']; ?>" style="text-decoration: none; color: #2c3e50;">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </a>
                        </div>
                        <div class="product-price">
                            ₹<?php echo number_format($row['price'], 2); ?>
                            <span class="discount">(<?php echo $discount_percentage; ?>% off)</span>
                        </div>
                        <div class="product-buttons">
                            <div class="cart-buy-buttons">
                                <?php if ($user_type === 'user'): ?>
                                    <?php if (!$is_out_of_stock): ?>
                                    <form action="cart.php" method="POST" style="flex: 1;">
                                        <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="action-button add-to-cart">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <button type="button" class="action-button unavailable-button" disabled>
                                            <i class="fas fa-shopping-cart"></i> Out of Stock
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button type="button" class="action-button add-to-cart" onclick="showUserOnlyMessage()">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
            }
        } else { 
        ?>
            <div class="no-products">
                <h3>No products found</h3>
                <p>We couldn't find any products in our database. Please check back later!</p>
            </div>
        <?php 
        } 
        ?>
    </div>
    
    <!-- Modal for showing message -->
    <div id="userOnlyModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3>Purchase Restricted</h3>
            <p>Only regular users can purchase products. Sellers and admins cannot add items to cart.</p>
            <button onclick="closeModal()" style="margin-top: 15px; padding: 8px 20px; background: #0077cc; color: white; border: none; border-radius: 5px; cursor: pointer;">OK</button>
        </div>
    </div>
    
    <script>
        // Function to show the modal
        function showUserOnlyMessage() {
            document.getElementById('userOnlyModal').style.display = 'block';
        }
        
        // Function to close the modal
        function closeModal() {
            document.getElementById('userOnlyModal').style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('userOnlyModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        function toggleLike(button, productId) {
            // Show loading state
            button.disabled = true;
            const icon = button.querySelector('i');
            const originalClass = icon.classList.contains('fa-heart') ? 'fa-heart' : 'fa-heart-o';
            icon.classList.replace(originalClass, 'fa-spinner');
            icon.classList.add('fa-spin');
            
            fetch('update_favorites.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&action=${button.classList.contains('active') ? 'remove' : 'add'}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
            button.classList.toggle('active');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update favorites: ' + error.message);
            })
            .finally(() => {
                button.disabled = false;
                icon.classList.remove('fa-spin');
                icon.classList.replace('fa-spinner', 'fa-heart');
            });
        }
    </script>
    
    <?php
    // Close the connection
    $conn->close();
    ?>
</body>
</html>