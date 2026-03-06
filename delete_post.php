<?php
include 'config.php';
checkLogin('admin');
$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM posts WHERE id='$id'");
mysqli_query($conn, "DELETE FROM interactions WHERE post_id='$id'");
mysqli_query($conn, "DELETE FROM comments WHERE post_id='$id'");
header("Location: admin_dashboard.php");
?>