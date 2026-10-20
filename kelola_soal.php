<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header("Location: index.php");
    exit();
}

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$exam    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM exams WHERE id = '$exam_id'"));

if (!$exam) {
    header("Location: admin_dashboard.php");
    exit();
}

$message = "";

// PROSES TAMBAH SOAL (MANUAL / LATEX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_soal'])) {
    $input_mode = $_POST['input_mode'];

    if ($input_mode == 'latex') {
        $raw_latex = $_POST['raw_latex'];
        preg_match_all('/\\\\item\\s+(.*?)\\s*\\\\begin\\{enumerate\\}\\[label=\\\\Alph\\*\\.\\](.*?)\\\\end\\{enumerate\\}/s', $raw_latex, $matches, PREG_SET_ORDER);

        $total_imported = 0;
        foreach ($matches as $item) {
            $question_text = mysqli_real_escape_string($koneksi, trim($item[1]));
            $options_block = $item[2];

            preg_match_all('/\\\\item\\s+(.*?)(?=\\\\item|$)/s', $options_block, $opt_matches);

            if (count($opt_matches[1]) >= 4) {
                mysqli_query($koneksi, "INSERT INTO questions (exam_id, question_text, question_type, score_weight) VALUES ('$exam_id', '$question_text', 'multiple_choice', '4.0')");
                $question_id = mysqli_insert_id($koneksi);

                for ($i = 0; $i < 4; $i++) {
                    $opt_text = mysqli_real_escape_string($koneksi, trim($opt_matches[1][$i]));
                    mysqli_query($koneksi, "INSERT INTO question_options (question_id, option_text, is_correct) VALUES ('$question_id', '$opt_text', 0)");
                }
                $total_imported++;
            }
        }
        $message = "Berhasil mengimpor $total_imported soal dari dokumen LaTeX!";

    } else {
        $question_text = mysqli_real_escape_string($koneksi, trim($_POST['question_text']));
        if (!empty($question_text)) {
            mysqli_query($koneksi, "INSERT INTO questions (exam_id, question_text, question_type, score_weight) VALUES ('$exam_id', '$question_text', 'multiple_choice', '4.0')");
            $question_id = mysqli_insert_id($koneksi);

            $options       = $_POST['options'];
            $correct_index = $_POST['correct_option'];

            foreach ($options as $index => $opt_text) {
                $opt_text_clean = mysqli_real_escape_string($koneksi, $opt_text);
                $is_correct     = ($index == $correct_index) ? 1 : 0;
                mysqli_query($koneksi, "INSERT INTO question_options (question_id, option_text, is_correct) VALUES ('$question_id', '$opt_text_clean', '$is_correct')");
            }
            $message = "Soal berhasil ditambahkan ke paket ujian!";
        }
    }
}

// PROSES HAPUS SOAL
if (isset($_GET['delete_qid'])) {
    $qid = (int)$_GET['delete_qid'];
    mysqli_query($koneksi, "DELETE FROM questions WHERE id = '$qid' AND exam_id = '$exam_id'");
    header("Location: kelola_soal.php?exam_id=" . $exam_id);
    exit();
}

