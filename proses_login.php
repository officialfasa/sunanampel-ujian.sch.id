<?php
session_start();
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = trim($_POST['password']);

    // 1. Cari Pengguna Berdasarkan Username atau NISN
    $query  = "SELECT * FROM users WHERE username = '$username' OR nisn_or_nip = '$username'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // 2. Verifikasi Password (Mendukung Hash Maupun Plain Password)
        $password_correct = false;
        if (password_verify($password, $user['password'])) {
            $password_correct = true;
        } elseif ($password === $user['password']) {
            $password_correct = true;
        }

        if ($password_correct) {
            
            // 3. Cek Status Verifikasi Khusus Siswa
            if ($user['role'] == 'student') {
                if ($user['is_approved'] == 0) {
                    // Belum diverifikasi / masih proses
                    header("Location: index.php?pesan=pending");
                    exit();
                } else if ($user['is_approved'] == 2) {
                    // Pendaftaran Ditolak
                    header("Location: index.php?pesan=rejected");
                    exit();
                }
            }

            // 4. Jika Disetujui (is_approved = 1) -> Set Session
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['full_name']    = $user['full_name'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['school_level'] = $user['school_level'];
            $_SESSION['grade_level']  = $user['grade_level'];
            $_SESSION['first_login']   = true;

            // Pengalihan Berdasarkan Role
            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else if ($user['role'] == 'teacher') {
                header("Location: guru_dashboard.php");
            } else {
                header("Location: siswa_dashboard.php");
            }
            exit();

        } else {
            // Password Salah
            header("Location: index.php?pesan=gagal");
            exit();
        }

    } else {
        // Username / NISN Tidak Ditemukan
        header("Location: index.php?pesan=gagal");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}