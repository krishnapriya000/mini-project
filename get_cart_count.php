<?php
session_start();
header('Content-Type: application/json');

// Debug information
file_put_contents('cart_debug.log', "=== GET_CART_COUNT.PHP ===\n", FILE_APPEND);
file_put_contents('cart_debug.log', 'Session cart data: ' . print_r($_SESSION['cart'] ?? 'Cart not set', true) . "\n", FILE_APPEND);

// Calculate total quantity correctly
$total_quantity = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    // Make sure all keys are integers
    $fixed_cart = [];
    foreach ($_SESSION['cart'] as $key => $value) {
        $fixed_cart[(int)$key] = (int)$value;
    }
    $_SESSION['cart'] = $fixed_cart;
    
    $total_quantity = array_sum($_SESSION['cart']);
    file_put_contents('cart_debug.log', "Total quantity: $total_quantity\n", FILE_APPEND);
}

echo json_encode(['count' => $total_quantity]);
file_put_contents('cart_debug.log', "Returned count: $total_quantity\n", FILE_APPEND);
file_put_contents('cart_debug.log', "=== GET_CART_COUNT.PHP END ===\n\n", FILE_APPEND);
?>