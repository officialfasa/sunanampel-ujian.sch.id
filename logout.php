<?php
session_start();

// Catat log aktivitas LOGOUT jika user_id tersedia
require_once 'koneksi.php';
if (isset($_SESSION['user_id'])) {
    $user_id    = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = mysqli_real_escape_string($koneksi, $_SERVER['HTTP_USER_AGENT']);
    $action     = "LOGOUT";
    $desc       = "Pengguna berhasil keluar dari sistem";

    $log_query = "INSERT INTO activity_logs (user_id, activity_type, description, ip_address, user_agent) 
                  VALUES ('$user_id', '$action', '$desc', '$ip_address', '$user_agent')";
    mysqli_query($koneksi, $log_query);
}

// Hapus semua data sesi
session_unset();
session_destroy();

// Alihkan kembali ke halaman login utama
header("Location: index.php");
exit();
?>