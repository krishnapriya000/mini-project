<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Ensure database connection is established
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Fetch seller details (assuming the seller is logged in and session contains signupid)
$seller_id = $_SESSION['signupid'] ?? 1; // Replace with actual session variable
$sql = "SELECT * FROM seller WHERE signupid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$seller = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <style>
        body {
            display: flex;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .dashboard-container {
            display: flex;
            width: 100%;
        }

        .sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            padding: 20px;
            min-height: 100vh;
        }

        .sidebar h2 {
            text-align: center;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 20px 0;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            transition: background 0.3s;
        }

        .sidebar ul li a:hover {
            background-color: #555;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
        }

        header {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 20px;
        }

        .stats {
            display: flex;
            gap: 20px;
        }

        .card {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
            flex: 1;
        }

        .orders {
            margin-top: 20px;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>Seller Dashboard</h2>
            <ul>
                <li><a href="#">Dashboard</a></li>
                <li><a href="product manage.php ">Manage Products</a></li>
                <li><a href="#">Orders</a></li>
                <li><a href="#">Earnings</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header>
                <h1>Welcome, <span><?php echo htmlspecialchars($seller['seller_name'] ?? 'Seller'); ?></span></h1>
                <p>Registration Type: <strong><?php echo htmlspecialchars($seller['reg_type'] ?? 'N/A'); ?></strong></p>
                <p>Status: <strong><?php echo htmlspecialchars($seller['status'] ?? 'Pending'); ?></strong></p>
            </header>

            <!-- Seller Details -->
            <section class="stats">
                <div class="card">Business Name: <strong><?php echo htmlspecialchars($seller['business_name'] ?? 'N/A'); ?></strong></div>
                <div class="card">Registered On: <strong><?php echo htmlspecialchars($seller['registration_date'] ?? 'N/A'); ?></strong></div>
                <div class="card">Description: <strong><?php echo htmlspecialchars($seller['description'] ?? 'N/A'); ?></strong></div>
            </section>

            <!-- Edit Seller Details -->
            <section class="orders">
                <h2>Edit Seller Details</h2>
                <form action="update_seller.php" method="POST">
                    <label>Business Name:</label>
                    <input type="text" name="business_name" value="<?php echo htmlspecialchars($seller['business_name'] ?? ''); ?>" required>
                    <label>Description:</label>
                    <textarea name="description" required><?php echo htmlspecialchars($seller['description'] ?? ''); ?></textarea>
                    <button type="submit">Update</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
