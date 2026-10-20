<?php
session_start();
require_once 'koneksi.php';

// Cek Akses Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$message = "";
$error   = "";

// PROSES TAMBAH GURU BARU
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_guru'])) {
    $username     = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password     = mysqli_real_escape_string($koneksi, trim($_POST['password']));
    $full_name    = mysqli_real_escape_string($koneksi, trim($_POST['full_name']));
    $school_level = mysqli_real_escape_string($koneksi, $_POST['school_level']);
    $grade_level  = mysqli_real_escape_string($koneksi, $_POST['grade_level']);

    // Gabungkan pilihan mapel (array) menjadi teks terpisah koma
    $subjects_arr = isset($_POST['subjects']) ? $_POST['subjects'] : [];
    if (!empty($_POST['subject_custom'])) {
        $subjects_arr[] = trim($_POST['subject_custom']);
    }
    $subject_str  = mysqli_real_escape_string($koneksi, implode(', ', array_unique($subjects_arr)));

    // Cek apakah username sudah ada
    $check_user = mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check_user) > 0) {
        $error = "Username '$username' sudah terdaftar! Gunakan username lain.";
    } else {
        // Simpan Akun Guru
        $query_insert = "INSERT INTO users (username, password, full_name, role, school_level, grade_level, subject, is_approved) 
                         VALUES ('$username', '$password', '$full_name', 'teacher', '$school_level', '$grade_level', '$subject_str', 1)";
        
        if (mysqli_query($koneksi, $query_insert)) {
            $message = "Berhasil menambahkan akun Guru baru beserta mapel yang diampu!";
        } else {
            $error = "Gagal menyimpan data guru: " . mysqli_error($koneksi);
        }
    }
}

// PROSES HAPUS GURU
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id = '$delete_id' AND role = 'teacher'");
    header("Location: kelola_guru.php");
    exit();
}

// Ambil data kelas
$grades_mi  = mysqli_query($koneksi, "SELECT * FROM grades WHERE school_level = 'MI' ORDER BY id ASC");
$grades_mts = mysqli_query($koneksi, "SELECT * FROM grades WHERE school_level = 'MTs' ORDER BY id ASC");

// AMBIL DAFTAR GURU
$query_guru = mysqli_query($koneksi, "SELECT u.*, g.grade_name 
                                       FROM users u 
                                       LEFT JOIN grades g ON u.grade_level = g.id 
                                       WHERE u.role = 'teacher' 
                                       ORDER BY u.id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Data Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0"><i class="fa-solid fa-chalkboard-user text-primary me-2"></i>Kelola Akun Guru</h3>
            <p class="text-muted m-0">Tambah & atur hak akses pengajar mata pelajaran dan kelas spesifik.</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill fw-bold"><i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success fw-bold rounded-3 alert-dismissible fade show"><i class="fa-solid fa-circle-check me-1"></i> <?= $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger fw-bold rounded-3 alert-dismissible fade show"><i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- FORM TAMBAH GURU -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-user-plus me-1"></i>Tambah Guru Baru</h5>
                
                <form action="" method="POST">
                    <input type="hidden" name="tambah_guru" value="1">

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lengkap Guru</label>
                        <input type="text" name="full_name" class="form-control" placeholder="Contoh: Ahmad Fauzi, S.Pd." required>
                    </div>

                    <!-- PILIHAN MULTI-MAPEL -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Mata Pelajaran yang Diampu (Bisa Pilih > 1)</label>
                        <div class="bg-light p-3 rounded-3 border">
                            <?php 
                            $default_mapel = ['Matematika', 'IPA', 'IPS', 'Bahasa Indonesia', 'Bahasa Inggris', 'Pancasila/PKn', 'Al-Qur\'an Hadis', 'Aqidah Akhlak', 'Fiqih', 'SKI', 'Bahasa Arab'];
                            foreach ($default_mapel as $m): 
                            ?>
                                <div class="form-check form-check-inline mb-2 me-3">
                                    <input class="form-check-input" type="checkbox" name="subjects[]" value="<?= $m; ?>" id="m_<?= md5($m); ?>">
                                    <label class="form-check-label small fw-bold" for="m_<?= md5($m); ?>"><?= $m; ?></label>
                                </div>
                            <?php endforeach; ?>
                            <hr class="my-2">
                            <input type="text" name="subject_custom" class="form-control form-control-sm mt-2" placeholder="+ Tambah Mapel Lain (Opsional)">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Jenjang Mengajar</label>
                            <select name="school_level" class="form-select">
                                <option value="MI">MI</option>
                                <option value="MTs">MTs</option>
                                <option value="ALL">Semua (MI & MTs)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Kelas Spesifik</label>
                            <select name="grade_level" class="form-select">
                                <option value="0">Semua Kelas</option>
                                <optgroup label="Jenjang MI">
                                    <?php while ($g = mysqli_fetch_assoc($grades_mi)): ?>
                                        <option value="<?= $g['id']; ?>">MI - Kelas <?= $g['grade_name']; ?></option>
                                    <?php endwhile; ?>
                                </optgroup>
                                <optgroup label="Jenjang MTs">
                                    <?php while ($g = mysqli_fetch_assoc($grades_mts)): ?>
                                        <option value="<?= $g['id']; ?>">MTs - Kelas <?= $g['grade_name']; ?></option>
                                    <?php endwhile; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Username Login</label>
                        <input type="text" name="username" class="form-control" placeholder="Username login" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Password Login</label>
                        <input type="password" name="password" class="form-control" placeholder="Password login" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm mt-2">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data Guru
                    </button>
                </form>
            </div>
        </div>

        <!-- TABEL DAFTAR GURU -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-list me-2 text-primary"></i>Daftar Guru Terdaftar</h5>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Guru</th>
                                <th>Username</th>
                                <th>Mapel yang Diampu</th>
                                <th>Jenjang/Kelas</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_guru && mysqli_num_rows($query_guru) > 0): ?>
                                <?php $no=1; while ($g = mysqli_fetch_assoc($query_guru)): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><strong><?= $g['full_name']; ?></strong></td>
                                        <td><code><?= $g['username']; ?></code></td>
                                        <td>
                                            <?php 
                                            if (!empty($g['subject'])) {
                                                $mapels = explode(',', $g['subject']);
                                                foreach ($mapels as $mpl) {
                                                    echo '<span class="badge bg-info text-dark me-1 mb-1">' . trim($mpl) . '</span>';
                                                }
                                            } else {
                                                echo '<span class="badge bg-secondary">Semua Mapel</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $g['school_level']; ?></span>
                                            <span class="badge bg-primary"><?= isset($g['grade_name']) && $g['grade_name'] ? 'Kelas ' . $g['grade_name'] : 'Semua Kelas'; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="kelola_guru.php?delete_id=<?= $g['id']; ?>" class="btn btn-danger btn-sm rounded-pill" onclick="return confirm('Hapus akun guru ini?');"><i class="fa-solid fa-trash me-1"></i> Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4"><i class="fa-solid fa-user-slash fa-2x mb-2 d-block"></i>Belum ada data guru terdaftar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>