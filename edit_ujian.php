<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header("Location: index.php");
    exit();
}

$exam_id = (int)$_GET['id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title            = mysqli_real_escape_string($koneksi, $_POST['title']);
    $category         = $_POST['category'];
    $school_level     = $_POST['school_level'];
    $duration_minutes = (int)$_POST['duration_minutes'];
    $start_time       = $_POST['start_time'];
    $end_time         = $_POST['end_time'];

    $query = "UPDATE exams 
              SET title='$title', category='$category', school_level='$school_level', 
                  duration_minutes='$duration_minutes', start_time='$start_time', end_time='$end_time' 
              WHERE id='$exam_id'";
    
    if (mysqli_query($koneksi, $query)) {
        $message = "Ujian berhasil diperbarui!";
    }
}

$exam = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM exams WHERE id = '$exam_id'"));
if (!$exam) { header("Location: admin_dashboard.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Ujian - <?= $exam['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
<div class="container col-md-8">
    <div class="card shadow-sm border-0 rounded-4 p-4">
        <h4 class="fw-bold mb-3">Edit Pengaturan Ujian</h4>
        <?php if ($message): ?><div class="alert alert-success"><?= $message; ?></div><?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Judul Ujian</label>
                <input type="text" name="title" class="form-control" value="<?= $exam['title']; ?>" required>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Kategori</label>
                    <select name="category" class="form-select">
                        <option value="school_exam" <?= $exam['category']=='school_exam'?'selected':''; ?>>Ujian Sekolah</option>
                        <option value="competition" <?= $exam['category']=='competition'?'selected':''; ?>>Kompetisi</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Target Jenjang</label>
                    <select name="school_level" class="form-select">
                        <option value="MI" <?= $exam['school_level']=='MI'?'selected':''; ?>>MI</option>
                        <option value="MTs" <?= $exam['school_level']=='MTs'?'selected':''; ?>>MTs</option>
                        <option value="ALL" <?= $exam['school_level']=='ALL'?'selected':''; ?>>Semua</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Durasi (Menit)</label>
                    <input type="number" name="duration_minutes" class="form-control" value="<?= $exam['duration_minutes']; ?>" required>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Waktu Mulai</label>
                    <input type="datetime-local" name="start_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($exam['start_time'])); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Waktu Selesai</label>
                    <input type="datetime-local" name="end_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($exam['end_time'])); ?>" required>
                </div>
            </div>
            <div class="d-flex justify-content-between">
                <a href="admin_dashboard.php" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-primary fw-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>