<?php
// Set zona waktu server ke Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

$student_id   = $_SESSION['user_id'];
$full_name    = $_SESSION['full_name'] ?? $_SESSION['username'];
$school_level = $_SESSION['school_level'] ?? 'MI';
$grade_level  = $_SESSION['grade_level'] ?? '1';

// Ambil data terbaru dari DB
$user_d = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$student_id'"));
$full_name    = $user_d['full_name'] ?: $user_d['username'];
$school_level = $user_d['school_level'] ?: 'MI';
$grade_level  = $user_d['grade_level'] ?: '1';

// Ambil daftar ujian beserta status pengerjaan siswa
$query_exam = "SELECT e.*, 
              (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) as total_soal,
              es.status as status_sesi, es.id as session_id
              FROM exams e 
              LEFT JOIN exam_sessions es ON es.exam_id = e.id AND es.student_id = '$student_id'
              WHERE e.school_level = '$school_level' OR e.school_level = 'ALL' 
              ORDER BY e.start_time ASC";
$result_exam = mysqli_query($koneksi, $query_exam);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ujian Siswa</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f3f6fc; color: #2c3e50; }
        .card-exam {
            border: none;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 6px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-exam:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.1);
        }
        .card-exam.completed-card {
            background: #e8fadf;
            border: 1px solid #b7eb8f;
        }
        .badge-tag {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .countdown-badge {
            background-color: #e6f7ff;
            color: #0958d9;
            border: 1px solid #91caff;
            border-radius: 10px;
            padding: 6px 10px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
        }
        .info-item {
            font-size: 0.82rem;
            margin-bottom: 4px;
        }
    </style>
</head>
<body class="py-4">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm py-3 mb-4 rounded-4 container">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold fs-6" href="#"><i class="fa-solid fa-graduation-cap me-2"></i>PORTAL UJIAN & KOMPETISI</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3 small fw-bold"><i class="fa-solid fa-user-circle me-1"></i> <?= $full_name; ?> (<?= $school_level; ?> Kelas <?= $grade_level; ?>)</span>
            <a href="logout.php" class="btn btn-light text-primary btn-sm rounded-pill fw-bold"><i class="fa-solid fa-right-from-bracket me-1"></i> Keluar</a>
        </div>
    </div>
</nav>

<div class="container mb-5">
    <div class="mb-4">
        <h4 class="fw-bold m-0">Daftar Ujian & Kompetisi</h4>
        <p class="text-muted small m-0">Pilih ujian atau kompetisi yang ingin dikerjakan.</p>
    </div>

    <div class="row g-4">
        <?php if (mysqli_num_rows($result_exam) > 0): ?>
            <?php while ($exam = mysqli_fetch_assoc($result_exam)): 
                $is_completed = ($exam['status_sesi'] == 'completed');
                $start_ts     = strtotime($exam['start_time']);
                $end_ts       = strtotime($exam['end_time']);
                $now_ts       = time();
                
                $hari_tgl     = date('D, d M Y', $start_ts);
                $jam_mulai    = date('H:i', $start_ts);
                $jam_selesai  = date('H:i', $end_ts);
            ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-exam h-100 p-3 <?= $is_completed ? 'completed-card' : ''; ?>">
                        <div>
                            <div class="d-flex align-items-center justify-content-between gap-1 mb-2">
                                <span class="badge-tag bg-primary text-white text-truncate" style="max-width: 60%;"><i class="fa-solid fa-book me-1"></i> <?= $exam['title']; ?></span>
                                <span class="badge-tag bg-info text-dark"><i class="fa-solid fa-layer-group me-1"></i> <?= strtoupper($exam['category']); ?></span>
                            </div>

                            <h6 class="fw-bold text-dark mb-3 text-truncate" title="<?= $exam['title']; ?>"><?= $exam['title']; ?></h6>

                            <?php if ($is_completed): ?>
                                <div class="alert alert-success py-1 px-2 small rounded-3 fw-bold mb-2 text-center">
                                    <i class="fa-solid fa-circle-check me-1"></i> Sudah Selesai Dikerjakan
                                </div>
                            <?php endif; ?>

                            <div class="text-secondary mb-3">
                                <div class="info-item"><i class="fa-regular fa-calendar text-primary me-1"></i> <strong>Tgl:</strong> <?= $hari_tgl; ?></div>
                                <div class="info-item"><i class="fa-regular fa-clock text-info me-1"></i> <strong>Jam:</strong> <?= $jam_mulai; ?> - <?= $jam_selesai; ?> WIB</div>
                                <div class="info-item"><i class="fa-solid fa-list-check text-success me-1"></i> <strong>Soal:</strong> <?= $exam['total_soal']; ?> Soal (<?= $exam['duration_minutes']; ?> Menit)</div>
                            </div>
                        </div>

                        <div class="border-top pt-2 mt-2" id="action_container_<?= $exam['id']; ?>">
                            <?php if ($is_completed): ?>
                                <button class="btn btn-outline-secondary w-100 rounded-pill btn-sm fw-bold" disabled><i class="fa-solid fa-lock me-1"></i> Selesai (1x Pengerjaan)</button>
                            <?php else: ?>
                                <div class="countdown-badge mb-2 d-none" id="cd_box_<?= $exam['id']; ?>">
                                    <i class="fa-solid fa-stopwatch me-1"></i> <span class="timer-text" id="timer_text_<?= $exam['id']; ?>" data-target="<?= $start_ts; ?>">Menghitung...</span>
                                </div>

                                <button id="btn_not_started_<?= $exam['id']; ?>" class="btn btn-secondary w-100 rounded-pill btn-sm fw-bold d-none" disabled><i class="fa-solid fa-clock me-1"></i> Belum Dimulai</button>

                                <button id="btn_start_modal_<?= $exam['id']; ?>" class="btn btn-success w-100 rounded-pill btn-sm fw-bold shadow-sm d-none" data-bs-toggle="modal" data-bs-target="#modalTataTertib<?= $exam['id']; ?>">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Kerjakan Ujian
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- MODAL TATA TERTIB SEBELUM MULAI -->
                <div class="modal fade" id="modalTataTertib<?= $exam['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0">
                            <div class="modal-header bg-primary text-white rounded-top-4">
                                <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-circle-exclamation me-2"></i>Tata Tertib Ujian</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <h6 class="fw-bold text-dark mb-3"><?= $exam['title']; ?></h6>
                                <ol class="small text-secondary mb-4">
                                    <li class="mb-2">Pastikan koneksi internet Anda stabil.</li>
                                    <li class="mb-2">Ujian ini hanya diperbolehkan dikerjakan <strong>1 kali saja</strong>.</li>
                                    <li class="mb-2">Anda dapat memilih status jawaban: <strong>Yakin, Ragu-ragu, atau Kosongkan</strong>.</li>
                                    <li class="mb-2">Waktu berjalan otomatis dan jawaban tersimpan secara berkala.</li>
                                    <li class="mb-2">Nilai hasil ujian hanya dapat dilihat oleh Admin/Guru.</li>
                                </ol>
                                <div class="form-check bg-light p-3 rounded-3 border mb-2">
                                    <input class="form-check-input" type="radio" id="agree_<?= $exam['id']; ?>" name="agree_terms">
                                    <label class="form-check-label small fw-bold text-dark" for="agree_<?= $exam['id']; ?>">
                                        Saya telah membaca & menyetujui seluruh tata tertib.
                                    </label>
                                </div>
                            </div>
                            <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4">
                                <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
                                <a href="kerjakan_ujian.php?id=<?= $exam['id']; ?>" id="btn_start_<?= $exam['id']; ?>" class="btn btn-success btn-sm rounded-pill fw-bold px-4 disabled">Mulai Sekarang <i class="fa-solid fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                document.getElementById('agree_<?= $exam['id']; ?>').addEventListener('change', function() {
                    if (this.checked) {
                        document.getElementById('btn_start_<?= $exam['id']; ?>').classList.remove('disabled');
                    }
                });
                </script>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fa-solid fa-folder-open fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Belum ada ujian yang tersedia untuk jenjang <?= $school_level; ?>.</h6>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Script Hitung Mundur Real-Time Berdasarkan Jam HP/Laptop Siswa
