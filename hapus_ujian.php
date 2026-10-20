<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $exam_id = (int)$_GET['id'];
    mysqli_query($koneksi, "DELETE FROM exams WHERE id = '$exam_id'");
}

header("Location: admin_dashboard.php");
exit();
?>