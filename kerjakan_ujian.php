<?php
session_start();
require_once 'koneksi.php';

// Pastikan yang mengakses adalah siswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id'];$exam_id    = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Cek Keberadaan Ujian
$exam = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM exams WHERE id = '$exam_id'"));
if (!$exam) {
    header("Location: siswa_dashboard.php");
    exit();
}

// Cek Sesi Ujian Siswa (Apakah sudah ada atau buat baru)
$session_query = mysqli_query($koneksi, "SELECT * FROM exam_sessions WHERE exam_id = '$exam_id' AND student_id = '$student_id'");
$session       = mysqli_fetch_assoc($session_query);

if (!$session) {
    // Buat sesi pengerjaan baru
    $start_time = date('Y-m-d H:i:s');$end_time   = date('Y-m-d H:i:s', strtotime("+$exam[duration_minutes] minutes"));
    
    mysqli_query($koneksi, "INSERT INTO exam_sessions (exam_id, student_id, start_time, end_time, status) 
                            VALUES ('$exam_id', '$student_id', '$start_time', '$end_time', 'ongoing')");
    $exam_session_id = mysqli_insert_id($koneksi);
} else {
    // Jika sudah selesai sebelumnya, langsung lemparkan ke selesai_ujian.php
    if ($session['status'] == 'completed') {
        header("Location: selesai_ujian.php?session_id=" . $session['id']);
        exit();
    }
    $exam_session_id =$session['id'];
    $end_time        =$session['end_time'];
}

// Ambil Daftar Soal Ujian
$questions_query = mysqli_query($koneksi, "SELECT * FROM questions WHERE exam_id = '$exam_id' ORDER BY id ASC");
$questions       = [];
while ($q = mysqli_fetch_assoc($questions_query)) {
    // Ambil pilihan jawaban untuk setiap soal
    $q_id =$q['id'];
    $opts_query = mysqli_query($koneksi, "SELECT id, option_text, option_key FROM question_options WHERE question_id = '$q_id' ORDER BY option_key ASC");
    $options = [];
    while ($opt = mysqli_fetch_assoc($opts_query)) {
        $options[] =$opt;
    }
    $q['options'] =$options;
    
    // Ambil jawaban terkurasi siswa jika sudah pernah diisi sebelumnya
    $ans_query = mysqli_query($koneksi, "SELECT selected_option_id, answer_status FROM student_answers WHERE exam_session_id = '$exam_session_id' AND question_id = '$q_id'");
    $ans_data  = mysqli_fetch_assoc($ans_query);
    $q['user_answer_id'] =$ans_data['selected_option_id'] ?? null;
    $q['answer_status']  =$ans_data['answer_status'] ?? 'yakin';
    
    $questions[] =$q;
}

$total_soal = count($questions);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian: <?= htmlspecialchars($exam['title']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- MathJax for rendering LaTeX equations -->
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; user-select: none; }
        .timer-box { font-size: 1.25rem; font-weight: 700; color: #dc3545; }
        .nav-btn-soal { width: 45px; height: 45px; margin: 3px; font-weight: 600; border-radius: 8px; }
        .btn-status-ragu { background-color: #ffc107 !important; color: #000 !important; }
        .btn-status-yakin { background-color: #198754 !important; color: #fff !important; }
        .btn-status-kosong { background-color: #e9ecef !important; color: #212529 !important; }
        .option-label { cursor: pointer; padding: 12px 16px; border: 2px solid #dee2e6; border-radius: 10px; margin-bottom: 10px; transition: all 0.2s; }
        .option-label:hover { border-color: #0d6efd; background-color: #f8f9fa; }
        .form-check-input:checked + .option-label { border-color: #0d6efd; background-color: #e7f1ff; font-weight: 600; }
    </style>
</head>
<body class="p-3">

<div class="container-fluid">
    <!-- Topbar Header Ujian & Timer -->
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm mb-3">
        <div>
            <h5 class="fw-bold m-0 text-primary"><?= htmlspecialchars($exam['title']); ?></h5>
            <small class="text-muted">Peserta: <strong><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></strong></small>
        </div>
        <div class="d-flex align-items-center bg-light px-3 py-2 rounded-3 border">
            <i class="fa-solid fa-clock me-2 text-danger"></i>
            <span class="timer-box" id="timer">00:00:00</span>
        </div>
    </div>

    <div class="row g-3">
        <!-- Area Konten Soal (1 Soal per Tampilan) -->
        <div class="col-md-8 col-lg-9">
            <div class="card border-0 shadow-sm rounded-3 p-4">
                <?php foreach ($questions as $index =>$q): ?>
                    <div class="question-container" id="question_box_<?= $index; ?>" style="display: <?= $index === 0 ? 'block' : 'none'; ?>;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary fs-6">Soal No. <?= $index + 1; ?> / <?=$total_soal; ?></span>
                        </div>

                        <!-- Pertanyaan -->
                        <div class="fs-5 mb-4 text-dark"><?= $q['question_text']; ?></div>

                        <!-- Pilihan Jawaban -->
                        <div class="options-group mb-4">
                            <?php foreach ($q['options'] as$opt): ?>
                                <?php $isChecked = ($q['user_answer_id'] ==$opt['id']) ? 'checked' : ''; ?>
                                <div class="form-check p-0">
                                    <input class="form-check-input d-none" type="radio" 
                                           name="option_<?= $q['id']; ?>" 
                                           id="opt_<?= $opt['id']; ?>" 
                                           value="<?= $opt['id']; ?>" 
                                           <?= $isChecked; ?>
                                           onchange="simpanJawaban(<?= $q['id']; ?>, <?= $opt['id']; ?>, <?=$index; ?>)">
                                    <label class="form-check-label d-block option-label" for="opt_<?= $opt['id']; ?>">
                                        <strong class="me-2"><?= $opt['option_key']; ?>.</strong> <?=$opt['option_text']; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Checkbox / Toggle Status Ragu-ragu -->
                        <div class="form-check mb-4 bg-light p-3 rounded-3 border">
                            <input class="form-check-input ms-1" type="checkbox" id="ragu_<?= $index; ?>" 
                                   <?= $q['answer_status'] == 'ragu' ? 'checked' : ''; ?>
                                   onchange="toggleRagu(<?= $q['id']; ?>, <?=$index; ?>)">
                            <label class="form-check-label fw-bold text-warning ms-2" for="ragu_<?= $index; ?>">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Tandai Ragu-ragu
                            </label>
                        </div>

                        <!-- Navigasi Tombol Sebelum/Sesudah -->
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary px-4 rounded-pill fw-bold" 
                                    onclick="navigasiSoal(<?= $index - 1; ?>)" 
                                    <?= $index === 0 ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-arrow-left me-1"></i> Sebelumnya
                            </button>

                            <?php if ($index ===$total_soal - 1): ?>
                                <button type="button" class="btn btn-success px-4 rounded-pill fw-bold" onclick="konfirmasiSelesai()">
                                    <i class="fa-solid fa-check-circle me-1"></i> Selesai Ujian
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary px-4 rounded-pill fw-bold" onclick="navigasiSoal(<?= $index + 1; ?>)">
                                    Berikutnya <i class="fa-solid fa-arrow-right ms-1"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sidebar Grid Nomor Soal -->
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-grip me-2"></i>Navigasi Nomor Soal</h6>
                <div class="d-flex flex-wrap" id="grid_soal">
                    <?php foreach ($questions as $index =>$q): ?>
                        <?php 
                            $btnClass = 'btn-status-kosong';
                            if ($q['answer_status'] == 'ragu') {$btnClass = 'btn-status-ragu';
                            } elseif ($q['user_answer_id'] != null) {$btnClass = 'btn-status-yakin';
                            }
                        ?>
                        <button type="button" id="grid_btn_<?= $index; ?>" 
                                class="btn nav-btn-soal <?= $btnClass; ?>" 
                                onclick="navigasiSoal(<?= $index; ?>)">
                            <?= $index + 1; ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <hr class="my-3">
                <div class="small">
                    <span class="badge btn-status-yakin me-1">&nbsp;</span> Answered (Yakin)<br>
                    <span class="badge btn-status-ragu me-1">&nbsp;</span> Ragu-ragu<br>
                    <span class="badge btn-status-kosong border text-dark me-1">&nbsp;</span> Belum Dijawab
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Penanda aktifnya ujian (digunakan untuk mematikan kecurangan saat tombol Selesai ditekan)
let isExamActive = true;
let currentIndex = 0;
const totalSoal = <?= $total_soal; ?>;

// Navigasi Tampilan Soal
function navigasiSoal(targetIndex) {
    if (targetIndex < 0 || targetIndex >= totalSoal) return;
    document.getElementById(`question_box_${currentIndex}`).style.display = 'none';
    document.getElementById(`question_box_${targetIndex}`).style.display = 'block';
    currentIndex = targetIndex;
    
    // Re-render MathJax jika terdapat LaTeX pada soal
    if (window.MathJax) {
        MathJax.typesetPromise();
    }
}

// Simpan Jawaban via AJAX
function simpanJawaban(questionId, optionId, index) {
    let isRagu = document.getElementById(`ragu_${index}`).checked ? 'ragu' : 'yakin';
    
    let formData = new FormData();
    formData.append('exam_session_id', <?= $exam_session_id; ?>);
    formData.append('question_id', questionId);
    formData.append('selected_option_id', optionId);
    formData.append('answer_status', isRagu);

    fetch('simpan_jawaban.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        let gridBtn = document.getElementById(`grid_btn_${index}`);
        if (isRagu === 'ragu') {
            gridBtn.className = 'btn nav-btn-soal btn-status-ragu';
        } else {
            gridBtn.className = 'btn nav-btn-soal btn-status-yakin';
        }
    });
}

// Toggle Status Ragu-ragu
function toggleRagu(questionId, index) {
    let selectedOpt = document.querySelector(`input[name="option_${questionId}"]:checked`);
    let optId = selectedOpt ? selectedOpt.value : null;
    
    if (optId) {
        simpanJawaban(questionId, optId, index);
    } else {
        let isRagu = document.getElementById(`ragu_${index}`).checked ? 'ragu' : 'kosong';
        let gridBtn = document.getElementById(`grid_btn_${index}`);
        if (isRagu === 'ragu') {
            gridBtn.className = 'btn nav-btn-soal btn-status-ragu';
        } else {
            gridBtn.className = 'btn nav-btn-soal btn-status-kosong';
        }
    }
}

// Timer Hitung Mundur Sesuai Limit Database
const endTime = new Date("<?= date('Y-m-d H:i:s', strtotime($end_time)); ?>").getTime();
const timerInterval = setInterval(function() {
    const now = new Date().getTime();
    const distance = endTime - now;

    if (distance < 0) {
        clearInterval(timerInterval);
        isExamActive = false; // Matikan anti-kecurangan
        alert("Waktu pengerjaan telah habis! Jawaban Anda otomatis tersimpan.");
        window.location.href = "selesai_ujian.php?session_id=<?= $exam_session_id; ?>";
        return;
    }

    const hours   = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    document.getElementById("timer").innerHTML = 
        (hours < 10 ? "0" + hours : hours) + ":" + 
        (minutes < 10 ? "0" + minutes : minutes) + ":" + 
        (seconds < 10 ? "0" + seconds : seconds);
}, 1000);

// Konfirmasi Selesai Ujian
function konfirmasiSelesai() {
    if (confirm("Apakah Anda yakin ingin menyelesaikan ujian ini? Seluruh jawaban akan disimpan.")) {
        isExamActive = false; // MATIKAN DETEKSI KECURANGAN
        window.location.href = "selesai_ujian.php?session_id=<?= $exam_session_id; ?>";
    }
}

// =========================================================
// SISTEM STRICT ANTI-KECURANGAN (HANYA AKTIF SAAT UJIAN ACTIVE)
// =========================================================

// Minta Layar Fullscreen
function requestFullScreen() {
    let elem = document.documentElement;
    if (elem.requestFullscreen) { elem.requestFullscreen(); }
    else if (elem.webkitRequestFullscreen) { elem.webkitRequestFullscreen(); }
    else if (elem.msRequestFullscreen) { elem.msRequestFullscreen(); }
}

document.addEventListener("click", function() {
    if (isExamActive && !document.fullscreenElement) {
        requestFullScreen();
    }
});

// Prevent Tombol Back Browser
history.pushState(null, null, location.href);
window.onpopstate = function () {
    if (isExamActive) {
        history.go(1);
        catatPelanggaran("Menekan tombol kembali browser");
    }
};

// Deteksi Pindah Tab / Minimize Browser
document.addEventListener("visibilitychange", function() {
    if (isExamActive && document.hidden) {
        catatPelanggaran("Berpindah tab / meminimalkan browser");
    }
});

// Deteksi Keluar Fullscreen
document.addEventListener("fullscreenchange", function() {
    if (isExamActive && !document.fullscreenElement) {
        catatPelanggaran("Keluar dari mode layar penuh (Fullscreen)");
    }
});

// Fungsi Pencatatan Pelanggaran Otomatis
function catatPelanggaran(alasan) {
    if (!isExamActive) return; // Jangan catat jika ujian sudah selesai/selesai dikonfirmasi
    
    isExamActive = false; // Matikan deteksi langsung
    let formData = new FormData();
    formData.append('exam_session_id', <?= $exam_session_id; ?>);
    formData.append('reason', alasan);

    fetch('catat_pelanggaran.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        alert("PELANGGARAN TERDETEKSI!\n\nAnda terdeteksi: " + alasan + ".\nUjian Anda dihentikan otomatis.");
        window.location.href = "selesai_ujian.php?session_id=<?= $exam_session_id; ?>&violation=1";
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>