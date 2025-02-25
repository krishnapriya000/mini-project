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

// Check if product ID is provided
if (!isset($_GET['id'])) {
    die("Product ID not specified.");
}

$product_id = $_GET['id'];

// Fetch product details
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Simplified query to match your database structure
$product_query = "
    SELECT * FROM product_table 
    WHERE product_id = ? AND seller_id = ?
";

$stmt = $conn->prepare($product_query);
$stmt->bind_param("ii", $product_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Product not found or you do not have permission to edit this product.");
}

$product = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $size = $_POST['size'];
    $colour = $_POST['colour'];
    $brand = $_POST['brand'];
    $condition_type = $_POST['condition_type'];
    $description = $_POST['description'];

    // Handle file upload if a new image is provided
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/products/";
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = $target_file;
        }
    } else {
        $image_url = $product['image_url']; // Keep existing image if no new one is uploaded
    }

    try {
        // Update product details
        $update_query = "
            UPDATE product_table SET
                name = ?, 
                category_id = ?, 
                price = ?, 
                stock_quantity = ?, 
                size = ?,
                colour = ?,
                brand = ?,
                condition_type = ?,
                description = ?,
                image_url = ?
            WHERE product_id = ? AND seller_id = ?
        ";

        $update_stmt = $conn->prepare($update_query);
        
        if ($update_stmt === false) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        $update_stmt->bind_param("sisissssssii", 
            $name, 
            $category_id, 
            $price, 
            $stock_quantity, 
            $size,
            $colour,
            $brand,
            $condition_type,
            $description,
            $image_url,
            $product_id,
            $seller_id
        );

        if ($update_stmt->execute()) {
            // Redirect to products page after successful update
            header("Location: products.php");
            exit();
        } else {
            throw new Exception("Error executing statement: " . $update_stmt->error);
        }
    } catch (Exception $e) {
        echo "Error updating product: " . $e->getMessage();
    } finally {
        if (isset($update_stmt)) {
            $update_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - BabyCubs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .current-image {
            margin: 10px 0;
        }
        .current-image img {
            max-width: 200px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Product</h2>
        <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="category_id">Category ID:</label>
                <input type="number" id="category_id" name="category_id" value="<?php echo htmlspecialchars($product['category_id']); ?>" required>
            </div>

            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>

            <div class="form-group">
                <label for="stock_quantity">Stock Quantity:</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
            </div>

            <div class="form-group">
                <label for="size">Size:</label>
                <input type="text" id="size" name="size" value="<?php echo htmlspecialchars($product['size']); ?>" required>
            </div>

            <div class="form-group">
                <label for="colour">Color:</label>
                <input type="text" id="colour" name="colour" value="<?php echo htmlspecialchars($product['colour']); ?>" required>
            </div>

            <div class="form-group">
                <label for="brand">Brand:</label>
                <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($product['brand']); ?>" required>
            </div>

            <div class="form-group">
                <label for="condition_type">Condition:</label>
                <select id="condition_type" name="condition_type" required>
                    <option value="New" <?php echo ($product['condition_type'] == 'New') ? 'selected' : ''; ?>>New</option>
                    <option value="Used" <?php echo ($product['condition_type'] == 'Used') ? 'selected' : ''; ?>>Used</option>
                    <option value="Refurbished" <?php echo ($product['condition_type'] == 'Refurbished') ? 'selected' : ''; ?>>Refurbished</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="image">Current Image:</label>
                <div class="current-image">
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Current product image">
                    <?php else: ?>
                        <p>No image currently uploaded</p>
                    <?php endif; ?>
                </div>
                <label for="image">Upload New Image (optional):</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>

            <button type="submit">Update Product</button>
        </form>
    </div>
</body>
</html>