function updateTimers() {
    document.querySelectorAll('.timer-text').forEach(function(elem) {
        var examId = elem.id.replace('timer_text_', '');
        var targetTs = parseInt(elem.getAttribute('data-target')) * 1000;
        var now = new Date().getTime(); // Mengambil jam HP/Laptop lokal siswa
        var diff = targetTs - now;

        var cdBox = document.getElementById('cd_box_' + examId);
        var btnNotStarted = document.getElementById('btn_not_started_' + examId);
        var btnStartModal = document.getElementById('btn_start_modal_' + examId);

        if (diff <= 0) {
            // Waktu ujian sudah tiba/dibuka
            if (cdBox) cdBox.classList.add('d-none');
            if (btnNotStarted) btnNotStarted.classList.add('d-none');
            if (btnStartModal) btnStartModal.classList.remove('d-none');
        } else {
            // Belum dimulai (Tampilkan Hitung Mundur)
            if (cdBox) cdBox.classList.remove('d-none');
            if (btnNotStarted) btnNotStarted.classList.remove('d-none');
            if (btnStartModal) btnStartModal.classList.add('d-none');

            var days = Math.floor(diff / (1000 * 60 * 60 * 24));
            var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((diff % (1000 * 60)) / 1000);

            elem.innerHTML = (days > 0 ? days + " hr " : "") + hours + "j " + minutes + "m " + seconds + "s";
        }
    });
}

setInterval(updateTimers, 1000);
updateTimers();
</script>
</body>
</html>