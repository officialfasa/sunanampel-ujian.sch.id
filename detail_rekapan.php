<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header("Location: index.php");
    exit();
}

$exam_id = (int)$_GET['id'];
$exam = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM exams WHERE id = '$exam_id'"));

// Hitung Statistik Tertinggi, Terendah, dan Rata-rata
$stat_query = mysqli_query($koneksi, "SELECT 
    MAX(total_score) as tertinggi, 
    MIN(total_score) as terendah, 
    AVG(total_score) as rata_rata 
    FROM exam_sessions WHERE exam_id = '$exam_id' AND status = 'completed'");
$stat = mysqli_fetch_assoc($stat_query);

// Cari Nama Siswa Nilai Tertinggi & Terendah
$tertinggi_siswa = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT u.full_name FROM exam_sessions es JOIN users u ON es.student_id = u.id WHERE es.exam_id = '$exam_id' AND es.total_score = '{$stat['tertinggi']}' LIMIT 1"))['full_name'] ?? '-';
$terendah_siswa  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT u.full_name FROM exam_sessions es JOIN users u ON es.student_id = u.id WHERE es.exam_id = '$exam_id' AND es.total_score = '{$stat['terendah']}' LIMIT 1"))['full_name'] ?? '-';

// Ambil Seluruh Siswa (Ikut maupun Tidak Ikut) Urut dari Nilai Tertinggi
$query_peserta = "SELECT u.full_name, u.school_level, u.grade_level, 
                  IFNULL(es.total_score, 0) as score, 
                  IFNULL(es.status, 'belum_mengerjakan') as status_ujian
                  FROM users u
                  LEFT JOIN exam_sessions es ON es.student_id = u.id AND es.exam_id = '$exam_id'
                  WHERE u.role = 'student' AND (u.school_level = '{$exam['school_level']}' OR '{$exam['school_level']}' = 'ALL')
                  ORDER BY score DESC, u.full_name ASC";
$result_peserta = mysqli_query($koneksi, $query_peserta);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai - <?= $exam['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light p-4">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Hasil Rekapan Nilai: <?= $exam['title']; ?></h3>
        <a href="admin_dashboard.php" class="btn btn-secondary rounded-pill">Kembali</a>
    </div>

    <!-- Ringkasan Statistik Nilai -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white p-3 border-0 shadow-sm rounded-4">
                <h6>NILAI TERTINGGI</h6>
                <h2 class="fw-bold m-0"><?= round($stat['tertinggi'], 1); ?></h2>
                <small class="text-white-50">Oleh: <?= $tertinggi_siswa; ?></small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white p-3 border-0 shadow-sm rounded-4">
                <h6>NILAI TERENDAH</h6>
                <h2 class="fw-bold m-0"><?= round($stat['terendah'], 1); ?></h2>
                <small class="text-white-50">Oleh: <?= $terendah_siswa; ?></small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white p-3 border-0 shadow-sm rounded-4">
                <h6>RATA-RATA UJIAN</h6>
                <h2 class="fw-bold m-0"><?= round($stat['rata_rata'], 1); ?></h2>
                <small class="text-white-50">Seluruh Peserta Selesai</small>
            </div>
        </div>
    </div>

    <!-- Tabel Peringkat Nilai Siswa -->
    <div class="card border-0 shadow-sm rounded-4 p-3">
        <table class="table table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>Peringkat</th>
                    <th>Nama Siswa</th>
                    <th>Jenjang / Kelas</th>
                    <th>Status Mengerjakan</th>
                    <th>Nilai Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; while ($p = mysqli_fetch_assoc($result_peserta)): ?>
                    <tr>
                        <td class="fw-bold">#<?= $rank++; ?></td>
                        <td class="fw-bold"><?= $p['full_name']; ?></td>
                        <td><?= $p['school_level']; ?> - Kelas <?= $p['grade_level']; ?></td>
                        <td>
                            <?php if ($p['status_ujian'] == 'completed'): ?>
                                <span class="badge bg-success">Sudah Ikut</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Tidak / Belum Mengerjakan</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-primary fs-6"><?= round($p['score'], 1); ?></span></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>