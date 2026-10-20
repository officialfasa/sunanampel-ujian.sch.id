<?php
session_start();
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name    = mysqli_real_escape_string($koneksi, $_POST['full_name']);
    $nisn         = mysqli_real_escape_string($koneksi, $_POST['nisn']);
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password     = mysqli_real_escape_string($koneksi, $_POST['password']);
    $school_level = mysqli_real_escape_string($koneksi, $_POST['school_level']);
    $grade_level  = mysqli_real_escape_string($koneksi, $_POST['grade_level']);

    // Cek Duplikasi Username atau NISN
    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' OR nisn='$nisn'");
    if (mysqli_num_rows($cek) > 0) {
        header("Location: daftar.php?pesan=duplicate");
        exit();
    } else {
        // Simpan data dengan status default 'pending' dan role 'student'
        $query = "INSERT INTO users (full_name, nisn, username, password, school_level, grade_level, role, status) 
                  VALUES ('$full_name', '$nisn', '$username', '$password', '$school_level', '$grade_level', 'student', 'pending')";
        
        if (mysqli_query($koneksi, $query)) {
            // DIARAHKAN KEMBALI KE DAFTAR.PHP
            header("Location: daftar.php?pesan=registered");
            exit();
        } else {
            echo "Gagal mendaftar: " . mysqli_error($koneksi);
        }
    }
}
?>