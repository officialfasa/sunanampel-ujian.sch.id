<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$student_id = $_SESSION['user_id'];
$is_violation = isset($_GET['violation']) ? true : false;

// Tandai status ujian selesai di database
if ($session_id > 0) {
    mysqli_query($koneksi, "UPDATE exam_sessions SET status = 'completed', end_time = NOW() WHERE id = '$session_id' AND student_id = '$student_id'");
    $session = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT es.*, e.title FROM exam_sessions es JOIN exams e ON es.exam_id = e.id WHERE es.id = '$session_id'"));
} else {
    header("Location: siswa_dashboard.php");
    exit();
}

$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Selesai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f3f6fc; }
        .success-card {
            border: none;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }
        .icon-box {
            width: 90px;
            height: 90px;
            line-height: 90px;
            border-radius: 50%;
            margin: 0 auto 20px auto;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">

<div class="container col-md-6 col-lg-5">
    <div class="card success-card text-center p-4 p-md-5">
        
        <?php if ($is_violation): ?>
            <!-- Jika Dihentikan Karena Pelanggaran -->
            <div class="icon-box bg-danger-subtle text-danger fs-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">Ujian Telah Dihentikan</h4>
            <p class="text-muted small mb-4">
                Terdeteksi pelanggaran tata tertib selama pengerjaan. Seluruh jawaban yang sempat terisi telah otomatis tersimpan di sistem.
            </p>
        <?php else: ?>
            <!-- Jika Selesai Secara Normal (Centang Hijau & Ucapan Selamat) -->
            <div class="icon-box bg-success-subtle text-success fs-1">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h3 class="fw-bold text-dark mb-2">Selamat, <?= $full_name; ?>!</h3>
            <h6 class="text-primary fw-bold mb-3"><?= $session['title'] ?? 'Ujian Online'; ?></h6>
            
            <p class="text-secondary small mb-4">
                Terima kasih telah menyelesaikan ujian ini dengan jujur dan tepat waktu. Seluruh jawaban Anda telah berhasil tersimpan di sistem server.
            </p>

            <div class="alert alert-success border-0 rounded-4 p-3 small mb-4 text-start">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-star text-warning fa-2x me-3"></i>
                    <div>
                        <strong class="d-block text-dark">Harapan & Doa:</strong>
                        <span>Semoga mendapatkan hasil yang terbaik dan memuaskan sesuai dengan ikhtiar dan perjuangan belajar Anda!</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tombol Kembali ke Dashboard -->
        <a href="siswa_dashboard.php" class="btn btn-primary btn-lg rounded-pill fw-bold w-100 shadow-sm py-2">
            <i class="fa-solid fa-house me-2"></i>Kembali ke Dashboard Utama
        </a>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>