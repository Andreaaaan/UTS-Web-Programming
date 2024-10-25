<?php
include 'db.php';
session_start();

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "todo_list_db");

if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

$user_id = $_SESSION['user_id']; // Ambil user_id dari sesi yang login

// Query untuk mendapatkan informasi pengguna
$stmt = $mysqli->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$profile_picture = $user_data['profile_picture'] ? $user_data['profile_picture'] : 'assets/default_profile.png'; // Gunakan default jika tidak ada

// Menambahkan To-Do List dan Tasks
if (isset($_POST['add_list'])) {
    $title = $_POST['title'];

    $stmt = $mysqli->prepare("INSERT INTO to_do_list (user_id, title) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $title);
    $stmt->execute();

    $to_do_list_id = $stmt->insert_id;
    foreach ($_POST['tasks'] as $task) {
        $stmt = $mysqli->prepare("INSERT INTO tasks (to_do_list_id, description) VALUES (?, ?)");
        $stmt->bind_param("is", $to_do_list_id, $task);
        $stmt->execute();
    }
    header("Location: dashboard.php");
}

// Hapus To-Do List
if (isset($_POST['delete_list'])) {
    $to_do_list_id = $_POST['to_do_list_id'];

    // Verifikasi bahwa user memiliki akses ke to-do list ini
    $stmt = $mysqli->prepare("DELETE FROM to_do_list WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $to_do_list_id, $user_id);
    $stmt->execute();
    header("Location: dashboard.php");
}

// Filter task
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sql_filter = "";
if ($filter == 'completed') {
    $sql_filter = "AND is_completed = 1";
} elseif ($filter == 'incomplete') {
    $sql_filter = "AND is_completed = 0";
}

// Pencarian berdasarkan judul dan deskripsi task
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$sql_search = "";
if (!empty($search_query)) {
    $sql_search = "AND (to_do_list.title LIKE '%$search_query%' OR tasks.description LIKE '%$search_query%')";
}

// Update task completion status
if (isset($_POST['save_tasks'])) {
    $task_ids = $_POST['task_ids']; // Semua ID task
    $completed_tasks = isset($_POST['is_completed']) ? $_POST['is_completed'] : []; // Task yang dicentang

    foreach ($task_ids as $task_id) {
        // Jika task dicentang, is_completed = 1, jika tidak dicentang is_completed = 0
        $is_completed = in_array($task_id, $completed_tasks) ? 1 : 0;

        $stmt = $mysqli->prepare("UPDATE tasks SET is_completed = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_completed, $task_id);
        $stmt->execute();
    }
    header("Location: dashboard.php");
}

// Menampilkan To-Do List dan Tasks berdasarkan user yang login, filter, dan search query
$to_do_lists = $mysqli->query("
    SELECT DISTINCT to_do_list.* 
    FROM to_do_list 
    LEFT JOIN tasks ON to_do_list.id = tasks.to_do_list_id 
    WHERE to_do_list.user_id = $user_id $sql_filter $sql_search
");

if (!$to_do_lists) {
    die("Query error: " . $mysqli->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/dashboard.css">
    <style>
        /* Notepad-like design for tasks */
        .notepad {
            background-color: transparent;
            border: 2px solid black;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-radius: 10px;
            font-family: 'Courier New', Courier, monospace;
            width: 100%;
            max-width: 350px;
            transition: all 0.3s;
        }

        /* Center align for add new to-do list */
        .add-list-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-bottom: 20px;
        }

        /* Sidebar styling */
        .side-navbar {
            height: 100%;
            width: 0;
            position: fixed;
            top: 0;
            right: 0;
            background-color: #333;
            z-index: 10000;
            overflow-x: hidden;
            transition: width 0.3s ease;
            padding-top: 60px;
        }
        /* Styling untuk tombol Account Information dan Logout di side navbar */
        .side-navbar .btn-outline-light,
        .side-navbar .btn-outline-danger {
            color: #ffffff; /* Warna teks */
            border-color: #ffffff; /* Warna border */
            width: 80%; /* Lebar tombol disesuaikan */
            padding: 10px; /* Padding seragam */
            font-family: 'Courier New', Courier, monospace; /* Konsistensi font */
            font-size: 16px; /* Ukuran font seragam */
            text-align: center; /* Posisikan teks di tengah */
            margin: 10px auto; /* Posisikan tombol di tengah dan beri jarak antar tombol */
            display: block; /* Pastikan tombol tampil sebagai block */
        }

        /* Hover styling untuk tombol Account Information */
        .side-navbar .btn-outline-light:hover {
            background-color: #f8f9fa; /* Warna latar saat hover */
            color: #343a40; /* Warna teks saat hover */
            border-color: #f8f9fa; /* Warna border saat hover */
        }

        /* Hover styling untuk tombol Logout */
        .side-navbar .btn-outline-danger:hover {
            background-color: #dc3545; /* Warna latar saat hover */
            color: #ffffff; /* Teks tetap putih saat hover */
            border-color: #dc3545; /* Warna border saat hover */
        }
        /* Styling untuk username di side navbar */
        .profile-info h3 {
            font-family: 'Courier New', Courier, monospace; /* Font konsisten */
            color: #ffffff; /* Warna teks putih */
            font-size:28px; /* Ukuran font yang cocok */
            font-weight: bold; /* Tambahkan ketebalan agar lebih menonjol */
        }

        /* Styling for task items in the notepad */
        .notepad-task {
            border-bottom: 1px dashed #c2b280;
            padding: 10px 0;
        }

        /* Title of the notepad */
        .notepad h3 {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 24px;
        }

        /* Checkbox and label styling for tasks */
        .notepad-task input[type="checkbox"] {
            margin-right: 10px;
        }

        /* Save and delete buttons */
        .btn-save {
            background-color: #8fbc8f;
            color: white;
        }

        .btn-save:hover {
            background-color: #6b8e23;
        }

        .btn-delete {
            background-color: #ff6347;
            color: white;
        }

        .btn-delete:hover {
            background-color: #e60000;
        }

        h1 {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bolder;
        }
        
        h2 {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bolder;
        }

        /* Grid layout for to-do lists */
        .to-do-list-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            justify-items: center;
        }

        @media (max-width: 768px) {
            .to-do-list-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .to-do-list-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Styling for search box and button */
        .search-box {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            font-family: 'Courier New', Courier, monospace; /* Menggunakan font yang sama */
        }

        .search-box input {
            width: 70%; /* Perbesar lebar input */
            margin-right: 10px;
            font-family: 'Courier New', Courier, monospace; /* Menggunakan font yang sama */
            font-size: 16px; /* Ukuran font */
        }

        .search-box button {
            background-color: transparent;
            border: none;
        }

        .search-box button img {
            width: 24px;
        }
    </style>
</head>
<body style="background-image: url('assets/paper.jpg'); background-size: cover;">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">
            <img src="assets/doit.png" alt="Logo" width="200">
        </a>

        <!-- Profile picture -->
        <div class="ml-auto">
            <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" class="rounded-circle" width="50" height="50" onclick="toggleSideNavbar()" style="cursor: pointer;">
        </div>
    </nav>

    <!-- Side Navbar -->
    <div id="sideNavbar" class="side-navbar">
        <a href="javascript:void(0)" class="closebtn" onclick="toggleSideNavbar()">&times;</a>
        <div class="profile-info text-center mt-4">
            <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" class="rounded-circle" width="100" height="100">
            <h3 class="text-white mt-3"><?php echo $_SESSION['username']; ?></h3>
        </div>
        <div class="text-center mt-4">
            <a href="profile.php" class="btn btn-outline-light btn-block">Account information</a>
            <a href="logout.php" class="btn btn-outline-danger btn-block">Logout</a>
        </div>
    </div>

    <!-- Tasks and lists section (Notepad-like) -->
    <section class="mt-4">
        <h1 class="text-center">WHAT'S ON YOUR MIND?</h1>

        <!-- Form to add a new to-do list and tasks -->
        <div class="add-list-container">
            <div class="notepad">
                <h3>Add New To-Do List</h3>
                <form action="dashboard.php" method="post">
                    <input type="text" name="title" placeholder="To-Do List Title" class="form-control add-task-input" required>
                    <div class="tasks-input">
                        <input type="text" name="tasks[]" placeholder="Task Description" class="form-control add-task-input" required>
                    </div>
                    <button type="button" class="btn btn-outline-secondary mb-3" onclick="addTaskInput()">Add More Task</button>
                    <button type="submit" name="add_list" class="btn btn-primary">Add To-Do List</button>
                </form>
            </div>
        </div>

        <!-- Filter Tasks -->
        <div class="filters mt-4 text-center">
            <a href="dashboard.php?filter=all" class="btn btn-secondary">All Tasks</a>
            <a href="dashboard.php?filter=completed" class="btn btn-success">Completed</a>
            <a href="dashboard.php?filter=incomplete" class="btn btn-warning">Incomplete</a>
        </div>

        <!-- Search box for task search -->
        <div class="search-box mt-3">
            <form action="dashboard.php" method="GET" class="d-flex">
                <input class="form-control" type="search" name="search" placeholder="Search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                <button class="btn btn-outline-dark" type="submit">
                    <img src="assets/search-icon.jpg" alt="Search">
                </button>
            </form>
        </div>

        <h2 class="mt-4 text-center">WHAT YOU HAVE TO DO</h2>

        <!-- Display each To-Do List in a grid layout -->
        <div class="to-do-list-grid">
            <?php while ($list = $to_do_lists->fetch_assoc()) { ?>
                <div class="notepad">
                    <h3 class="card-title">
                        <?php echo $list['title']; ?>
                    </h3>
                    <form action="dashboard.php" method="post">
                        <?php
                        $tasks = $mysqli->query("SELECT * FROM tasks WHERE to_do_list_id = {$list['id']} $sql_filter");
                        while ($task = $tasks->fetch_assoc()) { ?>
                            <div class="notepad-task">
                                <input type="hidden" name="task_ids[]" value="<?php echo $task['id']; ?>">
                                <input type="checkbox" name="is_completed[]" value="<?php echo $task['id']; ?>" <?php echo $task['is_completed'] ? 'checked' : ''; ?>>
                                <label><?php echo $task['description']; ?></label>
                            </div>
                        <?php } ?>

                        <!-- Tombol Save untuk menyimpan perubahan task -->
                        <button type="submit" name="save_tasks" class="btn btn-save">Save</button>
                    </form>
                    <!-- Delete List Button -->
                    <form action="dashboard.php" method="post" onsubmit="return confirm('Are you sure you want to delete this list?')" class="mt-2">
                        <input type="hidden" name="to_do_list_id" value="<?php echo $list['id']; ?>">
                        <button type="submit" name="delete_list" class="btn btn-delete mt-2">Delete List</button>
                    </form>
                </div>
            <?php } ?>
        </div>
    </section>

    <!-- Script for sidebar toggle -->
    <script>
        function addTaskInput() {
            const taskDiv = document.querySelector('.tasks-input');
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'tasks[]';
            input.placeholder = 'Task Description';
            input.classList.add('form-control', 'add-task-input');
            taskDiv.appendChild(input);
        }

        function toggleSideNavbar() {
            const sideNavbar = document.getElementById("sideNavbar");
            const overlay = document.querySelector(".sidebar-overlay");

            if (sideNavbar.style.width === "250px") {
                sideNavbar.style.width = "0"; // Tutup sidebar
                overlay.style.display = "none"; // Sembunyikan overlay
            } else {
                sideNavbar.style.width = "250px"; // Buka sidebar
                overlay.style.display = "block"; // Tampilkan overlay
            }

            overlay.onclick = function() {
                sideNavbar.style.width = "0"; // Tutup sidebar
                overlay.style.display = "none"; // Sembunyikan overlay
            };
        }
    </script>
</body>
</html>
