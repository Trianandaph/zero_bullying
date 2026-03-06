<?php
include 'config.php';

// Pastikan session sudah dimulai di config.php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

// Tandai semua notifikasi permintaan ditolak sebagai sudah dibaca
mysqli_query($conn, "UPDATE access_requests SET is_read = 1 WHERE guru_id = '$uid' AND status = 'rejected' AND is_read = 0");

// Ambil permintaan yang ditolak untuk guru ini
$query_rejected = "SELECT ar.*, p.content, u.fullname as student_name 
                   FROM access_requests ar 
                   JOIN posts p ON ar.post_id = p.id 
                   JOIN users u ON p.user_id = u.id 
                   WHERE ar.guru_id = '$uid' AND ar.status = 'rejected' 
                   ORDER BY ar.created_at DESC";
$result_rejected = mysqli_query($conn, $query_rejected);

// Ambil jumlah unread chat
$unread_q = mysqli_query($conn, "SELECT COUNT(*) as unread FROM messages WHERE receiver_id = '$uid' AND is_read = 0");
$unread_data = mysqli_fetch_assoc($unread_q);
$unread_count = $unread_data['unread'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Ditolak - Zero Bullying</title>
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
            margin-bottom: 40px;
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
            flex: 1;
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
            position: relative;
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

        /* Logout di bawah */
        .logout-container {
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
            background: rgba(248, 113, 113, 0.15);
            color: #fecaca;
        }

        /* Card */
        .card-custom {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            backdrop-filter: blur(5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
        }

        /* Alert box */
        .alert-rejected {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            color: #fca5a5;
        }

        .text-secondary-custom {
            color: var(--text-secondary);
        }

        .text-white {
            color: var(--text-primary) !important;
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
        <a href="guru_dashboard.php" class="nav-link-custom">
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
        <a href="guru_rejected.php" class="nav-link-custom active">
            <i class="bi bi-person-x-fill"></i> Permintaan Ditolak
        </a>
    </div>

    <!-- Logout di bawah -->
    <div class="logout-container">
        <div class="user-greeting">
            <i class="bi bi-person-circle me-2"></i> <?= htmlspecialchars($_SESSION['fullname']); ?>
        </div>
        <a href="logout.php" class="logout-link">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>

<!-- Konten Utama -->
<div class="main-content">
    <h3 class="mb-4" style="font-weight: 700;">
        <i class="bi bi-person-x-fill me-2" style="color: #f87171;"></i>Permintaan Ditolak
    </h3>

    <?php if(mysqli_num_rows($result_rejected) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result_rejected)): ?>
            <div class="card-custom">
                <div class="alert-rejected mb-3">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <strong>Permintaan Akses Ditolak</strong>
                    <p class="mb-0 small mt-1">Admin telah menolak permintaan akses Anda untuk cerita ini.</p>
                </div>

                <div class="mb-3">
                    <h6 class="small text-secondary-custom mb-2">Cerita yang Ditolak:</h6>
                    <p class="text-white mb-0"><?= nl2br(htmlspecialchars($row['content'])); ?></p>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
                    <small class="text-secondary-custom">
                        <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($row['created_at'])); ?>
                    </small>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card-custom text-center py-5">
            <i class="bi bi-check-circle fs-1 d-block mb-3" style="color: #4ade80;"></i>
            <p class="text-secondary-custom mb-0">Tidak ada permintaan yang ditolak. Semua permintaan Anda disetujui! 🎉</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Toggle sidebar untuk mobile
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
