<?php
include 'config.php';
$pid = $_GET['id'];
$type = $_GET['type'];
$uid = $_SESSION['user_id'];

// Hapus reaksi lama jika ada, lalu masukkan yang baru
mysqli_query($conn, "DELETE FROM interactions WHERE post_id='$pid' AND user_id='$uid'");
mysqli_query($conn, "INSERT INTO interactions (post_id, user_id, type) VALUES ('$pid', '$uid', '$type')");

header("Location: index.php");
?>