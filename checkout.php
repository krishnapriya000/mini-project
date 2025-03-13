<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include the database connection file
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$query = $conn->prepare("SELECT * FROM signup WHERE signupid = ?");
if (!$query) {
    die("Prepare failed: " . $conn->error);
}
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Fetch cart items
$cart_query = "SELECT ci.*, p.name as product_name, p.image_url, c.name as category_name, s.subcategory_name
               FROM cart_items ci
               JOIN product_table p ON ci.product_id = p.product_id
               JOIN categories_table c ON ci.category_id = c.category_id
               JOIN subcategories s ON ci.subcategory_id = s.id
               JOIN cart_table ct ON ci.cart_id = ct.cart_id
               WHERE ci.signupid = ? AND ct.status = 'active'";

$stmt = $conn->prepare($cart_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// Calculate totals
$subtotal = 0;
$shipping = 0;
$tax = 0;
$total = 0;

// Fetch default shipping address
$address_query = "SELECT * FROM shipping_addresses 
                  WHERE signupid = ? AND is_default = 1 
                  ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($address_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$address_result = $stmt->get_result();
$address_data = $address_result->fetch_assoc();

// Fetch saved addresses
$saved_addresses_query = "SELECT * FROM shipping_addresses WHERE signupid = ?";
$stmt = $conn->prepare($saved_addresses_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$saved_addresses = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BabyCubs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f8f9fa;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .form-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #0077cc;
            box-shadow: 0 0 0 2px rgba(0,119,204,0.1);
            outline: none;
        }

        .order-summary {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 20px;
        }

        .summary-title {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: #666;
        }

        .summary-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .total-row {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            font-size: 18px;
        }

        .payment-methods {
            margin-top: 30px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            border-color: #0077cc;
            background-color: #f8f9fa;
        }

        .payment-option input[type="radio"] {
            margin-right: 15px;
        }

        .place-order-btn {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .place-order-btn:hover {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46,204,113,0.3);
        }

        .cart-items {
            margin-bottom: 30px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .cart-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }

        .item-details h4 {
            margin: 0;
            color: #2c3e50;
        }

        .item-details p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <form action="process_order.php" method="POST" id="checkout-form">
            <div class="checkout-grid">
                <!-- Shipping Information -->
                <div class="checkout-form">
                    <h2 class="form-title">Shipping Information</h2>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" required><?php 
                            echo htmlspecialchars($address_data['address_line1'] ?? ''); 
                        ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                               value="<?php echo htmlspecialchars($address_data['city'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" 
                               value="<?php echo htmlspecialchars($address_data['state'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" 
                               value="<?php echo htmlspecialchars($address_data['postal_code'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="save_address" value="1" checked>
                            Save this address for future use
                        </label>
                    </div>

                    <?php if ($saved_addresses->num_rows > 0): ?>
                        <div class="form-group">
                            <label for="saved_address">Use Saved Address</label>
                            <select id="saved_address" name="saved_address" onchange="loadSavedAddress(this.value)">
                                <option value="">Select a saved address</option>
                                <?php while($addr = $saved_addresses->fetch_assoc()): ?>
                                    <option value="<?php echo $addr['address_id']; ?>">
                                        <?php echo htmlspecialchars($addr['address_line1'] . ', ' . $addr['city']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="payment-methods">
                        <h3 class="form-title">Payment Method</h3>
                        
                        <div class="payment-option">
                            <input type="radio" id="cod" name="payment_method" value="cod" checked>
                            <label for="cod">Cash on Delivery</label>
                        </div>
                        
                        <div class="payment-option">
                            <input type="radio" id="online" name="payment_method" value="online">
                            <label for="online">Online Payment</label>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <div class="cart-items">
                        <?php 
                        while($item = $cart_items->fetch_assoc()):
                            $item_total = $item['price'] * $item['quantity'];
                            $subtotal += $item_total;
                        ?>
                            <div class="cart-item">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <p>Quantity: <?php echo $item['quantity']; ?></p>
                                    <p>₹<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                            </div>
                        <?php endwhile; 

                        // Calculate other costs
                        $shipping = $subtotal >= 1000 ? 0 : 100;
                        $tax = $subtotal * 0.05; // 5% tax
                        $total = $subtotal + $shipping + $tax;
                        ?>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value">₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Shipping</span>
                        <span class="summary-value">
                            <?php echo $shipping > 0 ? '₹' . number_format($shipping, 2) : 'FREE'; ?>
                        </span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Tax (5%)</span>
                        <span class="summary-value">₹<?php echo number_format($tax, 2); ?></span>
                    </div>

                    <div class="summary-item total-row">
                        <span class="summary-label">Total</span>
                        <span class="summary-value">₹<?php echo number_format($total, 2); ?></span>
                    </div>

                    <button type="button" id="placeOrderBtn" class="place-order-btn" onclick="processPayment()">Place Order</button>
                </div>
            </div>
            <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
            <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
            <input type="hidden" name="razorpay_signature" id="razorpay_signature">
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let placeOrderBtn = document.getElementById("placeOrderBtn");

            if (!placeOrderBtn) {
                console.error("Error: placeOrderBtn not found in the DOM.");
                return;
            }

            placeOrderBtn.addEventListener("click", function () {
                let name = document.getElementById("name")?.value || "";
                let email = document.getElementById("email")?.value || "";
                let phone = document.getElementById("phone")?.value || "";
                let address = document.getElementById("address")?.value || "";
                let total_amount = document.getElementById("total_amount")?.value || 0;

                if (!name || !email || !phone || !address || !total_amount) {
                    alert("Please fill all required fields.");
                    return;
                }

                fetch("create_razorpay_order.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `name=${name}&email=${email}&phone=${phone}&address=${address}&total_amount=${total_amount}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        processPayment(data.order_id, data.amount, data.currency);
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => console.error("Error:", error));
            });
        });

        function processPayment(orderId, amount, currency) {
            var options = {
                "key": "YOUR_RAZORPAY_KEY",
                "amount": amount,
                "currency": currency,
                "name": "Baby Cubs",
                "description": "Order Payment",
                "order_id": orderId,
                "handler": function (response) {
                    window.location.href = "order-success.php?payment_id=" + response.razorpay_payment_id;
                },
                "prefill": {
                    "name": document.getElementById("name")?.value || "",
                    "email": document.getElementById("email")?.value || "",
                    "contact": document.getElementById("phone")?.value || ""
                },
                "theme": { "color": "#F37254" }
            };

            var rzp1 = new Razorpay(options);
            rzp1.open();
        }
    </script>
</body>
</html>