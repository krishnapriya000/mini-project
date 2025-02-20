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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #fff1f1;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background-color: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-icon {
            width: 50px;
            height: 50px;
            background-color: #ff9999;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .sidebar a {
            color: #666;
            text-decoration: none;
            padding: 10px;
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #ff9999;
            color: white;
        }

        .profile-container {
            flex: 1;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .form-group input:disabled {
            background-color: #f5f5f5;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-save {
            background-color: #ff9999;
            color: white;
        }

        .btn-delete {
            background-color: #ff6b6b;
            color: white;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile-icon">
            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
        </div>
        <a href="index.php">Home</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="profile-container">
        <div class="profile-header">
            <h1>My Profile</h1>
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
                           required pattern="[0-9]{10}" maxlength="10">
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
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


            <div class="buttons">
                <button type="submit" name="update_profile" class="btn btn-save">Save Changes</button>
                <button type="submit" name="delete_account" class="btn btn-delete" 
                        onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.')">
                    Delete Account
                </button>
            </div>
        </form>
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
    </script>
</body>
</html>