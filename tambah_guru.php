<?php
include 'config.php';
checkLogin('admin');

$success_msg = "";
$error_msg = "";

if (isset($_POST['add_guru'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $no_telp  = mysqli_real_escape_string($conn, $_POST['no_telp']);

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        $error_msg = "Username sudah digunakan!";
    } else {
        // Hash password sebelum disimpan
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO users (fullname, username, password, role, no_telp) VALUES ('$fullname', '$username', '" . mysqli_real_escape_string($conn, $hashed_password) . "', 'guru', '$no_telp')");
        $success_msg = "Akun Guru berhasil dibuat!";
    }
}

// Statistik sidebar
$total_users = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='user'"));
$total_posts = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM posts"));
$total_guru = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='guru'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Guru BK - Zero Bullying Admin</title>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --bg-dark: #0f1115;
            --card-dark: #111827;
            --border-dark: #1f2937;
            --accent-blue: #3b82f6;
            --accent-blue-hover: #2563eb;
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
            --glass-bg: rgba(17, 24, 39, 0.92);
        }

        * { font-family: 'Inter', sans-serif; }

        body {
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .sidebar-fixed {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-right: 1px solid rgba(255,255,255,0.1);
            padding: 2rem 1.5rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 5px 0 30px rgba(0,0,0,0.5);
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .sidebar-fixed::-webkit-scrollbar {
            display: none;
        }

        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 20px 30px;
            position: relative;
            z-index: 10;
        }

        @media (max-width: 991px) {
            .sidebar-fixed {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar-fixed.show { transform: translateX(0); }
            .main-content { margin-left: 0; width: 100%; }
            .menu-toggle {
                display: block !important;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 999;
                background: var(--accent-blue);
                border: none;
                color: white;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                box-shadow: 0 4px 10px rgba(0,0,0,0.3);
                transition: left 0.3s ease;
            }
            .menu-toggle.pushed {
                left: 300px;
            }
        }

        .menu-toggle { display: none; }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .brand-icon {
            background: var(--accent-blue);
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 0 20px rgba(59,130,246,0.5);
        }
        .brand-name {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #b0d4ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu { flex: 0 0 auto; }
        .nav-link-custom {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .nav-link-custom i {
            font-size: 1.2rem;
            margin-right: 12px;
            width: 24px;
            text-align: center;
        }
        .nav-link-custom:hover {
            background: rgba(59,130,246,0.15);
            color: white;
        }
        .nav-link-custom.active {
            background: var(--accent-blue);
            color: white;
            box-shadow: 0 8px 16px -4px rgba(59,130,246,0.4);
        }

        .stats-sidebar {
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid rgba(255,255,255,0.05);
            backdrop-filter: blur(5px);
        }
        .stat-item-sidebar {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .stat-icon {
            width: 45px;
            height: 45px;
            background: rgba(59,130,246,0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--accent-blue);
            font-size: 1.5rem;
        }
        .stat-info h6 { margin-bottom: 0; font-weight: 700; color: var(--text-primary); }
        .stat-info small { color: var(--text-secondary); }

        .user-info {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .user-greeting {
            background: rgba(255,255,255,0.05);
            padding: 8px 18px;
            border-radius: 40px;
            font-weight: 500;
            backdrop-filter: blur(5px);
            margin-bottom: 15px;
            color: var(--text-primary);
        }
        .logout-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #f87171;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s;
            font-weight: 500;
        }
        .logout-link:hover {
            background: rgba(248,113,113,0.15);
            color: #fecaca;
        }

        /* Form card */
        .form-card {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 28px;
            padding: 30px;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
        }

        .form-control-custom {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.15);
            color: var(--text-primary);
            border-radius: 40px;
            padding: 12px 20px;
        }
        .form-control-custom:focus {
            background: rgba(0,0,0,0.5);
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.3);
            color: white;
            outline: none;
        }

        .btn-primary-custom {
            background: linear-gradient(105deg, var(--accent-blue), var(--accent-blue-hover));
            border: none;
            border-radius: 40px;
            padding: 12px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-primary-custom:hover {
            box-shadow: 0 10px 20px -5px rgba(59,130,246,0.6);
        }

        .text-secondary-custom { color: var(--text-secondary); }
        .white-text { color: var(--text-primary) !important; }
    </style>
</head>
<body>

<button class="menu-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>

<!-- Sidebar Fixed -->
<div class="sidebar-fixed">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-shield-fill-check"></i></div>
        <span class="brand-name">Zero Bullying</span>
    </div>
    <div class="nav-menu">
        <a href="admin_dashboard.php" class="nav-link-custom"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="daftar_siswa.php" class="nav-link-custom"><i class="bi bi-people-fill"></i> Daftar Siswa</a>
        <a href="tambah_guru.php" class="nav-link-custom active"><i class="bi bi-person-plus-fill"></i> Tambah Guru BK</a>
        <a href="permintaan_akses.php" class="nav-link-custom"><i class="bi bi-envelope-paper"></i> Permintaan Akses</a>
    </div>
    <div class="stats-sidebar">
        <h6 class="fw-bold mb-3 white-text"><i class="bi bi-pie-chart-fill me-2"></i>Statistik</h6>
        <div class="stat-item-sidebar">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-info"><h6><?= $total_users ?></h6><small>Total Siswa</small></div>
        </div>
        <div class="stat-item-sidebar">
            <div class="stat-icon"><i class="bi bi-file-text"></i></div>
            <div class="stat-info"><h6><?= $total_posts ?></h6><small>Total Postingan</small></div>
        </div>
        <div class="stat-item-sidebar">
            <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
            <div class="stat-info"><h6><?= $total_guru ?></h6><small>Guru BK</small></div>
        </div>
    </div>
    <div class="user-info">
        <div class="user-greeting"><i class="bi bi-person-circle me-2"></i> <?= htmlspecialchars($_SESSION['fullname']); ?></div>
        <a href="logout.php" class="logout-link"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
</div>

<!-- Konten Utama -->
<div class="main-content">
    <h4 class="fw-bold white-text mb-4"><i class="bi bi-person-plus-fill me-2 text-accent"></i>Tambah Guru BK</h4>

    <div class="form-card">
        <?php if($success_msg): ?>
            <div class="alert alert-success py-2 small" style="background: rgba(34,197,94,0.1); border: 1px solid #166534; color: #bbf7d0;"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if($error_msg): ?>
            <div class="alert alert-danger py-2 small" style="background: rgba(239,68,68,0.1); border: 1px solid #991b1b; color: #fecaca;"><?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label text-secondary-custom">Nama Lengkap</label>
                <input type="text" name="fullname" class="form-control form-control-custom" placeholder="Nama Lengkap" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-secondary-custom">Username</label>
                <input type="text" name="username" class="form-control form-control-custom" placeholder="Username" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-secondary-custom">Password</label>
                <input type="password" name="password" class="form-control form-control-custom" placeholder="Password" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-secondary-custom">Nomor Telepon</label>
                <input type="text" name="no_telp" class="form-control form-control-custom" placeholder="Contoh: 08123456789" required>
            </div>
            <button type="submit" name="add_guru" class="btn btn-primary-custom">Simpan Akun Guru</button>
        </form>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar-fixed');
    const toggle = document.querySelector('.menu-toggle');
    sidebar.classList.toggle('show');
    toggle.classList.toggle('pushed');
}

document.addEventListener('click', function(e) {
    const sidebar = document.querySelector('.sidebar-fixed');
    const toggle = document.querySelector('.menu-toggle');
    if (window.innerWidth <= 991 && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('show');
        toggle.classList.remove('pushed');
    }
});
</script>
</body>
</html>