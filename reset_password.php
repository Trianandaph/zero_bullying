<?php
include 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Proteksi Vertifikasi OTP
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: verify_otp.php");
    exit();
}

$error = "";
$success = "";
$uid = $_SESSION['user_id_reset'];

if (isset($_POST['update_password'])) {
    $new_pw = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_pw = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    if (strlen($new_pw) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif ($new_pw !== $confirm_pw) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        // Hash password sebelum disimpan
        $hashed_password = password_hash($new_pw, PASSWORD_DEFAULT);
        $update = mysqli_query($conn, "UPDATE users SET password = '" . mysqli_real_escape_string($conn, $hashed_password) . "' WHERE id = '$uid'");

        if ($update) {
            $success = "Password berhasil diubah! Silakan login kembali.";
            // session reset
            unset($_SESSION['otp_reset'], $_SESSION['user_id_reset'], $_SESSION['email_reset'], $_SESSION['otp_verified']);
        } else {
            $error = "Gagal memperbarui: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setel Ulang Password - Zero Bullying</title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            min-height: 100vh;
            background: var(--bg-dark);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-reset {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 32px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
        }

        .icon-header {
            background: linear-gradient(135deg, #3b82f6, #1e4b8f);
            width: 70px;
            height: 70px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: white;
            box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.5);
        }

        h3 {
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 15px;
            margin-bottom: 30px;
        }

        .form-label {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: block;
            text-align: left;
        }

        .form-control {
            background: var(--border-dark);
            border: 1px solid #374151;
            border-radius: 16px;
            padding: 14px 18px;
            color: var(--text-primary);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            background: var(--border-dark);
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            color: var(--text-primary);
            outline: none;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
            font-weight: 300;
        }

        .btn-primary-custom {
            background: var(--accent-blue);
            border: none;
            border-radius: 40px;
            font-weight: 600;
            padding: 10px 20px;
            color: white;
            display: inline-block;
            text-decoration: none;
        }

        .btn-primary-custom:hover {
            background: var(--accent-blue-hover);
        }

        .alert-custom {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #fca5a5;
            border-radius: 30px;
            padding: 12px 16px;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .alert-success-custom {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #bbf7d0;
            border-radius: 30px;
            padding: 12px 16px;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .text-secondary-custom {
            color: var(--text-secondary);
        }

        .text-start {
            text-align: left;
        }
    </style>
</head>
<body>

<div class="card-reset text-center">
    <div class="icon-header">
        <i class="bi bi-key-fill"></i>
    </div>
    <h3 class="fw-bold mb-3">Password Baru</h3>
    <p class="subtitle mb-4">Silakan buat password baru.</p>
    
    <?php if($error): ?>
        <div class="alert-custom"><?= $error ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert-success-custom"><?= $success ?></div>
        <a href="login.php" class="btn-primary-custom mt-3 d-block">Masuk ke Akun</a>
    <?php else: ?>
        <form method="POST">
            <div class="mb-3 text-start">
                <label class="form-label">Password Baru</label>
                <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="mb-4 text-start">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
            </div>
            <button type="submit" name="update_password" class="btn-primary-custom">
                Simpan <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>