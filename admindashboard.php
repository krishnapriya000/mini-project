
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

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// Function to send deactivation email
function sendDeactivationEmail($email) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'krishnapriyarajesh0@gmail.com'; 
        $mail->Password   = 'xwry zrcw biqh sgwd'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('krishnapriyarajesh0@gmail.com', 'BabyCubs');
        $mail->addAddress($email);
        $mail->Subject = 'Account Deactivation Notice';
        $mail->Body    = "Dear User,\n\nYour account has been deactivated by the administrator. If you believe this was done in error, please contact our support team.\n\nBest regards,\nBabyCubs";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Fetch total users and sellers
$totalUsersQuery = "SELECT COUNT(*) AS total_users FROM user_table";
$totalSellersQuery = "SELECT COUNT(*) AS total_sellers FROM seller";
$totalUsersResult = mysqli_query($conn, $totalUsersQuery);
$totalSellersResult = mysqli_query($conn, $totalSellersQuery);
$totalUsers = mysqli_fetch_assoc($totalUsersResult)['total_users'];
$totalSellers = mysqli_fetch_assoc($totalSellersResult)['total_sellers'];

// Handle user/seller actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $success = false;
    $signupid = null;
    $new_status = '';

    // Determine status and which table to update
    if ($action === 'activate_user' || $action === 'deactivate_user') {
        $new_status = ($action === 'activate_user') ? 'active' : 'inactive';
        $stmt = $conn->prepare("UPDATE user_table SET status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_status, $id);
        $stmt->execute();
        $stmt->close();

        // Get signupid from user_table
        $stmt = $conn->prepare("SELECT signupid FROM user_table WHERE user_id = ?");
    } elseif ($action === 'activate_seller' || $action === 'deactivate_seller') {
        $new_status = ($action === 'activate_seller') ? 'active' : 'inactive';
        $stmt = $conn->prepare("UPDATE seller SET status = ? WHERE seller_id = ?");
        $stmt->bind_param("si", $new_status, $id);
        $stmt->execute();
        $stmt->close();

        // Get signupid from seller table
        $stmt = $conn->prepare("SELECT signupid FROM seller WHERE seller_id = ?");
    }

    // Fetch the signupid if query was set
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($signupid);
        $stmt->fetch();
        $stmt->close();
    }

    // If signup ID is found, update status in signup table
    if ($signupid) {
        $stmt = $conn->prepare("UPDATE signup SET status = ? WHERE signupid = ?");
        $stmt->bind_param("si", $new_status, $signupid);
        $success = $stmt->execute();
        $stmt->close();
    }

    // If deactivating, send email
    if ($new_status === 'inactive' && $success) {
        $emailQuery = "SELECT email FROM signup WHERE signupid = ?";
        $stmt = $conn->prepare($emailQuery);
        $stmt->bind_param("i", $signupid);
        $stmt->execute();
        $stmt->bind_result($email);
        $stmt->fetch();
        $stmt->close();

        if ($email) {
            sendDeactivationEmail($email);
        }
    }

    $_SESSION[$success ? 'message' : 'error'] = $success ? "Action completed successfully" : "Action failed";
    header("Location: admindashboard.php");
    exit();
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Fetch user and seller data
$usersResult = mysqli_query($conn, "SELECT * FROM user_table");
$sellersResult = mysqli_query($conn, "SELECT * FROM seller");




// session_start();
// require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// // Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }
// // Set headers to prevent caching
// header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Cache-Control: post-check=0, pre-check=0", false);
// header("Pragma: no-cache");
// // session_start();
// // require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php'); 
// // $database_name = "baby_db";
// // mysqli_select_db($conn, $database_name);
// // // // Check if the admin is logged in
// // // if (!isset($_SESSION['admin_logged_in'])) {
// // //     header("Location: login.php");
// // //     exit();
// // // }

// // // Fetch total users and sellers from the database

