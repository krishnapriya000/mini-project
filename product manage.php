<?php
// Include database connection
require_once 'db.php';
session_start(); // Start the session to access session variables

// Check if the user is logged in
if (!isset($_SESSION['seller_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Get seller_id from session
$seller_id = $_SESSION['seller_id'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate category_id
    if (empty($_POST['category_id'])) {
        echo "<script>alert('Category is required.');</script>";
        exit();
    }

    // Get form data
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];
    $nested_subcategory_id = isset($_POST['nested_subcategory_id']) && !empty($_POST['nested_subcategory_id']) ? $_POST['nested_subcategory_id'] : NULL;
    $brand = $_POST['brand'];
    $stock_quantity = $_POST['stock_quantity'];
    $size = $_POST['size'];
    $colour = $_POST['colour'];
    $condition_type = $_POST['condition_type'];
    $description = $_POST['description'];

    // Handle file uploads
    $image_urls = array(null, null, null); // Initialize with nulls for up to 3 images
    $target_dir = "uploads/products/";

    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            $dir_error = "Failed to create directory: " . error_get_last()['message'];
            error_log($dir_error);
            echo "<script>alert('$dir_error');</script>";
        }
    }

    // Check if images were uploaded
    if(isset($_FILES["product_image"]) && is_array($_FILES["product_image"]["name"])) {
        $file_count = count($_FILES["product_image"]["name"]);
        $max_images = min($file_count, 3); // Limit to 3 images
        
        for($i = 0; $i < $max_images; $i++) {
            if($_FILES["product_image"]["error"][$i] == 0) {
                $file_name = $_FILES["product_image"]["name"][$i];
                $imageFileType = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Generate unique filename
                $uniqueFileName = uniqid() . '_' . $i . '.' . $imageFileType;
                $target_file = $target_dir . $uniqueFileName;
                
                // Check if image file is actual image
                $check = getimagesize($_FILES["product_image"]["tmp_name"][$i]);
                if($check !== false) {
                    // Upload file
                    if(move_uploaded_file($_FILES["product_image"]["tmp_name"][$i], $target_file)) {
                        $image_urls[$i] = $target_file;
                    } else {
                        $upload_error = "Sorry, there was an error uploading image #" . ($i+1);
                        error_log($upload_error);
                        echo "<script>alert('$upload_error');</script>";
                    }
                } else {
                    echo "<script>alert('File #" . ($i+1) . " is not an image.');</script>";
                }
            }
        }
    } 
    // Handle single image upload (in case the form isn't updated)
    else if(isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
        $target_file = $target_dir . basename($_FILES["product_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Generate unique filename
        $uniqueFileName = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $uniqueFileName;
        
        // Check if image file is actual image
        $check = getimagesize($_FILES["product_image"]["tmp_name"]);
        if($check !== false) {
            // Upload file
            if(move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                $image_urls[0] = $target_file;
            } else {
                $upload_error = "Sorry, there was an error uploading your file";
                error_log($upload_error);
                echo "<script>alert('$upload_error');</script>";
            }
        } else {
            echo "<script>alert('File is not an image.');</script>";
        }
    } else {
        $file_error = "No file uploaded or file upload error";
        error_log($file_error);
        echo "<script>alert('$file_error');</script>";
    }

    if ($nested_subcategory_id === NULL) {
        $sql = "INSERT INTO product_table (seller_id, category_id, subcategory_id, 
                name, description, price, stock_quantity, size, colour, brand, condition_type, 
                image_url, image_url_2, image_url_3) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iiissdiissssss", // 14 characters for 14 variables
                $seller_id, 
                $category_id, 
                $subcategory_id, 
                $product_name, 
                $description, 
                $price, 
                $stock_quantity, 
                $size, 
                $colour, 
                $brand, 
                $condition_type, 
                $image_urls[0],
                $image_urls[1],
                $image_urls[2]
            );
    
            if ($stmt->execute()) {
                // Redirect to products.php after successful insertion
                header("Location: products.php");
                exit();
            } else {
                $error_message = "Error executing statement: " . $stmt->error;
                error_log($error_message);
                echo "<script>alert('$error_message');</script>";
            }
            $stmt->close();
        } else {
            $error_message = "Error preparing statement: " . $conn->error;
            error_log($error_message);
            echo "<script>alert('$error_message');</script>";
        }
    } else {
        $sql = "INSERT INTO product_table (seller_id, category_id, subcategory_id, nested_subcategory_id, 
                name, description, price, stock_quantity, size, colour, brand, condition_type, 
                image_url, image_url_2, image_url_3) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iiiissdiissssss", // 15 characters for 15 variables
                $seller_id, 
                $category_id, 
                $subcategory_id, 
                $nested_subcategory_id,
                $product_name, 
                $description, 
                $price, 
                $stock_quantity, 
                $size, 
                $colour, 
                $brand, 
                $condition_type, 
                $image_urls[0],
                $image_urls[1],
                $image_urls[2]
            );
    
            if ($stmt->execute()) {
                // Redirect to products.php after successful insertion
                header("Location: products.php");
                exit();
            } else {
                $error_message = "Error executing statement: " . $stmt->error;
                error_log($error_message);
                echo "<script>alert('$error_message');</script>";
            }
            $stmt->close();
        } else {
            $error_message = "Error preparing statement: " . $conn->error;
            error_log($error_message);
            echo "<script>alert('$error_message');</script>";
        }
    }
}

