<?php
session_start();
require 'config.php'; // Your database connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if category ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: product_view.php");
    exit();
}

$category_id = (int)$_GET['id'];

// Get the category name
$category_query = "SELECT name FROM categories_table WHERE category_id = ?";
$stmt = $conn->prepare($category_query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category_result = $stmt->get_result();

if ($category_result->num_rows === 0) {
    header("Location: product_view.php");
    exit();
}

$category_name = $category_result->fetch_assoc()['name'];
$stmt->close();

// Fetch products from the selected category
$product_query = "
    SELECT p.*, c.name AS category_name 
    FROM product_table p
    LEFT JOIN categories_table c ON p.category_id = c.category_id
    WHERE p.category_id = ? AND p.status = 1
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Prepare to check which products are favorited by the user
$favorited_products = [];
if (isset($_SESSION['user_id'])) {
    $favorites_query = "SELECT product_id FROM user_favorites WHERE user_id = ?";
    $stmt = $conn->prepare($favorites_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $favorites_result = $stmt->get_result();
    
    while ($row = $favorites_result->fetch_assoc()) {
        $favorited_products[] = $row['product_id'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs - <?php echo htmlspecialchars($category_name); ?> Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', Arial, sans-serif;
        }

        body {
            background: linear-gradient(to bottom, #f8f9fa, #e6ebfa);
            min-height: 100vh;
        }

        /* Enhanced Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(45deg, #0077cc, #1a8cff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        /* Search Container Improvements */
        .search-container {
            flex-grow: 1;
            max-width: 600px;
            margin: 0 40px;
        }

        .search-container form {
            display: flex;
            position: relative;
            width: 100%;
        }

        .search-container input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 30px;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .search-container input:focus {
            border-color: #0077cc;
            box-shadow: 0 0 15px rgba(0,119,204,0.1);
        }

        .search-btn {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            padding: 0 25px;
            background: linear-gradient(45deg, #0077cc, #1a8cff);
            color: white;
            border: none;
            border-radius: 0 30px 30px 0;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: linear-gradient(45deg, #005fa3, #0066cc);
        }

        /* Navigation Links Enhancement */
        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 22px;
            color: #2c3e50;
            padding: 10px;
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
            background: linear-gradient(45deg, #6c5ce7, #8e44ad);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108,92,231,0.3);
        }

        .user-icon:hover {
            transform: scale(1.1);
        }

        /* Category Navigation Enhancement */
        .category-nav {
            display: flex;
            justify-content: center;
            background: white;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 30px;
        }

        .category-nav::-webkit-scrollbar {
            height: 3px;
        }

        .category-nav::-webkit-scrollbar-thumb {
            background: #0077cc;
            border-radius: 10px;
        }

        .category-nav button {
            padding: 10px 25px;
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
            border-color: #0077cc;
            color: #0077cc;
            transform: translateY(-2px);
        }

        .category-nav button.active {
            background: linear-gradient(45deg, #0077cc, #1a8cff);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(0,119,204,0.3);
        }

        /* Category Title Enhancement */
        .category-title {
            text-align: center;
            margin: 30px 0;
            color: #2c3e50;
            font-size: 32px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            padding-bottom: 15px;
        }

        .category-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(45deg, #0077cc, #1a8cff);
            border-radius: 3px;
        }

        /* Product Container Enhancement */
        .product-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            padding: 40px;
            margin: 0 auto;
            max-width: 1400px;
        }

        /* Product Card Enhancement */
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .product-image {
            position: relative;
            height: 250px;
            background: #f8f9fa;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            max-width: 100%;
            max-height: 220px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 20px;
            background: white;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-title {
            font-weight: 600;
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.4;
            height: 45px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            text-align: center;
        }

        .product-price {
            color: #e74c3c;
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .discount {
            color: #27ae60;
            font-size: 14px;
            font-weight: 600;
            background-color: #e8f5e9;
            padding: 4px 10px;
            border-radius: 12px;
        }

        .like-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            border: 2px solid #ff69b4;
            color: #ff69b4;
            padding: 8px;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 2;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .like-btn:hover {
            transform: scale(1.1);
        }

        .like-btn.active {
            background: #ff69b4;
            color: white;
        }

        .product-buttons {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }

        .action-button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .add-to-cart {
            background: linear-gradient(45deg, #0077cc, #1a8cff);
            color: white;
        }

        .buy-now {
            background: linear-gradient(45deg, #00b894, #00a382);
            color: white;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* No Products Message Enhancement */
        .no-products {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 15px;
            width: 100%;
            max-width: 800px;
            margin: 40px auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .no-products h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .no-products p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .product-container {
                padding: 20px;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }

            .search-container {
                margin: 0 15px;
            }

            .category-title {
                font-size: 24px;
            }

            .product-card {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .logo {
                font-size: 24px;
            }

            .nav-links {
                gap: 15px;
            }

            .user-icon {
                width: 35px;
                height: 35px;
            }

            .category-nav button {
                padding: 8px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<div class="header">
        <a href="index.php" style="text-decoration: none;">
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
        // Fetch categories for the navigation
        $cat_query = "SELECT * FROM categories_table ORDER BY name";
        $cat_result = $conn->query($cat_query);
        
        if ($cat_result && $cat_result->num_rows > 0) {
            while ($cat_row = $cat_result->fetch_assoc()) {
                $active_class = ($cat_row['category_id'] == $category_id) ? 'active' : '';
                
                echo '<a href="category.php?id='.$cat_row['category_id'].'" style="text-decoration: none;">';
                echo '<button class="'.$active_class.'">'.htmlspecialchars($cat_row['name']).'</button>';
                echo '</a>';
            }
        }
        ?>
    </div>
    
    <h1 class="category-title"><?php echo htmlspecialchars($category_name); ?> Products</h1>
    
    <div class="product-container">
        <?php 
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
                $discount_percentage = rand(10, 60);
                $is_favorited = in_array($row['product_id'], $favorited_products);
                ?>
                
                <div class="product-card">
                    <div class="product-image">
                        <button class="like-btn <?php echo $is_favorited ? 'active' : ''; ?>" 
                                onclick="toggleLike(this, <?php echo $row['product_id']; ?>)">
                            <i class="fas fa-heart"></i>
                        </button>
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
                        <div class="product-title"><?php echo htmlspecialchars($row['name']); ?></div>
                        <div class="product-price">
                            ₹<?php echo number_format($row['price'], 2); ?>
                            <span class="discount">(<?php echo $discount_percentage; ?>% off)</span>
                        </div>
                        <div class="product-buttons">
                            <form action="cart.php" method="POST" style="flex: 1;">
                                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="action-button add-to-cart">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </form>
                            <!-- <form action="cart.php" method="POST" style="flex: 1;">
                                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="action-button buy-now">
                                    <i class="fas fa-bolt"></i> Buy Now
                                </button>
                            </form> -->
                        </div>
                    </div>
                </div>
                
            <?php 
            }
        } else { 
        ?>
            <div class="no-products">
                <h3>No products found in this category</h3>
                <p>We couldn't find any products in the "<?php echo htmlspecialchars($category_name); ?>" category. Please check back later or browse other categories!</p>
            </div>
        <?php 
        } 
        ?>
    </div>
    
    <?php
    $conn->close();
    ?>

<script>
function toggleLike(button, productId) {
    const isActive = button.classList.contains('active');
    
    // Show loading state
    button.disabled = true;
    const icon = button.querySelector('i');
    icon.className = 'fas fa-spinner fa-spin';
    
    fetch('update_favorites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&action=${isActive ? 'remove' : 'add'}`
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
    })
    .then(data => {
        if (data.error) throw new Error(data.error);
        button.classList.toggle('active');
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    })
    .finally(() => {
        button.disabled = false;
        icon.className = 'fas fa-heart';
    });
}
</script>
</body>
</html>