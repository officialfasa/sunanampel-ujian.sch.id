<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

$student_id      = $_SESSION['user_id'];
$exam_session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

$query = "SELECT es.*, e.title, e.category 
          FROM exam_sessions es 
          JOIN exams e ON es.exam_id = e.id 
          WHERE es.id = '$exam_session_id' AND es.student_id = '$student_id'";
$res   = mysqli_query($koneksi, $query);
$data  = mysqli_fetch_assoc($res);

if (!$data) {
    header("Location: siswa_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Ujian - <?= $data['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 10px 20px rgba(25, 135, 84, 0.3);
        }
    </style>
</head>
<body class="d-flex align-items-center vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="card border-0 shadow-lg rounded-4 p-4">
                <div class="card-body">
                    <i class="fa-solid fa-circle-check fa-4x text-success mb-3"></i>
                    <h3 class="fw-bold text-dark">Ujian Selesai!</h3>
                    <p class="text-muted mb-4"><?= $data['title']; ?></p>

                    <div class="score-circle mb-4">
                        <div>
                            <h1 class="fw-bold m-0"><?= round($data['total_score'], 1); ?></h1>
                            <small>NILAI AKHIR</small>
                        </div>
                    </div>

                    <p class="small text-muted">Nilai Anda telah berhasil dikalkulasi dan tersimpan otomatis ke sistem.</p>

                    <a href="siswa_dashboard.php" class="btn btn-primary rounded-pill px-4 fw-bold mt-2">
                        <i class="fa-solid fa-house me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>