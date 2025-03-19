<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

if (!isset($_SESSION['signupid'])) {
    header("Location: error.php");
    exit();
}

$signupid = $_SESSION['signupid'];

// Fetch latest payment details
$payment_query = "SELECT * FROM payment_table 
                 WHERE signupid = ? AND payment_status = 'paid'
                 ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($payment_query);
$stmt->bind_param("i", $signupid);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment = $payment_result->fetch_assoc();

// Get user details
$user_query = "SELECT * FROM signup WHERE signupid = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $signupid);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            padding: 20px;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 40px;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .receipt-header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .receipt-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .receipt-section h2 {
            color: #2c3e50;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-item {
            margin-bottom: 10px;
        }

        .info-label {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }

        .status-paid {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }

        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .total-row td {
            padding: 15px;
        }

        .print-button {
            text-align: center;
            margin-top: 30px;
        }

        .print-button button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .print-button button:hover {
            background-color: #0056b3;
        }

        .thank-you {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            color: #6c757d;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .receipt-container {
                box-shadow: none;
            }

            .print-button {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .receipt-container {
                padding: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>BabyCubs</h1>
            <p>Payment Receipt</p>
        </div>

        <div class="receipt-section">
            <h2>Payment Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Payment ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($payment['payment_id']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date</div>
                    <div class="info-value"><?php echo date('d M Y, h:i A', strtotime($payment['created_at'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value"><?php echo htmlspecialchars($payment['payment_method']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-paid">Paid</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="receipt-section">
            <h2>Amount Details</h2>
            <table>
                <tr>
                    <td>Total Amount</td>
                    <td style="text-align: right;">₹<?php echo number_format($payment['amount'], 2); ?></td>
                </tr>
            </table>
        </div>

        <?php if (!empty($user['address'])): ?>
        <div class="receipt-section">
            <h2>Shipping Address</h2>
            <p><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="thank-you">
            <p>Thank you for your purchase!</p>
            <p>For any queries, please contact our customer support.</p>
        </div>

        <div class="print-button">
            <button onclick="window.print()">
                <i class="fas fa-print"></i> Print Receipt
            </button>
        </div>
    </div>
</body>
</html> 