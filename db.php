<?php

$servername = "localhost";
$username = "root";
$password = "";
$database = "baby_db";


$conn = new mysqli($servername, $username, $password, $database);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// forgot password
define('SMTP_USER', 'krishnapriyarajesh0@gmail.com');
define('SMTP_PASS', 'xwry zrcw biqh sgwd');

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $database";
$conn->query($sql);
$conn->select_db($database);

// signup
$signup_table = "CREATE TABLE IF NOT EXISTS signup (
    signupid INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending',
    reg_type VARCHAR(50)
)";

$conn->query($signup_table);

// Alter Signup Table to add reset token columns
$alter_signup = "ALTER TABLE signup 
    ADD COLUMN IF NOT EXISTS reset_token VARCHAR(100) DEFAULT NULL, 
    ADD COLUMN IF NOT EXISTS reset_token_expiry TIMESTAMP NULL DEFAULT NULL";
$conn->query($alter_signup);

// login
$login_table = "CREATE TABLE IF NOT EXISTS login (
    login_id INT AUTO_INCREMENT PRIMARY KEY,
    signup_id INT,
    login_date DATE NOT NULL,
    FOREIGN KEY (signup_id) REFERENCES signup(signupid)
)";
$conn->query($login_table);

// user
$user_table = "CREATE TABLE IF NOT EXISTS user_table (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    signupid INT UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    reg_type VARCHAR(50) NOT NULL,
    FOREIGN KEY (signupid) REFERENCES signup(signupid)
)";
$conn->query($user_table);

// Seller Table
$seller_table = "CREATE TABLE IF NOT EXISTS seller (
    seller_id INT AUTO_INCREMENT PRIMARY KEY,
    signupid INT UNIQUE,
    seller_name VARCHAR(100) NOT NULL,
    business_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    reg_type VARCHAR(50) NOT NULL,
    registration_date DATE NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL,
    FOREIGN KEY (signupid) REFERENCES signup(signupid)
)";
$conn->query($seller_table);
$alter_user_table = "ALTER TABLE user_table 
    ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'";
$conn->query($alter_user_table);

$alter_seller_table = "ALTER TABLE seller 
    MODIFY COLUMN status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending'";
$conn->query($alter_seller_table);


$categories_table = "CREATE TABLE IF NOT EXISTS categories_table (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL
)";
$conn->query($categories_table);
if (!$conn->query($categories_table)) {
    echo "Error creating categories_table: " . $conn->error . "<br>";
}


// Create product table
$product_table = "CREATE TABLE IF NOT EXISTS product_table (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    category_id INT NOT NULL,
    subcategory_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    size VARCHAR(50) NOT NULL,
    colour VARCHAR(50) NOT NULL,
    brand VARCHAR(100),
    condition_type ENUM('New', 'Used', 'Refurbished') DEFAULT 'New',
    image_url VARCHAR(255) NOT NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES seller(seller_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories_table(category_id) ON DELETE CASCADE,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
    INDEX idx_product_status (status),
    INDEX idx_product_category (category_id),
    INDEX idx_product_subcategory (subcategory_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Try to create the table and handle any errors
try {
    if ($conn->query($product_table) === TRUE) {
       // echo "Product table created successfully<br>";
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    echo "Error creating product_table: " . $e->getMessage() . "<br>";
    // Log the error for debugging
    error_log("Product table creation error: " . $e->getMessage());
}

// Verify the table exists and has the correct structure
try {
    $result = $conn->query("DESCRIBE product_table");
    if ($result) {
       // echo "Product table structure verified successfully<br>";
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    echo "Error verifying product_table: " . $e->getMessage() . "<br>";
    error_log("Product table verification error: " . $e->getMessage());
}



// Create order table
$order_table = "CREATE TABLE IF NOT EXISTS order_table (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,5) NOT NULL,
    status_name ENUM('pending', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    shipping_address VARCHAR(255) NOT NULL,
    order_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    tracking_number VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user_table(user_id) ON DELETE CASCADE
)";
$conn->query($order_table);

$review_table = "CREATE TABLE IF NOT EXISTS review_table (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user_table(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product_table(product_id) ON DELETE CASCADE
)";
$conn->query($review_table);


$subcategories_table = "CREATE TABLE IF NOT EXISTS subcategories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_id INT(11) NOT NULL,
    subcategory_name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories_table(category_id) ON DELETE CASCADE

)";
$conn->query($subcategories_table);

$alter_subcategories = "ALTER TABLE subcategories 
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1";
$conn->query($alter_subcategories);
if (!$conn->query($alter_subcategories)) {
    echo "Error altering subcategories table: " . $conn->error . "<br>";
}





// Add this code after the subcategories table creation in your db.php file

// Create nested subcategories table
$nested_subcategories_table = "CREATE TABLE IF NOT EXISTS nested_subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_subcategory_id INT NOT NULL,
    nested_subcategory_name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
)";

// Execute the query and handle any errors
if (!$conn->query($nested_subcategories_table)) {
    echo "Error creating nested_subcategories table: " . $conn->error . "<br>";
}

// Also update the product table to reference nested subcategories if needed
$alter_product_table = "ALTER TABLE product_table 
    ADD COLUMN IF NOT EXISTS nested_subcategory_id INT,
    ADD FOREIGN KEY (nested_subcategory_id) REFERENCES nested_subcategories(id) ON DELETE SET NULL";

if (!$conn->query($alter_product_table)) {
    echo "Error altering product table: " . $conn->error . "<br>";
}

// Add index for better query performance
$add_index = "CREATE INDEX IF NOT EXISTS idx_nested_parent ON nested_subcategories(parent_subcategory_id)";
if (!$conn->query($add_index)) {
    echo "Error creating index: " . $conn->error . "<br>";
}


// Add this to your db.php file

$brand_table = "CREATE TABLE IF NOT EXISTS brand_table (
    brand_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    subcategory_id INT NOT NULL,
    brand_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1, -- Add this line
    FOREIGN KEY (category_id) REFERENCES categories_table(category_id) ON DELETE CASCADE,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_brand_category_subcategory (brand_name, category_id, subcategory_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($brand_table)) {
    echo "Error creating brand table: " . $conn->error . "<br>";
}

// Close the connection
//$conn->close();
?>
