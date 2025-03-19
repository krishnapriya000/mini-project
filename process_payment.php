<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if user is logged in
if (!isset($_SESSION['signupid'])) {
    echo "Session error: User identification not found. Please <a href='login.php'>login again</a>.";
    exit();
}

// Get signupid from session
$signupid = $_SESSION['signupid'];

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}

// Get payment details from the form
$razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
$order_id = $_POST['order_id'] ?? '';
$amount = $_POST['amount'] ?? 0;
$fullname = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$postal_code = $_POST['postal_code'] ?? '';

// Validate required fields
if (empty($razorpay_payment_id) || empty($order_id) || empty($amount)) {
    echo "Payment processing error: Missing required payment information.";
    exit();
}

// Create full shipping address
$shipping_address = $fullname . ", " . $address . ", " . $city . " - " . $postal_code . " | Phone: " . $phone;

// Variable to store payment details
$payment_details = null;

// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Begin transaction
$conn->begin_transaction();

try {
    // Verify signupid exists in signup table
    $check_signupid_query = "SELECT signupid FROM signup WHERE signupid = ?";
    $stmt = $conn->prepare($check_signupid_query);
    $stmt->bind_param("i", $signupid);
    $stmt->execute();
    $signupid_result = $stmt->get_result();

    if ($signupid_result->num_rows == 0) {
        throw new Exception("Signup ID not found in signup table: " . $signupid);
    }

    // Insert into orders_table
    $insert_order_query = "INSERT INTO orders_table (order_id, signupid, payment_id, total_amount, shipping_address, order_status, payment_status) 
                          VALUES (?, ?, ?, ?, ?, 'processing', 'paid')";
    $stmt = $conn->prepare($insert_order_query);

    if (!$stmt) {
        throw new Exception("Prepare statement failed for orders_table: " . $conn->error);
    }

    $stmt->bind_param("sisds", $order_id, $signupid, $razorpay_payment_id, $amount, $shipping_address);

    if (!$stmt->execute()) {
        throw new Exception("Insert into orders_table failed: " . $stmt->error);
    }

    // Insert into payment_table
    $insert_payment_query = "INSERT INTO payment_table (order_id, signupid, payment_id, amount, payment_method, payment_status) 
                            VALUES (?, ?, ?, ?, 'Razorpay', 'paid')";
    $stmt = $conn->prepare($insert_payment_query);

    if (!$stmt) {
        throw new Exception("Prepare statement failed for payment_table: " . $conn->error);
    }

    $stmt->bind_param("sisd", $order_id, $signupid, $razorpay_payment_id, $amount);

    if (!$stmt->execute()) {
        throw new Exception("Insert into payment_table failed: " . $stmt->error);
    }

    // Update cart status to 'ordered'
    $update_cart_query = "UPDATE cart_table SET status = 'ordered' 
                         WHERE signupid = ? AND status = 'active'";
    $stmt = $conn->prepare($update_cart_query);

    if (!$stmt) {
        throw new Exception("Prepare statement failed for cart update: " . $conn->error);
    }

    $stmt->bind_param("i", $signupid);

    if (!$stmt->execute()) {
        throw new Exception("Update cart_table failed: " . $stmt->error);
    }

    // Update cart items status
    $update_cart_items_query = "UPDATE cart_items ci 
                               JOIN cart_table ct ON ci.cart_id = ct.cart_id 
                               SET ci.status = 'ordered' 
                               WHERE ci.signupid = ? AND (ci.status = 'active' OR ci.status IS NULL)
                               AND ct.status = 'ordered'";
    $stmt = $conn->prepare($update_cart_items_query);

    if (!$stmt) {
        throw new Exception("Prepare statement failed for cart items update: " . $conn->error);
    }

    $stmt->bind_param("i", $signupid);

    if (!$stmt->execute()) {
        throw new Exception("Update cart_items failed: " . $stmt->error);
    }

    // Commit transaction
    $conn->commit();

    // Fetch payment details from payment_table
    $fetch_payment_query = "SELECT * FROM payment_table WHERE order_id = ? AND signupid = ?";
    $stmt = $conn->prepare($fetch_payment_query);

    if ($stmt) {
        $stmt->bind_param("si", $order_id, $signupid);
        
        if ($stmt->execute()) {
            $payment_result = $stmt->get_result();
            
            if ($payment_result->num_rows > 0) {
                $payment_details = $payment_result->fetch_assoc();
            }
        }
    }

    // Store order ID in session for order confirmation page
    $_SESSION['last_order_id'] = $order_id;

    // For production, uncomment this to immediately redirect to confirmation page
    // header("Location: order_confirmation.php");
    // exit();

} catch (Exception $e) {
    // Rollback transaction if there was an error
    $conn->rollback();
    
    // Log the error (in a production environment)
    error_log("Payment processing error: " . $e->getMessage());

    // Redirect to error page
    header("Location: error.php?message=" . urlencode("There was an error processing your payment. Please try again or contact support."));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs - Payment Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .processing-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .success-icon {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .processing-title {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .processing-subtitle {
            color: #6c757d;
            font-size: 18px;
        }
        
        .processing-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .info-section {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            border: 1px solid #e9ecef;
        }
        
        .section-title {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 500;
            color: #6c757d;
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #2c3e50;
            font-size: 16px;
            font-weight: 500;
        }
        
        .status-paid {
            color: #28a745;
            font-weight: 600;
        }
        
        .btn-confirmation {
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            background-color: #0077cc;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-confirmation:hover {
            background-color: #005fa3;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .processing-container {
                padding: 20px;
            }
            
            .processing-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="processing-container">
        <div class="header">
            <i class="fas fa-check-circle success-icon"></i>
            <h1 class="processing-title">Payment Successful!</h1>
            <p class="processing-subtitle">Thank you for your purchase</p>
        </div>
        
        <div class="processing-info">
            <div class="info-section">
                <h2 class="section-title">Order Details</h2>
                <div class="info-item">
                    <span class="info-label">Order ID</span>
                    <div class="info-value"><?php echo htmlspecialchars($order_id); ?></div>
                </div>
                <div class="info-item">
                    <span class="info-label">Amount Paid</span>
                    <div class="info-value">₹<?php echo number_format($amount, 2); ?></div>
                </div>
                <?php if ($payment_details): ?>
                <div class="info-item">
                    <span class="info-label">Payment Date</span>
                    <div class="info-value"><?php echo htmlspecialchars($payment_details['created_at']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h2 class="section-title">Payment Information</h2>
                <div class="info-item">
                    <span class="info-label">Payment ID</span>
                    <div class="info-value"><?php echo htmlspecialchars($razorpay_payment_id); ?></div>
                </div>
                <?php if ($payment_details): ?>
                <div class="info-item">
                    <span class="info-label">Payment Method</span>
                    <div class="info-value"><?php echo htmlspecialchars($payment_details['payment_method']); ?></div>
                </div>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <div class="info-value status-paid"><?php echo htmlspecialchars($payment_details['payment_status']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="button-group">
            <a href="order_confirmation.php" class="btn-confirmation">
                <i class="fas fa-shopping-bag"></i> View Order Details
            </a>
            <a href="print_receipt.php?order_id=<?php echo urlencode($order_id); ?>" 
               class="btn-confirmation btn-receipt" target="_blank">
                <i class="fas fa-print"></i> Print Receipt
            </a>
        </div>
    </div>
    
    <script>
        // Optional: Redirect after a delay
        setTimeout(function() {
            window.location.href = "order_confirmation.php";
        }, 5000);
    </script>
</body>
</html>