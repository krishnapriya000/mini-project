<?php
session_start();
require 'config.php'; // Your database connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$signupid = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Fetch user details
$query = $conn->prepare("SELECT * FROM signup WHERE signupid = ?");
$query->bind_param("i", $signupid);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Check if user exists
if (!$user) {
    die("User not found.");
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Sanitize inputs
        $phone = preg_replace("/[^0-9]/", "", $_POST['phone']);
        $address = $conn->real_escape_string(trim($_POST['address']));
        $city = $conn->real_escape_string(trim($_POST['city']));
        $district = $conn->real_escape_string(trim($_POST['district']));
        
        // Validate phone number
        if (strlen($phone) == 10) {
            // First, add address-related columns if they don't exist
            $alter_query = "ALTER TABLE signup 
                ADD COLUMN IF NOT EXISTS address VARCHAR(255),
                ADD COLUMN IF NOT EXISTS city VARCHAR(100),
                ADD COLUMN IF NOT EXISTS district VARCHAR(100)";
            $conn->query($alter_query);
            
            // Update user profile
            $updateQuery = $conn->prepare("UPDATE signup SET phone = ?, address = ?, city = ?, district = ? WHERE signupid = ?");
            $updateQuery->bind_param("ssssi", $phone, $address, $city, $district, $signupid);
            
            if ($updateQuery->execute()) {
                $message = "Profile updated successfully!";
                $messageType = 'success';
                // Refresh page to show updated data
                header("Refresh:1");
            } else {
                $message = "Error updating profile: " . $conn->error;
                $messageType = 'error';
            }
        } else {
            $message = "Invalid phone number! Please enter 10 digits.";
            $messageType = 'error';
        }
    }
    
    // Handle account deletion
    if (isset($_POST['delete_account'])) {
        $conn->begin_transaction();
        try {
            // Delete from signup table
            $deleteQuery = $conn->prepare("DELETE FROM signup WHERE signupid = ?");
            $deleteQuery->bind_param("i", $signupid);
            $deleteQuery->execute();
            
            $conn->commit();
            session_destroy();
            header("Location: login.php?message=account_deleted");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error deleting account: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}
if (isset($_POST['delete_account'])) {
    $conn->begin_transaction();
    try {
        // Instead of deleting, update status to 'inactive'
        $updateStatusQuery = $conn->prepare("UPDATE signup SET status = 'inactive' WHERE signupid = ?");
        $updateStatusQuery->bind_param("i", $signupid);
        $updateStatusQuery->execute();

        $conn->commit(); // Commit the transaction

        session_destroy();
        header("Location: login.php?message=account_disabled");
        exit();
    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction on failure
        $message = "Error disabling account: " . $e->getMessage();
        $messageType = 'error';
    }
}



// List of districts
$districts = [
    'Thiruvananthapuram',
    'Kollam',
    'Pathanamthitta',
    'Alappuzha',
    'Kottayam',
    'Idukki',
    'Ernakulam',
    'Thrissur',
    'Palakkad',
    'Malappuram',
    'Kozhikode',
    'Wayanad',
    'Kannur',
    'Kasaragod'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs - My Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #fff9f9;
            min-height: 100vh;
            display: flex;
            color: #333;
        }

        .sidebar {
            width: 280px;
            background-color: white;
            padding: 30px 20px;
            box-shadow: 2px 0 15px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: sticky;
            top: 0;
            height: 100vh;
            transition: all 0.3s ease;
        }

        .profile-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ff9999, #ff6b6b);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(255, 107, 107, 0.3);
            transition: transform 0.3s;
        }

        .profile-icon:hover {
            transform: scale(1.05);
        }

        .sidebar a {
            color: #555;
            text-decoration: none;
            padding: 12px 20px;
            width: 100%;
            text-align: left;
            margin-bottom: 8px;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar a i {
            width: 24px;
            text-align: center;
        }

        .sidebar a:hover {
            background-color: #ff9999;
            color: white;
            transform: translateX(5px);
        }

        .sidebar a.active {
            background-color: #ff9999;
            color: white;
            font-weight: 500;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .profile-container {
            width: 100%;
            max-width: 900px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.05);
            padding: 40px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .profile-header h1 {
            color: #ff6b6b;
            font-size: 32px;
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
        }

        .profile-header h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, #ff9999, #ff6b6b);
            border-radius: 3px;
        }

        .message {
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-weight: 500;
            font-size: 15px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            background-color: #fafafa;
        }

        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 15px;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #ff9999;
            box-shadow: 0 0 0 3px rgba(255, 153, 153, 0.2);
            outline: none;
            background-color: white;
        }

        .form-group input:disabled {
            background-color: #f5f5f5;
            color: #666;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-save {
            background: linear-gradient(135deg, #ff9999, #ff6b6b);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 107, 107, 0.3);
        }

        .btn-delete {
            background: linear-gradient(135deg, #ff6b6b, #f44336);
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(244, 67, 54, 0.3);
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                padding: 20px;
                flex-direction: row;
                justify-content: space-around;
            }
            
            .profile-icon {
                display: none;
            }
            
            .sidebar a {
                padding: 10px;
                justify-content: center;
                margin-bottom: 0;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-container {
                padding: 25px;
            }
            
            .buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Floating animation for profile icon */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .floating {
            animation: float 3s ease-in-out infinite;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #ff9999;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #ff6b6b;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile-icon floating">
            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
        </div>
        <a href="index.php" class="active"><i class="fas fa-home"></i> Home</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>Update your personal information</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form id="profileForm" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                               required pattern="[0-9]{10}" maxlength="10" placeholder="Enter 10 digit phone number">
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Enter your address">
                    </div>

                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="Enter your city">
                    </div>

                    <div class="form-group">
                        <label>District</label>
                        <select name="district" required>
                            <option value="">Select District</option>
                            <?php foreach ($districts as $districtOption): ?>
                                <option value="<?php echo $districtOption; ?>" 
                                    <?php echo (isset($user['district']) && $user['district'] == $districtOption) ? 'selected' : ''; ?>>
                                    <?php echo $districtOption; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="buttons">
                    <button type="submit" name="update_profile" class="btn btn-save">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="submit" name="delete_account" class="btn btn-delete" 
                            onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.')">
                        <i class="fas fa-trash-alt"></i> Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Phone number validation
        const phoneInput = document.querySelector('input[name="phone"]');
        phoneInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
        });

        // Form change detection
        const form = document.getElementById('profileForm');
        let formChanged = false;

        form.addEventListener('input', () => {
            formChanged = true;
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Add focus effect to form inputs
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.querySelector('label').style.color = '#ff6b6b';
            });
            input.addEventListener('blur', () => {
                input.parentElement.querySelector('label').style.color = '#666';
            });
        });
    </script>
</body>
</html>