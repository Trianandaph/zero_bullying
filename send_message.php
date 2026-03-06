<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) die;
$sender = $_SESSION['user_id'];
$receiver = mysqli_real_escape_string($conn, $_POST['receiver_id']);
$message = mysqli_real_escape_string($conn, $_POST['message']);
mysqli_query($conn, "INSERT INTO messages (sender_id, receiver_id, message) VALUES ('$sender', '$receiver', '$message')");
header("Location: chat_bk.php?to=$receiver");