// // // Set headers to prevent caching
// // header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// // header("Cache-Control: post-check=0, pre-check=0", false);
// // header("Pragma: no-cache");
// $totalUsersQuery = "SELECT COUNT(*) AS total_users FROM user_table";
// $totalSellersQuery = "SELECT COUNT(*) AS total_sellers FROM seller";

// $totalUsersResult = mysqli_query($conn, $totalUsersQuery);
// $totalSellersResult = mysqli_query($conn, $totalSellersQuery);

// $totalUsers = mysqli_fetch_assoc($totalUsersResult)['total_users'];
// $totalSellers = mysqli_fetch_assoc($totalSellersResult)['total_sellers'];

// // Handle user activation/deactivation or deletion
// // Handle user and seller actions
// if (isset($_POST['action']) && isset($_POST['id'])) {
//     $id = intval($_POST['id']);
//     $action = $_POST['action'];
//     $success = false;

//     switch ($action) {
//         case 'activate_user':
//             $stmt = $conn->prepare("UPDATE user_table SET status = 'active' WHERE user_id = ?");
//             $stmt->bind_param("i", $id);
//             $success = $stmt->execute();
//             break;
//         case 'deactivate_user':
//             $stmt = $conn->prepare("UPDATE user_table SET status = 'inactive' WHERE user_id = ?");
//             $stmt->bind_param("i", $id);
//             $success = $stmt->execute();
//             break;
//         case 'activate_seller':
//             $stmt = $conn->prepare("UPDATE seller SET status = 'active' WHERE seller_id = ?");
//             $stmt->bind_param("i", $id);
//             $success = $stmt->execute();
//             break;
//         case 'deactivate_seller':
//             $stmt = $conn->prepare("UPDATE seller SET status = 'inactive' WHERE seller_id = ?");
//             $stmt->bind_param("i", $id);
//             $success = $stmt->execute();
//             break;
//     }

//     if ($success) {
//         $_SESSION['message'] = "Action completed successfully";
//     } else {
//         $_SESSION['error'] = "Action failed";
//     }

//     header("Location: admindashboard.php");
//     exit();
// }
// // Logout functionality
// if (isset($_GET['logout'])) {
//     session_destroy();
//     header("Location: index.php");
//     exit();
// }

// // Fetch user and seller data
// $usersQuery = "SELECT * FROM user_table";
// $sellersQuery = "SELECT * FROM seller";

