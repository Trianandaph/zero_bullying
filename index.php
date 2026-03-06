<?php
include 'config.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; 
$page = isset($_GET['page']) ? $_GET['page'] : 'home'; 

// Hitung pesan baru untuk siswa (dari guru)
$unread_count = 0;
if ($role == 'user') {
    $query_unread = "SELECT COUNT(*) as total FROM messages m 
                     JOIN users u ON m.sender_id = u.id 
                     WHERE m.receiver_id = '$uid' AND u.role = 'guru' AND m.is_read = 0";
    $result_unread = mysqli_query($conn, $query_unread);
    if ($result_unread && mysqli_num_rows($result_unread) > 0) {
        $row = mysqli_fetch_assoc($result_unread);
        $unread_count = (int)$row['total'];
    }
}

// Statistik User
$query_stats = "SELECT 
    (SELECT COUNT(*) FROM posts WHERE user_id = '$uid') as my_post_count,
    (SELECT COUNT(*) FROM interactions i JOIN posts p ON i.post_id = p.id WHERE p.user_id = '$uid' AND i.type = 'agree') as my_support_count";
$stats_res = mysqli_fetch_assoc(mysqli_query($conn, $query_stats));

// Include post handler untuk upload dan sensor
include 'post_handler.php';

// Inisialisasi variabel error
$error_message = null;

// Logika Posting
if (isset($_POST['send_post'])) {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        // Censor blocked words
        $censored_content = censorContent($content);
        $censored_content = mysqli_real_escape_string($conn, $censored_content);
        
        // Handle photo upload
        $photo_filename = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = uploadPostPhoto($_FILES['photo']);
            if (isset($upload_result['error'])) {
                $error_message = $upload_result['error'];
                // Jangan insert post jika upload gagal
            } else if (isset($upload_result['filename'])) {
                $photo_filename = $upload_result['filename'];
            }
        }
        
        // Insert post jika tidak ada error upload
        if (!isset($error_message)) {
            $photo_part = $photo_filename ? ",'$photo_filename'" : ", NULL";
            $query_post = "INSERT INTO posts (user_id, content, photo) VALUES ('$uid', '$censored_content'$photo_part)";
            if (mysqli_query($conn, $query_post)) {
                header("Location: index.php");
                exit();
            }
        }
    }
}

// Logika Filter & Halaman
$order_by = "p.created_at DESC";
if ($filter == 'trending') {
    $order_by = "(agree_count + disagree_count) DESC";
}

$where_clause = "";
if ($page == 'my_posts') {
    $where_clause = "WHERE p.user_id = '$uid'";
}

$query_get_posts = "SELECT p.*, 
    (SELECT COUNT(*) FROM interactions WHERE post_id = p.id AND type = 'agree') as agree_count,
    (SELECT COUNT(*) FROM interactions WHERE post_id = p.id AND type = 'disagree') as disagree_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p 
    $where_clause
    ORDER BY $order_by";

$result_posts = mysqli_query($conn, $query_get_posts);

