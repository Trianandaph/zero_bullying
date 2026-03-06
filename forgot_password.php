<?php
// Tampilkan error untuk debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php'; // Sesi biasanya sudah dimulai di sini

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Pastikan folder PHPMailer ada di lokasi yang sama dengan file ini
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

$error = "";
$success = "";

// Logika kirim email
if (isset($_POST['verifikasi_email'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Cek email di database
    $query = mysqli_query($conn, "SELECT id, fullname FROM users WHERE email = '$email'");
    
    if ($query && mysqli_num_rows($query) > 0) {
        $user_data = mysqli_fetch_assoc($query);
        $otp = rand(100000, 999999);
        
        $_SESSION['otp_reset'] = $otp;
        $_SESSION['user_id_reset'] = $user_data['id'];
        $_SESSION['email_reset'] = $email;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'zerobullying6@gmail.com'; // Email Gmail Anda
            $mail->Password   = 'skvwqwjrzaswfwdz';         // 16 DIGIT APP PASSWORD
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('zerobullying6@gmail.com', 'Admin Zero Bullying');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'OTP Reset Password';
            $mail->Body    = "Halo {$user_data['fullname']}, kode OTP Anda: <b>$otp</b>";

            $mail->send();
            $success = "OTP berhasil dikirim ke email!";
            header("refresh:2;url=verify_otp.php");
        } catch (Exception $e) {
            $error = "Gagal kirim email: {$mail->ErrorInfo}";
        }
    } else {
        $error = "Email tidak ditemukan! Pastikan kolom email di database sudah terisi.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Identitas - Zero Bullying</title>
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

        .auth-box {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 32px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
        }

        h4 {
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

        .btn-primary {
            background: var(--accent-blue);
            border: none;
        }

        .btn-primary:hover {
            background: var(--accent-blue-hover);
        }

        .back-link {
            color: var(--text-secondary);
            text-decoration: none;
        }

        .back-link:hover {
            color: var(--text-primary);
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
        }
    </style>
</head>
<body>

<div class="auth-box">
    <h4>Verifikasi Identitas</h4>
    <p class="subtitle">Masukkan email Anda untuk menerima kode OTP reset password.</p>

    <?php if($error): ?>
        <div class="alert-custom"><?= $error ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert-success-custom"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-4">
            <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
        </div>
        <button type="submit" name="verifikasi_email" class="btn btn-primary w-100">
            Kirim Kode OTP <i class="bi bi-arrow-right ms-2"></i>
        </button>
        <a href="login.php" class="back-link d-block mt-3 text-center">Kembali ke Login</a>
    </form>
</div>

<script>
</script>

</body>
</html>