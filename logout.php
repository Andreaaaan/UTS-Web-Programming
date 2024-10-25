<?php
session_start();
session_destroy(); // Mengakhiri sesi
header("Location: index.php"); // Kembali ke halaman login
exit();
?>
