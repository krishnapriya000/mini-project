<?php
// include "header.php";
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Ensure database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}




// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
                $seller_id = $_POST['seller_id'];
                $category_id = $_POST['category_id'];
                $subcategory_id = $_POST['subcategory_id'];
                //$product_name = isset($_POST['product_name']) ? $_POST['product_name'] : '';
$price = isset($_POST['price']) ? $_POST['price'] : 0;
$stock_quantity = isset($_POST['stock_quantity']) ? $_POST['stock_quantity'] : 0;

                $description = $_POST['description'];
               
                $name = isset($_POST['name']) ? $_POST['name'] : '';
                $description = isset($_POST['description']) ? $_POST['description'] : '';
                $size = isset($_POST['size']) ? $_POST['size'] : '';
                $colour = isset($_POST['colour']) ? $_POST['colour'] : '';
                $brand = isset($_POST['brand']) ? $_POST['brand'] : '';
                $condition_type = isset($_POST['condition_type']) ? $_POST['condition_type'] : '';
                $image_url = isset($image_url) ? $image_url : ''; 
                
                
               
                
                $status = 1; // Active status

                // Handle image upload
                $upload_dir = "uploads/products/";

                // Check if directory exists, if not, create it
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true); // Creates directory with full permissions
                }
                
                $file_name = time() . '_' . basename($_FILES['image_url']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image_url']['tmp_name'], $target_file)) {
                    echo "File uploaded successfully.";
                } else {
                    echo "File upload failed.";
                }
                
                $stmt = $conn->prepare("INSERT INTO product_table 
                (seller_id, category_id, subcategory_id, name, description, price, stock_quantity, size, colour, brand, condition_type, image_url, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW());");
            
            $stmt->bind_param("iiissdisssssi", 
    $seller_id, 
    $category_id, 
    $subcategory_id, 
    $name, 
    $description, 
    $price, 
    $stock_quantity, 
    $size, 
    $colour, 
    $brand, 
    $condition_type, 
    $image_url, 
    $status 
);

        

                if ($stmt->execute()) {
                    $_SESSION['message'] = "Product added successfully";
                } else {
                    $_SESSION['error'] = "Error adding product: " . $stmt->error;
                }
                break;

            case 'update_product':
                $product_id = $_POST['product_id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $stock_quantity = $_POST['stock_quantity'];
                $size = $_POST['size'];
                $colour = $_POST['colour'];
                $brand = $_POST['brand'];
                $condition_type = $_POST['condition_type'];

                // Handle image update if new image is uploaded
                $image_sql = "";
                $image_param = "";
                if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === 0) {
                    $target_dir = "uploads/products/";
                    $image_url = time() . '_' . basename($_FILES["image_url"]["name"]);
                    $target_file = $target_dir . $image_url;
                    
                    if (move_uploaded_file($_FILES["image_url"]["tmp_name"], $target_file)) {
                        $image_sql = ", image_url = ?";
                        $image_param = $image_url;
                    }
                }

                $sql = "UPDATE product_table SET name = ?, description = ?, price = ?, stock_quantity = ?, size = ?, colour = ?, brand = ?, condition_type = ?" . $image_sql . " WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($image_param) {
                    $stmt->bind_param("iiissdisssssi", 
    $seller_id, 
    $category_id, 
    $subcategory_id, 
    $name, 
    $description, 
    $price, 
    $stock_quantity, 
    $size, 
    $colour, 
    $brand, 
    $condition_type, 
    $image_url, 
    $status
);
                } else {
                    $stmt->bind_param("iiissdisssssi", 
    $seller_id, 
    $category_id, 
    $subcategory_id, 
    $name, 
    $description, 
    $price, 
    $stock_quantity, 
    $size, 
    $colour, 
    $brand, 
    $condition_type, 
    $image_url, 
    $status
);
                }

                if ($stmt->execute()) {
                    $_SESSION['message'] = "Product updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating product: " . $stmt->error;
                }
                break;

            case 'delete_product':
                $product_id = $_POST['product_id'];
                $stmt = $conn->prepare("UPDATE product_table SET status = 0 WHERE product_id = ?");
                $stmt->bind_param("iiissdisssssi", 
    $seller_id, 
    $category_id, 
    $subcategory_id, 
    $name, 
    $description, 
    $price, 
    $stock_quantity, 
    $size, 
    $colour, 
    $brand, 
    $condition_type, 
    $image_url, 
    $status
);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Product deleted successfully";
                } else {
                    $_SESSION['error'] = "Error deleting product: " . $stmt->error;
                }
                break;
        }
    }
}

// Fetch all active categories
$categoriesQuery = "SELECT * FROM categories_table WHERE is_active = 1 ORDER BY name";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

// Fetch all active sellers
$sellersQuery = "SELECT * FROM seller WHERE status = 1 ORDER BY seller_name";
$sellersResult = mysqli_query($conn, $sellersQuery);

// Fetch all active products with related information
$productsQuery = "SELECT p.*, c.name as category_name, s.subcategory_name, sl.seller_name 
                 FROM product_table p 
                 JOIN categories_table c ON p.category_id = c.category_id 
                 JOIN subcategories s ON p.subcategory_id = s.id 
                 JOIN seller sl ON p.seller_id = sl.seller_id 
                 WHERE p.status = 1 
                 ORDER BY p.name";
$productsResult = mysqli_query($conn, $productsQuery);

