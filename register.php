<?php
include 'config.php';

// Inisialisasi variabel agar form tetap terisi setelah reload jika ada error
$fullname = $username = $email = $kelas = $jurusan = $no_telp = $password = "";
$gender = "L"; // Default gender
$error_telp = $error_email = "";

if (isset($_POST['register'])) {
    // Tangkap data dan amankan dari SQL Injection
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); 
    $kelas    = mysqli_real_escape_string($conn, $_POST['kelas']);
    $jurusan  = mysqli_real_escape_string($conn, $_POST['jurusan']);
    $gender   = mysqli_real_escape_string($conn, $_POST['gender']);
    $no_telp  = mysqli_real_escape_string($conn, $_POST['no_telp']);

    $stop_process = false;

    // --- 1. VALIDASI FORMAT & DUPLIKASI EMAIL ---
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_email = "Format email tidak valid!";
        $stop_process = true;
    } else {
        $check_email = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $error_email = "Email ini sudah terdaftar!";
            $stop_process = true;
        }
    }

    // --- 2. VALIDASI FORMAT NOMOR TELEPON ---
    if (!preg_match('/^08[0-9]{8,11}$/', $no_telp)) {
        $error_telp = "Format salah! Harus mulai 08 dan 10-13 digit.";
        $stop_process = true;
    }

    // --- 3. CEK DUPLIKASI NOMOR TELEPON ---
    $check_telp = mysqli_query($conn, "SELECT no_telp FROM users WHERE no_telp = '$no_telp'");
    if (mysqli_num_rows($check_telp) > 0) {
        $error_telp = "Nomor telepon ini sudah terdaftar!";
        $stop_process = true;
    }

    // --- 4. CEK DUPLIKASI USERNAME ---
    $check_user = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check_user) > 0) {
        echo "<script>alert('Username sudah digunakan! Silahkan pilih yang lain.');</script>";
        $stop_process = true;
    }

    // --- 5. PROSES SIMPAN KE DATABASE ---
    if (!$stop_process) {
        // Hash password sebelum disimpan
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query = "INSERT INTO users (fullname, username, email, password, role, kelas, jurusan, gender, no_telp) 
                  VALUES ('$fullname', '$username', '$email', '" . mysqli_real_escape_string($conn, $hashed_password) . "', 'user', '$kelas', '$jurusan', '$gender', '$no_telp')";
        
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Berhasil Daftar! Silahkan Login'); window.location='login.php';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Zero Bullying</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --bg-dark: #0f1115; --card-dark: #111827; --border-dark: #1f2937; --accent-blue: #3b82f6; --accent-blue-hover: #2563eb; --text-primary: #e5e7eb; --text-secondary: #9ca3af; }
        body { background-color: var(--bg-dark); color: var(--text-primary); min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; margin: 0; padding: 40px 0; }
        .brand-header { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
        .brand-logo { background-color: var(--accent-blue); color: white; width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 0 20px rgba(59, 130, 246, 0.4); }
        .logo-text { font-size: 28px; font-weight: 700; color: var(--text-primary); }
        .register-box { background: var(--card-dark); padding: 40px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); width: 100%; max-width: 550px; border: 1px solid var(--border-dark); }
        .reg-title { font-weight: 700; font-size: 26px; margin-bottom: 8px; }
        .reg-subtitle { color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 30px; line-height: 1.5; }
        .form-label { color: var(--text-primary); font-size: 0.85rem; font-weight: 500; margin-bottom: 8px; }
        .input-group-text { background-color: var(--border-dark); border: 1px solid #374151; color: var(--text-secondary); border-right: none; }
        .form-control, .form-select { background-color: var(--border-dark) !important; border: 1px solid #374151; color: var(--text-primary) !important; padding: 10px 12px; border-left: none; font-size: 0.9rem; }
        .form-select { border-left: 1px solid #374151; }
        .form-control:focus { border-color: var(--accent-blue); box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
        .btn-primary { background-color: var(--accent-blue); border: none; padding: 12px; font-weight: 600; border-radius: 10px; transition: background-color 0.2s; margin-top: 15px; }
        .btn-primary:hover { background-color: var(--accent-blue-hover); }
        .footer-link { text-align: center; margin-top: 25px; color: var(--text-secondary); font-size: 0.9rem; }
        .footer-link a { color: var(--accent-blue); text-decoration: none; font-weight: 600; }
        .text-danger-custom { color: #f87171; font-size: 0.75rem; margin-top: 5px; display: block; }
        .encryption-tag { text-align: center; font-size: 10px; color: #4b5563; margin-top: 25px; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

    <div class="brand-header">
        <div class="brand-logo"><i class="bi bi-shield-fill-check"></i></div>
        <span class="logo-text">Zero Bullying</span>
    </div>

    <div class="register-box">
        <h3 class="reg-title">Student Registration</h3>
        <p class="reg-subtitle">Join our community. Your real identity is required for security.</p>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="fullname" class="form-control" placeholder="input name" value="<?php echo htmlspecialchars($fullname); ?>" required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="input username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <?php if ($error_email): ?>
                    <small class="text-danger-custom"><i class="bi bi-exclamation-circle me-1"></i> <?php echo $error_email; ?></small>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="input password" required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="no_telp" class="form-control" placeholder="08xxxxxxxx" 
                               value="<?php echo htmlspecialchars($no_telp); ?>" required 
                               oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="13">
                    </div>
                    <?php if ($error_telp): ?>
                        <small class="text-danger-custom"><i class="bi bi-exclamation-circle me-1"></i> <?php echo $error_telp; ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Class</label>
                    <input type="text" name="kelas" class="form-control" style="border-left: 1px solid #374151;" placeholder="input class" value="<?php echo htmlspecialchars($kelas); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Jurusan</label>
                    <input type="text" name="jurusan" class="form-control" style="border-left: 1px solid #374151;" placeholder="input jurusan" value="<?php echo htmlspecialchars($jurusan); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="L" <?php echo ($gender == 'L') ? 'selected' : ''; ?>>Laki-laki (Male)</option>
                    <option value="P" <?php echo ($gender == 'P') ? 'selected' : ''; ?>>Perempuan (Female)</option>
                </select>
            </div>

            <button type="submit" name="register" class="btn btn-primary w-100">Create Account <i class="bi bi-arrow-right ms-2"></i></button>
            
            <div class="footer-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>

            <div class="encryption-tag">
                <i class="bi bi-shield-lock-fill"></i> End-to-end encrypted � Counselor verified
            </div>
        </form>
    </div>

</body>
</html>