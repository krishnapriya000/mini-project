<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    //$login_result = validateLogin($conn, $username, $password);
    
    if ($login_result['success']) {
        $_SESSION['user_id'] = $login_result['user_id'];
        $_SESSION['username'] = $login_result['username'];
        header("Location: index.php");
        exit();
    } else {
        $error_message = $login_result['message'];
    }
}

$validation_passed = false;
$error_message = '';
$success_message = '';

$errors = [
    'username' => '',
    'password' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    if (empty($username)) {
        $errors['username'] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors['username'] = "Username must be at least 3 characters long";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = "Username can only contain letters, numbers, and underscores";
    }

    $password = $_POST['password'];
    if (empty($password)) {
        $errors['password'] = "Password is required";
    }

    if (empty(array_filter($errors))) {
        if ($username === "testuser" && $password === "Password123") {
            $validation_passed = true;
            $success_message = "Login successful!";
            $_SESSION['success_message'] = $success_message;
        } else {
            $errors['username'] = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* Your existing CSS remains the same */
        body {
            background-image: url('image/image1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #fff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

        .container {
            max-width: 400px;
            width: 100%;
            padding: 25px;
            background-color: rgba(17, 17, 17, 0.85);
            border: 1px solid rgba(51, 51, 51, 0.5);
            border-radius: 8px;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(5px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #fff;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 10px;
            background-color: rgba(34, 34, 34, 0.8);
            border: 1px solid rgba(51, 51, 51, 0.8);
            color: #fff;
            border-radius: 4px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
            border-color: #e17055;
            box-shadow: 0 0 5px rgba(225, 112, 85, 0.3);
        }

        .error-message {
            color: #ff6b6b;
            font-size: 14px;
            margin-top: 5px;
            min-height: 20px;
        }

        .helper-message {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .login-btn {
            background-color: #e17055;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background-color: #d65d45;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(225, 112, 85, 0.4);
        }

        .forgotten-password {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgotten-password:hover {
            color: #e17055;
        }

        h2 {
            color: #fff;
            margin-bottom: 25px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .success {
            color: #4CAF50;
            background-color: rgba(76, 175, 80, 0.1);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }

        a {
            color: #e17055;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #d65d45;
        }

        .valid-input {
            border-color: #4CAF50 !important;
        }

        .invalid-input {
            border-color: #ff6b6b !important;
        }

        .validation-status {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            display: none;
        }

        .valid-status {
            color: #4CAF50;
        }

        .invalid-status {
            color: #ff6b6b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>

        <?php if ($validation_passed): ?>
            <div class="success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validateLoginForm()">
            <div class="form-group">
                <label for="login-username">Username</label>
                <input type="text" 
                       name="username" 
                       id="login-username" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       oninput="validateField('username')"
                       onfocus="showHelper('username')"
                       onblur="hideHelper('username')"
                       placeholder="Enter your username"
                       autocomplete="username">
                <div class="validation-status" id="username-status"></div>
                <div class="error-message" id="username-error">
                    <?php echo htmlspecialchars($errors['username']); ?>
                </div>
                <div class="helper-message" id="username-helper">
                    Username must be at least 3 characters (letters, numbers, underscores only)
                </div>
            </div>

            <div class="form-group">
                <label for="login-password">Password</label>
                <input type="password" 
                       name="password" 
                       id="login-password"
                       oninput="validateField('password')"
                       onfocus="showHelper('password')"
                       onblur="hideHelper('password')"
                       placeholder="Enter your password"
                       autocomplete="current-password">
                <div class="validation-status" id="password-status"></div>
                <div class="error-message" id="password-error">
                    <?php echo htmlspecialchars($errors['password']); ?>
                </div>
                <div class="helper-message" id="password-helper">
                    Password must be at least 6 characters with uppercase, lowercase, and numbers
                </div>
            </div>

            <div class="form-group">
                <a href="forgot.php" class="forgotten-password">Forgot Password?</a>
            </div>

            <button type="submit" name="login" class="login-btn">Login</button>

            <p style="text-align: center; margin-top: 20px;">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </p>
        </form>
    </div>

    <script>
    function validateField(field) {
        const input = document.getElementById('login-' + field);
        const error = document.getElementById(field + '-error');
        const status = document.getElementById(field + '-status');
        const helper = document.getElementById(field + '-helper');
        let isValid = true;
        let errorMessage = '';

        // Reset states
        input.classList.remove('valid-input', 'invalid-input');
        status.style.display = 'none';
        error.textContent = '';
        helper.style.display = 'none';

        const value = input.value.trim();

        if (field === 'username') {
            if (value === '') {
                errorMessage = 'Username is required';
                isValid = false;
            } else if (value.length < 3) {
                errorMessage = 'Username must be at least 3 characters';
                isValid = false;
            } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                errorMessage = 'Only letters, numbers, and underscores allowed';
                isValid = false;
            }
        } else if (field === 'password') {
            if (value === '') {
                errorMessage = 'Password is required';
                isValid = false;
            } else if (value.length < 6) {
                errorMessage = 'Password must be at least 6 characters';
                isValid = false;
            } else if (!/[A-Z]/.test(value)) {
                errorMessage = 'Password must include an uppercase letter';
                isValid = false;
            } else if (!/[a-z]/.test(value)) {
                errorMessage = 'Password must include a lowercase letter';
                isValid = false;
            } else if (!/[0-9]/.test(value)) {
                errorMessage = 'Password must include a number';
                isValid = false;
            }
        }

        // Update UI based on validation results
        if (value !== '') {
            input.classList.add(isValid ? 'valid-input' : 'invalid-input');
            status.style.display = 'block';
            status.textContent = isValid ? '✓' : '✗';
            status.className = 'validation-status ' + (isValid ? 'valid-status' : 'invalid-status');
        }

        if (errorMessage) {
            error.textContent = errorMessage;
        } else if (value === '') {
            helper.style.display = 'block';
        }

        return isValid;
    }

    function showHelper(field) {
        const helper = document.getElementById(field + '-helper');
        const error = document.getElementById(field + '-error');
        
        if (!error.textContent) {
            helper.style.display = 'block';
        }
    }

    function hideHelper(field) {
        const helper = document.getElementById(field + '-helper');
        helper.style.display = 'none';
    }

    function validateLoginForm() {
        const usernameValid = validateField('username');
        const passwordValid = validateField('password');
        return usernameValid && passwordValid;
    }
    </script>
</body>
</html>