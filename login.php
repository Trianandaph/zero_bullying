<?php
include 'config.php';

// Proteksi: Jika sudah login, dilarang akses halaman login lagi
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin_dashboard.php");
    } elseif ($_SESSION['role'] == 'guru') {
        header("Location: guru_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = "";

if (isset($_POST['login'])) {
    // Input ini bisa berupa Username atau Email
    $identifier = mysqli_real_escape_string($conn, $_POST['identifier']);
    $password = $_POST['password']; 

    // Query: ambil user berdasarkan username atau email
    $query = "SELECT * FROM users WHERE (username = '$identifier' OR email = '$identifier')";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Verifikasi password dengan password_verify()
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];

            // Redirect berdasarkan role
            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] == 'guru') {
                header("Location: guru_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Username/Email atau Password salah!";
        }
    } else {
        $error = "Username/Email atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Back - Zero Bullying</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Reset & Global */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--bg-dark);
            color: var(--text-primary);
            position: relative;
            overflow-x: hidden;
        }

        /* Efek glassmorphism untuk kardus login */
        .login-box {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 32px;
            padding: 40px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
            z-index: 10;
        }

        /* Header: Logo di atas, teks di bawahnya */
        .brand-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            z-index: 10;
        }

        .brand-icon {
            background: linear-gradient(135deg, #3b82f6, #1e4b8f);
            color: white;
            width: 70px;
            height: 70px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.5);
            margin-bottom: 15px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s;
        }

        .brand-icon:hover {
            filter: brightness(1.05);
        }

        .brand-name {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #b0d4ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
            text-shadow: 0 2px 10px rgba(0,100,255,0.3);
        }

        .brand-tagline {
            font-size: 14px;
            color: #b0c7e0;
            background: rgba(255,255,255,0.1);
            padding: 5px 15px;
            border-radius: 30px;
            backdrop-filter: blur(5px);
            font-weight: 400;
        }

        /* Judul dalam form */
        .form-title {
            color: white;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 8px;
            text-align: center;
        }

        .form-subtitle {
            color: #b0c7e0;
            text-align: center;
            font-size: 15px;
            margin-bottom: 30px;
        }

        /* Label form */
        .form-label {
            color: #e0edff;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: block;
        }

        /* Input group */
        .input-group {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.3s;
        }

        .input-group:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.4);
            background: rgba(255, 255, 255, 0.1);
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: #9cb9e0;
            font-size: 1.2rem;
            padding-left: 18px;
        }

        .form-control {
            background: transparent !important;
            border: none;
            color: #e2e8f0 !important; /* Warna teks yang diisi: abu terang, tidak putih murni */
            padding: 14px 18px 14px 0;
            font-size: 1rem;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
            font-weight: 300;
        }

        .form-control:focus {
            outline: none;
            box-shadow: none;
            background: transparent;
        }

        /* Link lupa password */
        .forgot-password {
            float: right;
            color: #7aa9ff;
            text-decoration: none;
            font-size: 0.85rem;
            margin-top: 6px;
            transition: color 0.2s;
            cursor: pointer;
        }

        .forgot-password:hover {
            color: #b0d0ff;
            text-decoration: underline;
        }

        /* Tombol login */
        .btn-login {
            background: linear-gradient(105deg, #3b82f6, #2563eb);
            border: none;
            color: white;
            font-weight: 600;
            padding: 14px 20px;
            border-radius: 40px;
            width: 100%;
            font-size: 1.1rem;
            margin-top: 15px;
            transition: all 0.3s;
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            background: linear-gradient(105deg, #2563eb, #1d4ed8);
            box-shadow: 0 20px 30px -5px rgba(37, 99, 235, 0.7);
        }

        .btn-login i {
            transition: transform 0.3s;
        }

        .btn-login:hover i {
            transform: none;
        }

        /* Footer link */
        .footer-text {
            text-align: center;
            margin-top: 30px;
            color: #b0c7e0;
        }

        .footer-text a {
            color: #7aa9ff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .footer-text a:hover {
            color: #b0d0ff;
            text-decoration: underline;
        }

        /* Enkripsi tag */
        .encryption-tag {
            text-align: center;
            font-size: 11px;
            color: #5f7ea0;
            margin-top: 25px;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }

        /* Alert error */
        .alert-custom {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #fca5a5;
            border-radius: 30px;
            padding: 12px 16px;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
        }

        /* Pastikan semua teks terbaca */
        h1, h2, h3, p, label, a, span {
            color: inherit;
        }

        /* Menangani autofill browser */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #e2e8f0 !important;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #1f2a3f;
        }
    </style>
</head>
<body>

<div class="brand-container">
    <div class="brand-icon">
        <i class="bi bi-shield-fill-check"></i>
    </div>
    <div class="brand-name">Zero Bullying</div>
    <div class="brand-tagline">safe space for every student</div>
</div>

<div class="login-box">
    <h2 class="form-title">Welcome Back</h2>
    <p class="form-subtitle">Providing a safe space for every student.</p>

    <?php if ($error != ""): ?>
        <div class="alert-custom text-center mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-4">
            <label class="form-label">Username or Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" id="login_input" name="identifier" class="form-control shadow-none" placeholder="Enter username or email" required>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control shadow-none" placeholder="Enter your password" required>
            </div>
            <a onclick="handleForgotPassword()" class="forgot-password">Forgot password?</a>
        </div>
        
        <button type="submit" name="login" class="btn-login">
            Sign In <i class="bi bi-arrow-right"></i>
        </button>
    </form>
    
    <div class="footer-text">
        New student? <a href="register.php">Create an account</a>
    </div>

   </div>

<script>
// Fungsi lupa password
function handleForgotPassword() {
    const loginField = document.getElementById('login_input');
    const loginValue = loginField.value.trim();

    if (loginValue === "") {
        alert("Harap masukkan Username atau Email Anda terlebih dahulu untuk verifikasi akun.");
        loginField.focus();
    } else {
        window.location.href = "forgot_password.php?user=" + encodeURIComponent(loginValue);
    }
}

</script>

<!-- Font Inter -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</body>
</html>