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
     $query = "SELECT p.*, c.name AS category_name 
               FROM product_table p
               JOIN user_favorites uf ON p.product_id = uf.product_id
               LEFT JOIN categories_table c ON p.category_id = c.category_id
               WHERE uf.user_id = ? AND p.status = 1
               ORDER BY uf.created_at DESC";
     
     $stmt = $conn->prepare($query);
     if (!$stmt) {
         throw new Exception("Prepare failed: " . $conn->error);
     }
     
     $stmt->bind_param("i", $userId);
     if (!$stmt->execute()) {
         throw new Exception("Execute failed: " . $stmt->error);
     }
     
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
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
     <style>
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
     <!-- Include your header/navigation -->
     <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/baby/header.php'); ?>
     
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
                                 <form action="cart.php" method="POST" style="flex: 1;">
                                     <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                     <button type="submit" class="action-button add-to-cart">
                                         <i class="fas fa-shopping-cart"></i> Add to Cart
                                     </button>
                                 </form>
                                 <form action="checkout.php" method="POST" style="flex: 1;">
                                     <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                     <input type="hidden" name="quantity" value="1">
                                     <button type="submit" class="action-button buy-now">
                                         <i class="fas fa-bolt"></i> Buy Now
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