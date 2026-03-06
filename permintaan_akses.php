<?php
include 'config.php';
checkLogin('admin');

// Logika Approve/Reject Request
if (isset($_GET['action']) && isset($_GET['req_id'])) {
    $req_id = intval($_GET['req_id']);
    $action = $_GET['action'];
    if ($action === 'approve') {
        mysqli_query($conn, "UPDATE access_requests SET status='approved' WHERE id=$req_id");
    } elseif ($action === 'reject') {
        mysqli_query($conn, "UPDATE access_requests SET status='rejected' WHERE id=$req_id");
    }
    header("Location: permintaan_akses.php");
    exit();
}

// Filter status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$allowed_statuses = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'pending';
}

// Query permintaan akses
$where_clause = ($status_filter === 'all') ? "" : "WHERE ar.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
$query_requests = "SELECT ar.*, u.fullname AS guru_name, u.role AS guru_role, p.content AS post_content, pu.fullname AS post_author
                   FROM access_requests ar 
                   JOIN users u ON ar.guru_id = u.id 
                   JOIN posts p ON ar.post_id = p.id
                   JOIN users pu ON p.user_id = pu.id
                   $where_clause
                   ORDER BY ar.created_at DESC";
$result_requests = mysqli_query($conn, $query_requests);

// Statistik
$total_users = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='user'"));
$total_posts = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM posts"));
$total_guru = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='guru'"));

// Hitung per status untuk badge
$count_pending = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM access_requests WHERE status='pending'"));
$count_approved = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM access_requests WHERE status='approved'"));
$count_rejected = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM access_requests WHERE status='rejected'"));
$count_all = $count_pending + $count_approved + $count_rejected;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Akses - Zero Bullying Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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

        /* Request card */
        .request-card {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 28px;
            padding: 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
        }
        .request-card:hover {
            border-color: #334155;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.45);
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
            align-items: flex-start;
        }
        .info-icon {
            width: 30px;
            color: var(--accent-blue);
            flex-shrink: 0;
            padding-top: 2px;
        }
        .info-label {
            width: 100px;
            color: var(--text-secondary);
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .info-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        /* Filter tabs */
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

        /* Status badges */
        .badge-pending {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
            padding: 5px 14px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .badge-approved {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            padding: 5px 14px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .badge-rejected {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            padding: 5px 14px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        /* Action buttons */
        .btn-approve {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid #22c55e;
            color: #4ade80;
            border-radius: 30px;
            padding: 6px 16px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.2s;
            text-decoration: none;
        }
        .btn-approve:hover {
            background: #22c55e;
            color: white;
        }
        .btn-reject {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #f87171;
            border-radius: 30px;
            padding: 6px 16px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.2s;
            text-decoration: none;
        }
        .btn-reject:hover {
            background: #ef4444;
            color: white;
        }

        .post-snippet {
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            padding: 10px 14px;
            margin-top: 10px;
            border-left: 3px solid var(--accent-blue);
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .text-secondary-custom { color: var(--text-secondary); }
        .white-text { color: var(--text-primary) !important; }
        .count-badge {
            background: rgba(255,255,255,0.1);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            margin-left: 6px;
        }
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
        <a href="tambah_guru.php" class="nav-link-custom"><i class="bi bi-person-plus-fill"></i> Tambah Guru BK</a>
        <a href="permintaan_akses.php" class="nav-link-custom active"><i class="bi bi-envelope-paper"></i> Permintaan Akses</a>
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
    <h4 class="fw-bold white-text mb-4"><i class="bi bi-envelope-paper me-2"></i>Permintaan Akses</h4>

    <!-- Filter Tabs -->
    <ul class="nav nav-tabs-custom">
        <li class="nav-item">
            <a class="nav-link <?= $status_filter == 'pending' ? 'active' : '' ?>" href="permintaan_akses.php?status=pending">
                <i class="bi bi-hourglass-split me-1"></i> Pending <span class="count-badge"><?= $count_pending ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter == 'approved' ? 'active' : '' ?>" href="permintaan_akses.php?status=approved">
                <i class="bi bi-check-circle me-1"></i> Approved <span class="count-badge"><?= $count_approved ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter == 'rejected' ? 'active' : '' ?>" href="permintaan_akses.php?status=rejected">
                <i class="bi bi-x-circle me-1"></i> Rejected <span class="count-badge"><?= $count_rejected ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter == 'all' ? 'active' : '' ?>" href="permintaan_akses.php?status=all">
                <i class="bi bi-list-ul me-1"></i> Semua <span class="count-badge"><?= $count_all ?></span>
            </a>
        </li>
    </ul>

    <?php if (mysqli_num_rows($result_requests) > 0): ?>
        <div class="row">
            <?php while($req = mysqli_fetch_assoc($result_requests)): ?>
                <div class="col-md-6">
                    <div class="request-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold white-text mb-0"><?= htmlspecialchars($req['guru_name']) ?></h5>
                            <?php if ($req['status'] === 'pending'): ?>
                                <span class="badge-pending"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                            <?php elseif ($req['status'] === 'approved'): ?>
                                <span class="badge-approved"><i class="bi bi-check-circle me-1"></i>Approved</span>
                            <?php else: ?>
                                <span class="badge-rejected"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                            <?php endif; ?>
                        </div>

                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-person-badge"></i></span>
                            <span class="info-label">Role</span>
                            <span class="info-value"><?= htmlspecialchars(ucfirst($req['guru_role'])) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-file-text"></i></span>
                            <span class="info-label">Post #</span>
                            <span class="info-value"><?= $req['post_id'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-person"></i></span>
                            <span class="info-label">Penulis</span>
                            <span class="info-value"><?= htmlspecialchars($req['post_author']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon"><i class="bi bi-calendar3"></i></span>
                            <span class="info-label">Tanggal</span>
                            <span class="info-value"><?= date('d M Y, H:i', strtotime($req['created_at'])) ?></span>
                        </div>

                        <div class="post-snippet">
                            <i class="bi bi-quote me-1"></i> "<?= htmlspecialchars(mb_strimwidth($req['post_content'], 0, 120, '...')) ?>"
                        </div>

                        <?php if ($req['status'] === 'pending'): ?>
                            <div class="d-flex gap-2 mt-3">
                                <a href="permintaan_akses.php?action=approve&req_id=<?= $req['id'] ?>&status=<?= $status_filter ?>" class="btn-approve" onclick="return confirm('Setujui permintaan ini?')">
                                    <i class="bi bi-check-lg me-1"></i> Approve
                                </a>
                                <a href="permintaan_akses.php?action=reject&req_id=<?= $req['id'] ?>&status=<?= $status_filter ?>" class="btn-reject" onclick="return confirm('Tolak permintaan ini?')">
                                    <i class="bi bi-x-lg me-1"></i> Reject
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center text-secondary-custom py-5">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <p>Tidak ada permintaan akses <?= ($status_filter !== 'all') ? 'dengan status ' . $status_filter : '' ?>.</p>
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
