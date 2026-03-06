<?php
include 'config.php';

// Pastikan session sudah dimulai di config.php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

// Ambil filter dari URL, default 'all'
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Logika Request Akses ke Admin
if (isset($_GET['request_id'])) {
    $pid = $_GET['request_id'];
    $gid = $uid;
    $cek = mysqli_query($conn, "SELECT id FROM access_requests WHERE post_id='$pid' AND guru_id='$gid'");
    if (mysqli_num_rows($cek) == 0) {
        mysqli_query($conn, "INSERT INTO access_requests (post_id, guru_id) VALUES ('$pid', '$gid')");
        // Redirect dengan filter yang sama
        $redirect_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        header("Location: guru_dashboard.php?filter=" . urlencode($redirect_filter));
        exit();
    }
}

// Data Stats untuk Sidebar Kiri
$total_monitored = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM posts"));
$pending_req = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM access_requests WHERE status='pending'"));
$rejected_req = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM access_requests WHERE guru_id='$uid' AND status='rejected' AND is_read = 0"));

// Ambil jumlah unread chat untuk badge (pesan dari siswa ke guru)
$unread_q = mysqli_query($conn, "SELECT COUNT(*) as unread FROM messages WHERE receiver_id = '$uid' AND is_read = 0");
$unread_data = mysqli_fetch_assoc($unread_q);
$unread_count = $unread_data['unread'] ?? 0;

// Tentukan urutan berdasarkan filter
$order_by = "p.created_at DESC";
if ($filter == 'trending') {
    $order_by = "(agree_count + disagree_count) DESC, p.created_at DESC";
}

// Ambil Postingan dengan subquery untuk menghitung interaksi
$query_posts = "SELECT p.*, 
                (SELECT COUNT(*) FROM interactions WHERE post_id = p.id AND type = 'agree') as agree_count,
                (SELECT COUNT(*) FROM interactions WHERE post_id = p.id AND type = 'disagree') as disagree_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                FROM posts p 
                ORDER BY $order_by";
