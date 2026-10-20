<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header("Location: index.php");
    exit();
}

$message = "";
$grades_list = mysqli_query($koneksi, "SELECT * FROM grades ORDER BY school_level ASC, id ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title            = mysqli_real_escape_string($koneksi, trim($_POST['title']));
    $category         = $_POST['category'];
    $school_level     = $_POST['school_level'];
    $grade_id         = (int)$_POST['grade_id'];
    $duration_minutes = (int)$_POST['duration_minutes'];
    $start_time       = $_POST['start_time'];
    
    if (!empty($_POST['end_time'])) {
        $end_time = $_POST['end_time'];
    } else {
        $end_time = date('Y-m-d H:i:s', strtotime($start_time . " + {$duration_minutes} minutes"));
    }

    $created_by = $_SESSION['user_id'];

    // Simpan Paket Ujian Utama
    $query_exam = "INSERT INTO exams (title, category, school_level, grade_id, duration_minutes, start_time, end_time, created_by) 
                   VALUES ('$title', '$category', '$school_level', '$grade_id', '$duration_minutes', '$start_time', '$end_time', '$created_by')";
    
    if (mysqli_query($koneksi, $query_exam)) {
        $exam_id = mysqli_insert_id($koneksi);
        // Langsung arahkan ke halaman input & kelola soal
        header("Location: kelola_soal.php?exam_id=" . $exam_id);
        exit();
    } else {
        $message = "Gagal membuat paket ujian: " . mysqli_error($koneksi);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Paket Ujian Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body class="p-4">

<div class="container my-4 col-md-9">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-primary text-white p-4 rounded-top-4">
            <h4 class="fw-bold m-0"><i class="fa-solid fa-folder-plus me-2"></i>Langkah 1: Buat Paket Ujian Baru</h4>
        </div>
        <div class="card-body p-4">
            
            <?php if ($message): ?>
                <div class="alert alert-danger fw-bold rounded-3"><?= $message; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Judul / Nama Ujian</label>
                    <input type="text" name="title" class="form-control" placeholder="Contoh: UTS Matematika Kelas 5 MI" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Kategori Ujian</label>
                        <select name="category" class="form-select">
                            <option value="school_exam">Ujian Sekolah / UTS / UAS</option>
                            <option value="competition">Kompetisi / Lomba</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Target Jenjang</label>
                        <select name="school_level" class="form-select">
                            <option value="MI">MI (Madrasah Ibtidaiyah)</option>
                            <option value="MTs">MTs (Madrasah Tsanawiyah)</option>
                            <option value="ALL">Semua Jenjang / UMUM</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Target Kelas Spesifik</label>
                        <select name="grade_id" class="form-select">
                            <option value="0">Semua Kelas</option>
                            <?php while ($g = mysqli_fetch_assoc($grades_list)): ?>
                                <option value="<?= $g['id']; ?>"><?= $g['school_level']; ?> - <?= $g['grade_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Durasi Ujian (Menit)</label>
                        <input type="number" name="duration_minutes" class="form-control" value="90" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Waktu Mulai Ujian</label>
                        <input type="datetime-local" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Waktu Selesai Ujian</label>
                        <input type="datetime-local" name="end_time" class="form-control" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="fa-solid fa-arrow-left me-1"></i> Kembali</a>
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold px-5">
                        Lanjut ke Pengisian Soal <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>