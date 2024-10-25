<?php
include 'db.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "todo_list_db");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$user_id = $_SESSION['user_id'];

// Fetch user information
$stmt = $mysqli->prepare("SELECT username, email, password, profile_picture FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$current_profile_picture = $user_data['profile_picture'] ?: 'assets/default_profile.png';

$message = '';

// Handle profile update
if (isset($_POST['update_profile'])) {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Verify current password
    if (password_verify($current_password, $user_data['password'])) {
        // Process profile picture upload
        if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['profile_picture']['name'];
            $file_tmp = $_FILES['profile_picture']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($file_ext, $valid_extensions)) {
                $new_filename = "assets/profile_pictures/" . uniqid() . "." . $file_ext;
                move_uploaded_file($file_tmp, $new_filename);

                // Update profile picture in database
                $stmt = $mysqli->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $stmt->bind_param("si", $new_filename, $user_id);
                $stmt->execute();
                $current_profile_picture = $new_filename;
            } else {
                $message = "Invalid file type for profile picture!";
            }
        }

        // Update username and email
        $stmt = $mysqli->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
        $stmt->execute();

        // Update password if provided
        if (!empty($new_password) && $new_password === $confirm_new_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();
            $message = "Profile and password updated successfully!";
        } else {
            $message = "Profile updated successfully!";
        }
    } else {
        $message = "Incorrect current password!";
    }
}

// Process profile picture upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $file_name = $_FILES['profile_picture']['name'];
    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION); // Get the file extension
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif']; // Define allowed file extensions

    // Validate the file extension
    if (in_array($file_ext, $allowed_extensions)) {
        // Define upload directory and check if it exists
        $upload_dir = 'assets/profile_pictures';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
        }

        // Create a unique filename for the uploaded file
        $new_filename = "$upload_dir/" . uniqid() . "." . $file_ext;

        // Attempt to move the uploaded file
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $new_filename)) {
            // Update profile picture path in the database
            $stmt = $mysqli->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_filename, $user_id);
            $stmt->execute();
            echo "Profile picture updated successfully!";
        } else {
            echo "";
        }
    } else {
        echo "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
    }
} else {
    echo "";
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/dashboard.css">
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-image: url('assets/paper.jpg');
            background-size: cover;
        }
        
        .profile-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            font-family: 'Courier New', Courier, monospace;
        }
        
        .profile-container h2 {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            margin: 0 auto 15px;
        }

        .btn-back {
            margin-top: 15px;
            display: block;
            width: 100%;
            text-align: center;
        }
        
        .message {
            text-align: center;
            font-size: 16px;
            color: #333;
            margin-top: 10px;
            margin-bottom: -10px;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">My Account</a>
        <div class="ml-auto">
            <a href="dashboard.php" class="btn btn-outline-light btn-back">Back to Dashboard</a>
        </div>
    </nav>

    <!-- Profile information and update form -->
    <div class="profile-container">
        <h2>Account Information</h2>
        <img src="<?php echo $current_profile_picture; ?>" alt="Profile Picture" class="profile-pic">

        <?php if ($message): ?>
            <p class="message <?php echo strpos($message, 'successfully') ? 'success' : 'error'; ?>"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Profile Picture Upload -->
            <div class="form-group">
                <label for="profile_picture">Profile Picture</label>
                <input type="file" class="form-control" name="profile_picture" id="profile_picture" accept="image/*">
            </div>

            <!-- Username field -->
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" name="username" id="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
            </div>

            <!-- Email field -->
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
            </div>

            <!-- Current password field -->
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" class="form-control" name="current_password" id="current_password" required placeholder="Enter current password">
            </div>

            <!-- New password and confirmation fields -->
            <div class="form-group">
                <label for="new_password">New Password (optional)</label>
                <input type="password" class="form-control" name="new_password" id="new_password" placeholder="Enter new password">
            </div>

            <div class="form-group">
                <label for="confirm_new_password">Confirm New Password</label>
                <input type="password" class="form-control" name="confirm_new_password" id="confirm_new_password" placeholder="Confirm new password">
            </div>

            <!-- Submit button -->
            <button type="submit" name="update_profile" class="btn btn-primary btn-block">Update Profile</button>
        </form>
    </div>
</body>
</html>
