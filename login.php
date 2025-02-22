<?php
session_start();
ob_start();

require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        // Modified query to join with seller table
        $sql = "SELECT s.*, sel.seller_id 
                FROM signup s 
                LEFT JOIN seller sel ON s.signupid = sel.signupid 
                WHERE s.username = ?";
                
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Database query preparation failed: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            // Debug information
            error_log("User found: " . print_r($row, true));

            if ($row['status'] === 'inactive') {
                $error_message = "Your account has been deactivated. Please contact support.";
            } elseif ($row['status'] === 'pending') {
                $error_message = "Your account is pending approval.";
            } elseif ($row['status'] === 'active') {
                if (password_verify($password, $row['password'])) {
                    // Set all necessary session variables
                    $_SESSION['user_id'] = $row['signupid'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['reg_type'] = $row['reg_type'];
                    
                    // For sellers, also store seller_id
                    if ($row['reg_type'] === 'seller') {
                        $_SESSION['seller_id'] = $row['seller_id'];
                    }

                    // Log the login attempt
                    error_log("Successful login - Username: $username, Type: " . $row['reg_type']);

                    // Insert login record
                    $login_sql = "INSERT INTO login (signup_id, login_date) VALUES (?, CURDATE())";
                    $login_stmt = $conn->prepare($login_sql);
                    $login_stmt->bind_param("i", $row['signupid']);
                    $login_stmt->execute();

                    // Clear any existing output
                    ob_clean();

                    // Redirect based on user type
                    switch ($row['reg_type']) {
                        case 'admin':
                            header("Location: admindashboard.php");
                            break;
                        case 'seller':
                            header("Location: sellerdashboard.php");
                            break;
                        default:
                            header("Location: index.php");
                            break;
                    }
                    exit();
                } else {
                    error_log("Failed login attempt - Invalid password for username: $username");
                    $error_message = "Invalid password!";
                }
            } else {
                $error_message = "Invalid account status!";
            }
        } else {
            error_log("Failed login attempt - No user found for username: $username");
            $error_message = "No account found with this username.";
        }

        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url("./images/pic28.jpg");
            background-size: cover;
            margin: 0;
            padding: 20px;
        }

        .container {
            background: #e6e6e6;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            font-weight: 500;
        }

        input {
            display: block;
            width: 100%;
            padding: 12px;
            margin-bottom: 5px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        input:focus {
            border-color: #007BFF;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }

        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: #007BFF;
            border: none;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .login-btn:hover {
            background-color: rgb(97, 124, 248);
        }

        .signup-link {
            margin-top: 20px;
        }

        h2 {
            color: #333;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="login-btn">Login</button>

            <p class="forgot-password">
                <a href="forgot-password.php">Forgot Password?</a>
            </p>

            <p class="signup-link">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </p>
        </form>
    </div>
</body>
</html>