if (!$productsResult) {
    die("Error fetching products: " . mysqli_error($conn));
}
$product['stock_quantity'] = isset($product['stock_quantity']) ? $product['stock_quantity'] : 0;

// Determine stock status text and CSS class
if ($product['stock_quantity'] > 0) {
    $stockText = "In Stock";
    $stockClass = "in-stock"; // You can use this class for styling
} else {
    $stockText = "Out of Stock";
    $stockClass = "out-of-stock";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: rgb(248, 191, 125); display: flex; }

        .sidebar {
            width: 250px;
            background: rgb(179, 69, 10);
            color: white;
            height: 100vh;
            padding: 20px;
            position: fixed;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            margin-right: 5px;
        }

        .btn-primary { background: #007bff; }
        .btn-danger { background: #dc3545; }
        .btn-edit { background: rgb(103, 157, 28); }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
        }

        .close {
            float: right;
            cursor: pointer;
            font-size: 24px;
        }

        .product-details {
            margin-top: 10px;
        }

        .product-details p {
            margin: 5px 0;
        }

        .stock-status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.9em;
        }

        .in-stock { background: #d4edda; color: #155724; }
        .low-stock { background: #fff3cd; color: #856404; }
        .out-of-stock { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">BabyCubs</div>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="sellerdashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="category_admin.php"><i class="fas fa-list"></i> Categories</a></li>
            <li><a href="product_admin.php"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Add Product Form -->
        <div class="card">
            <h2>Add New Product</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                

                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="category_select" required onchange="loadSubcategories()">
                        <option value="">Select Category</option>
                        <?php while ($category = mysqli_fetch_assoc($categoriesResult)): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Subcategory</label>
                    <select name="subcategory_id" id="subcategory_select" required>
                        <option value="">Select Category First</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="product_name" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Price</label>
                    <input type="number" name="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" required>
                </div>

                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" required>
                </div>

                <div class="form-group">
                    <label>Condition</label>
                    <select name="condition" required>
                        <option value="New">New</option>
                        <option value="Used">Used</option>
                        <option value="Refurbished">Refurbished</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image_url" accept="image/*" required>
                </div>

                <button type="submit" class="btn btn-primary">Add Product</button>
            </form>
        </div>

        <!-- Products Display -->
        <div class="card">
            <h2>Products</h2>
            <div class="product-grid">
                <?php while ($product = mysqli_fetch_assoc($productsResult)): ?>
                    <div class="product-card"><?php 
$product_name = isset($_POST['product_name']) ? $_POST['product_name'] : ''; // Default to empty string
$image_url = isset($image_url) ? $image_url : ''; // Ensure $image_url is set
?>

<img src="<?php echo htmlspecialchars($image_url); ?>" 
     alt="<?php echo htmlspecialchars($product_name); ?>" 
     class="product-image">


                        <div class="product-details">
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
                            <p><strong>Subcategory:</strong> <?php echo htmlspecialchars($product['subcategory_name']); ?></p>
                            <p><strong>Seller:</strong> <?php echo htmlspecialchars($product['seller_name']); ?></p>
                            <p><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
                            <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['brand']); ?></p>
                            <p><strong>Condition:</strong> <?php echo htmlspecialchars($product['condition_type']); ?></p>
                            
                            
                            <p><strong>Stock Status:</strong> 
    <span class="stock-status <?php echo $stockClass; ?>">
        <?php echo $stockText; ?> (<?php echo $product['stock_quantity']; ?>)
    </span>
</p>

                            
                            <div class="action-buttons">
                                <button onclick="showEditProductModal(<?php echo htmlspecialchars(json_encode($product)); ?>)" class="btn btn-edit">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editProductModal')">&times;</span>
            <h2>Edit Product</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_product">
                <input type="hidden" name="product_id" id="edit_product_id">
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="product_name" id="edit_product_name" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Price</label>
                    <input type="number" name="price" id="edit_price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" id="edit_stock" required>
                </div>

                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" id="edit_brand" required>
                </div>

                <div class="form-group">
                    <label>Condition</label>
                    <select name="condition" id="edit_condition" required>
                        <option value="New">New</option>
                        <option value="Used">Used</option>
                        <option value="Refurbished">Refurbished</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image_url" accept="image/*">
                    <small>Leave empty to keep current image</small>
                </div>

                <button type="submit" class="btn btn-primary">Update Product</button>
            </form>
        </div>
    </div>

    <script>
        // Function to load subcategories based on selected category
        function loadSubcategories() {
            const categoryId = document.getElementById('category_select').value;
            const subcategorySelect = document.getElementById('subcategory_select');
            
            // Clear current options
            subcategorySelect.innerHTML = '<option value="">Loading...</option>';
            
            if (categoryId) {
                fetch(`get_subcategories.php?category_id=${categoryId}`)
                    .then(response => response.json())
                    .then(data => {
                        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                        data.forEach(subcategory => {
                            subcategorySelect.innerHTML += `<option value="${subcategory.id}">${subcategory.subcategory_name}</option>`;
                        });
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                    });
            } else {
                subcategorySelect.innerHTML = '<option value="">Select Category First</option>';
            }
        }

        // Function to show edit product modal
        function showEditProductModal(product) {
            document.getElementById('edit_product_id').value = product.product_id;
            document.getElementById('edit_product_name').value = product.product_name;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_stock').value = product.stock;
            document.getElementById('edit_brand').value = product.brand;
            document.getElementById('edit_condition').value = product.condition_type;
            
            document.getElementById('editProductModal').style.display = 'block';
        }

        // Function to close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>