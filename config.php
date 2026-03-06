<?php
session_start();

// Gunakan kredensial yang sudah berhasil Anda coba
$host = "localhost";
$user = "webuser";
$pass = "12345";
$db   = "zero_bullying";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Fungsi proteksi halaman
function checkLogin($role) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $role) {
        header("Location: login.php");
        exit();
    }
}
?>