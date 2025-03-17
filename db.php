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





// Add this code after establishing your database connection
$alter_query = "ALTER TABLE product_table 
               ADD COLUMN IF NOT EXISTS image_url_2 VARCHAR(255) AFTER image_url,
               ADD COLUMN IF NOT EXISTS image_url_3 VARCHAR(255) AFTER image_url_2";

// Execute the ALTER TABLE query
if ($conn->query($alter_query) === TRUE) {
    // Columns added successfully or already exist
    error_log("Image columns added successfully or already exist");
} else {
    // Error adding columns
    error_log("Error adding image columns: " . $conn->error);
}



// // Drop existing order_table if you want to recreate it
// $drop_order_table = "DROP TABLE IF EXISTS order_table";
// $conn->query($drop_order_table);

// Create modified order_table
$order_table = "CREATE TABLE IF NOT EXISTS order_table (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status_name ENUM('pending', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_method ENUM('cod', 'online') DEFAULT 'cod',
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    tracking_number VARCHAR(50) DEFAULT NULL,
    razorpay_order_id VARCHAR(100) NULL,
    razorpay_payment_id VARCHAR(100) NULL,
    razorpay_signature VARCHAR(255) NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_table(user_id) ON DELETE CASCADE
)";

if ($conn->query($order_table) === TRUE) {
    //echo "Order table modified successfully<br>";
} else {
    echo "Error modifying order table: " . $conn->error . "<br>";
}

// Create order_items table if it doesn't exist
$order_items_table = "CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES order_table(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product_table(product_id) ON DELETE RESTRICT
)";

if ($conn->query($order_items_table) === TRUE) {
    //echo "Order items table created successfully<br>";
} else {
    echo "Error creating order items table: " . $conn->error . "<br>";
}

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



$cart_table = "CREATE TABLE IF NOT EXISTS cart_table (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    signupid INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'abandoned') DEFAULT 'active',
    FOREIGN KEY (signupid) REFERENCES signup(signupid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($cart_table)) {
    echo "Error creating cart table: " . $conn->error . "<br>";
}

$cart_items_table = "CREATE TABLE IF NOT EXISTS cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT,
    signupid INT NOT NULL,
    product_id INT NOT NULL,
    category_id INT NOT NULL,
    subcategory_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart_table(cart_id) ON DELETE CASCADE,
    FOREIGN KEY (signupid) REFERENCES signup(signupid) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product_table(product_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories_table(category_id) ON DELETE CASCADE,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_product (cart_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($cart_items_table)) {
    echo "Error creating cart items table: " . $conn->error . "<br>";
}

$alter_cart_items = "ALTER TABLE cart_items 
    ADD COLUMN IF NOT EXISTS status ENUM('active', 'disabled') DEFAULT 'active'";

if (!$conn->query($alter_cart_items)) {
    echo "Error altering cart_items table: " . $conn->error . "<br>";
} else {
    // echo "Cart items table altered successfully<br>";
}

// Update existing cart_items to have 'active' status if they don't have a status yet
$update_cart_items = "UPDATE cart_items SET status = 'active' WHERE status IS NULL";
if (!$conn->query($update_cart_items)) {
    echo "Error updating cart_items status: " . $conn->error . "<br>";
} else {
    // echo "Existing cart items updated successfully<br>";
}

// Add an index on the status column for better query performance
$add_status_index = "CREATE INDEX IF NOT EXISTS idx_cart_items_status ON cart_items(status)";
if (!$conn->query($add_status_index)) {
    echo "Error creating status index: " . $conn->error . "<br>";
} else {
    // echo "Status index created successfully<br>";
}

// ... existing database connection code ...

// Create shipping_addresses table
$shipping_addresses = "CREATE TABLE IF NOT EXISTS shipping_addresses (
    address_id INT PRIMARY KEY AUTO_INCREMENT,
    signupid INT,
    address_line1 TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (signupid) REFERENCES signup(signupid) ON DELETE CASCADE
)";

// Execute the query
if ($conn->query($shipping_addresses) === TRUE) {
    //echo "Shipping addresses table created successfully<br>";
} else {
    echo "Error creating shipping addresses table: " . $conn->error . "<br>";
}



// Close the connection
//$conn->close();
?>