// $usersResult = mysqli_query($conn, $usersQuery);
// $sellersResult = mysqli_query($conn, $sellersQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { display: flex; background:rgb(248, 191, 125); color: #333; }

        /* Sidebar */
        .sidebar { width: 250px; background:rgb(179, 69, 10); padding: 20px; position: fixed; height: 100%; color: white; }
        .sidebar h2 { color:rgb(240, 225, 163); text-align: center; margin-bottom: 20px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li { padding: 12px; cursor: pointer; transition: 0.3s; border-radius: 5px; }
        .sidebar ul li:hover, .sidebar ul .active { background:rgb(241, 170, 103); }
        .sidebar ul li a { text-decoration: none; color: white; display: block; }

        /* Main Content */
        .main-content { flex: 1; margin-left: 250px; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px; background:rgb(179, 69, 10); color: white; border-bottom: 2px solid #ffcc00; }
        .logout { background: #ff4500; color: #fff; padding: 8px 12px; border: none; cursor: pointer; border-radius: 4px; transition: 0.3s; }
        .logout:hover { background: #cc3700; }

        /* Stats */
        .stats { display: flex; gap: 20px; margin-top: 20px; }
        .stat-card { background:rgb(179, 69, 10); padding: 20px; flex: 1; text-align: center; border-radius: 8px; color:rgb(234, 228, 239); }
        .stat-card h3 { margin-bottom: 10px; }

        /* Table */
        .table-container { margin-top: 20px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 4px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background:rgb(179, 69, 10); color: white; }
        .btn { padding: 5px 10px; border: none; cursor: pointer; border-radius: 4px; }
        .btn-approve { background: #27ae60; color: white; }
        .btn-reject { background: #e74c3c; color: white; }
    </style>
</head>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { display: flex; background:rgb(248, 191, 125); color: #333; }

        /* Sidebar */
        .sidebar { width: 250px; background:rgb(179, 69, 10); padding: 20px; position: fixed; height: 100%; color: white; }
        .sidebar h2 { color:rgb(240, 225, 163); text-align: center; margin-bottom: 20px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li { padding: 12px; cursor: pointer; transition: 0.3s; border-radius: 5px; }
        .sidebar ul li:hover, .sidebar ul .active { background:rgb(241, 170, 103); }
        .sidebar ul li a { text-decoration: none; color: white; display: block; }

        /* Main Content */
        .main-content { flex: 1; margin-left: 250px; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px; background:rgb(179, 69, 10); color: white; border-bottom: 2px solid #ffcc00; }
        .logout { background: #ff4500; color: #fff; padding: 8px 12px; border: none; cursor: pointer; border-radius: 4px; transition: 0.3s; }
        .logout:hover { background: #cc3700; }

        /* Stats */
        .stats { display: flex; gap: 20px; margin-top: 20px; }
        .stat-card { background:rgb(179, 69, 10); padding: 20px; flex: 1; text-align: center; border-radius: 8px; color:rgb(234, 228, 239); }
        .stat-card h3 { margin-bottom: 10px; }

        /* Table */
        .table-container { margin-top: 20px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 4px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background:rgb(179, 69, 10); color: white; }
        .btn { padding: 5px 10px; border: none; cursor: pointer; border-radius: 4px; }
        .btn-approve { background: #27ae60; color: white; }
        .btn-reject { background: #e74c3c; color: white; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>BabyCubs</h2>
        <ul>
            <li class="active"><a href="#">Dashboard</a></li>
            <li><a href="index.php">Home</a></li>
            <li><a href="#Manage Users">Manage Users</a></li>
            <li><a href="#Manage Seller">Manage Sellers</a></li>
            <li><a href="manage categories.php">Manage Categories</a></li>
            <li><a href="admin_orders.php">Orders</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Welcome, Admin!</h2>
        </div>

        <div class="stats">
            <div class="stat-card"><h3>Total Users</h3><p><?php echo $totalUsers; ?></p></div>
            <div class="stat-card"><h3>Total Sellers</h3><p><?php echo $totalSellers; ?></p></div>
        </div>

        <section id="Manage Users">
            <div class="table-container">
                <h3>Manage Users</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Reg Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($usersResult)) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['reg_type']); ?></td>
                                <td><?php echo ucfirst($user['status']); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $user['user_id']; ?>">
                                        <?php if ($user['status'] === 'inactive') { ?>
                                            <button class="btn btn-approve" name="action" value="activate_user">Activate</button>
                                        <?php } else { ?>
                                            <button class="btn btn-reject" name="action" value="deactivate_user">Deactivate</button>
                                        <?php } ?>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="Manage Seller">
            <div class="table-container">
                <h3>Manage Sellers</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Seller Name</th>
                            <th>Business Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($seller = mysqli_fetch_assoc($sellersResult)) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($seller['seller_name']); ?></td>
                                <td><?php echo htmlspecialchars($seller['business_name']); ?></td>
                                <td><?php echo ucfirst($seller['status']); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $seller['seller_id']; ?>">
                                        <?php if ($seller['status'] === 'inactive' || $seller['status'] === 'pending') { ?>
                                            <button class="btn btn-approve" name="action" value="activate_seller">Activate</button>
                                        <?php } else { ?>
                                            <button class="btn btn-reject" name="action" value="deactivate_seller">Deactivate</button>
                                        <?php } ?>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>