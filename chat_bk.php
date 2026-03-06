<?php
include 'config.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Admin tidak diizinkan mengakses chat
if ($role == 'admin') {
    header("Location: index.php");
    exit();
}

// Tombol kembali
$back_link = ($role == 'guru') ? 'guru_dashboard.php' : 'index.php';

// Ambil ID tujuan chat (validasi integer)
$selected_id = isset($_GET['to']) ? (int)$_GET['to'] : null;

// ===================== DAFTAR KONTAK =====================
if ($role == 'guru') {
    // Guru: tampilkan semua siswa yang pernah berinteraksi (kirim atau terima pesan)
    $query_contact = "SELECT DISTINCT u.id, u.fullname, u.role 
                      FROM users u 
                      INNER JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
                      WHERE (m.receiver_id = '$uid' OR m.sender_id = '$uid') 
                      AND u.id != '$uid' 
                      ORDER BY u.fullname ASC";
} else {
    // Siswa: hanya melihat guru BK (role = 'guru')
    $query_contact = "SELECT id, fullname, role FROM users WHERE role = 'guru' ORDER BY fullname ASC";
}

$res_contact = mysqli_query($conn, $query_contact);
if (!$res_contact) {
    die("Query error: " . mysqli_error($conn));
}

// Update status pesan menjadi terbaca jika chat dibuka
if ($selected_id) {
    mysqli_query($conn, "UPDATE messages SET is_read = 1 
                         WHERE sender_id = '$selected_id' AND receiver_id = '$uid'");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Konseling - Zero Bullying</title>
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

        /* Navbar dengan efek glass */
        .navbar-glass {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1.5rem;
        }

        /* Kontak List */
        .contact-list {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 28px;
            height: 70vh;
            overflow-y: auto;
            padding: 10px;
        }

        .contact-item {
            display: block;
            padding: 15px 20px;
            border-radius: 20px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 5px;
            border: 1px solid transparent;
        }

        .contact-item:hover {
            background: rgba(59, 130, 246, 0.15);
            color: white;
            border-color: rgba(59, 130, 246, 0.3);
        }

        .contact-item.active {
            background: var(--accent-blue);
            color: white;
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.5);
        }

        /* Badge notifikasi dengan angka */
        .badge-new {
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 50px;
            min-width: 24px;
            text-align: center;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(239, 68, 68, 0.5);
        }

        /* Chat Card */
        .chat-card {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 28px;
            overflow: hidden;
            height: 70vh;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            background: rgba(0, 0, 0, 0.2);
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* Bubble Chat */
        .msg {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 24px;
            font-size: 0.95rem;
            position: relative;
            word-wrap: break-word;
        }

        .msg-me {
            align-self: flex-end;
            background: linear-gradient(105deg, var(--accent-blue), var(--accent-blue-hover));
            color: white;
            border-bottom-right-radius: 4px;
        }

        .msg-them {
            align-self: flex-start;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            border-bottom-left-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .msg-time {
            font-size: 0.65rem;
            opacity: 0.7;
            margin-top: 5px;
            text-align: right;
        }

        /* Input Area */
        .chat-input {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
        }

        .form-control-dark {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: var(--text-primary);
            border-radius: 40px;
            padding: 12px 20px;
        }

        .form-control-dark:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            color: white;
            outline: none;
        }

        .btn-send {
            background: linear-gradient(105deg, var(--accent-blue), var(--accent-blue-hover));
            border: none;
            border-radius: 40px;
            padding: 12px 25px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }

        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.6);
        }

        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
            text-align: center;
            padding: 20px;
        }

        .online-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            margin-right: 5px;
            box-shadow: 0 0 10px #10b981;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--border-dark);
            border-radius: 10px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-glass py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-white d-flex align-items-center" href="<?= $back_link ?>">
            <i class="bi bi-arrow-left-circle-fill me-2" style="color: var(--accent-blue); font-size: 1.8rem;"></i>
            <span class="text-white">Kembali ke <?= ($role == 'guru') ? 'Dashboard Guru' : 'Home Feed' ?></span>
        </a>
    </div>
</nav>

