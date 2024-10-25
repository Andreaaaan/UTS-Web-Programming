<?php
include 'db.php'; // Koneksi ke database
session_start();

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    // Cek apakah password dan konfirmasi password sesuai
    if ($password === $confirm_password) {
        // Hash password untuk keamanan
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Cek apakah email sudah ada di database
        $check_email_query = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $check_email_query);

        if (mysqli_num_rows($result) == 0) {
            // Query untuk memasukkan data ke database
            $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed_password')";

            if (mysqli_query($conn, $sql)) {
                echo "Registration successful! You can now <a href='index.php'>login</a>";
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($conn);
            }
        } else {
            echo "Email is already registered! Please use another email.";
        }
    } else {
        echo "Password and Confirm Password do not match!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>REGISTER</title>
    <link rel="stylesheet" type="text/css" href="assets/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Tambahkan jQuery untuk AJAX -->
</head>
<body>
    <h2>REGISTER</h2>
    <form method="POST" action="">
        <!-- Input username dan tombol cek -->
        <div class="input-group">
            <input type="text" name="username" id="username" placeholder="Username" required>
            <button type="button" id="check-username-btn">Check</button>
        </div>

        <!-- Input email -->
        <input type="email" name="email" placeholder="Email" required><br>

        <!-- Input password dan konfirmasi password -->
        <input type="password" name="password" placeholder="Password" required><br>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>

        <!-- Tombol submit -->
        <button type="submit" name="register">Register</button>
    </form>

    <div class="login-link">
        <p>Already have an account? <a href="index.php">Login</a></p>
    </div>

    <script>
    $(document).ready(function() {
        // Cek ketersediaan username ketika tombol "Check Username" diklik
        $("#check-username-btn").on("click", function() {
            var username = $("#username").val().trim(); // Ambil nilai dari input username

            if (username !== "") {
                $.ajax({
                    url: 'check_username.php', // URL ke file PHP yang memeriksa username
                    type: 'POST',
                    data: {username: username},
                    success: function(response) {
                        if (response == "taken") {
                            alert("Username is already taken!");
                        } else {
                            alert("Username is available!");
                        }
                    }
                });
            } else {
                alert("Please enter a username");
            }
        });
    });
    </script>
</body>
</html>
