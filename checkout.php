<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];
$get_user_query = "SELECT * FROM user_table WHERE user_id = ?";
$stmt = $conn->prepare($get_user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$signupid = $user_data['signupid'];

// Fetch cart items
$cart_items_query = "SELECT ci.*, p.name, p.price
                    FROM cart_items ci
                    JOIN product_table p ON ci.product_id = p.product_id
                    JOIN cart_table ct ON ci.cart_id = ct.cart_id
                    WHERE ci.signupid = ? AND ct.status = 'active' 
                    AND (ci.status = 'active' OR ci.status IS NULL)";

$stmt = $conn->prepare($cart_items_query);
$stmt->bind_param("i", $signupid);
$stmt->execute();
$cart_items = $stmt->get_result();

// Calculate total
$subtotal = 0;
$total_items = 0;

while($item = $cart_items->fetch_assoc()) {
    $total_items += $item['quantity'];
    $item_total = $item['quantity'] * $item['price'];
    $subtotal += $item_total;
}

// Calculate shipping, tax, and total
$shipping = $subtotal >= 1000 ? 0 : 100;
$tax = $subtotal * 0.05;
$total = $subtotal + $shipping + $tax;

// Reset the cart items result
$stmt->execute();
$cart_items = $stmt->get_result();

// Razorpay API key
$razorpay_key_id = "rzp_test_Z4RWNiIGZc3YxK";

// Create a unique order ID
$order_id = 'ORD' . time() . $user_id;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs - Checkout</title>
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
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: linear-gradient(to right, #ffffff, #f8f9fa);
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: color 0.3s ease;
        }
        
        .logo:hover {
            color: #0077cc;
        }
        
        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }
        
        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 22px;
            color: #2c3e50;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .icon-btn:hover {
            color: #0077cc;
            background-color: rgba(0,119,204,0.1);
            transform: translateY(-2px);
        }
        
        .checkout-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .checkout-title {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .checkout-flex {
            display: flex;
            gap: 30px;
        }
        
        .checkout-form {
            flex: 1;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 30px;
        }
        
        .checkout-summary {
            flex: 1;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #0077cc;
            box-shadow: 0 0 0 2px rgba(0,119,204,0.2);
        }
        
        .section-title {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .summary-label {
            font-weight: 600;
            color: #555;
        }
        
        .summary-value {
            font-weight: 700;
            color: #2c3e50;
        }
        
        .item-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .checkout-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .item-price {
            color: #777;
            font-size: 14px;
        }
        
        .item-quantity {
            font-weight: 600;
            color: #555;
            margin-left: 15px;
        }
        
        .payment-btn {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
            font-size: 16px;
        }
        
        .payment-btn:hover {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46,204,113,0.3);
        }
        
        @media (max-width: 768px) {
            .checkout-flex {
                flex-direction: column;
            }
            
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: space-around;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" style="text-decoration: none;">
            <div class="logo">BabyCubs</div>
        </a>
        
        <div class="nav-links">
            <a href="index.php" style="text-decoration: none;">
                <button class="icon-btn" title="Home">
                    <i class="fas fa-home"></i>
                </button>
            </a>
            <a href="product_view.php" style="text-decoration: none;">
                <button class="icon-btn" title="Products">
                    <i class="fas fa-shopping-bag"></i>
                </button>
            </a>
            <a href="cart.php" style="text-decoration: none;">
                <button class="icon-btn" title="Cart">
                    <i class="fas fa-shopping-cart"></i>
                </button>
            </a>
            <a href="profile.php" style="text-decoration: none;">
                <div class="icon-btn" title="Profile">
                    <i class="fas fa-user"></i>
                </div>
            </a>
        </div>
    </div>
    
    <div class="checkout-container">
        <h1 class="checkout-title">Checkout</h1>
        
        <div class="checkout-flex">
            <div class="checkout-form">
                <h2 class="section-title">Shipping Information</h2>
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-input" id="fullname" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" id="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" class="form-input" id="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-input" id="address" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" class="form-input" id="city" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Postal Code</label>
                    <input type="text" class="form-input" id="postal_code" required>
                </div>
            </div>
            
            <div class="checkout-summary">
                <h2 class="section-title">Order Summary</h2>
                
                <div class="item-list">
                    <?php while($item = $cart_items->fetch_assoc()): ?>
                        <div class="checkout-item">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                            </div>
                            <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                        </div>
                    <?php endwhile; ?>
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
                
                <div class="summary-item" style="border-top: 2px solid #e0e0e0; padding-top: 15px;">
                    <span class="summary-label" style="font-size: 18px;">Total</span>
                    <span class="summary-value" style="font-size: 18px; color: #e74c3c;">₹<?php echo number_format($total, 2); ?></span>
                </div>
                
                <button id="razorpay-button" class="payment-btn">Pay Now</button>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('razorpay-button').addEventListener('click', function() {
            const fullname = document.getElementById('fullname').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const address = document.getElementById('address').value;
            const city = document.getElementById('city').value;
            const postal_code = document.getElementById('postal_code').value;
            
            // Validate form fields
            if (!fullname || !email || !phone || !address || !city || !postal_code) {
                alert('Please fill all required fields');
                return;
            }
            
            // Razorpay options
            const options = {
                key: "<?php echo $razorpay_key_id; ?>",
                amount: <?php echo $total * 100; ?>, // Amount in paise
                currency: "INR",
                name: "BabyCubs",
                description: "Purchase from BabyCubs",
                image: "https://your-website.com/logo.png", // Replace with your logo URL
                order_id: "", // Generate this on your server
                handler: function(response) {
                    // On successful payment
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'process_payment.php';
                    
                    // Add payment response data
                    const paymentIdInput = document.createElement('input');
                    paymentIdInput.type = 'hidden';
                    paymentIdInput.name = 'razorpay_payment_id';
                    paymentIdInput.value = response.razorpay_payment_id;
                    form.appendChild(paymentIdInput);
                    
                    // Add order data
                    const orderIdInput = document.createElement('input');
                    orderIdInput.type = 'hidden';
                    orderIdInput.name = 'order_id';
                    orderIdInput.value = '<?php echo $order_id; ?>';
                    form.appendChild(orderIdInput);
                    
                    // Add amount data
                    const amountInput = document.createElement('input');
                    amountInput.type = 'hidden';
                    amountInput.name = 'amount';
                    amountInput.value = '<?php echo $total; ?>';
                    form.appendChild(amountInput);
                    
                    // Add shipping info
                    const nameInput = document.createElement('input');
                    nameInput.type = 'hidden';
                    nameInput.name = 'fullname';
                    nameInput.value = fullname;
                    form.appendChild(nameInput);
                    
                    const emailInput = document.createElement('input');
                    emailInput.type = 'hidden';
                    emailInput.name = 'email';
                    emailInput.value = email;
                    form.appendChild(emailInput);
                    
                    const phoneInput = document.createElement('input');
                    phoneInput.type = 'hidden';
                    phoneInput.name = 'phone';
                    phoneInput.value = phone;
                    form.appendChild(phoneInput);
                    
                    const addressInput = document.createElement('input');
                    addressInput.type = 'hidden';
                    addressInput.name = 'address';
                    addressInput.value = address;
                    form.appendChild(addressInput);
                    
                    const cityInput = document.createElement('input');
                    cityInput.type = 'hidden';
                    cityInput.name = 'city';
                    cityInput.value = city;
                    form.appendChild(cityInput);
                    
                    const postalInput = document.createElement('input');
                    postalInput.type = 'hidden';
                    postalInput.name = 'postal_code';
                    postalInput.value = postal_code;
                    form.appendChild(postalInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                },
                prefill: {
                    name: fullname,
                    email: email,
                    contact: phone
                },
                notes: {
                    address: address + ", " + city + " - " + postal_code
                },
                theme: {
                    color: "#0077cc"
                }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
        });
    </script>
</body>
</html>