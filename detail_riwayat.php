<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header("Location: index.php");
    exit();
}

$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$exam    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM exams WHERE id = '$exam_id'"));

if (!$exam) {
    header("Location: admin_dashboard.php");
    exit();
}

// Hitung Total Soal dalam Ujian Ini
$total_soal_res = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM questions WHERE exam_id = '$exam_id'"));
$total_soal     = $total_soal_res['total'] > 0 ? $total_soal_res['total'] : 1;

// Ambil Seluruh Sesi Pengerjaan Siswa
$query_sessions = "SELECT es.*, u.full_name, u.school_level, g.grade_name
                   FROM exam_sessions es
                   JOIN users u ON es.student_id = u.id
                   LEFT JOIN grades g ON u.grade_level = g.id
                   WHERE es.exam_id = '$exam_id'
                   ORDER BY es.start_time DESC";
$result_sessions = mysqli_query($koneksi, $query_sessions);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Riwayat Ujian - <?= $exam['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold m-0"><i class="fa-solid fa-file-lines text-primary me-2"></i>Rincian Riwayat Hasil Ujian</h3>
            <p class="text-muted m-0">Event: <strong><?= $exam['title']; ?></strong> | Jenjang: <?= $exam['school_level']; ?> | Durasi: <?= $exam['duration_minutes']; ?> Menit</p>
        </div>
        <div>
            <a href="ekspor_pdf_rekapan.php?id=<?= $exam_id; ?>" target="_blank" class="btn btn-danger rounded-pill fw-bold me-2"><i class="fa-solid fa-file-pdf me-1"></i> Ekspor Laporan PDF</a>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill fw-bold"><i class="fa-solid fa-arrow-left me-1"></i> Kembali</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-users text-primary me-2"></i>Daftar Pengerjaan Peserta</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>Jenjang/Kelas</th>
                        <th>Waktu Mulai</th>
                        <th class="text-center">Benar</th>
                        <th class="text-center">Salah</th>
                        <th class="text-center">Ragu-ragu</th>
                        <th class="text-center">Kosong</th>
                        <th class="text-center">Nilai Akhir</th>
                        <th>Pelanggaran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result_sessions) > 0): ?>
                        <?php 
                        $no = 1; 
                        while ($s = mysqli_fetch_assoc($result_sessions)): 
                            $session_id = $s['id'];

                            // Hitung Statistik Jawaban Per Siswa
                            $answers_res = mysqli_query($koneksi, "SELECT sa.*, qo.is_correct 
                                                                  FROM student_answers sa 
                                                                  LEFT JOIN question_options qo ON sa.selected_option_id = qo.id 
                                                                  WHERE sa.exam_session_id = '$session_id'");
                            
                            $jml_benar = 0;
                            $jml_salah = 0;
                            $jml_ragu  = 0;
                            $jml_terjawab = 0;

                            while ($ans = mysqli_fetch_assoc($answers_res)) {
                                if ($ans['selected_option_id'] != NULL) {
                                    $jml_terjawab++;
                                    if ($ans['is_correct'] == 1) {
                                        $jml_benar++;
                                    } else {
                                        $jml_salah++;
                                    }
                                }
                                if ($ans['answer_status'] == 'ragu') {
                                    $jml_ragu++;
                                }
                            }

                            $jml_kosong = $total_soal - $jml_terjawab;
                            if ($jml_kosong < 0) $jml_kosong = 0;

                            // Kalkulasi Nilai Skala 100
                            $nilai_akhir = round(($jml_benar / $total_soal) * 100, 2);
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><strong><?= $s['full_name']; ?></strong></td>
                                <td><span class="badge bg-secondary"><?= $s['school_level']; ?> <?= $s['grade_name'] ? 'Kelas '.$s['grade_name'] : ''; ?></span></td>
                                <td><small class="text-muted"><?= date('d M Y, H:i', strtotime($s['start_time'])); ?> WIB</small></td>
                                <td class="text-center"><span class="badge bg-success fs-6"><?= $jml_benar; ?></span></td>
                                <td class="text-center"><span class="badge bg-danger fs-6"><?= $jml_salah; ?></span></td>
                                <td class="text-center"><span class="badge bg-warning text-dark fs-6"><?= $jml_ragu; ?></span></td>
                                <td class="text-center"><span class="badge bg-secondary fs-6"><?= $jml_kosong; ?></span></td>
                                <td class="text-center"><span class="badge bg-primary fs-6 p-2"><?= $nilai_akhir; ?></span></td>
                                <td>
                                    <?php if (!empty($s['violation_reason'])): ?>
                                        <span class="badge bg-danger p-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $s['violation_reason']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">Tidak Ada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">Belum ada peserta yang mengikuti ujian ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>