<?php
include 'db.php';
session_start();

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Query untuk mendapatkan user berdasarkan email
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    // Cek apakah email ditemukan
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Set sesi dengan user_id dan username
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];  // Simpan username dalam sesi

            // Redirect ke dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            echo "Password salah!";
        }
    } else {
        echo "Email tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>LOGIN</title>
    <link rel="stylesheet" type="text/css" href="assets/style.css">
    <style>
        /* CSS untuk efek transisi */
        body {
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        body.fade-in {
            opacity: 1;
        }

        body.fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <h2>LOGIN</h2>
    <form method="POST" action="index.php">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit" name="login">Login</button>
    </form>
    <div class="register-link">
        <p>Have no account? <a href="register.php">Register</a></p>
    </div>
    <script>
        // Transisi halaman saat dimuat
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('fade-in');
        });

        // Tambahkan efek transisi saat mengklik link
        document.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                var href = this.href;
                document.body.classList.add('fade-out');
                setTimeout(function() {
                    window.location.href = href;
                }, 500);
            });
        });
    </script>
</body>
</html>
