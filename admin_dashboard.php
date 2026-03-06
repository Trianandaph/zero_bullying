<?php
include 'config.php';
checkLogin('admin');

$success_msg = "";
$error_msg = "";

// 1. Logika Tambah Guru BK (masih di halaman ini, namun bisa juga dipindah ke halaman terpisah)
if (isset($_POST['add_guru'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $no_telp  = mysqli_real_escape_string($conn, $_POST['no_telp']);

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        $error_msg = "Username sudah digunakan!";
    } else {
        mysqli_query($conn, "INSERT INTO users (fullname, username, password, role, no_telp) VALUES ('$fullname', '$username', '$password', 'guru', '$no_telp')");
        $success_msg = "Akun Guru berhasil dibuat!";
    }
}

// 3. Filter postingan (All / Trending)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$order_by = "p.created_at DESC";
if ($filter == 'trending') {
    $order_by = "(agree_count + disagree_count) DESC";
}

// 4. Ambil Postingan & Interaksi
$query_posts = "SELECT p.*, u.fullname, u.no_telp,
                (SELECT COUNT(*) FROM interactions WHERE post_id = p.id AND type = 'agree') as agree_count,
                (SELECT COUNT(*) FROM interactions WHERE post_id = p.id AND type = 'disagree') as disagree_count
                FROM posts p JOIN users u ON p.user_id = u.id ORDER BY $order_by";
$result_posts = mysqli_query($conn, $query_posts);

// 5. Statistik untuk sidebar
$total_users = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='user'"));
$total_posts = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM posts"));
$total_guru = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='guru'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Zero Bullying</title>
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

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Sidebar fixed dengan efek glass */
        .sidebar-fixed {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 1.5rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 5px 0 30px rgba(0, 0, 0, 0.5);
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

        /* Responsif */
        @media (max-width: 991px) {
            .sidebar-fixed {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar-fixed.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
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

        .menu-toggle {
            display: none;
        }

        /* Logo di sidebar */
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
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }
        .brand-name {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #b0d4ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Menu navigasi */
        .nav-menu {
            flex: 0 0 auto;
        }
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
            background: rgba(59, 130, 246, 0.15);
            color: white;
        }
        .nav-link-custom.active {
            background: var(--accent-blue);
            color: white;
            box-shadow: 0 8px 16px -4px rgba(59, 130, 246, 0.4);
        }

        /* Statistik sidebar */
        .stats-sidebar {
            background: rgba(0, 0, 0, 0.2);
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
            background: rgba(59, 130, 246, 0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--accent-blue);
            font-size: 1.5rem;
        }
        .stat-info h6 {
            margin-bottom: 0;
            font-weight: 700;
            color: var(--text-primary);
        }
        .stat-info small {
            color: var(--text-secondary);
        }

        /* Daftar permintaan akses di sidebar */
        .request-list {
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
            padding: 15px;
            margin-top: 10px;
        }
        .request-item {
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 8px;
            border-left: 3px solid var(--accent-blue);
        }

        /* User info di sidebar */
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
            background: transparent;
        }
        .logout-link:hover {
            background: rgba(248, 113, 113, 0.15);
            color: #fecaca;
        }

        /* Post Card */
        .post-card {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 28px;
            padding: 25px;
            margin-bottom: 25px;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
        }
        .post-card:hover {
            border-color: #334155;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.45);
        }

        /* Tombol hapus */
        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #f87171;
            border-radius: 30px;
            padding: 6px 16px;
            font-size: 0.9rem;
            transition: 0.2s;
            text-decoration: none;
        }
        .btn-delete:hover {
            background: #ef4444;
            color: white;
        }

        /* Nav tabs */
        .nav-tabs-custom {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            gap: 8px;
            margin-bottom: 25px;
        }
        .nav-tabs-custom .nav-link {
            color: var(--text-secondary);
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 500;
        }
        .nav-tabs-custom .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.05);
        }
        .nav-tabs-custom .nav-link.active {
            background: var(--accent-blue);
            color: white;
        }

        /* Tombol Interaksi */
        .btn-interact {
            background: none;
            border: none;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 40px;
            transition: 0.2s;
            color: var(--text-secondary);
            cursor: pointer;
        }
        .btn-interact:hover:not(:disabled) { 
            background: rgba(255,255,255,0.05);
            color: white;
        }
        .btn-interact:disabled {
            cursor: default;
        }
        .btn-interact.text-success { color: #4ade80; }
        .btn-interact.text-danger { color: #f87171; }
        .btn-interact.text-primary { color: var(--accent-blue); }

        /* Comment box */
        .comment-box {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 20px;
            margin-top: 15px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .comment-collapse {
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        .comment-item {
            background: rgba(255,255,255,0.02);
            border-left: 3px solid var(--accent-blue);
            border-radius: 16px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .comment-item .text-secondary-custom {
            color: var(--text-secondary);
        }
        .comment-item .text-white {
            color: var(--text-primary);
        }
        .text-secondary-custom { color: var(--text-secondary); }
        .white-text { color: var(--text-primary) !important; }
        .text-info {
            color: #7aa9ff !important;
        }
        .text-warning {
            color: #fbbf24 !important;
        }
    </style>
</head>
<body>

<!-- Tombol toggle untuk mobile -->
<button class="menu-toggle" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar Fixed -->
<div class="sidebar-fixed">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-shield-fill-check"></i></div>
        <span class="brand-name">Zero Bullying</span>
    </div>

    <!-- Menu Navigasi -->
    <div class="nav-menu">
        <a href="admin_dashboard.php" class="nav-link-custom active">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="daftar_siswa.php" class="nav-link-custom">
            <i class="bi bi-people-fill"></i> Daftar Siswa
        </a>
        <a href="tambah_guru.php" class="nav-link-custom">
            <i class="bi bi-person-plus-fill"></i> Tambah Guru BK
        </a>
        <a href="permintaan_akses.php" class="nav-link-custom">
            <i class="bi bi-envelope-paper"></i> Permintaan Akses
        </a>
    </div>

    <!-- Statistik -->
    <div class="stats-sidebar">
        <h6 class="fw-bold mb-3 white-text"><i class="bi bi-pie-chart-fill me-2"></i>Statistik</h6>
        <div class="stat-item-sidebar">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-info">
                <h6><?= $total_users ?></h6>
                <small>Total Siswa</small>
            </div>
        </div>
        <div class="stat-item-sidebar">
            <div class="stat-icon"><i class="bi bi-file-text"></i></div>
            <div class="stat-info">
                <h6><?= $total_posts ?></h6>
                <small>Total Postingan</small>
            </div>
        </div>
        <div class="stat-item-sidebar">
            <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
            <div class="stat-info">
                <h6><?= $total_guru ?></h6>
                <small>Guru BK</small>
            </div>
        </div>
    </div>

    <!-- User info & logout -->
    <div class="user-info">
        <div class="user-greeting">
            <i class="bi bi-person-circle me-2"></i> <?= htmlspecialchars($_SESSION['fullname']); ?>
        </div>
        <a href="logout.php" class="logout-link">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>

<!-- Konten Utama -->
<div class="main-content">
    <!-- Filter Tabs -->
    <ul class="nav nav-tabs-custom">
        <li class="nav-item">
            <a class="nav-link <?= $filter == 'all' ? 'active' : '' ?>" href="admin_dashboard.php?filter=all">All Stories</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $filter == 'trending' ? 'active' : '' ?>" href="admin_dashboard.php?filter=trending">Trending</a>
        </li>
    </ul>

    <!-- Daftar Postingan -->
    <?php if (mysqli_num_rows($result_posts) > 0): ?>
        <?php while($post = mysqli_fetch_assoc($result_posts)): ?>
            <div class="post-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="fw-bold text-white"><?= htmlspecialchars($post['fullname']) ?></span>
                        <span class="badge bg-secondary ms-2" style="background: var(--border-dark)!important;"><?= htmlspecialchars($post['no_telp']) ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-secondary-custom"><?= date('d M Y, H:i', strtotime($post['created_at'])) ?></small>
                        <!-- Tombol hapus postingan -->
                        <a href="delete_post.php?id=<?= $post['id'] ?>" class="btn-delete" onclick="return confirm('Hapus postingan ini?')">
                            <i class="bi bi-trash3"></i>
                        </a>
                    </div>
                </div>
                
                <p class="my-3 text-white fs-5">"<?= nl2br(htmlspecialchars($post['content'])) ?>"</p>
                
                <!-- Foto (jika ada) -->
                <?php if(!empty($post['photo'])): ?>
                <div class="post-photo mb-3 mt-3">
                    <img src="uploads/posts/<?= htmlspecialchars($post['photo']); ?>" alt="Post Photo" class="img-fluid rounded" style="max-width: 100%; max-height: 400px; object-fit: cover;">
                </div>
                <?php endif; ?>
                
                <?php 
                $pid = $post['id'];
                $q_c = mysqli_query($conn, "SELECT c.*, u.fullname, u.role FROM comments c JOIN users u ON c.user_id=u.id WHERE post_id='$pid' ORDER BY c.created_at ASC");
                $comment_count = mysqli_num_rows($q_c);
                ?>
                
                <!-- Tombol Interaksi -->
                <div class="d-flex gap-2 border-top pt-3 mt-3" style="border-color: rgba(255,255,255,0.1)!important;">
                    <button class="btn-interact text-success" disabled>
                        <i class="bi bi-hand-thumbs-up-fill me-1"></i> <?= $post['agree_count'] ?> Setuju
                    </button>
                    <button class="btn-interact text-danger" disabled>
                        <i class="bi bi-hand-thumbs-down-fill me-1"></i> <?= $post['disagree_count'] ?> Tidak Setuju
                    </button>
                    <button class="btn-interact text-primary" onclick="toggleComments(<?= $post['id'] ?>)">
                        <i class="bi bi-chat-left-text-fill me-1"></i> Komentar (<?= $comment_count ?>)
                    </button>
                </div>

                <!-- Area Komentar (hidden by default) -->
                <div id="comment-area-<?= $post['id'] ?>" class="comment-collapse" style="display: none;">
                    <div class="comment-box">
                        <h6 class="small fw-bold text-primary mb-3"><i class="bi bi-chat-quote me-1"></i>Diskusi & Tanggapan:</h6>
                        
                        <?php 
                        if($comment_count > 0):
                            mysqli_data_seek($q_c, 0); // Reset pointer
                            while($c = mysqli_fetch_assoc($q_c)):
                                if ($c['role'] == 'guru' || $c['role'] == 'admin') {
                                    $display_name = htmlspecialchars($c['fullname']) . " (" . ucfirst($c['role']) . ")";
                                    $text_color = "text-info"; 
                                    $icon = "bi-patch-check-fill";
                                } else {
                                    $display_name = "Anonymous Student";
                                    $text_color = "text-secondary-custom";
                                    $icon = "bi-person-fill";
                                }
                        ?>
                            <div class="comment-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold <?= $text_color ?>">
                                        <i class="bi <?= $icon ?> me-1"></i> <?= $display_name ?>
                                    </span>
                                    <small class="text-secondary-custom" style="font-size: 11px;"><?= date('d M, H:i', strtotime($c['created_at'])) ?></small>
                                </div>
                                <div class="small text-white"><?= htmlspecialchars($c['comment']) ?></div>
                            </div>
                        <?php 
                            endwhile;
                        else:
                            echo "<p class='text-secondary-custom small'>Belum ada komentar. Jadilah yang pertama memberikan dukungan!</p>";
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center text-secondary-custom py-5">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <p>Belum ada postingan.</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Toggle sidebar dan hamburger button
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar-fixed');
    const toggle = document.querySelector('.menu-toggle');
    sidebar.classList.toggle('show');
    toggle.classList.toggle('pushed');
}

// Tutup sidebar saat klik di luar (mobile)
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar-fixed');
    const toggle = document.querySelector('.menu-toggle');
    if (window.innerWidth <= 991) {
        if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
            sidebar.classList.remove('show');
            toggle.classList.remove('pushed');
        }
    }
});

// Toggle komentar
function toggleComments(postId) {
    var x = document.getElementById("comment-area-" + postId);
    if (x.style.display === "none" || x.style.display === "") {
        x.style.display = "block";
    } else {
        x.style.display = "none";
    }
}
</script>
</body>
</html>