// Cek role untuk menu Contact BK (sembunyikan untuk admin)
$show_contact_bk = ($role != 'admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zero Bullying - Dashboard</title>
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
        .sidebar-link {
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
        .sidebar-link i {
            font-size: 1.2rem;
            margin-right: 12px;
            width: 24px;
            text-align: center;
        }
        .sidebar-link:hover {
            background: rgba(59, 130, 246, 0.15);
            color: white;
        }
        .sidebar-link.active {
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

        /* Quick Stats Card */
        .stat-card { 
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(5px);
        }
        .stat-card .text-accent {
            color: var(--accent-blue);
            font-weight: 700;
        }
        .stat-card .text-success {
            color: #4ade80;
            font-weight: 700;
        }
        .stat-card .text-secondary-custom {
            color: var(--text-secondary);
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
        .post-card .card-body {
            padding: 1.5rem;
        }
        .post-card .text-secondary-custom {
            color: var(--text-secondary);
        }
        .post-card .text-white {
            color: var(--text-primary) !important;
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
        }
        .btn-interact:hover { 
            background: rgba(255,255,255,0.05);
            color: white;
        }
        .btn-interact.text-success { color: #4ade80; }
        .btn-interact.text-danger { color: #f87171; }
        .btn-interact.text-primary { color: var(--accent-blue); }

        /* Form kontrol */
        .form-control-dark {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255,255,255,0.15);
            color: var(--text-primary);
            border-radius: 40px;
            padding: 12px 20px;
            backdrop-filter: blur(5px);
        }
        .form-control-dark:focus {
            background: rgba(0, 0, 0, 0.5);
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            color: var(--text-primary);
            outline: none;
        }
        .form-control-dark::placeholder {
            color: rgba(255, 255, 255, 0.5);
            font-weight: 300;
        }

        /* Tombol biru */
        .btn-primary-custom {
            background: linear-gradient(105deg, var(--accent-blue), var(--accent-blue-hover));
            border: none;
            padding: 10px 28px;
            border-radius: 40px;
            font-weight: 600;
            transition: all 0.3s;
            color: white;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.6);
        }

        /* Upload Photo Area */
        .upload-photo-area {
            border: 2px dashed rgba(59, 130, 246, 0.4);
            border-radius: 20px;
            padding: 25px;
            background: rgba(59, 130, 246, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            text-align: center;
        }
        .upload-photo-area:hover {
            border-color: var(--accent-blue);
            background: rgba(59, 130, 246, 0.1);
        }
        .upload-photo-area.drag-over {
            border-color: var(--accent-blue);
            background: rgba(59, 130, 246, 0.2);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
        .upload-photo-area input[type="file"] {
            display: none;
        }
        .upload-icon {
            font-size: 2.5rem;
            color: var(--accent-blue);
            margin-bottom: 10px;
            display: block;
        }
        .upload-text {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 5px;
        }
        .upload-subtext {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* Photo Preview */
        .photo-preview {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 15px;
            background: rgba(0, 0, 0, 0.3);
        }
        .photo-preview img {
            width: 100%;
            height: auto;
            max-height: 300px;
            object-fit: cover;
            display: block;
        }
        .photo-preview-remove {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(248, 113, 113, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .photo-preview-remove:hover {
            background: rgba(248, 113, 113, 1);
            transform: scale(1.1);
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

        /* Comment box */
        .comment-box {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 20px;
            margin-top: 15px;
            border: 1px solid rgba(255,255,255,0.1);
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

    <!-- Quick Stats -->
    <div class="stat-card">
        <h6 class="text-uppercase small fw-bold text-secondary-custom mb-3"><i class="bi bi-bar-chart me-1"></i> Quick Stats</h6>
        <div class="row text-center">
            <div class="col-6">
                <div class="fs-4 fw-bold text-accent"><?= $stats_res['my_post_count']; ?></div>
                <div class="small text-secondary-custom">Stories</div>
            </div>
            <div class="col-6 border-start" style="border-color: rgba(255,255,255,0.1)!important;">
                <div class="fs-4 fw-bold text-success"><?= $stats_res['my_support_count']; ?></div>
                <div class="small text-secondary-custom">Supports</div>
            </div>
        </div>
    </div>

    <!-- Menu Navigasi -->
    <div class="nav-menu">
        <a href="index.php?page=home" class="sidebar-link <?= $page == 'home' ? 'active' : '' ?>">
            <i class="bi bi-house-door"></i> Home Feed
        </a>
        <a href="index.php?page=my_posts" class="sidebar-link <?= $page == 'my_posts' ? 'active' : '' ?>">
            <i class="bi bi-file-text"></i> My Posts
        </a>
        <?php if ($show_contact_bk): ?>
        <a href="chat_bk.php" class="sidebar-link">
            <i class="bi bi-chat-dots"></i> Contact BK
            <?php if ($role == 'user' && $unread_count > 0): ?>
                <span class="badge-notif"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
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
    <?php if($page == 'home'): ?>
    <!-- Baris Tombol Post dan Tab Filter -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <ul class="nav nav-tabs-custom mb-0">
            <li class="nav-item">
                <a class="nav-link <?= $filter == 'all' ? 'active' : '' ?>" href="index.php?page=<?= $page ?>&filter=all">All Stories</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter == 'trending' ? 'active' : '' ?>" href="index.php?page=<?= $page ?>&filter=trending">Trending</a>
            </li>
        </ul>
        <button class="btn btn-primary-custom" id="togglePostForm" type="button">
            <i class="bi bi-plus-circle me-2"></i>Post Cerita
        </button>
    </div>

    <!-- Form Posting (hidden, muncul jika tombol diklik atau ada error) -->
    <div id="postFormContainer" class="mb-4" style="<?= isset($error_message) && $error_message ? 'display:block;' : 'display:none;' ?>">
        <div class="post-card p-4">
            <form method="POST" enctype="multipart/form-data">
                <?php if(isset($error_message) && $error_message): ?>
                    <div class="alert alert-danger mb-3" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
                <textarea name="content" class="form-control form-control-dark mb-3" rows="3" placeholder="Bagikan ceritamu secara anonim..." required></textarea>
                
                <!-- Upload Photo Area -->
                <div class="upload-photo-area" id="upload-area">
                    <i class="bi bi-cloud-arrow-up upload-icon"></i>
                    <div class="upload-text">Tarik & Lepas Foto atau Klik</div>
                    <div class="upload-subtext">JPG, PNG, GIF, WebP (Max 5MB)</div>
                    <input type="file" id="photo-input" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>
                
                <!-- Photo Preview -->
                <div id="photo-preview" class="photo-preview" style="display: none;">
                    <img id="preview-img" src="" alt="Preview">
                    <button type="button" class="photo-preview-remove" onclick="removePhoto()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                
                <div class="text-end mt-3">
                    <button type="submit" name="send_post" class="btn btn-primary-custom px-4">
                        <i class="bi bi-send me-2"></i>Post Cerita
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Jika bukan home, tampilkan tab filter biasa tanpa tombol -->
    <ul class="nav nav-tabs-custom mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $filter == 'all' ? 'active' : '' ?>" href="index.php?page=<?= $page ?>&filter=all">All Stories</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $filter == 'trending' ? 'active' : '' ?>" href="index.php?page=<?= $page ?>&filter=trending">Trending</a>
        </li>
    </ul>
    <?php endif; ?>

    <!-- Daftar Postingan -->
    <?php if (mysqli_num_rows($result_posts) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result_posts)): ?>
            <div class="post-card">
                <div class="card-body">
                    <!-- Header -->
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold text-secondary-custom"><i class="bi bi-incognito me-1"></i> Anonymous Student</span>
                        <small class="text-secondary-custom"><?= date('d F Y, H:i', strtotime($row['created_at'])); ?></small>
                    </div>
                    
                    <!-- Konten -->
                    <p class="fs-5 text-white my-3">"<?= nl2br(htmlspecialchars($row['content'])); ?>"</p>
                    
                    <!-- Foto (jika ada) -->
                    <?php if(!empty($row['photo'])): ?>
                    <div class="post-photo mb-3 mt-3">
                        <img src="uploads/posts/<?= htmlspecialchars($row['photo']); ?>" alt="Post Photo" class="img-fluid rounded" style="max-width: 100%; max-height: 400px; object-fit: cover;">
                    </div>
                    <?php endif; ?>
                    
                    <!-- Tombol Interaksi -->
                    <div class="d-flex gap-2 border-top pt-3 mt-3" style="border-color: rgba(255,255,255,0.1)!important;">
                        <a href="interact.php?id=<?= $row['id']; ?>&type=agree" class="btn-interact text-success text-decoration-none">
                            <i class="bi bi-hand-thumbs-up-fill me-1"></i> <?= $row['agree_count']; ?> Setuju
                        </a>
                        <a href="interact.php?id=<?= $row['id']; ?>&type=disagree" class="btn-interact text-danger text-decoration-none">
                            <i class="bi bi-hand-thumbs-down-fill me-1"></i> <?= $row['disagree_count']; ?> Tidak Setuju
                        </a>
                        <button class="btn-interact text-primary" onclick="toggleComments(<?= $row['id']; ?>)">
                            <i class="bi bi-chat-left-text-fill me-1"></i> Komentar (<?= $row['comment_count']; ?>)
                        </button>
                    </div>

                    <!-- Area Komentar (hidden by default) -->
                    <div id="comment-area-<?= $row['id']; ?>" class="comment-collapse" style="display: none;">
                        <div class="comment-box">
                            <h6 class="small fw-bold text-primary mb-3"><i class="bi bi-chat-quote me-1"></i>Diskusi & Tanggapan:</h6>
                            
                            <?php 
                            $pid = $row['id'];
                            $query_comments = "SELECT c.*, u.fullname, u.role FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = '$pid' ORDER BY c.created_at ASC";
                            $res_comments = mysqli_query($conn, $query_comments);
                            
                            if(mysqli_num_rows($res_comments) > 0):
                                while($c = mysqli_fetch_assoc($res_comments)):
                                    if ($c['role'] == 'guru' || $c['role'] == 'admin') {
                                        $display_name = htmlspecialchars($c['fullname']) . " (" . ucfirst($c['role']) . ")";
                                        $text_color = "text-info"; 
                                        $icon = "bi-patch-check-fill";
                                    } else if ($c['user_id'] == $uid) {
                                        $display_name = "Anda (Saya)";
                                        $text_color = "text-warning";
                                        $icon = "bi-person-fill";
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
                                        <small class="text-secondary-custom" style="font-size: 11px;"><?= date('d M, H:i', strtotime($c['created_at'])); ?></small>
                                    </div>
                                    <div class="small text-white"><?= htmlspecialchars($c['comment']); ?></div>
                                </div>
                            <?php 
                                endwhile; 
                            else:
                                echo "<p class='text-secondary-custom small'>Belum ada komentar. Jadilah yang pertama memberikan dukungan!</p>";
                            endif;
                            ?>

                            <!-- Form Komentar -->
                            <form action="post_comment.php" method="POST" class="mt-3">
                                <input type="hidden" name="post_id" value="<?= $row['id']; ?>">
                                <div class="input-group">
                                    <input type="text" name="comment" class="form-control form-control-dark" placeholder="Tulis komentar/dukungan..." required>
                                    <button type="submit" class="btn btn-primary-custom">Kirim</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center text-secondary-custom py-5">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <p>Belum ada cerita. Jadilah yang pertama berbagi!</p>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleComments(postId) {
    var x = document.getElementById("comment-area-" + postId);
    if (x.style.display === "block") {
        x.style.display = "none";
    } else {
        x.style.display = "block";
    }
}

// Upload Photo Preview & Drag-Drop
const uploadArea = document.getElementById('upload-area');
const photoInput = document.getElementById('photo-input');
const photoPreview = document.getElementById('photo-preview');
const previewImg = document.getElementById('preview-img');

// Click to upload
uploadArea.addEventListener('click', () => photoInput.click());

// File selection
photoInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        showPreview(e.target.files[0]);
    }
});

// Drag & Drop
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('drag-over');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        photoInput.files = files;
        showPreview(files[0]);
    }
});

function showPreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        previewImg.src = e.target.result;
        photoPreview.style.display = 'block';
        uploadArea.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function removePhoto() {
    photoInput.value = '';
    photoPreview.style.display = 'none';
    uploadArea.style.display = 'block';
}

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

// Toggle form posting
const toggleBtn = document.getElementById('togglePostForm');
const formContainer = document.getElementById('postFormContainer');
if (toggleBtn && formContainer) {
    // Set teks tombol sesuai status awal (jika form tampil karena error)
    if (formContainer.style.display === 'block') {
        toggleBtn.innerHTML = '<i class="bi bi-dash-circle me-2"></i>Tutup Form';
    } else {
        toggleBtn.innerHTML = '<i class="bi bi-plus-circle me-2"></i>Post Cerita';
    }

    toggleBtn.addEventListener('click', function() {
        if (formContainer.style.display === 'none' || formContainer.style.display === '') {
            formContainer.style.display = 'block';
            toggleBtn.innerHTML = '<i class="bi bi-dash-circle me-2"></i>Tutup Form';
        } else {
            formContainer.style.display = 'none';
            toggleBtn.innerHTML = '<i class="bi bi-plus-circle me-2"></i>Post Cerita';
        }
    });
}
</script>
</body>
</html>