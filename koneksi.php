<?php
$host     = "127.0.0.1";
$user     = "root";
$password = ""; // Default Laragon password kosong
$database = "db_ujian_sekolah";

$koneksi = mysqli_connect($host, $user, $password, $database);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
} 

// Hilangkan tanda komentar (//) pada baris di bawah jika ingin menguji koneksi di browser
// echo "Berhasil terhubung ke database db_ujian_sekolah!";
?>