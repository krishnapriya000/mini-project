<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url("./images/pic28.jpg");
            background-repeat: no-repeat;
            background-position: center;
            margin: 0;
            background-size: cover;
            position: relative;
            padding: 20px;
        }
        .container {
            background: #e6e6e6;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            opacity: 0.95;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
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
        .helper-text {
            font-size: 12px;
            color: #752020;
            margin: 5px 0;
            min-height: 15px;
        }
        .error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
        .btn {
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
        .btn:hover {
            background: #0056b3;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #007BFF;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }


        .form-group input[type="radio"] {
    width: auto;
    display: inline-block;
    margin: 0;
}

.form-group label[style*="inline-flex"]:hover {
    cursor: pointer;
    color: #007BFF;
}



    </style>
</head>
<body>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');
require_once 'connect.php';
$database_name = "baby_db";
mysqli_select_db($conn, $database_name);

$usernameErr = $emailErr = $numberErr = $passwordErr = $confirmPasswordErr = "";
$username = $email = $number = $password = $confirmPassword = "";

function validatePhoneNumber($phoneNumber) {
    $cleanedNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
    
    if (empty($cleanedNumber)) {
        return ['isValid' => false, 'message' => 'Phone number is required'];
    }
    
    if (str_starts_with($cleanedNumber, '000') || str_starts_with($cleanedNumber, '12345')) {
        return ['isValid' => false, 'message' => 'Invalid phone number format'];
    }
    
    if (!in_array($cleanedNumber[0], ['9', '8', '7', '6'])) {
        return ['isValid' => false, 'message' => 'Phone number must start with 9, 8, 7, or 6'];
    }
    
    if (strlen($cleanedNumber) !== 10) {
        return ['isValid' => false, 'message' => 'Phone number must be exactly 10 digits'];
    }
    
    return ['isValid' => true, 'message' => ''];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Username validation
    if (empty($_POST["username"])) {
        $usernameErr = "Username is required";
    } else {
        $username = test_input($_POST["username"]);
        if (!preg_match("/^[a-zA-Z-' ]*$/", $username)) {
            $usernameErr = "Only letters and spaces are allowed";
        }
    }

    // Email validation
    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
    } else {
        $email = test_input($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }

    // Phone number validation
    if (empty($_POST["number"])) {
        $numberErr = "Mobile number is required";
    } else {
        $number = test_input($_POST["number"]);
        $phoneValidation = validatePhoneNumber($number);
        if (!$phoneValidation['isValid']) {
            $numberErr = $phoneValidation['message'];
        }
    }

    // Password validation
    if (empty($_POST["password"])) {
        $passwordErr = "Password is required";
    } else {
        $password = test_input($_POST["password"]);
        if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || 
            !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password)) {
            $passwordErr = "Password must be at least 8 characters, contain an uppercase, lowercase, and a number";
        }
    }

    // Confirm password validation
    if (empty($_POST["confirm-password"])) {
        $confirmPasswordErr = "Confirm Password is required";
    } else {
        $confirmPassword = test_input($_POST["confirm-password"]);
        if ($confirmPassword !== $password) {
            $confirmPasswordErr = "Passwords do not match";
        }
    }

    $reg_type = isset($_POST["reg_type"]) ? test_input($_POST["reg_type"]) : 'user';

    if ($reg_type === 'admin') {
        $check_admin_sql = "SELECT COUNT(*) as admin_count FROM signup WHERE reg_type = 'admin'";
        $check_admin_result = $conn->query($check_admin_sql);
        $check_admin_row = $check_admin_result->fetch_assoc();

        if ($check_admin_row['admin_count'] > 0) {
            die("<script>alert('Error: Only one admin account is allowed.'); window.location.href='signup.php';</script>");
        }
    }

    if (empty($usernameErr) && empty($emailErr) && empty($numberErr) && empty($passwordErr) && empty($confirmPasswordErr)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO signup (username, email, password, phone, status, reg_type) VALUES (?, ?, ?, ?, 'active', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $number, $reg_type);

        if ($stmt->execute()) {
            $signup_id = $stmt->insert_id;
            $current_date = date('Y-m-d');

            if ($reg_type === 'user') {
                $user_sql = "INSERT INTO user_table (signupid, username, reg_type) VALUES (?, ?, ?)";
                $user_stmt = $conn->prepare($user_sql);
                $user_stmt->bind_param("iss", $signup_id, $username, $reg_type);
                $user_stmt->execute();
            }
            
            if ($reg_type === 'seller') {
                $business_name = test_input($_POST["business_name"]);
                $description = test_input($_POST["description"]);
                $seller_sql = "INSERT INTO seller (signupid, seller_name, business_name, description, reg_type, registration_date, status) 
                               VALUES (?, ?, ?, ?, ?, CURDATE(), 'active')";
                $seller_stmt = $conn->prepare($seller_sql);
                $seller_stmt->bind_param("issss", $signup_id, $username, $business_name, $description, $reg_type);
                $seller_stmt->execute();
            }

            $login_sql = "INSERT INTO login (signup_id, login_date) VALUES (?, ?)";
            $login_stmt = $conn->prepare($login_sql);
            $login_stmt->bind_param("is", $signup_id, $current_date);
            $login_stmt->execute();

            header("Location: login.php");
            exit();
        } else {
            echo "<script>alert('Error registering user: " . $conn->error . "');</script>";
        }
    }
}

function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>

<div class="container">
    <h2>Sign Up</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="signupForm">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo $username; ?>" 
                   onfocus="showHelperText('username-helper', 'Enter your full name using only letters and spaces.')" 
                   onblur="clearHelperText('username-helper')">
            <span id="username-helper" class="helper-text"></span>
            <span class="error"><?php echo $usernameErr; ?></span>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo $email; ?>" 
                   onfocus="showHelperText('email-helper', 'Enter a valid email address (e.g., name@example.com)')" 
                   onblur="clearHelperText('email-helper')">
            <span id="email-helper" class="helper-text"></span>
            <span class="error"><?php echo $emailErr; ?></span>
        </div>

        <div class="form-group">
            <label for="number">Mobile Number</label>
            <input type="tel" id="number" name="number" value="<?php echo $number; ?>" 
                   onfocus="showHelperText('number-helper', 'Enter 10-digit number starting with 9, 8, 7, or 6')" 
                   onblur="clearHelperText('number-helper')">
            <span id="number-helper" class="helper-text"></span>
            <span class="error"><?php echo $numberErr; ?></span>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" 
                   onfocus="showHelperText('password-helper', 'Password must be at least 8 characters with uppercase, lowercase, and numbers')" 
                   onblur="clearHelperText('password-helper')">
            <span id="password-helper" class="helper-text"></span>
            <span class="error"><?php echo $passwordErr; ?></span>
        </div>

        <div class="form-group">
            <label for="confirm-password">Confirm Password</label>
            <input type="password" id="confirm-password" name="confirm-password" 
                   onfocus="showHelperText('confirm-password-helper', 'Re-enter your password')" 
                   onblur="clearHelperText('confirm-password-helper'); validateConfirmPassword();">
            <span id="confirm-password-helper" class="helper-text"></span>
            <span id="confirm-password-error" class="error"><?php echo $confirmPasswordErr; ?></span>
        </div>


        <div class="form-group">
    <label>Registration Type</label>
    <div style="display: flex; gap: 20px; margin: 10px 0;">
        <label style="display: inline-flex; align-items: center; gap: 5px;">
            <input type="radio" name="reg_type" value="user">
            User
        </label>
        <label style="display: inline-flex; align-items: center; gap: 5px;">
            <input type="radio" name="reg_type" value="seller">
            Seller
        </label>
        
    </div>
</div>






        <button type="submit" class="btn">Sign Up</button>
    </form>
    <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
</div>

<script>
function showHelperText(elementId, message) {
    document.getElementById(elementId).textContent = message;
}

function clearHelperText(elementId) {
    document.getElementById(elementId).textContent = "";
}

function validateConfirmPassword() {
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirm-password").value;
    const confirmPasswordError = document.getElementById("confirm-password-error");

    if (confirmPassword && confirmPassword !== password) {
        confirmPasswordError.textContent = "Passwords do not match";
    } else {
        confirmPasswordError.textContent = "";
    }
}

// Real-time phone number validation
document.getElementById("number").addEventListener("input", function(e) {
    const number = e.target.value;
    const errorSpan = document.querySelector("#number + .error");
    
    // Only allow digits
    if (!/^\d*$/.test(number)) {
        e.target.value = number.replace(/\D/g, '');
    }
    
    // Check starting digit
    if (number && !['9','8','7','6'].includes(number[0])) {
        errorSpan.textContent = "Must start with 9, 8, 7, or 6";
    } else {
        errorSpan.textContent = "";
    }
});
</script>
</body>
</html>