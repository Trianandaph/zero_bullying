<?php
include 'config.php';
checkLogin('admin');

// Ambil data semua siswa (role = 'user')
$query_siswa = "SELECT id, fullname, username, email, kelas, jurusan, no_telp FROM users WHERE role = 'user' ORDER BY fullname ASC";
$result_siswa = mysqli_query($conn, $query_siswa);

// Statistik untuk sidebar (opsional, bisa diambil ulang atau pakai query terpisah)
$total_users = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='user'"));
$total_posts = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM posts"));
$total_guru = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='guru'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Siswa - Zero Bullying Admin</title>
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

        /* Card siswa */
        .student-card {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 28px;
            padding: 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
        }
        .student-card:hover {
            border-color: #334155;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.45);
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
            align-items: center;
        }
        .info-icon {
            width: 30px;
            color: var(--accent-blue);
        }
        .info-label {
            width: 80px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .info-value {
            color: var(--text-primary);
            font-weight: 500;
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
        <a href="daftar_siswa.php" class="nav-link-custom active"><i class="bi bi-people-fill"></i> Daftar Siswa</a>
        <a href="tambah_guru.php" class="nav-link-custom"><i class="bi bi-person-plus-fill"></i> Tambah Guru BK</a>
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
    <h4 class="fw-bold white-text mb-4"><i class="bi bi-people-fill me-2 text-accent"></i>Daftar Siswa</h4>

    <?php if (mysqli_num_rows($result_siswa) > 0): ?>
        <div class="row">
            <?php while($row = mysqli_fetch_assoc($result_siswa)): ?>
                <div class="col-md-6">
                    <div class="student-card">
                        <h5 class="fw-bold white-text mb-3"><?= htmlspecialchars($row['fullname']) ?></h5>
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-person-badge"></i></span>
                            <span class="info-label">Username</span>
                            <span class="info-value"><?= htmlspecialchars($row['username']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-envelope"></i></span>
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($row['email'] ?? '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-building"></i></span>
                            <span class="info-label">Kelas</span>
                            <span class="info-value"><?= htmlspecialchars($row['kelas'] ?? '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-book"></i></span>
                            <span class="info-label">Jurusan</span>
                            <span class="info-value"><?= htmlspecialchars($row['jurusan'] ?? '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-telephone"></i></span>
                            <span class="info-label">No. HP</span>
                            <span class="info-value"><?= htmlspecialchars($row['no_telp'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center text-secondary-custom py-5">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <p>Belum ada siswa terdaftar.</p>
        </div>
    <?php endif; ?>
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