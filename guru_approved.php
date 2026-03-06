<?php
include 'config.php';

// Pastikan session sudah dimulai di config.php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

// Ambil jumlah unread chat untuk badge (sama seperti di dashboard)
$unread_q = mysqli_query($conn, "SELECT COUNT(*) as unread FROM messages WHERE receiver_id = '$uid' AND is_read = 0");
$unread_data = mysqli_fetch_assoc($unread_q);
$unread_count = $unread_data['unread'] ?? 0;

// Ambil jumlah permintaan yang ditolak untuk badge (hanya yang belum dibaca)
$rejected_req = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM access_requests WHERE guru_id='$uid' AND status='rejected' AND is_read = 0"));

// Ambil data akses yang sudah disetujui beserta info siswa
$query_approved = "SELECT ar.*, p.content, u.fullname, u.email, u.kelas, u.jurusan, u.no_telp 
                   FROM access_requests ar
                   JOIN posts p ON ar.post_id = p.id
                   JOIN users u ON p.user_id = u.id
                   WHERE ar.guru_id = '$uid' AND ar.status = 'approved'
                   ORDER BY ar.created_at DESC";
$result_approved = mysqli_query($conn, $query_approved);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Disetujui - Guru BK</title>
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

        /* Sidebar fixed (sama seperti dashboard) */
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
        .badge-notif {
            margin-left: auto;
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 50px;
            font-weight: 600;
        }

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
            background: rgba(248, 113, 113, 0.15);
            color: #fecaca;
        }

        /* Kartu siswa */
        .student-card {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 28px;
            padding: 25px;
            margin-bottom: 25px;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
        }
        .student-card:hover {
            border-color: #334155;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.45);
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            align-items: center;
        }
        .info-icon {
            width: 35px;
            color: var(--accent-blue);
            font-size: 1.2rem;
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

        .btn-wa-large {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid #22c55e;
            color: #4ade80;
            border-radius: 40px;
            padding: 10px 25px;
            font-weight: 600;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-wa-large:hover {
            background: #22c55e;
            color: white;
        }

        .text-secondary-custom { color: var(--text-secondary); }
        .white-text { color: var(--text-primary) !important; }
    </style>
</head>
<body>

<!-- Tombol toggle untuk mobile -->
<button class="menu-toggle" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar Fixed (sama dengan dashboard) -->
<div class="sidebar-fixed">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-shield-fill-check"></i></div>
        <span class="brand-name">Zero Bullying</span>
    </div>

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
        <a href="guru_approved.php" class="nav-link-custom active">
            <i class="bi bi-person-check-fill"></i> Akses Disetujui
        </a>
        <a href="guru_rejected.php" class="nav-link-custom">
            <i class="bi bi-person-x-fill"></i> Permintaan Ditolak
            <?php if($rejected_req > 0): ?>
                <span class="badge-notif"><?= $rejected_req ?></span>
            <?php endif; ?>
        </a>
    </div>

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
    <h4 class="fw-bold white-text mb-4"><i class="bi bi-person-check-fill me-2 text-accent"></i>Akses Disetujui</h4>

    <?php if (mysqli_num_rows($result_approved) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result_approved)): 
            // Format nomor WhatsApp
            $wa_num = preg_replace('/[^0-9]/', '', $row['no_telp']);
            if(substr($wa_num, 0, 1) == '0') $wa_num = '62' . substr($wa_num, 1);
        ?>
            <div class="student-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="fw-bold white-text"><?= htmlspecialchars($row['fullname']) ?></h5>
                    <span class="badge-status badge-approved px-3 py-1">Disetujui</span>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-envelope-fill"></i></span>
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($row['email'] ?? '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-telephone-fill"></i></span>
                            <span class="info-label">No. HP</span>
                            <span class="info-value"><?= htmlspecialchars($row['no_telp'] ?? '-') ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-building"></i></span>
                            <span class="info-label">Kelas</span>
                            <span class="info-value"><?= htmlspecialchars($row['kelas'] ?? '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-book-fill"></i></span>
                            <span class="info-label">Jurusan</span>
                            <span class="info-value"><?= htmlspecialchars($row['jurusan'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-3 rounded" style="background: rgba(0,0,0,0.2);">
                    <small class="text-secondary-custom">Laporan yang diakses:</small>
                    <p class="white-text mb-0">"<?= nl2br(htmlspecialchars($row['content'])) ?>"</p>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <a href="https://wa.me/<?= $wa_num ?>" target="_blank" class="btn-wa-large">
                        <i class="bi bi-whatsapp"></i> Chat WhatsApp
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-secondary-custom d-block mb-3"></i>
            <p class="text-secondary-custom">Belum ada akses yang disetujui.</p>
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

// Tutup sidebar di mobile
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