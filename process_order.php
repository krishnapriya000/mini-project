<?php

// After you've created the order and assigned the order_id to cart items:

// Update product stock quantities based on the order
function updateStockQuantities($conn, $order_id) {
    // Get all items in this order
    $items_query = "SELECT product_id, quantity FROM cart_items WHERE order_id = ?";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($item = $result->fetch_assoc()) {
        $product_id = $item['product_id'];
        $quantity_ordered = $item['quantity'];
        
        // Get current stock
        $stock_query = "SELECT stock_quantity FROM product_table WHERE product_id = ?";
        $stmt = $conn->prepare($stock_query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stock_result = $stmt->get_result();
        
        if ($stock_result && $stock_result->num_rows > 0) {
            $product = $stock_result->fetch_assoc();
            $new_stock = max(0, $product['stock_quantity'] - $quantity_ordered);
            
            // Update the stock quantity
            $update_query = "UPDATE product_table SET stock_quantity = ? WHERE product_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ii", $new_stock, $product_id);
            $stmt->execute();
        }
    }
}

// Call the function after creating the order
updateStockQuantities($conn, $order_id); 