$result_posts = mysqli_query($conn, $query_posts);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guru BK Panel - Zero Bullying</title>
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
            margin-bottom: 20px;
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

        /* Badge notifikasi */
        .badge-notif {
            margin-left: auto;
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 50px;
            font-weight: 600;
        }

        /* Ringkasan Aktivitas di sidebar */
        .stats-sidebar {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 20px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid rgba(255,255,255,0.05);
            backdrop-filter: blur(5px);
            flex: 0 0 auto;
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

        /* User info di sidebar */
        .user-info {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            flex-shrink: 0;
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

        .avatar-circle {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Tombol interaksi */
        .interaction-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-right: 20px;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 30px;
        }
        .interaction-btn:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }
        .interaction-btn i { font-size: 1.1rem; }

        /* Comment section */
        .comment-collapse { 
            display: none; 
            margin-top: 20px; 
            padding-top: 20px; 
            border-top: 1px solid rgba(255,255,255,0.1); 
        }
        
        .comment-input-area {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 24px;
            padding: 15px;
            color: var(--text-primary);
            width: 100%;
            min-height: 80px;
            resize: vertical;
            margin-bottom: 10px;
        }
        .comment-input-area:focus {
            border-color: var(--accent-blue);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .comment-input-area::placeholder { color: rgba(255,255,255,0.5); }

        .btn-guidance { 
            background: linear-gradient(105deg, var(--accent-blue), var(--accent-blue-hover));
            border: none;
            border-radius: 40px;
            font-weight: 600;
            padding: 8px 25px;
            color: white;
            transition: all 0.3s;
        }
        .btn-guidance:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.6);
        }

        .comment-item {
            background: rgba(255,255,255,0.02);
            border-left: 4px solid var(--accent-blue);
            border-radius: 16px;
            padding: 12px;
            margin-bottom: 12px;
        }

        /* Badge status */
        .badge-status {
            padding: 6px 16px;
            border-radius: 30px;
            font-weight: 500;
        }
        .badge-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        .badge-approved {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }

        /* Tombol WA */
        .btn-wa {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid #22c55e;
            color: #4ade80;
            border-radius: 30px;
            padding: 6px 16px;
            font-size: 0.9rem;
            transition: 0.2s;
            text-decoration: none;
        }
        .btn-wa:hover {
            background: #22c55e;
            color: white;
        }

        /* Nav tabs untuk filter */
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

        /* Text colors */
        .text-accent { color: var(--accent-blue); }
        .text-secondary-custom { color: var(--text-secondary); }
        .white-text { color: var(--text-primary) !important; }
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
        <a href="guru_dashboard.php" class="nav-link-custom active">
            <i class="bi bi-grid-fill"></i> Feed Laporan
        </a>
        <a href="chat_bk.php" class="nav-link-custom">
            <i class="bi bi-chat-fill"></i> Chat
            <?php if($unread_count > 0): ?>
                <span class="badge-notif"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>
        <a href="guru_approved.php" class="nav-link-custom">
            <i class="bi bi-person-check-fill"></i> Akses Disetujui
        </a>
        <a href="guru_rejected.php" class="nav-link-custom">
            <i class="bi bi-person-x-fill"></i> Permintaan Ditolak
            <?php if($rejected_req > 0): ?>
                <span class="badge-notif"><?= $rejected_req ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Ringkasan Aktivitas di Sidebar -->
    <div class="stats-sidebar">
        <h6 class="fw-bold mb-3 white-text"><i class="bi bi-bar-chart me-2"></i>Ringkasan Aktivitas</h6>
        <div class="stat-item-sidebar">
            <div class="stat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div class="stat-info">
                <h6><?= $total_monitored ?></h6>
                <small>Total Postingan</small>
            </div>
        </div>
        <div class="stat-item-sidebar">
            <div class="stat-icon" style="color: #fbbf24;"><i class="bi bi-clock-history"></i></div>
            <div class="stat-info">
                <h6><?= $pending_req ?></h6>
                <small>Menunggu Izin</small>
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
    <div class="row">
        <!-- Kolom Tengah (Feed) - Lebar penuh -->
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold white-text">Daftar Laporan Masuk</h4>
                <div class="text-secondary-custom small">Monitoring Aktivitas Siswa</div>
            </div>

            <!-- Tab Filter -->
            <ul class="nav nav-tabs-custom mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= $filter == 'all' ? 'active' : '' ?>" href="guru_dashboard.php?filter=all">All Stories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $filter == 'trending' ? 'active' : '' ?>" href="guru_dashboard.php?filter=trending">Trending</a>
                </li>
            </ul>

            <?php while($row = mysqli_fetch_assoc($result_posts)): 
                $pid = $row['id'];
            ?>
                <div class="post-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="avatar-circle me-3">
                                <i class="bi bi-person-fill white-text fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold white-text text-uppercase">Siswa Anonymous <?= $pid ?></h6>
                                <small class="text-secondary-custom"> <?= date('H:i, d M Y', strtotime($row['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>

                    <p class="fs-5 mb-4 white-text fw-normal">
                        "<?= nl2br(htmlspecialchars($row['content'])) ?>"
                    </p>

                    <!-- Foto (jika ada) -->
                    <?php if(!empty($row['photo'])): ?>
                    <div class="post-photo mb-4 mt-3">
                        <img src="uploads/posts/<?= htmlspecialchars($row['photo']); ?>" alt="Post Photo" class="img-fluid rounded" style="max-width: 100%; max-height: 400px; object-fit: cover;">
                    </div>
                    <?php endif; ?>

                    <div class="d-flex align-items-center mb-0 flex-wrap">
                        <button class="interaction-btn">
                            <i class="bi bi-hand-thumbs-up text-success"></i> <?= $row['agree_count'] ?> Setuju
                        </button>
                        <button class="interaction-btn">
                            <i class="bi bi-hand-thumbs-down text-danger"></i> <?= $row['disagree_count'] ?> Tidak Setuju
                        </button>
                        <button class="interaction-btn" onclick="toggleComments(<?= $pid ?>)">
                            <i class="bi bi-chat-left-text text-primary"></i> <?= $row['comment_count'] ?> Komentar
                        </button>

                        <?php 
                        // Query untuk cek status akses dan ambil nomor HP user terkait
                        $req_q = mysqli_query($conn, "SELECT ar.status, u.no_telp 
                                                      FROM access_requests ar 
                                                      JOIN posts p ON ar.post_id = p.id 
                                                      JOIN users u ON p.user_id = u.id 
                                                      WHERE ar.post_id='$pid' AND ar.guru_id='$uid'");
                        $req = mysqli_fetch_assoc($req_q);
                        
                        if (!$req): ?>
                            <a href="?request_id=<?= $pid ?>&filter=<?= $filter ?>" class="btn btn-primary ms-auto rounded-pill px-4 fw-bold" style="background: var(--accent-blue); border: none;">
                                <i class="bi bi-unlock-fill me-1"></i> Buka Identitas
                            </a>
                        <?php elseif ($req['status'] == 'pending'): ?>
                            <span class="badge-status badge-pending ms-auto px-4 py-2">Menunggu Admin</span>
                        <?php elseif ($req['status'] == 'approved'): 
                            // Button WhatsApp
                            $wa_num = preg_replace('/[^0-9]/', '', $req['no_telp']);
                            if(substr($wa_num, 0, 1) == '0') $wa_num = '62' . substr($wa_num, 1);
                        ?>
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <span class="badge-status badge-approved px-4 py-2">Akses Disetujui</span>
                                <a href="https://wa.me/<?= $wa_num ?>" target="_blank" class="btn-wa">
                                    <i class="bi bi-whatsapp me-1"></i> Chat WA
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="comment-section-<?= $pid ?>" class="comment-collapse">
                        <form action="post_comment.php" method="POST" class="mb-4">
                            <input type="hidden" name="post_id" value="<?= $pid ?>">
                            <textarea name="comment" class="comment-input-area" placeholder="Berikan arahan atau respon bimbingan konseling..."></textarea>
                            <div class="text-end">
                                <button type="submit" class="btn btn-guidance text-white">Kirim Respon</button>
                            </div>
                        </form>

                        <div class="comment-list">
                            <?php 
                            $q_c = mysqli_query($conn, "SELECT c.*, u.fullname, u.role FROM comments c JOIN users u ON c.user_id=u.id WHERE post_id='$pid' ORDER BY c.created_at DESC");
                            while($c = mysqli_fetch_assoc($q_c)): 
                                $isGuru = ($c['role'] == 'guru');
                            ?>
                                <div class="comment-item" style="border-left-color: <?= $isGuru ? '#3b82f6' : '#94a3b8' ?>;">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="fw-bold <?= $isGuru ? 'text-info' : 'white-text' ?>">
                                            <?= $isGuru ? htmlspecialchars($c['fullname']) . ' (Guru BK)' : 'Siswa' ?>
                                        </span>
                                        <span class="text-secondary-custom"><?= date('H:i', strtotime($c['created_at'])) ?></span>
                                    </div>
                                    <div class="white-text small"><?= htmlspecialchars($c['comment']) ?></div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
function toggleComments(postId) {
    const section = document.getElementById('comment-section-' + postId);
    if (!section) return;
    if (section.style.display === 'block') {
        section.style.display = 'none';
    } else {
        section.style.display = 'block';
    }
}
</script>

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
</script>

</body>
</html>