// Fetch categories for dropdown
$conn = new mysqli($servername, $username, $password, $database);
$category_query = "SELECT * FROM categories_table ORDER BY name";
$category_result = $conn->query($category_query);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - BabyCubs Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: white;
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
            color: var(--primary-color);
            font-size: 24px;
            font-weight: bold;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            color: var(--text-color);
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
        
        .add-product-form {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
            min-width: 250px;
            margin: 0 10px 15px 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .form-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #5649c0;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #ddd;
            color: var(--dark-color);
            margin-right: 10px;
        }
        
        .btn-outline:hover {
            background-color: #f5f5f5;
        }
        
        .image-previews {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.image-preview {
    flex: 1;
    height: 150px;
    border: 1px dashed #ddd;
    border-radius: 4px;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f9f9f9;
    overflow: hidden;
}

.image-preview img {
    max-width: 100%;
    max-height: 100%;
    display: none;
}
        
        .image-preview span {
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Your existing HTML structure -->
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - BabyCubs Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: white;
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
            color: var(--primary-color);
            font-size: 24px;
            font-weight: bold;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            color: var(--text-color);
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
        
        .add-product-form {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
            min-width: 250px;
            margin: 0 10px 15px 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .form-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #5649c0;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #ddd;
            color: var(--dark-color);
            margin-right: 10px;
        }
        
        .btn-outline:hover {
            background-color: #f5f5f5;
        }
        
        .image-previews {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.image-preview {
    flex: 1;
    height: 150px;
    border: 1px dashed #ddd;
    border-radius: 4px;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f9f9f9;
    overflow: hidden;
}

.image-preview img {
    max-width: 100%;
    max-height: 100%;
    display: none;
}
        
        .image-preview span {
            color: #999;
            font-size: 14px;
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
            <li><a href="products.php" class="active"><i class="fas fa-box"></i> MyProducts</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="content">
        <div class="content-header">
            <h2>Add New Product</h2>
        </div>
        
        <div class="add-product-form">
            <form action="product manage.php" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="product_name">Product Name</label>
                        <input type="text" id="product_name" name="product_name" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price (₹)</label>
                        <input type="number" id="price" name="price" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php 
                            if ($category_result && $category_result->num_rows > 0) {
                                while($category_row = $category_result->fetch_assoc()) {
                                    echo "<option value='" . $category_row["category_id"] . "'>" . $category_row["name"] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subcategory">Subcategory</label>
                        <select id="subcategory" name="subcategory_id" required>
                            <option value="">Select Subcategory</option>
                            <!-- Will be populated via JavaScript after category selection -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nested_subcategory">Nested Subcategory (Optional)</label>
                        <select id="nested_subcategory" name="nested_subcategory_id">
                            <option value="">Select Nested Subcategory</option>
                            <!-- Will be populated via JavaScript after subcategory selection -->
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <select id="brand" name="brand">
                            <option value="">Select Brand</option>
                            <!-- Will be populated via JavaScript after category/subcategory selection -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="stock">Stock Quantity</label>
                        <input type="number" id="stock" name="stock_quantity" min="0" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="size">Size</label>
                        <input type="text" id="size" name="size" required>
                    </div>
                    <div class="form-group">
                        <label for="colour">Color</label>
                        <input type="text" id="colour" name="colour" required>
                    </div>
                    <div class="form-group">
                        <label for="condition">Condition</label>
                        <select id="condition" name="condition_type" required>
                            <option value="New">New</option>
                            <option value="Used">Used</option>
                            <option value="Refurbished">Refurbished</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Product Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                
                <div class="form-group">
    <label for="product_image">Product Images (Upload up to 3)</label>
    <input type="file" id="product_image" name="product_image[]" accept="image/*" multiple required>
    <div class="image-previews">
        <div class="image-preview">
            <img id="image_preview_display_1" src="#" alt="Product Preview 1">
            <span id="image_preview_text_1">Image 1 preview</span>
        </div>
        <div class="image-preview">
            <img id="image_preview_display_2" src="#" alt="Product Preview 2">
            <span id="image_preview_text_2">Image 2 preview</span>
        </div>
        <div class="image-preview">
            <img id="image_preview_display_3" src="#" alt="Product Preview 3">
            <span id="image_preview_text_3">Image 3 preview</span>
        </div>
    </div>
</div>
                
                <div class="form-actions">
                    <button type="reset" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Preview images before upload
document.getElementById('product_image').addEventListener('change', function(e) {
    const previews = [
        { img: document.getElementById('image_preview_display_1'), text: document.getElementById('image_preview_text_1') },
        { img: document.getElementById('image_preview_display_2'), text: document.getElementById('image_preview_text_2') },
        { img: document.getElementById('image_preview_display_3'), text: document.getElementById('image_preview_text_3') }
    ];
    
    // Reset all previews
    previews.forEach(preview => {
        preview.img.style.display = 'none';
        preview.text.style.display = 'block';
    });
    
    // Display new previews
    if (e.target.files) {
        const maxImages = Math.min(e.target.files.length, 3);
        
        for (let i = 0; i < maxImages; i++) {
            const reader = new FileReader();
            const currentPreview = previews[i];
            
            reader.onload = function(event) {
                currentPreview.img.src = event.target.result;
                currentPreview.img.style.display = 'block';
                currentPreview.text.style.display = 'none';
            }
            
            reader.readAsDataURL(e.target.files[i]);
        }
    }
});
        
        // Dynamic dropdowns for categories, subcategories, etc.
        document.getElementById('category').addEventListener('change', function() {
            const categoryId = this.value;
            
            if (categoryId) {
                // Fetch subcategories based on selected category
                fetch(`get_subcategories.php?category_id=${categoryId}`)
                    .then(response => response.json())
                    .then(data => {
                        const subcategorySelect = document.getElementById('subcategory');
                        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                        
                        data.forEach(subcategory => {
                            const option = document.createElement('option');
                            option.value = subcategory.id;
                            option.textContent = subcategory.subcategory_name;
                            subcategorySelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching subcategories:', error));
                
                // Also fetch brands for this category
                fetchBrands(categoryId, null);
            }
        });
        
        document.getElementById('subcategory').addEventListener('change', function() {
            const subcategoryId = this.value;
            const categoryId = document.getElementById('category').value;
            
            if (subcategoryId) {
                // Fetch nested subcategories
                fetch(`get_nested_subcategories.php?subcategory_id=${subcategoryId}`)
                    .then(response => response.json())
                    .then(data => {
                        const nestedSelect = document.getElementById('nested_subcategory');
                        nestedSelect.innerHTML = '<option value="">Select Nested Subcategory</option>';
                        
                        data.forEach(nested => {
                            const option = document.createElement('option');
                            option.value = nested.id;
                            option.textContent = nested.nested_subcategory_name;
                            nestedSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching nested subcategories:', error));
                
                // Update brands based on category and subcategory
                fetchBrands(categoryId, subcategoryId);
            }
        });
        
        function fetchBrands(categoryId, subcategoryId) {
            let url = `get_brands.php?category_id=${categoryId}`;
            if (subcategoryId) {
                url += `&subcategory_id=${subcategoryId}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const brandSelect = document.getElementById('brand');
                    brandSelect.innerHTML = '<option value="">Select Brand</option>';
                    
                    data.forEach(brand => {
                        const option = document.createElement('option');
                        option.value = brand.brand_name;
                        option.textContent = brand.brand_name;
                        brandSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching brands:', error));
        }
    </script>
</body>
</html>