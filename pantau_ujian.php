<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' &&$_SESSION['role'] != 'teacher')) {
    header("Location: index.php");
    exit();
}

$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$exam    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM exams WHERE id = '$exam_id'"));

if (!$exam) {
    header("Location: admin_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pantau Ujian Live - <?= $exam['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .alert-card-danger {
            border-left: 5px solid #dc3545;
            background-color: #fff5f5;
        }
        .pulse-badge {
            animation: pulse-animation 1.5s infinite;
        }
        @keyframes pulse-animation {
            0% { box-shadow: 0 0 0 0px rgba(220, 53, 69, 0.7); }
            100% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
        }
    </style>
</head>
<body class="p-4">

<!-- Audio Sirine Notifikasi Pelanggaran -->
<audio id="alertSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0"><i class="fa-solid fa-desktop text-primary me-2"></i>Monitoring Live Ujian</h3>
            <p class="text-muted m-0">Ujian: <strong><?= $exam['title']; ?></strong> | Status: <span class="badge bg-success">Berlangsung Real-Time</span></p>
        </div>
        <div>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill fw-bold"><i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Dashboard</a>
        </div>
    </div>

    <!-- BANNER ALERT PELANGGARAN TERDETEKSI -->
    <div id="violationBanner" class="alert alert-danger d-none rounded-4 shadow-sm p-3 mb-4 border-0">
        <div class="d-flex align-items-center">
            <i class="fa-solid fa-triangle-exclamation fa-2x me-3 pulse-badge rounded-circle p-2 bg-danger text-white"></i>
            <div>
                <h5 class="fw-bold mb-1" id="bannerTitle">PERINGATAN KECURANGAN!</h5>
                <p class="m-0 small" id="bannerText">Terdeteksi siswa melanggar tata tertib pengerjaan ujian.</p>
            </div>
        </div>
    </div>

    <!-- TABEL MONITORING PESERTA -->
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0"><i class="fa-solid fa-users me-2 text-primary"></i>Daftar Aktivitas Peserta</h5>
            <span class="badge bg-danger rounded-pill px-3 py-2 fw-bold" id="totalViolationBadge"><i class="fa-solid fa-shield-halved me-1"></i> 0 Pelanggaran</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>Jenjang/Kelas</th>
                        <th>Waktu Mulai</th>
                        <th>Status Pengerjaan</th>
                        <th class="text-center">Indikasi Pelanggaran</th>
                    </tr>
                </thead>
                <tbody id="monitoringTableBody">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-2"></i> Memuat data monitoring...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const examId = <?= $exam_id; ?>;
let previousViolationCount = 0;

function loadMonitoringData() {
    fetch('cek_pelanggaran_realtime.php?exam_id=' + examId)
    .then(res => res.json())
    .then(resData => {
        if (resData.status === 'success') {
            const data = resData.data;
            const totalCount = resData.total_violations;

            document.getElementById('totalViolationBadge').innerHTML = `<i class="fa-solid fa-shield-halved me-1"></i> ${totalCount} Pelanggaran`;

            // Jika ada akumulasi pelanggaran baru, bunyikan alarm
            if (totalCount > previousViolationCount && previousViolationCount !== 0) {
                document.getElementById('alertSound').play().catch(e => console.log("Audio play blocked"));
                
                const banner = document.getElementById('violationBanner');
                banner.classList.remove('d-none');
                document.getElementById('bannerText').innerText = `Terdeteksi total ${totalCount} kejadian kecurangan pada ujian ini!`;
            }
            previousViolationCount = totalCount;

            // Render isi Tabel
            let html = '';
            if (data.length > 0) {
                data.forEach((item, index) => {
                    html += `
                        <tr class="alert-card-danger">
                            <td>${index + 1}</td>
                            <td><strong>${item.student_name}</strong></td>
                            <td><span class="badge bg-secondary">${item.grade_info}</span></td>
                            <td><span class="badge bg-info text-dark">Layar Terkunci / Selesai</span></td>
                            <td><span class="badge bg-danger"><i class="fa-solid fa-lock me-1"></i> Didiskualifikasi</span></td>
                            <td class="text-center">
                                <span class="badge bg-danger p-2 fs-6 shadow-sm pulse-badge">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Terdeteksi: ${item.violation_reason} (${item.violation_count}x)
                                </span>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = `
                    <tr>
                        <td colspan="6" class="text-center text-success py-4 fw-bold">
                            <i class="fa-solid fa-circle-check fa-2x mb-2 d-block"></i> Belum ada indikasi pelanggaran kecurangan yang terdeteksi.
                        </td>
                    </tr>
                `;
            }

            document.getElementById('monitoringTableBody').innerHTML = html;
        }
    });
}

// Jalankan pemantauan otomatis setiap 3 detik sekali
setInterval(loadMonitoringData, 3000);
loadMonitoringData();
</script>
</body>
</html>