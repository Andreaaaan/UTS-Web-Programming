<?php
include 'db.php'; // Koneksi ke database

if (isset($_POST['username'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    // Query untuk memeriksa apakah username sudah ada di database
    $check_username_query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $check_username_query);
    
    if (mysqli_num_rows($result) > 0) {
        // Username sudah ada
        echo "taken";
    } else {
        // Username tersedia
        echo "available";
    }
}
?>
