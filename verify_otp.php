<?php
// Periksa status sesi agar tidak bentrok dengan config.php
if (session_status() === PHP_SESSION_NONE) {
}

include 'config.php';

// Proteksi: Jika tidak ada data sesi reset, kembalikan ke halaman forgot_password
if (!isset($_SESSION['otp_reset']) || !isset($_SESSION['email_reset'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = "";

if (isset($_POST['verifikasi_otp'])) {
    $input_otp = mysqli_real_escape_string($conn, $_POST['otp']);
    
    // Validasi: Cek apakah OTP yang diinput sama dengan yang ada di Session
    if ($input_otp == $_SESSION['otp_reset']) {
        // Jika benar, tandai bahwa user telah melewati tahap verifikasi
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Kode OTP salah. Silakan periksa kembali email Anda.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - Zero Bullying</title>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(145deg, #0a1a2f 0%, #1a3b5c 100%);
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Partikel background */
        .particle {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.6), rgba(200, 230, 255, 0.2));
            filter: blur(2px);
            z-index: 0;
            pointer-events: none;
            animation: float 20s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); }
            25% { transform: translateY(-40px) translateX(20px); }
            50% { transform: translateY(-20px) translateX(-30px); }
            75% { transform: translateY(30px) translateX(15px); }
        }

        .auth-box {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            z-index: 10;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .icon-box {
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
            color: white;
        }

        .subtitle {
            color: #b0c7e0;
            font-size: 15px;
            margin-bottom: 30px;
        }

        .email-highlight {
            background: rgba(255, 255, 255, 0.05);
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
            color: white;
            display: inline-block;
            margin-bottom: 20px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            padding: 14px 18px;
            color: #e2e8f0;
            transition: all 0.3s;
            text-align: center;
            font-size: 28px;
            letter-spacing: 10px;
            font-weight: 600;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.4);
            color: white;
            outline: none;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
            font-weight: 300;
            font-size: 18px;
            letter-spacing: normal;
        }

        .btn-primary-custom {
            background: linear-gradient(105deg, #3b82f6, #2563eb);
            border: none;
            border-radius: 16px;
            padding: 14px;
            font-weight: 600;
            color: white;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s;
            cursor: pointer;
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.5);
        }

        .btn-primary-custom:hover {
            box-shadow: 0 15px 30px -5px rgba(59, 130, 246, 0.7);
            transform: translateY(-2px);
        }

        .resend-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        .resend-link:hover {
            color: #60a5fa;
            text-decoration: underline;
        }

        .alert-custom {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #fca5a5;
            border-radius: 30px;
            padding: 12px 16px;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
            margin-bottom: 20px;
        }

        .text-secondary-custom {
            color: #b0c7e0;
        }
    </style>
</head>
<body>

<div class="auth-box">
    <div class="icon-box"><i class="bi bi-shield-check"></i></div>
    <h3>Masukkan OTP</h3>
    <p class="subtitle">Kode verifikasi telah dikirim ke:</p>
    <p class="email-highlight"><?php echo htmlspecialchars($_SESSION['email_reset']); ?></p>

    <?php if($error): ?>
        <div class="alert-custom"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-4">
            <input type="text" name="otp" class="form-control" placeholder="000000" maxlength="6" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
        </div>
        <button type="submit" name="verifikasi_otp" class="btn-primary-custom">
            Verifikasi Kode <i class="bi bi-arrow-right ms-2"></i>
        </button>
        
        <div class="mt-4">
            <p class="text-secondary-custom small">Tidak menerima kode? <a href="forgot_password.php" class="resend-link">Kirim Ulang</a></p>
        </div>
    </form>
</div>

<script>
</script>

</body>
</html>