<div class="container position-relative" style="z-index: 10;">
    <div class="row g-4">
        <!-- Kolom Kontak (Kiri) -->
        <div class="col-lg-4">
            <h5 class="mb-3">
                <i class="bi bi-chat-square-text me-2" style="color: var(--accent-blue);"></i>
                <?= ($role == 'guru') ? 'Pesan dari Siswa' : 'Petugas BK Online' ?>
            </h5>
            <div class="contact-list">
                <?php if (mysqli_num_rows($res_contact) > 0): ?>
                    <?php while($c = mysqli_fetch_assoc($res_contact)): 
                        $is_active = ($selected_id == $c['id']) ? 'active' : '';

                        // Hitung jumlah pesan belum dibaca dari kontak ini
                        $unread_count = 0;
                        $query_unread = "SELECT COUNT(*) as total FROM messages WHERE sender_id = '{$c['id']}' AND receiver_id = '$uid' AND is_read = 0";
                        $res_unread = mysqli_query($conn, $query_unread);
                        if ($res_unread) {
                            $data_unread = mysqli_fetch_assoc($res_unread);
                            $unread_count = $data_unread['total'];
                        }
                    ?>
                        <a href="chat_bk.php?to=<?= $c['id'] ?>" class="contact-item <?= $is_active ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($c['fullname']) ?></div>
                                    <small class="<?= ($c['role'] == 'siswa') ? 'text-secondary' : 'text-info' ?>">
                                        <?= ucfirst($c['role']) ?>
                                    </small>
                                </div>
                                <?php if($selected_id != $c['id'] && $unread_count > 0): ?>
                                    <span class="badge-new"><?= $unread_count ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox fs-1 mb-3"></i>
                        <p>Belum ada percakapan.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom Chat (Kanan) -->
        <div class="col-lg-8">
            <?php if($selected_id): 
                // Ambil informasi target
                $target_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname, role FROM users WHERE id = '$selected_id'"));
                if (!$target_info) {
                    echo "<div class='chat-card d-flex align-items-center justify-content-center'><div class='empty-state'>Kontak tidak ditemukan.</div></div>";
                } else {
            ?>
                <div class="chat-card">
                    <div class="chat-header">
                        <div class="bg-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: linear-gradient(135deg, var(--accent-blue), var(--accent-blue-hover))!important;">
                            <i class="bi bi-person-fill text-white fs-5"></i>
                        </div>
                        <div>
                            <h6 class="m-0 fw-bold"><?= htmlspecialchars($target_info['fullname']) ?></h6>
                            <small class="text-success">
                                <span class="online-dot"></span> Online
                            </small>
                        </div>
                    </div>

                    <div class="chat-messages" id="chatBox">
                        <?php
                        $query_msg = "SELECT * FROM messages WHERE 
                                      (sender_id = '$uid' AND receiver_id = '$selected_id') OR 
                                      (sender_id = '$selected_id' AND receiver_id = '$uid') 
                                      ORDER BY created_at ASC";
                        $res_msg = mysqli_query($conn, $query_msg);
                        if (mysqli_num_rows($res_msg) > 0) {
                            while($m = mysqli_fetch_assoc($res_msg)):
                                $class = ($m['sender_id'] == $uid) ? 'msg-me' : 'msg-them';
                            ?>
                                <div class="msg <?= $class ?>">
                                    <?= htmlspecialchars($m['message']) ?>
                                    <div class="msg-time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
                                </div>
                            <?php endwhile; 
                        } else {
                            echo "<div class='empty-state'>Belum ada pesan. Mulai percakapan!</div>";
                        }
                        ?>
                    </div>

                    <div class="chat-input">
                        <form action="send_message.php" method="POST">
                            <input type="hidden" name="receiver_id" value="<?= $selected_id ?>">
                            <div class="input-group">
                                <input type="text" name="message" class="form-control form-control-dark" placeholder="Ketik pesan anda..." required autocomplete="off">
                                <button type="submit" class="btn-send ms-2">
                                    <i class="bi bi-send-fill me-1"></i> Kirim
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php 
                } // end if target_info
            else: 
            ?>
                <div class="chat-card d-flex align-items-center justify-content-center">
                    <div class="empty-state">
                        <i class="bi bi-chat-quote display-1 mb-3" style="color: var(--accent-blue);"></i>
                        <h5>Pilih kontak untuk memulai percakapan</h5>
                        <p class="text-secondary">Kerahasiaan percakapan antara Siswa dan Guru BK dilindungi.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Auto scroll ke bawah
    var chatBox = document.getElementById("chatBox");
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    
</script>

</body>
</html>