// Ambil Seluruh Soal yang Sudah Diinput
$questions_list = mysqli_query($koneksi, "SELECT * FROM questions WHERE exam_id = '$exam_id' ORDER BY id ASC");
$total_soal     = mysqli_num_rows($questions_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Soal - <?= $exam['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0"><i class="fa-solid fa-file-circle-plus text-primary me-2"></i>Kelola Isi Soal Ujian</h3>
            <p class="text-muted m-0">Paket Ujian: <strong><?= $exam['title']; ?></strong> | Total Soal Terinput: <span class="badge bg-success fs-6"><?= $total_soal; ?> Soal</span></p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-success rounded-pill fw-bold px-4"><i class="fa-solid fa-circle-check me-1"></i> Selesai & Simpan Paket</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold rounded-3" role="alert">
            <i class="fa-solid fa-circle-check me-1"></i> <?= $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- SISIKAN KIRI: FORM INPUT SOAL (BISA BERKALI-KALI) -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-plus me-1"></i>Tambah Soal Baru</h5>
                
                <form action="" method="POST">
                    <input type="hidden" name="simpan_soal" value="1">
                    
                    <div class="mb-3">
                        <div class="form-check form-check-inline me-3">
                            <input class="form-check-input" type="radio" name="input_mode" id="modeManual" value="manual" checked onclick="toggleMode('manual')">
                            <label class="form-check-label fw-bold text-primary" for="modeManual">Manual</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="input_mode" id="modeLatex" value="latex" onclick="toggleMode('latex')">
                            <label class="form-check-label fw-bold text-success" for="modeLatex">Import Dokumen LaTeX</label>
                        </div>
                    </div>

                    <!-- CONTAINER MANUAL -->
                    <div id="manualContainer">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Pertanyaan Soal No. <?= $total_soal + 1; ?></label>
                            <textarea name="question_text" class="form-control" rows="3" placeholder="Tuliskan pertanyaan..."></textarea>
                        </div>
                        <label class="form-label fw-bold small">Pilihan Jawaban (Pilih Radio untuk Kunci Jawaban)</label>
                        <?php for ($i=0; $i<4; $i++): ?>
                            <div class="input-group mb-2">
                                <div class="input-group-text">
                                    <input type="radio" name="correct_option" value="<?= $i; ?>" <?= $i==0?'checked':''; ?>>
                                    <span class="ms-2 fw-bold"><?= chr(65+$i); ?></span>
                                </div>
                                <input type="text" name="options[]" class="form-control" placeholder="Pilihan <?= chr(65+$i); ?>">
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- CONTAINER LATEX -->
                    <div id="latexContainer" class="d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Tempelkan Kode Dokumen LaTeX Di Sini:</label>
                            <textarea name="raw_latex" class="form-control font-monospace" rows="8" placeholder="Tempelkan dokumen LaTeX..."></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm mt-3">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Soal & Tambah Lagi
                    </button>
                </form>
            </div>
        </div>

        <!-- SISIKAN KANAN: DAFTAR SOAL YANG SUDAH TERINPUT -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-list-check text-success me-2"></i>Daftar Soal Terinput dalam Paket</h5>
                
                <?php if ($total_soal > 0): ?>
                    <div class="accordion" id="accordionSoal">
                        <?php $no = 1; while ($q = mysqli_fetch_assoc($questions_list)): 
                            $qid = $q['id'];
                            $opts = mysqli_query($koneksi, "SELECT * FROM question_options WHERE question_id = '$qid'");
                        ?>
                            <div class="accordion-item mb-2 border rounded-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $qid; ?>">
                                        Soal #<?= $no++; ?>: <?= substr(strip_tags($q['question_text']), 0, 60); ?>...
                                    </button>
                                </h2>
                                <div id="collapse<?= $qid; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionSoal">
                                    <div class="accordion-body bg-light">
                                        <p class="fw-normal mb-3"><?= $q['question_text']; ?></p>
                                        <ul class="list-group mb-3">
                                            <?php $opt_char = 'A'; while ($o = mysqli_fetch_assoc($opts)): ?>
                                                <li class="list-group-item py-1 small <?= $o['is_correct']?'list-group-item-success fw-bold':''; ?>">
                                                    <?= $opt_char++; ?>. <?= $o['option_text']; ?> <?= $o['is_correct']?'<i class="fa-solid fa-check text-success ms-2"></i> (Kunci)':'' ?>
                                                </li>
                                            <?php endwhile; ?>
                                        </ul>
                                        <a href="kelola_soal.php?exam_id=<?= $exam_id; ?>&delete_qid=<?= $qid; ?>" class="btn btn-danger btn-sm rounded-pill" onclick="return confirm('Hapus soal ini?')"><i class="fa-solid fa-trash me-1"></i> Hapus Soal</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa-solid fa-file-circle-xmark fa-3x mb-3"></i>
                        <h6>Belum ada soal yang ditambahkan ke dalam paket ini.</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMode(mode) {
    if (mode === 'latex') {
        document.getElementById('latexContainer').classList.remove('d-none');
        document.getElementById('manualContainer').classList.add('d-none');
    } else {
        document.getElementById('latexContainer').classList.add('d-none');
        document.getElementById('manualContainer').classList.remove('d-none');
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>