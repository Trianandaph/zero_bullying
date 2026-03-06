<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
if (!in_array($_SESSION['role'], ['user', 'guru'], true)) { header("Location: login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pid = (int)$_POST['post_id'];
    $uid = $_SESSION['user_id'];
    $comment = mysqli_real_escape_string($conn, trim($_POST['comment']));

    if ($pid > 0 && $comment !== '') {
        mysqli_query($conn, "INSERT INTO comments (post_id, user_id, comment) VALUES ('$pid', '$uid', '$comment')");
    }
}

// Kembali ke halaman sebelumnya berdasarkan role
if ($_SESSION['role'] == 'guru') header("Location: guru_dashboard.php");
else header("Location: index.php");
?>
