<?php
session_start();
require_once 'db.php';

// Check if seller is logged in
if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit();
}

// Get seller information
$seller_id = $_SESSION['seller_id'];
$conn = new mysqli($servername, $username, $password, $database);

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the input values
    $business_name = htmlspecialchars($_POST['business_name']);
    $description = htmlspecialchars($_POST['description']);
    
    // Prepare the update query
    $update_query = "UPDATE seller SET business_name = ?, description = ? WHERE seller_id = ?";
    $stmt = $conn->prepare($update_query);
    
    if ($stmt) {
        // Bind parameters
        $stmt->bind_param("ssi", $business_name, $description, $seller_id);
        
        // Execute the statement
        if ($stmt->execute()) {
            echo "<script>alert('Information updated successfully!');</script>";
        } else {
            echo "<script>alert('Error updating information: " . $stmt->error . "');</script>";
        }
        
        // Close the statement
        $stmt->close();
    } else {
        echo "<script>alert('Prepare failed: " . $conn->error . "');</script>";
    }
}

// Get seller data after potential update
$seller_query = "SELECT s.*, sg.email, sg.phone 
                 FROM seller s 
                 JOIN signup sg ON s.signupid = sg.signupid 
                 WHERE s.seller_id = ?";

$stmt = $conn->prepare($seller_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error); // Debugging message
}

$stmt->bind_param("i", $seller_id);
if (!$stmt->execute()) {
    die("Query execution failed: " . $stmt->error); // Debugging message
}

$result = $stmt->get_result();
if (!$result) {
    die("Get result failed: " . $conn->error); // Debugging message
}

$seller_data = $result->fetch_assoc();
if (!$seller_data) {
    die("No seller data found for this seller ID.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - BabyCubs</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        :root {
            --primary-color: #ff69b4;
            --secondary-color: #f8bbd0;
            --text-color: #333;
            --sidebar-width: 250px;
        }

        body {
            background-color: #f5f5f5;
        }

        /* Header Styles */
        .header {
            background-color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
        }

        .logo {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: bold;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 70px;
            width: var(--sidebar-width);
            height: calc(100vh - 70px);
            background-color: white;
            padding: 2rem 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar-item {
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-item:hover {
            background-color: var(--secondary-color);
            color: var(--primary-color);
        }

        .sidebar-item.active {
            background-color: var(--secondary-color);
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: 70px;
            padding: 2rem;
        }

        .dashboard-card {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
        }

        .info-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .info-value {
            color: var(--text-color);
            font-size: 1.1rem;
            font-weight: bold;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            min-height: 150px;
        }

        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #ff4081;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">BabyCubs</div>
        <div class="header-right">
            <a href="index.php" style="color: var(--text-color);"><i class="fas fa-home"></i></a>
            <div class="profile-icon">
              <a href="profile.php"> <?php echo strtoupper(substr($seller_data['seller_name'], 0, 1)); ?></a>
            </div>
            <a href="logout.php" style="color: var(--text-color);"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="sidebar">
        <a href="sellerdashboard.php" class="sidebar-item active">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        <a href="product manage.php" class="sidebar-item">
            <i class="fas fa-plus"></i>
            Add Product
        </a>
       
        <!-- <a href="earnings.php" class="sidebar-item">
            <i class="fas fa-dollar-sign"></i>
            Earnings
        </a> -->
        <a href="logout.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-card">
            <h2 style="margin-bottom: 2rem;">Seller Information</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Seller Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller_data['seller_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller_data['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller_data['phone']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller_data['status']); ?></div>
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Business Name</label>
                    <input type="text" name="business_name" class="form-input" 
                           value="<?php echo htmlspecialchars($seller_data['business_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Business Description</label>
                    <textarea name="description" class="form-textarea" required><?php echo htmlspecialchars($seller_data['description']); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">Update Information</button>
            </form>
        </div>
    </main>

    <script>
        // Add active class to current sidebar item
        const currentPath = window.location.pathname;
        const sidebarItems = document.querySelectorAll('.sidebar-item');
        sidebarItems.forEach(item => {
            if (item.getAttribute('href') === currentPath.split('/').pop()) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    </script>
</body>
</html>