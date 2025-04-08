<?php
session_start();
require 'db.php'; // Ensure DB connection

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Make sure quantity is valid
    if ($quantity <= 0) {
        $quantity = 1;
    }
    
    // Start transaction for stock management
    $conn->begin_transaction();
    
    try {
        // Fetch product details from database with FOR UPDATE to lock the row
        $query = "SELECT * FROM product_table WHERE product_id = ? FOR UPDATE";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
            // Check if product is in stock
            if ($product['stock_quantity'] <= 0) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Product is out of stock"
                ]);
                $conn->rollback();
                exit;
            }
            
            // Check if requested quantity is available
            if ($quantity > $product['stock_quantity']) {
                $quantity = $product['stock_quantity']; // Limit to available stock
                $limitedStockMessage = "Only " . $product['stock_quantity'] . " items available";
            }
            
            // Get the user's cart
            if (!isset($_SESSION['signupid'])) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "User not logged in"
                ]);
                $conn->rollback();
                exit;
            }
            
            $signupid = $_SESSION['signupid'];
            
            // Check if user already has an active cart
            $cart_check_query = "SELECT cart_id FROM cart_table 
                                WHERE signupid = ? AND status = 'active' 
                                LIMIT 1";
            
            $stmt = $conn->prepare($cart_check_query);
            $stmt->bind_param("i", $signupid);
            $stmt->execute();
            $cart_result = $stmt->get_result();
            
            // If cart doesn't exist, create one
            if ($cart_result->num_rows === 0) {
                $create_cart_query = "INSERT INTO cart_table (signupid, status) VALUES (?, 'active')";
                $stmt = $conn->prepare($create_cart_query);
                $stmt->bind_param("i", $signupid);
                $stmt->execute();
                $cart_id = $conn->insert_id;
            } else {
                $cart_row = $cart_result->fetch_assoc();
                $cart_id = $cart_row['cart_id'];
            }
            
            // Check if product already exists in cart
            $check_item_query = "SELECT cart_item_id, quantity, status FROM cart_items 
                                WHERE cart_id = ? AND product_id = ?
                                LIMIT 1";
            
            $stmt = $conn->prepare($check_item_query);
            $stmt->bind_param("ii", $cart_id, $product_id);
            $stmt->execute();
            $item_result = $stmt->get_result();
            
            if ($item_result->num_rows > 0) {
                // Item exists in cart
                $item_row = $item_result->fetch_assoc();
                
                if ($item_row['status'] === 'active') {
                    // Calculate new quantity
                    $new_quantity = $item_row['quantity'] + $quantity;
                    
                    // Check if new total quantity exceeds available stock
                    if ($new_quantity > $product['stock_quantity']) {
                        $new_quantity = $product['stock_quantity'];
                        $message = "Cart updated to maximum available quantity!";
                    } else {
                        $message = "Cart updated successfully!";
                    }
                    
                    $update_query = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("ii", $new_quantity, $item_row['cart_item_id']);
                    $stmt->execute();
                } else {
                    // If inactive, reactivate it and set new quantity
                    $update_query = "UPDATE cart_items SET status = 'active', quantity = ? WHERE cart_item_id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("ii", $quantity, $item_row['cart_item_id']);
                    $stmt->execute();
                    $message = "Product added to cart successfully!";
                }
            } else {
                // Add new product to cart
                $add_query = "INSERT INTO cart_items 
                             (cart_id, signupid, product_id, category_id, subcategory_id, quantity, price, status) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
                
                $stmt = $conn->prepare($add_query);
                $category_id = $product['category_id'];
                $subcategory_id = $product['subcategory_id'];
                $price = $product['price'];
                
                $stmt->bind_param("iiiiids", $cart_id, $signupid, $product_id, 
                                 $category_id, $subcategory_id, 
                                 $quantity, $price);
                
                if ($stmt->execute()) {
                    $message = "Product added to cart successfully!";
                } else {
                    throw new Exception("Error adding product to cart: " . $stmt->error);
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Return success response
            $response = [
                "status" => "success", 
                "message" => $message
            ];
            
            if (isset($limitedStockMessage)) {
                $response["stockWarning"] = $limitedStockMessage;
            }
            
            echo json_encode($response);
            
    } else {
            echo json_encode([
                "status" => "error", 
                "message" => "Product not found"
            ]);
            $conn->rollback();
        }
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode([
            "status" => "error", 
            "message" => "Error: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid request"
    ]);
}

// Add this to check your cart data
$debug_query = "SELECT * FROM cart_table WHERE signupid = ?";
$stmt = $conn->prepare($debug_query);
$stmt->bind_param("i", $_SESSION['signupid']);
$stmt->execute();
$result = $stmt->get_result();

echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>