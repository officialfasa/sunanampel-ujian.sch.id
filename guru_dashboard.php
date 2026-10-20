<?php
session_start();
require_once 'koneksi.php';

// Cek Akses khusus Guru atau Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php");
    exit();
}

$teacher_id   = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$subject      = $_SESSION['subject'] ?? '';
$school_level = $_SESSION['school_level'] ?? 'ALL';
$grade_level  = $_SESSION['grade_level'] ?? 0;

// Ambil info lengkap guru dari database
$teacher_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$teacher_id'"));
if ($teacher_data) {
    $teacher_name = $teacher_data['full_name'] ?: $teacher_data['username'];
    $subject      = $teacher_data['subject'] ?: 'Semua Mapel';
    $school_level = $teacher_data['school_level'] ?: 'ALL';
    $grade_level  = $teacher_data['grade_level'] ?: 0;
}

// 1. Ambil Paket Ujian yang Diampu oleh Guru Ini (Gunakan alias e. untuk menghindari ambiguous column)
$where_exam = "WHERE 1=1";
if ($school_level != 'ALL') {
    $where_exam .= " AND (e.school_level = '$school_level' OR e.school_level = 'ALL')";
}
if ($grade_level > 0) {
    $where_exam .= " AND (e.grade_id = '$grade_level' OR e.grade_id = 0)";
}

$query_exams = mysqli_query($koneksi, "SELECT e.*, g.grade_name,
                                       (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) as total_soal,
                                       (SELECT COUNT(*) FROM exam_sessions es WHERE es.exam_id = e.id AND es.status = 'completed') as total_selesai
                                       FROM exams e
                                       LEFT JOIN grades g ON e.grade_id = g.id
                                       $where_exam
                                       ORDER BY e.id DESC");

// 2. Ambil Daftar Siswa Berdasarkan Jenjang & Kelas Guru (Gunakan alias u. untuk tabel users)
$where_student = "WHERE u.role = 'student'";
if ($school_level != 'ALL') {
    $where_student .= " AND u.school_level = '$school_level'";
}
if ($grade_level > 0) {
    $where_student .= " AND u.grade_level = '$grade_level'";
}

$query_students = mysqli_query($koneksi, "SELECT u.*, g.grade_name 
                                          FROM users u 
                                          LEFT JOIN grades g ON u.grade_level = g.id 
                                          $where_student 
                                          ORDER BY u.id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - Portal Ujian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <!-- Navbar / Header Dashboard -->
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">
        <div>
            <h4 class="fw-bold m-0 text-primary"><i class="fa-solid fa-chalkboard-user me-2"></i>Dashboard Pengajar</h4>
            <span class="text-muted small">Selamat Datang, <strong><?= $teacher_name; ?></strong> | Mapel: <span class="badge bg-info text-dark"><?= $subject; ?></span> | Jenjang: <span class="badge bg-secondary"><?= $school_level; ?></span></span>
        </div>
        <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill fw-bold"><i class="fa-solid fa-right-from-bracket me-1"></i> Keluar</a>
    </div>

    <div class="row g-4">
        <!-- TABEL PAKET UJIAN PENGUJI -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0"><i class="fa-solid fa-folder-open text-primary me-2"></i>Daftar Ujian Mengajar</h5>
                    <a href="buat_ujian.php" class="btn btn-primary btn-sm rounded-pill fw-bold"><i class="fa-solid fa-plus me-1"></i> Buat Ujian</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Ujian</th>
                                <th>Kelas</th>
                                <th>Jumlah Soal</th>
                                <th>Peserta Selesai</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_exams && mysqli_num_rows($query_exams) > 0): ?>
                                <?php $no=1; while ($ex = mysqli_fetch_assoc($query_exams)): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><strong><?= $ex['title']; ?></strong></td>
                                        <td><span class="badge bg-primary"><?= $ex['grade_name'] ? 'Kelas ' . $ex['grade_name'] : 'Semua Kelas'; ?></span></td>
                                        <td><span class="badge bg-secondary"><?= $ex['total_soal']; ?> Soal</span></td>
                                        <td><span class="badge bg-success"><?= $ex['total_selesai']; ?> Siswa</span></td>
                                        <td class="text-center">
                                            <a href="kelola_soal.php?exam_id=<?= $ex['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1"><i class="fa-solid fa-list-check"></i> Soal</a>
                                            <a href="pantau_ujian.php?id=<?= $ex['id']; ?>" class="btn btn-sm btn-warning text-dark rounded-pill"><i class="fa-solid fa-desktop"></i> Pantau</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4"><i class="fa-solid fa-folder-xmark fa-2x mb-2 d-block"></i>Belum ada paket ujian yang dibuat.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TABEL SISWA KELAS BINAAN -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-users text-success me-2"></i>Siswa dalam Binaan</h5>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Siswa</th>
                                <th>Jenjang/Kelas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_students && mysqli_num_rows($query_students) > 0): ?>
                                <?php $no=1; while ($st = mysqli_fetch_assoc($query_students)): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><strong><?= $st['full_name'] ?: $st['username']; ?></strong></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $st['school_level']; ?></span>
                                            <span class="badge bg-info text-dark"><?= $st['grade_name'] ? 'Kelas ' . $st['grade_name'] : 'Kelas ' . $st['grade_level']; ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4"><i class="fa-solid fa-user-slash fa-2x mb-2 d-block"></i>Belum ada data siswa terdaftar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>