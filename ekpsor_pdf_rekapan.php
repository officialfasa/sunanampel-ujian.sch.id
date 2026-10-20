<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header("Location: index.php");
    exit();
}

$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$exam    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM exams WHERE id = '$exam_id'"));

if (!$exam) { exit("Data ujian tidak ditemukan."); }

$total_soal_res = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM questions WHERE exam_id = '$exam_id'"));
$total_soal     = $total_soal_res['total'] > 0 ? $total_soal_res['total'] : 1;

$query_sessions = "SELECT es.*, u.full_name, u.school_level, g.grade_name
                   FROM exam_sessions es
                   JOIN users u ON es.student_id = u.id
                   LEFT JOIN grades g ON u.grade_level = g.id
                   WHERE es.exam_id = '$exam_id'
                   ORDER BY u.full_name ASC";
$result_sessions = mysqli_query($koneksi, $query_sessions);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan_Hasil_Ujian_<?= str_replace(' ', '_', $exam['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; background-color: #fff; color: #000; }
        .header-kop { border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="p-4" onload="window.print()">

<div class="no-print mb-3 text-end">
    <button onclick="window.print()" class="btn btn-primary fw-bold"><i class="fa-solid fa-print"></i> Cetak / Simpan PDF</button>
</div>

<div class="header-kop text-center">
    <h3 class="fw-bold text-uppercase m-0">LAPORAN HASIL REKAPAN UJIAN & KOMPETISI</h3>
    <h5 class="m-0">PORTAL UJIAN ONLINE MADRASAH IBTIDAIYAH & TSANAWIYAH</h5>
    <small>Alamat: Lingkungan Madrasah | Website: portal-ujian.sch.id</small>
</div>

<table class="table table-borderless mb-3">
    <tr>
        <td width="18%"><strong>Nama Event / Ujian</strong></td>
        <td width="2%">:</td>
        <td width="30%"><?= $exam['title']; ?></td>
        <td width="18%"><strong>Target Jenjang</strong></td>
        <td width="2%">:</td>
        <td width="30%"><?= $exam['school_level']; ?></td>
    </tr>
    <tr>
        <td><strong>Kategori</strong></td>
        <td>:</td>
        <td><?= strtoupper($exam['category']); ?></td>
        <td><strong>Total Soal</strong></td>
        <td>:</td>
        <td><?= $total_soal; ?> Soal</td>
    </tr>
</table>

<table class="table table-bordered align-middle">
    <thead class="table-light text-center">
        <tr>
            <th width="5%">No</th>
            <th>Nama Lengkap Siswa</th>
            <th width="15%">Jenjang / Kelas</th>
            <th width="10%">Benar</th>
            <th width="10%">Salah</th>
            <th width="10%">Ragu</th>
            <th width="10%">Kosong</th>
            <th width="12%">Nilai Akhir</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($result_sessions) > 0): ?>
            <?php 
            $no = 1; 
            while ($s = mysqli_fetch_assoc($result_sessions)): 
                $session_id = $s['id'];
                $answers_res = mysqli_query($koneksi, "SELECT sa.*, qo.is_correct 
                                                      FROM student_answers sa 
                                                      LEFT JOIN question_options qo ON sa.selected_option_id = qo.id 
                                                      WHERE sa.exam_session_id = '$session_id'");
                $jml_benar = 0; $jml_salah = 0; $jml_ragu = 0; $jml_terjawab = 0;
                while ($ans = mysqli_fetch_assoc($answers_res)) {
                    if ($ans['selected_option_id'] != NULL) {
                        $jml_terjawab++;
                        if ($ans['is_correct'] == 1) $jml_benar++; else $jml_salah++;
                    }
                    if ($ans['answer_status'] == 'ragu') $jml_ragu++;
                }
                $jml_kosong = $total_soal - $jml_terjawab; if ($jml_kosong < 0) $jml_kosong = 0;
                $nilai_akhir = round(($jml_benar / $total_soal) * 100, 2);
            ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><strong><?= $s['full_name']; ?></strong></td>
                    <td class="text-center"><?= $s['school_level']; ?> <?= $s['grade_name'] ? 'Kelas '.$s['grade_name'] : ''; ?></td>
                    <td class="text-center"><?= $jml_benar; ?></td>
                    <td class="text-center"><?= $jml_salah; ?></td>
                    <td class="text-center"><?= $jml_ragu; ?></td>
                    <td class="text-center"><?= $jml_kosong; ?></td>
                    <td class="text-center fw-bold"><?= $nilai_akhir; ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" class="text-center py-3">Belum ada data pengerjaan ujian.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="row mt-5">
    <div class="col-8"></div>
    <div class="col-4 text-center">
        <p>Mengetahui,<br><strong>Panitia Pelaksana Ujian</strong></p>
        <br><br><br>
        <p><strong>( _______________________ )</strong></p>
    </div>
</div>

</body>
</html>