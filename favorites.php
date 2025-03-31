<?php
 session_start();
 require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');
 
 // Debug mode - set to false in production
 define('DEBUG_MODE', true);
 if (DEBUG_MODE) {
     error_reporting(E_ALL);
     ini_set('display_errors', 1);
 }
 
 // Redirect if not logged in
 if (!isset($_SESSION['user_id'])) {
     header("Location: login.php?redirect=favorites");
     exit();
 }
 
 $userId = $_SESSION['user_id'];
 
 // Get favorite products with error handling
 try {
     // First get the user_id from user_table
     $user_query = "SELECT user_id FROM user_table WHERE signupid = ?";
     $stmt = $conn->prepare($user_query);
     $stmt->bind_param("i", $_SESSION['user_id']);
     $stmt->execute();
     $user_result = $stmt->get_result();
     
     if (!$user_result->num_rows) {
         throw new Exception("User not found");
     }
     
     $user_id = $user_result->fetch_assoc()['user_id'];
     
     // Then get the favorite products
     $query = "SELECT p.*, c.name AS category_name 
               FROM product_table p
               JOIN user_favorites uf ON p.product_id = uf.product_id
               LEFT JOIN categories_table c ON p.category_id = c.category_id
               WHERE uf.user_id = ? AND p.status = 1
               ORDER BY uf.created_at DESC";
     
     $stmt = $conn->prepare($query);
     $stmt->bind_param("i", $user_id);
     $stmt->execute();
     $result = $stmt->get_result();
     $favoriteProducts = $result->fetch_all(MYSQLI_ASSOC);
     
 } catch (Exception $e) {
     $error = "Error loading favorites: " . $e->getMessage();
     if (DEBUG_MODE) {
         die($error); // Show error details in debug mode
     }
     // Log error and continue with empty list in production
     error_log($error);
     $favoriteProducts = [];
 }
 ?>
 
 <!DOCTYPE html>
 <html lang="en">
 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>My Favorites - BabyCubs</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <style>
         /* Add these header styles */
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
         
         body {
             font-family: 'Poppins', Arial, sans-serif;
             background: linear-gradient(to bottom, #f8f9fa, #e6ebfa);
             min-height: 100vh;
             margin: 0;
         }
         
         .container {
             max-width: 1200px;
             margin: 20px auto;
             padding: 20px;
         }
         
         .favorites-header {
             text-align: center;
             margin-bottom: 30px;
             padding: 20px;
             background: white;
             border-radius: 10px;
             box-shadow: 0 2px 10px rgba(0,0,0,0.05);
         }
         
         .favorites-results {
             display: grid;
             grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
             gap: 30px;
             padding: 20px;
         }
         
         .product-card {
             background: white;
             border-radius: 15px;
             overflow: hidden;
             box-shadow: 0 5px 20px rgba(0,0,0,0.08);
             transition: all 0.3s ease;
         }
         
         .product-card:hover {
             transform: translateY(-5px);
             box-shadow: 0 10px 25px rgba(0,0,0,0.1);
         }
         
         .product-image {
             position: relative;
             height: 250px;
             background: #f8f9fa;
             padding: 20px;
             display: flex;
             align-items: center;
             justify-content: center;
         }
         
         .product-image img {
             max-width: 100%;
             max-height: 100%;
             object-fit: contain;
             transition: transform 0.3s ease;
         }
         
         .product-card:hover .product-image img {
             transform: scale(1.05);
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
             cursor: pointer;
             z-index: 2;
             transition: all 0.3s ease;
         }
         
         .like-btn.active {
             background: #ff69b4;
             color: white;
         }
         
         .like-btn:hover {
             transform: scale(1.1);
         }
         
         .product-info {
             padding: 20px;
         }
         
         .product-title {
             font-weight: 600;
             font-size: 16px;
             color: #2c3e50;
             margin-bottom: 10px;
             height: 40px;
             overflow: hidden;
             display: -webkit-box;
             -webkit-line-clamp: 2;
             -webkit-box-orient: vertical;
         }
         
         .product-price {
             color: #e74c3c;
             font-weight: 700;
             font-size: 18px;
             margin: 10px 0;
         }
         
         .product-category {
             color: #7f8c8d;
             font-size: 14px;
             margin-bottom: 15px;
         }
         
         .product-buttons {
             display: flex;
             gap: 10px;
         }
         
         .action-button {
             flex: 1;
             padding: 10px;
             border: none;
             border-radius: 5px;
             cursor: pointer;
             font-weight: 600;
             font-size: 14px;
             transition: all 0.3s ease;
             text-align: center;
         }
         
         .add-to-cart {
             background: #3498db;
             color: white;
         }
         
         .buy-now {
             background: #2ecc71;
             color: white;
         }
         
         .action-button:hover {
             opacity: 0.9;
             transform: translateY(-2px);
         }
         
         .no-results {
             text-align: center;
             padding: 40px;
             background: white;
             border-radius: 10px;
             box-shadow: 0 2px 10px rgba(0,0,0,0.05);
             margin: 20px 0;
         }
         
         .no-results h3 {
             color: #2c3e50;
             margin-bottom: 15px;
         }
         
         .no-results p {
             color: #7f8c8d;
             margin-bottom: 20px;
         }
         
         .browse-btn {
             display: inline-block;
             padding: 10px 25px;
             background: #3498db;
             color: white;
             text-decoration: none;
             border-radius: 5px;
             font-weight: 600;
             transition: all 0.3s ease;
         }
         
         .browse-btn:hover {
             background: #2980b9;
             transform: translateY(-2px);
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
     
     <div class="container">
         <div class="favorites-header">
             <h1>My Favorite Products</h1>
             <p>Your saved items</p>
         </div>
         
         <?php if (!empty($favoriteProducts)): ?>
             <div class="favorites-results">
                 <?php foreach ($favoriteProducts as $product): ?>
                     <div class="product-card" id="product-<?php echo $product['product_id']; ?>">
                         <div class="product-image">
                             <button class="like-btn active" onclick="toggleLike(this, <?php echo $product['product_id']; ?>)">
                                 <i class="fas fa-heart"></i>
                             </button>
                             <a href="product_details.php?id=<?php echo $product['product_id']; ?>">
                                 <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'assets/images/placeholder.jpg'); ?>" 
                                      alt="<?php echo htmlspecialchars($product['name']); ?>"
                                      onerror="this.src='assets/images/placeholder.jpg'">
                             </a>
                         </div>
                         <div class="product-info">
                             <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
                             <?php if (!empty($product['category_name'])): ?>
                                 <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                             <?php endif; ?>
                             <div class="product-price">
                                 ₹<?php echo number_format($product['price'], 2); ?>
                             </div>
                             <div class="product-buttons">
                                 <form action="cart.php" method="POST" style="width: 100%;">
                                     <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                     <button type="submit" class="action-button add-to-cart">
                                         <i class="fas fa-shopping-cart"></i> Add to Cart
                                     </button>
                                 </form>
                             </div>
                         </div>
                     </div>
                 <?php endforeach; ?>
             </div>
         <?php else: ?>
             <div class="no-results">
                 <h3>You haven't liked any products yet</h3>
                 <p>Start browsing our products and click the heart icon to save your favorites!</p>
                 <a href="index.php" class="browse-btn">
                     Browse Products
                 </a>
             </div>
         <?php endif; ?>
     </div>
     
     <script>
     // Enhanced toggleLike function with loading states
     function toggleLike(button, productId) {
         const productCard = button.closest('.product-card');
         const wasActive = button.classList.contains('active');
         
         // Show loading state
         button.disabled = true;
         const icon = button.querySelector('i');
         icon.classList.replace('fa-heart', 'fa-spinner', 'fa-spin');
         
         fetch('update_favorites.php', {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/x-www-form-urlencoded',
             },
             body: `product_id=${productId}&action=${wasActive ? 'remove' : 'add'}`
         })
         .then(response => {
             if (!response.ok) {
                 throw new Error('Network response was not ok');
             }
             return response.json();
         })
         .then(data => {
             if (data.error) {
                 throw new Error(data.error);
             }
             
             if (wasActive) {
                 // Remove product card when unliking
                 productCard.style.opacity = '0';
                 setTimeout(() => {
                     productCard.remove();
                     
                     // Check if no favorites left
                     if (!document.querySelector('.product-card')) {
                         document.querySelector('.favorites-results').innerHTML = `
                             <div class="no-results">
                                 <h3>You haven't liked any products yet</h3>
                                 <p>Start browsing our products and click the heart icon to save your favorites!</p>
                                 <a href="index.php" class="browse-btn">
                                     Browse Products
                                 </a>
                             </div>
                         `;
                     }
                 }, 300);
             } else {
                 // Just update button state when liking from favorites page
                 button.classList.toggle('active');
             }
         })
         .catch(error => {
             console.error('Error:', error);
             alert('Failed to update favorites: ' + error.message);
             button.classList.toggle('active');
         })
         .finally(() => {
             button.disabled = false;
             icon.classList.replace('fa-spinner', 'fa-spin', 'fa-heart');
         });
     }
     </script>
 </body>
 </html>